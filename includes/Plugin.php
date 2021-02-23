<?php
namespace Loxo;

/**
 * Main Plugin File.
 *
 * @package Loxo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class.
 *
 * @class Loxo
 */
final class Plugin {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	public $name = 'Loxo';

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public $version = '1.0.4';

	/**
	 * Singleton The reference the *Singleton* instance of this class.
	 *
	 * @var Loxo
	 */
	protected static $instance = null;

	/**
	 * Private clone method to prevent cloning of the instance of the
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Private unserialize method to prevent unserializing.
	 *
	 * @return void
	 */
	private function __wakeup() {}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	private function __construct() {
		$this->define_constants();
		$this->initialize();

		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 20 );
		add_action( 'init', array( $this, 'load_plugin_translations' ) );
		add_action( 'loxo_pre_cache_all_jobs_hook', array( $this, 'pre_cache_all_jobs' ) );
	}

	/**
	 * Define constants
	 */
	private function define_constants() {
		define( 'LOXO_DIR', plugin_dir_path( LOXO_PLUGIN_FILE ) );
		define( 'LOXO_URL', plugin_dir_url( LOXO_PLUGIN_FILE ) );
		define( 'LOXO_BASENAME', plugin_basename( LOXO_PLUGIN_FILE ) );
		define( 'LOXO_VERSION', $this->version );
		define( 'LOXO_NAME', $this->name );
	}

	/**
	 * Pre cache all jobs for better performance.
	 */
	public function pre_cache_all_jobs() {
		loxo_clear_all_cache();
		loxo_get_all_jobs();
	}

	/**
	 * Initialize the plugin
	 */
	private function initialize() {
		// Schedule a cronjob to pre cache all jobs.
		if ( ! wp_next_scheduled( 'loxo_pre_cache_all_jobs_hook' ) ) {
			wp_schedule_single_event( time() + 240, 'loxo_pre_cache_all_jobs_hook' );
		}

		new Frontend();
		new Latest_Jobs_Shortcode();

		if ( 'yes' === get_option( 'loxo_enable_sitemap' ) ) {
			new Sitemap();
		}

		if ( is_admin() ) {
			new Admin\Main();
			new Admin\Ajax_Handlers();
			new Admin\Page\Settings();
		}
	}

	/**
	 * Load plugin translation file
	 */
	public function load_plugin_translations() {
		load_plugin_textdomain(
			'loxo',
			false,
			basename( dirname( LOXO_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Flush rewrite rules if scheduled
	 */
	public function maybe_flush_rewrite_rules() {
		if ( get_option( 'loxo_flush_rewrite_rules' ) ) {
			delete_option( 'loxo_flush_rewrite_rules' );
			flush_rewrite_rules();
		}
	}

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
