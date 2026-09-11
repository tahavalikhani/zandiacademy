<?php
/**
 * Zandi podcast bot — the door.
 *
 * WHERE THIS IS IN THE PLAN
 *
 * The three questions the bootstrap existed to answer came back on
 * 11 September 2026, all green: the German host reaches Telegram, Telegram
 * reaches the German host, and the group is a SUPERGROUP (-1002167405019),
 * which is what makes remove-then-let-back-in possible at all. The Iranian
 * site cannot reach Telegram — expected, and the reason this file runs in
 * Germany — but it can reach this host, which is the direction that matters:
 * WordPress will push the paid list here rather than being asked for it.
 *
 * So this version stops measuring and starts working. What it does NOT yet
 * have is the paid list, because the WooCommerce side is not built. Until it
 * arrives, every join request is held and offered to Shima with two buttons —
 * which is exactly what AradBot made her do by hand, except now it happens
 * inside a bot she owns, on a group whose door nobody else holds a key to.
 *
 * THE SEAM THAT MATTERS
 *
 * zandi_bot_may_join() is the whole future of this file. Today it answers
 * "ask Shima". When the site can say who has paid, it answers from the list
 * and the same code path approves in under a second without waking anybody.
 * Nothing else has to change: the request arrives the same way, the approval
 * call is the same call, and the notification becomes a receipt instead of a
 * question.
 *
 * @package Zandi
 */

declare( strict_types = 1 );

const ZANDI_BOT_API     = 'https://api.telegram.org/bot';
const ZANDI_BOT_TIMEOUT = 10;
const ZANDI_BOT_LOG     = __DIR__ . '/updates.log.php';

/**
 * The updates Telegram is asked to deliver.
 *
 * Deliberately a short list. `chat_member` is the one people forget and then
 * wonder why nobody is told when a member leaves: Telegram withholds it unless
 * it is named here, even from an administrator bot.
 *
 * @var array<int,string>
 */
const ZANDI_BOT_UPDATES = array( 'message', 'my_chat_member', 'chat_member', 'chat_join_request', 'callback_query' );

/**
 * Loads config.php, or explains precisely what is missing.
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

	foreach ( array( 'token', 'secret', 'setup_key', 'webhook_url' ) as $key ) {
		if ( empty( $config[ $key ] ) || str_contains( (string) $config[ $key ], 'HERE' ) ) {
			zandi_bot_stop( sprintf( 'config.php still has the placeholder for "%s".', $key ) );
		}
	}

	foreach ( array( 'secret', 'setup_key' ) as $key ) {
		if ( strlen( (string) $config[ $key ] ) < 20 ) {
			zandi_bot_stop( sprintf( 'The %s in config.php is too short. Use 30 or more random characters.', $key ) );
		}
	}

	/*
	 * The setup key travels in the address bar and the Telegram secret does not.
	 * Making them the same string would drag the one that must stay private into
	 * browser history, the access log and every screenshot of the window.
	 */
	if ( hash_equals( (string) $config['secret'], (string) $config['setup_key'] ) ) {
		zandi_bot_stop( 'secret and setup_key in config.php must be two different strings.' );
	}

	$config['admin_chat_id'] = (int) ( $config['admin_chat_id'] ?? 0 );
	$config['group_chat_id'] = (int) ( $config['group_chat_id'] ?? 0 );

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
	$ch     = curl_init( ZANDI_BOT_API . $config['token'] . '/' . $method );

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
 * URL executes that tag and returns nothing.
 *
 * @param string $line Text.
 * @return void
 */
function zandi_bot_log( string $line ): void {
	if ( ! file_exists( ZANDI_BOT_LOG ) ) {
		file_put_contents( ZANDI_BOT_LOG, "<?php exit; ?>\n" );
	}

	file_put_contents(
		ZANDI_BOT_LOG,
		sprintf( "%s  %s\n", gmdate( 'Y-m-d H:i:s' ), str_replace( array( "\r", "\n" ), ' ', $line ) ),
		FILE_APPEND | LOCK_EX
	);
}

/**
 * Sends Shima a message, and nobody else.
 *
 * Every notification in this file goes through here, so there is exactly one
 * place that decides who is told. HTML parse mode, because a person's own
 * Telegram name is attacker-controlled text and goes into these strings — see
 * zandi_bot_who(), which escapes it.
 *
 * @param string                   $html     Message body, already escaped.
 * @param array<int,array<int,mixed>>|null $keyboard Inline keyboard rows, or null.
 * @return void
 */
function zandi_bot_notify( string $html, ?array $keyboard = null ): void {
	$config = zandi_bot_config();

	if ( ! $config['admin_chat_id'] ) {
		return;
	}

	$params = array(
		'chat_id'    => $config['admin_chat_id'],
		'text'       => $html,
		'parse_mode' => 'HTML',
	);

	if ( $keyboard ) {
		$params['reply_markup'] = json_encode( array( 'inline_keyboard' => $keyboard ) );
	}

	$sent = zandi_bot_call( 'sendMessage', $params );

	/*
	 * A bot cannot message somebody who has never opened it. If that is why
	 * this failed, the fix is for Shima to press Start once — worth recording
	 * rather than swallowing, because the symptom is simply silence.
	 */
	if ( empty( $sent['ok'] ) ) {
		zandi_bot_log( 'NOTIFY-FAILED  ' . ( $sent['description'] ?? $sent['error'] ?? '?' ) );
	}
}

/**
 * A person, rendered for a notification and safe to put in HTML.
 *
 * Names come from the person, so they can contain angle brackets, and a bare
 * name is useless anyway when two students are both called سارا — the numeric
 * id is what the rest of the system joins on.
 *
 * @param array<string,mixed> $user Telegram User object.
 * @return string
 */
function zandi_bot_who( array $user ): string {
	$name = trim( ( $user['first_name'] ?? '' ) . ' ' . ( $user['last_name'] ?? '' ) );
	$name = '' !== $name ? $name : 'بی‌نام';
	$out  = '<b>' . htmlspecialchars( $name, ENT_QUOTES, 'UTF-8' ) . '</b>';

	if ( ! empty( $user['username'] ) ) {
		$out .= ' (@' . htmlspecialchars( (string) $user['username'], ENT_QUOTES, 'UTF-8' ) . ')';
	}

	return $out . "\n<code>" . (int) ( $user['id'] ?? 0 ) . '</code>';
}

/**
 * Whether this person may be let into the group.
 *
 * THE SEAM. Today there is no paid list — WooCommerce is not wired up yet — so
 * this returns null, meaning "undecided, ask Shima", and the join request is
 * held with two buttons on it.
 *
 * When the site starts pushing its list here, this becomes a lookup returning
 * true or false and the request is settled in well under a second without
 * anybody being woken. Nothing else in this file changes: same update, same
 * approve call, same notification — except it reads as a receipt rather than a
 * question.
 *
 * @param int $user_id Telegram user id.
 * @return bool|null True to admit, false to refuse, null to ask.
 */
function zandi_bot_may_join( int $user_id ): ?bool {
	unset( $user_id );

	return null;
}

/**
 * Approves or declines a held join request, and says so.
 *
 * @param int    $user_id Telegram user id.
 * @param bool   $admit   True to approve.
 * @param string $by      Who decided — 'admin' or 'auto'.
 * @return bool Whether Telegram accepted the decision.
 */
function zandi_bot_settle( int $user_id, bool $admit, string $by ): bool {
	$config = zandi_bot_config();
	$method = $admit ? 'approveChatJoinRequest' : 'declineChatJoinRequest';

	$result = zandi_bot_call(
		$method,
		array(
			'chat_id' => $config['group_chat_id'],
			'user_id' => $user_id,
		)
	);

	zandi_bot_log(
		sprintf(
			'%s  user=%d  by=%s  %s',
			$admit ? 'APPROVED' : 'DECLINED',
			$user_id,
			$by,
			empty( $result['ok'] ) ? 'FAILED: ' . ( $result['description'] ?? $result['error'] ?? '?' ) : 'ok'
		)
	);

	return ! empty( $result['ok'] );
}

/**
 * Somebody has asked to come in.
 *
 * @param array<string,mixed> $request ChatJoinRequest.
 * @return void
 */
function zandi_bot_join_request( array $request ): void {
	$user    = $request['from'] ?? array();
	$user_id = (int) ( $user['id'] ?? 0 );

	zandi_bot_log( sprintf( 'JOIN-REQUEST  user=%d  chat=%s', $user_id, $request['chat']['id'] ?? '?' ) );

	if ( ! $user_id ) {
		return;
	}

	$verdict = zandi_bot_may_join( $user_id );

	if ( true === $verdict || false === $verdict ) {
		zandi_bot_settle( $user_id, $verdict, 'auto' );

		zandi_bot_notify(
			( $verdict ? "✅ خودکار تایید شد\n\n" : "⛔ خودکار رد شد\n\n" ) . zandi_bot_who( $user )
		);

		return;
	}

	/*
	 * Undecided. Hold it and ask. The ids are carried in the button rather than
	 * stored, so a restart or a lost file cannot orphan a pending request — the
	 * message itself is the state.
	 */
	zandi_bot_notify(
		"🔔 درخواست عضویت در گروه پادکست\n\n" . zandi_bot_who( $user ) . "\n\nاجازه بدهم بیاید داخل؟",
		array(
			array(
				array( 'text' => '✅ تایید', 'callback_data' => 'ok:' . $user_id ),
				array( 'text' => '⛔ رد', 'callback_data' => 'no:' . $user_id ),
			),
		)
	);

	/*
	 * Telegram allows the bot to write to somebody who has sent a join request
	 * even if they have never opened it — for five minutes, through user_chat_id.
	 * Worth spending: silence after tapping «عضویت» reads as a broken link.
	 */
	if ( ! empty( $request['user_chat_id'] ) ) {
		zandi_bot_call(
			'sendMessage',
			array(
				'chat_id' => (int) $request['user_chat_id'],
				'text'    => 'درخواستت رسید ✅ تا بررسی بشه چند لحظه صبر کن.',
			)
		);
	}
}

/**
 * Shima pressed one of the two buttons.
 *
 * @param array<string,mixed> $query CallbackQuery.
 * @return void
 */
function zandi_bot_callback( array $query ): void {
	$config  = zandi_bot_config();
	$presser = (int) ( $query['from']['id'] ?? 0 );
	$data    = (string) ( $query['data'] ?? '' );

	/*
	 * A forwarded message keeps its buttons, so the presser is checked rather
	 * than assumed. Without this, anyone who was sent the notification could
	 * open the group's door.
	 */
	if ( $presser !== $config['admin_chat_id'] ) {
		zandi_bot_call(
			'answerCallbackQuery',
			array(
				'callback_query_id' => $query['id'] ?? '',
				'text'              => 'این دکمه برای تو نیست.',
				'show_alert'        => true,
			)
		);

		zandi_bot_log( sprintf( 'CALLBACK-REFUSED  from=%d  data=%s', $presser, $data ) );

		return;
	}

	if ( ! preg_match( '/^(ok|no):(\d+)$/', $data, $m ) ) {
		zandi_bot_call( 'answerCallbackQuery', array( 'callback_query_id' => $query['id'] ?? '' ) );
		return;
	}

	$admit   = 'ok' === $m[1];
	$user_id = (int) $m[2];
	$done    = zandi_bot_settle( $user_id, $admit, 'admin' );

	zandi_bot_call(
		'answerCallbackQuery',
		array(
			'callback_query_id' => $query['id'] ?? '',
			'text'              => $done ? ( $admit ? 'تایید شد' : 'رد شد' ) : 'نشد — لاگ را ببین',
		)
	);

	/*
	 * Rewrite the message without its buttons. Leaving them would let the same
	 * request be settled twice, and the second press fails with an error that
	 * looks like a bug rather than a repeat.
	 */
	if ( isset( $query['message']['message_id'] ) ) {
		$verdict = $done
			? ( $admit ? '✅ تایید شد' : '⛔ رد شد' )
			: '⚠️ انجام نشد — شاید درخواست منقضی شده باشد.';

		zandi_bot_call(
			'editMessageText',
			array(
				'chat_id'    => $config['admin_chat_id'],
				'message_id' => (int) $query['message']['message_id'],
				'text'       => ( $query['message']['text'] ?? '' ) . "\n\n— " . $verdict,
				'parse_mode' => 'HTML',
			)
		);
	}
}

/**
 * A member's status in the group changed.
 *
 * @param array<string,mixed> $change ChatMemberUpdated.
 * @return void
 */
function zandi_bot_member_change( array $change ): void {
	$config = zandi_bot_config();

	if ( (int) ( $change['chat']['id'] ?? 0 ) !== $config['group_chat_id'] ) {
		return;
	}

	$was  = (string) ( $change['old_chat_member']['status'] ?? '' );
	$now  = (string) ( $change['new_chat_member']['status'] ?? '' );
	$user = $change['new_chat_member']['user'] ?? array();

	if ( $was === $now ) {
		return;
	}

	zandi_bot_log( sprintf( 'MEMBER  user=%s  %s -> %s', $user['id'] ?? '?', $was, $now ) );

	$inside = array( 'member', 'administrator', 'creator' );
	$came   = ! in_array( $was, $inside, true ) && in_array( $now, $inside, true );
	$went   = in_array( $was, $inside, true ) && ! in_array( $now, $inside, true );

	if ( $came ) {
		zandi_bot_notify( "➕ وارد گروه شد\n\n" . zandi_bot_who( $user ) );
		return;
	}

	if ( $went ) {
		$why = 'kicked' === $now ? 'حذف شد' : 'خودش خارج شد';
		zandi_bot_notify( '➖ ' . $why . "\n\n" . zandi_bot_who( $user ) );
	}
}

/**
 * Handles one incoming update from Telegram.
 *
 * @param array<string,mixed> $update Decoded update.
 * @return void
 */
function zandi_bot_handle( array $update ): void {
	if ( isset( $update['chat_join_request'] ) ) {
		zandi_bot_join_request( $update['chat_join_request'] );
		return;
	}

	if ( isset( $update['callback_query'] ) ) {
		zandi_bot_callback( $update['callback_query'] );
		return;
	}

	if ( isset( $update['chat_member'] ) ) {
		zandi_bot_member_change( $update['chat_member'] );
		return;
	}

	if ( isset( $update['my_chat_member']['chat'] ) ) {
		$chat   = $update['my_chat_member']['chat'];
		$status = (string) ( $update['my_chat_member']['new_chat_member']['status'] ?? '?' );

		zandi_bot_log(
			sprintf( 'SELF  chat_id=%s  type=%s  status=%s', $chat['id'] ?? '?', $chat['type'] ?? '?', $status )
		);

		/*
		 * Being demoted is the one event that silently breaks everything: the
		 * bot keeps running and every approval starts failing. Say so loudly.
		 */
		if ( ! in_array( $status, array( 'administrator', 'creator' ), true ) ) {
			zandi_bot_notify(
				"⚠️ ربات دیگر ادمین گروه نیست (<code>" . htmlspecialchars( $status, ENT_QUOTES, 'UTF-8' ) . "</code>).\n\n"
				. 'تا وقتی ادمین نباشد نمی‌تواند کسی را اضافه یا حذف کند.'
			);
		}

		return;
	}

	$message = $update['message'] ?? null;

	if ( ! is_array( $message ) ) {
		return;
	}

	$chat = $message['chat'] ?? array();
	$text = (string) ( $message['text'] ?? '' );

	zandi_bot_log(
		sprintf( 'MESSAGE chat_id=%s type=%s from=%s', $chat['id'] ?? '?', $chat['type'] ?? '?', $message['from']['id'] ?? '?' )
	);

	if ( 'private' === ( $chat['type'] ?? '' ) && str_starts_with( $text, '/start' ) ) {
		zandi_bot_call(
			'sendMessage',
			array(
				'chat_id' => $chat['id'],
				'text'    => "سلام! 👋\n\nاین ربات دسترسی گروه پادکست Bonjour Monjour را مدیریت می‌کند.\n\nفعلاً در حال راه‌اندازی است.",
			)
		);
	}
}

/* =========================================================================
 * Routing — three doors, and everything else is a 404 that gives nothing away.
 * ====================================================================== */

$config = zandi_bot_config();
$key    = (string) ( $_GET['key'] ?? '' );

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
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

if ( '' === $key || ! hash_equals( (string) $config['setup_key'], $key ) ) {
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
			'allowed_updates' => json_encode( ZANDI_BOT_UPDATES ),
		)
	);

	$notice = ! empty( $result['ok'] ) ? 'وبهوک ثبت شد.' : 'ثبت وبهوک شکست خورد: ' . ( $result['description'] ?? $result['error'] ?? '?' );
}

if ( 'clear' === $action ) {
	zandi_bot_call( 'deleteWebhook' );
	$notice = 'وبهوک حذف شد.';
}

if ( 'test' === $action ) {
	zandi_bot_notify( "🔔 این یک پیام تست است.\n\nاگر این را می‌بینی، خبررسانی کار می‌کند." );
	$notice = 'پیام تست فرستاده شد. تلگرامت را ببین — اگر نیامد، یک بار ربات را باز کن و Start بزن.';
}

$me    = zandi_bot_call( 'getMe' );
$hook  = zandi_bot_call( 'getWebhookInfo' );
$log   = is_readable( ZANDI_BOT_LOG ) ? (string) file_get_contents( ZANDI_BOT_LOG ) : '';
$lines = array_slice( array_filter( explode( "\n", str_replace( '<?php exit; ?>', '', $log ) ) ), -25 );

header( 'Content-Type: text/html; charset=utf-8' );
?>
<!doctype html>
<html lang="fa" dir="rtl">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>ربات پادکست</title>
<style>
body{font-family:Tahoma,system-ui,sans-serif;max-width:46rem;margin:0 auto;padding:24px 18px 60px;background:#f6f7f9;color:#16202f;line-height:1.7}
h1{font-size:1.4rem;margin:0 0 4px}
h2{font-size:1.05rem;margin:28px 0 8px}
.card{background:#fff;border:1px solid #e1e6ed;border-radius:10px;padding:14px 16px;margin-top:12px}
.ok{color:#1b6e52;font-weight:700}.bad{color:#b3261e;font-weight:700}
code,pre{font-family:ui-monospace,Menlo,monospace;font-size:.82rem;direction:ltr;unicode-bidi:isolate}
pre{background:#eff2f6;border:1px solid #e1e6ed;border-radius:8px;padding:12px;overflow-x:auto;text-align:left}
a.btn{display:inline-block;background:#1b365d;color:#fff;text-decoration:none;padding:8px 16px;border-radius:8px;margin-left:8px;margin-top:6px;font-size:.9rem}
a.btn.grey{background:#5b6980}
.note{background:#fff6e5;border:1px solid #f0dcb4;border-radius:8px;padding:12px 14px;margin-top:12px;font-size:.92rem}
dt{font-weight:700;font-size:.85rem;color:#5b6980;margin-top:8px}
dd{margin:0}
</style>

<h1>ربات پادکست Bonjour Monjour</h1>
<p style="color:#5b6980;margin:0">صفحه‌ی مدیریت. فقط با کلید باز می‌شود.</p>

<?php if ( $notice ) : ?>
	<div class="note"><?php echo htmlspecialchars( $notice, ENT_QUOTES, 'UTF-8' ); ?></div>
<?php endif; ?>

<h2>وضعیت</h2>
<div class="card">
	<dl>
		<dt>ربات</dt>
		<dd><?php echo ! empty( $me['ok'] ) ? '<span class="ok">✅ @' . htmlspecialchars( (string) ( $me['result']['username'] ?? '?' ), ENT_QUOTES, 'UTF-8' ) . '</span>' : '<span class="bad">❌ ' . htmlspecialchars( (string) ( $me['description'] ?? $me['error'] ?? '?' ), ENT_QUOTES, 'UTF-8' ) . '</span>'; ?></dd>
		<dt>وبهوک</dt>
		<dd><code><?php echo htmlspecialchars( (string) ( $hook['result']['url'] ?? '—' ), ENT_QUOTES, 'UTF-8' ); ?></code></dd>
		<dt>در صف مانده / آخرین خطا</dt>
		<dd><code><?php echo (int) ( $hook['result']['pending_update_count'] ?? 0 ); ?></code> / <code><?php echo htmlspecialchars( (string) ( $hook['result']['last_error_message'] ?? '—' ), ENT_QUOTES, 'UTF-8' ); ?></code></dd>
		<dt>خبررسانی به</dt>
		<dd><code><?php echo (int) $config['admin_chat_id']; ?></code></dd>
		<dt>گروه</dt>
		<dd><code><?php echo (int) $config['group_chat_id']; ?></code></dd>
	</dl>
	<p>
		<a class="btn" href="?key=<?php echo rawurlencode( $key ); ?>&amp;do=test">پیام تست برای من بفرست</a>
		<a class="btn grey" href="?key=<?php echo rawurlencode( $key ); ?>&amp;do=set">ثبت دوباره‌ی وبهوک</a>
	</p>
</div>

<h2>چه اتفاقی افتاده</h2>
<div class="card">
	<?php if ( $lines ) : ?>
		<pre><?php echo htmlspecialchars( implode( "\n", $lines ), ENT_QUOTES, 'UTF-8' ); ?></pre>
	<?php else : ?>
		<p style="margin:0;color:#5b6980">هنوز چیزی ثبت نشده.</p>
	<?php endif; ?>
</div>

<div class="note">
	<strong>الان ربات چه می‌کند؟</strong> هر کسی درخواست عضویت بدهد، ربات جلویش را می‌گیرد و برای تو پیام می‌فرستد با دو دکمه‌ی «تایید» و «رد». ورود و خروج اعضا را هم خبر می‌دهد.
	<br><br>
	وقتی فروش پادکست روی سایت راه افتاد، همین مسیر خودکار می‌شود: ربات از سایت می‌پرسد چه کسی پول داده و خودش تایید می‌کند. آن وقت این پیام‌ها به جای سؤال، رسید می‌شوند.
</div>
