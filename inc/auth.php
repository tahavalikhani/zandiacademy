<?php
/**
 * Student accounts — registration, login, logout and route guarding.
 *
 * WordPress already owns authentication: users, roles, capabilities, sessions,
 * hashing and cookies. Nothing here reimplements any of that. What this file
 * adds is the part core does not have — Persian, phone-first, front-end pages in
 * the theme's own design instead of the grey `wp-login.php`.
 *
 * IDENTITY IS THE MOBILE NUMBER. The phone is stored as `user_login` and
 * mirrored into `billing_phone` user meta, which is where WooCommerce and every
 * Iranian OTP plugin look for it. Email is optional, because delivery from
 * Iranian IPs to Gmail is unreliable and asking for an address the student will
 * never confirm buys nothing.
 *
 * WHY POSTS ARE HANDLED HERE AND NOT AT admin-post.php. The rest of the theme
 * posts to `admin-post.php` and redirects. An auth form cannot: it has to
 * re-render with the submitted values still in the fields and the errors beside
 * them. So these forms post to themselves and are processed on
 * `template_redirect`, which is exactly how core's own `wp-login.php` works.
 * Nothing lands in a query string, so no phone number reaches a server log.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * The account routes, in the order the rewrite rule accepts them.
 *
 * @return array<int,string>
 */
function zandi_account_routes() {
	return array( 'login', 'register', 'logout', 'panel' );
}

/**
 * The account route the current URL asks for, or an empty string.
 *
 * The whitelist matters: the query var would otherwise accept anything and hand
 * it to the template.
 *
 * The rule itself is registered in `zandi_register_routes()` and the query var
 * in `zandi_query_vars()`, both in functions.php, so every route the theme owns
 * is declared in one place. `zandi_parse_request()` resolves the path directly
 * as well, so these URLs work even when the rewrite rules are missing.
 *
 * @return string
 */
function zandi_account_route() {
	$route = get_query_var( 'zandi_account' );

	return in_array( $route, zandi_account_routes(), true ) ? $route : '';
}

/* =========================================================================
 * URLs
 * ====================================================================== */

/**
 * The canonical URL for an account route.
 *
 * Falls back to `?zandi_account=login` when Settings → پیوندهای یکتا is on
 * «ساده», exactly as zandi_course_url() does — and for the same reason, which
 * is worth stating plainly because it is the failure everyone hits:
 *
 * With plain permalinks WordPress writes no rewrite block to .htaccess and
 * stores no rewrite rules, so a request for /panel/ is answered by Apache or
 * nginx looking for a directory of that name on disk. It returns its own 404 and
 * **PHP never runs** — no theme hook fires, and nothing in this file can
 * intervene. A query-string URL hits index.php, so WordPress boots and the
 * routing below takes over.
 *
 * @param string $route One of zandi_account_routes().
 * @return string
 */
function zandi_account_url( $route ) {
	if ( ! in_array( $route, zandi_account_routes(), true ) ) {
		$route = 'panel';
	}

	if ( ! zandi_pretty_permalinks() ) {
		return home_url( '/?zandi_account=' . $route );
	}

	return home_url( '/' . $route . '/' );
}

/**
 * The student dashboard URL.
 *
 * @return string
 */
function zandi_panel_url() {
	return zandi_account_url( 'panel' );
}

/**
 * The login URL.
 *
 * @param string $redirect_to Optional. Where to send the student afterwards.
 * @return string
 */
function zandi_login_url( $redirect_to = '' ) {
	$url = zandi_account_url( 'login' );

	// add_query_arg() urlencodes the value itself; encoding it here as well
	// would produce %253A and send the student to a nonexistent URL.
	return $redirect_to ? add_query_arg( 'redirect_to', $redirect_to, $url ) : $url;
}

/**
 * The registration URL.
 *
 * Takes a return address, exactly as zandi_login_url() does. It did not for a
 * long time, and that was a real hole rather than an omission: the placement
 * test's «ساختن حساب» button sent a student to /register/ with nowhere to come
 * back to, so they finished signing up and landed on an empty panel with no
 * sign of the result they had just been promised.
 *
 * The redirect is only half the fix. It cannot survive Digits rendering its own
 * form, so the sitting itself is claimed through a cookie — see
 * zandi_placement_claim(). This part is what makes the landing sensible; that
 * part is what makes the promise true.
 *
 * @param string $redirect_to Optional. Where to send the student afterwards.
 * @return string
 */
function zandi_register_url( $redirect_to = '' ) {
	$url = zandi_account_url( 'register' );

	// add_query_arg() urlencodes the value itself; encoding it here as well
	// would produce %253A and send the student to a nonexistent URL.
	return $redirect_to ? add_query_arg( 'redirect_to', $redirect_to, $url ) : $url;
}

/**
 * The logout URL, carrying core's `log-out` nonce.
 *
 * Reusing core's nonce action rather than inventing one means a logout link
 * generated anywhere in WordPress still works against this route.
 *
 * @return string
 */
function zandi_logout_url() {
	return wp_nonce_url( zandi_account_url( 'logout' ), 'log-out' );
}

/* =========================================================================
 * Phone numbers
 * ====================================================================== */

/**
 * Normalises an Iranian mobile number to `09XXXXXXXXX`.
 *
 * Accepts every shape a student might type — Persian or Arabic-Indic digits,
 * spaces and dashes, a `+98` or `0098` prefix, or the bare `9XXXXXXXXX` — and
 * returns one canonical form. Without this, the same person registers twice
 * under two spellings of one number.
 *
 * @param string $raw Whatever was typed.
 * @return string Normalised digits, not necessarily valid — check with
 *                zandi_is_valid_phone().
 */
function zandi_normalize_phone( $raw ) {
	$digits = str_replace(
		array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ),
		array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
		(string) $raw
	);

	// Drops '+', spaces, dashes and parentheses in one pass.
	$digits = preg_replace( '/\D+/', '', $digits );

	if ( 0 === strpos( $digits, '00' ) ) {
		$digits = substr( $digits, 2 );
	}

	if ( 12 === strlen( $digits ) && 0 === strpos( $digits, '98' ) ) {
		$digits = '0' . substr( $digits, 2 );
	}

	if ( 10 === strlen( $digits ) && 0 === strpos( $digits, '9' ) ) {
		$digits = '0' . $digits;
	}

	return $digits;
}

/**
 * Whether a normalised number is a plausible Iranian mobile.
 *
 * Deliberately only checks shape. Whether the number can actually receive a
 * message is not knowable until there is an SMS account — see
 * zandi_identity_verified().
 *
 * @param string $phone Normalised number.
 * @return bool
 */
function zandi_is_valid_phone( $phone ) {
	return 1 === preg_match( '/^09\d{9}$/', $phone );
}

/**
 * A phone number formatted for display, in Persian digits.
 *
 * @param string $phone Normalised number.
 * @return string
 */
function zandi_format_phone( $phone ) {
	return zandi_fa_digits( $phone );
}

/**
 * User-meta keys that may hold a student's mobile number, in priority order.
 *
 * The number is the join key across three systems — the WordPress account, the
 * WooCommerce order and the SpotPlayer licence — but each writes it under its
 * own name, so it has to be looked for in several places:
 *
 *   zandi_phone    written by this theme, and by the sync below
 *   digits_phone   Digits, usually with the country code attached
 *   digt_phone     older Digits builds
 *   billing_phone  WooCommerce at checkout, and what SpotPlayer's plugin reads
 *
 * TODO: confirm which key Digits actually uses on the live install — its
 * documentation does not say, and the two names above are taken from the
 * CVE-2025-4094 proof-of-concept, where they appear as POST fields rather than
 * meta keys. Checking one real signup under کاربران ← ویرایش کاربر settles it,
 * and this list can then be trimmed.
 *
 * @return array<int,string>
 */
function zandi_phone_meta_keys() {
	return (array) apply_filters(
		'zandi_phone_meta_keys',
		array( 'zandi_phone', 'digits_phone', 'digt_phone', 'billing_phone' )
	);
}

/**
 * The stored phone number for a user, normalised.
 *
 * Walks the candidate keys and returns the first value that survives
 * normalisation as a real Iranian mobile — so a country-code-prefixed number
 * from Digits and a bare one from this theme both come back in one shape.
 *
 * @param int $user_id User ID. Defaults to the current user.
 * @return string
 */
function zandi_user_phone( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();

	if ( ! $user_id ) {
		return '';
	}

	foreach ( zandi_phone_meta_keys() as $key ) {
		$phone = zandi_normalize_phone( (string) get_user_meta( $user_id, $key, true ) );

		if ( zandi_is_valid_phone( $phone ) ) {
			return $phone;
		}
	}

	// Accounts this theme created use the number as the login name.
	$user  = get_userdata( $user_id );
	$login = $user ? zandi_normalize_phone( $user->user_login ) : '';

	return zandi_is_valid_phone( $login ) ? $login : '';
}

/**
 * Mirrors whatever number the OTP plugin stored into the keys everything else reads.
 *
 * Digits keeps the number under its own meta key. WooCommerce writes and reads
 * `billing_phone`, and SpotPlayer's WooCommerce plugin keys the licence it
 * generates to that same field. Without this, a student who signs up by SMS
 * would reach checkout with an empty phone field and be issued a licence under
 * a number they never gave.
 *
 * Runs late on registration so the plugin has already written its own meta, and
 * again on login so accounts created before this existed are repaired the next
 * time their owner signs in.
 *
 * @param int $user_id User ID.
 * @return void
 */
function zandi_sync_student_phone( $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return;
	}

	$phone = zandi_user_phone( $user_id );

	if ( ! zandi_is_valid_phone( $phone ) ) {
		return;
	}

	foreach ( array( 'zandi_phone', 'billing_phone' ) as $key ) {
		if ( (string) get_user_meta( $user_id, $key, true ) !== $phone ) {
			update_user_meta( $user_id, $key, $phone );
		}
	}
}
add_action( 'user_register', 'zandi_sync_student_phone', 99 );

/**
 * Runs the phone sync on sign-in.
 *
 * `wp_login` passes the login *name* first, not an ID — and for an account this
 * theme created that name is the phone number itself, which is numeric. Casting
 * it to an int would address a user that does not exist, so the WP_User handed
 * over as the second argument is used instead.
 *
 * @param string  $user_login Login name.
 * @param WP_User $user       The user signing in.
 * @return void
 */
function zandi_sync_student_phone_on_login( $user_login, $user = null ) {
	if ( $user instanceof WP_User ) {
		zandi_sync_student_phone( $user->ID );
	}
}
add_action( 'wp_login', 'zandi_sync_student_phone_on_login', 99, 2 );

/* =========================================================================
 * The OTP provider
 *
 * Iranian students expect to sign in with a code sent by SMS, not a password.
 * Digits (unitedover.com) owns that flow: one form takes a mobile number, sends
 * a code, signs in an existing account and registers a new one — asking only for
 * a full name — without the theme touching credentials at all.
 *
 * PHONE ONLY AT LAUNCH (30 July 2026). Digits can also accept an email in the
 * same form, but turning that on makes it render a *tabbed* form — two inputs
 * behind two tabs — which is not the single field this was asked for. It is also
 * unusable until there is an SMTP account, and a passwordless account reachable
 * only by an email that never arrives is an account nobody can get back into.
 * Enabling it later is one Digits toggle plus a copy change in
 * zandi_login_copy(), which is worded for a phone number today.
 *
 * The theme's own phone + password forms are kept as a FALLBACK, reachable only
 * while no provider is active. That is deliberate. Digits is a paid plugin
 * sitting directly in the authentication path, and its 8.4.6.x line carried
 * CVE-2025-4094 (CVSS 9.8: no rate limit on OTP checks, so every code was
 * brute-forceable). If it is ever deactivated or its licence lapses, /login/
 * must degrade to a working form rather than a blank page.
 * ====================================================================== */

/**
 * Whether an OTP plugin owns the sign-in flow.
 *
 * Digits exposes `df_digits_form()` as its documented way to place the combined
 * login/registration form in a template, so its presence is the signal. The
 * filter lets a different provider — or a staging site — override the answer.
 *
 * @return bool
 */
function zandi_otp_provider_active() {
	return (bool) apply_filters( 'zandi_otp_provider_active', function_exists( 'df_digits_form' ) );
}

/**
 * The markup for one of the two auth forms, from whichever provider is in charge.
 *
 * BOTH ROUTES GO THROUGH HERE. That is the whole point: if /login/ renders the
 * provider's form and /register/ quietly falls back to the theme's own, the site
 * is running two different auth systems on two pages — a student signs up with a
 * password and then cannot sign in, because the login form wants a code. This
 * function is what keeps the pair matched.
 *
 * Resolution order, per route:
 *
 *   1. `zandi_login_shortcode()` / `zandi_register_shortcode()`, if a filter has
 *      set one. The documented escape hatch — it wins so a specific shortcode
 *      can always be forced.
 *   2. Digits' route-specific form, when that function exists.
 *   3. Digits' generic form, for builds that expose only the one entry point.
 *   4. An empty string, meaning the caller should draw the built-in fallback.
 *
 * @param string $route 'login' or 'register'.
 * @return string Markup, or '' when the theme should render its own form.
 */
function zandi_auth_form_markup( $route = 'login' ) {
	$cached = zandi_prepared_auth_form( $route );

	if ( null !== $cached ) {
		return $cached;
	}

	return zandi_render_auth_form( $route );
}

/**
 * Ask the provider for its markup, for real.
 *
 * Split out of zandi_auth_form_markup() so the same call can be made early —
 * see zandi_prepare_auth_form() — without the cache short-circuiting it.
 *
 * @param string $route 'login' or 'register'.
 * @return string Markup, or '' when the theme should render its own form.
 */
function zandi_render_auth_form( $route = 'login' ) {
	$is_register = ( 'register' === $route );

	$shortcode = $is_register ? zandi_register_shortcode() : zandi_login_shortcode();

	if ( '' !== $shortcode ) {
		return do_shortcode( $shortcode );
	}

	// Digits names these per form; older builds may expose only df_digits_form().
	$specific = $is_register ? 'df_digits_form_signup' : 'df_digits_form_login';

	if ( function_exists( $specific ) ) {
		return (string) call_user_func( $specific );
	}

	if ( function_exists( 'df_digits_form' ) ) {
		return (string) df_digits_form();
	}

	return '';
}

/**
 * Render the provider's form before wp_head(), and keep the markup.
 *
 * THIS IS WHAT MAKES DIGITS WORK, and it is not an optimisation.
 *
 * A plugin that renders a form also enqueues the script and stylesheet that
 * form needs, at the moment it is asked for the markup. The theme was asking
 * from inside template-parts/account/login.php — which runs during
 * `template_include`, long after `wp_enqueue_scripts` has fired and `wp_head()`
 * has already printed. Every `wp_enqueue_script()` Digits made landed in a
 * queue nobody would flush again, so the plugin's own CSS and JS never reached
 * the page at all.
 *
 * The symptom is not a missing stylesheet, which would be obvious. It is that
 * Digits builds its flow as a set of step panels — «ورود», «عضویت», «تایید
 * شماره موبایل» — and hides all but the current one *with JavaScript*. With no
 * JavaScript, every panel renders at once, stacked, on one page. That is
 * exactly what /register/ was showing.
 *
 * `template_redirect` fires before `wp_head()`, so rendering here puts the
 * enqueues back in front of the queue they belong to. The markup is stashed and
 * handed to the template later, so the plugin is still only asked once.
 *
 * Priority 20 so zandi_account_guard() — priority 10 — has already had its
 * chance to redirect; there is no point building a form for a request that is
 * about to become a 302.
 *
 * @return void
 */
function zandi_prepare_auth_form() {
	$route = zandi_account_route();

	if ( 'login' !== $route && 'register' !== $route ) {
		return;
	}

	zandi_prepared_auth_form( $route, zandi_render_auth_form( $route ) );
}
add_action( 'template_redirect', 'zandi_prepare_auth_form', 20 );

/**
 * The stash zandi_prepare_auth_form() writes and the template reads.
 *
 * Two modes, on purpose: called with one argument it reads, and returns null
 * when nothing has been prepared — which is what tells zandi_auth_form_markup()
 * to go and render for itself. Called with two it writes. A plain static keeps
 * the read and the write in the same request, which is all this needs.
 *
 * @param string      $route  'login' or 'register'.
 * @param string|null $markup Markup to store, or null to read.
 * @return string|null Stored markup, or null when there is none.
 */
function zandi_prepared_auth_form( $route, $markup = null ) {
	static $store = array();

	if ( null !== $markup ) {
		$store[ $route ] = (string) $markup;
	}

	return isset( $store[ $route ] ) ? $store[ $route ] : null;
}

/**
 * Whether the visitor has proved they hold this number.
 *
 * Only ever consulted on the fallback path. When an OTP provider is active it
 * verifies the code *before* the user row exists, so by the time WordPress has
 * a user to ask about, the answer is already yes and this is never reached.
 *
 * On the fallback path it stays a placeholder returning true — a password is a
 * different trust model, and nothing there has verified the number at all.
 *
 * @param string $phone Normalised number.
 * @return bool
 */
function zandi_identity_verified( $phone ) {
	return (bool) apply_filters( 'zandi_identity_verified', true, $phone );
}

/**
 * A shortcode to render instead of the built-in login form.
 *
 * Digits is detected automatically, so this is only needed to force a specific
 * shortcode or to plug in a different provider:
 *
 *     add_filter( 'zandi_login_shortcode', fn() => '[digits_login_form]' );
 *
 * @return string
 */
function zandi_login_shortcode() {
	return (string) apply_filters( 'zandi_login_shortcode', '' );
}

/**
 * A shortcode to render instead of the built-in registration form.
 *
 * Unused while an OTP provider is active — /register/ redirects to the single
 * form then. Kept for the fallback path.
 *
 * @return string
 */
function zandi_register_shortcode() {
	return (string) apply_filters( 'zandi_register_shortcode', '' );
}

/* =========================================================================
 * Errors
 *
 * Collected during the POST and read back by the template a few milliseconds
 * later in the same request — hence a static rather than a transient.
 * ====================================================================== */

/**
 * The error bag for the current request.
 *
 * @return WP_Error
 */
function zandi_auth_errors() {
	static $errors = null;

	if ( null === $errors ) {
		$errors = new WP_Error();
	}

	return $errors;
}

/**
 * A submitted value, for re-filling a form that failed validation.
 *
 * Safe to call unverified: the handler above has already checked the nonce and
 * refused the submission by the time a template renders, and the value is only
 * ever echoed back into the field the student typed it into.
 *
 * @param string $key Field name.
 * @return string
 */
function zandi_posted( $key ) {
	return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Display-only re-fill; the handler verifies the nonce before acting.
}

/**
 * Whether registration is open.
 *
 * Respects Settings → General → «هر کسی می‌تواند ثبت‌نام کند». WordPress ships
 * that switch off, so it has to be turned on before the form will accept
 * anyone — a deliberate gate, not an oversight.
 *
 * @return bool
 */
function zandi_registration_open() {
	return (bool) apply_filters( 'zandi_registration_open', (bool) get_option( 'users_can_register' ) );
}

/* =========================================================================
 * Routing and guards
 * ====================================================================== */

/**
 * Points the account routes at their templates.
 *
 * @param string $template Template chosen by WordPress.
 * @return string
 */
function zandi_account_template( $template ) {
	$route = zandi_account_route();

	if ( ! $route ) {
		return $template;
	}

	/*
	 * Status and query flags are settled on `wp` by zandi_prepare_virtual_page().
	 * Note what is deliberately not done here: claiming is_singular. Core's
	 * body_class() reads ->post_type off get_queried_object() on a singular
	 * query, which is null on a virtual page, and every one of these would emit
	 * a PHP warning.
	 */
	return 'panel' === $route
		? get_theme_file_path( 'template-dashboard.php' )
		: get_theme_file_path( 'template-account.php' );
}
add_filter( 'template_include', 'zandi_account_template' );

/**
 * Guards the account routes and processes their forms.
 *
 * Runs before any output, so a redirect is still possible.
 *
 * @return void
 */
function zandi_account_guard() {
	$route = zandi_account_route();

	if ( ! $route ) {
		return;
	}

	// An account page must never be served from a cache.
	nocache_headers();

	if ( 'logout' === $route ) {
		zandi_handle_logout();
		return;
	}

	if ( 'panel' === $route ) {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( zandi_login_url( zandi_panel_url() ) );
			exit;
		}

		return;
	}

	/*
	 * Someone already signed in has no use for the login or signup form.
	 *
	 * Staff go to wp-admin, not the student panel. Digits can be configured to
	 * redirect wp-login.php here, and when it is, an administrator who was
	 * already signed in got bounced login → panel and never reached the
	 * dashboard they were actually asking for.
	 */
	if ( is_user_logged_in() ) {
		wp_safe_redirect( zandi_is_staff() ? admin_url() : zandi_panel_url() );
		exit;
	}

	/*
	 * /register/ used to 301 here to /login/, from when the intent was a single
	 * combined form. Digits does not work that way — it ships a login form and a
	 * signup form that cross-link to each other — so the redirect was sending
	 * students away from the only page that could create their account. Both
	 * routes are real, and both render the provider's own form.
	 */

	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '' ) ) {
		return;
	}

	if ( 'login' === $route ) {
		zandi_handle_login();
		return;
	}

	zandi_handle_register();
}
add_action( 'template_redirect', 'zandi_account_guard' );

/**
 * Logs the student out and returns them to the homepage.
 *
 * An unsigned logout link is a cross-site request forgery — a third-party page
 * could sign your students out — so the nonce is required. A bad one simply
 * does nothing rather than showing core's die screen.
 *
 * @return void
 */
function zandi_handle_logout() {
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	if ( wp_verify_nonce( $nonce, 'log-out' ) ) {
		wp_logout();
	}

	wp_safe_redirect( home_url( '/' ) );
	exit;
}

/**
 * Where to send a student after signing in.
 *
 * `wp_validate_redirect()` confines the destination to this host, so a crafted
 * `?redirect_to=` cannot bounce a freshly signed-in student off-site.
 *
 * @return string
 */
function zandi_auth_redirect_target() {
	$requested = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Validated against the host below; the form's own nonce is checked by the caller.

	return $requested ? wp_validate_redirect( $requested, zandi_panel_url() ) : zandi_panel_url();
}

/**
 * Processes the login form.
 *
 * @return void
 */
function zandi_handle_login() {
	$errors = zandi_auth_errors();

	$nonce = isset( $_POST['zandi_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_login_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'zandi_login' ) ) {
		$errors->add( 'expired', 'صفحه منقضی شده بود. یک بار دیگه امتحان کن.' );
		return;
	}

	$identifier = isset( $_POST['zandi_identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_identifier'] ) ) : '';

	// The password is used verbatim: sanitising it would silently change it.
	$password = isset( $_POST['zandi_password'] ) ? (string) wp_unslash( $_POST['zandi_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised -- Passwords must not be altered; wp_signon() hashes and compares.

	if ( '' === $identifier || '' === $password ) {
		$errors->add( 'empty', 'شماره موبایل و رمز عبور رو وارد کن.' );
		return;
	}

	// An address is taken as typed; anything else is treated as a phone number.
	$login = is_email( $identifier ) ? $identifier : zandi_normalize_phone( $identifier );

	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $password,
			'remember'      => ! empty( $_POST['zandi_remember'] ),
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		/*
		 * One message for both "no such account" and "wrong password". Telling
		 * them apart would let anyone enumerate which numbers are registered.
		 */
		$errors->add( 'failed', 'شماره موبایل یا رمز عبور درست نیست.' );
		return;
	}

	wp_safe_redirect( zandi_auth_redirect_target() );
	exit;
}

/**
 * Processes the registration form.
 *
 * @return void
 */
function zandi_handle_register() {
	$errors = zandi_auth_errors();

	if ( ! zandi_registration_open() ) {
		$errors->add( 'closed', 'ثبت‌نام فعلاً باز نیست.' );
		return;
	}

	$nonce = isset( $_POST['zandi_register_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_register_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'zandi_register' ) ) {
		$errors->add( 'expired', 'صفحه منقضی شده بود. یک بار دیگه امتحان کن.' );
		return;
	}

	/*
	 * Honeypot. The field is hidden from people and left empty by them; bots
	 * fill every input they find. Google reCAPTCHA is unreachable from Iran, so
	 * this is the defence that actually works here.
	 */
	if ( ! empty( $_POST['zandi_website'] ) ) {
		$errors->add( 'spam', 'ثبت‌نام انجام نشد. اگر ربات نیستی، دوباره امتحان کن.' );
		return;
	}

	$name  = isset( $_POST['zandi_name'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_name'] ) ) : '';
	$phone = zandi_normalize_phone( isset( $_POST['zandi_phone'] ) ? wp_unslash( $_POST['zandi_phone'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised -- Normalised to digits below, which is stricter than sanitize_text_field().
	$email = isset( $_POST['zandi_email'] ) ? sanitize_email( wp_unslash( $_POST['zandi_email'] ) ) : '';

	$password = isset( $_POST['zandi_password'] ) ? (string) wp_unslash( $_POST['zandi_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised -- Passwords must not be altered.

	if ( '' === $name ) {
		$errors->add( 'name', 'اسمت رو بنویس.' );
	}

	if ( ! zandi_is_valid_phone( $phone ) ) {
		$errors->add( 'phone', 'شماره موبایل باید یازده رقم باشه و با ۰۹ شروع بشه.' );
	} elseif ( username_exists( $phone ) ) {
		$errors->add( 'phone_taken', 'این شماره قبلاً ثبت‌نام کرده. از همین‌جا وارد شو.' );
	}

	if ( '' !== $email && ! is_email( $email ) ) {
		$errors->add( 'email', 'ایمیل درست وارد نشده.' );
	} elseif ( '' !== $email && email_exists( $email ) ) {
		$errors->add( 'email_taken', 'این ایمیل قبلاً ثبت شده.' );
	}

	if ( strlen( $password ) < 8 ) {
		$errors->add( 'password', 'رمز عبور باید حداقل ۸ کاراکتر باشه.' );
	}

	if ( ! zandi_identity_verified( $phone ) ) {
		$errors->add( 'unverified', 'شماره موبایل تأیید نشد.' );
	}

	if ( $errors->has_errors() ) {
		return;
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $phone,
			'user_pass'    => $password,
			'user_email'   => $email,
			'display_name' => $name,
			'first_name'   => $name,
			'role'         => 'subscriber',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		$errors->add( 'create', $user_id->get_error_message() );
		return;
	}

	update_user_meta( $user_id, 'zandi_phone', $phone );

	// WooCommerce reads billing_phone at checkout, and SpotPlayer's WooCommerce
	// plugin keys the licence it generates to that number.
	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, 'billing_first_name', $name );

	/**
	 * Fires after a student account is created.
	 *
	 * Hook this to send a welcome SMS once a provider exists.
	 *
	 * @param int    $user_id New user ID.
	 * @param string $phone   Normalised mobile number.
	 */
	do_action( 'zandi_student_registered', $user_id, $phone );

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true, is_ssl() );

	wp_safe_redirect( zandi_auth_redirect_target() );
	exit;
}

/* =========================================================================
 * Keeping students out of wp-admin
 * ====================================================================== */

/**
 * Whether this user belongs in wp-admin at all.
 *
 * `edit_posts` is the dividing line: Shima and any future editor have it,
 * subscribers do not.
 *
 * @param int $user_id Optional user ID. Defaults to the current user.
 * @return bool
 */
function zandi_is_staff( $user_id = 0 ) {
	return $user_id
		? user_can( $user_id, 'edit_posts' )
		: current_user_can( 'edit_posts' );
}

/**
 * Sends students to their own dashboard if they land on wp-admin.
 *
 * The two exemptions matter: `admin-ajax.php` and `admin-post.php` both boot
 * the admin and fire this hook, and the theme's own enrol and notify forms post
 * to the latter. Redirecting there would break them for signed-in students.
 *
 * @return void
 */
function zandi_block_admin_for_students() {
	if ( ! is_user_logged_in() || zandi_is_staff() ) {
		return;
	}

	if ( wp_doing_ajax() ) {
		return;
	}

	$pagenow = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';

	if ( in_array( $pagenow, array( 'admin-ajax.php', 'admin-post.php' ), true ) ) {
		return;
	}

	wp_safe_redirect( zandi_panel_url() );
	exit;
}
add_action( 'admin_init', 'zandi_block_admin_for_students' );

/**
 * Hides the admin bar from students.
 *
 * @param bool $show Whether to show the bar.
 * @return bool
 */
function zandi_hide_admin_bar( $show ) {
	return zandi_is_staff() ? $show : false;
}
add_filter( 'show_admin_bar', 'zandi_hide_admin_bar' );

/**
 * Routes core's own login and logout redirects back into the theme.
 *
 * Anything that links to `wp-login.php` — a plugin, a bookmark, WooCommerce —
 * lands a student on the grey core form. These filters catch the round trip so
 * they end up in the panel instead.
 *
 * @param string           $redirect_to Requested destination.
 * @param string           $requested   Raw requested destination.
 * @param WP_User|WP_Error $user        Signed-in user, or an error.
 * @return string
 */
function zandi_login_redirect( $redirect_to, $requested, $user ) {
	if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
		return $redirect_to;
	}

	if ( zandi_is_staff( $user->ID ) ) {
		return $redirect_to;
	}

	// Honour an explicit on-site destination, otherwise the panel.
	return $requested ? wp_validate_redirect( $requested, zandi_panel_url() ) : zandi_panel_url();
}
add_filter( 'login_redirect', 'zandi_login_redirect', 10, 3 );

/**
 * Sends core's logout back to the homepage rather than the login form.
 *
 * @param string $redirect_to Requested destination.
 * @return string
 */
function zandi_logout_redirect( $redirect_to ) {
	return $redirect_to ? $redirect_to : home_url( '/' );
}
add_filter( 'logout_redirect', 'zandi_logout_redirect' );
