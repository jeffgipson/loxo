<?php
namespace Loxo;

use Loxo\Job\Data as Job_Data;
use Loxo\Job_Category\Data as Job_Category_Data;
use Loxo\Job_State\Data as Job_State_Data;

class Synchronizer {

    protected $logs = [];

    public function display_logs() {
        Utils::p( $this->logs );
    }

    /**
     * Delete jobs, categories & states.
     */
    public function cleanup() {
        $taxonomies = array( 'loxo_job_cat', 'loxo_job_state' );
        $post_types = array( 'loxo_job' );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms( array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            foreach ( $terms as $term ) {
                wp_delete_term( $term->term_id, $taxonomy );
            }
        }

        foreach ( $post_types as $post_type ) {
            $posts = get_posts( array(
                'post_type' => $post_type,
                'post_status' => 'all',
                'posts_per_page' => -1
            ));

            foreach ( $posts as $post ) {
                wp_delete_post( $post->ID, true );
            }
        }
    }

    /**
     * Synchronize jobs from loxo.
     */
    public function synchronize_jobs( $limit = null ) {
        loxo_log( 'Syncronizing jobs' );
        $jobs = loxo_get_all_jobs();

        if ( is_wp_error( $jobs ) ) {
            loxo_log( 'Loxo Api Error', array( 'error' => $jobs ) );
            return false;
        }

        // Debug
        /*
        $categories = array();
        foreach ( $jobs as $job ) {
            if ( ! empty( $job['categories' ] ) ) {
                foreach ( $job['categories' ] as $category ) {
                    if ( ! isset( $categories[ $category['name'] ] ) ) {
                        $categories[ $category['name'] ] = 1;
                    } else {
                        ++ $categories[ $category['name'] ];
                    }
                }
            }
        }
        loxo_log( 'All cats', $categories );
        */


        if ( $timestamp = wp_next_scheduled( 'loxo_synchronize_jobs' ) ) {
            wp_unschedule_event( $timestamp, 'loxo_synchronize_jobs'  );
        }

        if ( ! $limit ) {
            // Add a meta key to all existing local job. This makes sure we can delete unavailable/inactive jobs from local.
            $this->jobs_to_be_updated();
        }

        if ( $limit ) {
            $jobs = array_slice( $jobs, 0, $limit );
        }

        $this->logs = [];
        foreach ( $jobs as $job ) {
            $this->logs[] = $this->synchronize_collection_job( $job );
        }

        if ( ! $limit ) {
            $this->hide_orphan_jobs();
        }
    }

    public function jobs_to_be_updated() {
        $posts = get_posts( array(
            'post_type' => 'loxo_job',
            'post_status' => 'publish',
            'posts_per_page' => -1
        ));

        foreach ( $posts as $post ) {
            update_post_meta( $post->ID, '_loxo_update_scheduled', time() );
        }
    }

    public function hide_orphan_jobs() {
        $posts = get_posts( array(
            'post_type' => 'loxo_job',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_loxo_update_scheduled'
        ));

        foreach ( $posts as $post ) {
            wp_update_post( array(
                'ID' =>  $post->ID,
                'post_status' =>  'pending'
            ));
            delete_post_meta( $post->ID, '_loxo_update_scheduled' );
        }
    }

    /**
     * Import a job from collection/jobs endpoint.
     */
    public function synchronize_collection_job( $job_data ) {
        #loxo_log( 'Syncronizing collection job', $job_data );
        $slug = 'loxo-job-' . $job_data['id'];

        try {
            $job = new \Loxo\Job\Data( $slug );

            // Existing local job.
            if ( $job->get_id() ) {
                delete_post_meta( $job->get_id(), '_loxo_update_scheduled' );
            }

            $job->set_props( $this->get_job_data_props( $job_data ) );
            if ( ! empty( $job_data['published_at'] ) ) {
                $job->set_date_published( get_date_from_gmt( $job_data['published_at'] ) );
            }
            $job->set_date_checked( current_time( 'mysql' ) );
            $job->set_status( 'publish' );

            $job->save();

            if ( ! $job->get_description() ) {
                // Schedule so that job description gets updated.
                do_action( 'loxo_schedule_job_synchronization', $job->get_job_id(), 5 );
            }

            return $job->get_id();

        } catch ( \Loxo\Exception\Exception $e ) {
            loxo_log( 'Job save error', $e->getMessage() );
            return $e->getMessage();
        }
    }

    public function synchronize_job( $job_id ) {
        if ( $timestamp = wp_next_scheduled( 'loxo_synchronize_job', $job_id ) ) {
            wp_unschedule_event( $timestamp, 'loxo_synchronize_job', $job_id );
        }

        $job_data = loxo_api_get_job( $job_id, true );
		if ( is_wp_error( $job_data ) ) {
			return;
		}

        $slug = 'loxo-job-' . $job_data['id'];

        try {
            $job = new \Loxo\Job\Data( $slug );

            $job->set_props( $this->get_job_data_props( $job_data ) );
            $job->set_description( $job_data['description'] );
            $job->set_date_checked( current_time( 'mysql' ) );

            // While checking single job api, make sure to set status by status field.
            if ( isset( $job_data['status']['name'] ) && $job_data['status']['name'] === 'Active' ) {
                $job->set_status( 'publish' );
            } else {
                $job->set_status( 'pending' );
            }

            $job->set_user_id( '0' );

            $job->save();

            return $job->get_id();

        } catch ( \Loxo\Exception\Exception $e ) {
            return $e->getMessage();
        }
    }

    protected function get_job_data_props( $job_data ) {
        $job_state_id = 0;

		$job_state = new Job_State_Data();
		if ( $job_data['state_code'] ) {
			try {
				$job_state->set_props([
					'name' => $job_data['state_code']
				]);
				$job_state->save();
				$job_state_id = $job_state->get_id();
			} catch ( \Loxo\Exception\Resource_Exists $e ) {
				$job_state_id = (int) $e->getCode();
			} catch ( \Loxo\Exception\Exception $e ) {
			}
		}

		$job_category_ids = array();

		if ( $job_data['categories'] ) {
			foreach ( $job_data['categories'] as $category ) {
				$job_category = new Job_Category_Data();

				try {
					$job_category->set_props([
						'name' => $category['name']
					]);
					$job_category->save();
					$job_category_ids[] = $job_category->get_id();
                    # loxo_log( 'New category', $job_category->get_data() );

				} catch ( \Loxo\Exception\Resource_Exists $e ) {
                    # loxo_log( 'Existing category', $e->getCode() );
					$job_category_ids[] = (int) $e->getCode();
				} catch ( \Loxo\Exception\Exception $e ) {
				}
			}
        }

        $slug = 'loxo-job-' . $job_data['id'];

        $props = [
            'name' => $job_data['title'],
            'slug' => $slug,
            'job_id' => $job_data['id'],

            'city' => $job_data['city'],
            'zip' => $job_data['zip'],
            'country_code' => $job_data['country_code'],
            'salary' => $job_data['salary'],
            'country_code' => $job_data['country_code'],
            'type' => $job_data['job_type']['name'],
            'address' => $job_data['address'],

            'state_id' => $job_state_id,
            'category_ids' => $job_category_ids,
        ];

        return $props;
    }
}
