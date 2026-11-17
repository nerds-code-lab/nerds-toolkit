<?php
/** Shared escaped renderers for native editor shortcodes and Elementor widgets. */
defined( 'ABSPATH' ) || exit;

function nerds_lab_register_shortcodes(): void {
	add_shortcode( 'nerds_lab_contact', 'nerds_lab_contact_shortcode' );
	add_shortcode( 'nerds_lab_menu', 'nerds_lab_menu_shortcode' );
	add_shortcode( 'nerds_lab_identity', 'nerds_lab_identity_shortcode' );
}

function nerds_lab_contact_shortcode( $attributes = array() ): string {
	$attributes = shortcode_atts( array( 'fields' => 'phone,email,address,hours,map_url', 'heading' => '' ), (array) $attributes, 'nerds_lab_contact' );
	return nerds_lab_contact_markup( explode( ',', (string) $attributes['fields'] ), (string) $attributes['heading'] );
}

function nerds_lab_contact_markup( array $fields, string $heading = '' ): string {
	$settings = nerds_lab_get_business_settings();
	$items = array();
	foreach ( array_unique( array_map( 'trim', array_filter( $fields, 'is_string' ) ) ) as $name ) {
		if ( empty( $settings[ $name ] ) ) {
			continue;
		}
		$value = $settings[ $name ];
		$items[] = match ( $name ) {
			'phone' => '<li><a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $value ) ) . '">' . esc_html( $value ) . '</a></li>',
			'email' => '<li><a href="' . esc_url( 'mailto:' . $value ) . '">' . esc_html( $value ) . '</a></li>',
			'address' => '<li><address>' . nl2br( esc_html( $value ) ) . '</address></li>',
			'hours' => '<li>' . nl2br( esc_html( $value ) ) . '</li>',
			'map_url' => '<li><a href="' . esc_url( $value ) . '">' . esc_html__( 'Get directions', 'nerds-toolkit' ) . '</a></li>',
			'facebook_url' => '<li><a href="' . esc_url( $value ) . '">Facebook</a></li>',
			'instagram_url' => '<li><a href="' . esc_url( $value ) . '">Instagram</a></li>',
			default => '',
		};
	}
	$items = array_filter( $items );
	if ( ! $items ) {
		return '';
	}
	$title = '' !== $heading ? '<h2 class="nl-contact__heading">' . esc_html( $heading ) . '</h2>' : '';
	return '<div class="nl-contact">' . $title . '<ul class="nl-contact__items">' . implode( '', $items ) . '</ul></div>';
}

function nerds_lab_menu_shortcode( $attributes = array() ): string {
	$attributes = shortcode_atts( array( 'menu' => 0, 'label' => __( 'Navigation', 'nerds-toolkit' ), 'mobile_toggle' => 'yes', 'toggle_label' => __( 'Menu', 'nerds-toolkit' ) ), (array) $attributes, 'nerds_lab_menu' );
	$id = absint( $attributes['menu'] );
	if ( ! $id || ! wp_get_nav_menu_object( $id ) ) {
		return '';
	}
	$menu_id = wp_unique_id( 'nl-menu-' );
	$menu = wp_nav_menu( array( 'menu' => $id, 'container' => false, 'echo' => false, 'fallback_cb' => false, 'menu_class' => 'nl-layout-menu', 'menu_id' => $menu_id, 'depth' => 3 ) );
	if ( ! $menu ) {
		return '';
	}
	$button = '';
	$navigation_attributes = '';
	if ( 'yes' === $attributes['mobile_toggle'] ) {
		nerds_lab_enqueue_menu_assets();
		$navigation_attributes = ' data-nl-navigation';
		$button = '<button type="button" class="nl-layout-menu-toggle" aria-controls="' . esc_attr( $menu_id ) . '" aria-expanded="true" hidden>' . esc_html( $attributes['toggle_label'] ) . '</button>';
	}
	return '<nav class="nl-layout-navigation" aria-label="' . esc_attr( $attributes['label'] ) . '"' . $navigation_attributes . '>' . $button . $menu . '</nav>';
}

function nerds_lab_identity_shortcode( $attributes = array() ): string {
	$attributes = shortcode_atts( array( 'show_tagline' => 'no' ), (array) $attributes, 'nerds_lab_identity' );
	$logo_id = absint( get_theme_mod( 'custom_logo' ) );
	$name = get_bloginfo( 'name' );
	$logo = $logo_id ? wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'nl-layout-logo', 'alt' => $name, 'loading' => 'eager' ) ) : '';
	$content = $logo ?: esc_html( $name );
	$output = '<div class="nl-layout-identity"><a href="' . esc_url( home_url( '/' ) ) . '" rel="home">' . $content . '</a>';
	if ( 'yes' === $attributes['show_tagline'] && get_bloginfo( 'description' ) ) {
		$output .= '<p>' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
	}
	return $output . '</div>';
}
