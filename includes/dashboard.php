<?php
/** Theme-declared plugin recommendations and their native WordPress actions. */
defined( 'ABSPATH' ) || exit;

/**
 * Return the normalized plugins declared by the active theme or another integration.
 *
 * @return array<string,array{name:string,file:string,required:bool,description:string}>
 */
function nerds_toolkit_recommended_plugins(): array {
	$plugins = apply_filters( 'nerds_toolkit_recommended_plugins', array() );
	if ( ! is_array( $plugins ) ) {
		return array();
	}

	$normalized = array();
	foreach ( $plugins as $slug => $plugin ) {
		if ( ! is_string( $slug ) || ! is_array( $plugin ) ) {
			continue;
		}
		$slug = sanitize_key( $slug );
		$file = isset( $plugin['file'] ) && is_string( $plugin['file'] ) ? plugin_basename( $plugin['file'] ) : '';
		if ( '' === $slug || '' === $file || ! str_contains( $file, '/' ) ) {
			continue;
		}
		$normalized[ $slug ] = array(
			'name' => sanitize_text_field( $plugin['name'] ?? $slug ),
			'file' => $file,
			'required' => ! empty( $plugin['required'] ),
			'description' => sanitize_text_field( $plugin['description'] ?? '' ),
		);
	}
	return $normalized;
}

/** @return array{status:string,label:string,url:string} */
function nerds_toolkit_plugin_action( string $slug, array $plugin, array $installed ): array {
	$file = $plugin['file'];
	if ( is_plugin_active( $file ) ) {
		return array( 'status' => 'active', 'label' => __( 'Active', 'nerds-toolkit' ), 'url' => '' );
	}
	if ( isset( $installed[ $file ] ) ) {
		$url = current_user_can( 'activate_plugins' )
			? wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $file ) ), 'activate-plugin_' . $file )
			: '';
		return array( 'status' => 'inactive', 'label' => __( 'Activate', 'nerds-toolkit' ), 'url' => $url );
	}
	$url = current_user_can( 'install_plugins' )
		? wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $slug ) ), 'install-plugin_' . $slug )
		: '';
	return array( 'status' => 'missing', 'label' => __( 'Install', 'nerds-toolkit' ), 'url' => $url );
}

function nerds_toolkit_dashboard_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$plugins = nerds_toolkit_recommended_plugins();
	$installed = get_plugins();
	?>
	<div class="wrap nerds-toolkit-dashboard">
		<h1><?php esc_html_e( 'Nerds Toolkit', 'nerds-toolkit' ); ?></h1>
		<p><?php esc_html_e( 'Review the plugins recommended by the active theme and complete the site setup using WordPress-native installation actions.', 'nerds-toolkit' ); ?></p>
		<?php if ( array() === $plugins ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'The active theme has not declared any additional plugins.', 'nerds-toolkit' ); ?></p></div>
		<?php else : ?>
			<table class="widefat striped" aria-label="<?php esc_attr_e( 'Recommended plugins', 'nerds-toolkit' ); ?>">
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Plugin', 'nerds-toolkit' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Purpose', 'nerds-toolkit' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Priority', 'nerds-toolkit' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'nerds-toolkit' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $plugins as $slug => $plugin ) :
					$action = nerds_toolkit_plugin_action( $slug, $plugin, $installed );
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $plugin['name'] ); ?></th>
						<td><?php echo esc_html( $plugin['description'] ); ?></td>
						<td><?php echo esc_html( $plugin['required'] ? __( 'Required', 'nerds-toolkit' ) : __( 'Recommended', 'nerds-toolkit' ) ); ?></td>
						<td>
							<?php if ( 'active' === $action['status'] ) : ?>
								<span aria-label="<?php echo esc_attr( sprintf( __( '%s is active', 'nerds-toolkit' ), $plugin['name'] ) ); ?>">✓ <?php echo esc_html( $action['label'] ); ?></span>
							<?php elseif ( '' !== $action['url'] ) : ?>
								<a class="button button-secondary" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['label'] ); ?></a>
							<?php else : ?>
								<?php esc_html_e( 'Administrator access required', 'nerds-toolkit' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php do_action( 'nerds_toolkit_dashboard_after' ); ?>
	</div>
	<?php
}
