<?php
/**
 * Sitemap provider for the theme's virtual routes.
 *
 * The course and section pages are not posts. They are built in
 * zandi_parse_request() from a rewrite rule, so core's sitemap — which walks
 * post types and taxonomies — has nothing to list them from, and they appeared
 * in no sitemap at all. The only route Google had to /courses/a1 was an
 * internal link from a page it had already found.
 *
 * Loaded from zandi_add_sitemap_provider() in inc/seo.php, and only after
 * WP_Sitemaps_Provider is confirmed to exist — this file cannot be required
 * unconditionally.
 *
 * URLs are built with zandi_section_url() and zandi_course_url(), never
 * concatenated by hand: those helpers fall back to a query string when
 * permalinks are «ساده», and a sitemap full of URLs the web server answers with
 * its own 404 is worse than no sitemap.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lists /courses/{slug} and /{section}/ in wp-sitemap.xml.
 */
class Zandi_Sitemap_Provider extends WP_Sitemaps_Provider {

	/**
	 * Provider name, used in the sitemap URL.
	 *
	 * @var string
	 */
	protected $name = 'zandi';

	/**
	 * Object type this provider describes.
	 *
	 * @var string
	 */
	protected $object_type = 'zandi';

	/**
	 * Every URL this provider owns.
	 *
	 * Deliberately excluded: /login/, /register/, /logout/ and /panel/, which
	 * are noindex, and /placement/, which is unannounced and noindex while it
	 * is being reviewed — see zandi_placement_noindex().
	 *
	 * @return array<int,string>
	 */
	protected function zandi_urls() {
		$urls = array();

		foreach ( array_keys( zandi_sections() ) as $slug ) {
			$urls[] = zandi_section_url( $slug );
		}

		foreach ( array_keys( zandi_courses_data() ) as $slug ) {
			$urls[] = zandi_course_url( $slug );
		}

		/**
		 * Filters the virtual URLs listed in the sitemap.
		 *
		 * @param array<int,string> $urls Absolute URLs.
		 */
		return array_values( array_unique( (array) apply_filters( 'zandi_sitemap_urls', $urls ) ) );
	}

	/**
	 * URLs for one page of the sitemap.
	 *
	 * @param int    $page_num       Page of results, 1-based.
	 * @param string $object_subtype Unused; this provider has no subtypes.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_url_list( $page_num, $object_subtype = '' ) {
		$urls     = $this->zandi_urls();
		$per_page = wp_sitemaps_get_max_urls( $this->object_type );
		$offset   = ( max( 1, (int) $page_num ) - 1 ) * $per_page;
		$list     = array();

		foreach ( array_slice( $urls, $offset, $per_page ) as $url ) {
			$list[] = array( 'loc' => $url );
		}

		/** This filter is documented in wp-includes/sitemaps/providers/class-wp-sitemaps-posts.php */
		return apply_filters( 'wp_sitemaps_zandi_url_list', $list, $object_subtype, $page_num );
	}

	/**
	 * How many pages this provider needs.
	 *
	 * @param string $object_subtype Unused; this provider has no subtypes.
	 * @return int
	 */
	public function get_max_num_pages( $object_subtype = '' ) {
		$total    = count( $this->zandi_urls() );
		$per_page = wp_sitemaps_get_max_urls( $this->object_type );

		if ( $per_page < 1 || $total < 1 ) {
			return 1;
		}

		return (int) ceil( $total / $per_page );
	}
}
