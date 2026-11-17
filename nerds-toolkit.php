<?php
/**
 * Plugin Name: Nerds Toolkit
 * Plugin URI: https://github.com/nerds-code-lab/nerds-toolkit
 * Description: Reusable site layouts, business data, navigation, Elementor widgets and optional WooCommerce tools.
 * Version: 1.2.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Author: Nerds Lab
 * Author URI: https://github.com/nerds-code-lab
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nerds-toolkit
 * Update URI: https://github.com/nerds-code-lab/nerds-toolkit
 * GitHub Plugin URI: https://github.com/nerds-code-lab/nerds-toolkit
 */

defined( 'ABSPATH' ) || exit;

define( 'NERDS_TOOLKIT_VERSION', '1.2.0' );
define( 'NERDS_TOOLKIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'NERDS_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );

// Backward-compatible aliases for sites upgraded from Nerds Lab Core.
defined( 'NERDS_LAB_CORE_VERSION' ) || define( 'NERDS_LAB_CORE_VERSION', NERDS_TOOLKIT_VERSION );
defined( 'NERDS_LAB_CORE_PATH' ) || define( 'NERDS_LAB_CORE_PATH', NERDS_TOOLKIT_PATH );
defined( 'NERDS_LAB_CORE_URL' ) || define( 'NERDS_LAB_CORE_URL', NERDS_TOOLKIT_URL );

require_once NERDS_TOOLKIT_PATH . 'includes/business.php';
require_once NERDS_TOOLKIT_PATH . 'includes/dashboard.php';
require_once NERDS_TOOLKIT_PATH . 'includes/updater.php';
require_once NERDS_TOOLKIT_PATH . 'includes/layouts.php';
require_once NERDS_TOOLKIT_PATH . 'includes/content.php';
require_once NERDS_TOOLKIT_PATH . 'includes/elementor.php';
require_once NERDS_TOOLKIT_PATH . 'includes/navigation.php';
require_once NERDS_TOOLKIT_PATH . 'includes/catalog.php';
require_once NERDS_TOOLKIT_PATH . 'includes/quotes.php';

add_action( 'init', 'nerds_lab_register_layouts' );
add_action( 'init', 'nerds_lab_register_shortcodes' );
add_action( 'admin_init', 'nerds_lab_register_business_settings' );
add_action( 'admin_menu', 'nerds_lab_register_admin_menu' );
add_action( 'elementor/widgets/register', 'nerds_lab_register_elementor_widgets' );
add_action( 'wp_enqueue_scripts', 'nerds_lab_enqueue_layout_assets', 20 );
add_action( 'wp_enqueue_scripts', 'nerds_lab_register_menu_assets', 5 );
add_action( 'wp_enqueue_scripts', 'nerds_lab_enqueue_document_menu_assets', 15 );
add_action( 'wp_footer', 'nerds_lab_print_late_menu_styles', 5 );
add_filter( 'template_include', 'nerds_lab_layout_preview_template', 99 );
add_filter( 'rest_pre_dispatch', 'nerds_lab_protect_layout_rest', 10, 3 );
add_filter( 'wp_robots', 'nerds_lab_layout_robots' );
