<?php
/**
 * Front-end performance — the work that has to happen in PHP.
 *
 * Everything here is about the *first* load. The theme's own payload was never
 * the problem: one 9 KB script, two stylesheets, self-hosted fonts with a
 * preload, WooCommerce's CSS and cart fragments already dequeued off every page
 * that is not the shop (see zandi_woo_trim_assets()). What is left that a theme
 * can still fix is core's dead weight, and — the expensive one — outbound HTTP
 * calls that block the page while PHP waits on a host that will never answer.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. Outbound HTTP — the ten-second problem
 * ====================================================================== */

/**
 * Hosts that must never have their request timeout shortened.
 *
 * Money and access live behind these. ZarinPal is asked to verify a payment on
 * the visitor's return from the gateway; SpotPlayer issues the licence that is
 * the thing the student actually bought; نجوا sends the OTP without which
 * nobody can log in. A verification that gives up early is worse than a slow
 * page — the customer has paid and the order never completes — so these are
 * matched by host and left entirely alone.
 *
 * Matched as substrings against the request host, so subdomains
 * (`dl.spotplayer.ir`, `api.zarinpal.com`) are covered by the bare domain.
 *
 * @return string[]
 */
function zandi_perf_http_allowlist() {
	return (array) apply_filters(
		'zandi_perf_http_allowlist',
		array(
			'zarinpal.com',
			'spotplayer.ir',
			'najva.com',
		)
	);
}

/**
 * The ceiling applied to every other outbound request, in seconds.
 *
 * @return float
 */
function zandi_perf_http_cap() {
	return (float) apply_filters( 'zandi_perf_http_cap', 3 );
}

/**
 * Caps how long a page load may sit waiting on an external host.
 *
 * WordPress defaults an outbound request to a five-second timeout, and plugins
 * routinely raise their own to fifteen or thirty. From an Iranian server a
 * worrying number of the hosts they call — licence servers, update endpoints,
 * font and avatar CDNs, analytics — are unreachable rather than slow, so the
 * request does not fail, it *hangs* for the full timeout. Two of those on the
 * same page load is the ten-second first paint this file exists to stop. The
 * visitor waits the entire time staring at nothing, because PHP has not
 * produced a single byte yet.
 *
 * Three deliberate limits on the blast radius:
 *
 *   - Front-end page views only. Cron, WP-CLI, the REST API, admin-ajax and
 *     wp-admin keep the full timeout, so plugin updates, licence activation and
 *     scheduled work behave exactly as they did.
 *   - The payment, licence and SMS hosts above are exempt by name.
 *   - WooCommerce's cart, checkout and account pages are exempt wholesale,
 *     because that is where a gateway gets called from a front-end request.
 *
 * This is a safety net, not a diagnosis. If a page is slow because a plugin
 * calls a dead host, the cap turns ten seconds into three — the plugin still
 * wants fixing. Query Monitor's HTTP API panel names it.
 *
 * @param array<string,mixed> $args Request arguments.
 * @param string              $url  Request URL.
 * @return array<string,mixed>
 */
function zandi_perf_cap_http_timeout( $args, $url = '' ) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return $args;
	}

	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return $args;
	}

	// A gateway callback arrives as an ordinary front-end request.
	if ( ! empty( $_GET['wc-api'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context test.
		return $args;
	}

	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

	if ( '' === $host ) {
		return $args;
	}

	// Requests the site makes to itself — loopback checks, the site health
	// probe — are local and never the cause of a hang.
	$home = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

	if ( $home && $host === $home ) {
		return $args;
	}

	foreach ( zandi_perf_http_allowlist() as $allowed ) {
		if ( false !== strpos( $host, strtolower( $allowed ) ) ) {
			return $args;
		}
	}

	/*
	 * Conditional tags only answer once the main query is built. The filter can
	 * fire well before that — a licence check on `init` is the classic case —
	 * so this is checked when it can be and simply skipped when it cannot.
	 */
	if ( did_action( 'wp' ) && function_exists( 'is_checkout' ) ) {
		if ( is_checkout() || is_cart() || is_account_page() || is_wc_endpoint_url() ) {
			return $args;
		}
	}

	$cap = zandi_perf_http_cap();

	if ( $cap > 0 && ( ! isset( $args['timeout'] ) || $args['timeout'] > $cap ) ) {
		$args['timeout'] = $cap;
	}

	return $args;
}
add_filter( 'http_request_args', 'zandi_perf_cap_http_timeout', 99, 2 );

/* =========================================================================
 * 2. Core's dead weight
 * ====================================================================== */

/**
 * Drops the emoji polyfill.
 *
 * Core prints an inline detection script into every `<head>` and, when it
 * decides the browser needs help, fetches `wp-emoji-release.min.js` on top. No
 * browser this site's visitors use has needed it for years, and it is pure
 * added latency on a connection that is already the bottleneck. Removing it
 * changes nothing visible: emoji are ordinary characters in the font.
 *
 * @return void
 */
function zandi_perf_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );

	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	// Stops the browser opening a connection to s.w.org purely to fetch images
	// that are never requested — a host that is not reliably reachable here.
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'zandi_perf_disable_emoji' );

/**
 * Removes `<head>` links nothing on this site consumes.
 *
 * Bytes are not the point — these are three or four lines. The generator tag
 * publishing the exact WordPress version is the reason to drop it, and the rest
 * go with it. Feed links stay: `automatic-feed-links` is switched on in
 * zandi_setup() on purpose.
 *
 * @return void
 */
function zandi_perf_trim_head() {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
}
add_action( 'init', 'zandi_perf_trim_head' );

/**
 * Slows the front-end heartbeat.
 *
 * The Heartbeat API posts to admin-ajax.php on a timer, and admin-ajax.php is a
 * full uncached WordPress bootstrap every time. On shared hosting with a small
 * PHP worker pool that is real contention with visitors trying to load a page.
 * The front end has nothing to poll for, so it is pushed to the longest
 * interval core accepts; wp-admin is left alone so autosave and post locking
 * keep their normal responsiveness.
 *
 * @param array<string,mixed> $settings Heartbeat settings.
 * @return array<string,mixed>
 */
function zandi_perf_heartbeat( $settings ) {
	if ( ! is_admin() ) {
		$settings['interval'] = 60;
	}

	return $settings;
}
add_filter( 'heartbeat_settings', 'zandi_perf_heartbeat' );
