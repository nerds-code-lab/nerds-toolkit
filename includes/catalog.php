<?php
/** Optional WooCommerce catalog behavior, independent of the active theme. */
defined( 'ABSPATH' ) || exit;

function nerds_lab_catalog_enabled(): bool {
 return class_exists( 'WooCommerce' ) && 'yes' === get_option( 'nerds_lab_catalog_mode', 'no' );
}

add_filter( 'woocommerce_get_sections_products', static function( $sections ) {
 $sections['nerds_lab_catalog'] = __( 'Κατάλογος προϊόντων', 'nerds-toolkit' );
 return $sections;
} );
add_filter( 'woocommerce_get_settings_products', static function( $settings, $section ) {
 if ( 'nerds_lab_catalog' !== $section ) { return $settings; }
 $forms = array( 0 => __( 'Επιλέξτε φόρμα Contact Form 7', 'nerds-toolkit' ) );
 foreach ( get_posts( array( 'post_type' => 'wpcf7_contact_form', 'numberposts' => -1 ) ) as $form ) { $forms[$form->ID] = $form->post_title; }
 return array(
  array( 'title' => __( 'Λειτουργία καταλόγου', 'nerds-toolkit' ), 'type' => 'title', 'id' => 'nerds_lab_catalog', 'desc' => __( 'Τα προϊόντα παραμένουν διαθέσιμα για περιήγηση. Η λειτουργία καταλόγου αποκρύπτει τις τιμές και απενεργοποιεί τις online αγορές. Οι αποθηκευμένες τιμές δεν διαγράφονται.', 'nerds-toolkit' ) ),
  array( 'title' => __( 'Κατάλογος αντί για eshop', 'nerds-toolkit' ), 'desc' => __( 'Χωρίς τιμές, καλάθι και checkout', 'nerds-toolkit' ), 'id' => 'nerds_lab_catalog_mode', 'type' => 'checkbox', 'default' => 'no' ),
  array( 'title' => __( 'Φόρμα προσφοράς (Contact Form 7)', 'nerds-toolkit' ), 'id' => 'nerds_lab_quote_cf7_id', 'type' => 'select', 'options' => $forms, 'default' => 0, 'desc' => __( 'Επεξεργαστείτε κείμενα, πεδία και email στο μενού Επικοινωνία. Διατηρήστε το πεδίο email για την αποθήκευση αιτημάτων.', 'nerds-toolkit' ) ),
  array( 'type' => 'sectionend', 'id' => 'nerds_lab_catalog' ),
 );
}, 10, 2 );

function nerds_lab_catalog_purchasable( $purchasable ): bool {
 return nerds_lab_catalog_enabled() ? false : (bool) $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'nerds_lab_catalog_purchasable', 100 );
add_filter( 'woocommerce_variation_is_purchasable', 'nerds_lab_catalog_purchasable', 100 );
add_filter( 'woocommerce_add_to_cart_validation', 'nerds_lab_catalog_purchasable', 100 );
add_filter( 'woocommerce_get_price_html', static fn( $html ) => nerds_lab_catalog_enabled() ? '' : $html, 100 );
add_filter( 'woocommerce_sale_flash', static fn( $html ) => nerds_lab_catalog_enabled() ? '' : $html, 100 );
add_filter( 'woocommerce_structured_data_product', static function( $data ) {
 if ( nerds_lab_catalog_enabled() ) { unset( $data['offers'] ); }
 return $data;
}, 100 );
add_filter( 'woocommerce_catalog_orderby', static function( $options ) {
 if ( nerds_lab_catalog_enabled() ) { unset( $options['price'], $options['price-desc'] ); }
 return $options;
} );

function nerds_lab_catalog_inquiry_url( $product ): string {
 $business = nerds_lab_get_business_settings();
 if ( ! empty( $business['email'] ) ) {
  $subject = sprintf( __( 'Πληροφορίες για: %s', 'nerds-toolkit' ), $product->get_name() );
  $body = sprintf( __( "Θα ήθελα πληροφορίες για το προϊόν: %1\$s\nΚωδικός: %2\$s\n%3\$s\n\nΠοσότητα / συσκευασία που με ενδιαφέρει:\n", 'nerds-toolkit' ), $product->get_name(), $product->get_sku(), $product->get_permalink() );
  return 'mailto:' . $business['email'] . '?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( $body );
 }
 $page = (int) get_option( 'nerds_lab_catalog_contact_page' );
 return $page && 'publish' === get_post_status( $page ) ? (string) get_permalink( $page ) : '';
}

function nerds_lab_catalog_inquiry(): void {
 global $product;
 if ( ! $product instanceof WC_Product || ! nerds_lab_catalog_enabled() ) { return; }
 nerds_lab_quote_form( $product );
}

add_action( 'wp', static function() {
 if ( ! nerds_lab_catalog_enabled() ) { return; }
 remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
 add_action( 'woocommerce_single_product_summary', 'nerds_lab_catalog_inquiry', 30 );
} );
add_filter( 'woocommerce_loop_add_to_cart_link', static function( $html, $product ) {
 if ( ! nerds_lab_catalog_enabled() ) { return $html; }
 return '<a class="button nl-product-details" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html__( 'Δείτε το προϊόν', 'nerds-toolkit' ) . '</a>';
}, 100, 2 );
add_action( 'template_redirect', static function() {
 if ( nerds_lab_catalog_enabled() && ( is_cart() || is_checkout() ) ) {
  wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
  exit;
 }
} );
add_action( 'woocommerce_check_cart_items', static function() {
 if ( nerds_lab_catalog_enabled() && WC()->cart && ! WC()->cart->is_empty() ) {
  wc_add_notice( __( 'Οι online αγορές δεν είναι διαθέσιμες. Επικοινωνήστε για τα προϊόντα που σας ενδιαφέρουν.', 'nerds-toolkit' ), 'error' );
 }
} );
add_action( 'woocommerce_checkout_process', static function() {
 if ( nerds_lab_catalog_enabled() ) {
  wc_add_notice( __( 'Οι online παραγγελίες δεν είναι διαθέσιμες σε λειτουργία καταλόγου.', 'nerds-toolkit' ), 'error' );
 }
} );
// Blocks and direct Store API calls must respect the same catalog mode as classic forms.
add_filter( 'rest_pre_dispatch', static function( $result, $server, $request ) {
 if ( nerds_lab_catalog_enabled() && preg_match( '#^/wc/store/v[0-9]+/(cart|checkout|batch)(/|$)#', $request->get_route() ) ) {
  return new WP_Error( 'nerds_lab_catalog_only', __( 'Ο κατάλογος δεν δέχεται online παραγγελίες.', 'nerds-toolkit' ), array( 'status' => 403 ) );
 }
 return $result;
}, 10, 3 );
add_filter( 'rest_post_dispatch', static function( $response, $server, $request ) {
 if ( ! nerds_lab_catalog_enabled() || ! preg_match( '#^/wc/store/v[0-9]+/products(?:/|$)#', $request->get_route() ) || ! $response instanceof WP_REST_Response ) { return $response; }
 $redact = static function( $value ) use ( &$redact ) {
  if ( is_object( $value ) ) { $value = (array) $value; }
  if ( ! is_array( $value ) ) { return $value; }
  foreach ( $value as $key => &$item ) {
   if ( in_array( $key, array( 'price', 'regular_price', 'sale_price', 'min_price', 'max_price' ), true ) ) { $item = ''; }
   elseif ( in_array( $key, array( 'price_range', 'raw_prices' ), true ) ) { $item = null; }
   elseif ( in_array( $key, array( 'is_purchasable', 'on_sale' ), true ) ) { $item = false; }
   else { $item = $redact( $item ); }
  }
  unset( $item );
  return $value;
 };
 $response->set_data( $redact( $response->get_data() ) );
 return $response;
}, 100, 3 );
add_filter( 'render_block', static function( $html, $block ) {
 return nerds_lab_catalog_enabled() && in_array( $block['blockName'] ?? '', array( 'woocommerce/mini-cart', 'woocommerce/product-price', 'woocommerce/product-button', 'woocommerce/add-to-cart-form' ), true ) ? '' : $html;
}, 100, 2 );

function nerds_lab_catalog_shortcode( $attributes ): string {
 if ( ! class_exists( 'WC_Shortcode_Products' ) ) { return ''; }
 $attributes = shortcode_atts( array( 'limit' => 6, 'columns' => 3, 'category' => '', 'carousel' => 'no', 'label' => 'Προϊόντα καταλόγου', 'source' => 'catalog', 'preview_product' => 0 ), $attributes, 'nerds_lab_catalog' );
 $query = array( 'limit' => max( 1, min( 12, (int) $attributes['limit'] ) ), 'columns' => max( 1, min( 4, (int) $attributes['columns'] ) ), 'category' => sanitize_text_field( $attributes['category'] ), 'orderby' => 'menu_order title', 'order' => 'ASC', 'visibility' => 'visible' );
 if ( 'related' === $attributes['source'] ) {
  $product_id = is_product() ? get_queried_object_id() : 0;
  // Preview selection is only used in an authenticated Elementor editor/preview.
  if ( ! $product_id && current_user_can( 'edit_posts' ) && class_exists( '\Elementor\Plugin' ) && ( \Elementor\Plugin::instance()->editor->is_edit_mode() || \Elementor\Plugin::instance()->preview->is_preview_mode() ) ) { $product_id = absint( $attributes['preview_product'] ); }
  if ( ! $product_id ) { return ''; }
  $ids = wc_get_related_products( $product_id, $query['limit'], array( $product_id ) );
  $ids = array_filter( $ids, static function( $id ) { $item = wc_get_product( $id ); return $item && $item->is_visible() && ! post_password_required( $id ); } );
  if ( ! $ids ) { return ''; }
  $query['ids'] = implode( ',', array_map( 'absint', $ids ) );
  unset( $query['category'] );
 }
 $shortcode = new WC_Shortcode_Products( $query );
 $image_size = static fn() => 'medium_large';
 add_filter( 'single_product_archive_thumbnail_size', $image_size );
 try { $content = $shortcode->get_content(); }
 finally { remove_filter( 'single_product_archive_thumbnail_size', $image_size ); }
 if ( 'yes' !== $attributes['carousel'] || ! str_contains( $content, 'class="products ' ) ) { return $content; }
 wp_enqueue_script( 'nerds-lab-carousel' );
 wp_enqueue_style( 'nerds-lab-carousel' );
 return '<section class="nl-product-carousel" aria-roledescription="carousel" aria-label="' . esc_attr( $attributes['label'] ) . '"><div class="nl-carousel-controls" hidden><span class="nl-carousel-status" aria-live="polite" aria-atomic="true"></span><button type="button" class="nl-carousel-prev" aria-label="' . esc_attr__( 'Προηγούμενα προϊόντα', 'nerds-toolkit' ) . '"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 5l-7 7 7 7M7 12h14"/></svg></button><button type="button" class="nl-carousel-next" aria-label="' . esc_attr__( 'Επόμενα προϊόντα', 'nerds-toolkit' ) . '"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M10 5l7 7-7 7M3 12h14"/></svg></button></div>' . $content . '</section>';
}
add_shortcode( 'nerds_lab_catalog', 'nerds_lab_catalog_shortcode' );

add_action( 'wp_enqueue_scripts', static function() {
 wp_register_script( 'nerds-lab-carousel', NERDS_TOOLKIT_URL . 'assets/carousel.js', array(), (string) filemtime( NERDS_TOOLKIT_PATH . 'assets/carousel.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
 wp_register_style( 'nerds-lab-carousel', NERDS_TOOLKIT_URL . 'assets/carousel.css', array(), (string) filemtime( NERDS_TOOLKIT_PATH . 'assets/carousel.css' ) );
 $post = get_post();
 if ( $post && ( has_shortcode( $post->post_content, 'nerds_lab_catalog' ) || str_contains( (string) get_post_meta( $post->ID, '_elementor_data', true ), 'nerds-lab-catalog' ) ) ) {
  wp_enqueue_style( 'nerds-lab-carousel' );
  wp_enqueue_script( 'nerds-lab-carousel' );
 }
}, 5 );
