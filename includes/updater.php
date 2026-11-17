<?php
/** Native updater backed by the public manifest in the canonical GitHub repository. */
defined( 'ABSPATH' ) || exit;

function nerds_toolkit_release_metadata(): ?array {
	$cached = get_site_transient( 'nerds_toolkit_github_release' );
	if ( is_array( $cached ) ) { return $cached; }
	$response = wp_remote_get( 'https://raw.githubusercontent.com/nerds-code-lab/nerds-toolkit/main/update.json', array(
		'timeout' => 10,
		'headers' => array( 'Accept' => 'application/json', 'User-Agent' => 'Nerds-Toolkit/' . NERDS_TOOLKIT_VERSION ),
	) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return null; }
	$manifest = json_decode( wp_remote_retrieve_body( $response ), true );
	$version = sanitize_text_field( $manifest['version'] ?? '' );
	$package = esc_url_raw( $manifest['package'] ?? '' );
	if ( ! preg_match( '/^\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?$/', $version ) || '' === $package ) { return null; }
	$metadata = array(
		'version' => $version,
		'package' => $package,
		'url' => esc_url_raw( $manifest['url'] ?? 'https://github.com/nerds-code-lab/nerds-toolkit' ),
		'requires' => sanitize_text_field( $manifest['requires'] ?? '6.6' ),
		'requires_php' => sanitize_text_field( $manifest['requires_php'] ?? '8.2' ),
		'tested' => sanitize_text_field( $manifest['tested'] ?? get_bloginfo( 'version' ) ),
	);
	set_site_transient( 'nerds_toolkit_github_release', $metadata, 6 * HOUR_IN_SECONDS );
	return $metadata;
}

function nerds_toolkit_check_for_updates( $transient ) {
	if ( ! is_object( $transient ) ) { $transient = new stdClass(); }
	$plugin = plugin_basename( NERDS_TOOLKIT_PATH . 'nerds-toolkit.php' );
	$release = nerds_toolkit_release_metadata();
	if ( $release && version_compare( $release['version'], NERDS_TOOLKIT_VERSION, '>' ) ) {
		$transient->response[ $plugin ] = (object) array(
			'id' => 'https://github.com/nerds-code-lab/nerds-toolkit',
			'slug' => 'nerds-toolkit',
			'plugin' => $plugin,
			'new_version' => $release['version'],
			'url' => $release['url'],
			'package' => $release['package'],
			'requires' => $release['requires'],
			'requires_php' => $release['requires_php'],
			'tested' => $release['tested'],
		);
	}
	return $transient;
}
add_filter( 'pre_set_site_transient_update_plugins', 'nerds_toolkit_check_for_updates' );

function nerds_toolkit_plugin_information( $result, string $action, $arguments ) {
	if ( 'plugin_information' !== $action || 'nerds-toolkit' !== ( $arguments->slug ?? '' ) ) { return $result; }
	$release = nerds_toolkit_release_metadata();
	if ( ! $release ) { return $result; }
	return (object) array(
		'name' => 'Nerds Toolkit',
		'slug' => 'nerds-toolkit',
		'version' => $release['version'],
		'author' => '<a href="https://github.com/nerds-code-lab">Nerds Lab</a>',
		'homepage' => $release['url'],
		'requires' => $release['requires'],
		'requires_php' => $release['requires_php'],
		'download_link' => $release['package'],
		'sections' => array( 'description' => __( 'Reusable WordPress site layouts, shared business data, accessible navigation and optional integrations.', 'nerds-toolkit' ) ),
	);
}
add_filter( 'plugins_api', 'nerds_toolkit_plugin_information', 20, 3 );
