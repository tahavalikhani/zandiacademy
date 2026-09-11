<?php
/**
 * Plugin Name: Zandi Telegram Probe
 * Description: Answers one question — can this server talk to Telegram? Temporary diagnostic; delete once the answer has been read.
 * Version: 1.0.0
 *
 * WHY THIS EXISTS
 *
 * The podcast plan needs a Telegram bot, and a bot is only cheap if it can run
 * on hosting that already exists. Telegram is filtered outbound from Iranian
 * datacentres, which is why Iranian bots are normally hosted in Germany — but
 * "normally" is not "always", and a second host is a yearly bill plus a second
 * place to deploy to. Five minutes of measuring is worth avoiding that.
 *
 * So this asks the server directly, and it asks in the same way the real code
 * would: through wp_remote_get(), so anything wp-config.php says about proxies
 * or blocked hosts applies here exactly as it would in production. A raw
 * curl test from the command line can pass while WordPress still fails.
 *
 * WHAT IT CHECKS, AND WHY EACH ONE
 *
 *   1. An Iranian host (ZarinPal). If this fails, outbound HTTPS is broken
 *      generally and nothing below means anything.
 *   2. A neutral foreign host. Separates "this server cannot reach the outside
 *      world" from "this server cannot reach Telegram specifically".
 *   3. api.telegram.org, with a deliberately invalid token.
 *
 * Point 3 is the whole file, and the trick is that a WRONG TOKEN IS A PASS.
 * Telegram answers an invalid token with HTTP 401 and a JSON body. Getting 401
 * back means the packets went to Telegram and Telegram replied — the network
 * works. So the test needs no real token and there is no secret to leak.
 *
 * HOW TO USE IT
 *
 *   1. Upload this file to  wp-content/mu-plugins/  (create the folder if it is
 *      not there). Files in mu-plugins load automatically; nothing to activate.
 *   2. Sign in as an administrator.
 *   3. Go to  ابزارها ← تست تلگرام  and press the button.
 *   4. Copy the whole report out of the box at the bottom and send it over.
 *   5. DELETE THIS FILE when finished.
 *
 * SAFETY
 *
 * Nothing here runs on a front-end request: the file returns immediately unless
 * this is wp-admin. Inside wp-admin it registers one menu item, and the tests
 * only run when an administrator presses the button and the nonce checks out —
 * never on a page view, so nobody can make this server hammer Telegram by
 * loading a URL. It writes nothing to the database.
 *
 * The token field is optional and exists only to confirm a token you have
 * already created. It is never stored, never logged, and is redacted out of the
 * report before you copy it — so the report is safe to paste anywhere.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/*
 * Everything below is wp-admin code. Returning here means a front-end request
 * does not parse it, does not register a hook, and cannot reach it — the same
 * guard inc/students.php uses, for the same reason.
 */
if ( ! is_admin() ) {
	return;
}

/**
 * How long any one request may block.
 *
 * Deliberately short. A filtered host does not refuse the connection, it
 * swallows it — the request hangs for the whole timeout. Three tests at the
 * WordPress default of five seconds is fine; at thirty it looks like the page
 * has crashed.
 */
const ZANDI_TG_PROBE_TIMEOUT = 8;

/**
 * The endpoints, in the order the report reads best.
 *
 * `expect` is the HTTP status that means SUCCESS for that row, which is not
 * always 200: Telegram's 401 is the pass condition described in the header.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_tg_probe_targets() {
	return array(
		array(
			'key'    => 'iran',
			'label'  => 'یک سایت ایرانی (زرین‌پال)',
			'url'    => 'https://www.zarinpal.com/',
			'expect' => 200,
			'why'    => 'اگر این هم رد شود، مشکل از تلگرام نیست — سرور اصلاً به بیرون دسترسی ندارد.',
		),
		array(
			'key'    => 'world',
			'label'  => 'یک سایت خارجی خنثی',
			'url'    => 'https://example.com/',
			'expect' => 200,
			'why'    => 'فرق «کل اینترنت خارج بسته است» با «فقط تلگرام بسته است».',
		),
		array(
			'key'    => 'telegram',
			'label'  => 'تلگرام (api.telegram.org)',
			'url'    => 'https://api.telegram.org/bot0:INVALID/getMe',
			'expect' => 401,
			'why'    => 'پاسخ ۴۰۱ یعنی موفقیت: توکن عمداً غلط است، پس ۴۰۱ یعنی تلگرام جواب داده.',
		),
		array(
			'key'    => 'bot-host',
			'label'  => 'هاست ربات (bot.zandiacademy.com)',
			'url'    => 'https://bot.zandiacademy.com/',
			'expect' => 404,
			'why'    => 'سایت باید بتواند به ربات خبر بدهد چه کسی پول داده. پاسخ ۴۰۴ یعنی موفقیت — ربات به درخواست بدون کلید عمداً ۴۰۴ می‌دهد.',
		),
	);
}

/**
 * Runs one request and describes what happened in plain terms.
 *
 * The distinction that matters is between a WP_Error and a response. A refused
 * or filtered host produces a WP_Error whose message names the cURL failure —
 * 6 is DNS, 28 is a timeout, 35 is TLS — and those three point at three
 * different problems. A response of any status at all means the packets made a
 * round trip.
 *
 * @param array<string,mixed> $target One row from zandi_tg_probe_targets().
 * @return array<string,mixed>
 */
function zandi_tg_probe_run( $target ) {
	$started = microtime( true );

	$response = wp_remote_get(
		$target['url'],
		array(
			'timeout'     => ZANDI_TG_PROBE_TIMEOUT,
			'redirection' => 2,
			'sslverify'   => true,
			'user-agent'  => 'ZandiTelegramProbe/1.0',
		)
	);

	$elapsed = microtime( true ) - $started;

	if ( is_wp_error( $response ) ) {
		return array(
			'ok'      => false,
			'status'  => 0,
			'detail'  => $response->get_error_message(),
			'elapsed' => $elapsed,
		);
	}

	$status = (int) wp_remote_retrieve_response_code( $response );

	return array(
		'ok'      => $status === (int) $target['expect'],
		'status'  => $status,
		'detail'  => trim( substr( (string) wp_remote_retrieve_body( $response ), 0, 160 ) ),
		'elapsed' => $elapsed,
	);
}

/**
 * Confirms a real bot token, when one is supplied.
 *
 * Optional on purpose — the network question is already answered without it.
 * This exists so that after creating the bot you can prove the token itself is
 * good before it goes anywhere near a config file.
 *
 * The token is used for this one request and then dropped. It is never stored
 * and never printed back, so the report can be pasted anywhere.
 *
 * @param string $token Bot token from @BotFather.
 * @return array<string,mixed>
 */
function zandi_tg_probe_token( $token ) {
	$token = trim( $token );

	if ( '' === $token ) {
		return array();
	}

	$started = microtime( true );

	$response = wp_remote_get(
		'https://api.telegram.org/bot' . rawurlencode( $token ) . '/getMe',
		array(
			'timeout'    => ZANDI_TG_PROBE_TIMEOUT,
			'sslverify'  => true,
			'user-agent' => 'ZandiTelegramProbe/1.0',
		)
	);

	$elapsed = microtime( true ) - $started;

	if ( is_wp_error( $response ) ) {
		return array(
			'ok'      => false,
			'detail'  => $response->get_error_message(),
			'elapsed' => $elapsed,
		);
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) || empty( $body['ok'] ) || empty( $body['result']['username'] ) ) {
		return array(
			'ok'      => false,
			'detail'  => 'توکن رد شد (' . (int) wp_remote_retrieve_response_code( $response ) . ').',
			'elapsed' => $elapsed,
		);
	}

	return array(
		'ok'      => true,
		'detail'  => '@' . $body['result']['username'],
		'elapsed' => $elapsed,
	);
}

/**
 * The facts about this server I cannot read from outside.
 *
 * WP_HTTP_BLOCK_EXTERNAL is the one that catches people out: a host or a
 * security plugin can set it in wp-config.php, and then every wp_remote_get()
 * fails in a way that looks exactly like a network block but is a local
 * setting. It is worth knowing before anyone buys a second server.
 *
 * @return array<string,string>
 */
function zandi_tg_probe_environment() {
	global $wp_version;

	$facts = array(
		'PHP'              => PHP_VERSION,
		'WordPress'        => isset( $wp_version ) ? (string) $wp_version : 'unknown',
		'وب‌سرور'          => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'unknown',
		'cURL'             => function_exists( 'curl_version' ) ? ( curl_version()['version'] ?? 'yes' ) : 'MISSING',
		'OpenSSL'          => defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : 'MISSING',
		'allow_url_fopen'  => ini_get( 'allow_url_fopen' ) ? 'on' : 'off',
		'WooCommerce'      => defined( 'WC_VERSION' ) ? WC_VERSION : 'not active',
	);

	$flags = array(
		'WP_HTTP_BLOCK_EXTERNAL' => defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL ? 'ON — outbound requests are blocked in wp-config.php' : 'off',
		'WP_ACCESSIBLE_HOSTS'    => defined( 'WP_ACCESSIBLE_HOSTS' ) ? (string) WP_ACCESSIBLE_HOSTS : 'not set',
		'WP_PROXY_HOST'          => defined( 'WP_PROXY_HOST' ) ? (string) WP_PROXY_HOST : 'not set',
		'DISABLE_WP_CRON'        => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'true (a real cron job should be running)' : 'false',
	);

	return array_merge( $facts, $flags );
}

/**
 * Registers the screen.
 *
 * Under ابزارها rather than a top-level menu: this is a thing you use once and
 * delete, not a part of the site.
 *
 * @return void
 */
function zandi_tg_probe_menu() {
	add_management_page(
		'تست تلگرام',
		'تست تلگرام',
		'manage_options',
		'zandi-telegram-probe',
		'zandi_tg_probe_screen'
	);
}
add_action( 'admin_menu', 'zandi_tg_probe_menu' );

/**
 * Draws the screen, and runs the tests when asked.
 *
 * @return void
 */
function zandi_tg_probe_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'دسترسی ندارید.' );
	}

	$ran    = false;
	$rows   = array();
	$token  = array();
	$report = array();

	if ( isset( $_POST['zandi_tg_probe'] ) && check_admin_referer( 'zandi_tg_probe' ) ) {
		$ran = true;

		foreach ( zandi_tg_probe_targets() as $target ) {
			$rows[ $target['key'] ] = array_merge( $target, zandi_tg_probe_run( $target ) );
		}

		// Not sanitized with sanitize_text_field(): a bot token is a colon-bearing
		// opaque string and this value is used for one request, then dropped.
		$raw   = isset( $_POST['zandi_tg_token'] ) ? trim( (string) wp_unslash( $_POST['zandi_tg_token'] ) ) : '';
		$token = zandi_tg_probe_token( $raw );
	}

	echo '<div class="wrap"><h1>تست دسترسی به تلگرام</h1>';
	echo '<p style="max-width:46rem">این صفحه فقط یک سؤال را جواب می‌دهد: آیا همین سرور می‌تواند با تلگرام حرف بزند؟ اگر بتواند، ربات پادکست روی همین هاست اجرا می‌شود و لازم نیست هاست دومی بخری.</p>';

	echo '<form method="post">';
	wp_nonce_field( 'zandi_tg_probe' );
	echo '<p><label for="zandi_tg_token"><strong>توکن ربات (اختیاری)</strong></label><br>';
	echo '<input type="password" name="zandi_tg_token" id="zandi_tg_token" class="regular-text" autocomplete="off" placeholder="خالی بگذار — برای تست شبکه لازم نیست">';
	echo '<br><span class="description">اگر ربات را ساخته‌ای و می‌خواهی توکنش هم چک شود اینجا بگذار. ذخیره نمی‌شود و در گزارش هم نمی‌آید.</span></p>';
	submit_button( 'تست کن', 'primary', 'zandi_tg_probe' );
	echo '</form>';

	if ( ! $ran ) {
		echo '</div>';
		return;
	}

	echo '<table class="widefat striped" style="max-width:52rem"><thead><tr><th>چه چیزی</th><th>نتیجه</th><th>زمان</th></tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$mark  = $row['ok'] ? '✅' : '❌';
		$state = $row['ok'] ? 'موفق' : 'ناموفق';
		$note  = 0 === $row['status'] ? $row['detail'] : 'HTTP ' . $row['status'];

		printf(
			'<tr><td><strong>%s</strong><br><span class="description">%s</span></td><td>%s %s<br><code>%s</code></td><td>%.1fs</td></tr>',
			esc_html( $row['label'] ),
			esc_html( $row['why'] ),
			esc_html( $mark ),
			esc_html( $state ),
			esc_html( $note ),
			(float) $row['elapsed']
		);

		$report[] = sprintf( '%-28s %s  %s  (%.1fs)', $row['key'], $row['ok'] ? 'PASS' : 'FAIL', 0 === $row['status'] ? $row['detail'] : 'HTTP ' . $row['status'], $row['elapsed'] );
	}

	if ( $token ) {
		printf(
			'<tr><td><strong>%s</strong><br><span class="description">%s</span></td><td>%s<br><code>%s</code></td><td>%.1fs</td></tr>',
			'توکن ربات',
			'فقط وقتی توکن وارد شده باشد.',
			esc_html( $token['ok'] ? '✅ معتبر' : '❌ نامعتبر' ),
			esc_html( $token['detail'] ),
			(float) $token['elapsed']
		);

		$report[] = sprintf( '%-28s %s  %s', 'bot token', $token['ok'] ? 'PASS' : 'FAIL', $token['detail'] );
	}

	echo '</tbody></table>';

	/*
	 * Two independent verdicts, and the second one is the one that matters now.
	 *
	 * Telegram failing here was always the expected answer and is not a problem:
	 * it is why the bot runs in Germany. What the plan actually depends on is
	 * this server being able to reach the bot's host, because that is how
	 * WordPress tells the bot who has paid.
	 */
	$verdict = ! empty( $rows['telegram']['ok'] )
		? 'این سرور به تلگرام دسترسی دارد. ربات می‌تواند روی همین هاست هم اجرا شود.'
		: 'این سرور به تلگرام دسترسی ندارد — همان‌طور که انتظار می‌رفت. برای همین ربات روی هاست آلمان است و این ایراد نیست.';

	$bridge = ! empty( $rows['bot-host']['ok'] )
		? 'و مهم‌تر: این سرور به هاست ربات می‌رسد، پس سایت می‌تواند به ربات خبر بدهد چه کسی اشتراک خریده. این تنها چیزی بود که نقشه به آن نیاز داشت.'
		: 'اما این سرور به هاست ربات نمی‌رسد، و نقشه به آن نیاز دارد. این را به من بگو.';

	echo '<h2>نتیجه</h2><p style="max-width:46rem;font-size:1.05em"><strong>' . esc_html( $verdict ) . '</strong></p>';
	echo '<p style="max-width:46rem;font-size:1.05em"><strong>' . esc_html( $bridge ) . '</strong></p>';

	foreach ( zandi_tg_probe_environment() as $key => $value ) {
		$report[] = sprintf( '%-28s %s', $key, $value );
	}

	echo '<h2>این را کپی کن و بفرست</h2>';
	echo '<textarea readonly rows="22" style="width:100%;max-width:52rem;font-family:monospace;font-size:12px">' . esc_textarea( implode( "\n", $report ) ) . '</textarea>';
	echo '<p class="description">توکن داخل این متن نیست.</p>';
	echo '</div>';
}
