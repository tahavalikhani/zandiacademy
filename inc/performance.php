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
