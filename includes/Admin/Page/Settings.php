<?php
namespace Loxo\Admin\Page;

use Loxo\Utils;
use Loxo\Admin\WP_Settings_Api;

/**
 * Admin Settings Page Class.
 *
 * @package Loxo
 * @class Loxo_Admin_Modules_Page
 */

class Settings {
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
			'options-general.php',
			__( 'Loxo Settings', 'loxo' ),
			__( 'Loxo Settings', 'loxo' ),
			$access_cap,
			'loxo-settings',
			array( $this, 'render_page' )
		);

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'print_scripts' ) );
		add_action( "load-{$admin_page}", array( $this, 'handle_actions' ) );
	}

	public function handle_actions() {
		// Schedule rewrite rules regeneration.
		if ( isset( $_REQUEST['action'] ) && 'clear_cache' === $_REQUEST['action'] ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'loxo_clear_cache' ) ) {
				wp_die( __( 'Cheating huh?' ) );
			}

			loxo_clear_all_cache();

			wp_redirect( admin_url( 'options-general.php?page=loxo-settings&cache-cleared=true' ) );
			exit;
		}

		if ( isset( $_REQUEST['action'] ) && 'synchronize' === $_REQUEST['action'] ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'loxo_synchronize' ) ) {
				wp_die( __( 'Cheating huh?' ) );
			}

			$synchronizer = new \Loxo\Synchronizer();
			$synchronizer->synchronize_jobs();
			#$synchronizer->display_logs();

			wp_redirect( admin_url( 'options-general.php?page=loxo-settings&synchronized=true' ) );
			exit;
		}

		// Schedule rewrite rules regeneration.
		if ( isset( $_REQUEST['settings-updated'] ) ) {
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

        return $settings_fields;
    }

	public function render_page() {
		#$synchronizer = new \Loxo\Synchronizer();
		#$synchronizer->sunc_jobs();
		#$synchronizer->display_logs();

		?>
		<div class="wrap loxo-wrap">
			<h1><?php _e( 'Loxo Settings', 'loxo' ) ?></h1>
			<div class="loxo-sidebar">
				<p>
					<?php
					_e(
						'Loxo api jobs requests & single job data are cached with five minutes interval.
						Reset cache to see fresh content if needed.', 'loxo'
					);
					?></br/></br/><a class="button button-primary" href="<?php echo add_query_arg(
						array(
							'action' => 'clear_cache',
							'_wpnonce' => wp_create_nonce( 'loxo_clear_cache' )
						)
					); ?>"><?php _e( 'Clear Cache', 'loxo' ); ?></a>
				</p>

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
				</p>

				<?php if ( 'yes' === get_option( 'loxo_enable_sitemap' ) ) : ?>
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
