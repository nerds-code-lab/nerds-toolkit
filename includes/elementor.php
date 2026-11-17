<?php
/** Elementor is optional; no Elementor class is loaded until its register hook runs. */
defined( 'ABSPATH' ) || exit;

function nerds_lab_register_elementor_widgets( $manager ): void {
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}
	require_once NERDS_TOOLKIT_PATH . 'includes/widgets.php';
	$manager->register( new Nerds_Lab_Contact_Widget() );
	$manager->register( new Nerds_Lab_Menu_Widget() );
	$manager->register( new Nerds_Lab_Identity_Widget() );
	$manager->register( new Nerds_Lab_Catalog_Widget() );
}

function nerds_lab_enqueue_layout_assets(): void {
	if ( ! class_exists( '\Elementor\Plugin' ) || ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
		return;
	}
	foreach ( array_unique( array( absint( get_theme_mod( 'nl_header_layout' ) ), absint( get_theme_mod( 'nl_footer_layout' ) ) ) ) as $id ) {
		$layout = get_post( $id );
		if ( ! $id || ! $layout || 'nl_layout' !== $layout->post_type || 'publish' !== $layout->post_status || $layout->post_password ) {
			continue;
		}
		$elementor = \Elementor\Plugin::instance();
		$document = $elementor->documents->get( $id );
		if ( $document && $document->is_built_with_elementor() ) {
			$elementor->frontend->enqueue_styles();
			$elementor->frontend->enqueue_scripts();
			\Elementor\Core\Files\CSS\Post::create( $id )->enqueue();
		}
	}
}
