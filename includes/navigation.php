<?php
/** Conditional assets for progressively enhanced layout navigation. */
defined( 'ABSPATH' ) || exit;

function nerds_lab_register_menu_assets(): void {
	wp_register_style( 'nerds-lab-menu', NERDS_TOOLKIT_URL . 'assets/navigation.css', array(), NERDS_TOOLKIT_VERSION );
	wp_register_script( 'nerds-lab-menu', NERDS_TOOLKIT_URL . 'assets/navigation.js', array(), NERDS_TOOLKIT_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}

function nerds_lab_enqueue_menu_assets(): void {
	if ( ! wp_style_is( 'nerds-lab-menu', 'registered' ) ) {
		nerds_lab_register_menu_assets();
	}
	wp_enqueue_style( 'nerds-lab-menu' );
	wp_enqueue_script( 'nerds-lab-menu' );
}

function nerds_lab_enqueue_document_menu_assets(): void {
	$ids = array_unique( array( get_queried_object_id(), absint( get_theme_mod( 'nl_header_layout' ) ), absint( get_theme_mod( 'nl_footer_layout' ) ) ) );
	foreach ( $ids as $id ) {
		$post = $id ? get_post( $id ) : null;
		if ( ! $post || ( 'publish' !== $post->post_status && nerds_lab_preview_layout_id() !== $id ) || $post->post_password ) {
			continue;
		}
		$data = get_post_meta( $id, '_elementor_data', true );
		$elementor_content = is_string( $data ) ? $data : '';
		if ( has_shortcode( $post->post_content, 'nerds_lab_menu' ) || str_contains( $elementor_content, 'nerds-lab-menu' ) || str_contains( $elementor_content, 'nerds_lab_menu' ) ) {
			nerds_lab_enqueue_menu_assets();
			return;
		}
	}
}

function nerds_lab_print_late_menu_styles(): void {
	// A menu inside a dynamically inserted block may first be discovered after wp_head.
	if ( wp_style_is( 'nerds-lab-menu', 'enqueued' ) && ! wp_style_is( 'nerds-lab-menu', 'done' ) ) {
		wp_print_styles( 'nerds-lab-menu' );
	}
}
