<?php
namespace Loxo\Admin;

/**
 * Admin ajax handler class.
 *
 * @package Loxo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax handler.
 *
 * @class Loxo_Ajax_Handlers
 */

class Ajax_Handlers {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_loxo_activate_module', array( $this, 'activate_module_ajax' ) );
		add_action( 'wp_ajax_loxo_deactivate_module', array( $this, 'deactivate_module_ajax' ) );
		add_action( 'wp_ajax_loxo_module_footer', array( $this, 'module_footer_ajax' ) );
	}

	/**
	 * Adds plugin action links.
	 */
	public function module_footer_ajax() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Unauthorized request.', 'loxo' )
			));
		}

		$data = $_POST;
		if ( empty( $data['slug'] ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Invalid request.', 'loxo' )
			));
		}
		$module = loxo_get_module( $data['slug'] );

		wp_send_json( array(
			'success' => true,
			'footer'  => $this->get_module_footer( $module )
		));
	}

	/**
	 * Adds plugin action links.
	 */
	public function activate_module_ajax() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Unauthorized request.', 'loxo' )
			));
		}

		$data = stripslashes_deep( $_POST );
		if ( empty( $data['slug'] ) || empty( $data['nonce'] ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Invalid request.', 'loxo' )
			));
		}
		$module = loxo_get_module( $data['slug'] );

		if ( ! wp_verify_nonce( $data['nonce'], 'activate-plugin_' . $module->slug ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Validation failed', 'loxo' )
			));
		}

		$plugin_file = loxo_get_plugin_file( $module->slug );
		if ( ! $plugin_file ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Could not locate plugin file', 'loxo' )
			));
		}

		$activate = activate_plugin( $plugin_file );
		if ( is_wp_error( $activate ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => $activate->get_error_message()
			));
		}

		wp_send_json( array(
			'success' => true,
			'message' => __( 'Module activated', 'loxo' ),
			'footer'  => $this->get_module_footer( $module )
		) );
	}

	/**
	 * Adds plugin action links.
	 */
	public function deactivate_module_ajax() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Unauthorized request.', 'loxo' )
			));
		}

		$data = $_POST;
		if ( empty( $data['slug'] ) || empty( $data['nonce'] ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Invalid request.', 'loxo' )
			));
		}

		$module = loxo_get_module( $data['slug'] );

		if ( ! wp_verify_nonce( $data['nonce'], 'deactivate-plugin_' . $module->slug ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Validation failed', 'loxo' )
			));
		}


		$plugin_file = loxo_get_plugin_file( $module->slug );
		if ( ! $plugin_file ) {
			wp_send_json( array(
				'success' => false,
				'message' => __( 'Could not locate plugin file', 'loxo' )
			));
		}

		$deactivate = deactivate_plugins( $plugin_file );
		if ( is_wp_error( $deactivate ) ) {
			wp_send_json( array(
				'success' => false,
				'message' => $deactivate->get_error_message()
			));
		}

		wp_send_json( array(
			'success'     => true,
			'message'     => __( 'Module deactivated', 'loxo' ),
			'footer'	  => $this->get_module_footer( $module )
		) );
	}

	private function get_module_footer( $module ) {
		ob_start();
		include 'pages/views/module-footer.php';
		return ob_get_clean();
	}
}
