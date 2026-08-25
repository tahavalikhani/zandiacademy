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

	zandi_forget_intent();
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

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
