<?php
namespace Loxo\Admin;

use \Loxo\Job\Data as Job_Data;
use \Loxo\Synchronizer;

class Jobs {
    function __construct() {
        add_filter( 'post_row_actions', array( $this, 'job_preview_action' ), 10, 2 );
        add_filter( 'manage_loxo_job_posts_columns', array( $this, 'job_columns' ) );

        add_action( 'manage_loxo_job_posts_custom_column', array( $this, 'job_column_values' ), 10, 2 );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        add_action( 'wp_ajax_loxo_set_job_expiration', array( $this, 'set_job_expiration_ajax' ) );
    }

    public function set_job_expiration_ajax() {
        if ( ! isset( $_POST['id'] ) || ! isset( $_POST['id'] ) ) {
            wp_send_json_error( false );
        }

        $id = absint( $_POST['id'] );
        $job = new Job_Data( $id );
        if ( ! $job->get_id() ) {
            wp_send_json_error( false );
        }

        $date_expires = sanitize_text_field( $_POST['date_expires'] );

        try {
            $job->set_date_expires( $date_expires );
            $job->save();

            wp_send_json_success( array( 'date_expires' => $job->get_date_expires() ) );
        } catch ( \Loxo\Exception\Exception $e ) {
            wp_send_json_error( false );
            # return $e->getMessage();
        }
    }

    public function job_columns( $columns ) {
        unset( $columns['date'], $columns['taxonomy-loxo_job_cat'], $columns['taxonomy-loxo_job_state'] );

        $columns['job_id'] = __( 'Job ID', 'loxo' );
        $columns['taxonomy-loxo_job_cat'] = __( 'Categories', 'loxo' );
        $columns['taxonomy-loxo_job_state'] = __( 'States', 'loxo' );
        $columns['address'] = __( 'Address', 'loxo' );
        $columns['location'] = __( 'Location', 'loxo' );
        $columns['salary'] = __( 'Salary', 'loxo' );
        $columns['published'] = __( 'Published', 'loxo' );
        $columns['expires'] = __( 'Expires', 'loxo' );

        return $columns;
    }

    public function job_column_values( $column, $post_id ) {
        $job = new \Loxo\Job\Data( $post_id );
        switch ( $column ) :

            case 'job_id':
                echo $job->get_job_id();
                break;

            case 'address':
                if ( $job->get_address() ) {
                    echo $job->get_address();
                } else {
                    echo '-';
                }
                break;

            case 'location':
                $locations = array();
                if ( $job->get_zip() ) {
                    $locations[] = $job->get_zip();
                }
                if ( $job->get_city() ) {
                    $locations[] = $job->get_city();
                }
                echo join( ', ', $locations );
                break;

            case 'salary':
                if ( $job->get_salary() ) {
                    echo loxo_salary( $job->get_salary() );

                } elseif ( loxo_get_job_salary( $job ) ) {
                    echo loxo_get_job_salary( $job );
                    echo '<br/>';
                    printf( 
                        '<small><abbr title="%1$s">%2$s</abbr></small>', 
                        esc_attr__( 'This salary is automatically parsed from description' ),
                        esc_attr__( 'Auto Parsed' )
                    );
                }
                break;

            case 'published':
                if ( $job->get_date_published() ) {
                    echo wp_date( 'd M Y', strtotime( $job->get_date_published() ) );
                }
                break;

            case 'expires':
                $published_date = wp_date( 'Y-m-d', strtotime( $job->get_date_published() ) );
                if ( $job->get_date_expires() ) {
                    $date = wp_date( 'Y-m-d', strtotime( $job->get_date_expires() ) );
                } else {
                    $date = loxo_calculate_job_expiration( $job->get_date_published(), 'Y-m-d' );
                }

                echo '<input value="' . $date . '" data-id="' . $job->get_id() . '" data-min="' . $published_date . '" type="text" class="loxo-expiration-datepicker" readonly />';
            break;

        endswitch;
    }

	/**
	 * Register admin assets.
	 */
	public function enqueue_scripts( $hook ) {
        $screen = get_current_screen();

        if ( isset( $screen->id ) && 'edit-loxo_job' === $screen->id ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );

            wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
            wp_enqueue_style( 'jquery-ui' );

            wp_enqueue_style( 'loxo-admin' );
            wp_enqueue_script( 'loxo-admin' );
        }
		#echo $screen->id;
	}

    /**
     * Handle job page actions
     */
    public function handle_actions() {
        if ( isset( $_REQUEST['action'] ) && 'loxo_synchronize_job' === $_REQUEST['action'] ) {

            $job_id = $_REQUEST['job_id'];
            $synchronizer = new Synchronizer();
    		$synchronizer->synchronize_job( $job_id );

            wp_redirect( add_query_arg( array( 'action' => false, 'job_id' => false, 'settings-updated' => 'job-synchronized' ) ) );
            exit;
        }
    }

    public function job_preview_action( $actions, $post ) {
        if ( 'loxo_job' === $post->post_type && 'publish' === $post->post_status ) {
            $job = new Job_Data( $post->ID );

            if ( $job->get_job_id() ) {
                $actions['sync'] = sprintf( 
                    '<a href="%1$s">%2$s</a>',
                    esc_url( add_query_arg( array( 'action' => 'loxo_synchronize_job', 'job_id' => $job->get_job_id() ) ) ),
                    esc_html( __( 'Synchronize', 'loxo' ) )
                );
                $actions['view'] = sprintf( 
                    '<a href="%1$s">%2$s</a>',
                    esc_url( loxo_get_new_job_url( $job->get_job_id(), $job ) ),
                    esc_html( __( 'View', 'loxo' ) )
                );
            }
        }

        return $actions;
    }
}