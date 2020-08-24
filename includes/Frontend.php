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
		wp_register_script( 'loxo-front', LOXO_URL . 'assets/js/front.js', array( 'jquery' ) );
	}

	/**
	 * Adds plugin action links.
	 */
	public function enqueue_scripts() {
		$all_jobs = loxo_get_all_jobs();
		if ( is_wp_error( $all_jobs ) ) {
			$all_jobs = array();
		} else {
			$all_jobs = array_values( $all_jobs );
		}

		wp_localize_script( 'loxo-front', 'loxo', array(
			'jobs' => $all_jobs,
			'notMatch' => __( 'No job matched your selection.' ),
			'noItems' => __( 'No jobs available right now.' )
		) );

		wp_enqueue_style( 'loxo-front' );
		wp_enqueue_script( 'loxo-front' );
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
		$job = loxo_api_get_job( $job_id );

		// If there's an error with all jobs, bail.
		if ( is_wp_error( $job ) ) {
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

		# Utils::d( $job );

		// Remove Yoast seo metadata.
		if ( defined( 'WPSEO_VERSION' ) ) {
			$this->remove_wpseo_metadata();
		}

		// Add single job metadata + schema.
		add_action( 'wp_head', array( $this, 'single_job_metadata' ), 1 );
	}

	/**
	 * Enable callback on job page content.
	 */
	public function the_content( $content ) {
		if ( get_query_var( 'loxo_job_id' ) > 0 ) {
			$job = loxo_api_get_job( get_query_var( 'loxo_job_id' ) );
			if ( is_wp_error( $job ) ) {
				return sprintf(
					__( 'Error: %s', 'loxo' ),
					$job->get_error_message()
				);
			}

			ob_start();
			include LOXO_DIR . '/templates/single-job-content.php';
			$content = ob_get_clean();

		} else {
			$all_jobs = loxo_get_all_jobs();

			if ( is_wp_error( $all_jobs ) ) {
				$content .= sprintf(
					/* translators: %s: Api error string. */
					__( 'Error: %s', 'loxo' ),
					$all_jobs->get_error_message()
				);
			} else {
				$job_categories = loxo_get_job_categories();
				$job_states = loxo_api_job_states();

				$selected_job_category = 'any';
				if ( isset( $_REQUEST['job_category'] ) ) {
					$category_name = $_REQUEST['job_category'];
					foreach ( $job_categories as $job_category ) {
						if ( $job_category['name'] === $category_name ) {
							$selected_job_category = $job_category['id'];
							break;
						}
					}
				}

				ob_start();
				include LOXO_DIR . '/templates/listing-content.php';
				$content .= ob_get_clean();
			}
		}

		return $content;
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
		$job = loxo_api_get_job( get_query_var( 'loxo_job_id' ) );
		if ( is_wp_error( $job ) ) {
			return;
		}

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
