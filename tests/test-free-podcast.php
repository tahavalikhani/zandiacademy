<?php
/**
 * /free-podcast/: who receives the gift, what it is worth, and the page.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-free-podcast.php`
 * runs it; a browser gets nothing.
 *
 * The owner's rule, 27 September 2026: sign up FROM THIS PAGE and get the days;
 * sign up anywhere else and get nothing. Every way into /register/ the site has
 * is walked below, the way Digits actually makes the request — an AJAX call
 * whose referer is the page the form was on — because «it works from the
 * button» is the half of this that nobody would forget to check.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

set_error_handler(
	function ( $no, $str, $file, $line ) {
		throw new ErrorException( $str . ' — ' . basename( $file ) . ':' . $line, 0, $no, $file, $line );
	}
);

define( 'ZANDI_BOT_SECRET', 'test-secret-not-the-real-one-0000000000' );
define( 'ZANDI_BOT_URL', 'https://bot.example.test' );

// Core functions the shared stub does not carry.
function nocache_headers() {}
function wp_verify_nonce( $nonce, $action = -1 ) { return 'stub' === $nonce ? 1 : false; }
function get_header() {}
function get_footer() {}

require ZANDI_THEME . '/inc/content.php';
require ZANDI_THEME . '/inc/courses.php';
require ZANDI_THEME . '/inc/icons.php';
require ZANDI_THEME . '/inc/template-tags.php';
require ZANDI_THEME . '/inc/auth.php';
require ZANDI_THEME . '/inc/panel.php';
require ZANDI_THEME . '/inc/placement.php';
require ZANDI_THEME . '/inc/podcast.php';
require ZANDI_THEME . '/inc/free-podcast.php';

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;
	if ( $got === $want ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n       got:  " . var_export( $got, true ) . "\n       want: " . var_export( $want, true ) . "\n";
}

function check_true( $label, $got ) {
	check( $label, (bool) $got, true );
}

const GIFT = 'https://example.test/free-podcast/';

/**
 * Drawing /register/ for a signed-out visitor, the way a browser arrives.
 *
 * Runs zandi_capture_intent() exactly as template_redirect would, so whatever
 * cookie the real page would set is set here.
 *
 * @param string      $uri     The /register/ URL, query string and all.
 * @param string|null $referer The page the visitor was on, or null for none.
 */
function open_register( $uri, $referer = null ) {
	$query = (string) parse_url( $uri, PHP_URL_QUERY );
	parse_str( $query, $vars );

	$_GET     = $vars;
	$_POST    = array();
	$_REQUEST = $vars;

	if ( null === $referer ) {
		unset( $_SERVER['HTTP_REFERER'] );
	} else {
		$_SERVER['HTTP_REFERER'] = $referer;
	}

	$GLOBALS['stub_logged_in']   = false;
	$GLOBALS['stub_query_vars']  = array( 'zandi_account' => 'register' );

	zandi_capture_intent();
}

/**
 * Digits' signup request, from the page the form was on, then user_register.
 *
 * @param string|null $form_page The referer, or null when a policy sends none.
 * @param array       $posted    Anything the plugin posts.
 * @return bool Whether the new account received the gift.
 */
function sign_up( $form_page, $posted = array() ) {
	static $next = 1000;
	$user_id = ++$next;

	$_GET     = array();
	$_POST    = $posted;
	$_REQUEST = $posted;

	if ( null === $form_page ) {
		unset( $_SERVER['HTTP_REFERER'] );
	} else {
		$_SERVER['HTTP_REFERER'] = $form_page;
	}

	$GLOBALS['stub_logged_in']  = false;
	$GLOBALS['stub_query_vars'] = array();

	zandi_free_podcast_on_register( $user_id );

	$GLOBALS['last_user'] = $user_id;

	return null !== zandi_podcast_gift_grant( $user_id );
}

/** A fresh browser: no cookie, nobody signed in. */
function new_visitor() {
	unset( $_COOKIE[ zandi_intent_cookie() ] );
	unset( $_SERVER['HTTP_REFERER'] );
	$GLOBALS['stub_logged_in']  = false;
	$GLOBALS['stub_user_caps']  = false;
	$GLOBALS['stub_http']       = array();
	unset( $GLOBALS['stub_options'][ zandi_free_podcast_option() ] );
}

$register_to_gift = zandi_register_url( GIFT );

echo "\n— The address —\n";
check( 'the page lives at /free-podcast/', zandi_free_podcast_url(), GIFT );
check_true( 'its own URL is recognised', zandi_is_free_podcast_url( GIFT ) );
check_true( 'without the trailing slash too', zandi_is_free_podcast_url( 'https://example.test/free-podcast' ) );
check_true( 'and with the tags an Instagram link carries', zandi_is_free_podcast_url( GIFT . '?utm_source=ig&igsh=abc' ) );
check( 'the podcast page is not it', zandi_is_free_podcast_url( 'https://example.test/podcast/' ), false );
check( 'nor a page whose slug merely starts the same', zandi_is_free_podcast_url( 'https://example.test/free-podcast-old/' ), false );
check( 'nor the same path on somebody else\'s site', zandi_is_free_podcast_url( 'https://evil.example/free-podcast/' ), false );
check( 'the button goes to /register/ with this page encoded as the destination', $register_to_gift, 'https://example.test/register/?redirect_to=' . rawurlencode( GIFT ) );

$GLOBALS['stub_options']['permalink_structure'] = '';
check( 'with «ساده» permalinks it is a query string, not a 404', zandi_free_podcast_url(), 'https://example.test/?zandi_free_podcast=1' );
check_true( 'and that form is recognised as well', zandi_is_free_podcast_url( 'https://example.test/?zandi_free_podcast=1' ) );
unset( $GLOBALS['stub_options']['permalink_structure'] );

echo "\n— Signing up FROM this page: the gift —\n";

new_visitor();
open_register( $register_to_gift, GIFT );
check_true( 'the big button: /register/?redirect_to=…/free-podcast/', sign_up( $register_to_gift ) );

new_visitor();
check_true( 'the same, when /register/ came out of a page cache and set no cookie', sign_up( $register_to_gift ) );

new_visitor();
open_register( 'https://example.test/register/', GIFT );
check_true( 'the header\'s «ثبت نام» pressed while on this page (the referer is remembered)', sign_up( 'https://example.test/register/' ) );

new_visitor();
check_true( 'Digits\' own form or popup on this page itself', sign_up( GIFT ) );

new_visitor();
open_register( $register_to_gift, GIFT );
check_true( 'a no-referrer policy: the cookie from /register/ is the evidence', sign_up( null ) );

new_visitor();
open_register( $register_to_gift, GIFT );
check_true( 'a strict-origin policy that strips the referer to the bare site', sign_up( 'https://example.test/' ) );

new_visitor();
$GLOBALS['stub_options']['permalink_structure'] = '';
$plain_register = zandi_register_url( zandi_free_podcast_url() );
open_register( $plain_register, zandi_free_podcast_url() );
check_true( 'with «ساده» permalinks, end to end', sign_up( $plain_register ) );
unset( $GLOBALS['stub_options']['permalink_structure'] );

echo "\n— Signing up from ANYWHERE ELSE: nothing —\n";

new_visitor();
open_register( 'https://example.test/register/', 'https://example.test/' );
check( 'the header\'s «ثبت نام» on the homepage', sign_up( 'https://example.test/register/' ), false );

new_visitor();
$course_register = zandi_register_url( 'https://example.test/courses/a1/' );
open_register( $course_register, 'https://example.test/courses/a1/' );
check( 'a course page\'s signup', sign_up( $course_register ), false );

new_visitor();
open_register( $register_to_gift, GIFT );
$course_register = zandi_register_url( 'https://example.test/courses/a1/' );
open_register( $course_register, 'https://example.test/courses/a1/' );
check( 'looked at the gift, then signed up from a course page — the live journey wins', sign_up( $course_register ), false );

new_visitor();
open_register( $register_to_gift, GIFT );
check( 'a gift cookie from an abandoned visit, then an account made at the checkout', sign_up( 'https://example.test/checkout/' ), false );

new_visitor();
open_register( $register_to_gift, GIFT );
check( 'the same cookie, then Digits\' popup on a course page', sign_up( 'https://example.test/courses/b1/' ), false );

new_visitor();
$placement_register = zandi_register_url( 'https://example.test/placement/?report=1' );
open_register( $placement_register, 'https://example.test/placement/' );
check( 'the placement test\'s «ساختن حساب»', sign_up( $placement_register ), false );

new_visitor();
check( 'a signup request whose referer is another website', sign_up( 'https://evil.example/free-podcast/' ), false );

new_visitor();
check( 'no referer and no cookie: nothing says they came from here', sign_up( null ), false );

new_visitor();
open_register( $register_to_gift, GIFT );
$GLOBALS['stub_logged_in'] = true;
zandi_free_podcast_on_register( 4242 );
check( 'the owner adding a student under کاربران ← افزودن', zandi_podcast_gift_grant( 4242 ), null );

new_visitor();
open_register( $register_to_gift, GIFT );
$GLOBALS['stub_user_caps'] = true;
check( 'a staff account', sign_up( $register_to_gift ), false );

echo "\n— The owner's switch —\n";

new_visitor();
check_true( 'on when the option has never been saved — live the moment the theme is uploaded', zandi_free_podcast_open() );

$GLOBALS['stub_options'][ zandi_free_podcast_option() ] = '0';
open_register( $register_to_gift, GIFT );
check( 'switched off: a signup from the page gets nothing', sign_up( $register_to_gift ), false );
check( 'and nothing is pushed to the bot', count( $GLOBALS['stub_http'] ), 0 );

$GLOBALS['stub_options'][ zandi_free_podcast_option() ] = '1';
check_true( 'switched back on, it is open again', zandi_free_podcast_open() );

check( 'an unticked box (absent from the POST) is saved as off', zandi_free_podcast_sanitize_open( null ), '0' );
check( 'a ticked box is saved as on', zandi_free_podcast_sanitize_open( '1' ), '1' );
check( 'anything else is off, not «on because non-empty»', zandi_free_podcast_sanitize_open( 'yes' ), '0' );

echo "\n— What the gift is worth —\n";

new_visitor();
open_register( $register_to_gift, GIFT );
$before = time();
sign_up( $register_to_gift );
$user  = $GLOBALS['last_user'];
$grant = zandi_podcast_gift_grant( $user );

check( 'seven days', $grant['days'], 7 );
check_true( 'stamped at the moment of signup', $grant['paid_at'] >= $before && $grant['paid_at'] <= time() );
check( 'access runs exactly seven days from signup', zandi_podcast_compute_expiry( $user ), $grant['paid_at'] + 7 * DAY_IN_SECONDS );
check( 'the expiry mirror is written at once', (int) get_user_meta( $user, zandi_podcast_expires_meta_key(), true ), $grant['paid_at'] + 7 * DAY_IN_SECONDS );
check( 'the student is active straight away', zandi_podcast_state( $user ), 'active' );
check( 'the bot is told, once', count( $GLOBALS['stub_http'] ), 1 );
check_true( 'without making the signup wait on Germany', false === $GLOBALS['stub_http'][0]['args']['blocking'] );

$sent = json_decode( $GLOBALS['stub_http'][0]['args']['body'], true );
check( 'and it is told the right date', $sent['expires'], $grant['paid_at'] + 7 * DAY_IN_SECONDS );

$GLOBALS['stub_http'] = array();
check( 'a second user_register for the same account changes nothing', zandi_free_podcast_grant( $user ), false );
check( 'the record still says the first signup', zandi_podcast_gift_grant( $user ), $grant );
check( 'and nothing more is pushed', count( $GLOBALS['stub_http'] ), 0 );

add_filter( 'zandi_free_podcast_days', function () { return 10; } );
check( 'changing the offer later does not rewrite a gift already given', zandi_podcast_compute_expiry( $user ), $grant['paid_at'] + 7 * DAY_IN_SECONDS );
check( 'though the next signup would get the new length', zandi_free_podcast_days(), 10 );
$GLOBALS['stub_filters']['zandi_free_podcast_days'] = array();

echo "\n— It stacks like a purchase, not like the manual floor —\n";

$t = 1800000000;
check(
	'a 30-day plan bought on day 3 of the gift starts after the gift ends: 37 days',
	zandi_podcast_stack( zandi_podcast_sort_grants( array( array( 'paid_at' => $t + 3 * DAY_IN_SECONDS, 'days' => 30 ), array( 'paid_at' => $t, 'days' => 7 ) ) ) ),
	$t + 37 * DAY_IN_SECONDS
);
check(
	'the order they are listed in does not matter once sorted',
	zandi_podcast_sort_grants( array( array( 'paid_at' => 5, 'days' => 1 ), array( 'paid_at' => 2, 'days' => 1 ) ) )[0]['paid_at'],
	2
);

$floor_user = 3100;
zandi_podcast_record_gift( $floor_user, 7, $t );
update_user_meta( $floor_user, zandi_podcast_manual_meta_key(), $t + 60 * DAY_IN_SECONDS );
check( 'a pre-website member with a later manual date keeps the later date', zandi_podcast_compute_expiry( $floor_user ), $t + 60 * DAY_IN_SECONDS );

check( 'a gift of zero days is never recorded', zandi_podcast_record_gift( 3200, 0 ), false );
check( 'nor for no account', zandi_podcast_record_gift( 0, 7 ), false );

echo "\n— The «اتصال به تلگرام» press —\n";

$link = zandi_podcast_connect_link( $user );
check_true( 'the button goes through this site first', 0 === strpos( $link, 'https://example.test/wp-admin/admin-post.php?action=zandi_podcast_connect' ) );
check_true( 'carrying a nonce', false !== strpos( $link, '_wpnonce=' ) );
check( 'it is not a raw bot link any more', false === strpos( $link, 't.me' ), true );

$GLOBALS['stub_http'] = array();
$target = zandi_podcast_connect_target( $user, 'stub' );
check_true( 'the press ends at the bot\'s deep link, token fresh', 0 === strpos( $target, 'https://t.me/bonjourmonjour_bot?start=' . $user . '-' ) );
check( 'after one push', count( $GLOBALS['stub_http'] ), 1 );
check_true( 'that WAITS for the bot, so it knows before the student arrives', true === $GLOBALS['stub_http'][0]['args']['blocking'] );

$GLOBALS['stub_http'] = array();
check( 'a forged or stale nonce goes back to the panel', zandi_podcast_connect_target( $user, 'forged' ), '' );
check( 'and pushes nothing', count( $GLOBALS['stub_http'] ), 0 );
check( 'nobody signed in goes back to the panel', zandi_podcast_connect_target( 0, 'stub' ), '' );

echo "\n— The page, in each state —\n";

/**
 * Renders the whole page for one visitor.
 *
 * @param int $user_id Signed-in user, 0 for none.
 * @return string
 */
function render_page( $user_id ) {
	$GLOBALS['stub_current_user'] = $user_id;
	$GLOBALS['stub_logged_in']    = (bool) $user_id;

	ob_start();
	include ZANDI_THEME . '/template-free-podcast.php';

	return (string) ob_get_clean();
}

$guest = render_page( 0 );
check( 'signed out, it is the offer', zandi_free_podcast_state( 0 ), 'guest' );
check( 'with the button to /register/ and this page as the destination, twice', substr_count( $guest, 'href="' . $register_to_gift . '"' ), 2 );
check_true( 'the headline carries the day count in Persian digits', false !== strpos( $guest, '۷ روز پادکست فرانسه،' ) );
check_true( 'the hours card, from the podcast\'s own facts', false !== strpos( $guest, '+۱۶ ساعت' ) );
check_true( 'the episodes card', false !== strpos( $guest, '۱۰۰ قسمت' ) && false !== strpos( $guest, 'هر کدوم حدود ۱۵ دقیقه' ) );
check_true( 'the transcript card', false !== strpos( $guest, 'متن کامل' ) );
check( 'four cards', substr_count( $guest, 'class="fp-card"' ), 4 );
check( 'the owner\'s instruction: no mention of a phone number', false === strpos( $guest, 'شماره' ) && false === strpos( $guest, 'موبایل' ), true );
check_true( 'the illustration is decorative, hidden from screen readers', false !== strpos( $guest, 'class="fp-visual" aria-hidden="true"' ) );
check_true( 'the French name is isolated, so it keeps its word order in the Persian line', false !== strpos( $guest, '<span dir="ltr" class="latin-run">Bonjour Monjour</span>' ) );
check( 'no scroll-reveal above the fold', false === strpos( $guest, 'reveal' ), true );

check( 'signed in with a live gift', zandi_free_podcast_state( $user ), 'gifted' );
$gifted = render_page( $user );
check_true( 'it says so', false !== strpos( $gifted, 'هدیه‌ت فعال شد' ) );
check_true( 'and until when', false !== strpos( $gifted, 'فعال تا' ) && false !== strpos( $gifted, '<time datetime="' ) );
check_true( 'and offers the connect button, through this site', false !== strpos( $gifted, 'admin-post.php?action=zandi_podcast_connect' ) );
check( 'with no signup button left to press', false === strpos( $gifted, $register_to_gift ), true );

update_user_meta( $user, zandi_podcast_telegram_meta_key(), 555 );
$connected = render_page( $user );
check_true( 'once connected, it says that instead', false !== strpos( $connected, zandi_podcast_copy()['panel_connected'] ) );
check( 'and the connect button is gone', false === strpos( $connected, 'zandi_podcast_connect' ), true );
delete_user_meta( $user, zandi_podcast_telegram_meta_key() );

$old_user = 3300;
zandi_podcast_record_gift( $old_user, 7, time() - 30 * DAY_IN_SECONDS );
check( 'signed in with a gift long spent', zandi_free_podcast_state( $old_user ), 'gift_over' );
$over = render_page( $old_user );
check_true( 'it says the gift is over and points at the plans', false !== strpos( $over, 'هدیه‌ت تموم شده.' ) && false !== strpos( $over, 'href="https://example.test/podcast/"' ) );

$existing = 3400;
check( 'signed in, never had it — an older account', zandi_free_podcast_state( $existing ), 'not_eligible' );
$other = render_page( $existing );
check_true( 'it says the gift is for new accounts', false !== strpos( $other, 'این هدیه مال حساب‌های تازه‌ست.' ) );
check( 'and offers no signup it could not use', false === strpos( $other, $register_to_gift ), true );

echo "\n— The page, when the owner closes the offer —\n";

$GLOBALS['stub_options'][ zandi_free_podcast_option() ] = '0';
$GLOBALS['stub_query_vars'] = array( 'zandi_free_podcast' => '1' );
$GLOBALS['stub_redirect']   = null;
try {
	zandi_free_podcast_request();
} catch ( Throwable $e ) {
	// The real handler exits after redirecting; the stub records instead.
}
check( 'closed: /free-podcast/ sends visitors to the podcast page', $GLOBALS['stub_redirect'], 'https://example.test/podcast/' );
unset( $GLOBALS['stub_options'][ zandi_free_podcast_option() ] );

echo "\n— Wiring that fails silently if it is missing —\n";

$functions = file_get_contents( ZANDI_THEME . '/functions.php' );
check_true( 'a rewrite rule', false !== strpos( $functions, "add_rewrite_rule( zandi_free_podcast_slug() . '/?$', 'index.php?zandi_free_podcast=1', 'top' );" ) );
check_true( 'a public query var', false !== strpos( $functions, "\$vars[] = 'zandi_free_podcast';" ) );
check_true( 'a parse_request entry, so a plugin flushing the rules cannot kill it', false !== strpos( $functions, "\$wp->query_vars['zandi_free_podcast'] = '1';" ) );
check_true( 'a title filter — a virtual route without one has no <title>', false !== strpos( $functions, "add_filter( 'document_title_parts', 'zandi_free_podcast_title' );" ) );
check_true( 'the stylesheet is enqueued by its mtime, never the bare constant', false !== strpos( $functions, "zandi_asset_version( 'assets/css/free-podcast.css' )" ) );
check_true( 'the grant is hooked to user_register', in_array( array( 'user_register', 'zandi_free_podcast_on_register' ), $GLOBALS['stub_actions'], true ) );
check_true( 'the connect press is hooked for signed-in users only', in_array( array( 'admin_post_zandi_podcast_connect', 'zandi_podcast_handle_connect' ), $GLOBALS['stub_actions'], true ) && ! in_array( array( 'admin_post_nopriv_zandi_podcast_connect', 'zandi_podcast_handle_connect' ), $GLOBALS['stub_actions'], true ) );

$css = file_get_contents( ZANDI_THEME . '/assets/css/free-podcast.css' );
check_true( 'the owner\'s mobile rule: no illustration by default…', 1 === preg_match( '/^\.fp-visual \{\n\tdisplay: none;\n\}/m', $css ) );
check_true( '…and it returns only from 640px up', 1 === preg_match( '/@media \(min-width: 640px\) \{\n\t\.fp-visual \{\n\t\tdisplay: block;/', $css ) );
check_true( 'every animation stops under reduced motion', false !== strpos( $css, 'prefers-reduced-motion: reduce' ) );

$style = file_get_contents( ZANDI_THEME . '/style.css' );
check_true( 'the centred band is in style.css\'s text-align restore list', 1 === preg_match( '/\.pt-share,\n\t\.fp-band\n\) :is\(/', $style ) );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
