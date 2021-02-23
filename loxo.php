<?php
/**
 * Plugin Name: Loxo
 * Plugin URI: https://www.ramaxsearch.com
 * Description: Display jobs from your loxo.co saas application. job listing, job filter, single job details, sitemap, job schema
 * Version: 1.0.4
 * Author: Loxo
 * Author URI: https://shazzad.me
 * Requires at least: 4.4
 * Tested up to: 5.4.1
 * Text Domain: loxo
 * Domain Path: /languages
 *
 * @package Loxo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define base file.
if ( ! defined( 'LOXO_PLUGIN_FILE' ) ) {
	define( 'LOXO_PLUGIN_FILE', __FILE__ );
}

// Load dependencies.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Intialize everything after plugins_loaded action.
 *
 * @return void
 */
function loxo_init() {
	loxo();
}
add_action( 'plugins_loaded', 'loxo_init', 5 );

/**
 * Get an instance of plugin main class.
 *
 * @return Loxo Instance of main class.
 */
function loxo() {
	return \Loxo\Plugin::get_instance();
}

function loxo_activate() {
	update_option( 'loxo_flush_rewrite_rules', time() );
}
register_activation_hook( __FILE__, 'loxo_activate' );
