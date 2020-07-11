<?php
/* Listing Page Content */
?>
<?php
printf(
	'<a id="job-%d" href="%s" class="loxo-job">
	<div class="job-inner">',
	$job['id'],
	loxo_get_job_url( $job['id'], $job['title'] )
);
	echo '<div class="job-header">';
		echo '<div class="job-id">';
			printf(
				'Job ID: %d',
				$job['id']
			);
		echo '</div>';
		echo '<h3 class="job-title">';
			echo $job['title'];
		echo '</h3>';
		echo '<div class="job-data">';
			echo '<span class="job-published">';
				printf(
					'Published: %s',
					wp_date( 'M jS Y', strtotime( $job['published_at'] ) )
				);
			echo '</span>';

			$locations = array();
			if ( ! empty( $job['city'] ) ) {
				$locations[] = $job['city'];
			}
			if ( ! empty( $job['state_code'] ) ) {
				$locations[] = $job['state_code'];
			}
			if ( ! empty( $locations ) ) {
				echo ' &middot; <span class="job-location">';
					printf(
						'Location: %s',
						implode( ', ', $locations )
					);
				echo '</span>';
			}
		echo '</div>';

	echo '</div>';

	echo '<div class="job-meta">';
		if ( ! empty( $job['salary'] ) ) {
			echo '<span class="job-salary">';
				echo $job['salary'];
			echo '</span>';
		}

		echo '<span class="job-type">';
			echo $job['job_type']['name'];
		echo '</span>';

	echo '</div>';

echo '</div></a>';
