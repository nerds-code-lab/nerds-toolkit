<?php
/** Business settings use WordPress capabilities, nonces and sanitization. */
defined( 'ABSPATH' ) || exit;

function nerds_lab_business_fields(): array {
	return array(
		'phone' => array( 'label' => __( 'Phone', 'nerds-toolkit' ), 'type' => 'tel' ),
		'email' => array( 'label' => __( 'Email', 'nerds-toolkit' ), 'type' => 'email' ),
		'address' => array( 'label' => __( 'Address', 'nerds-toolkit' ), 'type' => 'textarea' ),
		'hours' => array( 'label' => __( 'Opening hours', 'nerds-toolkit' ), 'type' => 'textarea' ),
		'map_url' => array( 'label' => __( 'Directions URL', 'nerds-toolkit' ), 'type' => 'url' ),
		'facebook_url' => array( 'label' => __( 'Facebook URL', 'nerds-toolkit' ), 'type' => 'url' ),
		'instagram_url' => array( 'label' => __( 'Instagram URL', 'nerds-toolkit' ), 'type' => 'url' ),
	);
}

function nerds_lab_sanitize_business_settings( $input ): array {
	$input = is_array( $input ) ? $input : array();
	$output = array();
	foreach ( nerds_lab_business_fields() as $name => $field ) {
		$value = isset( $input[ $name ] ) && is_scalar( $input[ $name ] ) ? (string) $input[ $name ] : '';
		$output[ $name ] = match ( $field['type'] ) {
			'email' => sanitize_email( $value ),
			'url' => esc_url_raw( $value, array( 'http', 'https' ) ),
			'textarea' => sanitize_textarea_field( $value ),
			default => sanitize_text_field( $value ),
		};
	}
	return $output;
}

function nerds_lab_get_business_settings(): array {
	return nerds_lab_sanitize_business_settings( get_option( 'nerds_lab_business', array() ) );
}

function nerds_lab_register_business_settings(): void {
	register_setting( 'nerds_lab_business', 'nerds_lab_business', array(
		'type' => 'array',
		'sanitize_callback' => 'nerds_lab_sanitize_business_settings',
		'default' => array(),
		'show_in_rest' => false,
	) );
	add_settings_section( 'nerds_lab_contact', __( 'Business details', 'nerds-toolkit' ), '__return_false', 'nerds-lab-business' );
	foreach ( nerds_lab_business_fields() as $name => $field ) {
		add_settings_field( $name, $field['label'], 'nerds_lab_business_field', 'nerds-lab-business', 'nerds_lab_contact', array_merge( $field, array( 'name' => $name, 'label_for' => 'nl-' . $name ) ) );
	}
}

function nerds_lab_business_field( array $field ): void {
	$value = nerds_lab_get_business_settings()[ $field['name'] ];
	$name = 'nerds_lab_business[' . $field['name'] . ']';
	if ( 'textarea' === $field['type'] ) {
		printf( '<textarea id="%s" name="%s" class="large-text" rows="3">%s</textarea>', esc_attr( $field['label_for'] ), esc_attr( $name ), esc_textarea( $value ) );
		return;
	}
	printf( '<input id="%s" name="%s" type="%s" value="%s" class="regular-text">', esc_attr( $field['label_for'] ), esc_attr( $name ), esc_attr( $field['type'] ), esc_attr( $value ) );
}

function nerds_lab_register_admin_menu(): void {
	add_menu_page( __( 'Nerds Toolkit', 'nerds-toolkit' ), __( 'Nerds Toolkit', 'nerds-toolkit' ), 'manage_options', 'nerds-lab', 'nerds_toolkit_dashboard_page', 'dashicons-layout', 59 );
	add_submenu_page( 'nerds-lab', __( 'Dashboard', 'nerds-toolkit' ), __( 'Dashboard', 'nerds-toolkit' ), 'manage_options', 'nerds-lab', 'nerds_toolkit_dashboard_page' );
	add_submenu_page( 'nerds-lab', __( 'Business details', 'nerds-toolkit' ), __( 'Business details', 'nerds-toolkit' ), 'manage_options', 'nerds-lab-business', 'nerds_lab_settings_page' );
}

function nerds_lab_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Site settings', 'nerds-toolkit' ); ?></h1>
		<p><?php esc_html_e( 'Update these details once. Contact widgets and shortcodes use the same information throughout your site.', 'nerds-toolkit' ); ?></p>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'Appearance settings', 'nerds-toolkit' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=nl_layout' ) ); ?>"><?php esc_html_e( 'Header and footer layouts', 'nerds-toolkit' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Navigation menus', 'nerds-toolkit' ); ?></a>
		</p>
		<?php settings_errors(); ?>
		<form action="options.php" method="post">
			<?php settings_fields( 'nerds_lab_business' ); ?>
			<?php do_settings_sections( 'nerds-lab-business' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
