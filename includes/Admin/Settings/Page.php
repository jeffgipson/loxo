<?php
namespace Loxo\Admin\Settings;

use Loxo\Utils;
use Loxo\Admin\WP_Settings_Api;

/**
 * Admin Settings Page Class.
 *
 * @package Loxo
 * @class Loxo\Admin\Settings\Page
 */

class Page {
	private $settings_api;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings_api = new WP_Settings_Api();

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Sanitize settings option
	 */
	public function admin_menu() {
		// Access capability.
		$access_cap = apply_filters( 'loxo_admin_page_access_cap', 'manage_options' );

		// Register menu.
		$admin_page = add_submenu_page(
			'edit.php?post_type=loxo_job',
			__( 'Loxo Settings', 'loxo' ),
			__( 'Settings', 'loxo' ),
			$access_cap,
			'loxo-settings',
			array( $this, 'render_page' )
		);

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'print_scripts' ) );
		add_action( "load-{$admin_page}", array( $this, 'handle_actions' ) );
	}

	public function handle_actions() {
		// Clear cache.
		if ( isset( $_REQUEST['action'] ) && 'clear_cache' === $_REQUEST['action'] ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'loxo_clear_cache' ) ) {
				wp_die( __( 'Cheating huh?' ) );
			}

			loxo_clear_all_cache();

			$message = urlencode( __( 'Cache cleared.', 'loxo' ) );
			wp_redirect( admin_url( 'edit.php?post_type=loxo_job&page=loxo-settings&message='. $message ) );
			exit;
		}

		// Synchronize all jobs.
		if ( isset( $_REQUEST['action'] ) && 'synchronize' === $_REQUEST['action'] ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'loxo_synchronize' ) ) {
				wp_die( __( 'Cheating huh?' ) );
			}

			$synchronizer = new \Loxo\Synchronizer();
			$sync = $synchronizer->synchronize_jobs();

			if ( false === $sync ) {
				$message = urlencode( __( 'Synchronizion failed.', 'loxo' ) );
				wp_redirect( admin_url( 'edit.php?post_type=loxo_job&page=loxo-settings&error=' . $message ) );
			} else {
				$message = urlencode( __( 'Synchronizion completed', 'loxo' ) );
				wp_redirect( admin_url( 'edit.php?post_type=loxo_job&page=loxo-settings&message=' . $message ) );
			}
			exit;
		}

		// Delete everything.
		if ( isset( $_REQUEST['action'] ) && 'delete_all' === $_REQUEST['action'] ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'loxo_delete_all' ) ) {
				wp_die( __( 'Cheating huh?' ) );
			}

			$synchronizer = new \Loxo\Synchronizer();
			$synchronizer->cleanup();

			$message = urlencode( __( 'All data deleted', 'loxo' ) );
			wp_redirect( admin_url( 'edit.php?post_type=loxo_job&page=loxo-settings&message=' . $message ) );
			exit;
		}

		// Schedule rewrite rules regeneration.
		if ( isset( $_REQUEST['settings-updated'] ) ) {
			delete_option( 'loxo_api_credentials_error' );
			update_option( 'loxo_flush_rewrite_rules', time() );
		}
	}

    public function admin_init() {
        $this->settings_api->set_page( 'loxo-settings' );
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );
        $this->settings_api->admin_init();
    }

    public function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'api',
                'title' => __( 'API Settings', 'loxo' )
            ),
            array(
                'id'    => 'general',
                'title' => __( 'General Settings', 'loxo' )
            ),
            array(
                'id'    => 'synchronizer',
                'title' => __( 'Synchronizer Setting', 'loxo' )
            ),
            array(
                'id'    => 'seo',
                'title' => __( 'SEO Settings', 'loxo' )
            )
        );
        return $sections;
    }

    public function get_settings_fields() {
        $settings_fields = array(
            'api' => array(
				array(
                    'id'                => 'loxo_agency_key',
                    'name'              => 'loxo_agency_key',
                    'label'             => __( 'Agency Key', 'loxo' ),
					'desc'				=> __( 'Contact loxo support to get this information.', 'loxo' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'id'                => 'loxo_api_username',
                    'name'              => 'loxo_api_username',
                    'label'             => __( 'API Username', 'loxo' ),
					'desc'				=> __( 'Contact loxo support to get this information.', 'loxo' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
				array(
                    'id'                => 'loxo_api_password',
                    'name'              => 'loxo_api_password',
                    'label'             => __( 'API Password', 'loxo' ),
					'desc'				=> __( 'Contact loxo support to get this information.', 'loxo' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
            ),
            'general' => array(
                array(
                    'id'                => 'loxo_listing_page_id',
                    'name'              => 'loxo_listing_page_id',
                    'label'             => __( 'Listing Page', 'loxo' ),
					'desc'				=> __( 'Job listing will be automatically displayed on this page. Also, single job pages will be generated using this page url.', 'loxo' ),
                    'type'              => 'pages'
                ),
				array(
                    'id'                => 'loxo_listings_per_page',
                    'name'              => 'loxo_listings_per_page',
					'label'             => __( 'Listings Per Page', 'loxo' ),
					'desc'				=> __( 'Number of jobs to display per page while paginating', 'loxo' ),
					'type'              => 'text',
					'default'			=> '10',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
				array(
                    'id'                => 'loxo_job_expiration_custom_field',
                    'name'              => 'loxo_job_expiration_custom_field',
                    'label'             => __( 'Name of the job expiration custom field', 'loxo' ),
					'desc'				=> __( 'If loxo has enabled a custom date field for you that you would use for job expiration/validthrough date, enter the field name here.', 'loxo' ),
					'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
				array(
                    'id'                => 'loxo_default_job_validity_days',
                    'name'              => 'loxo_default_job_validity_days',
                    'label'             => __( 'Default job validity (in days)', 'loxo' ),
					'desc'				=> __( 'If job expiration custom field comes empty, a validThrough date will be calculated in conjunction with job publication date.', 'loxo' ),
					'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field'
                )
			),
			'synchronizer' => array(
				array(
                    'id'                => 'loxo_all_jobs_synchronizer_interval',
                    'name'              => 'loxo_all_jobs_synchronizer_interval',
                    'label'             => __( 'Jobs Update Frequency (in minutes)', 'loxo' ),
					'desc'				=> __( 'How frequently jobs should be updated.', 'loxo' ),
                    'type'              => 'text',
					'default'			=> 300,
                    'sanitize_callback' => 'sanitize_text_field'
				)
			),
            'seo' => array(
                array(
                    'id'                => 'loxo_hiring_company_name',
                    'name'              => 'loxo_hiring_company_name',
                    'label'             => __( 'Hiring Company Name', 'loxo' ),
					'desc'				=> __( 'Used on single job company name schema.', 'loxo' ),
                    'type'              => 'text',
					'default'			=> get_bloginfo( 'sitename'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
				array(
                    'id'                => 'loxo_hiring_company_url',
                    'name'              => 'loxo_hiring_company_url',
                    'label'             => __( 'Hiring Company Url', 'loxo' ),
					'desc'				=> __( 'Used on single job company sameAs schema.', 'loxo' ),
                    'type'              => 'text',
					'default'			=> home_url( '/' ),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
				array(
                    'id'                => 'loxo_hiring_company_logo',
                    'name'              => 'loxo_hiring_company_logo',
                    'label'             => __( 'Hiring Company Logo', 'loxo' ),
					'desc'				=> __( 'Used on single job company logo schema.', 'loxo' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
				array(
                    'id'                => 'loxo_enable_sitemap',
                    'name'              => 'loxo_enable_sitemap',
                    'label'             => __( 'Enable Jobs Sitemap?', 'loxo' ),
					'desc'				=> __( 'If enabled, a sitemap will be created for your jobs', 'loxo' ),
                    'type'              => 'checkbox',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
		);

		/*
		$job_statuses = loxo_api_get_job_statuses();
		if ( ! is_wp_error( $job_statuses ) ) {
			$job_statuses_options = array( '' => __( 'All' ) );
			foreach ( $job_statuses as $job_status ) {
				$job_statuses_options[ $job_status['id'] ] = $job_status['name'];
			}

			$settings_fields['api'][] = array(
				'id'      => 'loxo_active_job_status_id',
				'name'    => 'loxo_active_job_status_id',
				'label'   => __( 'Active Job Status', 'loxo' ),
				'desc'	  => __( 'Only active jobs are displayed.', 'loxo' ),
				'type'    => 'select',
				'options' => $job_statuses_options
			);
		}
		*/

        return $settings_fields;
    }

	public function render_page() {
		#$synchronizer = new \Loxo\Synchronizer();
		#$synchronizer->sunc_jobs();
		#$synchronizer->display_logs();

		$post_type_object = get_post_type_object( 'loxo_job' );
		Utils::p( $post_type_object->cap );

		?>
		<div class="wrap loxo-wrap">
			<h1><?php _e( 'Loxo Settings', 'loxo' ) ?></h1>
			<div class="loxo-sidebar">
				<p>
					<?php
					_e(
						'Clear cached data.', 'loxo'
					);
					?></br/></br/><a class="button button-primary" href="<?php echo add_query_arg(
						array(
							'action' => 'clear_cache',
							'_wpnonce' => wp_create_nonce( 'loxo_clear_cache' )
						)
					); ?>"><?php _e( 'Clear Cache', 'loxo' ); ?></a>
				</p>
				<hr />

				<p>
					<?php
					_e(
						'All jobs are stored locally for quick access. You can synchronize jobs from loxo to local storage using button below', 'loxo'
					);
					?></br/></br/><a class="button button-primary" href="<?php echo add_query_arg(
						array(
							'action' => 'synchronize',
							'_wpnonce' => wp_create_nonce( 'loxo_synchronize' )
						)
					); ?>"><?php _e( 'Synchronize', 'loxo' ); ?></a>
					<?php 
					if ( $timestamp = wp_next_scheduled( 'loxo_synchronize_jobs' ) ) {
						echo '<br /><br /><strong>';
						printf( __( 'Auto synchronizer will run in %s' ), human_time_diff( $timestamp ) );
						echo '</strong>';
					}
					?>
				</p>
				<hr />

				<p>
					<?php
					_e(
						'Delete all jobs, categories & states from local storate', 'loxo'
					);
					?></br/></br/><a class="button button-primary button-danger" href="<?php echo add_query_arg(
						array(
							'action' => 'delete_all',
							'_wpnonce' => wp_create_nonce( 'loxo_delete_all' )
						)
					); ?>"><?php _e( 'Delete All', 'loxo' ); ?></a>
				</p>

				<?php if ( 'yes' === get_option( 'loxo_enable_sitemap' ) ) : ?>
					<hr />
					<p>
						<?php _e( 'A sitemap is automatically generated for all active jobs. It is also added on the robots.txt file so that search engine both can find it. Click view sitemap to see how it looks.', 'loxo' ); ?>
						<br /><br /><a class="button button-secondary" target="_blank" href="<?php echo loxo_get_sitemap_url(); ?>"><?php _e( 'View Sitemap' ); ?></a>
						<br /><br /><?php _e( 'If you have added or deactivated job on loxo, resubmit sitemap to google for reindexing.', 'loxo' ); ?>
						<br /><br /><a class="button button-secondary" target="_blank" href="http://www.google.com/ping?sitemap=<?php echo loxo_get_sitemap_url(); ?>"><?php _e( 'Submit Sitemap to Google', 'loxo' ); ?></a>
					</p>
				<?php endif; ?>

	        </div>
			<div class="loxo-form">
            	<?php $this->settings_api->show_forms(); ?>
			</div>
		</div>
		<?php
	}

	public function print_scripts() {
		wp_enqueue_style( 'loxo-admin' );
		do_action( 'loxo_admin_page_scripts' );
	}
}
