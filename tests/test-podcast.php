<?php
/**
 * The podcast subscription: the maths, the states, and the token.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-podcast.php`
 * runs it; a browser gets nothing.
 *
 * Three things here are worth more than the rest.
 *
 * The STACKING RULE, because getting it wrong short-changes somebody who
 * renewed early and nobody finds out until they complain.
 *
 * The GRACE BOUNDARY, because «expired» and «still let in» are two different
 * answers on the same day and the bot acts on the second one.
 *
 * The TOKEN'S SHAPE, because Telegram's deep-link parameter accepts at most 64
 * characters and only A-Z a-z 0-9 _ - . A dot or an equals sign in there does
 * not error — the parameter is silently truncated or dropped, the bot sees no
 * token, and the student is told to connect their account again forever.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

define( 'ZANDI_BOT_SECRET', 'test-secret-not-the-real-one-0000000000' );
define( 'ZANDI_BOT_URL', 'https://bot.example.test' );

require ZANDI_THEME . '/inc/content.php';
require ZANDI_THEME . '/inc/courses.php';
require ZANDI_THEME . '/inc/icons.php';
require ZANDI_THEME . '/inc/template-tags.php';
require ZANDI_THEME . '/inc/auth.php';
require ZANDI_THEME . '/inc/panel.php';
require ZANDI_THEME . '/inc/placement.php';
require ZANDI_THEME . '/inc/podcast.php';

/*
 * THE BOT'S OWN CODE, run here against the site's own tokens. The two live in
 * different codebases on different continents and never speak; if they drift
 * apart the failure is invisible from both sides — the student taps the connect
 * link, nothing happens, and there is nothing to look at. So the contract is
 * proved on every test run instead of being hoped for.
 */
require ZANDI_THEME . '/tools/zandi-bot/token.php';

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;
	if ( $got === $want ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n       got:  " . var_export( $got, true ) . "\n       want: " . var_export( $want, true ) . "\n";
}

function check_true( $label, $got ) {
	global $pass, $fail;
	if ( $got ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n";
}

$day = 86400;

echo "\n— The route —\n";
check( 'the slug is /podcast/', zandi_podcast_slug(), 'podcast' );
$GLOBALS['stub_options']['permalink_structure'] = '/%postname%/';
check( 'pretty permalinks give a tidy URL', zandi_podcast_url(), 'https://example.test/podcast/' );
$GLOBALS['stub_options']['permalink_structure'] = '';
check( 'and «ساده» still resolves, instead of 404ing at the web server', zandi_podcast_url(), 'https://example.test/?zandi_podcast=1' );
$GLOBALS['stub_options']['permalink_structure'] = '/%postname%/';
check_true( 'the page is noindex while it is unlinked', zandi_podcast_noindex() );

echo "\n— The stacking rule —\n";
$t0 = 1757000000;

check( 'nothing bought is no access', zandi_podcast_stack( array() ), 0 );

check(
	'one 30-day plan runs 30 days from payment',
	zandi_podcast_stack( array( array( 'paid_at' => $t0, 'days' => 30 ) ) ),
	$t0 + ( 30 * $day )
);

check(
	'renewing EARLY adds to the remainder rather than burning it',
	zandi_podcast_stack(
		array(
			array( 'paid_at' => $t0, 'days' => 30 ),
			array( 'paid_at' => $t0 + ( 10 * $day ), 'days' => 30 ),
		)
	),
	$t0 + ( 60 * $day )
);

check(
	'renewing LATE runs from the day it was paid, not from the lapsed date',
	zandi_podcast_stack(
		array(
			array( 'paid_at' => $t0, 'days' => 30 ),
			array( 'paid_at' => $t0 + ( 100 * $day ), 'days' => 30 ),
		)
	),
	$t0 + ( 130 * $day )
);

check(
	'renewing on the exact day it lapses leaves no gap',
	zandi_podcast_stack(
		array(
			array( 'paid_at' => $t0, 'days' => 30 ),
			array( 'paid_at' => $t0 + ( 30 * $day ), 'days' => 30 ),
		)
	),
	$t0 + ( 60 * $day )
);

check(
	'a zero-day product is not a subscription and grants nothing',
	zandi_podcast_stack( array( array( 'paid_at' => $t0, 'days' => 0 ) ) ),
	0
);

echo "\n— Where a student stands —\n";
$user = 7;

check( 'no purchase is «none»', zandi_podcast_state( $user ), 'none' );
check_true( 'and «none» is not access', ! zandi_podcast_has_access( $user ) );

update_user_meta( $user, zandi_podcast_expires_meta_key(), time() + ( 5 * $day ) );
check( 'a live date is «active»', zandi_podcast_state( $user ), 'active' );
check( 'and five days left reads as five', zandi_podcast_days_left( $user ), 5 );

update_user_meta( $user, zandi_podcast_expires_meta_key(), time() - 3600 );
check( 'an hour past the date is «grace»', zandi_podcast_state( $user ), 'grace' );
check_true( 'and grace still lets you in — this is what the bot acts on', zandi_podcast_has_access( $user ) );
check( 'a day left in grace shows zero days, not a negative one', zandi_podcast_days_left( $user ), 0 );

update_user_meta( $user, zandi_podcast_expires_meta_key(), time() - ( 2 * $day ) );
check( 'past the grace period is «expired»', zandi_podcast_state( $user ), 'expired' );
check_true( 'and expired is not access', ! zandi_podcast_has_access( $user ) );

echo "\n— The manual floor, for the AradBot members —\n";
$old = 11;
update_user_meta( $old, zandi_podcast_manual_meta_key(), time() + ( 20 * $day ) );
check( 'somebody with no order but a hand-entered date is active', zandi_podcast_state( $old ), 'active' );
delete_user_meta( $old, zandi_podcast_manual_meta_key() );

echo "\n— The connect token —\n";
$token = zandi_podcast_bind_token( 42 );

check( 'a good token names the student it was made for', zandi_podcast_read_bind_token( $token ), 42 );
check_true( 'it fits in Telegram\'s 64-character start parameter', strlen( $token ) <= 64 );
check_true( 'and uses only the characters that parameter accepts', (bool) preg_match( '/^[A-Za-z0-9_-]+$/', $token ) );
check( 'a token with one character changed is refused', zandi_podcast_read_bind_token( substr( $token, 0, -1 ) . ( 'a' === substr( $token, -1 ) ? 'b' : 'a' ) ), 0 );
check( 'a token claiming another student is refused', zandi_podcast_read_bind_token( '99' . substr( $token, 2 ) ), 0 );
check( 'nonsense is refused', zandi_podcast_read_bind_token( 'hello' ), 0 );
check( 'an empty token is refused', zandi_podcast_read_bind_token( '' ), 0 );

/*
 * An expired token has to be refused even though its signature is perfect —
 * otherwise a link from a screenshot six months old still opens the group.
 */
$stale = '42-' . ( time() - 60 );
$stale = $stale . '-' . substr( hash_hmac( 'sha256', $stale, ZANDI_BOT_SECRET ), 0, 32 );
check( 'a correctly signed but expired token is refused', zandi_podcast_read_bind_token( $stale ), 0 );

check( 'the connect link is a deep link into the bot', zandi_podcast_connect_url( 42 ), 'https://t.me/bonjourmonjour_bot?start=' . $token );

echo "\n— Prices —\n";
$plans = zandi_podcast_plans();
check( 'three plans, as the owner priced them', count( $plans ), 3 );
check( 'one month is ۵۹۰٬۰۰۰ تومان', zandi_podcast_plan_price( $plans[0] ), 590000 );
check( 'three months is ۹۹۰٬۰۰۰', zandi_podcast_plan_price( $plans[1] ), 990000 );
check( 'six months is ۱٬۹۹۰٬۰۰۰', zandi_podcast_plan_price( $plans[2] ), 1990000 );
check( 'and the days match the labels', array_column( $plans, 'days' ), array( 30, 90, 180 ) );

echo "\n— The page, before the owner has filled anything in —\n";
/*
 * Three sections have to render as nothing rather than as an empty heading.
 * «سرفصل‌ها» above no chapters, or «قسمت‌های رایگان» above no players, reads as
 * a section that failed to load — which is worse than a page that simply does
 * not have that section yet.
 */
check( 'the starting price is the cheapest plan, for the hero line', zandi_podcast_starting_price(), 590000 );
check( 'سرفصل is empty until the owner writes it', zandi_podcast_chapters(), array() );
check( 'three free episode slots are defined', count( zandi_podcast_episodes() ), 3 );
check( 'but none render until a file is actually uploaded', zandi_podcast_available_episodes(), array() );
check( 'and there is no cover until one is supplied', zandi_podcast_cover(), '' );

echo "\n— The site and the bot agree about the connect token —\n";
$fresh = zandi_podcast_bind_token( 314 );

check( 'the bot reads a token the site minted', zandi_bot_read_token( $fresh, ZANDI_BOT_SECRET ), 314 );
check( 'both sides answer identically', zandi_bot_read_token( $fresh, ZANDI_BOT_SECRET ), zandi_podcast_read_bind_token( $fresh ) );
check( 'a different key refuses it — so a leaked bot config is not a leaked site', zandi_bot_read_token( $fresh, 'some-other-secret-entirely-00000000' ), 0 );
check( 'the bot refuses a tampered token too', zandi_bot_read_token( '315' . substr( $fresh, 3 ), ZANDI_BOT_SECRET ), 0 );
check( 'and refuses nonsense without warning about it', zandi_bot_read_token( 'nope', ZANDI_BOT_SECRET ), 0 );

echo "\n— What the site actually tells the bot —\n";
/*
 * The payload is the contract between a server in Iran and a bot in Germany,
 * and it fails silently in both directions if the shape is wrong.
 *
 * It is keyed on the WordPress user id and NOT on the Telegram id, and that is
 * the whole reason the feature works. The site never learns a Telegram id: the
 * student introduces themselves to the BOT by tapping a signed link, so the bot
 * holds that pair, and it cannot tell us because the site refuses requests from
 * datacentre addresses. Keying on the Telegram id meant this returned false for
 * every student who had not connected yet and never ran again when they did —
 * access paid for and silently never granted.
 */
$GLOBALS['stub_http'] = array();
$buyer                = 21;
update_user_meta( $buyer, zandi_podcast_expires_meta_key(), 1800000000 );

check_true( 'a push goes out even though the site has no Telegram id for them', zandi_podcast_push( $buyer ) );
check( 'exactly one request', count( $GLOBALS['stub_http'] ), 1 );

$sent = json_decode( $GLOBALS['stub_http'][0]['args']['body'], true );

check( 'it names the WordPress user', $sent['user_id'], $buyer );
check( 'it carries the expiry', $sent['expires'], 1800000000 );
check( 'it carries the grace period, so the bot cannot disagree about it', $sent['grace'], 86400 );
check_true( 'and it does NOT try to send a Telegram id', ! isset( $sent['telegram_id'] ) );
check_true( 'the body is signed, not merely sent over HTTPS', ! empty( $GLOBALS['stub_http'][0]['args']['headers']['X-Zandi-Signature'] ) );
check_true( 'and it does not block checkout on a server in Germany', false === $GLOBALS['stub_http'][0]['args']['blocking'] );

echo "\n— The panel —\n";
$copy = zandi_podcast_copy();
check_true( 'a student with no subscription is offered the podcast, not silence', '' !== $copy['panel_none_body'] );
check_true( 'and the tab row has a place to land', in_array( '#my-podcast', array_column( zandi_panel_nav(), 'url' ), true ) );

echo "\n— A subscription must be re-purchasable —\n";
/*
 * zandi_woo_block_repurchase() stops anybody buying a product they already own,
 * which is right for a course and fatal for a renewal: the student whose access
 * is about to lapse would find the button gone at the moment they wanted it.
 */
check_true( 'a podcast product stays purchasable even when owned', zandi_podcast_allow_renewal( false, 0 ) === false );

echo "\n";
echo $fail ? "FAILED: $fail\n" : "All $pass checks passed.\n";
exit( $fail ? 1 : 0 );
