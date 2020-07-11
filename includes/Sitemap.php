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
class Sitemap {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rules' ), 10 );
		add_action( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'sitemap_handler' ) );
		add_filter( 'robots_txt', array( $this, 'add_robots' ), 0, 2 );
		add_filter( 'redirect_canonical', array( $this, 'redirect_canonical' ) );
	}

	/**
	 * add extra rules for sitemap and stylesheet url.
	 */
	public function add_rules() {
		add_rewrite_rule(
			'^' . loxo_get_sitemap_name() . '\.xml$',
			'index.php?loxo_sitemap=index',
			'top'
		);

		add_rewrite_rule(
			'^' . loxo_get_sitemap_name() . '\.xls$',
			'index.php?loxo_sitemap=stylesheet',
			'top'
		);
	}

	/**
	 * Add public query var
	 */
	public function add_query_vars( $query_vars ) {
		$query_vars[] = 'loxo_sitemap';
	    return $query_vars;
	}


	/**
	 * Prevent trailing slashes.
	 *
	 * @since 5.5.0
	 *
	 * @param string $redirect The redirect URL currently determined.
	 * @return bool|string $redirect The canonical redirect URL.
	 */
	public function redirect_canonical( $redirect ) {
		if ( get_query_var( 'loxo_sitemap' ) ) {
			return false;
		}

		return $redirect;
	}

	/**
	 * Adds the sitemap index to robots.txt.
	 *
	 * @param string $output robots.txt output.
	 * @param bool   $public Whether the site is public or not.
	 * @return string The robots.txt output.
	 */
	public function add_robots( $output, $public ) {
		if ( $public ) {
			$output .= "\nSitemap: " . esc_url( loxo_get_sitemap_url() ) . "\n";
		}

		return $output;
	}

	/**
	 * Render and display sitemap if condition met.
	 */
	public function sitemap_handler() {
		global $wp_query;

		$sitemap = sanitize_text_field( get_query_var( 'loxo_sitemap' ) );
		if ( ! $sitemap ) {
			return;
		}

		// Render stylesheet if requested.
		if ( 'stylesheet' === $sitemap ) {
			$this->render_sitemap_stylesheet();
			exit;
		}

		$all_jobs = loxo_get_all_jobs();

		// If there's an error with all jobs, bail.
		if ( is_wp_error( $all_jobs ) ) {
			$wp_query->set_404();
			return;
		}

		$url_list = array();
		foreach ( $all_jobs as $job ) {
			$url_list[] = array(
				'loc' => loxo_get_job_url( $job['id'], $job['title'] )
			);
		}

		$this->render_sitemap( $url_list );
		exit;
	}

	/**
	 * Render stylesheet for sitemap.
	 */
	public function render_sitemap_stylesheet() {
		header( 'Content-type: application/xml; charset=UTF-8' );
		echo $this->get_sitemap_stylesheet();
		exit;
	}

	/**
	 * Render jobs sitemap.
	 *
	 * @param array $url_list Array of URLs for a sitemap.
	 */
	public function render_sitemap( $url_list ) {
		header( 'Content-type: application/xml; charset=UTF-8' );

		// Bail if missing dependencies
		$this->check_for_simple_xml_availability();

		$sitemap_xml = $this->get_sitemap_xml( $url_list );

		if ( ! empty( $sitemap_xml ) ) {
			echo $sitemap_xml;
		}
	}

	/**
	 * Gets XML for a sitemap.
	 *
	 * @param array $url_list Array of URLs for a sitemap.
	 * @return string|false A well-formed XML string for a sitemap index. False on error.
	 */
	public function get_sitemap_xml( $url_list ) {
		$stylesheet = '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/loxo-jobs.xls' ) ) . '" ?>';
		$urlset = new \SimpleXMLElement(
			sprintf(
				'%1$s%2$s%3$s',
				'<?xml version="1.0" encoding="UTF-8" ?>',
				$stylesheet,
				'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" />'
			)
		);

		foreach ( $url_list as $url_item ) {
			$url = $urlset->addChild( 'url' );

			// Add each element as a child node to the <url> entry.
			foreach ( $url_item as $name => $value ) {
				if ( 'loc' === $name ) {
					$url->addChild( $name, esc_url( $value ) );
				} elseif ( in_array( $name, array( 'lastmod', 'changefreq', 'priority' ), true ) ) {
					$url->addChild( $name, $this->esc_xml( $value ) );
				}
			}
		}

		return $urlset->asXML();
	}

	/**
	 * Checks for the availability of the SimpleXML extension and errors if missing.
	 *
	 * @since 5.5.0
	 */
	private function check_for_simple_xml_availability() {
		if ( ! class_exists( '\SimpleXMLElement' ) ) {
			add_filter(
				'wp_die_handler',
				static function () {
					return '_xml_wp_die_handler';
				}
			);

			wp_die(
				sprintf(
					/* translators: %s: SimpleXML */
					$this->esc_xml( __( 'Could not generate XML sitemap due to missing %s extension', 'loxo' ) ),
					'SimpleXML'
				),
				$this->esc_xml( __( 'WordPress &rsaquo; Error', 'loxo' ) ),
				array(
					'response' => 501, // "Not implemented".
				)
			);
		}
	}

	/**
	 * Returns the escaped xsl for all sitemaps, except index.
	 */
	public function get_sitemap_stylesheet() {
		$css           = $this->get_stylesheet_css();
		$title         = $this->esc_xml( __( 'XML Sitemap', 'loxo' ) );
		$description = $this->esc_xml( __( 'This XML Sitemap is generated by Loxo Plugin to make jobs visible for search engines.', 'loxo' ) );
		$learn_more  = sprintf(
			'<a href="%s">%s</a>',
			esc_url( __( 'https://www.sitemaps.org/', 'loxo' ) ),
			$this->esc_xml( __( 'Learn more about XML sitemaps.', 'loxo' ) )
		);

		$text = sprintf(
			/* translators: %s: number of URLs. */
			$this->esc_xml( __( 'Number of URLs in this XML Sitemap: %s.', 'loxo' ) ),
			'<xsl:value-of select="count( sitemap:urlset/sitemap:url )" />'
		);

		$lang       = get_language_attributes( 'html' );
		$url        = $this->esc_xml( __( 'URL', 'loxo' ) );
		$lastmod    = $this->esc_xml( __( 'Last Modified', 'loxo' ) );
		$changefreq = $this->esc_xml( __( 'Change Frequency', 'loxo' ) );
		$priority   = $this->esc_xml( __( 'Priority', 'loxo' ) );

		$xsl_content = <<<XSL
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
		version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
		exclude-result-prefixes="sitemap"
		>

	<xsl:output method="html" encoding="UTF-8" indent="yes"/>

	<!--
	  Set variables for whether lastmod, changefreq or priority occur for any url in the sitemap.
	  We do this up front because it can be expensive in a large sitemap.
	  -->
	<xsl:variable name="has-lastmod"    select="count( /sitemap:urlset/sitemap:url/sitemap:lastmod )"    />
	<xsl:variable name="has-changefreq" select="count( /sitemap:urlset/sitemap:url/sitemap:changefreq )" />
	<xsl:variable name="has-priority"   select="count( /sitemap:urlset/sitemap:url/sitemap:priority )"   />

	<xsl:template match="/">
		<html {$lang}>
			<head>
				<title>{$title}</title>
				<style>{$css}</style>
			</head>
			<body>
				<div id="sitemap__header">
					<h1>{$title}</h1>
					<p>{$description}</p>
					<p>{$learn_more}</p>
				</div>
				<div id="sitemap__content">
					<p class="text">{$text}</p>
					<table id="sitemap__table">
						<thead>
							<tr>
								<th class="loc">{$url}</th>
								<xsl:if test="\$has-lastmod">
									<th class="lastmod">{$lastmod}</th>
								</xsl:if>
								<xsl:if test="\$has-changefreq">
									<th class="changefreq">{$changefreq}</th>
								</xsl:if>
								<xsl:if test="\$has-priority">
									<th class="priority">{$priority}</th>
								</xsl:if>
							</tr>
						</thead>
						<tbody>
							<xsl:for-each select="sitemap:urlset/sitemap:url">
								<tr>
									<td class="loc"><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc" /></a></td>
									<xsl:if test="\$has-lastmod">
										<td class="lastmod"><xsl:value-of select="sitemap:lastmod" /></td>
									</xsl:if>
									<xsl:if test="\$has-changefreq">
										<td class="changefreq"><xsl:value-of select="sitemap:changefreq" /></td>
									</xsl:if>
									<xsl:if test="\$has-priority">
										<td class="priority"><xsl:value-of select="sitemap:priority" /></td>
									</xsl:if>
								</tr>
							</xsl:for-each>
						</tbody>
					</table>
				</div>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>

XSL;
		return $xsl_content;
	}

	/**
	 * Gets the CSS to be included in sitemap XSL stylesheets.
	 *
	 * @return string The CSS.
	 */
	public function get_stylesheet_css() {
		return '
			body {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				color: #444;
			}

			#sitemap__table {
				border: solid 1px #ccc;
				border-collapse: collapse;
			}

			#sitemap__table tr th {
				text-align: left;
			}

			#sitemap__table tr td,
			#sitemap__table tr th {
				padding: 10px;
			}

			#sitemap__table tr:nth-child(odd) td {
				background-color: #eee;
			}

			a:hover {
				text-decoration: none;
			}';
	}

	/**
	 * Escaping for XML blocks.
	 *
	 * @since 5.5.0
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_xml( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$safe_text = wp_check_invalid_utf8( $text );

		$cdata_regex = '\<\!\[CDATA\[.*?\]\]\>';
		$regex       = <<<EOF
/
	(?=.*?{$cdata_regex})                 # lookahead that will match anything followed by a CDATA Section
	(?<non_cdata_followed_by_cdata>(.*?)) # the "anything" matched by the lookahead
	(?<cdata>({$cdata_regex}))            # the CDATA Section matched by the lookahead

|	                                      # alternative

	(?<non_cdata>(.*))                    # non-CDATA Section
/sx
EOF;
		$safe_text = (string) preg_replace_callback(
			$regex,
			function( $matches ) {
				if ( ! $matches[0] ) {
					return '';
				} elseif ( ! empty( $matches['non_cdata'] ) ) {
					// escape HTML entities in the non-CDATA Section.
					return $this->_esc_xml_non_cdata_section( $matches['non_cdata'] );
				}

				// Return the CDATA Section unchanged, escape HTML entities in the rest.
				return $this->_esc_xml_non_cdata_section( $matches['non_cdata_followed_by_cdata'] ) . $matches['cdata'];
			},
			$safe_text
		);

		/**
		 * Filters a string cleaned and escaped for output in XML.
		 *
		 * Text passed to $this->esc_xml() is stripped of invalid or special characters
		 * before output. HTML named character references are converted to their
		 * equivalent code points.
		 *
		 * @since 5.5.0
		 *
		 * @param string $safe_text The text after it has been escaped.
		 * @param string $text      The text prior to being escaped.
		 */
		return apply_filters( 'esc_xml', $safe_text, $text ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	}

	/**
	 * Escaping for non-CDATA Section XML blocks.
	 *
	 * @access private
	 * @since 5.5.0
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function _esc_xml_non_cdata_section( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		global $allowedentitynames;

		$safe_text = _wp_specialchars( $text, ENT_QUOTES );
		// Replace HTML entities with their Unicode codepoints,
		// without doing the same for the 5 XML entities.
		$html_only_entities = array_diff( $allowedentitynames, array( 'amp', 'lt', 'gt', 'apos', 'quot' ) );
		$safe_text          = (string) preg_replace_callback(
			'/&(' . implode( '|', $html_only_entities ) . ');/',
			function( $matches ) {
				return html_entity_decode( $matches[0], ENT_HTML5 );
			},
			$safe_text
		);

		return $safe_text;
	}
}
