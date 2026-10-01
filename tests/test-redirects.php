<?php
/**
 * Where the site sends people, and whether they arrive.
 *
 * The reported symptom: a signed-out visitor picks a course, is told to sign
 * in — correctly — and after signing in lands on the homepage instead of back
 * at the checkout they were three clicks into. Every gated flow on the site
 * funnels through that one step, which is why it looked like «all of the
 * redirects» were broken. They are one redirect, broken once.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-redirects.php`
 * runs it; a browser gets nothing.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

/*
 * Core functions this file needs that the shared stub does not carry — other
 * suites declare their own nocache_headers() and is_front_page(), and PHP will
 * not declare a function twice.
 */
function nocache_headers() {}
function is_front_page() { return ! empty( $GLOBALS['stub_front_page'] ); }
function is_wc_endpoint_url( $endpoint = false ) { return ! empty( $GLOBALS['stub_wc_endpoint'] ) && $GLOBALS['stub_wc_endpoint'] === $endpoint; }

/*
 * The sign-in cookie a browser sends. 'valid' stands for a live session; its
 * absence is a visitor who is not signed in yet.
 */
define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_stub' );
function wp_validate_auth_cookie( $cookie = '', $scheme = '' ) { return 'valid' === $cookie ? 7 : false; }

/* Signing in, recorded rather than done, so a test can see who was signed in. */
function wp_salt( $scheme = 'auth' ) { return 'stub-salt-' . $scheme; }
function wp_set_auth_cookie( $user_id, $remember = false, $secure = '', $token = '' ) { $GLOBALS['stub_auth_cookie'][] = (int) $user_id; }
function wp_set_current_user( $id, $name = '' ) {
	$GLOBALS['stub_current_user'] = (int) $id;
	$GLOBALS['stub_logged_in']    = (bool) $id;

	return isset( $GLOBALS['stub_users'][ $id ] ) ? $GLOBALS['stub_users'][ $id ] : null;
}

/** Enough of WP_Error for the auth partials' error list. */
class WP_Error {
	private $errors = array();
	public function add( $code, $message ) { $this->errors[ $code ][] = $message; }
	public function has_errors() { return ! empty( $this->errors ); }
	public function get_error_messages( $code = '' ) {
		if ( $code ) {
			return isset( $this->errors[ $code ] ) ? $this->errors[ $code ] : array();
		}

		return $this->errors ? array_merge( ...array_values( $this->errors ) ) : array();
	}
	public function get_error_message( $code = '' ) {
		$messages = $this->get_error_messages( $code );

		return $messages ? $messages[0] : '';
	}
}

// content.php for zandi_contact(), which the auth partials read.
require ZANDI_THEME . '/inc/content.php';
require ZANDI_THEME . '/inc/courses.php';
require ZANDI_THEME . '/inc/icons.php';
require ZANDI_THEME . '/inc/template-tags.php';
require ZANDI_THEME . '/inc/auth.php';
require ZANDI_THEME . '/inc/panel.php';
require ZANDI_THEME . '/inc/placement.php';

$stub_user               = new WP_User();
$stub_user->ID           = 7;
$stub_user->user_login   = '09121234567';
$GLOBALS['stub_users'][7] = $stub_user;

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

/** Reads a query parameter back out of a URL the way PHP would on the next request. */
function query_param( $url, $key ) {
	$parts = wp_parse_url( $url );

	if ( empty( $parts['query'] ) ) {
		return null;
	}

	parse_str( $parts['query'], $vars );

	return isset( $vars[ $key ] ) ? $vars[ $key ] : null;
}

/**
 * Puts the request back to a GET of one URL.
 *
 * The query vars are set the way the rewrite rules would have set them by the
 * time template_redirect fires — that is what zandi_account_route() and
 * zandi_is_placement() read, not the path.
 */
function request( $uri, $query = array(), $logged_in = false ) {
	$_GET     = $query;
	$_POST    = array();
	$_REQUEST = $query;
	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_SERVER['REQUEST_URI']    = $uri;

	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$vars = array();

	if ( in_array( $path, zandi_account_routes(), true ) ) {
		$vars['zandi_account'] = $path;
	}

	if ( 'placement' === $path ) {
		$vars['zandi_placement'] = '1';
	}

	$GLOBALS['stub_is_admin']    = false;
	$GLOBALS['stub_logged_in']   = $logged_in;
	$GLOBALS['stub_redirect']    = null;
	$GLOBALS['stub_query_vars']  = $vars;
	$GLOBALS['stub_front_page']  = ( '' === $path );
	$GLOBALS['stub_wc_endpoint'] = '';

	zandi_forget_intent();
}

/**
 * The sign-in request itself, the way Digits makes it.
 *
 * An AJAX POST from the page the form is on, so that page is the referer. Nobody
 * is the current user yet — core sets the cookie in this request but makes
 * nobody current. Cookies are left alone: the browser sends whatever it holds.
 *
 * @param string $form_page URL of the page the form was on.
 * @param array  $posted    Anything the plugin posts along with the code.
 */
function signing_in( $form_page, $posted = array() ) {
	$_GET     = array();
	$_POST    = $posted;
	$_REQUEST = $posted;
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_SERVER['REQUEST_URI']    = '/wp-admin/admin-ajax.php';
	$_SERVER['HTTP_REFERER']   = $form_page;
	unset( $_COOKIE[ LOGGED_IN_COOKIE ] );

	$GLOBALS['stub_logged_in']    = false;
	$GLOBALS['stub_current_user'] = 0;
}

/**
 * The first page view after signing in — wherever Digits sent them.
 *
 * Unlike request() this forgets nothing: the landing recorded at sign-in is the
 * thing under test.
 *
 * @param string $uri     Where they landed.
 * @param int    $user_id Who they are now.
 */
function arrive( $uri, $user_id ) {
	$_GET     = array();
	$_POST    = array();
	$_REQUEST = array();
	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_SERVER['REQUEST_URI']    = $uri;
	unset( $_SERVER['HTTP_REFERER'] );

	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );

	$GLOBALS['stub_is_admin']     = false;
	$GLOBALS['stub_logged_in']    = true;
	$GLOBALS['stub_current_user'] = $user_id;
	$GLOBALS['stub_redirect']     = null;
	$GLOBALS['stub_query_vars']   = in_array( $path, zandi_account_routes(), true ) ? array( 'zandi_account' => $path ) : array();
	$GLOBALS['stub_front_page']   = ( '' === $path );
	$GLOBALS['stub_wc_endpoint']  = '';
}

/** Renders one of the two auth partials for a signed-out visitor. */
function render_auth( $route, $query ) {
	request( '/' . $route . '/', $query );
	$GLOBALS['stub_current_user'] = 0;

	ob_start();
	include ZANDI_THEME . '/template-parts/account/' . $route . '.php';

	return ob_get_clean();
}

echo "\n— A destination has to survive being put in a URL —\n";

/*
 * add_query_arg() does not urlencode. A destination carrying its own query
 * string therefore ends at the first &, and everything after it becomes a
 * parameter of the LOGIN page instead. The placement report is the case in the
 * codebase today: its token is simply lost.
 */
$report = 'https://example.test/placement/?report=1&r=TOKEN123';
$login  = zandi_login_url( $report );

check( 'the login URL carries the whole destination', query_param( $login, 'redirect_to' ), $report );
check_true( 'and the destination is not leaking its own parameters into the login page', null === query_param( $login, 'r' ) );

$checkout = 'https://example.test/checkout/';
check( 'a plain destination survives too', query_param( zandi_login_url( $checkout ), 'redirect_to' ), $checkout );
check( 'the signup URL behaves the same', query_param( zandi_register_url( $report ), 'redirect_to' ), $report );

echo "\n— The return address has to survive the form —\n";

/*
 * THIS IS THE REPORTED BUG. zandi_auth_redirect_target() reads redirect_to and
 * is correct — but it is only ever called by the theme's OWN login and signup
 * handlers, and those never run in production: Digits owns both forms and
 * processes both submissions. Nothing on the live site reads redirect_to at
 * all, so the destination is dropped and the plugin's own setting decides where
 * everyone lands.
 *
 * The fix cannot be a hidden field — the theme does not own that markup. It is
 * the mechanism the placement feature already had to invent for itself: a
 * cookie, remembered on the way in and spent on the way back.
 */
request( '/login/', array( 'redirect_to' => $checkout ) );
zandi_capture_intent();

check( 'arriving at the login form records where they were going', zandi_intent(), $checkout );

request( '/register/', array( 'redirect_to' => $checkout ) );
zandi_capture_intent();
check( 'so does arriving at the signup form', zandi_intent(), $checkout );

// Signed in now, anywhere at all — the first page view takes them back.
request( '/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = $checkout;

check( 'the first page view after signing in returns them', zandi_resume_to(), $checkout );
check_true( 'and the return address is spent, so it cannot fire twice', '' === zandi_intent() );

echo "\n— Nothing may be bounced somewhere it did not ask for —\n";

request( '/login/', array( 'redirect_to' => 'https://evil.example/steal' ) );
zandi_capture_intent();
check( 'an off-site destination is refused', zandi_intent(), '' );

request( '/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = 'https://example.test/login/';
check( 'and it never returns anyone to the login form itself', zandi_resume_to(), '' );

request( '/checkout/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = 'https://example.test/checkout/';
check( 'nor to the page they are already on', zandi_resume_to(), '' );

request( '/placement/', array( 'start' => '1' ), true );
$_COOKIE[ zandi_intent_cookie() ] = 'https://example.test/placement/?report=1';
check( 'nor off a placement state they deliberately chose', zandi_resume_to(), '' );

$_SERVER['REQUEST_METHOD'] = 'POST';
$_COOKIE[ zandi_intent_cookie() ] = $checkout;
request_method_post();
check( 'and never during a form submission', zandi_resume_to(), '' );

function request_method_post() {
	$_SERVER['REQUEST_METHOD'] = 'POST';
}

echo "\n— Someone already signed in still gets where they asked to go —\n";

request( '/login/', array( 'redirect_to' => $checkout ), true );
check( 'following a login link while already signed in honours the destination', zandi_auth_redirect_target(), $checkout );

request( '/login/', array(), true );
check( 'with nothing asked for, a student lands on their panel', zandi_auth_redirect_target(), zandi_panel_url() );

/** Runs zandi_resume_intent() and reports where it would have sent the request. */
function zandi_resume_to() {
	try {
		zandi_resume_intent();
	} catch ( Zandi_Stub_Redirect $e ) {
		return $e->getMessage();
	}

	return '';
}

echo "\n— Reading the address must never spend it —\n";

/*
 * THE BUG THAT SURVIVED THREE ROUNDS OF FIXES, and the reason this section
 * exists at the top of the file.
 *
 * template-parts/account/login.php calls zandi_auth_redirect_target() while the
 * login page is being DRAWN, to fill a hidden field. The getter used to clear
 * the address as a side effect — so the page wiped its own return address on
 * the very request that had recorded it a moment earlier, every single time it
 * was displayed. The student signed in with nothing left and landed on the
 * homepage, which is exactly what was reported.
 *
 * Reading is not honouring. Only the code that actually redirects may spend it.
 */
request( '/login/', array( 'redirect_to' => 'https://example.test/checkout/' ) );
zandi_capture_intent();

$before = zandi_intent();
$field  = zandi_auth_redirect_target();

check( 'the form reads the destination for its hidden field', $field, 'https://example.test/checkout/' );
check( 'and the address is still there afterwards', zandi_intent(), $before );

// Drawing the page twice must not consume it either.
zandi_auth_redirect_target();
zandi_auth_redirect_target();
check( 'however many times it is read', zandi_intent(), 'https://example.test/checkout/' );

// A filter whose answer the plugin may throw away must not consume it.
zandi_login_redirect( '', '', $GLOBALS['stub_users'][7] );
check( 'a login_redirect filter reading it does not spend it', zandi_intent(), 'https://example.test/checkout/' );

// And it still survives to the landing page.
request( '/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = 'https://example.test/checkout/';
check( 'so the journey still completes', zandi_resume_to(), 'https://example.test/checkout/' );

echo "\n— Arriving at the form with nothing in the URL —\n";

/*
 * The case that was still losing people. The theme's own links carry the
 * destination; nothing else does. Digits can send a visitor to the login page
 * itself, and the header's «ورود» is a plain link — both arrive with an empty
 * query string. The referer is the only remaining trace of where they were.
 */
request( '/login/', array() );
$_SERVER['HTTP_REFERER'] = 'https://example.test/checkout/';
zandi_capture_intent();
check( 'the page they came from is used instead', zandi_intent(), 'https://example.test/checkout/' );

request( '/login/', array() );
$_SERVER['HTTP_REFERER'] = 'https://evil.example/checkout/';
zandi_capture_intent();
check( 'but only if it is this site', zandi_intent(), '' );

request( '/login/', array() );
$_SERVER['HTTP_REFERER'] = 'https://example.test/register/';
zandi_capture_intent();
check( 'and never another auth page, which would loop', zandi_intent(), '' );

request( '/login/', array( 'redirect_to' => 'https://example.test/checkout/' ) );
$_SERVER['HTTP_REFERER'] = 'https://example.test/courses/a1/';
zandi_capture_intent();
check( 'an explicit destination still beats the referer', zandi_intent(), 'https://example.test/checkout/' );
unset( $_SERVER['HTTP_REFERER'] );

echo "\n— The address has to survive the sign-in itself —\n";

/*
 * A cookie set before the form and read after it has to live through an AJAX
 * sign-in, a plugin redirect and possibly a cached landing page. The moment
 * there is an account, there is somewhere sturdier to put it.
 */
request( '/login/', array( 'redirect_to' => checkout_url_for_meta() ) );
zandi_capture_intent();
zandi_persist_intent_on_login( '09121234567', $GLOBALS['stub_users'][7] );
check_true( 'signing in copies it onto the account', checkout_url_for_meta() === get_user_meta( 7, zandi_intent_meta_key(), true ) );

// Now lose the cookie entirely, the way a cache or a redirect chain would.
request( '/', array(), true );
$_COOKIE = array();
$GLOBALS['stub_current_user'] = 7;
check( 'and it is still honoured with the cookie gone', zandi_resume_to(), checkout_url_for_meta() );
check_true( 'then cleared from the account too', '' === get_user_meta( 7, zandi_intent_meta_key(), true ) );

function checkout_url_for_meta() {
	return 'https://example.test/checkout/';
}

echo "\n— The whole journey the owner described —\n";

/*
 * Signed out, picks a course, is told to sign in, signs in. Every step below is
 * the real function the site runs, in the order the site runs it. The last line
 * is the bug: before this change it read «https://example.test/», the homepage.
 */
$course_checkout = 'https://example.test/checkout/';

// 1. The enrol button has put the course in the cart and sent them to checkout.
request( '/checkout/', array(), false );

// 2. WooCommerce's checkout is gated, so the theme records where they are.
zandi_remember_intent( $course_checkout );
check( 'standing on the checkout, the site knows where they are', zandi_intent(), $course_checkout );

// 3. They follow «وارد شو», which carries the destination as well.
$gate_link = zandi_login_url( $course_checkout );
check( 'the gate link carries the checkout', query_param( $gate_link, 'redirect_to' ), $course_checkout );

request( '/login/', array( 'redirect_to' => $course_checkout ) );
zandi_capture_intent();
check( 'arriving at the form records it again', zandi_intent(), $course_checkout );

/*
 * 4. Digits renders the form, Digits processes the submission, and Digits
 *    redirects wherever its own settings say — the homepage. Nothing the theme
 *    wrote was consulted. This is the state the site is actually in.
 */
request( '/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = $course_checkout;

// 5. The first page view after signing in takes them back.
check( 'and after signing in they are returned to the checkout', zandi_resume_to(), $course_checkout );
check_true( 'with the address spent', '' === zandi_intent() );

echo "\n— The same journey, for someone who signs UP rather than in —\n";

request( '/register/', array( 'redirect_to' => $course_checkout ) );
zandi_capture_intent();
check( 'the signup form records it too', zandi_intent(), $course_checkout );

request( '/panel/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = $course_checkout;
check( 'and being dropped on the panel does not lose it', zandi_resume_to(), $course_checkout );

echo "\n— And the journeys that are not the checkout —\n";

$report = 'https://example.test/placement/?report=1';
request( '/login/', array( 'redirect_to' => $report ) );
zandi_capture_intent();
request( '/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = $report;
check( 'the placement report', zandi_resume_to(), $report );

/*
 * The panel is an account route AND a perfectly good destination. Lumping it in
 * with the auth forms stranded the very journey the guard exists to protect: ask
 * for /panel/ signed out, get sent to sign in, and end up wherever the plugin
 * dropped you.
 */
$panel = zandi_panel_url();
request( '/login/', array( 'redirect_to' => $panel ) );
zandi_capture_intent();
request( '/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = $panel;
check( 'the panel, which is a destination like any other', zandi_resume_to(), $panel );

foreach ( array( 'login', 'register', 'logout' ) as $route ) {
	request( '/', array(), true );
	$_COOKIE[ zandi_intent_cookie() ] = zandi_account_url( $route );
	check( "but never back to /$route/", zandi_resume_to(), '' );
}

$course = 'https://example.test/courses/a1/';
request( '/login/', array( 'redirect_to' => $course ) );
zandi_capture_intent();
request( '/', array(), true );
$_COOKIE[ zandi_intent_cookie() ] = $course;
check( 'a course page', zandi_resume_to(), $course );

echo "\n— The sign-in pages are never served from a page cache —\n";

/*
 * WHY EVERY CHECK ABOVE PASSED WHILE THE OWNER KEPT LANDING ON THE HOMEPAGE.
 * This file cannot see a page cache, and LiteSpeed was answering /login/ from
 * one. A cached copy runs no PHP, so zandi_capture_intent() recorded nothing
 * for anybody but the first visitor of each URL. nocache_headers() was the only
 * defence and LiteSpeed never reads it — its own flag and the DONOTCACHEPAGE
 * constant are the two things that stop it (src/control.cls.php, 7.9.1).
 */
$GLOBALS['stub_did'] = array();
request( '/login/', array( 'redirect_to' => $checkout ) );
zandi_account_guard();
check_true( 'the login page sets DONOTCACHEPAGE, which LiteSpeed obeys', defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE );
check_true( 'and says so through LiteSpeed\'s own API', in_array( 'litespeed_control_set_nocache', $GLOBALS['stub_did'], true ) );

$GLOBALS['stub_did'] = array();
request( '/register/', array() );
zandi_account_guard();
check_true( 'so does the signup page', in_array( 'litespeed_control_set_nocache', $GLOBALS['stub_did'], true ) );

$GLOBALS['stub_did'] = array();
request( '/panel/', array() );
try {
	zandi_account_guard();
} catch ( Zandi_Stub_Redirect $zandi_ignored ) {
	unset( $zandi_ignored );
}
check_true( 'and the panel, before it sends a signed-out visitor to sign in', in_array( 'litespeed_control_set_nocache', $GLOBALS['stub_did'], true ) );

echo "\n— The homepage is never somewhere to send anybody back to —\n";

/*
 * The owner's words were «برمیگردن صفحه اول» — they go back to the first page.
 * One way that happened was the theme's alone: somebody who pressed the header's
 * «ثبت نام» on the homepage had the homepage recorded, from the referer, as
 * where they were going — and was redirected there after signing up.
 */
request( '/register/', array() );
$_SERVER['HTTP_REFERER'] = 'https://example.test/';
zandi_capture_intent();
check( 'pressing «ثبت نام» on the homepage records nothing', zandi_intent(), '' );

request( '/register/', array() );
$_SERVER['HTTP_REFERER'] = 'https://example.test/?utm_source=ig&utm_medium=social';
zandi_capture_intent();
check( 'nor when the homepage was reached from an Instagram link', zandi_intent(), '' );

request( '/register/', array() );
$_SERVER['HTTP_REFERER'] = 'https://example.test/podcast/';
zandi_capture_intent();
check( 'any other page they were reading still is', zandi_intent(), 'https://example.test/podcast/' );
unset( $_SERVER['HTTP_REFERER'] );

request( '/login/', array( 'redirect_to' => 'https://example.test/' ) );
zandi_capture_intent();
check( 'not even when a link asks for the homepage outright', zandi_intent(), '' );

request( '/login/', array( 'redirect_to' => 'https://example.test/' ), true );
check( 'so a student already signed in who follows it gets the panel', zandi_auth_redirect_target(), zandi_panel_url() );

check_true( 'wp-admin is no destination for a student', ! zandi_is_destination( admin_url() ) );
check_true( 'nor wp-login.php', ! zandi_is_destination( 'https://example.test/wp-login.php?redirect_to=x' ) );
check_true( 'nor Digits\' own sign-in page', ! zandi_is_destination( 'https://example.test/?login=true&type=register' ) );
check_true( 'nor an auth page under «ساده» permalinks', ! zandi_is_destination( 'https://example.test/?zandi_account=login' ) );
check_true( 'while an ordinary page under «ساده» permalinks is one', zandi_is_destination( 'https://example.test/?page_id=8' ) );

echo "\n— Signed in with nowhere to go: the panel, never the homepage —\n";

/*
 * Digits signs people in over AJAX and then sends them wherever its settings
 * say — with the redirect fields blank, the homepage. The theme only ever acted
 * when it held a destination, so a student with none stayed where Digits put
 * them. Every student sign-in now leaves a landing on the account.
 */
$student = $GLOBALS['stub_users'][7];

// On /register/, having pressed «ثبت نام» on the homepage.
request( '/register/', array() );
$_SERVER['HTTP_REFERER'] = 'https://example.test/';
zandi_capture_intent();
zandi_forget_intent( 7 );

// Digits' sign-up, posted from that page.
signing_in( 'https://example.test/register/' );
zandi_persist_intent_on_register( 7 );
check_true( 'the sign-up leaves a landing on the account', zandi_signed_in_at( 7 ) > 0 );

// Digits drops them on the homepage.
arrive( '/', 7 );
check( 'and the homepage sends them on to their panel', zandi_resume_to(), zandi_panel_url() );
check_true( 'once — the landing is spent', ! zandi_signed_in_at( 7 ) );

arrive( '/', 7 );
check( 'so their next visit to the homepage is just a visit', zandi_resume_to(), '' );

// Landing anywhere but the homepage is left alone.
request( '/register/', array() );
signing_in( 'https://example.test/register/' );
zandi_persist_intent_on_register( 7 );
arrive( '/podcast/', 7 );
check( 'Digits landing them on any other page leaves them there', zandi_resume_to(), '' );
check_true( 'and spends the landing all the same', ! zandi_signed_in_at( 7 ) );

// A landing is good for minutes, not for the rest of the day.
request( '/login/', array() );
signing_in( 'https://example.test/login/' );
zandi_persist_intent_on_login( '09121234567', $student );
update_user_meta( 7, zandi_intent_time_key(), time() - 10 * MINUTE_IN_SECONDS );
arrive( '/', 7 );
check( 'a sign-in from ten minutes ago steers nothing', zandi_resume_to(), '' );
check_true( 'and is cleared rather than left to fire later', '' === get_user_meta( 7, zandi_intent_time_key(), true ) );

// Written by the build before landings were timed: an address and no time.
update_user_meta( 7, zandi_intent_meta_key(), $course_checkout );
arrive( '/courses/a1/', 7 );
check( 'an address left behind by the older build is not followed', zandi_resume_to(), '' );
check_true( 'it is cleared instead', '' === get_user_meta( 7, zandi_intent_meta_key(), true ) );

// The owner adding a student in wp-admin fires user_register too.
zandi_forget_intent( 7 );
request( '/', array(), true );
zandi_persist_intent_on_register( 7 );
check_true( 'an account made by somebody already signed in records no landing', ! zandi_signed_in_at( 7 ) );

echo "\n— The destination survives a login page served from a cache —\n";

/*
 * The case a cookie cannot cover: the cache answered /login/?redirect_to=…, so
 * no PHP ran and nothing was remembered. The sign-in request's referer IS that
 * page, and its query string still names the checkout.
 */
zandi_forget_intent( 7 );
$_COOKIE = array();
signing_in( zandi_login_url( $course_checkout ) );
zandi_persist_intent_on_login( '09121234567', $student );
check( 'the sign-in reads the destination off the page the form was on', get_user_meta( 7, zandi_intent_meta_key(), true ), $course_checkout );

arrive( '/', 7 );
check( 'and the homepage Digits lands them on sends them to the checkout', zandi_resume_to(), $course_checkout );

// For a plugin that never fires wp_login, the cookie core sets is enough.
zandi_forget_intent( 7 );
signing_in( zandi_login_url( $course_checkout ) );
zandi_persist_intent_on_cookie( 'cookie', 0, 0, 7 );
check( 'a sign-in that only sets the auth cookie is recorded too', get_user_meta( 7, zandi_intent_meta_key(), true ), $course_checkout );

// Core re-issues that cookie when a signed-in student changes their password.
zandi_forget_intent( 7 );
arrive( '/panel/', 7 );
$_COOKIE[ LOGGED_IN_COOKIE ] = 'valid';
zandi_persist_intent_on_cookie( 'cookie', 0, 0, 7 );
check_true( 'but a password change is not a sign-in', ! zandi_signed_in_at( 7 ) );
unset( $_COOKIE[ LOGGED_IN_COOKIE ] );

// A browser still holding a session that has run out is signed out all the same.
signing_in( zandi_login_url( $course_checkout ) );
$_COOKIE[ LOGGED_IN_COOKIE ] = 'expired';
zandi_persist_intent_on_cookie( 'cookie', 0, 0, 7 );
check_true( 'while signing in again over an expired session is', zandi_signed_in_at( 7 ) > 0 );
unset( $_COOKIE[ LOGGED_IN_COOKIE ] );

echo "\n— What is happening now beats what a cookie remembers —\n";

zandi_forget_intent( 7 );
$_COOKIE[ zandi_intent_cookie() ] = 'https://example.test/podcast/';
signing_in( zandi_login_url( $course_checkout ) );
zandi_persist_intent_on_login( '09121234567', $student );
arrive( '/', 7 );
check( 'the checkout they are signing in for beats a journey they abandoned', zandi_resume_to(), $course_checkout );
check_true( 'and the stale cookie goes with it', '' === zandi_intent() );

echo "\n— Staff are left alone —\n";

zandi_forget_intent( 7 );
$GLOBALS['stub_user_caps'] = true;
signing_in( zandi_login_url( $course_checkout ) );
zandi_persist_intent_on_login( '09121234567', $student );
check_true( 'the owner or an editor signing in records no landing', ! zandi_signed_in_at( 7 ) );
unset( $GLOBALS['stub_user_caps'] );

echo "\n— Nobody is steered off a payment page —\n";

zandi_forget_intent( 7 );
$_COOKIE[ zandi_intent_cookie() ] = $course_checkout;
arrive( '/checkout/order-received/12/', 7 );
$GLOBALS['stub_wc_endpoint'] = 'order-received';
check( 'the page the bank returns a paying student to is never redirected', zandi_resume_to(), '' );
check_true( 'and the address is spent there', '' === zandi_intent() );

echo "\n— A filter handed the homepage does not hand it back —\n";

/*
 * If Digits runs its choice past login_redirect, that choice arrives as the
 * requested URL. Passing a requested homepage straight back is how this filter
 * used to endorse the bug.
 */
zandi_forget_intent( 7 );
signing_in( zandi_login_url( $course_checkout ) );
zandi_persist_intent_on_login( '09121234567', $student );
check( 'login_redirect offered the homepage answers with the recorded checkout', zandi_login_redirect( home_url( '/' ), home_url( '/' ), $student ), $course_checkout );

zandi_forget_intent( 7 );
signing_in( 'https://example.test/login/' );
zandi_persist_intent_on_login( '09121234567', $student );
check( 'and with nothing recorded, the panel', zandi_login_redirect( home_url( '/' ), home_url( '/' ), $student ), zandi_panel_url() );
check( 'an explicit destination still wins', zandi_login_redirect( home_url( '/' ), $course, $student ), $course );
zandi_forget_intent( 7 );

echo "\n— Moving between the two auth pages keeps the destination —\n";

/*
 * Sent from the checkout to /login/ with no account yet, a student presses
 * «ثبت‌نام کن». That link was a bare /register/, which dropped the checkout for
 * Digits — it reads redirect_to off the page its form is on — and left only the
 * cookie to remember it.
 */
$html = render_auth( 'login', array( 'redirect_to' => $course_checkout ) );
check_true( 'the login page\'s link to signup carries the checkout', false !== strpos( $html, 'register/?redirect_to=' . rawurlencode( $course_checkout ) ) );

$html = render_auth( 'register', array( 'redirect_to' => $course_checkout ) );
check_true( 'and the signup page\'s link back carries it too', false !== strpos( $html, 'login/?redirect_to=' . rawurlencode( $course_checkout ) ) );

$html = render_auth( 'login', array() );
check_true( 'with nowhere to go, the link invents nowhere', false !== strpos( $html, 'href="https://example.test/register/"' ) );

echo "\n— When the page Digits opens is a cached copy —\n";

/*
 * The owner's screenshot of 1 October 2026, straight after signing up from
 * /free-podcast/: the homepage, with the SIGNED-OUT header. That page ran no
 * PHP, so nothing above could fire. The browser has to be told instead, by a
 * cookie it can read and a script that is in every copy of every page.
 */
$free_podcast = 'https://example.test/free-podcast/';

/** Runs the landing handler and reports where it sent the browser. */
function land_from( $from ) {
	$_GET     = array( 'action' => 'zandi_landing', 'from' => $from );
	$_REQUEST = $_GET;
	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_SERVER['REQUEST_URI']    = '/wp-admin/admin-post.php?action=zandi_landing';

	try {
		zandi_handle_landing();
	} catch ( Zandi_Stub_Redirect $e ) {
		return $e->getMessage();
	}

	return '';
}

/** The URL without the one-off parameter, and whether it had one. */
function without_fresh( $url ) {
	$fresh = (bool) preg_match( '/[?&]' . zandi_landing_param() . '=\d+/', $url );
	$url   = preg_replace( '/([?&])' . zandi_landing_param() . '=\d+&?/', '$1', $url );

	return array( rtrim( $url, '?&' ), $fresh );
}

// The sign-up, posted from /register/?redirect_to=…/free-podcast/.
zandi_forget_intent( 7 );
$_COOKIE = array();
$GLOBALS['stub_did'] = array();
signing_in( zandi_register_url( $free_podcast ) );
zandi_persist_intent_on_register( 7 );
check( 'signing up from the gift page records it as the destination', get_user_meta( 7, zandi_intent_meta_key(), true ), $free_podcast );
check_true( 'and leaves the browser a cookie its own script can read', isset( $_COOKIE[ zandi_landing_cookie() ] ) && '1' === $_COOKIE[ zandi_landing_cookie() ] );
check_true( 'and tells LiteSpeed this request is a sign-in', in_array( 'litespeed_vary_ajax_force', $GLOBALS['stub_did'], true ) );
check_true( 'overriding the refusal it adds for REST', ! empty( $GLOBALS['stub_filters']['litespeed_can_change_vary'] ) );

// Digits opens the homepage; LiteSpeed answers from its cache; the script runs.
arrive( '/wp-admin/admin-post.php', 7 );
list( $to, $fresh ) = without_fresh( land_from( '/' ) );
check( 'the landing sends them from the cached homepage to the gift page', $to, $free_podcast );
check_true( 'as a fresh copy no cache has seen', $fresh );
check_true( 'and spends the landing', ! zandi_signed_in_at( 7 ) );
check_true( 'cookie included, so the script cannot fire twice', ! isset( $_COOKIE[ zandi_landing_cookie() ] ) );

// Nothing recorded, dropped on the cached homepage: the panel.
zandi_forget_intent( 7 );
signing_in( 'https://example.test/register/' );
zandi_persist_intent_on_register( 7 );
arrive( '/wp-admin/admin-post.php', 7 );
list( $to ) = without_fresh( land_from( '/?utm_source=ig' ) );
check( 'with nowhere to go, the cached homepage becomes the panel', $to, zandi_panel_url() );

// Nothing recorded, dropped on some other cached page: that page, fresh.
zandi_forget_intent( 7 );
signing_in( 'https://example.test/login/' );
zandi_persist_intent_on_login( '09121234567', $student );
arrive( '/wp-admin/admin-post.php', 7 );
list( $to, $fresh ) = without_fresh( land_from( '/podcast/' ) );
check( 'any other page they were dropped on is reloaded, signed in', $to, 'https://example.test/podcast/' );
check_true( 'again as a fresh copy', $fresh );

// The page address comes from the script. Nothing else is accepted.
zandi_forget_intent( 7 );
arrive( '/wp-admin/admin-post.php', 7 );
list( $to ) = without_fresh( land_from( '//evil.example/steal' ) );
check( 'a protocol-relative address is refused', $to, zandi_panel_url() );
list( $to ) = without_fresh( land_from( 'https://evil.example/steal' ) );
check( 'and so is a full one', $to, zandi_panel_url() );
list( $to ) = without_fresh( land_from( '/\\evil.example/steal' ) );
check( 'and a backslash pretending to be a host', $to, zandi_panel_url() );

echo "\n— When the sign-up leaves nobody signed in —\n";

/*
 * The owner's second screenshot, 1 October 2026: straight after signing up from
 * /free-podcast/, the sign-in page — this handler finding no session. Digits had
 * made the account and left the browser signed out. The browser that made the
 * account holds a one-time pass; the hop uses it.
 */
zandi_forget_intent( 7 );
$GLOBALS['stub_auth_cookie'] = array();
signing_in( zandi_register_url( $free_podcast ) );
zandi_persist_intent_on_register( 7 );
check( 'a sign-up leaves the browser a pass to that account', zandi_signup_pass_user(), 7 );

$GLOBALS['stub_logged_in']    = false;
$GLOBALS['stub_current_user'] = 0;
list( $to, $fresh ) = without_fresh( land_from( '/' ) );
check( 'signed out after the sign-up, the hop signs them in and they reach the gift page', $to, $free_podcast );
check_true( 'as a fresh copy', $fresh );
check_true( 'really signed in, and as that account', array( 7 ) === $GLOBALS['stub_auth_cookie'] && 7 === get_current_user_id() );
check_true( 'the pass is spent: it signs nobody in twice', 0 === zandi_signup_pass_user() && '' === get_user_meta( 7, zandi_signup_key_meta(), true ) );

// A pass is only as good as its signature.
zandi_forget_intent( 7 );
signing_in( zandi_register_url( $free_podcast ) );
zandi_persist_intent_on_register( 7 );
$zandi_real = $_COOKIE[ zandi_signup_cookie() ];
list( $zandi_uid, $zandi_exp, $zandi_mac ) = explode( '.', $zandi_real );
$_COOKIE[ zandi_signup_cookie() ] = '8.' . $zandi_exp . '.' . $zandi_mac;
check( 'a pass edited to name another account opens nothing', zandi_signup_pass_user(), 0 );
$_COOKIE[ zandi_signup_cookie() ] = $zandi_uid . '.' . ( (int) $zandi_exp + 60 ) . '.' . $zandi_mac;
check( 'nor one with its expiry pushed back', zandi_signup_pass_user(), 0 );
$zandi_old = time() - 1;
$_COOKIE[ zandi_signup_cookie() ] = $zandi_uid . '.' . $zandi_old . '.' . zandi_signup_mac( 7, $zandi_old, get_user_meta( 7, zandi_signup_key_meta(), true ) );
check( 'nor a genuine one that has run out', zandi_signup_pass_user(), 0 );
$_COOKIE[ zandi_signup_cookie() ] = 'not-a-pass';
check( 'nor anything that is not one', zandi_signup_pass_user(), 0 );
$_COOKIE[ zandi_signup_cookie() ] = $zandi_real;
check( 'while the genuine one still does', zandi_signup_pass_user(), 7 );

// No pass and no session: leave them where they were. Never the sign-in form.
zandi_forget_intent( 7 );
$GLOBALS['stub_logged_in']    = false;
$GLOBALS['stub_current_user'] = 0;
$GLOBALS['stub_auth_cookie']  = array();
$_COOKIE[ zandi_landing_cookie() ] = '1';
check( 'signed out with no pass, back to the page they were on — not /login/', land_from( '/free-podcast/' ), $free_podcast );
check_true( 'nobody is signed in', array() === $GLOBALS['stub_auth_cookie'] );
check_true( 'and the cookie is dropped, so nothing loops', ! isset( $_COOKIE[ zandi_landing_cookie() ] ) );
$_COOKIE[ zandi_landing_cookie() ] = '1';
check( 'with no page to go back to, the homepage', land_from( '' ), home_url( '/' ) );

// A pass that can never work is dropped, not carried around until it expires.
$_COOKIE[ zandi_landing_cookie() ] = '1';
$_COOKIE[ zandi_signup_cookie() ]  = '7.' . ( time() + 200 ) . '.' . str_repeat( 'ab', 32 );
check( 'a forged pass signs nobody in at the hop', land_from( '/free-podcast/' ), $free_podcast );
check_true( 'and is dropped there', array() === $GLOBALS['stub_auth_cookie'] && ! isset( $_COOKIE[ zandi_signup_cookie() ] ) );
$_COOKIE[ zandi_signup_cookie() ] = 'not-a-pass';
zandi_redeem_signup_on_request();
check_true( 'or on the first page that runs PHP', ! is_user_logged_in() && array() === $GLOBALS['stub_auth_cookie'] && ! isset( $_COOKIE[ zandi_signup_cookie() ] ) );

// The page Digits opens ran PHP: the pass is used before anything else decides.
zandi_forget_intent( 7 );
$GLOBALS['stub_auth_cookie'] = array();
signing_in( zandi_register_url( $free_podcast ) );
zandi_persist_intent_on_register( 7 );
arrive( '/', 7 );
$GLOBALS['stub_logged_in']    = false;
$GLOBALS['stub_current_user'] = 0;
$GLOBALS['stub_did']          = array();
zandi_redeem_signup_on_request();
check_true( 'a signed-out page view holding a pass is signed in', array( 7 ) === $GLOBALS['stub_auth_cookie'] && is_user_logged_in() );
check_true( 'and that page is kept out of the cache, now that it is personal', in_array( 'litespeed_control_set_nocache', $GLOBALS['stub_did'], true ) );
check( 'then sent where the sign-up was going', zandi_resume_to(), $free_podcast );

// Somebody Digits DID sign in is never offered the pass.
zandi_forget_intent( 7 );
$GLOBALS['stub_auth_cookie'] = array();
signing_in( zandi_register_url( $free_podcast ) );
zandi_persist_intent_on_register( 7 );
arrive( '/', 7 );
zandi_redeem_signup_on_request();
check_true( 'a visitor already signed in is not signed in again', array() === $GLOBALS['stub_auth_cookie'] );
zandi_resume_to();
check_true( 'and landing spends the pass anyway', 0 === zandi_signup_pass_user() );

// Signing out inside the five minutes takes the pass with it.
zandi_forget_intent( 7 );
signing_in( zandi_register_url( $free_podcast ) );
zandi_persist_intent_on_register( 7 );
zandi_forget_landing_on_logout( 7 );
check_true( 'signing out drops the pass', 0 === zandi_signup_pass_user() );

// Never for staff, never for an account the owner adds.
zandi_forget_intent( 7 );
$GLOBALS['stub_user_caps'] = true;
signing_in( zandi_register_url( $free_podcast ) );
zandi_persist_intent_on_register( 7 );
check_true( 'no pass for a staff account', ! isset( $_COOKIE[ zandi_signup_cookie() ] ) );
unset( $GLOBALS['stub_user_caps'] );
zandi_forget_intent( 7 );
request( '/wp-admin/user-new.php', array(), true );
zandi_persist_intent_on_register( 7 );
check_true( 'nor for one made by somebody already signed in', ! isset( $_COOKIE[ zandi_signup_cookie() ] ) );

// When the landing page DID run PHP, the server answers first and clears it.
zandi_forget_intent( 7 );
signing_in( zandi_register_url( $free_podcast ) );
zandi_persist_intent_on_register( 7 );
arrive( '/', 7 );
check( 'an uncached homepage still redirects on the server', zandi_resume_to(), $free_podcast );
check_true( 'and clears the browser\'s cookie with it', ! isset( $_COOKIE[ zandi_landing_cookie() ] ) );

// Staff sign in to wp-admin: no cookie, no detour.
zandi_forget_intent( 7 );
$GLOBALS['stub_user_caps'] = true;
signing_in( 'https://example.test/login/' );
zandi_persist_intent_on_login( 'shima', $student );
check_true( 'the owner signing in gets no landing cookie', ! isset( $_COOKIE[ zandi_landing_cookie() ] ) );
unset( $GLOBALS['stub_user_caps'] );

echo "\n— The script that reads it —\n";

ob_start();
zandi_landing_script();
$script = ob_get_clean();

check_true( 'is a single inline script', 1 === substr_count( $script, '<script' ) );
check_true( 'kept away from LiteSpeed\'s defer, delay and combine', false !== strpos( $script, 'data-no-optimize="1"' ) && false !== strpos( $script, 'data-no-defer="1"' ) && false !== strpos( $script, 'data-no-delay="1"' ) );
check_true( 'goes to the landing handler on admin-post.php', false !== strpos( $script, 'admin-post.php?action=zandi_landing' ) );
check_true( 'looks for the landing cookie by name', false !== strpos( $script, zandi_landing_cookie() . '=1' ) );
check_true( 'never acts on a page that is already the result of a landing', false !== strpos( $script, '!p.test(s)' ) );
check_true( 'and runs again on a page restored by history.back()', false !== strpos( $script, '"pageshow"' ) && false !== strpos( $script, 'e.persisted' ) );
check_true( 'can carry nothing that closes the script tag early', false === strpos( substr( $script, 8 ), '</script' ) || strpos( $script, '</script' ) === strrpos( $script, '</script' ) );

$header = file_get_contents( ZANDI_THEME . '/header.php' );
check_true( 'and every page prints it, in the head, before wp_head()', false !== strpos( $header, 'zandi_landing_script()' ) && strpos( $header, 'zandi_landing_script()' ) < strpos( $header, 'wp_head()' ) );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
