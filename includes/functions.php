<?php
function loxo_salary( $salary ) {
	$_salary = preg_replace( '/[^0-9\.]/i', '', trim( $salary ) );
	if ( empty( $_salary ) ) {
		return $salary;
	}

	return '$' . number_format( $_salary, 0, '.', ',' );
}

add_action( 'template_redirect2', function(){
	$job = new \Loxo\Job\Data( 'loxo-job-469477' );
	#$job->set_salary( '$2000.00 - #5000.0 per week' );
	\Loxo\Utils::d( loxo_get_job_salary_data( $job ) );
	exit;
});

function loxo_get_job_salary( $job ) {
	$salary_data = loxo_get_job_salary_data( $job );
	if ( $salary_data ) {
		if ( ! empty( $salary_data['min'] ) && ! empty( $salary_data['max'] ) ) {
			return '$' . number_format( $salary_data['min'], 0, '.', ',' ) . ' - ' . '$' . number_format( $salary_data['max'], 0, '.', ',' );
		} elseif ( ! empty( $salary_data['value'] ) ) {
			return '$' . number_format( $salary_data['value'], 0, '.', ',' );
		}
	}

	return $job->get_salary();
}

/**
 * Get job salary data.
 * 
 * @param \Loxo\Job $job Loxo job object.
 */
function loxo_get_job_salary_data( $job ) {
	$data = array(
		'value' => '',
		'unit' => 'YEAR',
		'min' => '',
		'max' => ''
	);

	if ( $job->get_salary() ) {

		$salary_string = trim( $job->get_salary() );

		$salary = preg_replace( '/[^0-9\.]/i', '', $salary_string );

		// string - DOE etc.
		if ( empty( $salary ) ) {
			return false;
		}

		$data['unit'] = loxo_get_salary_unit( $salary_string );

		$range = loxo_get_salary_range( $salary_string );

		if ( $range ) {
			$data['min'] = $range['min'];
			$data['max'] = $range['max'];
			$data['value'] = $range['max'];
		} else {
			$salary = preg_replace( '/[^0-9\.]/i', '', $salary_string );
			// string - DOE etc.
			if ( empty( $salary ) ) {
				$data['value'] = $salary_string;
			} else {
				$data['value'] = number_format( $salary, 0, '.', '' );
			}
		}

		return $data;
	}

	if ( 'yes' === get_option( 'loxo_enable_salary_intelligence' ) ) {

		if ( $job->get_description() ) {
			$plain_desc = strtolower( strip_tags( $job->get_description() ) );

			if ( preg_match( '/min compensation: (\d+)/i', $plain_desc, $match ) ) {
				$data['min'] = $match['1'];
			}

			if ( preg_match( '/max compensation: (\d+)/i', $plain_desc, $match ) ) {
				$data['max'] = $match['1'];
			}

			if ( empty( $data['value'] ) ) {
				$data['value'] = $data['max'];
			}

			if ( ! empty( array_filter( $data ) ) ) {
				return $data;
			}
		}

		return false;
	}
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

function loxo_get_salary_range( $salary ) {
	if ( false !== strpos( strtolower( $salary ), 'per' ) ) {
		$salary = substr( $salary, 0, strpos( strtolower( $salary ), 'per' ) );
	}

	if ( false === strpos( $salary, '-' ) ) {
		return false;
	}

	$salary = preg_replace( '/[^0-9\.\-]/i', '', trim( $salary ) );
	$parts = explode( '-', $salary );

	if ( count( $parts ) !== 2 ) {
		return false;
	}

	return array(
		'min' => number_format( $parts[0], 0, '.', '' ),
		'max' => number_format( $parts[1], 0, '.', '' )
	);
}

function loxo_calculate_job_expiration( $date_posted, $format = 'Y-m-d' ) {
	return gmdate( $format, strtotime( $date_posted ) + ( DAY_IN_SECONDS * get_option( 'loxo_default_job_validity_days', 180 ) ) );
}

function loxo_sanitize_job_description( $desc ) {
	$desc = wpautop( $desc );
	$desc = str_replace(
		array(
			'<p>&nbsp;</p>'
		),
		array(
			''
		),
		$desc
	);
	$desc = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $desc );

	return $desc;
}

function loxo_get_sitemap_url() {
	return home_url( '/' . loxo_get_sitemap_name() . '.xml' );
}

function loxo_get_sitemap_name() {
	return 'loxo-jobs';
}


function loxo_get_new_job_url( $job_id, $job = '' ) {
	if ( loxo_get_listing_page_id() ) {
		if ( ! $job ) {
			$job = new \Loxo\Job\Data( 'loxo-job-' . $job_id );
		}

		if ( ! $job->get_id() ) {
			return false;
		}

		$job_title = $job->get_name();
		$job_slug = sanitize_title( $job_title );

		if ( $job->get_city() ) {
			$job_slug .= '-in-' . sanitize_title_with_dashes( $job->get_city() );
		}

		$job_slug .= '-' . $job_id;

		$url = untrailingslashit( get_permalink( loxo_get_listing_page_id() ) ) . '/' . $job_slug . '/';
	} else {
		$url = home_url( '?loxo_job_id=' . $job_id );
	}

	return $url;
}

function loxo_get_job_url( $job_id, $job_title = '' ) {
	if ( loxo_get_listing_page_id() ) {
		if ( ! $job_title ) {
			$job = new \Loxo\Job\Data( 'loxo-job-' . $job_id );
			if ( $job->get_id() ) {
				$job_title = $job->get_name();
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

	delete_option( 'loxo_api_credentials_error' );

	// Clear w3 total cache.
	if ( loxo_get_listing_page_id() ) {
		do_action( 'w3tc_flush_post', loxo_get_listing_page_id() );
		do_action( 'w3tc_flush_url', get_permalink( loxo_get_listing_page_id() ), null );
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
	$params = array(
		'page'     => 1,
		'per_page' => 100,
		'status' => 'active',
		'published_at_sort' => 'desc'
	);
	/*
	if ( get_option( 'loxo_active_job_status_id' ) ) {
		$params['job_status_id'] = get_option( 'loxo_active_job_status_id' );
	}
	*/

	$api_jobs = loxo_api_get_jobs( $params );
	if ( is_wp_error( $api_jobs ) ) {
		return $api_jobs;
	}

	$jobs = $api_jobs['results'];

	if ( $params['page'] < $api_jobs['total_pages'] ) {
		for ( $params['page'] = 2; $params['page'] <= $api_jobs['total_pages']; $params['page'] ++ ) {
			$api_jobs = loxo_api_get_jobs( $params );
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
function loxo_api_get_job( $id, $refresh = false ) {
	return loxo_api_get( "/jobs/{$id}/", array(), 300, $refresh );
}

/**
 * Get jobs.
 *
 * @return array Array of jobs.
 */
function loxo_api_get_jobs( $params = array(), $refresh = false ) {
	return loxo_api_get( '/jobs/', $params, 300, $refresh );
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
function loxo_api_get( $path, $params = array(), $ttl = 0, $refresh = false ) {
	$agency_key   = get_option( 'loxo_agency_key' );
	$api_username = get_option( 'loxo_api_username' );
	$api_password = get_option( 'loxo_api_password' );

	if ( ! $api_username || ! $api_username || ! $api_username ) {
		return new WP_Error( 
			'missing_credentials', 
			__( 'Could not connect to loxo. Missing api credentials.', 'loxo' )
		);
	}

	$cache_key = 'loxo_cache_'. md5( $agency_key . $path . serialize( $params ) );
	if ( $ttl > 0 && ! $refresh ) {
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
	loxo_log( 'API Request - ' . $path, $params );

	// Delete previously stored error.
	delete_option( 'loxo_api_credentials_error' );

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
		$response = new WP_Error( 'wrong_agency', __( 'Wrong Agency Key.', 'loxo' ) );
	} elseif ( 401 === $code ) {
		$response = new WP_Error( 'wrong_credentials', __( 'Wrong API Credentials.', 'loxo' ) );
	} elseif ( 500 === $code ) {
		$response = new WP_Error( 'not_exists', __( 'Resource not found.', 'loxo' ) );
	}

	if ( is_wp_error( $response ) ) {
		if ( in_array( $response->get_error_code(), array( 'wrong_agency', 'wrong_credentials' ) ) ) {
			update_option( 'loxo_api_credentials_error', $response->get_error_message() );
		}
		return $response;
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

	if ( ! $agency_key || ! $api_username || ! $api_username ) {
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