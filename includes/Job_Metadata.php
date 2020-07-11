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
		$title = sprintf( '%s | %s', $this->job['title'], get_bloginfo( 'sitename' ) );
		$url = loxo_get_job_url( $this->job['id'], $this->job['title'] );
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
<?php if ( loxo_get_job_updated_at( $this->job['id'] ) ) : ?>
	<meta property="article:modified_time" content="<?php echo gmdate( DATE_W3C, strtotime( loxo_get_job_updated_at( $this->job['id'] ) ) ); ?>" />
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
		$description = sprintf( 'Hiring %s - %s.', ucwords( strtolower( $this->job['title'] ) ), $this->job['job_type']['name'] );

		if ( ! empty( $this->job['city'] ) ) {
			$description .= sprintf( ' Job location %s.', $this->job['city'] );
		}

		if ( ! empty( $this->job['salary'] ) ) {
			$description .= sprintf( ' Compensation %s.', $this->job['salary'] );
		}

		return $description;
	}

	private function get_job_schema_data() {
		// Date posted.
		if ( loxo_get_job_published_at( $this->job['id'] ) ) {
			$date_posted = gmdate( 'Y-m-d', strtotime( loxo_get_job_published_at( $this->job['id'] ) ) );
		} else {
			// Use the pushing date of the listing page.
			$listing_page = get_page( loxo_get_listing_page_id() );
			$date_posted = gmdate( 'Y-m-d', strtotime( $listing_page->post_date_gmt ) );
		}

		// Valid through.
		$expiration_field = get_option( 'loxo_job_expiration_custom_field' );
		if ( ! empty( $this->job[ $expiration_field ] ) && 'null' !== $this->job[ $expiration_field ] ) {
			$valid_through = gmdate( 'Y-m-d', strtotime( $this->job[ $expiration_field ] ) );
		} else {
			$valid_through = gmdate( 'Y-m-d', strtotime( $date_posted ) + ( DAY_IN_SECONDS * get_option( 'loxo_default_job_validity_days', 180 ) ) );
		}

		// Default employmentType is full time, change if needed.
		$employment_type = 'FULL_TIME';
		if ( ! empty( $this->job['job_type'] ) ) {
			if ( 'Contract' === $this->job['job_type']['name'] ) {
				$employment_type = "CONTRACTOR";
			}
		}

		$schema = array(
			"@context"    => "https://schema.org/",
			"@type"       => "JobPosting",
			"title"       => $this->job['title'],
			"description" => $this->job['description_text'],
			"identifier"  => array(
				"@type" => "PropertyValue",
				"name"  => get_option( 'loxo_hiring_company_name', get_bloginfo( 'sitename') ),
				"value" => $this->job['id']
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
		if ( ! empty( $this->job['address'] ) && 'null' !== $this->job['address'] ) {
			$schema['jobLocation']['address']['streetAddress'] = $this->job['address'];
		}

		// addressLocality.
		// Missing.

		// addressRegion.
		if ( ! empty( $this->job['state_code'] ) && 'null' !== $this->job['state_code'] ) {
			$schema['jobLocation']['address']['addressRegion'] = $this->job['state_code'];
		}

		// addressLocality.
		if ( ! empty( $this->job['city'] ) && 'null' !== $this->job['city'] ) {
			$schema['jobLocation']['address']['addressLocality'] = $this->job['city'];
		}

		// addressCountry.
		if ( ! empty( $this->job['country_code'] ) && 'null' !== $this->job['country_code'] ) {
			$schema['jobLocation']['address']['addressCountry'] = $this->job['country_code'];
		}

		// postalCode.
		if ( ! empty( $this->job['zip'] ) && 'null' !== $this->job['zip'] ) {
			$schema['jobLocation']['address']['postalCode'] = $this->job['zip'];
		}

		// Add salary to schema.
		if ( ! empty( $this->job['salary'] ) ) {
			$salary = preg_replace( '/[^0-9\.]/i', '', trim( $this->job['salary'] ) );

			if ( ! empty( $salary ) ) {

				$unit_text = 'YEAR';
				if ( false !== strpos( strtolower( $this->job['salary'] ), 'per hour' ) ) {
					$unit_text = 'HOUR';
				} elseif ( false !== strpos( strtolower( $this->job['salary'] ), 'per day' ) ) {
					$unit_text = 'DAY';
				} elseif ( false !== strpos( strtolower( $this->job['salary'] ), 'per week' ) ) {
					$unit_text = 'WEEK';
				} elseif ( false !== strpos( strtolower( $this->job['salary'] ), 'per month' ) ) {
					$unit_text = 'MONTH';
				}

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
