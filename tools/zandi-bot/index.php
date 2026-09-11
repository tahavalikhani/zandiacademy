<?php
/**
 * Zandi podcast bot — bootstrap.
 *
 * WHAT THIS IS, AND WHAT IT IS NOT
 *
 * This is not the finished bot. It is the smallest thing that proves the chain
 * works end to end — German host → Telegram → group → back again — and that
 * answers the three questions nobody can answer from Iran:
 *
 *   1. Can this host actually reach api.telegram.org?
 *   2. Can Telegram actually reach this host? (A webhook is the only proof.
 *      Outbound working says nothing about inbound.)
 *   3. Is the podcast group a supergroup, and what is its chat id?
 *
 * Question 3 matters more than it looks. banChatMember works everywhere, but
 * unbanChatMember only works in supergroups and channels — in a basic group the
 * bot can remove somebody and then cannot let them back in when they renew.
 * There is no way to ask from outside; the group has to tell us, and it tells
 * us by the bot being added to it and one message arriving.
 *
 * The access logic — entitlements, join requests, the expiry sweep — lands on
 * top of this once these three answers are in.
 *
 * INSTALLING IT
 *
 *   1. Upload index.php and config.sample.php to the bot subdomain's folder.
 *   2. Rename config.sample.php to config.php and fill in the two secrets.
 *   3. Open  https://bot.zandiacademy.com/?key=YOUR-SECRET  and follow it.
 *
 * @package Zandi
 */

declare( strict_types = 1 );

const ZANDI_BOT_API     = 'https://api.telegram.org/bot';
const ZANDI_BOT_TIMEOUT = 10;
const ZANDI_BOT_LOG     = __DIR__ . '/updates.log.php';

/**
 * Loads config.php, or explains precisely what is missing.
 *
 * Fails loudly rather than falling back to defaults. A bot running on a
 * half-configured file would answer Telegram with nonsense and the symptom
 * would be a silence nobody could diagnose.
 *
 * @return array<string,mixed>
 */
function zandi_bot_config(): array {
	static $config = null;

	if ( null !== $config ) {
		return $config;
	}

	$path = __DIR__ . '/config.php';

	if ( ! is_readable( $path ) ) {
		zandi_bot_stop( 'config.php is missing. Copy config.sample.php to config.php and fill it in.' );
	}

	$config = require $path;

	if ( ! is_array( $config ) ) {
		zandi_bot_stop( 'config.php must return an array.' );
	}

	foreach ( array( 'token', 'secret', 'webhook_url' ) as $key ) {
		if ( empty( $config[ $key ] ) || str_contains( (string) $config[ $key ], 'HERE' ) ) {
			zandi_bot_stop( sprintf( 'config.php still has the placeholder for "%s".', $key ) );
		}
	}

	if ( strlen( (string) $config['secret'] ) < 20 ) {
		zandi_bot_stop( 'The secret in config.php is too short. Use 30 or more random characters.' );
	}

	return $config;
}

/**
 * Ends the request with a plain message and no clues about what lives here.
 *
 * @param string $message Text.
 * @param int    $status  HTTP status.
 * @return never
 */
function zandi_bot_stop( string $message, int $status = 500 ) {
	http_response_code( $status );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo $message, "\n";
	exit;
}

/**
 * Calls one Bot API method.
 *
 * @param string              $method Method name, e.g. 'getMe'.
 * @param array<string,mixed> $params Parameters.
 * @return array<string,mixed> Decoded reply, or an 'error' key.
 */
function zandi_bot_call( string $method, array $params = array() ): array {
	$config = zandi_bot_config();
	$url    = ZANDI_BOT_API . $config['token'] . '/' . $method;

	$ch = curl_init( $url );

	curl_setopt_array(
		$ch,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => http_build_query( $params ),
			CURLOPT_TIMEOUT        => ZANDI_BOT_TIMEOUT,
			CURLOPT_CONNECTTIMEOUT => ZANDI_BOT_TIMEOUT,
		)
	);

	$body  = curl_exec( $ch );
	$error = curl_error( $ch );
	curl_close( $ch );

	if ( false === $body ) {
		return array( 'ok' => false, 'error' => $error ?: 'no response' );
	}

	$decoded = json_decode( (string) $body, true );

	return is_array( $decoded ) ? $decoded : array( 'ok' => false, 'error' => 'unreadable reply' );
}

/**
 * Appends one line to the update log.
 *
 * The log lives in the web root because that is the only writable place a
 * shared host guarantees, so the file opens with a PHP exit tag: fetching its
 * URL executes that tag and returns nothing. Reading it happens through the
 * setup page, which needs the key.
 *
 * @param string $line Text.
 * @return void
 */
function zandi_bot_log( string $line ): void {
	if ( ! file_exists( ZANDI_BOT_LOG ) ) {
		file_put_contents( ZANDI_BOT_LOG, "<?php exit; ?>\n" );
	}

	$entry = sprintf( "%s  %s\n", gmdate( 'Y-m-d H:i:s' ), str_replace( array( "\r", "\n" ), ' ', $line ) );

	file_put_contents( ZANDI_BOT_LOG, $entry, FILE_APPEND | LOCK_EX );
}

/**
 * Handles one incoming update from Telegram.
 *
 * Everything is logged and almost nothing is answered — at this stage the point
 * is to learn the group's identity, not to be useful. The one reply is to
 * /start in a private chat, so there is a visible sign of life.
 *
 * @param array<string,mixed> $update Decoded update.
 * @return void
 */
function zandi_bot_handle( array $update ): void {
	/*
	 * my_chat_member fires when the bot is added to or promoted in a chat. It
	 * is the cheapest way to learn a private group's id: there is no API that
	 * looks one up, the chat has to introduce itself.
	 */
	if ( isset( $update['my_chat_member']['chat'] ) ) {
		$chat = $update['my_chat_member']['chat'];

		zandi_bot_log(
			sprintf(
				'JOINED  chat_id=%s  type=%s  title=%s  status=%s',
				$chat['id'] ?? '?',
				$chat['type'] ?? '?',
				$chat['title'] ?? '-',
				$update['my_chat_member']['new_chat_member']['status'] ?? '?'
			)
		);

		return;
	}

	$message = $update['message'] ?? $update['channel_post'] ?? null;

	if ( ! is_array( $message ) ) {
		zandi_bot_log( 'OTHER   ' . implode( ',', array_keys( $update ) ) );
		return;
	}

	$chat = $message['chat'] ?? array();

	zandi_bot_log(
		sprintf(
			'MESSAGE chat_id=%s  type=%s  title=%s  from=%s  text=%s',
			$chat['id'] ?? '?',
			$chat['type'] ?? '?',
			$chat['title'] ?? '-',
			$message['from']['id'] ?? '?',
			mb_substr( (string) ( $message['text'] ?? '' ), 0, 60 )
		)
	);

	if ( 'private' === ( $chat['type'] ?? '' ) && str_starts_with( (string) ( $message['text'] ?? '' ), '/start' ) ) {
		zandi_bot_call(
			'sendMessage',
			array(
				'chat_id' => $chat['id'],
				'text'    => "سلام! ربات وصل است و کار می‌کند.\n\nهنوز کاری بلد نیست — این فقط تست اتصال است.\n\nشناسه‌ی شما: " . ( $message['from']['id'] ?? '?' ),
			)
		);
	}
}

/* =========================================================================
 * Routing
 *
 * Three doors, and everything else is a 404 that gives nothing away. A bot
 * endpoint is a public URL that anyone can find, so it should look like
 * nothing when it is poked.
 * ====================================================================== */

$config = zandi_bot_config();
$key    = (string) ( $_GET['key'] ?? '' );

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	/*
	 * setWebhook was given a secret_token, so Telegram repeats it on every
	 * delivery. Anything without it is someone else knocking.
	 */
	$sent = (string) ( $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '' );

	if ( ! hash_equals( (string) $config['secret'], $sent ) ) {
		zandi_bot_stop( 'Not found', 404 );
	}

	$update = json_decode( (string) file_get_contents( 'php://input' ), true );

	if ( is_array( $update ) ) {
		zandi_bot_handle( $update );
	}

	http_response_code( 200 );
	exit;
}

if ( '' === $key || ! hash_equals( (string) $config['secret'], $key ) ) {
	zandi_bot_stop( 'Not found', 404 );
}

/* ---- setup page ---- */

$action = (string) ( $_GET['do'] ?? '' );
$notice = '';

if ( 'set' === $action ) {
	$result = zandi_bot_call(
		'setWebhook',
		array(
			'url'             => rtrim( (string) $config['webhook_url'], '/' ) . '/',
			'secret_token'    => $config['secret'],
			'allowed_updates' => json_encode( array( 'message', 'my_chat_member', 'chat_join_request' ) ),
		)
	);

	$notice = ! empty( $result['ok'] ) ? 'وبهوک ثبت شد.' : 'ثبت وبهوک شکست خورد: ' . ( $result['description'] ?? $result['error'] ?? '?' );
}

if ( 'clear' === $action ) {
	zandi_bot_call( 'deleteWebhook' );
	$notice = 'وبهوک حذف شد.';
}

$me      = zandi_bot_call( 'getMe' );
$hook    = zandi_bot_call( 'getWebhookInfo' );
$log     = is_readable( ZANDI_BOT_LOG ) ? (string) file_get_contents( ZANDI_BOT_LOG ) : '';
$lines   = array_slice( array_filter( explode( "\n", str_replace( '<?php exit; ?>', '', $log ) ) ), -25 );
$reached = ! empty( $me['ok'] );

header( 'Content-Type: text/html; charset=utf-8' );
?>
<!doctype html>
<html lang="fa" dir="rtl">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>راه‌اندازی ربات</title>
<style>
body{font-family:Tahoma,system-ui,sans-serif;max-width:46rem;margin:0 auto;padding:24px 18px 60px;background:#f6f7f9;color:#16202f;line-height:1.7}
h1{font-size:1.4rem;margin:0 0 4px}
h2{font-size:1.05rem;margin:28px 0 8px}
.card{background:#fff;border:1px solid #e1e6ed;border-radius:10px;padding:14px 16px;margin-top:12px}
.ok{color:#1b6e52;font-weight:700}.bad{color:#b3261e;font-weight:700}
code,pre{font-family:ui-monospace,Menlo,monospace;font-size:.82rem;direction:ltr;unicode-bidi:isolate}
pre{background:#eff2f6;border:1px solid #e1e6ed;border-radius:8px;padding:12px;overflow-x:auto;text-align:left}
a.btn{display:inline-block;background:#1b365d;color:#fff;text-decoration:none;padding:8px 16px;border-radius:8px;margin-left:8px;font-size:.9rem}
.note{background:#fff6e5;border:1px solid #f0dcb4;border-radius:8px;padding:12px 14px;margin-top:12px;font-size:.92rem}
dt{font-weight:700;font-size:.85rem;color:#5b6980;margin-top:8px}
dd{margin:0}
</style>

<h1>راه‌اندازی ربات پادکست</h1>
<p style="color:#5b6980;margin:0">این صفحه موقتی است و فقط با کلید باز می‌شود.</p>

<?php if ( $notice ) : ?>
	<div class="note"><?php echo htmlspecialchars( $notice, ENT_QUOTES, 'UTF-8' ); ?></div>
<?php endif; ?>

<h2>۱. آیا این سرور به تلگرام می‌رسد؟</h2>
<div class="card">
	<?php if ( $reached ) : ?>
		<p class="ok">✅ بله. ربات: <code>@<?php echo htmlspecialchars( (string) ( $me['result']['username'] ?? '?' ), ENT_QUOTES, 'UTF-8' ); ?></code></p>
		<p style="font-size:.9rem;color:#5b6980;margin:0">یعنی هم توکن درست است و هم هاست آلمان فیلتر ندارد.</p>
	<?php else : ?>
		<p class="bad">❌ نه.</p>
		<pre><?php echo htmlspecialchars( (string) ( $me['description'] ?? $me['error'] ?? 'unknown' ), ENT_QUOTES, 'UTF-8' ); ?></pre>
		<p style="font-size:.9rem;color:#5b6980;margin:0">اگر نوشته <code>Unauthorized</code> توکن غلط است؛ اگر از timeout حرف زده، شبکه‌ی هاست مشکل دارد.</p>
	<?php endif; ?>
</div>

<h2>۲. آیا تلگرام به این سرور می‌رسد؟</h2>
<div class="card">
	<dl>
		<dt>آدرس ثبت‌شده</dt>
		<dd><code><?php echo htmlspecialchars( (string) ( $hook['result']['url'] ?? '—' ), ENT_QUOTES, 'UTF-8' ); ?></code></dd>
		<dt>در صف مانده</dt>
		<dd><code><?php echo (int) ( $hook['result']['pending_update_count'] ?? 0 ); ?></code></dd>
		<dt>آخرین خطا</dt>
		<dd><code><?php echo htmlspecialchars( (string) ( $hook['result']['last_error_message'] ?? '—' ), ENT_QUOTES, 'UTF-8' ); ?></code></dd>
	</dl>
	<p style="margin-top:14px">
		<a class="btn" href="?key=<?php echo rawurlencode( $key ); ?>&amp;do=set">ثبت وبهوک</a>
		<a class="btn" style="background:#5b6980" href="?key=<?php echo rawurlencode( $key ); ?>&amp;do=clear">حذف وبهوک</a>
	</p>
	<p style="font-size:.9rem;color:#5b6980;margin:10px 0 0">اگر «آخرین خطا» خالی ماند و پیام‌ها پایین ظاهر شدند، یعنی تلگرام به این سرور می‌رسد.</p>
</div>

<h2>۳. گروه چه می‌گوید؟</h2>
<div class="card">
	<p style="margin:0 0 10px;font-size:.92rem">ربات را به گروه پادکست اضافه کن و ادمین کن، بعد یک پیام در گروه بفرست و این صفحه را تازه کن.</p>
	<?php if ( $lines ) : ?>
		<pre><?php echo htmlspecialchars( implode( "\n", $lines ), ENT_QUOTES, 'UTF-8' ); ?></pre>
		<p style="font-size:.9rem;color:#5b6980;margin:0">دنبال <code>type=supergroup</code> بگرد. اگر نوشت <code>type=group</code> باید به سوپرگروه تبدیل شود. عدد <code>chat_id</code> را برای من بفرست.</p>
	<?php else : ?>
		<p style="margin:0;color:#5b6980">هنوز هیچ پیامی نرسیده.</p>
	<?php endif; ?>
</div>

<h2>وقتی تمام شد</h2>
<div class="note">این صفحه را باز نگذار. بعد از اینکه سه جواب بالا را گرفتیم، <code>index.php</code> با نسخه‌ی کامل ربات عوض می‌شود.</div>
