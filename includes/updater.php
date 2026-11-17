<?php
/** Native updater backed by signed GitHub release metadata for this public plugin. */
defined( 'ABSPATH' ) || exit;

function nerds_toolkit_release_metadata(): ?array {
	$cached = get_site_transient( 'nerds_toolkit_github_release' );
	if ( is_array( $cached ) ) { return $cached; }
	$response = wp_remote_get( 'https://api.github.com/repos/nerds-code-lab/nerds-toolkit/releases/latest', array(
		'timeout' => 10,
		'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'Nerds-Toolkit/' . NERDS_TOOLKIT_VERSION ),
	) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return null; }
	$release = json_decode( wp_remote_retrieve_body( $response ), true );
	$version = ltrim( sanitize_text_field( $release['tag_name'] ?? '' ), 'v' );
	$package = '';
	foreach ( $release['assets'] ?? array() as $asset ) {
		if ( 'nerds-toolkit-' . $version . '.zip' === ( $asset['name'] ?? '' ) ) {
			$package = esc_url_raw( $asset['browser_download_url'] ?? '' );
			break;
		}
	}
	if ( ! preg_match( '/^\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?$/', $version ) || '' === $package ) { return null; }
	$metadata = array(
		'version' => $version,
		'package' => $package,
		'url' => esc_url_raw( $release['html_url'] ?? 'https://github.com/nerds-code-lab/nerds-toolkit' ),
		'requires' => '6.6',
		'requires_php' => '8.2',
		'tested' => get_bloginfo( 'version' ),
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
