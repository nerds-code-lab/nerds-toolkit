<?php
/** Native updater backed by the public manifest in the canonical GitHub repository. */
defined( 'ABSPATH' ) || exit;

defined( 'NERDS_TOOLKIT_UPDATE_MANIFEST_URL' ) || define( 'NERDS_TOOLKIT_UPDATE_MANIFEST_URL', 'https://raw.githubusercontent.com/nerds-code-lab/nerds-toolkit/main/update.json' );
defined( 'NERDS_TOOLKIT_UPDATE_CACHE_KEY' ) || define( 'NERDS_TOOLKIT_UPDATE_CACHE_KEY', 'nerds_toolkit_github_release' );
defined( 'NERDS_TOOLKIT_MAX_PACKAGE_BYTES' ) || define( 'NERDS_TOOLKIT_MAX_PACKAGE_BYTES', 10 * MB_IN_BYTES );

/** Only canonical, HTTPS, versioned release ZIPs may reach the upgrader. */
function nerds_toolkit_release_package_is_allowed( string $package, string $version = '' ): bool {
	$parts = wp_parse_url( $package );
	if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || 'raw.githubusercontent.com' !== ( $parts['host'] ?? '' ) ) {
		return false;
	}
	foreach ( array( 'port', 'user', 'pass', 'query', 'fragment' ) as $forbidden_part ) {
		if ( array_key_exists( $forbidden_part, $parts ) ) { return false; }
	}
	$path = $parts['path'] ?? '';
	if ( '' !== $version ) {
		return '/nerds-code-lab/nerds-toolkit/main/releases/nerds-toolkit-' . $version . '.zip' === $path;
	}
	return 1 === preg_match( '#^/nerds-code-lab/nerds-toolkit/main/releases/nerds-toolkit-\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?\.zip$#D', $path );
}

/** @return array{version:string,package:string,sha256:string,url:string,requires:string,requires_php:string,tested:string}|null */
function nerds_toolkit_normalize_release_manifest( array $manifest ): ?array {
	$version = sanitize_text_field( $manifest['version'] ?? '' );
	$package = esc_url_raw( $manifest['package'] ?? '', array( 'https' ) );
	$sha256 = strtolower( sanitize_text_field( $manifest['sha256'] ?? '' ) );
	if (
		! preg_match( '/^\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?$/D', $version ) ||
		! nerds_toolkit_release_package_is_allowed( $package, $version ) ||
		! preg_match( '/^[a-f0-9]{64}$/D', $sha256 )
	) {
		return null;
	}
	return array(
		'version' => $version,
		'package' => $package,
		'sha256' => $sha256,
		'url' => esc_url_raw( $manifest['url'] ?? 'https://github.com/nerds-code-lab/nerds-toolkit', array( 'https' ) ),
		'requires' => sanitize_text_field( $manifest['requires'] ?? '6.6' ),
		'requires_php' => sanitize_text_field( $manifest['requires_php'] ?? '8.2' ),
		'tested' => sanitize_text_field( $manifest['tested'] ?? get_bloginfo( 'version' ) ),
	);
}

function nerds_toolkit_cache_release_failure(): void {
	set_site_transient( NERDS_TOOLKIT_UPDATE_CACHE_KEY, array( 'failed' => true ), 15 * MINUTE_IN_SECONDS );
}

function nerds_toolkit_release_metadata(): ?array {
	$cached = get_site_transient( NERDS_TOOLKIT_UPDATE_CACHE_KEY );
	if ( is_array( $cached ) ) {
		if ( ! empty( $cached['failed'] ) ) { return null; }
		$validated_cache = nerds_toolkit_normalize_release_manifest( $cached );
		if ( $validated_cache ) { return $validated_cache; }
		// Discard metadata cached by older updater versions that had no checksum.
		delete_site_transient( NERDS_TOOLKIT_UPDATE_CACHE_KEY );
	}
	$response = wp_safe_remote_get( NERDS_TOOLKIT_UPDATE_MANIFEST_URL, array(
		'timeout' => 10,
		'redirection' => 0,
		'limit_response_size' => 64 * KB_IN_BYTES,
		'headers' => array( 'Accept' => 'application/json', 'User-Agent' => 'Nerds-Toolkit/' . NERDS_TOOLKIT_VERSION ),
	) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		nerds_toolkit_cache_release_failure();
		return null;
	}
	$manifest = json_decode( wp_remote_retrieve_body( $response ), true );
	$metadata = is_array( $manifest ) ? nerds_toolkit_normalize_release_manifest( $manifest ) : null;
	if ( ! $metadata ) {
		nerds_toolkit_cache_release_failure();
		return null;
	}
	set_site_transient( NERDS_TOOLKIT_UPDATE_CACHE_KEY, $metadata, 6 * HOUR_IN_SECONDS );
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

/** Verify a completed download before WordPress is allowed to unpack executable PHP. */
function nerds_toolkit_verify_package_file( string $file, string $expected_sha256, int $maximum_bytes = NERDS_TOOLKIT_MAX_PACKAGE_BYTES ) {
	$size = is_file( $file ) ? filesize( $file ) : false;
	if ( false === $size || $size < 1 || $size > $maximum_bytes ) {
		return new WP_Error( 'nerds_toolkit_package_size', __( 'The Nerds Toolkit update package has an invalid size.', 'nerds-toolkit' ) );
	}
	$actual_sha256 = hash_file( 'sha256', $file );
	if ( ! is_string( $actual_sha256 ) || ! hash_equals( $expected_sha256, $actual_sha256 ) ) {
		return new WP_Error( 'nerds_toolkit_package_checksum', __( 'The Nerds Toolkit update package failed its integrity check.', 'nerds-toolkit' ) );
	}
	return true;
}

/** Stream, bound and authenticate only this plugin's canonical package. */
function nerds_toolkit_pre_download( $reply, string $package, $upgrader, array $hook_extra ) {
	if ( false !== $reply || ! nerds_toolkit_release_package_is_allowed( $package ) ) {
		return $reply;
	}
	$release = nerds_toolkit_release_metadata();
	if ( ! $release || ! hash_equals( $release['package'], $package ) ) {
		return new WP_Error( 'nerds_toolkit_package_untrusted', __( 'The Nerds Toolkit update package is not trusted.', 'nerds-toolkit' ) );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$temporary_file = wp_tempnam( $package );
	if ( ! $temporary_file ) {
		return new WP_Error( 'nerds_toolkit_package_temp', __( 'WordPress could not create a temporary update file.', 'nerds-toolkit' ) );
	}
	$response = wp_safe_remote_get( $package, array(
		'timeout' => 60,
		'redirection' => 0,
		'stream' => true,
		'filename' => $temporary_file,
		'limit_response_size' => NERDS_TOOLKIT_MAX_PACKAGE_BYTES + 1,
		'headers' => array( 'Accept' => 'application/zip', 'User-Agent' => 'Nerds-Toolkit/' . NERDS_TOOLKIT_VERSION ),
	) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		wp_delete_file( $temporary_file );
		return is_wp_error( $response ) ? $response : new WP_Error( 'nerds_toolkit_package_http', __( 'The Nerds Toolkit update package could not be downloaded.', 'nerds-toolkit' ) );
	}
	$content_length = (int) wp_remote_retrieve_header( $response, 'content-length' );
	if ( $content_length > NERDS_TOOLKIT_MAX_PACKAGE_BYTES ) {
		wp_delete_file( $temporary_file );
		return new WP_Error( 'nerds_toolkit_package_size', __( 'The Nerds Toolkit update package is too large.', 'nerds-toolkit' ) );
	}
	$verified = nerds_toolkit_verify_package_file( $temporary_file, $release['sha256'] );
	if ( is_wp_error( $verified ) ) {
		wp_delete_file( $temporary_file );
		return $verified;
	}
	return $temporary_file;
}
add_filter( 'upgrader_pre_download', 'nerds_toolkit_pre_download', 10, 4 );

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
