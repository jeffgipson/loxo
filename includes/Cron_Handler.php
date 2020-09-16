<?php
namespace Loxo;

class Cron_Handler {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'schedule_cronjobs' ), 15 );
		add_action( 'loxo_synchronize_jobs', array( $this, 'synchronize_jobs' ) );
		add_action( 'loxo_synchronize_job', array( $this, 'synchronize_job' ) );

		add_action( 'loxo_schedule_jobs_synchronization', array( $this, 'schedule_jobs_synchronization' ) );
		add_action( 'loxo_schedule_job_synchronization', array( $this, 'schedule_job_synchronization' ), 10, 2 );
	}

	/**
	 * Schedule required cronjobs
	 */
	public function schedule_cronjobs() {
		$this->schedule_jobs_synchronization();
	}

	/**
	 * Pre cache all jobs for better performance.
	 */
	public function synchronize_jobs() {
		$synchronizer = new Synchronizer();
		$synchronizer->synchronize_jobs();
	}

	/**
	 * Pre cache all jobs for better performance.
	 */
	public function synchronize_job( $job_id ) {
		$synchronizer = new Synchronizer();
		$synchronizer->synchronize_job( $job_id );
	}

	public function schedule_job_synchronization( $job_id, $delay = 0 ) {
		if ( ! wp_next_scheduled( 'loxo_synchronize_job', array( $job_id ) ) ) {
			wp_schedule_single_event( time() + $delay, 'loxo_synchronize_job', array( $job_id ) );
		}
	}

	public function schedule_jobs_synchronization() {
		if ( ! wp_next_scheduled( 'loxo_synchronize_jobs' ) ) {
			$interval = get_option( 'loxo_all_jobs_synchronizer_interval' );
			if ( ! $interval ) {
				$interval = 300;
			}

			wp_schedule_single_event( time() + $interval, 'loxo_synchronize_jobs' );
		}
	}
}
