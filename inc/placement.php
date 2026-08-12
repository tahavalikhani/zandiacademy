<?php
/**
 * Free placement test — /placement/
 *
 * A thirty-question CEFR placement test covering A1 to B2. The question bank,
 * the band thresholds and the scoring rules all come from the owner's
 * specification (docs/placement-test.md) and its companion data file,
 * inc/data/questions.json, which is reproduced verbatim and MUST NOT be edited
 * here: nothing in this file rewrites a stem, an option or an answer.
 *
 * NOT LINKED FROM THE SITE YET, ON PURPOSE. The route exists and the page works,
 * but no menu, footer column or homepage section points at it, and
 * zandi_placement_noindex() keeps it out of search results. Launching it means
 * three deliberate steps, listed on that function.
 *
 * ── Why the test is scored on the server ──────────────────────────────────
 *
 * The specification sketches a browser-side implementation whose calculateLevel()
 * runs in JavaScript. This does it in PHP instead, for three reasons:
 *
 *   1. The answer key stays on the server. The options of every question are
 *      shuffled here, and the page never says which one is right. A client-side
 *      scorer has to ship `answer: 0` to the browser, where View Source is the
 *      whole exam.
 *   2. The result has to be storable. It is written to the student's account
 *      and shown in the panel, so the server has to compute it anyway — and a
 *      level POSTed by the browser is a claim, not a measurement.
 *   3. The theme's own rule: nothing may depend on JavaScript to be readable.
 *      With scripts off, the form is thirty questions on one page and one
 *      submit button, and it works. JavaScript only upgrades that to one
 *      question per screen with a progress bar.
 *
 * ── How an answer survives the round trip ─────────────────────────────────
 *
 * Options are shuffled per render, so a radio's value cannot be the option's own
 * index — every correct answer would be `value="0"` and the key would be back in
 * the markup. Instead each radio carries its POSITION on screen, and the form
 * carries the whole render's position→option map plus a signature of it. The
 * signature is load-bearing: without it a visitor could rewrite the map so every
 * position points at option 0 and score thirty out of thirty.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * The route
 *
 * Declared in all three places the theme requires — zandi_register_routes(),
 * zandi_query_vars() and zandi_parse_request(), all in functions.php — and its
 * URLs are built by zandi_placement_url() below, which falls back to a query
 * string when Settings → پیوندهای یکتا is on «ساده».
 * ====================================================================== */

/**
 * The URL slug the test answers on.
 *
 * @return string
 */
function zandi_placement_slug() {
	return (string) apply_filters( 'zandi_placement_slug', 'placement' );
}

/**
 * Whether the current request is the placement test.
 *
 * @return bool
 */
function zandi_is_placement() {
	return (bool) get_query_var( 'zandi_placement' );
}

/**
 * The canonical URL for one of the test's three states.
 *
 * The states are query arguments rather than three routes because they are one
 * page in three moods, and because a single rewrite rule is one thing to keep
 * in step instead of three.
 *
 * @param string $state 'intro'|'start'|'result'.
 * @param string $token Result token, for the 'result' state.
 * @return string
 */
function zandi_placement_url( $state = 'intro', $token = '' ) {
	$url = zandi_pretty_permalinks()
		? home_url( '/' . zandi_placement_slug() . '/' )
		: home_url( '/?zandi_placement=1' );

	if ( 'start' === $state ) {
		return add_query_arg( 'start', '1', $url );
	}

	if ( 'result' === $state && $token ) {
		return add_query_arg( 'r', $token, $url );
	}

	if ( 'report' === $state ) {
		return zandi_placement_report_url( $token );
	}

	return $url;
}

/**
 * The URL of the printable report.
 *
 * Two ways in, because the two sources have different lifetimes. With a token
 * it reads the transient, which is how a student reaches it seconds after
 * finishing. Without one it reads their saved result, which is how the panel
 * can still link to it months later, long after that transient has expired.
 *
 * @param string $token Optional result token.
 * @return string
 */
function zandi_placement_report_url( $token = '' ) {
	$url = add_query_arg( 'report', '1', zandi_placement_url() );

	return $token ? add_query_arg( 'r', $token, $url ) : $url;
}

/**
 * Which of the four states the current request is asking for.
 *
 * `report` is tested first: it carries a token as well, so checking `r` before
 * it would send every report request to the result page.
 *
 * @return string 'intro'|'test'|'result'|'report'.
 */
function zandi_placement_state() {
	if ( isset( $_GET['report'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only state switch.
		return 'report';
	}

	if ( isset( $_GET['r'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only state switch; the token itself is the credential.
		return 'result';
	}

	if ( isset( $_GET['start'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only state switch.
		return 'test';
	}

	return 'intro';
}

/**
 * Whether the detailed report is behind an account.
 *
 * TRUE, and deliberately so. The level, the band breakdown and the course
 * recommendation stay free and instant for everyone — the specification is
 * explicit that hiding the *result* behind a form reads as a trap and costs
 * more trust than the numbers are worth, and that rule has not moved.
 *
 * What sits behind the account is the thing the specification says to ask for a
 * number in exchange for: the *extra*. A full question-by-question review, the
 * study plan and a document with the student's own name on it are worth making
 * an account for; a level is not.
 *
 * @return bool
 */
function zandi_placement_report_requires_account() {
	return (bool) apply_filters( 'zandi_placement_report_requires_account', true );
}

/**
 * The result the report should render, from whichever source has it.
 *
 * @return array<string,mixed>|null
 */
function zandi_placement_report_result() {
	/*
	 * Memoised. The document title filter asks for this on wp_head and the
	 * template asks again a moment later, and without the static that is two
	 * transient reads — two uncached database queries — for one page.
	 */
	static $result = false;

	if ( false !== $result ) {
		return $result;
	}

	$token = isset( $_GET['r'] ) ? sanitize_text_field( wp_unslash( $_GET['r'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The random token is itself the credential.

	if ( $token ) {
		$stashed = zandi_placement_fetch( $token );

		if ( $stashed ) {
			$result = $stashed;

			return $result;
		}
	}

	// The transient has expired, or there was never a token: fall back to what
	// is saved against the account. This is the path the panel link uses.
	$result = zandi_placement_latest();

	return $result;
}

/* =========================================================================
 * Access
 * ====================================================================== */

/**
 * Whether the test is behind a login.
 *
 * FALSE FOR NOW, DELIBERATELY. The page is open to everyone so it can be tested
 * end to end without an account. The plan is to close it to signed-in students
 * once the flow is signed off — at which point this returns true and the guard
 * below sends everyone else to /login/ with a return address.
 *
 * Nothing else needs to change: results already save to the account of whoever
 * is signed in, and the result page already invites anonymous visitors to make
 * one.
 *
 * @return bool
 */
function zandi_placement_requires_login() {
	return (bool) apply_filters( 'zandi_placement_requires_login', false );
}

/**
 * Whether to ask search engines to stay away.
 *
 * TRUE FOR NOW. The page is not linked from anywhere and is not announced, so
 * an indexed copy would be a half-finished page found by strangers.
 *
 * TO LAUNCH THE TEST, three deliberate steps:
 *   1. return false here (or filter `zandi_placement_noindex`),
 *   2. add «تعیین سطح رایگان» to zandi_navigation() in inc/content.php,
 *   3. decide whether zandi_placement_requires_login() should now be true.
 *
 * @return bool
 */
function zandi_placement_noindex() {
	return (bool) apply_filters( 'zandi_placement_noindex', true );
}

/**
 * Sends signed-out visitors to /login/ when the test is closed.
 *
 * Runs before the submit handler so a POST cannot slip past the gate either.
 *
 * @return void
 */
function zandi_placement_guard() {
	if ( ! zandi_is_placement() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	/*
	 * The report is the one state that needs an account. The return address is
	 * the report itself, so a student who signs up from here lands on their own
	 * document rather than on an empty panel.
	 */
	if ( 'report' === zandi_placement_state() && zandi_placement_report_requires_account() ) {
		$token = isset( $_GET['r'] ) ? sanitize_text_field( wp_unslash( $_GET['r'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; carried through the redirect.

		wp_safe_redirect( zandi_login_url( zandi_placement_report_url( $token ) ) );
		exit;
	}

	if ( ! zandi_placement_requires_login() ) {
		return;
	}

	wp_safe_redirect( zandi_login_url( zandi_placement_url() ) );
	exit;
}
add_action( 'template_redirect', 'zandi_placement_guard', 5 );

/* =========================================================================
 * Claiming an anonymous result
 *
 * The result page tells a signed-out visitor «حساب بسازی، نتیجه‌ت توی پنل ذخیره
 * می‌شه». Until this existed that was not true: zandi_placement_save() only ran
 * when someone was already signed in at submit time, so an anonymous sitting
 * lived in a transient and nothing ever moved it onto the account created ten
 * seconds later. The panel stayed empty and the promise was a lie.
 *
 * WHY A COOKIE AND NOT `redirect_to`. A redirect parameter would have to
 * survive the signup form, and when Digits is active the theme renders Digits'
 * OWN form — markup this theme cannot add a hidden field to, which per
 * CLAUDE.md is the intended production state. A cookie survives that, survives
 * any redirect chain the plugin invents, and also covers the student who signs
 * *in* rather than up. `redirect_to` alone does none of the three.
 * ====================================================================== */

/**
 * The cookie that remembers an unclaimed sitting.
 *
 * @return string
 */
function zandi_placement_cookie() {
	return 'zandi_placement_token';
}

/**
 * Remembers a token in the browser so a later signup can claim it.
 *
 * Only for signed-out visitors: a signed-in student's result is already saved
 * against their account by the time this would run.
 *
 * @param string $token Result token.
 * @return void
 */
function zandi_placement_remember( $token ) {
	if ( is_user_logged_in() || headers_sent() ) {
		return;
	}

	setcookie(
		zandi_placement_cookie(),
		$token,
		array(
			'expires'  => time() + zandi_placement_result_ttl(),
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Moves a remembered result onto the account that just appeared.
 *
 * Hooked to both `user_register` and `wp_login`, because the student may have
 * signed up or may have signed in to an account they already had. Either way
 * the sitting in front of them is theirs.
 *
 * Never overwrites a newer result: if the account already holds one taken after
 * this one, the cookie is stale and only gets cleared.
 *
 * @param int|string $user Either a user ID (user_register) or a login (wp_login).
 * @param WP_User    $wp_user Optional user object, passed by wp_login.
 * @return void
 */
function zandi_placement_claim( $user = 0, $wp_user = null ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading our own cookie, validated below.
	$token = isset( $_COOKIE[ zandi_placement_cookie() ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ zandi_placement_cookie() ] ) ) : '';

	if ( '' === $token ) {
		return;
	}

	/*
	 * `user_register` passes a user ID; `wp_login` passes a login string and
	 * the user object second. Duck-typed rather than `instanceof WP_User` so
	 * the branch is reachable from a test harness — and so a plugin firing
	 * `wp_login` with its own user-shaped object is still understood.
	 */
	$user_id = ( is_object( $wp_user ) && isset( $wp_user->ID ) ) ? (int) $wp_user->ID : (int) $user;

	if ( ! $user_id ) {
		return;
	}

	$result   = zandi_placement_fetch( $token );
	$existing = zandi_placement_latest( $user_id );

	/*
	 * `<=`, not `<`. Timestamps have one-second resolution, so a student who
	 * finishes the test and signs in immediately can produce a saved result and
	 * a cookied one stamped the same second — and with a strict comparison the
	 * sitting in front of them would lose the tie and be silently dropped. A tie
	 * means they are almost certainly the same sitting anyway, so saving is both
	 * correct and harmless. Only a genuinely older cookie is ignored.
	 */
	if ( $result && ( ! $existing || $existing['time'] <= $result['time'] ) ) {
		zandi_placement_save( $user_id, $result );
	}

	zandi_placement_forget();
}
add_action( 'user_register', 'zandi_placement_claim', 20 );
add_action( 'wp_login', 'zandi_placement_claim', 20, 2 );

/* =========================================================================
 * Coming back to the report after signing up
 *
 * `redirect_to` is not enough, and the reason is the same one that forced the
 * cookie above. When Digits is active, zandi_auth_form_markup() returns DIGITS'
 * form and the theme's own — the one carrying the hidden redirect_to field — is
 * never rendered at all. Digits then redirects wherever its own settings say,
 * which is the front page. A student pressed «دریافت گزارش کامل», signed up, and
 * landed on the homepage with no report and no explanation.
 *
 * So the destination is remembered in a cookie when they ARRIVE at the auth
 * page, and resumed on the first signed-in page view afterwards — wherever the
 * plugin happened to drop them. It works for any auth plugin, because it never
 * asks the plugin for anything.
 * ====================================================================== */

/**
 * The cookie that remembers where they were going.
 *
 * @return string
 */
function zandi_placement_intent_cookie() {
	return 'zandi_placement_intent';
}

/**
 * How long an interrupted journey is worth resuming.
 *
 * Long enough for an SMS code to arrive and be typed, short enough that
 * somebody who abandoned signup and came back later is not bounced somewhere
 * they have forgotten asking for.
 *
 * @return int Seconds.
 */
function zandi_placement_intent_ttl() {
	return (int) apply_filters( 'zandi_placement_intent_ttl', 30 * MINUTE_IN_SECONDS );
}

/**
 * The report URL an auth page was reached on the way to, if any.
 *
 * Only ever claims a destination that is this feature's own report. A
 * `redirect_to` pointing anywhere else belongs to whoever put it there.
 *
 * @return string Validated URL, or ''.
 */
function zandi_placement_auth_destination() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, and validated against the host below.
	$requested = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

	if ( '' === $requested || false === strpos( $requested, 'report=1' ) ) {
		return '';
	}

	// Same guard the auth pages use: never bounce anyone off this host.
	$safe = wp_validate_redirect( $requested, '' );

	return $safe ? $safe : '';
}

/**
 * Remembers the destination when a signed-out visitor lands on an auth page.
 *
 * @return void
 */
function zandi_placement_remember_intent() {
	if ( is_user_logged_in() || ! function_exists( 'zandi_account_route' ) ) {
		return;
	}

	$route = zandi_account_route();

	if ( 'login' !== $route && 'register' !== $route ) {
		return;
	}

	$destination = zandi_placement_auth_destination();

	if ( '' === $destination || headers_sent() ) {
		return;
	}

	setcookie(
		zandi_placement_intent_cookie(),
		$destination,
		array(
			'expires'  => time() + zandi_placement_intent_ttl(),
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}
add_action( 'template_redirect', 'zandi_placement_remember_intent', 4 );

/**
 * Sends a freshly signed-in student to the report they were after.
 *
 * Fires once and then clears, so nobody is bounced twice and nobody is bounced
 * on a journey they did not ask for.
 *
 * @return void
 */
function zandi_placement_resume_intent() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading our own cookie, validated below.
	$intent = isset( $_COOKIE[ zandi_placement_intent_cookie() ] ) ? esc_url_raw( wp_unslash( $_COOKIE[ zandi_placement_intent_cookie() ] ) ) : '';

	if ( '' === $intent || ! is_user_logged_in() ) {
		return;
	}

	/*
	 * Only ever on an ordinary page view. A redirect fired during a form POST,
	 * an AJAX call or a feed would swallow whatever that request was doing.
	 */
	if ( is_admin() || wp_doing_ajax() || is_feed() || 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' ) ) {
		return;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	$destination = wp_validate_redirect( $intent, '' );

	zandi_placement_forget_intent();

	if ( '' === $destination ) {
		return;
	}

	// Already there — clear the cookie and let the page render, or this is a
	// redirect loop.
	$here = home_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );

	if ( untrailingslashit( $here ) === untrailingslashit( $destination ) ) {
		return;
	}

	wp_safe_redirect( $destination );
	exit;
}
add_action( 'template_redirect', 'zandi_placement_resume_intent', 6 );

/**
 * Drops the destination cookie.
 *
 * @return void
 */
function zandi_placement_forget_intent() {
	unset( $_COOKIE[ zandi_placement_intent_cookie() ] );

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		zandi_placement_intent_cookie(),
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
 * Drops the cookie once its sitting has been claimed.
 *
 * @return void
 */
function zandi_placement_forget() {
	unset( $_COOKIE[ zandi_placement_cookie() ] );

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		zandi_placement_cookie(),
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

/* =========================================================================
 * The question bank
 * ====================================================================== */

/**
 * The decoded question bank.
 *
 * Memoised: a single render asks for it from the template, the partials and the
 * scorer, and re-reading a 16 KB file from disk each time is a cost with no
 * upside.
 *
 * Returns an empty array when the file is missing or malformed rather than
 * fataling — the page then draws an honest empty state and the rest of the site
 * is unaffected.
 *
 * @return array<string,mixed>
 */
function zandi_placement_data() {
	static $data = null;

	if ( null !== $data ) {
		return $data;
	}

	$data = array();
	$path = get_theme_file_path( 'inc/data/questions.json' );

	if ( ! file_exists( $path ) ) {
		return $data;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Bundled theme file, not a remote request.
	$decoded = json_decode( (string) file_get_contents( $path ), true );

	if ( is_array( $decoded ) && ! empty( $decoded['questions'] ) && ! empty( $decoded['bands'] ) ) {
		$data = $decoded;
	}

	return $data;
}

/**
 * Every question, in the order the bank lists them (easy to hard).
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_placement_questions() {
	$data = zandi_placement_data();

	return isset( $data['questions'] ) ? array_values( $data['questions'] ) : array();
}

/**
 * Every question keyed by its own id, for the scorer.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_placement_questions_by_id() {
	$indexed = array();

	foreach ( zandi_placement_questions() as $question ) {
		$indexed[ (int) $question['id'] ] = $question;
	}

	return $indexed;
}

/**
 * The bands, sorted by their declared order.
 *
 * THRESHOLDS ARE NEVER HARD-CODED. `mastery` and `partial` are read from this
 * array everywhere, so raising the A2 bar after the pilot is one edit to the
 * JSON and no code change at all.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_placement_bands() {
	$data  = zandi_placement_data();
	$bands = isset( $data['bands'] ) ? array_values( $data['bands'] ) : array();

	usort(
		$bands,
		static function ( $a, $b ) {
			return (int) $a['order'] <=> (int) $b['order'];
		}
	);

	return $bands;
}

/**
 * The eight result rows, keyed by id.
 *
 * `label` and `headline` are used as written — they describe the student, and
 * the owner approved that wording.
 *
 * THE `cta` FIELD IS DELIBERATELY NOT RENDERED. It offers a waiting list, a free
 * revision booklet and a free consultation, none of which the academy has: the
 * consultation form was removed on 30 July 2026 and there is no waiting list and
 * no booklet. Printing them would be inventing a product. What the result page
 * offers instead is in zandi_placement_recommendations(), and every line of it
 * points at a course that is on sale today or at /contact/.
 *
 * @return array<string,array<string,string>>
 */
function zandi_placement_result_rows() {
	$data = zandi_placement_data();
	$rows = array();

	foreach ( isset( $data['results'] ) ? $data['results'] : array() as $row ) {
		$rows[ $row['id'] ] = $row;
	}

	return $rows;
}

/* =========================================================================
 * Rendering helpers — direction, above all
 *
 * THE SINGLE MOST IMPORTANT TECHNICAL POINT ON THIS PAGE. A French sentence
 * dropped into a right-to-left paragraph is reordered by the bidi algorithm,
 * because its punctuation is direction-neutral and inherits the paragraph. The
 * full stop of «Je ne parle pas français.» jumps to the front of the line, and
 * an apostrophe or a slash in a French option splits it in half.
 * ====================================================================== */

/**
 * Whether a string should be laid out left to right.
 *
 * True for a run that contains Latin letters and no Persian or Arabic ones — a
 * whole French sentence, in other words, which needs the paragraph itself to be
 * LTR rather than an isolated span inside an RTL one.
 *
 * A Persian option such as «۸:۱۵» has no Latin letters and stays RTL. A mixed
 * string is neither, and goes through zandi_bidi() instead.
 *
 * @param string $text Option, stem or prompt.
 * @return bool
 */
function zandi_placement_is_french( $text ) {
	return (bool) preg_match( '/\p{Latin}/u', $text ) && ! preg_match( '/\p{Arabic}/u', $text );
}

/**
 * The direction attributes an element holding this text needs.
 *
 * Returned for the ELEMENT, not for a span inside it, and that distinction is
 * the whole point. Wrapping a French sentence in an isolated span inside a
 * right-to-left paragraph fixes the character order but leaves the paragraph
 * right-aligned, so a French sentence hangs off the wrong edge of its box.
 * Putting the direction on the block makes `text-align: start` resolve to left
 * for that block and to right for the Persian around it, with no physical
 * alignment anywhere and nothing for rtl.css to undo.
 *
 * @param string $text Raw text from the question bank.
 * @return string Attribute string, with a leading space, or ''.
 */
function zandi_placement_dir_attrs( $text ) {
	return zandi_placement_is_french( $text ) ? ' dir="ltr" lang="fr"' : '';
}

/**
 * Escapes a fragment of the question bank for output.
 *
 * A wholly French run is escaped and left to the `dir="ltr"` on its element. A
 * Persian run that carries a French island — «معنی « la sœur » چیست؟» — goes
 * through zandi_bidi(), which isolates the island so the bidi algorithm cannot
 * reorder it against the Persian around it.
 *
 * The blanks are styled, never replaced. `___` stays in the text, so the
 * sentence a screen reader announces is the sentence the bank wrote; the span
 * only lets CSS draw a ruled gap instead of three underscores.
 *
 * @param string $text Raw text from the question bank.
 * @return string Escaped HTML, safe to echo.
 */
function zandi_placement_content( $text ) {
	$text = (string) $text;

	if ( '' === trim( $text ) ) {
		return '';
	}

	$markup = zandi_placement_is_french( $text ) ? esc_html( $text ) : zandi_bidi( $text );

	return preg_replace( '/_{2,}/', '<span class="pt-blank">$0</span>', $markup );
}

/**
 * Renders a question stem as lines, one script to a line.
 *
 * Five of the thirty stems ask a Persian question about a French sentence —
 * «معنی « la sœur » چیست؟». Set as one line on a phone, the French island lands
 * wherever the wrap happens to put it and the reader has to follow two
 * directions at once inside a single sentence. The owner's verdict was that it
 * "was not fully readable", and they were right.
 *
 * So a guillemet-quoted French run becomes its own line:
 *
 *     معنی
 *     la sœur
 *     چیست؟
 *
 * The guillemets go with the split. Their only job was to mark where the French
 * started and stopped, and a line break marks that better than a pair of marks
 * the bidi algorithm can reposition anyway.
 *
 * NOTHING IS TRANSLATED, REORDERED OR REWORDED — this is layout. All five mixed
 * stems in the bank use guillemets, so the rule is driven by the data rather
 * than by one example, and the other twenty-five come back as one line exactly
 * as before. A quoted run that is not French is left inline with its marks, so
 * a future Persian quotation is not broken onto a line of its own.
 *
 * @param string $text Stem from the question bank.
 * @return string Escaped HTML, safe to echo.
 */
function zandi_placement_stem( $text ) {
	$parts  = preg_split( '/(«[^»]*»)/u', (string) $text, -1, PREG_SPLIT_DELIM_CAPTURE );
	$lines  = array();
	$buffer = '';

	foreach ( $parts as $part ) {
		$quoted = preg_match( '/^«(.*)»$/us', $part, $matches );
		$inner  = $quoted ? trim( $matches[1] ) : '';

		if ( $quoted && zandi_placement_is_french( $inner ) ) {
			if ( '' !== trim( $buffer ) ) {
				$lines[] = array( trim( $buffer ), false );
				$buffer  = '';
			}

			$lines[] = array( $inner, true );

			continue;
		}

		$buffer .= $part;
	}

	if ( '' !== trim( $buffer ) ) {
		$lines[] = array( trim( $buffer ), false );
	}

	$markup = '';

	foreach ( $lines as $line ) {
		list( $content, $is_french ) = $line;

		$markup .= sprintf(
			'<span class="pt-q__line%1$s"%2$s>%3$s</span>',
			$is_french ? ' pt-q__line--fr' : '',
			zandi_placement_dir_attrs( $content ),
			zandi_placement_content( $content )
		);
	}

	return $markup;
}

/**
 * The icon for one of the three skills.
 *
 * Looked up at render time rather than stored with the score, so a result
 * already sitting in a student's user meta picks up a changed icon without a
 * migration.
 *
 * @param string $skill Skill key from the question bank.
 * @return string Icon name from inc/icons.php.
 */
function zandi_placement_skill_icon( $skill ) {
	$icons = apply_filters(
		'zandi_placement_skill_icons',
		array(
			'grammaire'            => 'layers',
			'lexique'              => 'tag',
			'compréhension écrite' => 'clipboard',
		)
	);

	return isset( $icons[ $skill ] ) ? $icons[ $skill ] : 'target';
}

/**
 * Whether a result's label is a bare CEFR code rather than a sentence.
 *
 * Seven of the eight are codes — «A1», «A1+», «B2». The eighth is
 * «هنوز به A1 نرسیده», which is a sentence, and the two cannot be set the same
 * way: one is display type in a chip, the other has to wrap.
 *
 * @param string $label Result label.
 * @return bool
 */
function zandi_placement_level_is_code( $label ) {
	return (bool) preg_match( '/^[A-Za-z]{1,3}[0-9]?\+?$/', (string) $label );
}

/**
 * A result's label, escaped and pointed the right way.
 *
 * «A1+» IS THE REASON THIS EXISTS. `+` is a direction-neutral character, so in a
 * right-to-left paragraph the bidi algorithm puts it on the left of the Latin
 * run beside it and the chip reads «+A1». zandi_bidi() does not catch it either:
 * its chain has to end on a letter or digit, precisely so that a full stop
 * closing a Persian sentence is left outside the isolated run — which means a
 * trailing `+` is left outside too. A code is therefore isolated whole, and only
 * the sentence goes through zandi_bidi().
 *
 * @param string $label Result label.
 * @return string Escaped HTML, safe to echo.
 */
function zandi_placement_level_label( $label ) {
	if ( zandi_placement_level_is_code( $label ) ) {
		return '<span dir="ltr">' . esc_html( $label ) . '</span>';
	}

	return zandi_bidi( $label );
}

/**
 * A date in the Persian calendar, for the panel.
 *
 * Same approach as the footer's copyright year: `intl` when it is available,
 * and a localised Gregorian date when it is not, rather than shipping a
 * conversion table.
 *
 * @param int $timestamp Unix timestamp.
 * @return string
 */
function zandi_placement_date( $timestamp ) {
	$timestamp = (int) $timestamp;

	if ( ! $timestamp ) {
		return '';
	}

	if ( class_exists( 'IntlDateFormatter' ) ) {
		$date = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );

		$formatter = new IntlDateFormatter(
			'fa_IR@calendar=persian',
			IntlDateFormatter::LONG,
			IntlDateFormatter::NONE,
			$date->getTimezone(),
			IntlDateFormatter::TRADITIONAL
		);

		$formatted = $formatter->format( $date );

		if ( false !== $formatted ) {
			return $formatted;
		}
	}

	return zandi_fa_digits( wp_date( 'Y/m/d', $timestamp ) );
}

/* =========================================================================
 * The shuffle, and the signature that makes it stick
 * ====================================================================== */

/**
 * Builds one render's option order.
 *
 * The real options are shuffled; «نمی‌دانم» is pinned to the end and never
 * moves, so a student learns where it lives and can reach for it without
 * reading four options again. That pinning is the specification's rule, not a
 * detail — the option only does its job if it is predictable.
 *
 * @return array<int,array<int,int>> Question id → list of option indices, in
 *                                   the order they will be drawn.
 */
function zandi_placement_build_order() {
	$order = array();

	foreach ( zandi_placement_questions() as $question ) {
		$id      = (int) $question['id'];
		$idk     = isset( $question['idkIndex'] ) ? (int) $question['idkIndex'] : count( $question['options'] ) - 1;
		$indices = range( 0, count( $question['options'] ) - 1 );

		$real = array_values( array_diff( $indices, array( $idk ) ) );

		shuffle( $real );

		$real[]        = $idk;
		$order[ $id ] = $real;
	}

	return $order;
}

/**
 * Flattens an order map into the string that travels in the form.
 *
 * One digit per option, four per question, in question order: "2013" means the
 * first option drawn is option 2, then 0, then 1, then «نمی‌دانم» at 3.
 *
 * Single digits are safe because no question has more than ten options, and the
 * bank's shape (three real plus one) is fixed by the specification.
 *
 * @param array<int,array<int,int>> $order Question id → option indices.
 * @return string
 */
function zandi_placement_encode_order( $order ) {
	$encoded = '';

	foreach ( zandi_placement_questions() as $question ) {
		$id = (int) $question['id'];

		foreach ( isset( $order[ $id ] ) ? $order[ $id ] : array() as $index ) {
			$encoded .= (string) (int) $index;
		}
	}

	return $encoded;
}

/**
 * Reads a posted order string back into a map.
 *
 * @param string $encoded Flattened order.
 * @return array<int,array<int,int>> Question id → option indices.
 */
function zandi_placement_decode_order( $encoded ) {
	$order  = array();
	$cursor = 0;

	foreach ( zandi_placement_questions() as $question ) {
		$id      = (int) $question['id'];
		$count   = count( $question['options'] );
		$slice   = substr( $encoded, $cursor, $count );
		$cursor += $count;

		if ( strlen( (string) $slice ) !== $count ) {
			return array();
		}

		$order[ $id ] = array_map( 'intval', str_split( $slice ) );
	}

	return $order;
}

/**
 * Signs an order string with the site's own salts.
 *
 * WHY THIS EXISTS. The radios carry positions on screen, not option indices, so
 * the server needs the map to know what was chosen. If the map were unsigned, a
 * visitor could replace it with one that points every position at option 0 —
 * which is always the correct answer in the bank — and score thirty out of
 * thirty without reading a question. wp_hash() keys on wp-config.php's salts, so
 * the map cannot be rewritten without them.
 *
 * @param string $encoded Flattened order.
 * @return string
 */
function zandi_placement_sign_order( $encoded ) {
	return wp_hash( 'zandi_placement|' . $encoded );
}

/* =========================================================================
 * Scoring — section 4 of the specification, in PHP
 * ====================================================================== */

/**
 * Scores a completed test.
 *
 * The algorithm is the specification's, step for step:
 *
 *   1. Count correct answers per band. «نمی‌دانم» and a wrong answer both score
 *      zero — there is no negative marking, because this is a placement test
 *      and negative marking depresses completion.
 *   2. NO SKIPPING. The level is the highest band that is mastered *and* whose
 *      lower bands are all mastered. Someone who passes A1 and B1 but fails A2
 *      is not B1: the B1 answers were probably luck.
 *   3. Half levels. Falling short of the next band but clearing its `partial`
 *      threshold earns a `+`.
 *   4. Confidence. Sitting exactly one answer below the next band's threshold
 *      while never once saying «نمی‌دانم» is the signature of guessing, so the
 *      half level is withheld.
 *
 * @param array<int,int> $answers Question id → chosen option index.
 * @param array<string,mixed> $context Optional. 'duration' in seconds.
 * @return array<string,mixed>
 */
function zandi_placement_score( $answers, $context = array() ) {
	$questions = zandi_placement_questions_by_id();
	$bands     = zandi_placement_bands();
	$scores    = array();

	foreach ( $bands as $band ) {
		$correct  = 0;
		$idk      = 0;
		$answered = 0;

		foreach ( $band['items'] as $item ) {
			$id = (int) $item;

			if ( ! isset( $questions[ $id ], $answers[ $id ] ) ) {
				continue;
			}

			++$answered;

			if ( (int) $answers[ $id ] === (int) $questions[ $id ]['answer'] ) {
				++$correct;
			} elseif ( (int) $answers[ $id ] === (int) $questions[ $id ]['idkIndex'] ) {
				++$idk;
			}
		}

		$scores[] = array(
			'id'       => $band['id'],
			'count'    => (int) $band['count'],
			'mastery'  => (int) $band['mastery'],
			'partial'  => (int) $band['partial'],
			'correct'  => $correct,
			'idk'      => $idk,
			'answered' => $answered,
			'passed'   => $correct >= (int) $band['mastery'],
		);
	}

	$total    = count( $scores );
	$mastered = 0;

	// Step 2 — walk up only while every band so far has been mastered.
	while ( $mastered < $total && $scores[ $mastered ]['correct'] >= $scores[ $mastered ]['mastery'] ) {
		++$mastered;
	}

	if ( 0 === $mastered ) {
		$level = 'pre-A1';
	} elseif ( $mastered === $total ) {
		$level = $scores[ $total - 1 ]['id'];
	} else {
		$base = $scores[ $mastered - 1 ]['id'];
		$next = $scores[ $mastered ];

		$half = $next['correct'] >= $next['partial'];

		// Step 4 — one short of the bar and never once unsure reads as guessing.
		if ( $next['correct'] === $next['mastery'] - 1 && 0 === $next['idk'] ) {
			$half = false;
		}

		$level = $half ? $base . '+' : $base;
	}

	/*
	 * Gap detection. The specification's sample returns early for the top and
	 * bottom outcomes and so never computes this for them; the same expression is
	 * evaluated here in every case, because "you did not clear A1 but you cleared
	 * A2" is exactly as worth saying as the middle cases, and above the top band
	 * the slice is empty and the answer is false anyway.
	 */
	$gap      = false;
	$gap_band = '';

	for ( $index = $mastered + 1; $index < $total; $index++ ) {
		if ( $scores[ $index ]['passed'] ) {
			$gap = true;
		}
	}

	if ( $gap && isset( $scores[ $mastered ] ) ) {
		$gap_band = $scores[ $mastered ]['id'];
	}

	return array(
		'level'    => $level,
		'mastered' => $mastered,
		'bands'    => $scores,
		'skills'   => zandi_placement_skill_scores( $answers ),
		'gap'      => $gap,
		'gap_band' => $gap_band,
		'idk'      => array_sum( wp_list_pluck( $scores, 'idk' ) ),
		'correct'  => array_sum( wp_list_pluck( $scores, 'correct' ) ),
		'answered' => array_sum( wp_list_pluck( $scores, 'answered' ) ),
		'total'    => count( $questions ),
		'duration' => isset( $context['duration'] ) ? (int) $context['duration'] : 0,
		'time'     => time(),
		'answers'  => $answers,
	);
}

/**
 * The three skill labels, in the order the result page draws them.
 *
 * The keys are the bank's own French skill names.
 *
 * @return array<string,string>
 */
function zandi_placement_skills() {
	return apply_filters(
		'zandi_placement_skills',
		array(
			'grammaire'            => 'گرامر',
			'lexique'              => 'واژگان',
			'compréhension écrite' => 'درک مطلب',
		)
	);
}

/**
 * Per-skill scores.
 *
 * A single level is a blunt answer. Knowing that the grammar is at 80% and the
 * reading at 25% tells a student what to work on, which is the part they can
 * act on tomorrow.
 *
 * @param array<int,int> $answers Question id → chosen option index.
 * @return array<int,array<string,mixed>>
 */
function zandi_placement_skill_scores( $answers ) {
	$labels = zandi_placement_skills();
	$totals = array();

	foreach ( array_keys( $labels ) as $skill ) {
		$totals[ $skill ] = array(
			'correct' => 0,
			'total'   => 0,
		);
	}

	foreach ( zandi_placement_questions() as $question ) {
		$skill = isset( $question['skill'] ) ? $question['skill'] : '';

		if ( ! isset( $totals[ $skill ] ) ) {
			continue;
		}

		++$totals[ $skill ]['total'];

		$id = (int) $question['id'];

		if ( isset( $answers[ $id ] ) && (int) $answers[ $id ] === (int) $question['answer'] ) {
			++$totals[ $skill ]['correct'];
		}
	}

	$scores = array();

	foreach ( $totals as $skill => $tally ) {
		$scores[] = array(
			'id'      => $skill,
			'label'   => $labels[ $skill ],
			'correct' => $tally['correct'],
			'total'   => $tally['total'],
			'percent' => $tally['total'] ? (int) round( ( $tally['correct'] / $tally['total'] ) * 100 ) : 0,
		);
	}

	return $scores;
}

/* =========================================================================
 * Storage
 * ====================================================================== */

/**
 * How long a result stays readable at its own URL.
 *
 * @return int Seconds.
 */
function zandi_placement_result_ttl() {
	return (int) apply_filters( 'zandi_placement_result_ttl', DAY_IN_SECONDS );
}

/**
 * Stashes a result and returns the token that reads it back.
 *
 * The result is handed to the browser through a redirect rather than rendered
 * straight out of the POST, so refreshing the page does not re-submit the test
 * and the back button behaves. The token is random, so one student's result URL
 * is not another's.
 *
 * @param array<string,mixed> $result Scored result.
 * @return string Token.
 */
function zandi_placement_stash( $result ) {
	$token = wp_generate_password( 20, false );

	set_transient( 'zandi_placement_' . $token, $result, zandi_placement_result_ttl() );

	// So an account made in the next few minutes can claim this sitting.
	zandi_placement_remember( $token );

	return $token;
}

/**
 * Reads a stashed result.
 *
 * @param string $token Token from the URL.
 * @return array<string,mixed>|null
 */
function zandi_placement_fetch( $token ) {
	$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );

	if ( '' === $token ) {
		return null;
	}

	$result = get_transient( 'zandi_placement_' . $token );

	return is_array( $result ) ? $result : null;
}

/**
 * Saves a result to a student's account.
 *
 * Two records are kept. The latest is what the panel shows; the history is
 * capped so a student who retakes the test weekly cannot grow their own user
 * meta without bound.
 *
 * EVERY ANSWER IS STORED, not just the level. After two or three hundred sittings
 * that is what shows which questions are useless — the ones everybody gets right,
 * the ones everybody gets wrong, and the distractors nobody ever picks — so the
 * bank can be improved instead of guessed at.
 *
 * TODO: an aggregate store for item analysis across all sitters, including the
 * ones with no account. `zandi_placement_completed` is the hook for it.
 *
 * @param int                 $user_id Student.
 * @param array<string,mixed> $result  Scored result.
 * @return void
 */
function zandi_placement_save( $user_id, $result ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return;
	}

	update_user_meta( $user_id, 'zandi_placement_result', $result );

	$history   = get_user_meta( $user_id, 'zandi_placement_history', true );
	$history   = is_array( $history ) ? $history : array();
	$history[] = array(
		'level' => $result['level'],
		'time'  => $result['time'],
	);

	update_user_meta( $user_id, 'zandi_placement_history', array_slice( $history, -10 ) );
}

/**
 * The student's most recent result, if they have one.
 *
 * @param int $user_id Optional. Defaults to the current user.
 * @return array<string,mixed>|null
 */
function zandi_placement_latest( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	if ( ! $user_id ) {
		return null;
	}

	$result = get_user_meta( $user_id, 'zandi_placement_result', true );

	return ( is_array( $result ) && ! empty( $result['level'] ) ) ? $result : null;
}

/* =========================================================================
 * The submit handler
 *
 * Posts to the page itself and is processed on `template_redirect`, the way the
 * auth forms are and the way core's own wp-login.php is — not admin-post.php,
 * because a failed submission has to come back to this page with a message.
 * ====================================================================== */

/**
 * Scores a submitted test and redirects to its result.
 *
 * @return void
 */
function zandi_placement_handle_submit() {
	if ( ! zandi_is_placement() || 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return;
	}

	if ( ! isset( $_POST['zandi_placement_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['zandi_placement_nonce'] ) );

	// An expired nonce is almost always a test left open for a day, not an
	// attack, so it earns a plain explanation rather than a wp_die().
	if ( ! wp_verify_nonce( $nonce, 'zandi_placement' ) ) {
		wp_safe_redirect( add_query_arg( 'e', 'expired', zandi_placement_url() ) );
		exit;
	}

	$encoded = isset( $_POST['zandi_placement_order'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_placement_order'] ) ) : '';
	$posted  = isset( $_POST['zandi_placement_sig'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_placement_sig'] ) ) : '';

	if ( ! preg_match( '/^[0-9]+$/', $encoded ) || ! hash_equals( zandi_placement_sign_order( $encoded ), $posted ) ) {
		wp_safe_redirect( add_query_arg( 'e', 'invalid', zandi_placement_url() ) );
		exit;
	}

	$order = zandi_placement_decode_order( $encoded );

	if ( ! $order ) {
		wp_safe_redirect( add_query_arg( 'e', 'invalid', zandi_placement_url() ) );
		exit;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
	$raw     = isset( $_POST['q'] ) && is_array( $_POST['q'] ) ? wp_unslash( $_POST['q'] ) : array();
	$answers = array();

	foreach ( $order as $id => $positions ) {
		if ( ! isset( $raw[ $id ] ) || '' === $raw[ $id ] ) {
			continue; // Left blank; scores as neither correct nor «نمی‌دانم».
		}

		$position = (int) $raw[ $id ];

		if ( ! isset( $positions[ $position ] ) ) {
			continue;
		}

		// The radio carried a position on screen; this is the option it drew.
		$answers[ (int) $id ] = (int) $positions[ $position ];
	}

	$duration = isset( $_POST['zandi_placement_duration'] ) ? absint( wp_unslash( $_POST['zandi_placement_duration'] ) ) : 0;

	// A tab left open overnight would otherwise record a nine-hour sitting.
	$duration = min( $duration, HOUR_IN_SECONDS );

	$result  = zandi_placement_score( $answers, array( 'duration' => $duration ) );
	$user_id = get_current_user_id();

	if ( $user_id ) {
		zandi_placement_save( $user_id, $result );
	}

	/**
	 * Fires once a placement test has been scored.
	 *
	 * The seam for item analysis: every answer is in $result['answers'].
	 *
	 * @param array<string,mixed> $result  Scored result.
	 * @param int                 $user_id Student, or 0 when signed out.
	 */
	do_action( 'zandi_placement_completed', $result, $user_id );

	wp_safe_redirect( zandi_placement_url( 'result', zandi_placement_stash( $result ) ) );
	exit;
}
add_action( 'template_redirect', 'zandi_placement_handle_submit' );

/* =========================================================================
 * Copy
 *
 * Every string behind a filter, the same as inc/content.php, so this is the seam
 * for the Customizer later. No support channel is named anywhere: «از صفحه تماس
 * بپرس» and zandi_support_url() carry that, and only /contact/ knows what the
 * channel actually is.
 * ====================================================================== */

/**
 * Copy for the page in all three of its states.
 *
 * @return array<string,mixed>
 */
function zandi_placement_copy() {
	$data      = zandi_placement_data();
	$questions = count( zandi_placement_questions() );
	$minutes   = isset( $data['estimatedMinutes'] ) ? (int) $data['estimatedMinutes'] : 12;

	return apply_filters(
		'zandi_placement_copy',
		array(
			'nav_label' => 'تعیین سطح رایگان',

			/* ---- intro ---- */
			'eyebrow'   => 'رایگان · بدون ثبت‌نام',
			'title'     => 'تعیین سطح زبان فرانسه',
			'lead'      => sprintf(
				'%s سوال، حدود %s دقیقه. آخرش می‌فهمی کجای مسیر A1 تا B2 ایستادی و منطقی‌ترین جا برای شروع کدوم دوره‌ست.',
				zandi_fa_digits( $questions ),
				zandi_fa_digits( $minutes )
			),

			'rules_title' => 'قبل از شروع، چند تا نکته',
			'rules'       => array(
				array(
					'icon'  => 'clock',
					'title' => sprintf( '%s سوال، حدود %s دقیقه', zandi_fa_digits( $questions ), zandi_fa_digits( $minutes ) ),
					'body'  => 'یه سوال توی هر صفحه. تایمر نداری و کسی وقت نمی‌گیره، پس عجله نکن — ولی روی یه سوال هم گیر نکن.',
				),
				array(
					'icon'  => 'layers',
					'title' => 'از A1 شروع می‌شه و تا B2 می‌ره',
					'body'  => 'سوال‌ها از آسون به سخت چیده شدن. هر چی جلوتر بری سخت‌تر می‌شه و این کاملاً طبیعیه؛ قرار نیست همه‌شون رو بلد باشی.',
				),
				array(
					'icon'  => 'lifebuoy',
					'title' => 'بلد نیستی؟ حدس نزن',
					'body'  => 'گزینهٔ «نمی‌دانم» برای همینه و همیشه آخرین گزینه‌ست. نمرهٔ منفی نداری، و هر بار که به‌جای حدس زدن ازش استفاده کنی نتیجه دقیق‌تر درمی‌آد.',
				),
				array(
					'icon'  => 'globe',
					'title' => 'صورت سوال‌ها از وسط، فرانسه می‌شه',
					'body'  => 'تا سوال ۱۶ فارسیه و از ۱۷ به بعد فرانسه. خودِ همین هم بخشی از سنجشه. درست قبلش بهت خبر می‌دم.',
				),
				array(
					'icon'  => 'route',
					'title' => 'راه برگشت نداری',
					'body'  => 'بعد از رد شدن از یه سوال نمی‌شه برگشت عقب. یه بار با خیال راحت انتخاب کن و برو جلو.',
				),
				array(
					'icon'  => 'sparkles',
					'title' => 'نتیجه همون‌جا، رایگان',
					'body'  => 'سطحت رو بلافاصله می‌بینی. هیچ فرمی، شماره‌ای و ثبت‌نامی جلوی نتیجه نیست.',
				),
			),

			'honesty_title' => 'نتیجه یه تخمین خوبه، نه یه مدرک',
			'honesty_body'  => 'این آزمون گرامر، واژگان و درک مطلبِ نوشتاری رو می‌سنجه. شنیدن، حرف زدن و نوشتن توش نیست، پس نتیجه‌ش مو‌به‌مو با یه آزمون رسمی مثل DELF یکی درنمی‌آد و ممکنه یه نیم‌سطح این‌ور و اون‌ور بشه. برای کاری که ازش می‌خوایم — اینکه بفهمی از کدوم دوره شروع کنی — کافیه.',

			'fair_title' => 'یه خواهش کوچیک',
			'fair_body'  => 'این امتحان نیست، هیچ نمره‌ای جایی ثبت نمی‌شه و کسی قرار نیست قضاوتت کنه. ولی اگر وسط آزمون سراغ گوگل و مترجم بری، نتیجه یه سطح بالاتر از خودت درمی‌آد و آخرش توی دوره‌ای می‌شینی که برات زوده — تنها کسی که ضرر می‌کنه خودتی. همون چیزی که واقعاً بلدی رو جواب بده؛ بقیه‌ش با من.',

			'sources_title' => 'این آزمون از روی چی ساخته شده؟',
			'sources_lead'  => 'سطح‌بندی سوال‌ها، تعداد گزینه‌ها و آستانه‌های نمره‌دهی هیچ‌کدوم سلیقه‌ای نیستن. از این منابع درآمدن:',

			'start'      => 'شروع آزمون',
			'start_note' => 'با زدن این دکمه آزمون شروع می‌شه.',

			/* ---- test ---- */
			'progress'      => 'سوال %1$s از %2$s',
			'next'          => 'بعدی',
			'submit'        => 'دیدن نتیجه',
			'switch_title'  => 'از این‌جا صورت سوال‌ها فرانسه می‌شه',
			'switch_body'   => 'تا حالا سوال‌ها به فارسی پرسیده می‌شد. از این‌جا به بعد خودِ صورت سوال هم فرانسه‌ست — این هم بخشی از سنجشه. هر جا رو نفهمیدی، «نمی‌دانم» رو بزن.',
			'switch_action' => 'ادامه می‌دم',
			'nojs_note'     => 'جاوااسکریپت مرورگرت خاموشه، برای همین همهٔ سوال‌ها یک‌جا نشون داده می‌شن. جواب‌ها رو بده و آخر صفحه «دیدن نتیجه» رو بزن.',
			'unanswered'    => 'یه گزینه انتخاب کن تا بریم سوال بعد.',

			/* ---- result ---- */
			'result_eyebrow' => 'نتیجهٔ تعیین سطح',
			'result_title'   => 'سطح تو',
			'result_correct' => '%1$s جواب درست از %2$s سوال',
			'result_idk'     => '%s بار «نمی‌دانم» زدی — همین باعث می‌شه نتیجه قابل‌اعتمادتر باشه.',
			'result_idk_zero' => 'هیچ‌وقت «نمی‌دانم» رو نزدی. اگر جایی حدس زدی، نتیجه ممکنه کمی بالاتر از سطح واقعیت باشه.',
			'result_time'    => 'در %s دقیقه',
			'bands_title'    => 'هر بخش چطور بود',
			'bands_note'     => 'سطح نهایی از بالاترین بخشی می‌آد که خودش و همهٔ بخش‌های پایین‌ترش رو رد کرده باشی. برای همین یه نمرهٔ خوب توی یه بخش بالاتر، به‌تنهایی سطحت رو بالا نمی‌بره.',
			'mastery_label'  => 'حد نصاب: %s',
			'skills_title'   => 'به تفکیک مهارت',
			'skills_note'    => 'این سه‌تا فقط مهارت‌های نوشتاری‌ان. شنیدن و حرف زدن توی این آزمون سنجیده نمی‌شه.',
			'gap_title'      => 'یه چیزی توی جواب‌هات به چشم می‌آد',
			'gap_body'       => 'توی بخش %1$s حد نصاب رو نگرفتی، ولی توی بخش‌های بالاتر خوب جواب دادی. یعنی احتمالاً یه شکاف توی %1$s داری که بهتره پرش کنی — وگرنه بعداً همون‌جا گیر می‌کنی.',
			'saved'          => 'این نتیجه توی پنلت ذخیره شد.',
			'save_prompt'    => 'حساب بسازی، همین نتیجه توی پنلت ذخیره می‌شه و گزارش کاملش رو هم می‌گیری.',
			'save_action'    => 'ساختن حساب',
			'retake'         => 'دوباره آزمون بده',

			/* ---- sharing ---- */
			'share'          => 'برای دوستت بفرست',
			'shared'         => 'کپی شد',
			'share_hint'     => 'متن نتیجه‌ات کپی می‌شه — بفرستش هر جا که دوست داری.',
			'share_intro'    => '🇫🇷 آزمون تعیین سطح زبان فرانسهٔ آکادمی زندی رو دادم!',
			'share_level'    => '📊 سطحم شد: %s',
			'share_score'    => '✅ %1$s جواب درست از %2$s سوال',
			'share_skills'   => '📚 %s',
			'share_time'     => '⏱️ توی %s دقیقه',
			'share_cta'      => "۳۰ سوال · حدود ۱۰ دقیقه · رایگان و بدون ثبت‌نام\n🎯 نتیجه رو همون لحظه می‌بینی و می‌فهمی از کدوم دوره باید شروع کنی.\n\nتو هم سطحت رو بسنج 👇",
			'expired_title'  => 'این نتیجه دیگه در دسترس نیست',
			'expired_body'   => 'نتیجه‌ها یک روز نگه داشته می‌شن. آزمون رو دوباره بده — کمتر از ۱۰ دقیقه طول می‌کشه.',
			'invalid_body'   => 'جواب‌ها درست به دستم نرسید. یه بار دیگه آزمون رو بده.',

			/* ---- the report ---- */
			'report_cta'          => 'دریافت گزارش کامل',
			'report_cta_hint'     => 'یک گزارش چندصفحه‌ای با مرور تک‌تک سوال‌ها و برنامهٔ مرور. برای دریافتش باید حساب داشته باشی.',
			'auth_notice_title'   => 'برای دیدن گزارش کامل، اول حسابت رو بساز',
			'auth_notice_body'    => 'نتیجه‌ات از بین نمی‌ره — با ساختن حساب همین‌جا ذخیره می‌شه، بعد از ثبت‌نام مستقیم می‌ری سراغ گزارش، و هر وقت بعداً خواستی از پنلت می‌بینیش.',
			'report_title'        => 'گزارش تعیین سطح زبان فرانسه',
			'report_subtitle'     => 'بر اساس چارچوب مرجع اروپایی برای زبان‌ها (CECRL)',
			'report_print'        => 'چاپ یا ذخیره به‌صورت PDF',
			'report_print_hint'   => 'در پنجرهٔ چاپ، مقصد را روی «ذخیره به‌صورت PDF» بگذار.',
			'report_for'          => 'صادرشده برای',
			'report_anon'         => 'دانشجوی آکادمی زندی',
			'report_date'         => 'تاریخ آزمون',
			'report_ref'          => 'شمارهٔ گزارش',
			'report_duration'     => 'مدت آزمون',
			'report_minutes'      => '%s دقیقه',
			'report_summary'      => 'خلاصهٔ نتیجه',
			'report_review'       => 'مرور سوال‌به‌سوال',
			'report_review_lead'  => 'هر سی سوال، با جوابی که دادی و جواب درست. سوال‌هایی که درست جواب دادی هم هستند تا بتوانی کل آزمون را دوره کنی.',
			'report_plan'         => 'چی را مرور کن',
			'report_plan_lead'    => 'این فهرست از روی همان سوال‌هایی ساخته شده که نگرفتی — نه چیز دیگری. از بالا شروع کن؛ نکته‌های سطح پایین‌تر پیش‌نیاز بقیه‌اند.',
			'report_plan_empty'   => 'در هیچ بخشی نکتهٔ جامانده‌ای نداری. کل آزمون را درست جواب دادی.',
			'report_method'       => 'روش نمره‌دهی',
			'report_sources'      => 'منابع',
			'report_limits'       => 'این گزارش چه چیزی نیست',
			'report_q'            => 'سوال %s',
			'report_your_answer'  => 'جواب تو',
			'report_right_answer' => 'جواب درست',
			'report_no_answer'    => 'بی‌جواب',
			'report_outcome'      => array(
				'correct' => 'درست',
				'wrong'   => 'نادرست',
				'idk'     => 'نمی‌دانم',
				'blank'   => 'بی‌جواب',
			),
			'report_threshold'    => 'حد نصاب %1$s از %2$s',

			/* ---- panel ---- */
			'panel_title'   => 'تعیین سطح',
			'panel_date'    => 'آزمون %s',
			'panel_action'  => 'دیدن دوره‌های پیشنهادی',
			'panel_retake'  => 'دوباره آزمون بده',

			/* ---- empty ---- */
			'missing_title' => 'آزمون تعیین سطح فعلاً در دسترس نیست',
			'missing_body'  => 'بانک سوال روی سرور پیدا نشد. اگر مدیر سایتی، فایل inc/data/questions.json را بررسی کن.',
		)
	);
}

/* =========================================================================
 * The report's own data
 * ====================================================================== */

/**
 * Every question with what the student did to it.
 *
 * THIS PUBLISHES THE ANSWER KEY, and that is the owner's decision, taken with
 * the trade-off in front of them. It is why the report needs an account, and it
 * is why doubling the bank to sixty and drawing thirty at random — the
 * specification's own next step — stops being a nice-to-have: today anyone with
 * a free account can read all thirty answers and retake for a perfect score.
 *
 * `focus` is the field that makes this worth printing. Every question carries
 * one — «subjonctif présent après « il faut que »» — so the review names the
 * grammar point rather than just marking a row wrong.
 *
 * @param array<string,mixed> $result Scored result.
 * @return array<int,array<string,mixed>>
 */
function zandi_placement_review( $result ) {
	$questions = zandi_placement_questions();
	$answers   = isset( $result['answers'] ) ? (array) $result['answers'] : array();
	$rows      = array();

	foreach ( $questions as $index => $question ) {
		$id     = (int) $question['id'];
		$answer = (int) $question['answer'];
		$idk    = isset( $question['idkIndex'] ) ? (int) $question['idkIndex'] : null;

		if ( ! isset( $answers[ $id ] ) ) {
			$outcome = 'blank';
			$chosen  = null;
		} else {
			$chosen = (int) $answers[ $id ];

			if ( $chosen === $answer ) {
				$outcome = 'correct';
			} elseif ( $chosen === $idk ) {
				$outcome = 'idk';
			} else {
				$outcome = 'wrong';
			}
		}

		$rows[] = array(
			'number'  => $index + 1,
			'id'      => $id,
			'band'    => $question['band'],
			'skill'   => $question['skill'],
			'focus'   => isset( $question['focus'] ) ? $question['focus'] : '',
			'stem'    => $question['stem'],
			'prompt'  => isset( $question['prompt'] ) ? $question['prompt'] : '',
			'passage' => isset( $question['passage'] ) ? $question['passage'] : '',
			'chosen'  => ( null !== $chosen && isset( $question['options'][ $chosen ] ) ) ? $question['options'][ $chosen ] : '',
			'correct' => isset( $question['options'][ $answer ] ) ? $question['options'][ $answer ] : '',
			'outcome' => $outcome,
		);
	}

	return $rows;
}

/**
 * What to go and revise, grouped by band.
 *
 * The specification asks for a «برنامهٔ مطالعهٔ شخصی». This is it, and it is
 * built entirely from the student's own answers: the `focus` of every question
 * they missed or skipped, in band order, so the earliest gaps come first. A
 * band they cleared outright is left out — there is nothing to say about it.
 *
 * NOTHING HERE IS INVENTED. No lesson numbers, no week-by-week schedule, no
 * promises about how long anything takes. Those would be made up, and this
 * theme does not do that.
 *
 * @param array<string,mixed> $result Scored result.
 * @return array<int,array<string,mixed>> Bands, each with its missed topics.
 */
function zandi_placement_study_plan( $result ) {
	$review = zandi_placement_review( $result );
	$labels = zandi_placement_skills();
	$bands  = array();

	foreach ( $review as $row ) {
		if ( 'correct' === $row['outcome'] || '' === $row['focus'] ) {
			continue;
		}

		if ( ! isset( $bands[ $row['band'] ] ) ) {
			$bands[ $row['band'] ] = array(
				'id'     => $row['band'],
				'topics' => array(),
			);
		}

		$bands[ $row['band'] ]['topics'][] = array(
			'focus'   => $row['focus'],
			'skill'   => isset( $labels[ $row['skill'] ] ) ? $labels[ $row['skill'] ] : $row['skill'],
			'outcome' => $row['outcome'],
			'number'  => $row['number'],
		);
	}

	// Band order, not the order they happen to have been added in.
	$ordered = array();

	foreach ( zandi_placement_bands() as $band ) {
		if ( isset( $bands[ $band['id'] ] ) ) {
			$ordered[] = $bands[ $band['id'] ];
		}
	}

	return $ordered;
}

/**
 * The report's reference number.
 *
 * Short, stable for one sitting, and derived rather than stored. It gives a
 * support conversation something to quote — «گزارش ZA-4F2A-91C7» — without
 * putting the token itself, which is a credential, on a printed page.
 *
 * @param array<string,mixed> $result Scored result.
 * @return string
 */
function zandi_placement_reference( $result ) {
	$seed = ( isset( $result['time'] ) ? (int) $result['time'] : 0 ) . '|' . ( isset( $result['level'] ) ? $result['level'] : '' );
	$hash = strtoupper( substr( wp_hash( 'zandi_placement_ref|' . $seed ), 0, 8 ) );

	return 'ZA-' . substr( $hash, 0, 4 ) . '-' . substr( $hash, 4, 4 );
}

/**
 * The message a student sends to a friend.
 *
 * Assembled here rather than written as one string in the copy array, because
 * it carries the student's own numbers — the level, the score, the three skill
 * percentages and the time taken. A share that says only «I did the test» gives
 * the person receiving it no reason to take it themselves; a share that shows a
 * real breakdown and ends on an invitation does.
 *
 * WHAT TRAVELS IS THE TEST'S PUBLIC ADDRESS, never the URL of the result page.
 * That one is a private token that expires in a day and shows one person's
 * level — sending it to a group chat would share a dead link and someone else's
 * result.
 *
 * THE LEFT-TO-RIGHT MARKS AROUND THE LEVEL ARE LOAD-BEARING. This text lands in
 * WhatsApp or Telegram as plain text, where the receiving app runs the bidi
 * algorithm over it with no CSS to help. «A1+» inside a Persian line would be
 * laid out «+A1», exactly as it was on the result page before
 * zandi_placement_level_label() fixed it there — the `+` is direction-neutral
 * and attaches to the paragraph, not to the Latin run. U+200E on each side pins
 * it. There is no other way to reach a message once it has left the page.
 *
 * @param array<string,mixed> $result Scored result.
 * @param string              $label  The result's label, e.g. «A1+».
 * @return string Plain text, ready for the clipboard or the share sheet.
 */
function zandi_placement_share_text( $result, $label ) {
	$copy = zandi_placement_copy();

	$level = zandi_placement_level_is_code( $label )
		? "\u{200E}" . $label . "\u{200E}"
		: $label;

	$skills = array();

	foreach ( $result['skills'] as $skill ) {
		$skills[] = sprintf( '%s %s٪', $skill['label'], zandi_fa_digits( $skill['percent'] ) );
	}

	$lines = array(
		$copy['share_intro'],
		'',
		sprintf( $copy['share_level'], $level ),
		sprintf(
			$copy['share_score'],
			zandi_fa_digits( $result['correct'] ),
			zandi_fa_digits( $result['total'] )
		),
	);

	if ( $skills ) {
		$lines[] = sprintf( $copy['share_skills'], implode( ' · ', $skills ) );
	}

	if ( ! empty( $result['duration'] ) ) {
		$lines[] = sprintf(
			$copy['share_time'],
			zandi_fa_digits( max( 1, (int) round( $result['duration'] / 60 ) ) )
		);
	}

	$lines[] = '';
	$lines[] = $copy['share_cta'];
	$lines[] = zandi_placement_url();

	/**
	 * Filters the message a student shares after the test.
	 *
	 * @param string              $text   Assembled message.
	 * @param array<string,mixed> $result Scored result.
	 * @param string              $label  Result label.
	 */
	return apply_filters( 'zandi_placement_share_text', implode( "\n", $lines ), $result, $label );
}

/**
 * Says why an auth page is being shown, when it is being shown for us.
 *
 * A student pressed «دریافت گزارش کامل» and got a signup form. Without a word
 * of explanation that reads as a paywall appearing out of nowhere, right after
 * a page that made a point of being free — and the honest answer, that the
 * result is about to be saved to them permanently, is a good one.
 *
 * Rendered on BOTH auth pages. CLAUDE.md is explicit that anything which
 * changes one of them changes the other; a student who already has an account
 * follows «وارد شو» from here and must not lose the explanation on the way.
 *
 * @return void
 */
function zandi_placement_auth_notice() {
	if ( '' === zandi_placement_auth_destination() ) {
		return;
	}

	$copy = zandi_placement_copy();
	?>
	<div class="auth__notice" role="status">
		<span class="auth__notice-icon" aria-hidden="true"><?php zandi_icon( 'clipboard' ); ?></span>

		<div>
			<p class="auth__notice-title"><?php echo zandi_bidi( $copy['auth_notice_title'] ); ?></p>
			<p class="auth__notice-body"><?php echo zandi_bidi( $copy['auth_notice_body'] ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * How the level was worked out, in the student's own terms.
 *
 * Straight out of the specification's sections 1 to 4. It is on the report
 * because a number with no method behind it is a number nobody has any reason
 * to believe, and because the thresholds are unusual enough — 71–75%, not 50% —
 * that they need explaining.
 *
 * @return array<int,array{title:string,body:string}>
 */
function zandi_placement_method_notes() {
	return apply_filters(
		'zandi_placement_method_notes',
		array(
			array(
				'title' => 'چهار بخش، هرکدام حد نصاب خودش',
				'body'  => 'آزمون چهار بلوک دارد: A1، A2، B1 و B2. هر بلوک حد نصاب جداگانه‌ای دارد و نمرهٔ کل معنایی ندارد — چیزی که سطح را تعیین می‌کند، عبور از حد نصاب هر بلوک است.',
			),
			array(
				'title' => 'چرا حد نصاب حدود ۷۵ درصد است و نه ۵۰ درصد',
				'body'  => 'در آزمون چهارگزینه‌ای با سه گزینهٔ واقعی، حدس تصادفی حدود ۳۳ درصد جواب درست می‌دهد. حد نصاب ۵۰ درصد فقط ۱۷ واحد بالاتر از شانس است و برای تصمیم‌گیری کافی نیست. حد نصاب‌ها روی ۷۱ تا ۷۵ درصد تنظیم شده‌اند.',
			),
			array(
				'title' => 'قانون «پرش ممنوع»',
				'body'  => 'سطح نهایی، بالاترین بلوکی است که خودش و همهٔ بلوک‌های پایین‌ترش حد نصاب را گرفته باشند. اگر کسی A1 را رد شود ولی A2 را نه، سطحش B1 نمی‌شود حتی اگر در B1 خوب جواب داده باشد — آن جواب‌ها به احتمال زیاد شانسی بوده‌اند.',
			),
			array(
				'title' => 'نیم‌سطح‌ها',
				'body'  => 'اگر بلوک بعدی را رد نشدی ولی دست‌کم نیمی از آن را درست زدی، نتیجه یک نیم‌سطح «+» می‌گیرد: یعنی سطح فعلی محکم است و داری وارد بعدی می‌شوی.',
			),
			array(
				'title' => 'گزینهٔ «نمی‌دانم» و شاخص اطمینان',
				'body'  => 'گزینهٔ «نمی‌دانم» برای این هست که مجبور به حدس زدن نباشی؛ نمرهٔ منفی ندارد. اگر دقیقاً یک سوال با حد نصاب فاصله داشته باشی و در تمام آن بلوک حتی یک بار «نمی‌دانم» نزده باشی، احتمال حدس زدن بالاست و نیم‌سطح داده نمی‌شود.',
			),
		)
	);
}

/**
 * What the report explicitly does not claim.
 *
 * Written down rather than left to be inferred, because a printed document with
 * a logo on it invites exactly the wrong assumption.
 *
 * @return array<int,string>
 */
function zandi_placement_limits() {
	return apply_filters(
		'zandi_placement_limits',
		array(
			'این مدرک رسمی نیست و جایگزین آزمون‌هایی مثل DELF یا TCF نمی‌شود. هیچ نهادی آن را نمی‌پذیرد.',
			'فقط سه مهارت نوشتاری سنجیده شده: گرامر، واژگان و درک مطلب. شنیدن، حرف زدن و نوشتن در این آزمون اندازه گرفته نمی‌شوند.',
			'نتیجه یک تخمین است. ممکن است نیم‌سطح این‌ور یا آن‌ور باشد، مخصوصاً اگر جایی حدس زده باشی.',
			'آزمون بدون نظارت انجام شده و درستی‌اش به صداقت خودت وابسته است.',
		)
	);
}

/**
 * The academic sources behind the test.
 *
 * Shown on the intro page, at the owner's request. No URLs: the specification
 * cites these by title and gave none, and a link invented to look complete is
 * how a citation stops being one.
 *
 * EVERY ENTRY IS SPLIT IN TWO, AND THAT IS NOT TIDINESS. A bibliographic
 * citation is the worst possible case for the bidi algorithm — it is a Latin
 * run studded with direction-neutral characters (`(`, `)`, `:`, `,`, `@`, `.`)
 * and each one ends a run and starts a new island, which the algorithm then
 * lays out right-to-left with respect to each other inside a Persian list.
 * Written as one mixed string, «Rodriguez, M. C. (2005). Three Options Are
 * Optimal…» rendered as «…Three Options Are Optimal .(2005) .Rodriguez, M. C»
 * and «Ev@lang» rendered as «lang@Ev».
 *
 * So `cite` holds the citation, in its own script, and gets `dir="ltr"` on its
 * own element; `note` holds the Persian gloss on a line of its own, where it can
 * be right-to-left without dragging the citation around with it. Neither has to
 * negotiate with the other.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_placement_sources() {
	return apply_filters(
		'zandi_placement_sources',
		array(
			array(
				'icon'  => 'layers',
				'title' => 'محتوای زبانی هر سطح',
				'items' => array(
					array(
						'cite' => 'Inventaire linguistique des contenus clés des niveaux du CECRL — Eaquals/CIEP, 2015',
						'note' => 'مرجع اصلی گرامر هر سطح',
					),
					array(
						'cite' => 'Beacco, J.-C. et al. — Niveau A1 / A2 / B1 / B2 pour le français, Didier',
						'note' => 'توصیف‌های مرجع شورای اروپا برای زبان فرانسه',
					),
					array(
						'cite' => 'Council of Europe — Reference Level Descriptions',
						'note' => '',
					),
				),
			),
			array(
				'icon'  => 'clipboard',
				'title' => 'طراحی آزمون',
				'items' => array(
					array(
						'cite' => 'Rodriguez, M. C. (2005). Three Options Are Optimal for Multiple-Choice Items: A Meta-Analysis of 80 Years of Research. Educational Measurement: Issues and Practice, 24(2), 3–13.',
						'note' => 'چرا هر سوال سه گزینهٔ واقعی دارد و نه پنج',
					),
					array(
						'cite' => 'Zhang, X. (2013). The I Don\'t Know Option in the Vocabulary Size Test. TESOL Quarterly, 47, 790–811.',
						'note' => 'چرا گزینهٔ «نمی‌دانم» وجود دارد',
					),
					array(
						'cite' => 'Haladyna, Downing & Rodriguez (2002). A review of multiple-choice item-writing guidelines for classroom assessment.',
						'note' => '',
					),
					array(
						'cite' => 'SELF — Université Grenoble Alpes',
						'note' => 'روش‌شناسی آزمون تعیین سطح نیمه‌تطبیقی',
					),
					array(
						'cite' => 'Ev@lang — France Éducation international',
						'note' => 'ساختار سوال و مقیاس رده‌بندی',
					),
					array(
						'cite' => 'Center for Applied Linguistics',
						'note' => 'راهنمای بهترین شیوه‌های تعیین سطح',
					),
				),
			),
			array(
				'icon'  => 'target',
				'title' => 'معیار سطح‌بندی',
				'items' => array(
					array(
						'cite' => 'DELF / DALF',
						'note' => 'آیین‌نامهٔ وزارت آموزش ملی فرانسه — حد نصاب ۵۰ از ۱۰۰ و کف حذفی ۵ از ۲۵',
					),
				),
			),
		)
	);
}

/**
 * What to offer a student at each of the eight outcomes.
 *
 * FACTS ONLY, WHICH IS WHY THIS TABLE EXISTS AT ALL. The specification's own CTA
 * column offers an A2 waiting list, a free revision booklet, a free consultation
 * and a DELF preparation service. The academy sells none of those — and the free
 * consultation was deliberately removed from the site on 30 July 2026 — so every
 * row here points either at a course that can be bought today (`zandi_courses_data()`)
 * or at /contact/, and at nothing else.
 *
 * The first slug is the course the result page leads with; a second is offered
 * underneath as consolidation. `contact` adds the honest line for the levels the
 * catalogue does not reach yet.
 *
 * @return array<string,array<string,mixed>>
 */
function zandi_placement_recommendations() {
	return apply_filters(
		'zandi_placement_recommendations',
		array(
			'pre-A1' => array(
				'lead'    => 'دورهٔ پایه دقیقاً برای همین‌جاست. از حرف اول شروع می‌کنه و هیچ پیش‌نیازی نداره.',
				'courses' => array( 'a1' ),
				'contact' => false,
			),
			'A1'     => array(
				'lead'    => 'پایه‌هاش رو داری، ولی هنوز کاملش نکردی. دورهٔ پایه همون چیزیه که این نصفه‌کاره رو تموم می‌کنه.',
				'courses' => array( 'a1' ),
				'contact' => false,
			),
			'A1+'    => array(
				'lead'    => 'A1 رو رد کردی و یه پات توی A2 است. قدم بعدیت دورهٔ متوسطه.',
				'courses' => array( 'a2', 'a1' ),
				'contact' => false,
			),
			'A2'     => array(
				'lead'    => 'سطح A2 رو در اختیار داری. دورهٔ متوسط جاییه که این رو محکم می‌کنه و می‌برتت جلوتر.',
				'courses' => array( 'a2' ),
				'contact' => false,
			),
			'A2+'    => array(
				'lead'    => 'A2 محکمه و داری وارد B1 می‌شی. دورهٔ پیشرفته قدم بعدیته.',
				'courses' => array( 'b1', 'a2' ),
				'contact' => false,
			),
			'B1'     => array(
				'lead'    => 'کاربر مستقلی — دورهٔ پیشرفته دقیقاً هم‌سطح توئه.',
				'courses' => array( 'b1' ),
				'contact' => false,
			),
			'B1+'    => array(
				'lead'    => 'B1 رو تموم کردی و داری وارد B2 می‌شی. بالاتر از دورهٔ پیشرفته هنوز چیزی روی سایت نیست، پس بگو دنبال چی هستی تا با هم ببینیم چی به دردت می‌خوره.',
				'courses' => array( 'b1' ),
				'contact' => true,
			),
			'B2'     => array(
				'lead'    => 'توی این آزمون کم نیاوردی. دوره‌ای بالاتر از B1 هنوز روی سایت نیست؛ اگر داری برای آزمونی مثل DELF B2 آماده می‌شی، بگو کجای کاری.',
				'courses' => array(),
				'contact' => true,
			),
		)
	);
}

/**
 * The recommendation for one outcome, with the course data already loaded.
 *
 * A course that has been removed from the catalogue is dropped rather than
 * rendered as a broken card.
 *
 * @param string $level Result id.
 * @return array<string,mixed>
 */
function zandi_placement_recommendation( $level ) {
	$all = zandi_placement_recommendations();

	if ( ! isset( $all[ $level ] ) ) {
		return array(
			'lead'    => '',
			'courses' => array(),
			'contact' => true,
		);
	}

	$recommendation            = $all[ $level ];
	$recommendation['courses'] = array_values(
		array_filter(
			array_map( 'zandi_get_course', $recommendation['courses'] )
		)
	);

	return $recommendation;
}

/**
 * Adds «تعیین سطح» to the panel's own navigation, once there is one to link to.
 *
 * Hooked rather than written into zandi_panel_nav() so the whole feature stays
 * in this file: switching the test off is deleting one require, not unpicking
 * edits from three others.
 *
 * @param array<int,array{label:string,url:string}> $items Panel nav items.
 * @return array<int,array{label:string,url:string}>
 */
function zandi_placement_panel_nav( $items ) {
	if ( ! zandi_placement_latest() ) {
		return $items;
	}

	$copy = zandi_placement_copy();

	array_splice(
		$items,
		1,
		0,
		array(
			array(
				'label' => $copy['panel_title'],
				'url'   => '#my-level',
			),
		)
	);

	return $items;
}
add_filter( 'zandi_panel_nav', 'zandi_placement_panel_nav' );
