<?php
namespace Loxo;

/**
 * Listing handler file.
 *
 * @package Loxo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Class.
 *
 * @class Custom_Post_Types
 */
class Custom_Post_Types {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'register_post_type_args', array( $this, 'job_post_type_args' ), 10, 2 );
		add_filter( 'register_taxonomy_args', array( $this, 'job_taxonomy_args' ), 10, 2 );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Disable job creation capability.
	 */
	public function job_post_type_args( $args, $post_type ) {
		if ( 'loxo_job' === $post_type && ! wp_doing_cron() ) {
			$args['capabilities'] = array(
				'create_posts' => 'create_jobs',
				'edit_post' => 'edit_job',
				#'edit_posts' => 'edit_jobs',
				'edit_others_posts' => 'edit_others_jobs',
				'edit_published_posts' => 'edit_published_jobs',
				'delete_published_posts' => 'delete_published_jobs'
			);
			$args['map_meta_cap'] = true;
		}

		return $args;
	}

	/**
	 * Disable job category/state modification capability.
	 */
	public function job_taxonomy_args( $args, $post_type ) {
		if ( in_array( $post_type, array( 'loxo_job_cat', 'loxo_job_state' ) ) && ! wp_doing_cron() ) {
			$args['capabilities'] = array(
				'edit_terms' => 'edit_job_terms',
				'delete_terms' => 'delete_job_terms'
			);
			$args['map_meta_cap'] = true;
		}

		return $args;
	}

	/**
	 * Register post types
	 */
  	public function register_post_types() {
		register_post_type( 'loxo_job', [
			'labels' => array(
				'name' => _x( 'Jobs', 'job post type general name' ),
				'singular_name' => _x( 'Job', 'job post type singular name' ),
				'menu_name' => __( 'Loxo Jobs', 'loxo' ),
				'all_items' => __( 'All Jobs', 'loxo' )
			),
			'show_ui' => true,
			'admin_menu_name' => 'Loxo Jobs',
			'rewrite' => false,
			'public' => false,
			'has_archive' => false,
			'delete_with_user' => false,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,
			'menu_icon' => 'dashicons-nametag',
			'supports' => array( 'title', 'editor', 'custom-fields' )
		]);
	}

	/**
	 * Register taxonomies
	 */
  	public function register_taxonomies() {
		// Job category taxonomy.
		register_taxonomy( 'loxo_job_cat', array( 'loxo_job' ), [
			'labels' => array(
				'name' => _x( 'Job Categories', 'job category general name' ),
				'singular_name' => _x( 'Job Category', 'job category singular name' )
			),
			'show_ui' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite' => false,
			'public' => false,
			'has_archive' => false,
			'delete_with_user' => false,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false
		]);

		// Job state taxonomy.
		register_taxonomy( 'loxo_job_state', array( 'loxo_job' ), [
			'labels' => array(
				'name' => _x( 'Job States', 'job state general name' ),
				'singular_name' => _x( 'Job State', 'job state singular name' )
			),
			'show_ui' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite' => false,
			'public' => false,
			'has_archive' => false,
			'delete_with_user' => false,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false
		]);
	}
}
