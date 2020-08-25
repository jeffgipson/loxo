<?php
/* Listing Page Content */
?>
<?php
printf(
	'<a id="job-%d" href="%s" class="loxo-job">
	<div class="job-inner">',
	$job->get_job_id(),
	loxo_get_job_url( $job->get_job_id(), $job->get_name() )
);
	echo '<div class="job-header">';
		echo '<div class="job-id">';
			printf(
				'Job ID: %d',
				$job->get_job_id()
			);
		echo '</div>';
		echo '<h3 class="job-title">';
			echo $job->get_name();
		echo '</h3>';

		echo '<div class="job-data">';
			echo '<span class="job-published">';
				printf(
					'Published: %s',
					wp_date( 'M jS Y', strtotime( $job->get_date_published() ) )
				);
			echo '</span>';

			$locations = array();
			if ( $job->get_city() ) {
				$locations[] = $job->get_city();
			}
			if ( $job->get_state_id() ) {
				$state = new \Loxo\Job_State\Data( $job->get_state_id() );
				$locations[] = $state->get_name();
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
		if ( $job->get_salary() ) {
			echo '<span class="job-salary">';
				echo loxo_salary( $job->get_salary() );
			echo '</span>';
		}

		echo '<span class="job-type">';
			echo $job->get_type();
		echo '</span>';

	echo '</div>';

	if ( $job->get_description() ) {
		// TODO: Store job summary on post_excerpt rather than parsing description.
		$summary = loxo_sanitize_job_description( $job->get_description() );
		$summary = str_replace( "&nbsp;", "", wp_strip_all_tags( $summary ) );

		echo '<div class="job-excerpt">';
		echo wp_trim_words( $summary, 40, '...' );
		echo '</div>';
	}

echo '</div></a>';
