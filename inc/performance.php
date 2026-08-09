<?php
/**
 * Front-end weight removal.
 *
 * WordPress ships a set of features on every page whether or not a site uses
 * them. None of this is wrong by default — it exists for sites that need it —
 * but this theme uses none of it, and on Iranian hosting where a round trip is
 * expensive, bytes that do nothing are worth removing.
 *
 * Everything here is front-end only. wp-admin and the block editor keep the
 * whole of WordPress, so nothing an editor relies on is affected. Each removal
 * sits behind its own filter, so any one of them can be switched back on from a
 * child theme or a one-line plugin without editing this file:
 *
 *     add_filter( 'zandi_disable_emoji', '__return_false' );
 *
 * What is deliberately NOT removed:
 *
 *   - feed_links. Two <link> tags, and the academy may publish a blog.
 *   - The REST API Link: header. Only the <head> tag goes; the header costs
 *     nothing and some tools genuinely use it.
 *   - Anything in the admin.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Removes the emoji detection script and its resource hint.
 *
 * The script exists to replace emoji with Twemoji images on browsers that
 * cannot render them. Every browser this site's audience uses draws 🇫🇷 and 📦
 * natively, so it is ~12 KB of JavaScript plus a blocking DNS lookup to s.w.org
 * — a host that is not reliably reachable from Iran — in exchange for nothing.
 *
 * @return void
 */
function zandi_disable_emoji() {
	if ( ! apply_filters( 'zandi_disable_emoji', true ) ) {
		return;
	}

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	// Stops TinyMCE loading the wpemoji plugin in the classic editor.
	add_filter(
		'tiny_mce_plugins',
		function ( $plugins ) {
			return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
		}
	);

	/*
	 * The s.w.org dns-prefetch is emitted as a resource hint keyed on the emoji
	 * SVG URL, so it has to be filtered out separately — removing the script
	 * alone leaves the hint behind.
	 */
	add_filter(
		'wp_resource_hints',
		function ( $urls, $relation ) {
			if ( 'dns-prefetch' !== $relation ) {
				return $urls;
			}

			$emoji = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/' );

			return array_diff( $urls, array( $emoji ) );
		},
		10,
		2
	);
}
add_action( 'init', 'zandi_disable_emoji' );

/**
 * Drops the block editor's stylesheets from pages that contain no blocks.
 *
 * wp-block-library is around 90 KB and loads on every front-end page by
 * default. This theme builds its pages from PHP templates, so on the homepage,
 * the course pages and the section pages it styles nothing at all.
 *
 * The guard is has_blocks() rather than a flat removal: if a post or page is
 * ever written in the block editor, that entry keeps its styling automatically.
 * There is no setting to remember and nothing to switch back on.
 *
 * @return void
 */
function zandi_dequeue_block_styles() {
	if ( is_admin() || ! apply_filters( 'zandi_disable_block_styles', true ) ) {
		return;
	}

	// A page built from blocks needs the block stylesheet. Leave it alone.
	if ( is_singular() && has_blocks() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-block-style' );

	/*
	 * classic-theme-styles is a shim giving block buttons their appearance in
	 * non-block themes. Nothing here renders a block button.
	 */
	wp_dequeue_style( 'classic-theme-styles' );

	// theme.json custom properties. This theme has no theme.json.
	wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'zandi_dequeue_block_styles', 100 );

/**
 * Removes oEmbed discovery.
 *
 * Discovery links let other sites embed this one. Nothing here is meant to be
 * embedded elsewhere, and wp-embed.js is only needed to host embeds of other
 * WordPress sites — which this theme never does.
 *
 * @return void
 */
function zandi_disable_oembed() {
	if ( ! apply_filters( 'zandi_disable_oembed', true ) ) {
		return;
	}

	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}
add_action( 'init', 'zandi_disable_oembed' );

/**
 * Clears the leftovers WordPress prints into <head>.
 *
 * Really Simple Discovery and the Windows Live Writer manifest are for desktop
 * blogging clients that have not existed for a decade. The generator tag
 * publishes the exact WordPress version to anyone scanning for known
 * vulnerabilities, which is a small but free thing to stop doing.
 *
 * @return void
 */
function zandi_clean_head() {
	if ( ! apply_filters( 'zandi_clean_head', true ) ) {
		return;
	}

	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

	/*
	 * The <head> tag only. The REST API Link: HTTP header stays — it is free
	 * and some editors and tools look for it.
	 */
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
}
add_action( 'init', 'zandi_clean_head' );

/**
 * Slows the Heartbeat API down on the front end.
 *
 * Heartbeat polls admin-ajax.php on a timer. In the admin that is what keeps
 * post locking and autosave working, so it is left alone there. On the front
 * end it has no job on this site, and each poll is a full PHP request on a
 * server whose response time is already the problem.
 *
 * @param array $settings Heartbeat settings.
 * @return array
 */
function zandi_throttle_heartbeat( $settings ) {
	if ( is_admin() || ! apply_filters( 'zandi_throttle_heartbeat', true ) ) {
		return $settings;
	}

	$settings['interval'] = 60;

	return $settings;
}
add_filter( 'heartbeat_settings', 'zandi_throttle_heartbeat' );

/* =========================================================================
 * Outbound HTTP — the ten-second problem
 *
 * Everything above removes bytes. This removes waiting, which on this site is
 * the larger number by far: bytes only start mattering once the server has
 * produced a first byte at all.
 * ====================================================================== */

/**
 * Hosts whose request timeout must never be shortened.
 *
 * Money and access live behind these. ZarinPal is asked to verify a payment on
 * the visitor's return from the gateway; SpotPlayer issues the licence that is
 * the thing the student actually bought; نجوا sends the OTP without which
 * nobody can log in. A verification that gives up early is far worse than a
 * slow page — the customer has paid and the order never completes — so these
 * are matched by host and left entirely alone.
 *
 * Matched as substrings against the request host, so subdomains
 * (`dl.spotplayer.ir`, `api.zarinpal.com`) are covered by the bare domain.
 *
 * @return string[]
 */
function zandi_http_allowlist() {
	return (array) apply_filters(
		'zandi_http_allowlist',
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
function zandi_http_timeout_cap() {
	return (float) apply_filters( 'zandi_http_timeout_cap', 3 );
}

/**
 * Caps how long a front-end page load may sit waiting on an external host.
 *
 * WordPress defaults an outbound request to a five-second timeout, and plugins
 * routinely raise their own to fifteen or thirty. From an Iranian server a
 * worrying number of the hosts they call — licence servers, update endpoints,
 * font and avatar CDNs, analytics — are unreachable rather than slow, so the
 * request does not fail, it *hangs* for the full timeout. Two of those on one
 * page load is a ten-second first byte with nothing painted, because PHP has
 * not sent anything yet.
 *
 * `http_request_args` rather than `http_request_timeout`: the latter only sets
 * the default, which a plugin passing its own `timeout` overrides — and those
 * are precisely the callers worth capping.
 *
 * Three deliberate limits on the blast radius:
 *
 *   - Front-end page views only. Cron, WP-CLI, REST, admin-ajax and wp-admin
 *     keep the full timeout, so plugin updates, licence activation and
 *     scheduled work behave exactly as they did.
 *   - The payment, licence and SMS hosts above are exempt by name.
 *   - Cart, checkout and account pages are exempt wholesale, because that is
 *     where a gateway gets called from a front-end request.
 *
 * This is a safety net, not a diagnosis. If a page is slow because a plugin
 * calls a dead host, the cap turns ten seconds into three — the plugin still
 * wants fixing, and Query Monitor's HTTP API panel names it.
 *
 * @param array<string,mixed> $args Request arguments.
 * @param string              $url  Request URL.
 * @return array<string,mixed>
 */
function zandi_cap_http_timeout( $args, $url = '' ) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return $args;
	}

	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return $args;
	}

	if ( ! apply_filters( 'zandi_cap_http_timeout', true ) ) {
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

	foreach ( zandi_http_allowlist() as $allowed ) {
		if ( false !== strpos( $host, strtolower( $allowed ) ) ) {
			return $args;
		}
	}

	/*
	 * Conditional tags only answer once the main query is built. The filter can
	 * fire well before that — a licence check on `init` is the classic case —
	 * so this is checked when it can be and skipped when it cannot.
	 */
	if ( did_action( 'wp' ) && function_exists( 'is_checkout' ) ) {
		if ( is_checkout() || is_cart() || is_account_page() || is_wc_endpoint_url() ) {
			return $args;
		}
	}

	$cap = zandi_http_timeout_cap();

	if ( $cap > 0 && ( ! isset( $args['timeout'] ) || $args['timeout'] > $cap ) ) {
		$args['timeout'] = $cap;
	}

	return $args;
}
add_filter( 'http_request_args', 'zandi_cap_http_timeout', 99, 2 );

/**
 * Path fragments identifying assets only the auth pages need.
 *
 * Matched against the registered `src`, not against handle or class names. That
 * distinction matters here: this theme has been bitten before by matching a
 * plugin's *class names* by substring (see the note in assets/css/panel.css),
 * because class names do not partition cleanly. A plugin's directory in the
 * `src` URL does — `/plugins/digits/` is that plugin's files and nothing else.
 *
 * @return string[]
 */
function zandi_auth_asset_paths() {
	return (array) apply_filters( 'zandi_auth_asset_paths', array( '/plugins/digits/' ) );
}

/**
 * Keeps the login plugin's assets on the two pages that render a login form.
 *
 * Digits enqueues its stylesheet and script on every front-end page, because it
 * cannot know the theme only ever asks for a form on two routes. Everywhere
 * else — the homepage above all — that is a stylesheet and a script fetched,
 * parsed and thrown away.
 *
 * Scoped to `/login/` and `/register/`, which per CLAUDE.md are the only auth
 * pages that exist and must always come from the same system. `/panel/` is for
 * users who are already signed in and renders no form, so it is excluded too.
 *
 * The one way this can bite: drop a Digits shortcode onto some other page and
 * its assets will have been removed. That is what the filter is for —
 *
 *     add_filter( 'zandi_trim_auth_assets', '__return_false' );
 *
 * @return void
 */
function zandi_trim_auth_assets() {
	if ( is_admin() || ! apply_filters( 'zandi_trim_auth_assets', true ) ) {
		return;
	}

	if ( ! function_exists( 'zandi_account_route' ) ) {
		return;
	}

	$route = zandi_account_route();

	if ( 'login' === $route || 'register' === $route ) {
		return;
	}

	$paths = zandi_auth_asset_paths();

	foreach ( array( 'style' => wp_styles(), 'script' => wp_scripts() ) as $type => $queue ) {
		if ( ! $queue instanceof WP_Dependencies ) {
			continue;
		}

		// Collected first: dequeuing inside the loop mutates what is iterated.
		$drop = array();

		foreach ( (array) $queue->queue as $handle ) {
			if ( ! isset( $queue->registered[ $handle ] ) ) {
				continue;
			}

			$src = (string) $queue->registered[ $handle ]->src;

			if ( '' === $src ) {
				continue;
			}

			foreach ( $paths as $needle ) {
				if ( false !== strpos( $src, $needle ) ) {
					$drop[] = $handle;
					break;
				}
			}
		}

		foreach ( $drop as $handle ) {
			if ( 'style' === $type ) {
				wp_dequeue_style( $handle );
			} else {
				wp_dequeue_script( $handle );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'zandi_trim_auth_assets', 100 );
