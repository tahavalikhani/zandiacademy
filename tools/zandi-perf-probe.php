<?php
/**
 * Plugin Name: Zandi Performance Probe
 * Description: Reports where a page load's time actually goes. Temporary diagnostic — delete once the numbers have been read.
 * Version: 1.0.0
 *
 * WHY THIS EXISTS
 *
 * Every round of performance work on this site so far has been done blind. The
 * theme has been audited repeatedly and is not the bottleneck; the advice that
 * follows from that — install a page cache, move WP-Cron, raise PHP — is the
 * right advice, but nobody has been able to say which of those is actually
 * costing the seconds, because nothing has ever measured this server.
 *
 * This measures it. It is not an optimiser and it changes nothing about how the
 * site behaves. It watches one page load and prints what it saw.
 *
 * HOW TO USE IT
 *
 *   1. Upload this file to  wp-content/mu-plugins/  (create the folder if it is
 *      not there). Files in mu-plugins load automatically — there is nothing to
 *      activate.
 *   2. Sign in as an administrator.
 *   3. Open the homepage with ?zandi_probe=1 on the end:
 *          https://zandiacademy.com/?zandi_probe=1
 *   4. Scroll to the bottom of the page and copy the whole report.
 *   5. Repeat on a course page:  /courses/a1?zandi_probe=1
 *   6. DELETE THIS FILE when finished.
 *
 * SAFETY
 *
 * The report is only ever generated for a signed-in administrator who asks for
 * it by hand. A visitor cannot see it, cannot trigger it, and is never slowed
 * down by it — for anyone else this file registers two cheap hooks and returns.
 * It writes nothing to the database.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Milestones, recorded as WordPress passes them.
 *
 * The gaps between these are the diagnosis. A large gap before `plugins_loaded`
 * is plugin code being parsed and booted; a large one at `init` is plugins
 * doing work on every request; a large one across the query is the database.
 *
 * @var array<string,float>
 */
$GLOBALS['zandi_probe_marks'] = array( 'request start' => zandi_probe_start_time() );

/**
 * The best available start-of-request timestamp.
 *
 * REQUEST_TIME_FLOAT is set by PHP before WordPress exists, so it captures the
 * part of the boot that a plugin otherwise cannot see.
 *
 * @return float
 */
function zandi_probe_start_time() {
	if ( isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ) {
		return (float) $_SERVER['REQUEST_TIME_FLOAT'];
	}

	return microtime( true );
}

/**
 * Records a named milestone.
 *
 * @param string $label Milestone name.
 * @return void
 */
function zandi_probe_mark( $label ) {
	$GLOBALS['zandi_probe_marks'][ $label ] = microtime( true );
}

foreach ( array(
	'muplugins_loaded',
	'plugins_loaded',
	'setup_theme',
	'after_setup_theme',
	'init',
	'wp_loaded',
	'wp',
	'template_redirect',
	'wp_head',
	'wp_footer',
) as $zandi_probe_hook ) {
	add_action(
		$zandi_probe_hook,
		function () use ( $zandi_probe_hook ) {
			zandi_probe_mark( $zandi_probe_hook );
		},
		-PHP_INT_MAX
	);
}

/**
 * Every outbound HTTP request the page made, and how long it blocked for.
 *
 * This is the one that finds a ten-second first byte. From an Iranian server a
 * surprising number of the hosts plugins call are unreachable rather than slow,
 * so the request does not fail — it hangs for the whole timeout while the
 * visitor stares at a white screen.
 *
 * @var array<int,array<string,mixed>>
 */
$GLOBALS['zandi_probe_http'] = array();

add_action(
	'http_api_debug',
	function ( $response, $context, $class, $args, $url ) {
		$GLOBALS['zandi_probe_http'][] = array(
			'url'     => (string) $url,
			'timeout' => isset( $args['timeout'] ) ? (float) $args['timeout'] : 0,
			'error'   => is_wp_error( $response ) ? $response->get_error_message() : '',
			'at'      => microtime( true ),
		);
	},
	10,
	5
);

/**
 * Whether this request should print a report.
 *
 * @return bool
 */
function zandi_probe_wanted() {
	if ( empty( $_GET['zandi_probe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only diagnostic switch.
		return false;
	}

	return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
}

/**
 * Formats a byte count.
 *
 * @param int|float $bytes Bytes.
 * @return string
 */
function zandi_probe_bytes( $bytes ) {
	$units = array( 'B', 'KB', 'MB', 'GB' );
	$i     = 0;

	while ( $bytes >= 1024 && $i < 3 ) {
		$bytes /= 1024;
		++$i;
	}

	return sprintf( '%.1f %s', $bytes, $units[ $i ] );
}

/**
 * How much of wp_options is loaded on every single request.
 *
 * THE MOST COMMONLY MISSED CAUSE OF A SLOW WordPress. Every option marked
 * `autoload = yes` is fetched and unserialized on every page load, cached page
 * or not. WooCommerce, a paid auth plugin and anything ever installed and
 * deleted all leave rows behind. Under ~200 KB is healthy; over 1 MB is a
 * measurable tax on every request; several MB is on its own enough to explain a
 * slow site.
 *
 * @return array<string,mixed>
 */
function zandi_probe_autoload() {
	global $wpdb;

	$size = (float) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto','auto-on')" );
	$rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto','auto-on')" );

	$worst = $wpdb->get_results( "SELECT option_name, LENGTH(option_value) AS bytes FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto','auto-on') ORDER BY bytes DESC LIMIT 12" );

	return array(
		'bytes' => $size,
		'rows'  => $rows,
		'worst' => is_array( $worst ) ? $worst : array(),
	);
}

/**
 * Counts transients, which live in the same table and bloat it.
 *
 * @return array<string,int>
 */
function zandi_probe_transients() {
	global $wpdb;

	return array(
		'all'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%'" ),
		'expired' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_timeout\\_%' AND option_value < %d", time() ) ),
	);
}

/**
 * Prints the report.
 *
 * @return void
 */
function zandi_probe_report() {
	if ( ! zandi_probe_wanted() ) {
		return;
	}

	zandi_probe_mark( 'shutdown' );

	$marks = $GLOBALS['zandi_probe_marks'];
	$start = reset( $marks );
	$end   = end( $marks );
	$total = ( $end - $start ) * 1000;

	$out   = array();
	$out[] = '===== ZANDI PERFORMANCE PROBE =====';
	$out[] = 'URL:  ' . ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) : home_url( '/' ) );
	$out[] = 'When: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC';
	$out[] = '';

	/* ---- Where the time went ---- */
	$out[] = '--- TIME (total PHP: ' . sprintf( '%.0f ms', $total ) . ') ---';
	$out[] = 'A big jump between two lines is where the time is going.';
	$out[] = '';

	$previous = $start;

	foreach ( $marks as $label => $when ) {
		$since = ( $when - $previous ) * 1000;
		$from  = ( $when - $start ) * 1000;

		$out[] = sprintf( '  %-20s %8.1f ms   (+%.1f ms)', $label, $from, $since );

		$previous = $when;
	}

	$out[] = '';

	/* ---- Database ---- */
	$out[] = '--- DATABASE ---';
	$out[] = '  queries this page: ' . (int) get_num_queries();

	if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES && ! empty( $GLOBALS['wpdb']->queries ) ) {
		$queries = $GLOBALS['wpdb']->queries;

		usort(
			$queries,
			function ( $a, $b ) {
				return $b[1] <=> $a[1];
			}
		);

		$out[] = '  slowest:';

		foreach ( array_slice( $queries, 0, 5 ) as $query ) {
			$out[] = sprintf( '    %6.1f ms  %s', $query[1] * 1000, substr( preg_replace( '/\s+/', ' ', $query[0] ), 0, 110 ) );
		}
	} else {
		$out[] = '  (for the slowest-query list, add the SAVEQUERIES constant to wp-config.php';
		$out[] = '   as true, reload, then REMOVE it again — it is itself a slowdown)';
	}

	$out[] = '';

	/* ---- Autoloaded options ---- */
	$autoload = zandi_probe_autoload();
	$verdict  = '  HEALTHY';

	if ( $autoload['bytes'] > 3145728 ) {
		$verdict = '  *** SEVERE — this alone can explain a slow site ***';
	} elseif ( $autoload['bytes'] > 1048576 ) {
		$verdict = '  *** HIGH — worth cleaning ***';
	} elseif ( $autoload['bytes'] > 512000 ) {
		$verdict = '  ELEVATED';
	}

	$out[] = '--- AUTOLOADED OPTIONS (read on EVERY request, cached or not) ---';
	$out[] = '  total: ' . zandi_probe_bytes( $autoload['bytes'] ) . ' across ' . $autoload['rows'] . ' rows';
	$out[] = $verdict;
	$out[] = '  largest:';

	foreach ( $autoload['worst'] as $row ) {
		$out[] = sprintf( '    %-52s %s', substr( $row->option_name, 0, 52 ), zandi_probe_bytes( $row->bytes ) );
	}

	$transients = zandi_probe_transients();
	$out[]      = '  transients: ' . $transients['all'] . ' total, ' . $transients['expired'] . ' expired and not cleaned up';
	$out[]      = '';

	/* ---- Outbound HTTP ---- */
	$out[] = '--- OUTBOUND HTTP DURING THIS PAGE LOAD ---';
	$out[] = '  Anything here is the visitor waiting on another server.';

	if ( empty( $GLOBALS['zandi_probe_http'] ) ) {
		$out[] = '  none — good';
	} else {
		foreach ( $GLOBALS['zandi_probe_http'] as $call ) {
			$out[] = sprintf( '    timeout %4.1fs  %s%s', $call['timeout'], substr( $call['url'], 0, 90 ), $call['error'] ? '   ERROR: ' . $call['error'] : '' );
		}
	}

	$out[] = '';

	/* ---- Environment ---- */
	$out[] = '--- ENVIRONMENT ---';
	$out[] = '  PHP:            ' . PHP_VERSION;
	$out[] = '  WordPress:      ' . get_bloginfo( 'version' );
	$out[] = '  memory used:    ' . zandi_probe_bytes( memory_get_peak_usage( true ) );

	$opcache = function_exists( 'opcache_get_status' ) ? @opcache_get_status( false ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Disabled OPcache raises a notice on some builds.

	if ( is_array( $opcache ) && ! empty( $opcache['opcache_enabled'] ) ) {
		$hits   = isset( $opcache['opcache_statistics']['hits'] ) ? (float) $opcache['opcache_statistics']['hits'] : 0;
		$misses = isset( $opcache['opcache_statistics']['misses'] ) ? (float) $opcache['opcache_statistics']['misses'] : 0;
		$rate   = ( $hits + $misses ) > 0 ? ( $hits / ( $hits + $misses ) ) * 100 : 0;

		$out[] = sprintf( '  OPcache:        ON  (hit rate %.1f%%)', $rate );
	} else {
		$out[] = '  OPcache:        *** OFF — every request re-parses all PHP. Turn this on first. ***';
	}

	$out[] = '  object cache:   ' . ( wp_using_ext_object_cache() ? 'ON' : 'OFF (Redis/Memcached not in use)' );
	$out[] = '  WP-Cron:        ' . ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'off the request path — good' : '*** running on visitor page loads ***' );
	$out[] = '  server:         ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'unknown' );

	$out[] = '';

	/* ---- Plugins ---- */
	$out[] = '--- ACTIVE PLUGINS ---';
	$out[] = '  Every one of these runs on every request.';

	$active = (array) get_option( 'active_plugins', array() );

	foreach ( $active as $plugin ) {
		$out[] = '    ' . $plugin;
	}

	$out[] = '  count: ' . count( $active );
	$out[] = '';
	$out[] = '===== END =====';

	echo "\n<pre style=\"direction:ltr;text-align:left;background:#0d1117;color:#c9d1d9;padding:20px;overflow:auto;font:12px/1.6 ui-monospace,monospace;border:2px solid #C8102E;margin:0\">\n";
	echo esc_html( implode( "\n", $out ) );
	echo "\n</pre>\n";
}
add_action( 'shutdown', 'zandi_probe_report', PHP_INT_MAX );
