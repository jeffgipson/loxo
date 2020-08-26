<?php
namespace Loxo\Admin;

/**
 * Admin main class.
 *
 * @package Loxo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Main Class.
 *
 * @class Loxo_Admin_Main
 */
class Main {
	/**
	 * Constructor
	 */
	public function __construct() {
		new Jobs();
		new Notices();
		new Settings\Page();

		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 5 );
		add_action( 'plugin_action_links_' . LOXO_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Register admin assets.
	 */
	public function register_scripts() {
		wp_register_style( 'loxo-admin', LOXO_URL . 'assets/css/admin.css', array( ) );
		wp_register_script( 'loxo-admin', LOXO_URL . 'assets/js/admin.js' );
	}

	/**
	 * Adds plugin action links.
	 */
	public function plugin_action_links( $links ) {
		$new_links = array();
		$new_links['settings'] = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=loxo-settings' ),
			__( 'Settings', 'loxo' )
		);

		return array_merge( $new_links, $links );
	}
}
