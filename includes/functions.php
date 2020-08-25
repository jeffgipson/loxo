<?php
function loxo_salary( $salary ) {
	$_salary = preg_replace( '/[^0-9\.]/i', '', trim( $salary ) );
	return '$ ' . number_format( $_salary, 2, '.', ',' );
}

function loxo_get_salary_unit( $salary ) {
	$_salary = preg_replace( '/[^0-9\.]/i', '', trim( $salary ) );

	$unit_text = 'YEAR';

	if ( ! empty( $_salary ) ) {
		if ( false !== strpos( strtolower( $salary ), 'per hour' ) ) {
			$unit_text = 'HOUR';
		} elseif ( false !== strpos( strtolower( $salary ), 'per day' ) ) {
			$unit_text = 'DAY';
		} elseif ( false !== strpos( strtolower( $salary ), 'per week' ) ) {
			$unit_text = 'WEEK';
		} elseif ( false !== strpos( strtolower( $salary ), 'per month' ) ) {
			$unit_text = 'MONTH';
		}
	}

	return $unit_text;
}

function loxo_sanitize_job_description( $desc ) {
	// return $desc;
	/*
	$desc = str_replace(
		array(
			'<li class="MsoNoSpacing">'
		),
		array(
			'<li>'
		),
		$desc
	);*/
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
			if ( ! is_wp_error( $job ) && isset( $job['title'] ) ) {
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
	$cache_ttl = 300;

	$params = array(
		'page'     => 1,
		'per_page' => 100,
	);
	if ( get_option( 'loxo_active_job_status_id' ) ) {
		$params['job_status_id'] = get_option( 'loxo_active_job_status_id' );
	}

	$api_jobs = loxo_api_get_jobs( $params, $cache_ttl );
	if ( is_wp_error( $api_jobs ) ) {
		return $api_jobs;
	}

	$jobs = $api_jobs['results'];

	if ( $params['page'] < $api_jobs['total_pages'] ) {
		for ( $params['page'] = 2; $params['page'] <= $api_jobs['total_pages']; $params['page'] ++ ) {
			$api_jobs = loxo_api_get_jobs( $params, $cache_ttl );
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
 * Get active job status id.
 *
 * @return array Array of jobs.
 */
function loxo_api_get_job_statuses() {
	return loxo_api_get( '/job_statuses/', array(), DAY_IN_SECONDS );
}

/**
 * Get job
 *
 * @return array Job details.
 */
function loxo_api_get_job( $id, $ttl = 300 ) {
	return loxo_api_get( "/jobs/{$id}/", array(), $ttl );
}

/**
 * Get jobs.
 *
 * @return array Array of jobs.
 */
function loxo_api_get_jobs( $params = array(), $cache = 60 ) {
	return loxo_api_get( '/jobs/', $params, $cache );
}

/**
 * Perform an GET request on LOXO API.
 * 
 * @param string $path Api path.
 * @param array $params Parameters.
 * @param int $ttl Cache time to live.
 * 
 * @return mixed.
 */
function loxo_api_get( $path, $params = array(), $ttl = 0 ) {
	$agency_key   = get_option( 'loxo_agency_key' );
	$api_username = get_option( 'loxo_api_username' );
	$api_password = get_option( 'loxo_api_password' );

	if ( ! $api_username || ! $api_username || ! $api_username ) {
		return new WP_Error( 
			'missing_credentials', 
			__( 'Could not connect to loxo. Missing api credentials.', 'loxo' )
		);
	}

	if ( $ttl > 0 ) {
		$cache_key = 'loxo_cache_'. md5( $agency_key . $path . serialize( $params ) );
		if ( false !== get_transient( $cache_key ) ) {
			return get_transient( $cache_key );
		}
	}

	$api_endpoint = 'https://loxo.co/api/';

	$url = $api_endpoint . $agency_key . $path;
	if ( ! empty( $params ) ) {
		$url = add_query_arg( $params, $url );
	}

	// Do log.
	loxo_log( 'loxo api request - ' . str_replace( $api_endpoint, '', $url ) );

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
		return new WP_Error( 'notFound', __( 'Resource not found.', 'loxo' ) );
	} elseif ( 500 === $code ) {
		return new WP_Error( 'notFound', __( 'Resource not found.', 'loxo' ) );
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
	$agency_key   = get_option( 'loxo_agency_key' );
	$api_username = get_option( 'loxo_api_username' );
	$api_password = get_option( 'loxo_api_password' );

	if ( ! $api_username || ! $api_username || ! $api_username ) {
		return new WP_Error( 
			'missing_credentials', 
			__( 'Could not apply on job. Missing api credentials.', 'loxo' )
		);
	}

	$api_endpoint = 'https://loxo.co/api/';

	$url = $api_endpoint . $agency_key . '/jobs/' . $job_id . '/apply';

	$boundary = wp_generate_password( 24 );
	$headers  = array(
		'content-type' => 'multipart/form-data; boundary=' . $boundary,
		'Authorization' => 'Basic ' . base64_encode( $api_username . ':' . $api_password ),
	);

	$payload = '';

	// First, add the standard POST fields.
	foreach ( $post_fields as $name => $value ) {
		$payload .= '--' . $boundary;
		$payload .= "\r\n";
		$payload .= 'Content-Disposition: form-data; name="' . $name .
			'"' . "\r\n\r\n";
		$payload .= $value;
		$payload .= "\r\n";
	}

	// Upload the file.
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

/**
 * Log
 */
function loxo_log( $message, $context = array() ) {
	if ( empty( $context ) ) {
		$context = array(
			'Cron' => (int) wp_doing_cron(),
			'Ajax' => (int) wp_doing_ajax()
		);
	}

	do_action(
		'w4_loggable_log',
		'Loxo',
		$message,
		$context
	);
}