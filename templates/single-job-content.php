<?php
if ( get_option( 'loxo_listing_page_id' ) ) {
	printf(
		'<p><a href="%s"><i class="fa fa-chevron-left"></i> %s</a></p>',
		get_permalink( get_option( 'loxo_listing_page_id' ) ),
		__( 'Back to Jobs', 'loxo' )
	);
}

/*
echo '## DEBUG ## job id: ';
echo $local_job->get_id();
if ( $local_job->get_description() ) {
	echo ' description: local';
} else {
	echo ' description: api';
}
*/

echo '<div class="loxo-single-job">';
	echo '<h1 class="job-title">';
		echo $local_job->get_name();
	echo '</h1>';

	echo '<dl class="job-meta">';
		if ( ! empty( $local_job->get_salary() ) ) {
			echo '<dt class="job-salary">' . __( 'Compensation', 'loxo' ) . '</dt>';
			echo '<dd class="job-salary">' . loxo_salary( $local_job->get_salary() ) . '</dd>';
		}

		echo '<dt class="job-type">' . __( 'Type', 'loxo' ) . '</dt>';
		echo '<dd class="job-type">' . $local_job->get_type() . '</dd>';

		$locations = array();
		if ( ! empty( $local_job->get_city() ) ) {
			$locations[] = $local_job->get_city();
		}
		if ( $local_job->get_state_id() ) {
			$state = new \Loxo\Job_State\Data( $local_job->get_state_id() );
			$locations[] = $state->get_name();
		}
		if ( ! empty( $locations ) ) {
			echo '<dt class="job-location">' . __( 'Location', 'loxo' ) . '</dt>';
			echo '<dd class="job-location">' . implode( ', ', $locations ) . '</dd>';
		}
	echo '</dl>';
	?>
	<div class="job-content">
		<div class="job-description">
			<?php 
			if ( $local_job->get_description() ) {
				echo loxo_sanitize_job_description( $local_job->get_description() );
			} else {
				echo loxo_sanitize_job_description( $job['description'] );
			}
			?>
		</div>
		<div class="job-apply">
			<?php if ( 'publish' === $local_job->get_status() ) : ?>
			<div class="loxo-job-share-icons">
				<?php
					printf(
						'<a href="http://www.facebook.com/sharer.php?u=%s" title="%s"><i class="fa fa-facebook"></i></a>',
						loxo_get_job_url( $local_job->get_job_id(), $local_job->get_name() ),
						__( 'Share on Facebook', 'loxo' )
					);
					printf(
						'<a href="https://twitter.com/share?url=%s" title="%s"><i class="fa fa-twitter"></i></a>',
						loxo_get_job_url( $local_job->get_job_id(), $local_job->get_name() ),
						__( 'Share on Twitter', 'loxo' )
					);
					printf(
						'<a href="http://www.linkedin.com/shareArticle?mini=true&url=%s" title="%s"><i class="fa fa-linkedin"></i></a>',
						loxo_get_job_url( $local_job->get_job_id(), $local_job->get_name() ),
						__( 'Share on LinkedIn', 'loxo' )
					);
					printf(
						'<a href="mailto:?subject=I wanted you to check this job&amp;body=Check out this job %s" title="%s"><i class="fa fa-envelope"></i></a>',
						loxo_get_job_url( $local_job->get_job_id(), $local_job->get_name() ),
						__( 'Share by Email', 'loxo' )
					);
				?>
			</div>
			<?php endif; ?>
			<?php

			$show_form = true;
			if ( isset( $_REQUEST['applied'] ) && $_REQUEST['applied'] ) {
				echo '<div class="loxo-alert loxo-alert-success loxo-job-applied">';
				echo '<i class="fa fa-thumbs-up alert-icon"></i>';
				echo '<div class="alert-heading">' . __( 'Congratulations!!!', 'loxo' ) . '</div>';
				echo '<div class="alert-message">' . __( 'You have successfully applied for this job.', 'loxo' ) . '</div>';
				echo '</div>';

				$show_form = false;
			} elseif ( 'publish' !== $local_job->get_status() ) {
				echo '<div class="loxo-alert loxo-alert-error loxo-job-applied">';
				echo '<i class="fa fa-exclamation-triangle alert-icon"></i>';
				echo '<div class="alert-heading">' . __( 'Job Closed.', 'loxo' ) . '</div>';
				echo '</div>';

				$show_form = false;
			}

			if ( ! empty( $_REQUEST['application-error'] ) ) {
				echo '<div class="loxo-alert loxo-alert-error loxo-job-not-applied">';
				$errors = explode( '|', urldecode( $_REQUEST['application-error'] ) );
				echo '<div class="alert-message">' . __( 'Could not apply for job. Please fix the error before resubmitting.', 'loxo' ) . '</div>';
				echo '<div class="alert-errors">';
				foreach ( $errors as $error ) {
					echo '<div class="alert-error">' . wp_unslash( $error ) . '</div>';
				}
				echo '</div>';
				echo '</div>';
			}
			?>

			<?php if ( $show_form ) :	?>
			<form class="loxo-form loxo-job-apply-form" method="post" enctype="multipart/form-data">
				<h4><?php _e( 'Apply', 'loxo' ); ?></h4>
				<div class="field-row">
					<label class="field-label" for="name"><?php _e( 'Name:', 'loxo' ); ?></label>
					<div class="control-wrap">
						<input type="text" id="name" name="name" class="field-control" />
					</div>
				</div>
				<div class="field-row">
					<label class="field-label" for="email"><?php _e( 'Email:', 'loxo' ); ?></label>
					<div class="control-wrap">
						<input type="email" id="email" name="email" class="field-control" />
					</div>
				</div>
				<div class="field-row">
					<label class="field-label" for="phone"><?php _e( 'Phone:', 'loxo' ); ?></label>
					<div class="control-wrap">
						<input type="text" id="phone" name="phone" class="field-control" />
					</div>
				</div>
				<div class="field-row">
					<label class="field-label" for="resume"><?php _e( 'Resume:', 'loxo' ); ?></label>
					<div class="control-wrap">
						<input type="file" id="resume" name="resume" class="field-control" />
					</div>
				</div>
				<div class="field-row">
					<input type="submit" value="<?php _e( 'Apply', 'loxo' ); ?>" />
					<input type="hidden" name="job_id" value="<?php echo $local_job->get_job_id(); ?>" />
					<input type="hidden" name="action" value="loxo_apply_to_job" />
					<?php wp_nonce_field( 'loxo-apply-to-job-' . $local_job->get_job_id() ); ?>
				</div>
			</form>
			<?php endif; ?>
		</div>
	</div>
</div>
