<?php
/* Listing Page Content */
?>
<div class="loxo-jobs-listing">
	<div class="loxo-jobs-filters">
		<form class="loxo-form" action="<?php echo get_permalink(); ?>" id="loxo-jobs-filter-form" method="GET">
			<?php if ( ! is_wp_error( $job_categories ) ) : ?>
				<div class="field-row">
					<label class="field-label"><?php _e( 'Category', 'loxo' ); ?></label>
					<div class="control-wrap">
						<select class="field-control" id="loxo-job-category" name="job_category">
							<?php
							foreach ( $job_categories as $job_category ) {
								printf(
									'<option value="%1$s"%2$s>%3$s (%4$d)</option>',
									$job_category['name'],
									$selected_job_category === $job_category['name'] ? ' selected="selected"' : '',
									$job_category['name'],
									$job_category['count']
								);
							}
							?>
						</select>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( ! is_wp_error( $job_states ) ) : ?>
			<div class="field-row">
				<label class="field-label"><?php _e( 'State', 'loxo' ); ?></label>
				<div class="control-wrap">
					<select class="field-control" id="loxo-job-state" name="job_state">
					<?php
					foreach ( $job_states as $job_state ) {
						printf(
							'<option value="%s">%s (%d)</option>',
							$job_state['name'],
							$job_state['name'],
							$job_state['count']
						);
					}
					?>
					</select>
				</div>
			</div>
			<?php endif; ?>
			<div class="field-row">
				<input class="sm-button reset-button" type="submit" value="<?php _e( 'Filter', 'loxo' ); ?>" />
			</div>
		</form>
	</div>
	<div class="loxo-jobs">
		<?php
		if ( ! empty( $jobs ) ) {
			foreach ( $jobs as $job ) {
				include 'listing-job.php';
			}

			$big = 999999999; // need an unlikely integer
			echo '<div class="loxo-pagination" id="pagination">';
				echo paginate_links( array(
					'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format' => '?paged=%#%',
					'current' => max( 1, get_query_var('paged') ),
					'total' => $jobs_query->max_num_pages
				) );
			echo '</div>';

		} else {
			echo '<p>' . __( 'No jobs available.', 'loxo' ) . '</p>';
		}
		?>
	</div>
</div>
