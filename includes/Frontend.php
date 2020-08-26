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
 * @class Query
 */
class Frontend {
	public function __construct() {
		add_action( 'init', array( $this, 'single_job_page_rule' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ), 8 );
		add_action( 'query_vars', array( $this, 'job_id_query_vars' ) );
		add_action( 'save_post_page', array( $this, 'flush_if_listing_page_updated' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 5 );
	}

	/**
	 * Register admin assets.
	 */
	public function register_scripts() {
		wp_register_style( 'loxo-front', LOXO_URL . 'assets/css/front.css' );
	}

	/**
	 * Adds plugin action links.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'loxo-front' );
	}

	/**
	 * Add rewrite rule for single job page.
	 *
	 * This would allow a dynamic subpage generation for each job.
	 */
	public function single_job_page_rule() {
		if ( loxo_get_listing_page_id() ) {
			$listing_page = get_page( loxo_get_listing_page_id() );

			add_rewrite_rule(
		        '(' . $listing_page->post_name . ')/[^/]+\-([0-9]+)',
		        'index.php?pagename=$matches[1]&loxo_job_id=$matches[2]',
		        'top'
		    );
		}
	}

	/**
	 * Peform additional action on single job page.
	 */
	public function template_redirect() {
		global $wp_query;

		if ( isset( $_POST['action'] ) && $_POST['action'] === 'loxo_apply_to_job' ) {
			$this->job_application_handler();
		}

		if ( ! loxo_get_listing_page_id() || ! is_page( loxo_get_listing_page_id() ) ) {
			return;
		}

		// Scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Content.
		add_filter( 'the_content', array( $this, 'the_content' ) );

		if ( ! get_query_var( 'loxo_job_id' ) ) {
			return;
		}

		$job_id = (int) sanitize_text_field( get_query_var( 'loxo_job_id' ) );

		// Check if job id exists.
		$local_job = new \Loxo\Job\Data( 'loxo-job-' . $job_id );

		// If there's an error with all jobs, bail.
		if ( ! $local_job->get_id() ) {
			wp_die(
				__( 'Sorry, the job you are looking for does not exists.', 404 ),
				sprintf( '%s - Error', get_bloginfo( 'sitename' ) ),
				array(
					'response' => 404,
					'link_url' => get_permalink( loxo_get_listing_page_id() ),
					'link_text' => __( 'View Active Jobs' )
				)
			);
		}

		// If job does not have description yet, this will update description.
		if ( ! $local_job->get_description() ) {
			do_action( 'loxo_synchronize_job', $job_id );
		} else {
			do_action( 'loxo_schedule_job_synchronization', $job_id, 10 );
		}

		// Remove Yoast seo metadata.
		if ( defined( 'WPSEO_VERSION' ) ) {
			$this->remove_wpseo_metadata();
		}

		// Add single job metadata + schema.
		add_action( 'wp_head', array( $this, 'single_job_metadata' ), 1 );

		#echo loxo_sanitize_job_description( $local_job->get_description() );
		#exit;
	}

	/**
	 * Enable callback on job page content.
	 */
	public function the_content( $content ) {
		if ( get_query_var( 'loxo_job_id' ) > 0 ) {

			$job_id = (int) sanitize_text_field( get_query_var( 'loxo_job_id' ) );

			// Check if job id exists.
			$local_job = new \Loxo\Job\Data( 'loxo-job-' . $job_id );

			if ( ! $local_job->get_id() ) {
				return sprintf(
					__( 'Error: %s', 'loxo' ),
					__( 'This Job does not exists anymore.' )
				);
			}

			ob_start();
			include LOXO_DIR . '/templates/single-job-content.php';
			$content = ob_get_clean();

		} else {
			$total_jobs = $this->get_total_jobs_count();

			$job_category_terms = get_terms( array(
				'taxonomy' => 'loxo_job_cat'
			) );
			$job_categories = array(
				array(
					'name' => 'Any',
					'value' => 'Any',
					'count' => $total_jobs
				)
			);
			foreach ( $job_category_terms as $job_category_term ) {
				$job_categories[] = array(
					'name' => $job_category_term->name,
					'value' => $job_category_term->name,
					'count' => $job_category_term->count
				);
			}

			$job_state_terms = get_terms( array(
				'taxonomy' => 'loxo_job_state'
			) );
			$job_states = array(
				array(
					'name' => 'Any',
					'value' => 'Any',
					'count' => $total_jobs
				)
			);

			foreach ( $job_state_terms as $job_state_term ) {
				$job_states[] = array(
					'name' => $job_state_term->name,
					'value' => $job_state_term->name,
					'count' => $job_state_term->count
				);
			}

			$filtered = false;
			$base_url = get_permalink();

			$selected_job_category = 'Any';
			if ( isset( $_REQUEST['job_category'] ) ) {
				$selected_job_category = $_REQUEST['job_category'];
			}

			$selected_job_state = 'Any';
			if ( isset( $_REQUEST['job_state'] ) ) {
				$selected_job_state = $_REQUEST['job_state'];
			}

			$tax_query = array();

			if ( 'Any' !== $selected_job_category ) {
				$filtered = true;
				$tax_query[] = array(
					'taxonomy' => 'loxo_job_cat',
					'field' => 'name',
					'terms' => $selected_job_category
				);
			}

			if ( 'Any' !== $selected_job_state ) {
				$filtered = true;
				$tax_query[] = array(
					'taxonomy' => 'loxo_job_state',
					'field' => 'name',
					'terms' => $selected_job_state
				);
			}

			if ( ! empty( $tax_query ) ) {
				$tax_query['relation'] = 'AND';
			}

			$per_page = (int) get_option( 'loxo_listings_per_page', 10 );
			if ( ! $per_page ) {
				$per_page = 10;
			}
			$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

			$jobs_query = new \WP_Query( array(
				'post_type' => 'loxo_job',
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'paged' => $paged,
				'tax_query' => $tax_query
			));

			$found_jobs = $jobs_query->found_posts;

			$job_posts = $jobs_query->get_posts();

			$jobs = array();
			foreach ( $job_posts as $job_post ) {
				$jobs[] = new \Loxo\Job\Data( $job_post->ID );
			}

			ob_start();
			include LOXO_DIR . '/templates/listing-content.php';
			$content .= ob_get_clean();
		}

		return $content;
	}

	private function get_total_jobs_count() {
		global $wpdb;

		$count = wp_cache_get( 'loxo_jobs_count' );

		if ( false === $count ) {
			$count = $wpdb->get_var( 
				$wpdb->prepare( 
					"SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
					'loxo_job',
					'publish'
				) 
			);
			wp_cache_set( 'loxo_jobs_count', $count );
		}

		return $count;
	}

	/**
	 * Handle job application request.
	 */
	private function job_application_handler() {
		if ( ! isset( $_POST['job_id'] ) || ! isset( $_POST['_wpnonce'] ) ) {
			wp_die( __( 'Cheating huh?', 'loxo' ) );
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'loxo-apply-to-job-' . $_POST['job_id'] ) ) {
			wp_die( __( 'Cheating hard?', 'loxo' ) );
		}

		if ( empty( $_FILES['resume'] ) || empty( $_FILES['resume']['name'] ) ) {
			wp_die( __( 'Please complete all fields, and submit again.', 'loxo' ) );
		}

		$apply = loxo_api_apply_job(
			absint( $_POST['job_id'] ),
			array(
				'name'  => sanitize_text_field( $_POST['name'] ),
				'email' => sanitize_email( $_POST['email'] ),
				'phone' => sanitize_text_field( $_POST['phone'] )
			),
			array(
				'name' => sanitize_file_name( $_FILES['resume']['name'] ),
				'file' => $_FILES['resume']['tmp_name']
			)
		);

		if ( ! empty( $apply['errors'] ) ) {
			wp_redirect(
				add_query_arg(
					'application-error',
					urlencode( implode( '|', $apply['errors'] ) ),
					$_POST['_wp_http_referer']
				)
			);
		} else {
			wp_redirect( add_query_arg( 'applied', true , $_POST['_wp_http_referer']  ) );
		}
		exit;
	}

	/**
	 * Generate and display single job metadata.
	 */
	public function single_job_metadata() {
		$job_id = (int) sanitize_text_field( get_query_var( 'loxo_job_id' ) );
		$job = new \Loxo\Job\Data( 'loxo-job-' . $job_id );

		$metadata = new Job_Metadata( $job );
		$metadata->display();
	}

	/**
	 * Remove yoast seo metadata completely from single job page.
	 */
	private function remove_wpseo_metadata() {
		if ( version_compare( WPSEO_VERSION, '14.0', '>') ) {
			$front_end = YoastSEO()->classes->get( \Yoast\WP\SEO\Integrations\Front_End_Integration::class );
			remove_action( 'wpseo_head', [ $front_end, 'present_head' ], -9999 );
		} else {
			global $wpseo_front;

			if ( isset( $wpseo_front ) ) {
				remove_action( 'wp_head',array( $wpseo_front,'head' ), 1 );

			} else if ( class_exists( 'WPSEO_Frontend' ) ) {
				$wpseo_frontend = WPSEO_Frontend::get_instance();
				remove_action( 'wp_head',array( $wpseo_frontend, 'head' ), 1 );
			}
		}
	}

	/**
	 * Whitelist new public query var.
	 */
	public function job_id_query_vars( $query_vars ) {
	    $query_vars[] = 'loxo_job_id';
	    return $query_vars;
	}

	/**
	 * Schedule a rewrite rules regeneration after listing page updates.
	 *
	 * @param  string $post_ID Current post id.
	 */
	public function flush_if_listing_page_updated( $post_ID ) {
		if ( loxo_get_listing_page_id() && absint( $post_ID ) === absint( loxo_get_listing_page_id() ) ) {
			update_option( 'loxo_flush_rewrite_rules', time() );
		}
	}
}
