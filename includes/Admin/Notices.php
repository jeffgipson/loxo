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
 * Admin Notices Class.
 *
 * @class Loxo_Admin_Main
 */
class Notices {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function admin_notices() {
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
				'Loxo Error: %1$s Please check & update your loxo <a href="%2$s">API Settings</a>', 
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

		if ( isset( $screen->id ) && 'edit-loxo_job' === $screen->id ) {
			if ( isset( $_REQUEST['settings-updated'] ) && 'job-synchronized' === $_REQUEST['settings-updated'] ) {
				$messages[] = __( 'Job synchronized.', 'loxo' );
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
}
