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
	public function __construct() {
		add_action( 'init', [$this, 'register'] );
	}

  	public function register() {
		register_post_type( 'loxo_job', [
			'labels' => array(
				'name' => _x('Jobs', 'post type general name'),
				'singular_name' => _x('Job', 'post type singular name')
			),
			'show_ui' => true,
			'rewrite' => false,
			'public' => false,
			'has_archive' => false,
			'delete_with_user' => false,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,
			'supports' => array( 'title', 'editor', 'custom-fields' )
		]);
		register_taxonomy( 'loxo_job_cat', array( 'loxo_job' ), [
			'labels' => array(
				'name' => _x('Job Categories', 'post type general name'),
				'singular_name' => _x('Job Category', 'post type singular name')
			),
			'show_ui' => true,
			'hierarchical' => true,
			'rewrite' => false,
			'public' => false,
			'has_archive' => false,
			'delete_with_user' => false,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false
		]);
		register_taxonomy( 'loxo_job_state', array( 'loxo_job' ), [
			'labels' => array(
				'name' => _x('Job States', 'post type general name'),
				'singular_name' => _x('Job State', 'post type singular name')
			),
			'show_ui' => true,
			'hierarchical' => true,
			'rewrite' => false,
			'public' => false,
			'has_archive' => false,
			'delete_with_user' => false,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false
		]);
	}
}
