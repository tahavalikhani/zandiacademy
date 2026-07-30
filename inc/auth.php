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
 * @return string
 */
function zandi_register_url() {
	return zandi_account_url( 'register' );
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
 * The stored phone number for a user.
 *
 * @param int $user_id User ID. Defaults to the current user.
 * @return string
 */
function zandi_user_phone( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();

	if ( ! $user_id ) {
		return '';
	}

	$phone = (string) get_user_meta( $user_id, 'zandi_phone', true );

	if ( $phone ) {
		return $phone;
	}

	// Accounts created before this meta existed, or by WooCommerce at checkout.
	$phone = (string) get_user_meta( $user_id, 'billing_phone', true );

	if ( $phone ) {
		return zandi_normalize_phone( $phone );
	}

	$user = get_userdata( $user_id );

	return ( $user && zandi_is_valid_phone( $user->user_login ) ) ? $user->user_login : '';
}

/* =========================================================================
 * The OTP seam
 *
 * Iranian students expect to sign in with a code sent by SMS, not a password.
 * The academy has no SMS account yet, so the built-in forms use a password and
 * the two hooks below are where OTP takes over when it arrives.
 *
 * NOTE: zandi_identity_verified() returns TRUE today. It is NOT a security
 * check — it is a declaration that nothing has verified this number yet. Do not
 * grant anything on the strength of it until an OTP flow is actually wired.
 * ====================================================================== */

/**
 * Whether the visitor has proved they hold this number.
 *
 * @param string $phone Normalised number.
 * @return bool Always true until an OTP provider is connected.
 */
function zandi_identity_verified( $phone ) {
	return (bool) apply_filters( 'zandi_identity_verified', true, $phone );
}

/**
 * A shortcode to render instead of the built-in login form.
 *
 * This is the drop-in point for an OTP plugin. Set it to that plugin's login
 * shortcode and the theme steps aside, keeping its own card, heading and page
 * chrome around the plugin's markup:
 *
 *     add_filter( 'zandi_login_shortcode', fn() => '[lwp_login]' );
 *
 * @return string
 */
function zandi_login_shortcode() {
	return (string) apply_filters( 'zandi_login_shortcode', '' );
}

/**
 * A shortcode to render instead of the built-in registration form.
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

	// Someone already signed in has no use for the login or signup form.
	if ( is_user_logged_in() ) {
		wp_safe_redirect( zandi_panel_url() );
		exit;
	}

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
