<?php
/**
 * The bot's copy of who has paid.
 *
 * WHY THE BOT KEEPS ITS OWN COPY INSTEAD OF ASKING
 *
 * The obvious design is a lookup: a join request arrives, the bot asks the
 * website whether that person has paid. It cannot. zandiacademy.com answers 503
 * to requests from datacentre addresses — measured three times on 11 September
 * 2026 — and this bot runs in one. Traffic only flows the other way: the site
 * reaches out to push, and this file is where those pushes land.
 *
 * That constraint turns out to be a feature. Because the answer is local, the
 * bot keeps removing expired members while the site is unreachable, and the
 * site keeps selling while the bot is down. Neither can take the other out.
 *
 * KEYED ON THE WordPress USER ID
 *
 * The site knows `user_id → expires` and never learns a Telegram id, because a
 * student introduces themselves to the BOT by tapping a signed link. The bot
 * knows `user_id → telegram_id` from that moment. Each side holds one half of
 * the join and neither has to ask the other — which is the only arrangement
 * this network allows.
 *
 * WHY A JSON FILE AND NOT MySQL
 *
 * A few hundred rows, read once per update and written rarely. A database would
 * be a second thing to configure, back up and lose. The file opens with a PHP
 * exit tag so fetching its URL returns nothing, and every write takes an
 * exclusive lock — two join requests arriving together is a normal Tuesday.
 *
 * @package Zandi
 */

defined( 'ZANDI_BOT' ) || exit;

const ZANDI_BOT_STORE = __DIR__ . '/store.json.php';

/**
 * The guard that makes a file in the web root unreadable over HTTP.
 *
 * Shared hosting only guarantees one writable place — the folder itself, inside
 * the document root — so the data would otherwise be fetchable at its own URL.
 * Requesting it executes this line and returns an empty page instead.
 */
const ZANDI_BOT_GUARD = "<?php exit; ?>\n";

/**
 * Reads the store.
 *
 * Returns a usable shape whatever it finds. A truncated or hand-edited file is
 * treated as empty rather than fatal: the cost of an empty store is that nobody
 * is admitted until the next sync, and the cost of a fatal is that the webhook
 * starts returning 500 and Telegram queues every update behind it.
 *
 * @return array{users:array<string,array<string,mixed>>,link:string}
 */
function zandi_bot_store() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$empty = array( 'users' => array(), 'link' => '' );

	if ( ! is_readable( ZANDI_BOT_STORE ) ) {
		$cache = $empty;

		return $cache;
	}

	$raw     = (string) file_get_contents( ZANDI_BOT_STORE );
	$decoded = json_decode( substr( $raw, strlen( ZANDI_BOT_GUARD ) ), true );

	$cache = is_array( $decoded ) && isset( $decoded['users'] ) && is_array( $decoded['users'] )
		? array( 'users' => $decoded['users'], 'link' => (string) ( $decoded['link'] ?? '' ) )
		: $empty;

	return $cache;
}

/**
 * Writes the store back.
 *
 * Whole-file, under an exclusive lock. A partial write here would be a member
 * list nobody could explain, so there is no patching — the same reasoning that
 * makes the site rebuild its mirrors wholesale rather than editing them.
 *
 * @param array<string,mixed> $data Store.
 * @return void
 */
function zandi_bot_store_save( $data ) {
	$GLOBALS['zandi_bot_store_cache'] = $data;

	file_put_contents(
		ZANDI_BOT_STORE,
		ZANDI_BOT_GUARD . wp_bot_json( $data ),
		LOCK_EX
	);

	/*
	 * The static cache inside zandi_bot_store() is already populated for this
	 * request, so it is refreshed through the same door it was filled by.
	 */
	zandi_bot_store_refresh( $data );
}

/**
 * Replaces the in-memory copy after a write.
 *
 * @param array<string,mixed>|null $data Store, or null to clear.
 * @return array<string,mixed>
 */
function zandi_bot_store_refresh( $data = null ) {
	static $held = null;

	if ( null !== $data ) {
		$held = $data;
	}

	return is_array( $held ) ? $held : array( 'users' => array(), 'link' => '' );
}

/**
 * JSON, pretty enough to read over somebody's shoulder in a file manager.
 *
 * @param array<string,mixed> $data Data.
 * @return string
 */
function wp_bot_json( $data ) {
	return (string) json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

/**
 * One student's row, by WordPress user id.
 *
 * @param int $user_id WordPress user.
 * @return array<string,mixed>
 */
function zandi_bot_user( $user_id ) {
	$store = zandi_bot_live_store();
	$key   = (string) (int) $user_id;

	return isset( $store['users'][ $key ] ) ? (array) $store['users'][ $key ] : array();
}

/**
 * One student's row, by Telegram id.
 *
 * A scan rather than a second index. With a few hundred rows it is faster than
 * keeping two structures honest, and a stale index is the kind of bug that
 * admits the wrong person.
 *
 * @param int $telegram_id Telegram user.
 * @return array{user_id:int,row:array<string,mixed>}|array{}
 */
function zandi_bot_by_telegram( $telegram_id ) {
	$telegram_id = (int) $telegram_id;
	$store       = zandi_bot_live_store();

	foreach ( $store['users'] as $user_id => $row ) {
		if ( (int) ( $row['tg'] ?? 0 ) === $telegram_id ) {
			return array( 'user_id' => (int) $user_id, 'row' => (array) $row );
		}
	}

	return array();
}

/**
 * The store as it stands right now, including writes made this request.
 *
 * @return array<string,mixed>
 */
function zandi_bot_live_store() {
	$held = zandi_bot_store_refresh();

	return $held['users'] || $held['link'] ? $held : zandi_bot_store();
}

/**
 * Whether a Telegram account is currently entitled to be in the group.
 *
 * Grace counts as inside, and the grace figure travels with the entitlement
 * rather than being a constant here — so the site and the bot cannot end up
 * disagreeing about what «paid up» means after somebody changes one of them.
 *
 * Somebody the store has never heard of is NOT entitled, and that is the right
 * answer for a join request. It is emphatically not a reason to remove them
 * from the group: see zandi_bot_sweep(), which only ever touches rows it knows.
 *
 * @param int $telegram_id Telegram user.
 * @return bool
 */
function zandi_bot_entitled( $telegram_id ) {
	$found = zandi_bot_by_telegram( $telegram_id );

	if ( ! $found ) {
		return false;
	}

	return zandi_bot_row_live( $found['row'] );
}

/**
 * Whether one row is still inside its paid window.
 *
 * @param array<string,mixed> $row Store row.
 * @return bool
 */
function zandi_bot_row_live( $row ) {
	$expires = (int) ( $row['expires'] ?? 0 );

	if ( ! $expires ) {
		return false;
	}

	return ( $expires + (int) ( $row['grace'] ?? 0 ) ) > time();
}
