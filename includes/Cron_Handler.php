<?php
namespace Loxo;

class Cron_Handler {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'schedule_cronjobs' ), 15 );
		add_action( 'loxo_synchronize_all_jobs', array( $this, 'synchronize_all_jobs' ) );
		add_action( 'loxo_synchronize_single_job', array( $this, 'synchronize_single_job' ) );
    }

    /**
     * Schedule required cronjobs
     */
    public function schedule_cronjobs() {
		// Schedule a cronjob to pre cache all jobs.
		if ( ! wp_next_scheduled( 'loxo_synchronize_all_jobs' ) ) {
			$interval = get_option( 'loxo_all_jobs_synchronizer_interval' );
			if ( ! $interval ) {
				$interval = 300;
			}

			wp_schedule_single_event( time() + $interval, 'loxo_synchronize_all_jobs' );
		}
    }

    /**
	 * Pre cache all jobs for better performance.
	 */
	public function synchronize_all_jobs() {
		$synchronizer = new Synchronizer();
		$synchronizer->synchronize_jobs();
	}

	/**
	 * Pre cache all jobs for better performance.
	 */
	public function synchronize_single_job( $job_id ) {
		$synchronizer = new Synchronizer();
		$synchronizer->synchronize_job( $job_id );
	}
}