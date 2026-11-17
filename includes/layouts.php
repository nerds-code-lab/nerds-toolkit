<?php
/** Reusable, administrator-managed layouts, with authenticated editor previews. */
defined( 'ABSPATH' ) || exit;

function nerds_lab_preview_layout_id(): int {
	// Read-only preview requests cannot change data; authorization is checked on the document.
	$id = isset( $_GET['elementor-preview'] ) ? absint( $_GET['elementor-preview'] ) : 0;
	if ( ! $id && isset( $_GET['preview'], $_GET['p'] ) && 'true' === $_GET['preview'] ) {
		$id = absint( $_GET['p'] );
	}
	if ( ! $id || ! current_user_can( 'manage_options' ) || 'nl_layout' !== get_post_type( $id ) ) {
		return 0;
	}
	return $id;
}

function nerds_lab_register_layouts(): void {
	$capabilities = array_fill_keys( array(
		'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts',
		'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts',
		'delete_published_posts', 'delete_others_posts', 'edit_private_posts',
		'edit_published_posts', 'create_posts',
	), 'manage_options' );
	register_post_type( 'nl_layout', array(
		'labels' => array(
			'name' => __( 'Site layouts', 'nerds-toolkit' ),
			'singular_name' => __( 'Site layout', 'nerds-toolkit' ),
			'add_new_item' => __( 'Add site layout', 'nerds-toolkit' ),
			'edit_item' => __( 'Edit site layout', 'nerds-toolkit' ),
			'not_found' => __( 'No layouts yet. Create a header or footer, then select it in Appearance settings.', 'nerds-toolkit' ),
		),
		'public' => false,
		'publicly_queryable' => nerds_lab_preview_layout_id() > 0,
		'show_ui' => true,
		'show_in_menu' => 'nerds-lab',
		'show_in_rest' => true,
		'show_in_nav_menus' => false,
		'exclude_from_search' => true,
		'has_archive' => false,
		'rewrite' => false,
		'query_var' => false,
		'capabilities' => $capabilities,
		'map_meta_cap' => false,
		'supports' => array( 'title', 'editor', 'revisions', 'elementor' ),
	) );
}

function nerds_lab_protect_layout_rest( $result, $server, $request ) {
	if ( preg_match( '#^/wp/v2/nl_layout(?:/|$)#', $request->get_route() ) && ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'rest_forbidden', __( 'You cannot access site layouts.', 'nerds-toolkit' ), array( 'status' => rest_authorization_required_code() ) );
	}
	return $result;
}

function nerds_lab_layout_preview_template( string $template ): string {
	$id = nerds_lab_preview_layout_id();
	if ( $id && is_singular( 'nl_layout' ) && get_queried_object_id() === $id ) {
		nocache_headers();
		return NERDS_TOOLKIT_PATH . 'templates/layout-preview.php';
	}
	return $template;
}

function nerds_lab_layout_robots( array $robots ): array {
	if ( nerds_lab_preview_layout_id() ) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}

/** Return public markup only; unpublished, invalid and recursive references return an empty string. */
function nerds_lab_render_layout( int $id ): string {
	static $rendering = array();
	$layout = get_post( $id );
	if ( $id < 1 || ! $layout || 'nl_layout' !== $layout->post_type || 'publish' !== $layout->post_status || ! empty( $layout->post_password ) || isset( $rendering[ $id ] ) ) {
		return '';
	}
	$rendering[ $id ] = true;
	$context = array();
	foreach ( array( 'post', 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages' ) as $key ) {
		$context[ $key ] = array( 'exists' => array_key_exists( $key, $GLOBALS ), 'value' => $GLOBALS[ $key ] ?? null );
	}
	try {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$elementor = \Elementor\Plugin::instance();
			$document = $elementor->documents->get( $id );
			if ( $document && $document->is_built_with_elementor() ) {
				return (string) $elementor->frontend->get_builder_content_for_display( $id, true );
			}
		}
		// Elementor manages its own document context and rejects rendering the current post as a template.
		$GLOBALS['post'] = $layout;
		setup_postdata( $layout );
		return (string) apply_filters( 'the_content', $layout->post_content );
	} finally {
		foreach ( $context as $key => $saved ) {
			if ( $saved['exists'] ) {
				$GLOBALS[ $key ] = $saved['value'];
			} else {
				unset( $GLOBALS[ $key ] );
			}
		}
		unset( $rendering[ $id ] );
	}
}
