<?php
/**
 * Search-engine head tags — the ones the theme did not have.
 *
 * The theme already hand-wrote description/canonical/Open Graph for section
 * pages (zandi_section_head), course pages (zandi_course_head) and the
 * placement test (zandi_placement_head), all in functions.php. Three things
 * were missing and are added here:
 *
 *   1. The homepage — the most-linked URL on the site — had no meta
 *      description, no canonical and no Open Graph tags at all.
 *   2. /login/, /register/ and /panel/ were fully indexable. A sign-in form and
 *      a private dashboard have no business in a search index.
 *   3. Nothing anywhere emitted og:image or JSON-LD, and the virtual routes
 *      (/courses/{slug}, /{section}/) appear in no sitemap, because core's
 *      sitemap lists posts and pages and these are neither.
 *
 * ONE RULE ABOVE THE OTHERS: every function here, and the three older ones in
 * functions.php, returns early when zandi_seo_plugin_active() is true. The
 * theme's canonical and og: output is unconditional, so installing Yoast or
 * Rank Math later would put a SECOND canonical on every course and section
 * page — which is worse than having none, because the two can disagree and
 * Google picks. The guard means the plugin simply wins the moment it arrives.
 *
 * FACTS ONLY applies here more than anywhere. A JSON-LD block is exactly the
 * place an invented rating, enrolment count or price gets shipped, because
 * nobody reads it. Nothing below is asserted that the site does not already say
 * in Persian on a page a human can read.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * The guard
 * ====================================================================== */

/**
 * Whether an SEO plugin is active and should own the head tags instead.
 *
 * @return bool
 */
function zandi_seo_plugin_active() {
	$active = defined( 'WPSEO_VERSION' )      // Yoast SEO.
		|| class_exists( 'RankMath' )         // Rank Math.
		|| defined( 'SEOPRESS_VERSION' )      // SEOPress.
		|| defined( 'AIOSEO_VERSION' );       // All in One SEO.

	/**
	 * Filters whether the theme stands down from emitting SEO tags.
	 *
	 * @param bool $active True when a plugin is handling them.
	 */
	return (bool) apply_filters( 'zandi_seo_plugin_active', $active );
}

/* =========================================================================
 * Shared pieces
 * ====================================================================== */

/**
 * The default social share image.
 *
 * Returns an empty string until a real 1200×630 card exists, which is the same
 * way zandi_course_cover() handles a missing file: an empty state rather than a
 * broken image. Callers print nothing when this is empty.
 *
 * @return string Absolute URL, or ''.
 */
function zandi_og_image() {
	$relative = 'assets/images/og-default.jpg';
	$url      = file_exists( get_theme_file_path( $relative ) ) ? zandi_asset_uri( $relative ) : '';

	/**
	 * Filters the default og:image URL.
	 *
	 * @param string $url Absolute URL, or '' to emit no tag.
	 */
	return (string) apply_filters( 'zandi_og_image', $url );
}

/**
 * Prints og:image and its dimensions, when there is one to print.
 *
 * @param string $url Absolute image URL. Empty prints nothing.
 * @return void
 */
function zandi_print_og_image( $url = '' ) {
	$url = $url ? $url : zandi_og_image();

	if ( ! $url ) {
		return;
	}

	printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $url ) );
	printf( "<meta name=\"twitter:image\" content=\"%s\">\n", esc_url( $url ) );
}

/**
 * Prints one JSON-LD block.
 *
 * JSON_HEX_TAG matters: it turns `<` into `<`, so a stray «</script>» in
 * any copy string cannot close the element early. The other two flags only make
 * the output readable — Persian stays Persian rather than becoming \uXXXX.
 *
 * @param array<string,mixed> $data Schema.org graph.
 * @return void
 */
function zandi_print_schema( $data ) {
	if ( ! $data ) {
		return;
	}

	$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );

	if ( ! $json ) {
		return;
	}

	echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode with JSON_HEX_TAG.
}

/**
 * The academy as a schema.org node, reused by every graph on the site.
 *
 * @return array<string,mixed>
 */
function zandi_schema_organization() {
	$site    = zandi_site();
	$socials = array();

	foreach ( zandi_socials() as $social ) {
		if ( ! empty( $social['url'] ) ) {
			$socials[] = $social['url'];
		}
	}

	$node = array(
		'@type'       => 'EducationalOrganization',
		'@id'         => home_url( '/#organization' ),
		'name'        => $site['name'],
		'url'         => home_url( '/' ),
		'description' => $site['description'],
		'inLanguage'  => 'fa-IR',
		/*
		 * Shima founded the academy and teaches every course — that is stated
		 * in Persian on /about/, so it is a fact the markup may repeat. No
		 * address, no telephone and no aggregateRating: zandi_contact() has no
		 * address or phone number yet, and there is no review data anywhere on
		 * this site. An empty field is better than a plausible-looking one.
		 */
		'founder'     => array(
			'@type' => 'Person',
			'name'  => 'شیما زندی',
		),
	);

	if ( $socials ) {
		$node['sameAs'] = $socials;
	}

	$logo = zandi_og_image();

	if ( $logo ) {
		$node['logo'] = $logo;
	}

	return $node;
}

/* =========================================================================
 * The homepage
 * ====================================================================== */

/**
 * Description, canonical, Open Graph and Twitter tags for the front page.
 *
 * The canonical is conditional on purpose. When the front page is a static
 * page it is is_singular(), so core's own rel_canonical() has already printed
 * one — printing a second here is the exact duplicate-canonical bug this file
 * guards against elsewhere.
 *
 * @return void
 */
function zandi_home_head() {
	if ( ! is_front_page() || zandi_seo_plugin_active() ) {
		return;
	}

	$site = zandi_site();
	$meta = zandi_home_meta();
	$url  = home_url( '/' );

	printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $meta['description'] ) );

	if ( ! is_singular() ) {
		printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $url ) );
	}

	printf( "<meta property=\"og:type\" content=\"website\">\n" );
	printf( "<meta property=\"og:locale\" content=\"fa_IR\">\n" );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( $site['name'] ) );
	printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $meta['title'] ) );
	printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $meta['description'] ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $url ) );

	printf( "<meta name=\"twitter:card\" content=\"summary_large_image\">\n" );
	printf( "<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr( $meta['title'] ) );
	printf( "<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr( $meta['description'] ) );

	zandi_print_og_image();
}
add_action( 'wp_head', 'zandi_home_head', 3 );

/**
 * Sets the browser title on the front page.
 *
 * Without this the homepage title is whatever تنظیمات ← همگانی happens to hold,
 * which is a database field nobody remembers and which no deploy can set. The
 * keyword belongs somewhere deterministic, and the hero <h1> is deliberately
 * left alone — it is the brand's voice, not a search string.
 *
 * @param array<string,string> $parts Title parts.
 * @return array<string,string>
 */
function zandi_home_title( $parts ) {
	if ( ! is_front_page() || zandi_seo_plugin_active() ) {
		return $parts;
	}

	$meta = zandi_home_meta();

	$parts['title'] = $meta['title'];

	// Core appends the tagline on the front page; the title above already
	// carries it, so leaving both in place says the same thing twice.
	unset( $parts['tagline'] );

	return $parts;
}
add_filter( 'document_title_parts', 'zandi_home_title' );

/* =========================================================================
 * Account routes — out of the index, and no longer sharing one title
 * ====================================================================== */

/**
 * Keeps /login/, /register/, /logout/ and /panel/ out of search results.
 *
 * `follow` rather than `nofollow`: the links out of these pages are the site's
 * own navigation and there is no reason to stop a crawler using them.
 *
 * @return void
 */
function zandi_account_head() {
	if ( ! zandi_account_route() || zandi_seo_plugin_active() ) {
		return;
	}

	echo '<meta name="robots" content="noindex, follow">' . "\n";
}
add_action( 'wp_head', 'zandi_account_head', 3 );

/* =========================================================================
 * Structured data
 * ====================================================================== */

/**
 * One JSON-LD graph per page type.
 *
 * Kept to what the page actually shows: the academy on the homepage, the course
 * on a course page, the questions on /faq/, and the breadcrumb trail that is
 * already drawn in the markup by zandi_breadcrumb().
 *
 * Deliberately absent: `offers`. Prices on this site are quoted in تومان, which
 * is not an ISO 4217 currency — the gateway settles in ریال and the euro price
 * is the out-of-Iran figure. Publishing a number under the wrong currency code
 * is a factor-of-ten error in public, so no price is published at all until
 * someone decides which unit the markup should carry.
 *
 * @return void
 */
function zandi_schema_head() {
	if ( zandi_seo_plugin_active() ) {
		return;
	}

	$graph = array();

	if ( is_front_page() ) {
		$site = zandi_site();

		$graph[] = zandi_schema_organization();
		$graph[] = array(
			'@type'      => 'WebSite',
			'@id'        => home_url( '/#website' ),
			'name'       => $site['name'],
			'url'        => home_url( '/' ),
			'inLanguage' => 'fa-IR',
			'publisher'  => array( '@id' => home_url( '/#organization' ) ),
		);
	}

	$course = zandi_current_course();

	if ( $course ) {
		$graph[] = zandi_schema_course( $course );
		$graph[] = zandi_schema_breadcrumb(
			array(
				array( 'label' => 'خانه', 'url' => home_url( '/' ) ),
				array( 'label' => 'دوره‌ها', 'url' => zandi_section_url( 'courses' ) ),
				array( 'label' => $course['short_name'], 'url' => zandi_course_url( $course['slug'] ) ),
			)
		);
	}

	$section = zandi_current_section();

	if ( $section ) {
		if ( 'faq' === $section['slug'] ) {
			$faq = zandi_schema_faq( zandi_faqs() );

			if ( $faq ) {
				$graph[] = $faq;
			}
		}

		$graph[] = zandi_schema_breadcrumb(
			array(
				array( 'label' => 'خانه', 'url' => home_url( '/' ) ),
				array( 'label' => $section['title'], 'url' => zandi_section_url( $section['slug'] ) ),
			)
		);
	}

	if ( ! $graph ) {
		return;
	}

	zandi_print_schema(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		)
	);
}
add_action( 'wp_head', 'zandi_schema_head', 4 );

/**
 * A course as a schema.org Course node.
 *
 * @param array<string,mixed> $course A course from zandi_courses_data().
 * @return array<string,mixed>
 */
function zandi_schema_course( $course ) {
	$node = array(
		'@type'       => 'Course',
		'@id'         => zandi_course_url( $course['slug'] ) . '#course',
		'name'        => $course['short_name'],
		'description' => $course['meta_description'],
		'url'         => zandi_course_url( $course['slug'] ),
		'provider'    => array( '@id' => home_url( '/#organization' ) ),
		/*
		 * The course is taught in Persian and teaches French. `inLanguage` is
		 * the language of the material; `teaches` is the subject. Conflating
		 * the two is the usual mistake here and it tells Google the videos are
		 * in French, which they are not.
		 */
		'inLanguage'  => 'fa-IR',
		'teaches'     => 'زبان فرانسه',
		'about'       => array(
			'@type' => 'Thing',
			'name'  => 'زبان فرانسه',
		),
	);

	if ( ! empty( $course['level'] ) ) {
		$node['educationalLevel'] = $course['level'];
	}

	/*
	 * courseMode «online» and a self-paced schedule are both facts stated on
	 * the page: the videos are recorded and access is lifetime.
	 */
	$node['hasCourseInstance'] = array(
		'@type'      => 'CourseInstance',
		'courseMode' => 'online',
		'inLanguage' => 'fa-IR',
	);

	return $node;
}

/**
 * A question list as a schema.org FAQPage node.
 *
 * @param array<int,array{question:string,answer:string}> $faqs Questions.
 * @return array<string,mixed>|null
 */
function zandi_schema_faq( $faqs ) {
	$entities = array();

	foreach ( (array) $faqs as $faq ) {
		if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
			continue;
		}

		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $faq['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['answer'],
			),
		);
	}

	if ( ! $entities ) {
		return null;
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => zandi_section_url( 'faq' ) . '#faq',
		'mainEntity' => $entities,
	);
}

/**
 * A breadcrumb trail as a schema.org BreadcrumbList node.
 *
 * Takes the same shape zandi_breadcrumb() is given, so the markup and the
 * structured data cannot describe two different trails.
 *
 * @param array<int,array{label:string,url?:string}> $trail Crumbs, in order.
 * @return array<string,mixed>
 */
function zandi_schema_breadcrumb( $trail ) {
	$items = array();

	foreach ( array_values( (array) $trail ) as $index => $crumb ) {
		if ( empty( $crumb['label'] ) ) {
			continue;
		}

		$item = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => $crumb['label'],
		);

		if ( ! empty( $crumb['url'] ) ) {
			$item['item'] = $crumb['url'];
		}

		$items[] = $item;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/* =========================================================================
 * Sitemap
 * ====================================================================== */

/**
 * Adds the theme's virtual routes to core's XML sitemap.
 *
 * /courses/a1, /faq/, /method/ and the rest are built in parse_request rather
 * than being posts, so core's sitemap — which walks post types and taxonomies —
 * lists none of them. Without this the only way Google finds a course page is
 * by following an internal link.
 *
 * The provider class extends a core class, so it lives in its own file and is
 * required only after the class is confirmed present — a theme that fataled
 * because sitemaps were disabled would take the whole site down. Account and
 * placement routes stay out: one is noindex by design, the others are private.
 *
 * Priority 20 on `init`, because core builds the sitemap server on `init` at 10
 * and there is nothing to register with before that.
 *
 * @return void
 */
function zandi_add_sitemap_provider() {
	if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) {
		return;
	}

	require_once get_theme_file_path( 'inc/class-zandi-sitemap-provider.php' );

	wp_register_sitemap_provider( 'zandi', new Zandi_Sitemap_Provider() );
}
add_action( 'init', 'zandi_add_sitemap_provider', 20 );

/* =========================================================================
 * The setting no deploy can reach
 * ====================================================================== */

/**
 * Shouts in wp-admin when the whole site is set to noindex.
 *
 * تنظیمات ← خواندن has a checkbox — «از موتورهای جستجو بخواهید این سایت را
 * ایندکس نکنند» — that makes WordPress emit `noindex, nofollow` on every page.
 * It is on by default on a great many installs because it is switched on while
 * a site is being built and then forgotten, and it lives in the database, so
 * nothing in this repository can see it, fix it, or notice it has happened.
 *
 * WordPress does warn about it, but only on the dashboard's Site Health card,
 * which is easy to scroll past. This notice appears on every admin screen and
 * links straight to the setting.
 *
 * @return void
 */
function zandi_noindex_admin_notice() {
	if ( '0' !== (string) get_option( 'blog_public' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
		esc_html( 'سایت برای موتورهای جستجو بسته است.' ),
		esc_html( 'گوگل هیچ صفحه‌ای از سایت را ایندکس نمی‌کند. برای رفع آن تیک «از موتورهای جستجو بخواهید این سایت را ایندکس نکنند» را بردارید:' ),
		esc_url( admin_url( 'options-reading.php' ) ),
		esc_html( 'تنظیمات ← خواندن' )
	);
}
add_action( 'admin_notices', 'zandi_noindex_admin_notice' );
