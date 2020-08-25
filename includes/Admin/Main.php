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
		new Settings\Page();

		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 5 );
		add_action( 'plugin_action_links_' . LOXO_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function admin_notices( $hook ) {
		$screen = get_current_screen();

		$errors = array();
		$messages = array();

		$agency_key   = get_option( 'loxo_agency_key' );
		$api_username = get_option( 'loxo_api_username' );
		$api_password = get_option( 'loxo_api_password' );

		if ( ! $agency_key || ! $api_username || ! $api_username ) {
			$errors[] = sprintf( 
				__( 'Loxo Error: Missing api credentials. Please update your loxo <a href="%s">API Settings</a>', 'loxo' ),
				admin_url( 'edit.php?post_type=loxo_job&page=loxo-settings' )
			);
		} elseif ( get_option( 'loxo_api_credentials_error' ) ) {
			$errors[] = sprintf( 
				'Loxo Error: %s Please check & update your loxo <a href="%s">API Settings</a>', 
				get_option( 'loxo_api_credentials_error' ),
				admin_url( 'edit.php?post_type=loxo_job&page=loxo-settings' )
			);
		}

		if ( isset( $screen->id ) && 'loxo_job_page_loxo-settings' === $screen->id ) {
			if ( isset( $_REQUEST['settings-updated'] ) && 'true' === $_REQUEST['settings-updated'] ) {
				$messages[] = __( 'Settings updated.', 'loxo' );
			} elseif ( isset( $_REQUEST['message'] ) ) {
				$messages[] = urldecode( $_REQUEST['message'] );
			} elseif ( isset( $_REQUEST['error'] ) ) {
				$errors[] = urldecode( $_REQUEST['error'] );
			}
		}

		foreach ( $errors as $error ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $error; ?></p>
			</div>
			<?php
		}

		foreach ( $messages as $message ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Register admin assets.
	 */
	public function register_admin_scripts() {
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
