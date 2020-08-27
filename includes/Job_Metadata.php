<?php
namespace Loxo;

/**
 * Listing handler file.
 *
 * @package Loxo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Class.
 *
 * @class Query
 */
class Job_Metadata {

	public $job = array();

	public function __construct( $job ) {
		$this->job = $job;
	}

	public function display() {
		$title = sprintf( '%s | %s', $this->job->get_name(), get_bloginfo( 'sitename' ) );
		$url = loxo_get_job_url( $this->job->get_job_id(), $this->job->get_name() );
		$description = $this->get_job_meta_description();

		$schema = $this->get_job_schema_data();

		?>
<!-- Metadata Generate with Loxo plugin -->
	<title><?php echo $title; ?></title>
	<meta name="description" content="<?php echo $description; ?>" />
	<meta name="robots" content="index, follow" />
	<meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
	<meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
	<link rel="canonical" href="<?php echo $url; ?>" />
	<meta property="og:type" content="article" />
	<meta property="og:locale" content="<?php echo get_locale(); ?>" />
	<meta property="og:title" content="<?php echo $title; ?>" />
	<meta property="og:description" content="<?php echo $description; ?>" />
	<meta property="og:url" content="<?php echo $url; ?>" />
	<meta property="og:site_name" content="<?php echo get_bloginfo( 'sitename' ); ?>" />
<?php if ( $this->job->get_date_updated() ) : ?>
	<meta property="article:modified_time" content="<?php echo gmdate( DATE_W3C, strtotime( $this->job->get_date_updated() ) ); ?>" />
<?php endif; ?>
<?php if ( ! is_wp_error( $schema ) ) : ?>
	<script type="application/ld+json">
		<?php echo json_encode( $schema ); ?>

	</script>
	<!-- / Loxo plugin. -->
<?php endif; ?>
<?php
	}

	private function get_job_meta_description() {
		$description = sprintf( 'Hiring %s - %s.', ucwords( strtolower( $this->job->get_name() ) ), $this->job->get_type() );

		if ( ! empty( $this->job->get_city() ) ) {
			$description .= sprintf( ' Job location %s.', $this->job->get_city() );
		}

		if ( ! empty( $this->job->get_salary() ) ) {
			$description .= sprintf( ' Compensation %s.', $this->job->get_salary() );
		}

		return $description;
	}

	private function get_job_schema_data() {
		// Date posted.
		$date_posted = gmdate( 'Y-m-d', strtotime( $this->job->get_date_published() ) );

		// Valid through.
		if ( $this->job->get_date_expires() ) {
			$valid_through = gmdate( 'Y-m-d', strtotime( $this->job->get_date_expires() . ' 00:00:00' ) );
		} else {
			$valid_through = loxo_calculate_job_expiration( $date_posted );
		}

		// Default employmentType is full time, change if needed.
		$employment_type = 'FULL_TIME';
		if ( $this->job->get_type() ) {
			if ( 'Contract' === $this->job->get_type() ) {
				$employment_type = "CONTRACTOR";
			}
		}

		$schema = array(
			"@context"    => "https://schema.org/",
			"@type"       => "JobPosting",
			"title"       => $this->job->get_name(),
			"description" => $this->job->get_description(),
			"identifier"  => array(
				"@type" => "PropertyValue",
				"name"  => get_option( 'loxo_hiring_company_name', get_bloginfo( 'sitename') ),
				"value" => $this->job->get_job_id()
			),
			"datePosted"         => $date_posted,
			"validThrough"       => $valid_through,
			"employmentType"     => $employment_type,
			"hiringOrganization" => array(
				"@type"  => "Organization",
				"name"   => get_option( 'loxo_hiring_company_name', get_bloginfo( 'sitename') ),
				"sameAs" => get_option( 'loxo_hiring_company_url', home_url( '/') ),
				"logo"   => get_option( 'loxo_hiring_company_logo' ),
			),
			"jobLocation" => array(
				"@type"   => "Place",
				"address" => array(
					"@type"           => "PostalAddress",
					"streetAddress"   => "",
					"addressLocality" => "",
					"addressRegion"   => "",
					"postalCode"      => "",
					"addressCountry"  => "US"
				)
			)
		);

		// streetAddress.
		if ( $this->job->get_address() ) {
			$schema['jobLocation']['address']['streetAddress'] = $this->job->get_address();
		}

		// addressLocality.
		// Missing.

		// addressRegion.
		if ( $this->job->get_state_id() ) {
			$state = new \Loxo\Job_State\Data( $this->job->get_state_id() );
			$schema['jobLocation']['address']['addressRegion'] = $state->get_name();
		}

		// addressLocality.
		if ( $this->job->get_city() ) {
			$schema['jobLocation']['address']['addressLocality'] = $this->job->get_city();
		}

		// addressCountry.
		if ( $this->job->get_country_code() ) {
			$schema['jobLocation']['address']['addressCountry'] = $this->job->get_country_code();
		}

		// postalCode.
		if ( $this->job->get_zip() ) {
			$schema['jobLocation']['address']['postalCode'] = $this->job->get_zip();
		}

		// Add salary to schema.
		if ( ! empty( $this->job->get_salary() ) ) {
			$salary = preg_replace( '/[^0-9\.]/i', '', trim( $this->job->get_salary() ) );

			if ( ! empty( $salary ) ) {
				$unit_text = loxo_get_salary_unit( $this->job->get_salary() );

				$schema['baseSalary'] = array(
					"@type"    => "MonetaryAmount",
					"currency" => "USD",
					"value" => array(
						"@type"    => "QuantitativeValue",
						"value"    => number_format( $salary, 2, '.', '' ),
						"unitText" => $unit_text
					)
				);
			}
		}

		# Utils::d($schema);

		return $schema;
	}
}
