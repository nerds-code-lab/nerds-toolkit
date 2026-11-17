<?php
/** Widgets use one shared data source and the same renderers as the native editor. */
defined( 'ABSPATH' ) || exit;

class Nerds_Lab_Contact_Widget extends \Elementor\Widget_Base {
	public function get_name(): string { return 'nerds-lab-contact'; }
	public function get_title(): string { return __( 'Business contact', 'nerds-toolkit' ); }
	public function get_icon(): string { return 'eicon-info-circle'; }
	public function get_categories(): array { return array( 'general' ); }
	public function get_keywords(): array { return array( 'nerds', 'contact', 'phone', 'business' ); }
	protected function is_dynamic_content(): bool { return true; }

	protected function register_controls(): void {
		$this->start_controls_section( 'contact', array( 'label' => __( 'Contact information', 'nerds-toolkit' ) ) );
		$this->add_control( 'heading', array( 'label' => __( 'Heading', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '' ) );
		$options = array_map( static fn( $field ) => $field['label'], nerds_lab_business_fields() );
		$this->add_control( 'fields', array( 'label' => __( 'Show fields', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'options' => $options, 'default' => array( 'phone', 'email', 'address', 'hours', 'map_url' ) ) );
		$this->add_control( 'source_note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Edit the shared values under Nerds Lab → Business details in WordPress.', 'nerds-toolkit' ) ) );
		$this->end_controls_section();
		$this->start_controls_section( 'appearance', array( 'label' => __( 'Appearance', 'nerds-toolkit' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'text_color', array( 'label' => __( 'Text color', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .nl-contact, {{WRAPPER}} .nl-contact a' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'typography', 'selector' => '{{WRAPPER}} .nl-contact' ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		echo nerds_lab_contact_markup( is_array( $settings['fields'] ?? null ) ? $settings['fields'] : array(), (string) ( $settings['heading'] ?? '' ) ); // Escaped by shared renderer.
	}
}

class Nerds_Lab_Menu_Widget extends \Elementor\Widget_Base {
	public function get_name(): string { return 'nerds-lab-menu'; }
	public function get_title(): string { return __( 'WordPress menu', 'nerds-toolkit' ); }
	public function get_icon(): string { return 'eicon-nav-menu'; }
	public function get_categories(): array { return array( 'general' ); }
	public function get_style_depends(): array { return array( 'nerds-lab-menu' ); }
	public function get_script_depends(): array { return array( 'nerds-lab-menu' ); }
	protected function is_dynamic_content(): bool { return true; }

	protected function register_controls(): void {
		$options = array( 0 => __( 'Select a menu', 'nerds-toolkit' ) );
		foreach ( wp_get_nav_menus() as $menu ) {
			$options[ $menu->term_id ] = $menu->name;
		}
		$this->start_controls_section( 'navigation_section', array( 'label' => __( 'Navigation', 'nerds-toolkit' ) ) );
		$this->add_control( 'menu', array( 'label' => __( 'Menu', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $options, 'default' => 0 ) );
		$this->add_control( 'label', array( 'label' => __( 'Accessible label', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __( 'Main navigation', 'nerds-toolkit' ) ) );
		$this->add_control( 'mobile_toggle', array( 'label' => __( 'Collapsible menu on mobile', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'toggle_label', array( 'label' => __( 'Mobile button label', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __( 'Menu', 'nerds-toolkit' ), 'condition' => array( 'mobile_toggle' => 'yes' ) ) );
		$this->end_controls_section();
		$this->start_controls_section( 'appearance', array( 'label' => __( 'Appearance', 'nerds-toolkit' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'direction', array( 'label' => __( 'Direction', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => array( 'row' => __( 'Horizontal', 'nerds-toolkit' ), 'column' => __( 'Vertical', 'nerds-toolkit' ) ), 'default' => 'row', 'selectors' => array( '{{WRAPPER}} .nl-layout-menu' => 'display:flex;flex-wrap:wrap;gap:1em;list-style:none;padding:0;margin:0;flex-direction:{{VALUE}};' ) ) );
		$this->add_control( 'link_color', array( 'label' => __( 'Link color', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .nl-layout-menu a' => 'color:{{VALUE}};' ) ) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'typography', 'selector' => '{{WRAPPER}} .nl-layout-menu' ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		echo nerds_lab_menu_shortcode( array( 'menu' => $settings['menu'] ?? 0, 'label' => $settings['label'] ?? '', 'mobile_toggle' => $settings['mobile_toggle'] ?? 'yes', 'toggle_label' => $settings['toggle_label'] ?? __( 'Menu', 'nerds-toolkit' ) ) ); // WordPress escapes menu output; wrapper attributes are escaped.
	}
}

class Nerds_Lab_Identity_Widget extends \Elementor\Widget_Base {
	public function get_name(): string { return 'nerds-lab-identity'; }
	public function get_title(): string { return __( 'Site identity', 'nerds-toolkit' ); }
	public function get_icon(): string { return 'eicon-site-logo'; }
	public function get_categories(): array { return array( 'general' ); }
	protected function is_dynamic_content(): bool { return true; }

	protected function register_controls(): void {
		$this->start_controls_section( 'identity', array( 'label' => __( 'Site identity', 'nerds-toolkit' ) ) );
		$this->add_control( 'show_tagline', array( 'label' => __( 'Show tagline', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_responsive_control( 'logo_width', array( 'label' => __( 'Logo width', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 24, 'max' => 600 ), '%' => array( 'min' => 5, 'max' => 100 ) ), 'default' => array( 'unit' => 'px', 'size' => 160 ), 'selectors' => array( '{{WRAPPER}} .nl-layout-logo' => 'width:{{SIZE}}{{UNIT}};height:auto;max-width:100%;' ) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		echo nerds_lab_identity_shortcode( array( 'show_tagline' => $this->get_settings_for_display( 'show_tagline' ) ) ); // Escaped by shared renderer.
	}
}


/** Native controls for the dynamic WooCommerce collection; no paid widget needed. */
class Nerds_Lab_Catalog_Widget extends \Elementor\Widget_Base {
 public function get_name(): string { return 'nerds-lab-catalog'; }
 public function get_title(): string { return __( 'Προϊόντα καταλόγου', 'nerds-toolkit' ); }
 public function get_icon(): string { return 'eicon-products'; }
 public function get_categories(): array { return array( 'general' ); }
 public function get_script_depends(): array { return array( 'nerds-lab-carousel' ); }
 public function get_style_depends(): array {
  $styles = array( 'nerds-lab-carousel' );
  if ( wp_style_is( 'nerds-lab-woocommerce', 'registered' ) ) { $styles[] = 'nerds-lab-woocommerce'; }
  return $styles;
 }
 protected function is_dynamic_content(): bool { return true; }
 protected function register_controls(): void {
  $this->start_controls_section( 'collection', array( 'label' => __( 'Προϊόντα', 'nerds-toolkit' ) ) );
  $this->add_control( 'source', array( 'label' => __( 'Πηγή προϊόντων', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'catalog', 'options' => array( 'catalog' => 'Κατάλογος', 'related' => 'Παρόμοια με το τρέχον προϊόν' ) ) );
  $this->add_control( 'preview_product', array( 'label' => __( 'ID προϊόντος για προεπισκόπηση στον editor', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0, 'condition' => array( 'source' => 'related' ) ) );
  $this->add_responsive_control( 'columns', array( 'label' => __( 'Στήλες', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 4, 'default' => 3, 'tablet_default' => 2, 'mobile_default' => 1, 'selectors' => array( '{{WRAPPER}} .woocommerce ul.products' => 'grid-template-columns:repeat({{VALUE}},minmax(0,1fr));' ) ) );
  $this->add_control( 'limit', array( 'label' => __( 'Πλήθος προϊόντων', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 12, 'default' => 6 ) );
  $options = array( '' => __( 'Όλες οι κατηγορίες', 'nerds-toolkit' ) );
  if ( taxonomy_exists( 'product_cat' ) ) {
   $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
   if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { $options[$term->slug] = $term->name; } }
  }
  $this->add_control( 'category', array( 'label' => __( 'Κατηγορία', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $options, 'default' => '', 'condition' => array( 'source' => 'catalog' ) ) );
  $this->add_control( 'carousel', array( 'label' => __( 'Carousel', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
  $this->add_control( 'label', array( 'label' => __( 'Περιγραφή προσβασιμότητας', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Προϊόντα καταλόγου' ) );
  $this->end_controls_section();
  $this->start_controls_section( 'cards_style', array( 'label' => __( 'Κάρτες προϊόντων', 'nerds-toolkit' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
  $this->add_responsive_control( 'gap', array( 'label' => __( 'Απόσταση', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 8, 'max' => 64 ) ), 'selectors' => array( '{{WRAPPER}} .woocommerce ul.products' => 'gap:{{SIZE}}px;' ) ) );
  $this->add_control( 'title_color', array( 'label' => __( 'Χρώμα τίτλων', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .woocommerce-loop-product__title' => 'color:{{VALUE}};' ) ) );
  $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'card_typography', 'selector' => '{{WRAPPER}} .woocommerce-loop-product__title' ) );
  $this->add_control( 'image_fit', array( 'label' => __( 'Προσαρμογή εικόνας', 'nerds-toolkit' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'cover', 'options' => array( 'cover' => 'Κάλυψη', 'contain' => 'Ολόκληρη εικόνα' ), 'selectors' => array( '{{WRAPPER}} .woocommerce ul.products li.product a img' => 'object-fit:{{VALUE}};' ) ) );
  $this->end_controls_section();
 }
 protected function render(): void {
  echo nerds_lab_catalog_shortcode( $this->get_settings_for_display() ); // WooCommerce escapes cards; wrapper attributes escaped by shortcode.
 }
}
