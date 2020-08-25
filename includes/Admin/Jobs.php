<?php
namespace Loxo\Admin;

use \Loxo\Job\Data as Job_Data;
use \Loxo\Synchronizer;

class Jobs {
    function __construct() {
        add_filter( 'post_row_actions', array( $this, 'job_preview_action' ), 10, 2 );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
    }

    public function handle_actions() {
        if ( isset( $_REQUEST['action'] ) && 'loxo_synchronize_job' === $_REQUEST['action'] ) {

            $job_id = $_REQUEST['job_id'];
            $synchronizer = new Synchronizer();
    		$synchronizer->synchronize_job( $job_id );

            wp_redirect( add_query_arg( array( 'action' => false, 'job_id' => false ) ) );
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
                    esc_url( loxo_get_job_url( $job->get_job_id(), $job->get_name() ) ),
                    esc_html( __( 'View', 'loxo' ) )
                );
            }
        }
        return $actions;
    }
}