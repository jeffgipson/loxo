<?php
/* Listing Page Content */
?>
<div class="loxo-jobs-listing">
	<div class="loxo-jobs-filters">
		<form class="loxo-form" id="loxo-jobs-filter-form">
			<?php if ( ! is_wp_error( $job_categories ) ) : ?>
				<div class="field-row">
					<label class="field-label"><?php _e( 'Category', 'loxo' ); ?></label>
					<div class="control-wrap">
						<select class="field-control" id="loxo-job-category">
							<?php
							foreach ( $job_categories as $job_category ) {
								printf(
									'<option value="%s">%s (%d)</option>',
									$job_category['id'],
									$job_category['name'],
									$job_category['count']
								);
							}
							?>
						</select>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( ! is_wp_error( $job_types ) ) : ?>
			<div class="field-row">
				<label class="field-label"><?php _e( 'Type', 'loxo' ); ?></label>
				<div class="control-wrap">
					<select class="field-control" id="loxo-job-type">
						<?php
						foreach ( $job_types as $job_type ) {
							printf(
								'<option value="%s">%s (%d)</option>',
								$job_type['id'],
								$job_type['name'],
								$job_type['count']
							);
						}
						?>
					</select>
				</div>
			</div>
			<?php endif; ?>
			<?php if ( ! is_wp_error( $job_cities ) ) : ?>
			<div class="field-row">
				<label class="field-label"><?php _e( 'City', 'loxo' ); ?></label>
				<div class="control-wrap">
					<select class="field-control" id="loxo-job-city">
					<?php
					foreach ( $job_cities as $job_city ) {
						printf(
							'<option value="%s">%s (%d)</option>',
							$job_city['id'],
							$job_city['name'],
							$job_city['count']
						);
					}
					?>
					</select>
				</div>
			</div>
			<?php endif; ?>
			<div class="field-row">
				<input class="sm-button reset-button" type="reset" value="<?php _e( 'Reset', 'loxo' ); ?>" />
			</div>
		</form>
	</div>
	<div class="loxo-jobs">
		<div class="no-jobs"></div>
		<?php
		foreach ( $all_jobs as $job ) {
			include 'listing-job.php';
		}
		?>
	</div>
</div>
