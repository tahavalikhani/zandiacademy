<?php
/**
 * Zandi podcast bot — the door to ◆ Podcast Bonjour Monjour 🇫🇷
 *
 * WHAT THIS DOES
 *
 * Holds the group's only entrance and opens it for people who have paid, by
 * itself, within a second of them asking. Removes them a day after their
 * subscription lapses, reminds them before it does, and tells Shima what it
 * did. Nobody approves anything by hand unless they want to.
 *
 * THE SHAPE, AND WHY IT IS THIS SHAPE
 *
 * Telegram is filtered outbound from Iranian datacentres, so this cannot run on
 * the website's server — it runs in Germany. And the website answers 503 to
 * requests from datacentre addresses, so this cannot call the website either.
 * Traffic flows one way only: the site pushes entitlement here, this bot keeps
 * a local copy in store.php, and every decision is made from that copy.
 *
 * Which means the two halves fail independently. The site down: expired members
 * are still removed on time. This bot down: the shop still sells, and join
 * requests queue in Telegram until it returns.
 *
 * THE JOIN IS SPLIT ACROSS THE TWO SIDES ON PURPOSE
 *
 * The site knows `user_id → expires`. It never learns a Telegram id, because a
 * bot cannot look anybody up by phone number — there is no such API at any
 * price. The student hands theirs to THIS bot by tapping a signed link, so the
 * bot learns `user_id → telegram_id`. Neither side has to ask the other.
 *
 * WHY A LEAKED INVITE LINK IS WORTHLESS
 *
 * The group's link creates a join REQUEST rather than a membership. Tapping it
 * does not let anybody in; it asks, and this bot answers by looking the person
 * up. Post the link publicly and nobody unpaid gets through — unlike a one-time
 * link, which is still a key and works for whoever opens it first.
 *
 * @package Zandi
 */

declare( strict_types = 1 );

define( 'ZANDI_BOT', true );

const ZANDI_BOT_API     = 'https://api.telegram.org/bot';
const ZANDI_BOT_TIMEOUT = 10;
const ZANDI_BOT_LOG     = __DIR__ . '/updates.log.php';

/**
 * The updates Telegram is asked for.
 *
 * `chat_member` is the one people forget: Telegram withholds it unless it is
 * named here, even from an administrator bot, and then nobody is ever told that
 * a member left.
 */
const ZANDI_BOT_UPDATES = array( 'message', 'my_chat_member', 'chat_member', 'chat_join_request', 'callback_query' );

/**
 * Days before expiry that earn a reminder, largest first.
 *
 * Three, not one. A single warning on the last day reaches somebody on a
 * Friday evening who cannot act until Monday, by which time they have been
 * removed and the renewal never happens.
 */
const ZANDI_BOT_REMINDERS = array( 7, 3, 1 );

require __DIR__ . '/store.php';
require __DIR__ . '/token.php';

/* =========================================================================
 * 1. Plumbing
 * ====================================================================== */

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

	foreach ( array( 'token', 'secret', 'setup_key', 'bridge_secret', 'webhook_url' ) as $key ) {
		if ( empty( $config[ $key ] ) || str_contains( (string) $config[ $key ], 'HERE' ) || str_contains( (string) $config[ $key ], 'INVENT' ) ) {
			zandi_bot_stop( sprintf( 'config.php still has the placeholder for "%s".', $key ) );
		}
	}

	foreach ( array( 'secret', 'setup_key', 'bridge_secret' ) as $key ) {
		if ( strlen( (string) $config[ $key ] ) < 20 ) {
			zandi_bot_stop( sprintf( 'The %s in config.php is too short. Use 30 or more random characters.', $key ) );
		}
	}

	/*
	 * Three distinct jobs, three distinct strings. The setup key travels in the
	 * address bar; the other two must never appear in a URL, and reusing one
	 * would drag it there.
	 */
	if ( count( array_unique( array( $config['secret'], $config['setup_key'], $config['bridge_secret'] ) ) ) < 3 ) {
		zandi_bot_stop( 'secret, setup_key and bridge_secret in config.php must be three different strings.' );
	}

	$config['admin_chat_id'] = (int) ( $config['admin_chat_id'] ?? 0 );
	$config['group_chat_id'] = (int) ( $config['group_chat_id'] ?? 0 );
	$config['buy_url']       = (string) ( $config['buy_url'] ?? '' );

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
 * @param string              $method Method name.
 * @param array<string,mixed> $params Parameters.
 * @return array<string,mixed>
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
 * Appends one line to the log.
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
 * One function, so there is exactly one place that decides who is told. HTML
 * mode, because a person's own Telegram name goes into these strings and is
 * attacker-controlled text — zandi_bot_who() escapes it.
 *
 * @param string                           $html     Escaped message body.
 * @param array<int,array<int,mixed>>|null $keyboard Inline keyboard rows.
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

	if ( empty( $sent['ok'] ) ) {
		zandi_bot_log( 'NOTIFY-FAILED  ' . ( $sent['description'] ?? $sent['error'] ?? '?' ) );
	}
}

/**
 * Writes to one student. Silent on failure by design.
 *
 * A bot cannot message somebody who has never opened it, and that is a normal
 * state rather than an error: it simply means they have not connected yet.
 *
 * @param int    $telegram_id Recipient.
 * @param string $text        Plain text.
 * @param string $button_url  Optional button target.
 * @param string $button_text Optional button label.
 * @return bool
 */
function zandi_bot_tell( int $telegram_id, string $text, string $button_url = '', string $button_text = '' ): bool {
	if ( ! $telegram_id ) {
		return false;
	}

	$params = array( 'chat_id' => $telegram_id, 'text' => $text );

	if ( $button_url && $button_text ) {
		$params['reply_markup'] = json_encode(
			array( 'inline_keyboard' => array( array( array( 'text' => $button_text, 'url' => $button_url ) ) ) )
		);
	}

	$sent = zandi_bot_call( 'sendMessage', $params );

	return ! empty( $sent['ok'] );
}

/**
 * A person, rendered for a notification and safe to put in HTML.
 *
 * @param array<string,mixed> $user Telegram User object.
 * @return string
 */
function zandi_bot_who( array $user ): string {
	$name = trim( ( $user['first_name'] ?? '' ) . ' ' . ( $user['last_name'] ?? '' ) );
	$out  = '<b>' . htmlspecialchars( '' !== $name ? $name : 'بی‌نام', ENT_QUOTES, 'UTF-8' ) . '</b>';

	if ( ! empty( $user['username'] ) ) {
		$out .= ' (@' . htmlspecialchars( (string) $user['username'], ENT_QUOTES, 'UTF-8' ) . ')';
	}

	return $out . "\n<code>" . (int) ( $user['id'] ?? 0 ) . '</code>';
}

/* =========================================================================
 * 2. The group's door
 * ====================================================================== */

/**
 * The invite link handed to a paying student.
 *
 * Created once and cached, and it creates a JOIN REQUEST rather than a
 * membership — which is what makes a leaked link worthless. Tapping it does not
 * admit anybody; it asks, and zandi_bot_join_request() answers by looking the
 * person up. A one-time link would still be a key, and works for whoever opens
 * it first rather than for the person it was issued to.
 *
 * `creates_join_request` cannot be combined with `member_limit` — Telegram
 * refuses the call — which is fine, because the limit is the weaker idea.
 *
 * @return string
 */
function zandi_bot_group_link(): string {
	$store = zandi_bot_live_store();

	if ( ! empty( $store['link'] ) ) {
		return (string) $store['link'];
	}

	$config = zandi_bot_config();

	$made = zandi_bot_call(
		'createChatInviteLink',
		array(
			'chat_id'              => $config['group_chat_id'],
			'name'                 => 'zandi-auto',
			'creates_join_request' => 'true',
		)
	);

	if ( empty( $made['result']['invite_link'] ) ) {
		zandi_bot_log( 'LINK-FAILED  ' . ( $made['description'] ?? $made['error'] ?? '?' ) );

		return '';
	}

	$store['link'] = (string) $made['result']['invite_link'];
	zandi_bot_store_save( $store );

	return $store['link'];
}

/**
 * Approves or declines a held join request, and records what happened.
 *
 * @param int    $telegram_id Telegram user.
 * @param bool   $admit       True to approve.
 * @param string $by          'auto' or 'admin'.
 * @return bool
 */
function zandi_bot_settle( int $telegram_id, bool $admit, string $by ): bool {
	$config = zandi_bot_config();

	$result = zandi_bot_call(
		$admit ? 'approveChatJoinRequest' : 'declineChatJoinRequest',
		array( 'chat_id' => $config['group_chat_id'], 'user_id' => $telegram_id )
	);

	zandi_bot_log(
		sprintf(
			'%s  tg=%d  by=%s  %s',
			$admit ? 'APPROVED' : 'DECLINED',
			$telegram_id,
			$by,
			empty( $result['ok'] ) ? 'FAILED: ' . ( $result['description'] ?? $result['error'] ?? '?' ) : 'ok'
		)
	);

	return ! empty( $result['ok'] );
}

/**
 * Removes somebody without banning them.
 *
 * Ban then immediately unban. A plain ban would stop them ever coming back,
 * and the whole point of a subscription is that renewing lets you back in —
 * this is also the reason the group has to be a supergroup, because
 * unbanChatMember does not work in a basic one.
 *
 * @param int $telegram_id Telegram user.
 * @return bool
 */
function zandi_bot_remove( int $telegram_id ): bool {
	$config = zandi_bot_config();

	$banned = zandi_bot_call(
		'banChatMember',
		array( 'chat_id' => $config['group_chat_id'], 'user_id' => $telegram_id )
	);

	zandi_bot_call(
		'unbanChatMember',
		array( 'chat_id' => $config['group_chat_id'], 'user_id' => $telegram_id, 'only_if_banned' => 'true' )
	);

	zandi_bot_log(
		sprintf( 'REMOVED  tg=%d  %s', $telegram_id, empty( $banned['ok'] ) ? 'FAILED: ' . ( $banned['description'] ?? '?' ) : 'ok' )
	);

	return ! empty( $banned['ok'] );
}

/* =========================================================================
 * 3. Updates from Telegram
 * ====================================================================== */

/**
 * A student has tapped their connect link.
 *
 * This is the one moment the two halves of the identity meet. Afterwards the
 * bot can act on every future push from the site without anybody doing anything
 * again.
 *
 * @param array<string,mixed> $message Telegram message.
 * @param string              $token   Start parameter.
 * @return void
 */
function zandi_bot_bind( array $message, string $token ): void {
	$telegram_id = (int) ( $message['from']['id'] ?? 0 );
	$user_id     = zandi_bot_read_token( $token, (string) zandi_bot_config()['bridge_secret'] );

	if ( ! $telegram_id ) {
		return;
	}

	if ( ! $user_id ) {
		zandi_bot_log( sprintf( 'BIND-REFUSED  tg=%d', $telegram_id ) );
		zandi_bot_tell( $telegram_id, "این لینک معتبر نیست یا منقضی شده.\n\nاز پنل کاربریت دوباره روی «اتصال به تلگرام» بزن." );

		return;
	}

	$store = zandi_bot_live_store();
	$key   = (string) $user_id;
	$row   = $store['users'][ $key ] ?? array();

	$row['tg']          = $telegram_id;
	$row['bound_at']    = time();
	$store['users'][$key] = $row;

	zandi_bot_store_save( $store );
	zandi_bot_log( sprintf( 'BOUND  user=%d  tg=%d', $user_id, $telegram_id ) );

	zandi_bot_notify( "🔗 حسابش رو وصل کرد\n\n" . zandi_bot_who( $message['from'] ?? array() ) . "\n\nکاربر سایت: <code>" . $user_id . '</code>' );

	if ( ! zandi_bot_row_live( $row ) ) {
		/*
		 * Connected but not paid. Common and not an error: somebody can connect
		 * before buying, or after lapsing.
		 */
		zandi_bot_tell(
			$telegram_id,
			"حسابت وصل شد ✅\n\nاشتراک فعالی پیدا نکردم. بعد از خرید، خودم درِ گروه رو برات باز می‌کنم.",
			(string) zandi_bot_config()['buy_url'],
			'دیدن اشتراک‌ها'
		);

		return;
	}

	$link = zandi_bot_group_link();

	zandi_bot_tell(
		$telegram_id,
		"حسابت وصل شد ✅\n\nاشتراکت فعاله. با دکمه‌ی زیر بیا داخل گروه — درخواستت رو خودم تایید می‌کنم.",
		$link,
		'ورود به گروه پادکست'
	);
}

/**
 * Somebody has asked to come in.
 *
 * @param array<string,mixed> $request ChatJoinRequest.
 * @return void
 */
function zandi_bot_join_request( array $request ): void {
	$user        = $request['from'] ?? array();
	$telegram_id = (int) ( $user['id'] ?? 0 );

	if ( ! $telegram_id ) {
		return;
	}

	if ( zandi_bot_entitled( $telegram_id ) ) {
		zandi_bot_settle( $telegram_id, true, 'auto' );
		zandi_bot_notify( "✅ خودکار تایید شد\n\n" . zandi_bot_who( $user ) );

		return;
	}

	/*
	 * Not on the list. Declined, and told why — Telegram allows a message to
	 * somebody who has sent a join request even if they have never opened the
	 * bot, through user_chat_id and for five minutes. Silence after tapping
	 * «عضویت» reads as a broken link.
	 *
	 * Shima is told too, with a button to overrule. She should not have to, but
	 * the one case it covers is a real one: an existing member from before the
	 * site sold anything, who has not connected an account yet.
	 */
	zandi_bot_settle( $telegram_id, false, 'auto' );

	$to = (int) ( $request['user_chat_id'] ?? $telegram_id );

	zandi_bot_tell(
		$to,
		"برای ورود به گروه پادکست باید اشتراک فعال داشته باشی.\n\nاگر خریدی و هنوز حسابت رو وصل نکردی، از پنل کاربریت «اتصال به تلگرام» رو بزن.",
		(string) zandi_bot_config()['buy_url'],
		'دیدن اشتراک‌ها'
	);

	zandi_bot_notify(
		"⛔ رد شد (اشتراک فعالی نداشت)\n\n" . zandi_bot_who( $user ) . "\n\nاگر باید راهش بدی:",
		array( array( array( 'text' => '✅ به هر حال راهش بده', 'callback_data' => 'ok:' . $telegram_id ) ) )
	);
}

/**
 * Shima pressed the override button.
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
	 * than assumed. Without this, anybody the notification reached could open
	 * the group.
	 */
	if ( $presser !== $config['admin_chat_id'] ) {
		zandi_bot_call(
			'answerCallbackQuery',
			array( 'callback_query_id' => $query['id'] ?? '', 'text' => 'این دکمه برای تو نیست.', 'show_alert' => true )
		);

		zandi_bot_log( sprintf( 'CALLBACK-REFUSED  from=%d', $presser ) );

		return;
	}

	if ( ! preg_match( '/^(ok|no):(\d+)$/', $data, $m ) ) {
		zandi_bot_call( 'answerCallbackQuery', array( 'callback_query_id' => $query['id'] ?? '' ) );

		return;
	}

	$admit = 'ok' === $m[1];
	$done  = zandi_bot_settle( (int) $m[2], $admit, 'admin' );

	zandi_bot_call(
		'answerCallbackQuery',
		array(
			'callback_query_id' => $query['id'] ?? '',
			'text'              => $done ? ( $admit ? 'تایید شد' : 'رد شد' ) : 'نشد — شاید درخواست منقضی شده',
		)
	);

	/*
	 * Rewrite the message without its buttons. Leaving them would let the same
	 * request be settled twice, and the second press fails with an error that
	 * reads as a bug rather than as a repeat.
	 */
	if ( isset( $query['message']['message_id'] ) ) {
		zandi_bot_call(
			'editMessageText',
			array(
				'chat_id'    => $config['admin_chat_id'],
				'message_id' => (int) $query['message']['message_id'],
				'text'       => ( $query['message']['text'] ?? '' ) . "\n\n— " . ( $done ? ( $admit ? '✅ راه داده شد' : '⛔ رد شد' ) : '⚠️ انجام نشد' ),
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

	$was = (string) ( $change['old_chat_member']['status'] ?? '' );
	$now = (string) ( $change['new_chat_member']['status'] ?? '' );

	if ( $was === $now ) {
		return;
	}

	$inside = array( 'member', 'administrator', 'creator' );
	$user   = $change['new_chat_member']['user'] ?? array();

	if ( ! in_array( $was, $inside, true ) && in_array( $now, $inside, true ) ) {
		zandi_bot_notify( "➕ وارد گروه شد\n\n" . zandi_bot_who( $user ) );

		return;
	}

	if ( in_array( $was, $inside, true ) && ! in_array( $now, $inside, true ) ) {
		zandi_bot_notify( '➖ ' . ( 'kicked' === $now ? 'حذف شد' : 'خودش خارج شد' ) . "\n\n" . zandi_bot_who( $user ) );
	}
}

/**
 * Handles one incoming update.
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
		$status = (string) ( $update['my_chat_member']['new_chat_member']['status'] ?? '?' );

		zandi_bot_log( sprintf( 'SELF  chat=%s  status=%s', $update['my_chat_member']['chat']['id'] ?? '?', $status ) );

		/*
		 * Losing admin is the one failure that leaves the bot running while
		 * every approval quietly fails. Say so loudly.
		 */
		if ( (int) ( $update['my_chat_member']['chat']['id'] ?? 0 ) === zandi_bot_config()['group_chat_id']
			&& ! in_array( $status, array( 'administrator', 'creator' ), true ) ) {
			zandi_bot_notify( "⚠️ ربات دیگه ادمین گروه نیست.\n\nتا ادمین نباشه نمی‌تونه کسی رو اضافه یا حذف کنه." );
		}

		return;
	}

	$message = $update['message'] ?? null;

	if ( ! is_array( $message ) || 'private' !== ( $message['chat']['type'] ?? '' ) ) {
		return;
	}

	$text = trim( (string) ( $message['text'] ?? '' ) );

	if ( ! str_starts_with( $text, '/start' ) ) {
		return;
	}

	$parts = explode( ' ', $text, 2 );
	$token = isset( $parts[1] ) ? trim( $parts[1] ) : '';

	if ( '' !== $token ) {
		zandi_bot_bind( $message, $token );

		return;
	}

	/*
	 * /start with nothing after it. Somebody who found the bot on their own, so
	 * point them at the panel rather than guessing who they are.
	 */
	zandi_bot_tell(
		(int) ( $message['chat']['id'] ?? 0 ),
		"سلام! 👋\n\nاین ربات درِ گروه پادکست Bonjour Monjour رو باز می‌کنه.\n\nاگر اشتراک داری، از پنل کاربریت روی «اتصال به تلگرام» بزن تا بشناسمت.",
		(string) zandi_bot_config()['buy_url'],
		'دیدن اشتراک‌ها'
	);
}

/* =========================================================================
 * 4. The site's push
 * ====================================================================== */

/**
 * Accepts one entitlement update from the website.
 *
 * Signed rather than merely sent over HTTPS, so a stranger who finds this URL
 * cannot grant themselves access. hash_equals() again, for the same reason.
 *
 * Idempotent: the site sends the whole answer rather than a change, so a
 * duplicate delivery is a no-op and a missed one is repaired by the next push
 * or by the nightly full sync.
 *
 * @return never
 */
function zandi_bot_sync() {
	$config = zandi_bot_config();
	$body   = (string) file_get_contents( 'php://input' );
	$given  = (string) ( $_SERVER['HTTP_X_ZANDI_SIGNATURE'] ?? '' );

	if ( '' === $given || ! hash_equals( hash_hmac( 'sha256', $body, (string) $config['bridge_secret'] ), $given ) ) {
		zandi_bot_log( 'SYNC-REFUSED  bad signature' );
		zandi_bot_stop( 'Not found', 404 );
	}

	$data    = json_decode( $body, true );
	$user_id = (int) ( $data['user_id'] ?? 0 );

	if ( ! is_array( $data ) || ! $user_id ) {
		zandi_bot_stop( 'bad payload', 400 );
	}

	$store = zandi_bot_live_store();
	$key   = (string) $user_id;
	$row   = $store['users'][ $key ] ?? array();

	$was             = (int) ( $row['expires'] ?? 0 );
	$row['expires']  = (int) ( $data['expires'] ?? 0 );
	$row['grace']    = (int) ( $data['grace'] ?? 0 );
	$row['synced']   = time();

	/*
	 * A later date means a renewal, so the reminder counter resets and the same
	 * person can be warned again next time round. Without this, somebody who
	 * renewed after a «one day left» message would never be reminded again.
	 */
	if ( $row['expires'] > $was ) {
		$row['reminded'] = 0;
		unset( $row['removed'] );
	}

	$store['users'][ $key ] = $row;
	zandi_bot_store_save( $store );

	zandi_bot_log( sprintf( 'SYNC  user=%d  expires=%d  tg=%d', $user_id, $row['expires'], (int) ( $row['tg'] ?? 0 ) ) );

	/*
	 * A fresh purchase by somebody already connected is the happy path, and it
	 * should not wait for them to think of rejoining.
	 */
	if ( ! empty( $row['tg'] ) && $row['expires'] > $was && zandi_bot_row_live( $row ) ) {
		zandi_bot_tell(
			(int) $row['tg'],
			"اشتراکت ثبت شد ✅\n\nبا دکمه‌ی زیر بیا داخل گروه.",
			zandi_bot_group_link(),
			'ورود به گروه پادکست'
		);
	}

	header( 'Content-Type: application/json' );
	echo json_encode( array( 'ok' => true ) );
	exit;
}

/* =========================================================================
 * 5. The nightly sweep
 * ====================================================================== */

/**
 * Reminds people before they lapse, and removes them a day after.
 *
 * ONLY EVER TOUCHES ROWS IT KNOWS ABOUT, and that restraint is the important
 * part. The group still holds people who joined before the site sold anything —
 * they are not in the store, so they are invisible here and stay where they are.
 * A sweep that removed everybody it could not account for would empty the group
 * the first time it ran.
 *
 * @return array<string,int>
 */
function zandi_bot_sweep(): array {
	$store = zandi_bot_live_store();
	$now   = time();
	$stats = array( 'reminded' => 0, 'removed' => 0, 'checked' => 0 );

	foreach ( $store['users'] as $key => $row ) {
		$telegram_id = (int) ( $row['tg'] ?? 0 );
		$expires     = (int) ( $row['expires'] ?? 0 );

		if ( ! $telegram_id || ! $expires ) {
			continue; // Never connected, or never bought. Nothing to do either way.
		}

		++$stats['checked'];

		$cutoff = $expires + (int) ( $row['grace'] ?? 0 );

		if ( $cutoff <= $now ) {
			if ( empty( $row['removed'] ) ) {
				zandi_bot_remove( $telegram_id );

				zandi_bot_tell(
					$telegram_id,
					"اشتراکت تموم شد و دسترسیت به گروه بسته شد.\n\nهر وقت تمدید کنی، خودم دوباره راهت می‌دم.",
					(string) zandi_bot_config()['buy_url'],
					'تمدید اشتراک'
				);

				$row['removed'] = $now;
				++$stats['removed'];
			}

			$store['users'][ $key ] = $row;

			continue;
		}

		$days_left = (int) ceil( ( $expires - $now ) / 86400 );
		$sent      = (int) ( $row['reminded'] ?? 0 );

		foreach ( ZANDI_BOT_REMINDERS as $threshold ) {
			/*
			 * `reminded` holds the smallest threshold already used. A reminder
			 * goes out only when the day count has crossed a NEW, smaller one,
			 * so a sweep running twice in a day does not send twice.
			 */
			if ( $days_left <= $threshold && ( 0 === $sent || $threshold < $sent ) ) {
				zandi_bot_tell(
					$telegram_id,
					sprintf(
						"یادآوری: %d روز دیگه اشتراک پادکستت تموم می‌شه.\n\nاگه تمدید نکنی دسترسیت به گروه بسته می‌شه.",
						max( 1, $days_left )
					),
					(string) zandi_bot_config()['buy_url'],
					'تمدید اشتراک'
				);

				$row['reminded'] = $threshold;
				++$stats['reminded'];
				break;
			}
		}

		$store['users'][ $key ] = $row;
	}

	zandi_bot_store_save( $store );
	zandi_bot_log( sprintf( 'SWEEP  checked=%d reminded=%d removed=%d', $stats['checked'], $stats['reminded'], $stats['removed'] ) );

	if ( $stats['reminded'] || $stats['removed'] ) {
		zandi_bot_notify(
			sprintf(
				"🧹 جاروی روزانه\n\nیادآوری: %d نفر\nحذف‌شده: %d نفر",
				$stats['reminded'],
				$stats['removed']
			)
		);
	}

	return $stats;
}

/* =========================================================================
 * 6. Routing — four doors, everything else is a 404 that gives nothing away
 * ====================================================================== */

$config = zandi_bot_config();
$key    = (string) ( $_GET['key'] ?? '' );

if ( isset( $_GET['sync'] ) && 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	zandi_bot_sync();
}

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	if ( ! hash_equals( (string) $config['secret'], (string) ( $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '' ) ) ) {
		zandi_bot_stop( 'Not found', 404 );
	}

	$update = json_decode( (string) file_get_contents( 'php://input' ), true );

	if ( is_array( $update ) ) {
		zandi_bot_handle( $update );
	}

	http_response_code( 200 );
	exit;
}

if ( isset( $_GET['cron'] ) ) {
	if ( ! hash_equals( (string) $config['setup_key'], (string) $_GET['cron'] ) ) {
		zandi_bot_stop( 'Not found', 404 );
	}

	$stats = zandi_bot_sweep();

	header( 'Content-Type: text/plain; charset=utf-8' );
	printf( "checked=%d reminded=%d removed=%d\n", $stats['checked'], $stats['reminded'], $stats['removed'] );
	exit;
}

if ( '' === $key || ! hash_equals( (string) $config['setup_key'], $key ) ) {
	zandi_bot_stop( 'Not found', 404 );
}

require __DIR__ . '/setup.php';
