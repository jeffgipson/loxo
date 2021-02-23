<?php
namespace Loxo;

/**
 * Provide sitemap facility for jobs.
 *
 * @package Loxo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap Class.
 *
 * @class Sitemap
 */
class Latest_Jobs_Shortcode {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_shortcode( 'loxo-latest-jobs', array( $this, 'latest_jobs_shortcode' ), 10 );
	}

	public function latest_jobs_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit' => 3
			),
			$atts
		);

		$api_jobs = loxo_api_get_jobs( 1, $atts['limit'], 300 );
		if ( is_wp_error( $api_jobs ) ) {
			return '';
		}

		ob_start();

		foreach ( $api_jobs['results'] as $job ) :
		?>
		<div class="wpb_column vc_column_container vc_col-sm-4 sm-border-box">
			<div class="wpb_wrapper">
				<div class="sm-featurebox sm_content_element sm-wrap-double-circle sm-icon-top" id="sm_featurebox-6">
					<div class="featurebox-icon sm-blue">
						<div class="icon-wrap sm-white"><i class="fa fa-star"></i></div>
						<div class="border-overlay sm-bg-color"></div>
					</div>
					<div class="featurebox-content">
						<p>
							<?php 
							echo $job['title']; 

							if ( ! empty( $job['salary'] ) ) {
								echo ' – ' . str_replace( ',000', 'K', $job['salary'] );
							}
							?>
						</p>
					</div>
					<div class="featurebox-link">
						<a href="<?php echo loxo_get_job_url( $job['id'], $job['title'] ); ?>">Learn More<i class="fa fa-caret-right"></i></a>
					</div>
				</div>
			</div>
		</div>
		<?php
		endforeach;

		return ob_get_clean();
	}
}
