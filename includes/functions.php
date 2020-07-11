<?php
function loxo_sanitize_job_description( $desc ) {
	$desc = str_replace(
		array(
			'<li class="MsoNoSpacing">'
		),
		array(
			'<li>'
		),
		$desc
	);
	$desc = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $desc );

	return wpautop( $desc );
}

function loxo_get_sitemap_url() {
	return home_url( '/' . loxo_get_sitemap_name() . '.xml' );
}

function loxo_get_sitemap_name() {
	return 'loxo-jobs';
}

function loxo_get_job_url( $job_id, $job_title = '' ) {
	if ( loxo_get_listing_page_id() ) {
		if ( ! $job_title ) {
			$job = loxo_api_get_job( $job_id );
			if ( ! is_wp_error( $job ) ) {
				$job_title = $job['title'];
			}
		}

		if ( $job_title ) {
			$job_slug = sanitize_title_with_dashes( $job_title ) . '-' . $job_id;
		} else {
			$job_slug = 'job-' . $job_id;
		}

		$url = untrailingslashit( get_permalink( loxo_get_listing_page_id() ) ) . '/' . $job_slug . '/';
	} else {
		$url = home_url( '?loxo_job_id=' . $job_id );
	}

	return $url;
}

function loxo_get_listing_page_id() {
	if ( ! get_option( 'loxo_listing_page_id' ) ) {
		return 0;
	}

	return (int) get_option( 'loxo_listing_page_id' );
}

function loxo_clear_all_cache() {
	// Clear transient.
	global $wpdb;
	$match = '%\_loxo_cache\_%';
	$options = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '$match'");
	if ( ! empty( $options ) ) {
		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}

	// Clear opcache.
	if ( function_exists( 'opcache_reset' ) ) {
		opcache_reset();
	}
}

/**
 * Get all jobs.
 *
 * @return mixed All jobs array or WP_Error.
 */
function loxo_get_all_jobs() {
	$page = 1;
	$per_page = 100;
	$cache_ttl = 300;

	$api_jobs = loxo_api_get_jobs( $page, $per_page, $cache_ttl );
	if ( is_wp_error( $api_jobs ) ) {
		return $api_jobs;
	}

	$jobs = $api_jobs['results'];

	if ( $page < $api_jobs['total_pages'] ) {
		for ( $page = 2; $page <= $api_jobs['total_pages']; $page ++ ) {
			# \Loxo\Utils::p( $page );
			$api_jobs = loxo_api_get_jobs( $page, $per_page, $cache_ttl );
			if ( ! is_wp_error( $api_jobs ) && ! empty( $api_jobs['results'] ) ) {
				$jobs = array_merge( $jobs, $api_jobs['results'] );
			}
		}
	}

	if ( ! empty( $jobs ) ) {
		uasort( $jobs, 'loxo_sort_by_published_at' );
	}

	return $jobs;
}

/**
 * Order items by priority
 *
 * @param  array $a [description]
 * @param  array $b [description]
 * @return interger [description]
 */
function loxo_sort_by_published_at( $a, $b ) {
	if ( ! isset( $a['published_at'] ) || ! isset( $b['published_at'] ) ) {
		return 1;
	}

	if ( strtotime( $a['published_at'] ) == strtotime( $b['published_at'] ) ) {
		return 0;
	}

	if ( strtotime( $a['published_at'] ) < strtotime( $b['published_at'] )) {
		return 1;
	}

	return -1;
}

/**
 * Get job from local cache.
 *
 * @return array Array of job types.
 */
function loxo_get_job_updated_at( $id ) {
	$all_jobs = loxo_get_all_jobs();
	if ( ! is_wp_error( $all_jobs ) ) {
		foreach ( $all_jobs as $job ) {
			if ( $job['id'] === $id ) {
				if ( isset( $job['updated_at'] ) ) {
					return $job['updated_at'];
				} else {
					return false;
				}
			}
		}
	}

	return false;
}


/**
 * Get job from local cache.
 *
 * @return array Array of job types.
 */
function loxo_get_job_published_at( $id ) {
	$all_jobs = loxo_get_all_jobs();
	if ( ! is_wp_error( $all_jobs ) ) {
		foreach ( $all_jobs as $job ) {
			if ( $job['id'] === $id ) {
				if ( isset( $job['published_at'] ) ) {
					return $job['published_at'];
				} else {
					return false;
				}
			}
		}
	}

	return false;
}

/**
 * Get active job status id.
 *
 * @return array Array of jobs.
 */
function loxo_api_get_active_job_status_id() {
	# return 12725;

	$statuses = loxo_api_get( '/job_statuses/', array(), DAY_IN_SECONDS );
	if ( is_wp_error( $statuses ) ) {
		return 0;
	}

	foreach ( $statuses as $statuse ) {
		if ( strtolower( $statuse['name'] ) === 'active' ) {
			return $statuse['id'];
		}
	}

	foreach ( $statuses as $status ) {
		if ( $status['default'] || $status['default'] === true || $status['default'] === 'true' ) {
			return $status['id'];
		}
	}

	if ( ! empty( $statuses ) ) {
		reset( $statuses );
		$status = key( $statuses );
		return $status['id'];
	}

	return 0;
}


/**
 * Get jobs types.
 *
 * @return array Array of job types.
 */
function loxo_get_job_types() {
	$all_jobs = loxo_get_all_jobs();
	if ( is_wp_error( $all_jobs ) ) {
		return array();
	}

	$types = array(
		array(
			'id' => 'any',
			'name' => 'Any',
			'count' => count( $all_jobs )
		)
	);
	$others_count = 0;

	foreach ( $all_jobs as $job ) {
		if ( empty( $job['job_type'] ) ) {
			$others_count ++;
			continue;
		}

		if ( ! array_key_exists( $job['job_type']['id'], $types ) ) {
			$types[ $job['job_type']['id'] ] = $job['job_type'];
			$types[ $job['job_type']['id'] ]['count'] = 1;
		} else {
			++ $types[ $job['job_type']['id'] ]['count'];
		}
	}

	if ( $others_count > 0 ) {
		$types[] = array(
			'id' => 'others',
			'name' => 'Others',
			'count' => $others_count
		);
	}

	return array_values( $types );
}


/**
 * Get jobs categories.
 *
 * @return array Array of job categories.
 */
function loxo_get_job_categories() {
	$all_jobs = loxo_get_all_jobs();
	if ( is_wp_error( $all_jobs ) ) {
		return array();
	}

	$categories = array(
		array(
			'id' => 'any',
			'name' => 'Any',
			'count' => count( $all_jobs )
		)
	);
	$others_count = 0;

	foreach ( $all_jobs as $job ) {
		if ( empty( $job['categories'] ) ) {
			++ $others_count;
			continue;
		}

		foreach ( $job['categories'] as $category ) {
			if ( ! array_key_exists( $category['id'], $categories ) ) {
				$categories[ $category['id'] ] = $category;
				$categories[ $category['id'] ]['count'] = 1;
			} else {
				++ $categories[ $category['id'] ]['count'];
			}
		}
	}

	if ( $others_count > 0 ) {
		$categories[] = array(
			'id' => 'others',
			'name' => 'Others',
			'count' => $others_count
		);
	}

	return array_values( $categories );
}


/**
 * Get job statuses.
 *
 * @return array Array of job statuses.
 */
function loxo_api_job_cities() {
	$all_jobs = loxo_get_all_jobs();
	if ( is_wp_error( $all_jobs ) ) {
		return array();
	}

	$cities = array(
		'any' => array(
			'id' => 'any',
			'name' => 'Any',
			'count' => count( $all_jobs )
		)
	);
	$others_count = 0;

	foreach ( $all_jobs as $job ) {
		if ( empty( $job['city'] ) ) {
			++ $others_count;
			continue;
		}

		if ( ! array_key_exists( $job['city'], $cities ) ) {
			$cities[ $job['city'] ] = array(
				'id' => $job['city'],
				'name' => $job['city'],
				'count' => 1
			);
		} else {
			++ $cities[ $job['city'] ]['count'];
		}
	}

	if ( $others_count > 0 ) {
		$cities[] = array(
			'id' => 'others',
			'name' => 'Others',
			'count' => $others_count
		);
	}

	return array_values( $cities );
}


/**
 * Get job
 *
 * @return array Job details.
 */
function loxo_api_get_job( $id ) {
	return loxo_api_get( "/jobs/{$id}/", array(), 300 );
}


/**
 * Get jobs.
 *
 * @return array Array of jobs.
 */
function loxo_api_get_jobs( $page = 1, $per_page = 20, $cache = 60 ) {
	$params = array(
		'page'     => $page,
		'per_page' => $per_page,
	);

	if ( loxo_api_get_active_job_status_id() ) {
		$params['job_status_id'] = loxo_api_get_active_job_status_id();
	}

	return loxo_api_get( '/jobs/', $params, $cache );
}


function loxo_api_get( $path, $params = array(), $ttl = 0 ) {
	$api_username = get_option( 'loxo_api_username' );
	$api_password = get_option( 'loxo_api_password' );
	$agency_key   = get_option( 'loxo_agency_key' );

	if ( ! $api_username || ! $api_username || ! $api_username ) {
		return new WP_Error( 'missing_credentials', __( 'Could not connect to loxo. Missing api credentials.', 'loxo' ) );
	}

	$cache_key = 'loxo_cache_'. md5( $agency_key . $path . serialize( $params ) );
	if ( $ttl > 0 && get_transient( $cache_key ) ) {
		return get_transient( $cache_key );
	}

	$api_endpoint = 'https://loxo.co/api/';

	$url = $api_endpoint . $agency_key . $path;
	if ( ! empty( $params ) ) {
		$url = add_query_arg( $params, $url );
	}

	$args = array(
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $api_username . ':' . $api_password )
		)
	);

	$response = wp_remote_get( $url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 404 === $code ) {
		return new WP_Error( 'notFound', __( 'Resource not found.' ) );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $ttl > 0 ) {
		set_transient( $cache_key, $body, $ttl );
	}

	return $body;
}

/**
 * Send job application request to loxo api.
 *
 * @param  int    $job_id      Loxo job id.
 * @param  array  $post_fields Array data: name, email, phone.
 * @param  array  $resume      Array data: resume name and file path.
 * @return mixed               Api response or WP_Error.
 */
function loxo_api_apply_job( $job_id, $post_fields = array(), $resume = array() ) {
	$api_username = get_option( 'loxo_api_username' );
	$api_password = get_option( 'loxo_api_password' );
	$agency_key   = get_option( 'loxo_agency_key' );

	if ( ! $api_username || ! $api_username || ! $api_username ) {
		return new WP_Error( 'missing_credentials', __( 'Could not retrive jobs. Missing api credentials.', 'loxo' ) );
	}

	$api_endpoint = 'https://loxo.co/api/';

	$url = $api_endpoint . $agency_key . '/jobs/' . $job_id . '/apply';

	$boundary = wp_generate_password( 24 );
	$headers  = array(
		'content-type' => 'multipart/form-data; boundary=' . $boundary,
		'Authorization' => 'Basic ' . base64_encode( $api_username . ':' . $api_password ),
	);

	$payload = '';

	// First, add the standard POST fields:
	foreach ( $post_fields as $name => $value ) {
		$payload .= '--' . $boundary;
		$payload .= "\r\n";
		$payload .= 'Content-Disposition: form-data; name="' . $name .
			'"' . "\r\n\r\n";
		$payload .= $value;
		$payload .= "\r\n";
	}

	// Upload the file
	$payload .= '--' . $boundary;
	$payload .= "\r\n";
	$payload .= 'Content-Disposition: form-data;';
	$payload .= ' name="resume";';
	$payload .= ' filename="' . $resume['name'] . '"';
	$payload .= "\r\n";
	//        $payload .= 'Content-Type: image/jpeg' . "\r\n";
	$payload .= "\r\n";
	$payload .= file_get_contents( $resume['file'] );
	$payload .= "\r\n";

	$payload .= '--' . $boundary . '--';

	$args = array(
		'headers' => array(
			'content-type' => 'multipart/form-data; boundary=' . $boundary,
			'Authorization' => 'Basic ' . base64_encode( $api_username . ':' . $api_password )
		),
		'body' => $payload,
	);

	$response = wp_remote_post( $url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	return $body;
}
