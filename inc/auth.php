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

	/*
	 * rawurlencode() IS REQUIRED HERE. add_query_arg() does not encode its
	 * values — build_query() calls _http_build_query() with $urlencode = false
	 * — and a comment in this spot claimed the opposite for a long time. The
	 * consequence: any destination carrying its own query string ended at the
	 * first `&`, and everything after it became a parameter of the LOGIN page.
	 *
	 *   /login/?redirect_to=https://site/placement/?report=1&r=TOKEN
	 *
	 * reads back as redirect_to=…/placement/?report=1 plus a stray r=TOKEN, so
	 * the token was silently dropped. Encoded, the whole address survives.
	 */
	return $redirect_to ? add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url ) : $url;
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

	/*
	 * rawurlencode() IS REQUIRED HERE. add_query_arg() does not encode its
	 * values — build_query() calls _http_build_query() with $urlencode = false
	 * — and a comment in this spot claimed the opposite for a long time. The
	 * consequence: any destination carrying its own query string ended at the
	 * first `&`, and everything after it became a parameter of the LOGIN page.
	 *
	 *   /login/?redirect_to=https://site/placement/?report=1&r=TOKEN
	 *
	 * reads back as redirect_to=…/placement/?report=1 plus a stray r=TOKEN, so
	 * the token was silently dropped. Encoded, the whole address survives.
	 */
	return $redirect_to ? add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url ) : $url;
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

/**
 * Records when a student last signed in.
 *
 * WordPress keeps no such field. The owner's student panel wants it — an
 * account that paid for a course and has not been back since is the one worth a
 * message — and there is nowhere else to read it from.
 *
 * ONE WRITE PER SIGN-IN, NEVER PER PAGE VIEW. This is the only thing the whole
 * students screen adds to a request that is not already in wp-admin, and it is
 * on an action that fires once a session rather than on a template hook. Moving
 * it to `init` or `wp` to catch cookie-authenticated visits would put a write on
 * every page load; do not.
 *
 * @param string  $user_login Login name.
 * @param WP_User $user       The user signing in.
 * @return void
 */
function zandi_record_last_login( $user_login, $user = null ) {
	if ( ! $user instanceof WP_User ) {
		return;
	}

	update_user_meta( $user->ID, 'zandi_last_login', time() );
}
add_action( 'wp_login', 'zandi_record_last_login', 99, 2 );

/**
 * When a student last signed in.
 *
 * @param int $user_id User ID.
 * @return int Unix timestamp, or 0 for an account that has never signed in
 *             since this started being recorded.
 */
function zandi_last_login( $user_id ) {
	return (int) get_user_meta( (int) $user_id, 'zandi_last_login', true );
}

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
		$markup = do_shortcode( $shortcode );
	} else {
		// Digits names these per form; older builds expose only df_digits_form().
		$specific = $is_register ? 'df_digits_form_signup' : 'df_digits_form_login';

		if ( function_exists( $specific ) ) {
			$markup = (string) call_user_func( $specific );
		} elseif ( function_exists( 'df_digits_form' ) ) {
			$markup = (string) df_digits_form();
		} else {
			return '';
		}
	}

	/**
	 * The provider's markup, on its way to the card.
	 *
	 * @param string $markup Markup from the provider.
	 * @param string $route  'login' or 'register'.
	 */
	return (string) apply_filters( 'zandi_provider_form_markup', $markup, $route );
}
add_filter( 'zandi_provider_form_markup', 'zandi_clean_provider_form', 10, 2 );

/* -------------------------------------------------------------------------
 * Cleaning the provider's markup
 *
 * WHY THIS IS PHP AND NOT MORE CSS
 *
 * Two things Digits prints are duplicates of things the card already says:
 * its own «عضویت» heading, under the theme's «حسابت رو بساز», and its own
 * «قبلا عضو شدید؟ اکنون وارد شوید», under the theme's «قبلاً حساب ساختی؟».
 *
 * assets/css/panel.css has been through three rounds of trying to reach those
 * with selectors, and its header records why that keeps failing: Digits' class
 * names do not partition — `digits_tab_content_mobile` is a step panel,
 * `digits_submit_wrapper` is a container — so a substring match that looks like
 * it names the heading also names something load-bearing. CSS can restyle a
 * control it can identify. It cannot tell a duplicate heading from a live one.
 *
 * But the theme already *holds* this markup as a string before it is printed —
 * zandi_prepare_auth_form() renders it on template_redirect and stashes it. So
 * the duplicates can be matched on what they actually are, which is their text,
 * and removed from the tree. No class names are consulted anywhere below.
 *
 * THE SAFETY PROPERTY THAT MATTERS MORE THAN THE FEATURE
 *
 * If this ever returns an empty string, zandi_auth_form_markup() reports that no
 * provider is active and the partials draw the theme's own password form. That
 * is the split-auth regression CLAUDE.md warns about: a student registers with a
 * password on /register/, then cannot sign in on /login/, which wants a code.
 *
 * So every path below fails *open*. Missing extension, parse error, thrown
 * exception, empty result, a result with no <form> left in it — all return the
 * original string untouched. The worst outcome of a bug here is the page exactly
 * as it looks today.
 * ---------------------------------------------------------------------- */

/**
 * Normalises Persian text for comparison.
 *
 * Three things vary between a plugin's translation file and a string written
 * here, and none of them are visible: ZWNJ (U+200C), which «ثبت‌نام» has and
 * «ثبت نام» does not; the Arabic ي and ك, which many translations use where
 * Persian wants ی and ک; and trailing punctuation.
 *
 * @param string $text Raw text.
 * @return string Comparable text.
 */
function zandi_norm_fa( $text ) {
	$text = str_replace(
		array( "\xE2\x80\x8C", "\xE2\x80\x8F", "\xE2\x80\x8E", 'ي', 'ك' ),
		array( '', '', '', 'ی', 'ک' ),
		(string) $text
	);

	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = trim( (string) $text );

	// «عضویت!» and «عضویت» are the same heading.
	return trim( $text, " \t\n\r\0\x0B.،؛:!؟?" );
}

/**
 * Headings the provider prints that the card has already said.
 *
 * Matched whole, never as a substring: «ورود» is also inside «ورود با رمز
 * عبور», which is a control and must survive.
 *
 * @return string[]
 */
function zandi_provider_duplicate_titles() {
	return (array) apply_filters(
		'zandi_provider_duplicate_titles',
		array( 'ورود', 'عضویت', 'ثبت نام', 'ثبتنام', 'ورود / عضویت', 'Login', 'Register', 'Sign up', 'Sign in' )
	);
}

/**
 * Cross-links the provider prints that the card already offers.
 *
 * The theme's own link is the one to keep. It is built by zandi_login_url() /
 * zandi_register_url(), which fall back to a query string when permalinks are
 * «ساده»; Digits writes a pretty path either way, so on a «ساده» install its
 * link 404s and the theme's does not.
 *
 * @return string[]
 */
function zandi_provider_duplicate_links() {
	return (array) apply_filters(
		'zandi_provider_duplicate_links',
		array( 'اکنون وارد شوید', 'اکنون عضو شوید', 'وارد شوید', 'عضو شوید', 'ثبت نام کنید', 'Login now', 'Register now' )
	);
}

/**
 * Removes the provider's duplicate heading and cross-link.
 *
 * @param string $markup Markup from the provider.
 * @param string $route  'login' or 'register'. Unused — both forms duplicate the
 *                       same two things, and the lists above cover both.
 * @return string Cleaned markup, or the original whenever cleaning is not safe.
 */
function zandi_clean_provider_form( $markup, $route = 'login' ) {
	unset( $route );

	$markup = (string) $markup;

	/*
	 * No form, nothing to protect and nothing worth cleaning. This is also the
	 * guard that keeps the check at the end meaningful.
	 */
	if ( '' === trim( $markup ) || false === stripos( $markup, '<form' ) ) {
		return $markup;
	}

	/*
	 * ext-dom ships with almost every PHP build and mbstring with every
	 * WordPress host, but «almost» is not good enough for the file that renders
	 * the login page. Absent either, this does nothing at all.
	 */
	if ( ! class_exists( 'DOMDocument' ) || ! function_exists( 'mb_encode_numericentity' ) ) {
		return $markup;
	}

	try {
		$cleaned = zandi_strip_provider_duplicates( $markup );
	} catch ( Exception $e ) {
		return $markup;
	} catch ( Error $e ) {
		return $markup;
	}

	// Fail open. A form that lost its <form> is worse than one that kept a heading.
	if ( '' === trim( (string) $cleaned ) || false === stripos( (string) $cleaned, '<form' ) ) {
		return $markup;
	}

	return $cleaned;
}

/**
 * The DOM pass itself.
 *
 * Split out so zandi_clean_provider_form() is nothing but guards — every early
 * return there is a decision about safety, and mixing them with parsing made
 * both harder to read.
 *
 * @param string $markup Markup from the provider.
 * @return string Cleaned markup, or '' if the tree could not be read.
 */
function zandi_strip_provider_duplicates( $markup ) {
	/*
	 * DOMDocument::loadHTML() assumes ISO-8859-1, so every Persian character
	 * comes back as mojibake unless the input is pure ASCII first. Numeric
	 * entities in, numeric entities out, decoded at the end — and decoded with
	 * mb_decode_numericentity() rather than html_entity_decode(), which would
	 * also unescape a legitimate `&amp;` sitting in a placeholder or an href.
	 */
	$map  = array( 0x80, 0x10FFFF, 0, 0x1FFFFF );
	$html = mb_encode_numericentity( $markup, $map, 'UTF-8' );

	$doc  = new DOMDocument( '1.0', 'UTF-8' );
	$prev = libxml_use_internal_errors( true );

	/*
	 * A known wrapper, because the plugin hands over a fragment with several
	 * top-level nodes and there has to be something to serialise the children
	 * of. NOIMPLIED and NODEFDTD stop libxml adding <html><body> around it,
	 * which would otherwise be pasted into the middle of the card.
	 */
	$loaded = $doc->loadHTML(
		'<div id="zandi-provider-root">' . $html . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);

	libxml_clear_errors();
	libxml_use_internal_errors( $prev );

	if ( ! $loaded ) {
		return '';
	}

	$xpath = new DOMXPath( $doc );
	$roots = $xpath->query( '//*[@id="zandi-provider-root"]' );

	if ( ! $roots || 0 === $roots->length ) {
		return '';
	}

	$root = $roots->item( 0 );

	zandi_remove_provider_titles( $xpath );
	zandi_remove_provider_links( $xpath );

	$out = '';

	foreach ( $root->childNodes as $child ) {
		$out .= $doc->saveHTML( $child );
	}

	return mb_decode_numericentity( $out, $map, 'UTF-8' );
}

/**
 * Drops the provider's own form title.
 *
 * Leaf elements only — one with an element child is a container, and containers
 * here hold the step track. Controls are excluded outright: a *button* labelled
 * «عضویت» is the thing the whole page exists to offer, and it reads as a
 * duplicate heading to any match that looks only at text.
 *
 * @param DOMXPath $xpath Document index.
 * @return void
 */
function zandi_remove_provider_titles( DOMXPath $xpath ) {
	$titles = array_map( 'zandi_norm_fa', zandi_provider_duplicate_titles() );
	$nodes  = $xpath->query( '//*[not(*)][not(self::button or self::a or self::input or self::label or self::option or self::select or self::textarea)]' );

	if ( ! $nodes ) {
		return;
	}

	// Snapshotted: removing while iterating a live DOMNodeList skips nodes.
	$doomed = array();

	foreach ( $nodes as $node ) {
		if ( in_array( zandi_norm_fa( $node->textContent ), $titles, true ) ) {
			$doomed[] = $node;
		}
	}

	foreach ( $doomed as $node ) {
		if ( $node->parentNode ) {
			$node->parentNode->removeChild( $node );
		}
	}
}

/**
 * Drops the provider's cross-link to the other auth page.
 *
 * Removing only the <a> would leave «قبلا عضو شدید؟» stranded on its own line,
 * so the sentence around it goes too — but only when that sentence is nothing
 * but the prompt. A parent still holding a control, another link, or a
 * paragraph's worth of text is left exactly as it was.
 *
 * @param DOMXPath $xpath Document index.
 * @return void
 */
function zandi_remove_provider_links( DOMXPath $xpath ) {
	$phrases = array_map( 'zandi_norm_fa', zandi_provider_duplicate_links() );
	$nodes   = $xpath->query( '//a' );

	if ( ! $nodes ) {
		return;
	}

	$doomed = array();

	foreach ( $nodes as $node ) {
		if ( in_array( zandi_norm_fa( $node->textContent ), $phrases, true ) ) {
			$doomed[] = $node;
		}
	}

	foreach ( $doomed as $node ) {
		$parent = $node->parentNode;

		if ( ! $parent ) {
			continue;
		}

		$parent->removeChild( $node );

		if ( ! $parent instanceof DOMElement || 'zandi-provider-root' === $parent->getAttribute( 'id' ) ) {
			continue;
		}

		/*
		 * 80 characters: long enough for «قبلا عضو شدید؟», far short of any
		 * real sentence the plugin might have put a link inside.
		 */
		$leftover = zandi_norm_fa( $parent->textContent );

		if ( 0 === $parent->getElementsByTagName( '*' )->length && mb_strlen( $leftover, 'UTF-8' ) <= 80 && $parent->parentNode ) {
			$parent->parentNode->removeChild( $parent );
		}
	}
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
 * Gives each account route its own browser title.
 *
 * Without this all three shared one, and it was the site name on its own.
 *
 * The cause is worth writing down, because any future virtual route will walk
 * into it. zandi_prepare_virtual_page() sets `is_home = false` and claims
 * nothing in its place — no is_singular, no queried object — so by the time
 * wp_get_document_title() runs, every branch it tests is false: not singular,
 * not an archive, not a search, not a 404, not the front page. `$title['title']`
 * is never assigned, and the whole title collapses to `$title['site']`. Section,
 * course and placement pages escape it only because each has its own
 * document_title_parts filter. These three had none, so /login/, /register/ and
 * /panel/ all rendered `<title>آکادمی زندی</title>`.
 *
 * @param array<string,string> $parts Title parts.
 * @return array<string,string>
 */
function zandi_account_title( $parts ) {
	$route  = zandi_account_route();
	$titles = zandi_account_titles();

	if ( $route && isset( $titles[ $route ] ) ) {
		$parts['title'] = $titles[ $route ];
	}

	return $parts;
}
add_filter( 'document_title_parts', 'zandi_account_title' );

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
		/*
		 * An explicit destination still wins. Someone already signed in who
		 * follows «وارد شو» from the checkout gate — a link that carries
		 * redirect_to — was asking for the checkout, not for their panel, and
		 * dropping them on the panel is the same lost-journey bug by another
		 * route.
		 */
		wp_safe_redirect( zandi_auth_redirect_target( zandi_is_staff() ? admin_url() : zandi_panel_url() ) );
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
 * Two sources, in order: an explicit `?redirect_to=` on this request, then the
 * address remembered on the way in. The second is what makes this work at all
 * in production — Digits processes the form and never passes redirect_to on, so
 * without the cookie there is nothing here to read.
 *
 * CONSUMING IS THE POINT, not a side effect: honouring a remembered address is
 * exactly the moment it stops being owed, and leaving it set would bounce the
 * student again on the page after this one.
 *
 * An account route is never a destination. `/login/` would loop and `/logout/`
 * would sign out the person who just signed in.
 *
 * @param string $fallback Optional. Where to go when nothing was asked for.
 *                         Defaults to the student panel.
 * @return string
 */
function zandi_auth_redirect_target( $fallback = '' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Validated below; the form's own nonce is checked by the caller.
	$requested = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
	$target    = zandi_safe_destination( $requested );

	if ( '' === $target ) {
		$target = zandi_intent();
	}

	if ( '' !== $target && ! zandi_is_account_url( $target ) ) {
		zandi_forget_intent();

		return $target;
	}

	return $fallback ? $fallback : zandi_panel_url();
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
 * The return address
 *
 * THE PROBLEM THIS SOLVES, in the owner's words: a visitor picks a course, is
 * told to sign in — correctly — and after signing in lands on the homepage
 * instead of back at the checkout they were three clicks into.
 *
 * `?redirect_to=` is the obvious mechanism and it is not enough, for a reason
 * that is structural rather than a mistake. Digits owns both auth forms in
 * production — CLAUDE.md is explicit that this is the intended state — which
 * means Digits also processes both submissions. zandi_handle_login() and
 * zandi_handle_register(), the only two functions that ever read redirect_to,
 * never run. Nothing on the live site consulted the destination at all, so the
 * plugin's own setting decided where everybody landed, and every gated flow on
 * the site funnelled through that one step. One redirect, broken once, looking
 * like all of them.
 *
 * The fix cannot be a hidden field: the theme does not own that markup and
 * CLAUDE.md forbids rewriting a plugin's form. So the address is remembered
 * out of band — a cookie, set when a signed-out visitor reaches an auth page
 * and spent on the first page view after they are signed in. It survives a
 * form the theme did not render, an AJAX sign-in, and any redirect chain the
 * plugin invents, because it never has to travel through any of them.
 *
 * The placement test had to invent exactly this for itself in August 2026, and
 * its comment there reached the same conclusion in the same words. This is that
 * mechanism, generalised out of that one feature and made the theme's own.
 * ====================================================================== */

/**
 * The cookie holding where a visitor was going before they were asked to sign in.
 *
 * @return string
 */
function zandi_intent_cookie() {
	return 'zandi_intent';
}

/**
 * How long an interrupted journey is worth resuming.
 *
 * Long enough for an SMS code to arrive and be typed, short enough that someone
 * who abandoned signup and came back tomorrow is not bounced somewhere they
 * have forgotten asking for.
 *
 * @return int Seconds.
 */
function zandi_intent_ttl() {
	return (int) apply_filters( 'zandi_intent_ttl', 30 * MINUTE_IN_SECONDS );
}

/**
 * A destination this site is willing to send somebody to.
 *
 * wp_validate_redirect() confines it to this host, so neither a crafted
 * `?redirect_to=` nor a forged cookie can bounce anyone off-site — the worst
 * either can do is send the person who supplied it somewhere on the site they
 * could have typed themselves.
 *
 * @param string $url Candidate destination.
 * @return string Validated absolute URL, or ''.
 */
function zandi_safe_destination( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	return (string) wp_validate_redirect( $url, '' );
}

/**
 * Remembers where somebody was going.
 *
 * $_COOKIE is written as well as the header, so the value is readable later in
 * the same request. Core does the same in wp_set_auth_cookie() and it matters
 * here: the request that records an intent is often the one that then redirects.
 *
 * @param string $url Destination.
 * @return bool Whether anything was remembered.
 */
function zandi_remember_intent( $url ) {
	$url = zandi_safe_destination( $url );

	if ( '' === $url ) {
		return false;
	}

	$_COOKIE[ zandi_intent_cookie() ] = $url;

	if ( ! headers_sent() ) {
		setcookie(
			zandi_intent_cookie(),
			$url,
			array(
				'expires'  => time() + zandi_intent_ttl(),
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	return true;
}

/**
 * The remembered destination, or ''.
 *
 * @return string
 */
function zandi_intent() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading our own cookie, validated below.
	$stored = isset( $_COOKIE[ zandi_intent_cookie() ] ) ? wp_unslash( $_COOKIE[ zandi_intent_cookie() ] ) : '';

	return zandi_safe_destination( esc_url_raw( (string) $stored ) );
}

/**
 * Drops the remembered destination.
 *
 * @return void
 */
function zandi_forget_intent() {
	unset( $_COOKIE[ zandi_intent_cookie() ] );

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		zandi_intent_cookie(),
		'',
		array(
			'expires'  => time() - YEAR_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Records the destination when a signed-out visitor reaches an auth page.
 *
 * Every gated flow on the site converges here, whether the visitor was sent by
 * a redirect or followed a link with `?redirect_to=` on it, so this one hook
 * covers all of them rather than each one having to remember for itself.
 *
 * @return void
 */
function zandi_capture_intent() {
	if ( is_user_logged_in() ) {
		return;
	}

	$route = zandi_account_route();

	if ( 'login' !== $route && 'register' !== $route ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, and validated by zandi_remember_intent().
	$requested = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

	if ( '' !== $requested ) {
		zandi_remember_intent( $requested );
	}
}
add_action( 'template_redirect', 'zandi_capture_intent', 4 );

/**
 * Whether this request may be redirected to a remembered destination.
 *
 * @return bool
 */
function zandi_may_resume_intent() {
	/*
	 * Only ever on an ordinary page view. Firing during a form POST, an AJAX
	 * call, a feed or a REST request would swallow whatever that request was
	 * doing — a checkout submission above all.
	 */
	if ( is_admin() || wp_doing_ajax() || is_feed() ) {
		return false;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	if ( 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' ) ) {
		return false;
	}

	/*
	 * Never off the placement test, in any of its states. The student has just
	 * chosen one — and the one they choose most often is «دوباره آزمون بده»,
	 * which asks for ?start=1 and would land on a months-old report instead of
	 * a fresh test, looking for all the world like the button does nothing.
	 */
	if ( function_exists( 'zandi_is_placement' ) && zandi_is_placement() ) {
		return false;
	}

	/**
	 * Filters whether a remembered destination may be resumed on this request.
	 *
	 * @param bool $may Whether to resume.
	 */
	return (bool) apply_filters( 'zandi_may_resume_intent', true );
}

/**
 * Sends a freshly signed-in visitor back to where they were going.
 *
 * Fires once and then clears, so nobody is bounced twice and nobody is bounced
 * on a journey they have forgotten asking for.
 *
 * @return void
 */
function zandi_resume_intent() {
	$intent = zandi_intent();

	if ( '' === $intent || ! is_user_logged_in() ) {
		return;
	}

	if ( ! zandi_may_resume_intent() ) {
		/*
		 * Spent rather than kept. On the placement route especially, holding it
		 * back would only mean bouncing them off the next page they opened.
		 */
		zandi_forget_intent();

		return;
	}

	zandi_forget_intent();

	/*
	 * Never back to an auth page. A destination of /login/ is a loop, and a
	 * destination of /logout/ would sign out the person who just signed in.
	 */
	if ( zandi_is_account_url( $intent ) ) {
		return;
	}

	// Already there: clear it and let the page render, or this is a loop.
	if ( zandi_same_url( $intent, zandi_current_url() ) ) {
		return;
	}

	wp_safe_redirect( $intent );
	exit;
}
add_action( 'template_redirect', 'zandi_resume_intent', 6 );

/**
 * The URL of the request being served.
 *
 * @return string
 */
function zandi_current_url() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

	return home_url( $uri );
}

/**
 * Whether two URLs address the same page, ignoring a trailing slash.
 *
 * @param string $a First URL.
 * @param string $b Second URL.
 * @return bool
 */
function zandi_same_url( $a, $b ) {
	return untrailingslashit( (string) $a ) === untrailingslashit( (string) $b );
}

/**
 * The routes that are never a destination.
 *
 * `/login/` and `/register/` would loop — the whole point of the return address
 * is that they have just left one. `/logout/` would sign out the person who has
 * just signed in.
 *
 * `/panel/` IS DELIBERATELY NOT HERE. It is an account route but it is also a
 * perfectly good place to be going, and it is where the guard sends a signed-out
 * visitor who asked for it. Refusing it stranded exactly the journey it was
 * meant to protect — caught by tests/test-redirects.php, which is the reason
 * this list is its own function rather than a reuse of zandi_account_routes().
 *
 * @return array<int,string>
 */
function zandi_auth_form_routes() {
	return array( 'login', 'register', 'logout' );
}

/**
 * Whether a URL is one of the routes that is never a destination.
 *
 * @param string $url URL to test.
 * @return bool
 */
function zandi_is_account_url( $url ) {
	$path = trim( (string) wp_parse_url( (string) $url, PHP_URL_PATH ), '/' );
	$base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

	if ( '' !== $base && 0 === strpos( $path, $base ) ) {
		$path = trim( substr( $path, strlen( $base ) ), '/' );
	}

	return in_array( sanitize_key( $path ), zandi_auth_form_routes(), true );
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
 * The capability that opens the owner's student panel.
 *
 * DELIBERATELY NOT zandi_is_staff(). That line is `edit_posts`, which an editor
 * hired to write blog posts has — and the students screen shows mobile numbers,
 * payment histories and test answers. `manage_options` is the owner alone.
 *
 * Defined here rather than in inc/students.php because inc/students.php is
 * loaded only inside wp-admin, and the printable placement report — a front-end
 * page — has to ask the same question when the owner opens a student's report.
 *
 * @return string
 */
function zandi_students_capability() {
	return (string) apply_filters( 'zandi_students_capability', 'manage_options' );
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

	// Honour an explicit on-site destination, otherwise the address remembered
	// on the way in, otherwise the panel. The middle one saves a hop for any
	// plugin that does apply this filter; zandi_resume_intent() is the backstop
	// for the ones that do not.
	if ( $requested ) {
		$target = zandi_safe_destination( $requested );

		if ( '' !== $target ) {
			return $target;
		}
	}

	return zandi_auth_redirect_target( zandi_panel_url() );
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
