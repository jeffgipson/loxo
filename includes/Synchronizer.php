<?php
namespace Loxo;

class Synchronizer {

    protected $logs = [];

    public function display_logs() {
        Utils::p( $this->logs );
    }

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

    public function synchronize_jobs( $limit = null ) {
        $this->jobs_to_be_updated();

        // loxo_clear_all_cache();
        $jobs = loxo_get_all_jobs();

        if ( is_wp_error( $jobs ) ) {
            return $jobs;
        }

        if ( $limit ) {
            $jobs = array_slice( $jobs, 0, $limit );
        }

        $this->logs = [];
        foreach ( $jobs as $job ) {
            $this->logs[] = $this->synchronize_collection_job( $job );
        }

        $this->hide_orphan_jobs();
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
        $slug = 'loxo-job-' . $job_data['id'];

        try {
            $job = new \Loxo\Job\Data( $slug );

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

            return $job->get_id();

        } catch ( \Loxo\Exception\Exception $e ) {
            return $e->getMessage();
        }
    }

    public function synchronize_job( $job_id ) {
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
            $job->set_status( 'publish' );
            $job->set_user_id( '0' );

            $job->save();

            return $job->get_id();

        } catch ( \Loxo\Exception\Exception $e ) {
            return $e->getMessage();
        }
    }

    protected function get_job_data_props( $job_data ) {
        $job_state_id = 0;

		$job_state = new \Loxo\Job_State\Data();
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
				$job_category = new \Loxo\Job_Category\Data();

				try {
					$job_category->set_props([
						'name' => $category['name'],
						'slug' => 'loxo-category-' . $category['id']
					]);
					$job_category->save();
					$job_category_ids[] = $job_category->get_id();
				} catch ( \Loxo\Exception\Resource_Exists $e ) {
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

            'state_id' => $job_state_id,
            'category_ids' => $job_category_ids,
        ];

        return $props;
    }
}
