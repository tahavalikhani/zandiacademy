<?php
/**
 * The sign-in log — TEMPORARY, added 1 October 2026 (1.5.5).
 *
 * Four rounds of «after signing up I am not signed in» were diagnosed from
 * screenshots, and each fix answered the one cause a screenshot could show. This
 * records what the server saw at each step of the journey, so the next report
 * comes with the step that failed and the reason it failed:
 *
 *   signup          Digits' sign-up request created the account: whether the
 *                   browser was already signed in, whether the landing cookie
 *                   and the sign-up pass were set, whether headers had already
 *                   gone (which makes every cookie, Digits' own included, fail);
 *   signin cookie   core set a sign-in cookie for a student — Digits signing
 *                   them in, or the pass doing it;
 *   page pass       a page that runs PHP received a sign-up pass, and what
 *                   became of it;
 *   hop             the landing hop ran: signed in or not, the pass and why it
 *                   was refused, where the student was sent.
 *
 * Each row also carries the request's scheme, host and path and WHICH of the
 * cookies the browser sent — names only, never a value — because a cookie the
 * server sets is not a cookie the browser sends back.
 *
 * Writes happen on those events only, never on a plain page view; the last 40
 * are kept in one option that is never autoloaded. The owner reads it at
 * wp-admin/admin-post.php?action=zandi_auth_trace — administrators only.
 *
 * To remove: delete this file and its require line in functions.php. auth.php
 * only fires the `zandi_auth_trace` action and does not care who listens.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * The option holding the log.
 *
 * @return string
 */
function zandi_auth_trace_option() {
	return 'zandi_auth_trace';
}

/**
 * What the request looked like, with nothing in it that could sign anybody in.
 *
 * @return array
 */
function zandi_auth_trace_request() {
	/*
	 * Read off the Cookie header the browser sent, not $_COOKIE: the theme writes
	 * $_COOKIE itself whenever it sets a cookie, and «the server set it» is not
	 * «the browser sent it» — the difference is the whole question.
	 */
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Only cookie NAMES are kept, matched against a fixed list.
	$header = isset( $_SERVER['HTTP_COOKIE'] ) ? (string) $_SERVER['HTTP_COOKIE'] : implode( '=; ', array_keys( $_COOKIE ) ) . '=';
	$known  = array( zandi_landing_cookie(), zandi_signup_cookie(), zandi_intent_cookie(), '_lscache_vary' );
	$sent   = array();

	foreach ( explode( ';', $header ) as $pair ) {
		$name = trim( (string) strtok( $pair, '=' ) );

		if ( in_array( $name, $known, true ) ) {
			$sent[] = $name;
		} elseif ( 0 === strpos( $name, 'wordpress_logged_in_' ) ) {
			$sent[] = 'wordpress_logged_in';
		} elseif ( 0 === strpos( $name, 'wordpress_sec_' ) ) {
			$sent[] = 'wordpress_sec';
		}
	}

	// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- Stored for an administrator's eyes and escaped on output.
	$uri     = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$referer = (string) wp_get_raw_referer();
	$origin  = isset( $_SERVER['HTTP_ORIGIN'] ) ? (string) wp_unslash( $_SERVER['HTTP_ORIGIN'] ) : '';
	$action  = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	// phpcs:enable

	return array(
		'scheme'  => is_ssl() ? 'https' : 'http',
		'host'    => isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '',
		'path'    => (string) wp_parse_url( $uri, PHP_URL_PATH ),
		'action'  => $action,
		'origin'  => $origin,
		'referer' => '' === $referer ? '' : (string) wp_parse_url( $referer, PHP_URL_SCHEME ) . '://' . (string) wp_parse_url( $referer, PHP_URL_HOST ) . (string) wp_parse_url( $referer, PHP_URL_PATH ),
		'sent'    => implode( ' ', array_unique( $sent ) ),
	);
}

/**
 * Adds one row to the log.
 *
 * @param string $event What happened.
 * @param array  $data  Facts about it. Never a cookie value, a key or a phone number.
 * @return void
 */
function zandi_auth_trace( $event, $data = array() ) {
	/**
	 * Filters whether the sign-in log records anything.
	 *
	 * @param bool $enabled Whether to record.
	 */
	if ( ! apply_filters( 'zandi_auth_trace_enabled', true ) ) {
		return;
	}

	$rows = get_option( zandi_auth_trace_option(), array() );
	$rows = is_array( $rows ) ? $rows : array();

	$rows[] = array_merge(
		array(
			'time'  => time(),
			'event' => (string) $event,
		),
		zandi_auth_trace_request(),
		is_array( $data ) ? $data : array()
	);

	update_option( zandi_auth_trace_option(), array_slice( $rows, -40 ), false );
}
add_action( 'zandi_auth_trace', 'zandi_auth_trace', 10, 2 );

/**
 * One value, as the log prints it.
 *
 * @param mixed $value Value.
 * @return string
 */
function zandi_auth_trace_value( $value ) {
	if ( is_bool( $value ) ) {
		return $value ? 'yes' : 'no';
	}

	return is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
}

/**
 * The page the owner reads it on.
 *
 * @return void
 */
function zandi_auth_trace_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ), 403 );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified on the next line.
	if ( isset( $_GET['clear'] ) && check_admin_referer( 'zandi_auth_trace_clear' ) ) {
		delete_option( zandi_auth_trace_option() );
	}

	$digits = '';

	if ( function_exists( 'get_plugins' ) ) {
		foreach ( get_plugins() as $file => $plugin ) {
			if ( false !== stripos( $plugin['Name'], 'digits' ) ) {
				$digits = $plugin['Version'] . ( is_plugin_active( $file ) ? '' : ' (inactive)' );
			}
		}
	}

	// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- Shown to an administrator, escaped below.
	$facts = array(
		'home'               => get_option( 'home' ),
		'siteurl'            => get_option( 'siteurl' ),
		'FORCE_SSL_ADMIN'    => force_ssl_admin(),
		'is_ssl() here'      => is_ssl(),
		'HTTPS'              => isset( $_SERVER['HTTPS'] ) ? (string) $_SERVER['HTTPS'] : '',
		'X-Forwarded-Proto'  => isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ? (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] : '',
		'COOKIE_DOMAIN'      => COOKIE_DOMAIN,
		'COOKIEPATH'         => COOKIEPATH,
		'theme'              => ZANDI_VERSION,
		'Digits'             => $digits,
		'LiteSpeed Cache'    => defined( 'LSCWP_V' ) ? LSCWP_V : '',
		'WordPress'          => get_bloginfo( 'version' ),
	);
	// phpcs:enable

	$rows = get_option( zandi_auth_trace_option(), array() );
	$rows = is_array( $rows ) ? array_reverse( $rows ) : array();

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );

	echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>گزارش ورود</title>';
	echo '<style>body{font:14px/1.5 system-ui,sans-serif;margin:16px;color:#1b365d}table{border-collapse:collapse;width:100%;margin:12px 0 24px;direction:ltr;font:12px/1.4 ui-monospace,monospace}td,th{border:1px solid #d5dbe5;padding:4px 6px;text-align:left;vertical-align:top}th{background:#f2f5f9}</style></head><body>';
	echo '<h1>گزارش ورود و ثبت‌نام</h1>';
	echo '<p>از این صفحه عکس بگیرید و بفرستید. هیچ کد یا رمزی در آن نیست.</p>';

	echo '<table>';
	foreach ( $facts as $label => $value ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( zandi_auth_trace_value( $value ) ) . '</td></tr>';
	}
	echo '</table>';

	if ( ! $rows ) {
		echo '<p>هنوز چیزی ثبت نشده.</p>';
	}

	foreach ( $rows as $row ) {
		echo '<table>';
		echo '<tr><th>time</th><td>' . esc_html( wp_date( 'Y-m-d H:i:s', (int) $row['time'] ) ) . '</td></tr>';
		foreach ( $row as $label => $value ) {
			if ( 'time' === $label || '' === $value ) {
				continue;
			}
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( zandi_auth_trace_value( $value ) ) . '</td></tr>';
		}
		echo '</table>';
	}

	echo '<p><a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'zandi_auth_trace', 'clear' => '1' ), admin_url( 'admin-post.php' ) ), 'zandi_auth_trace_clear' ) ) . '">پاک کردن گزارش</a></p>';
	echo '</body></html>';
	exit;
}
add_action( 'admin_post_zandi_auth_trace', 'zandi_auth_trace_screen' );
