<?php
/**
 * eNamad (نماد اعتماد الکترونیکی) — site ownership verification.
 *
 * eNamad proves you control the domain before it issues the trust seal. It
 * offers four methods; this file implements the **title** one (تایید عنوان),
 * which asks for the homepage's <title> to read exactly the bare domain while
 * their crawler checks, and says the title may be restored afterwards.
 *
 * That method was chosen because it is the only one of the four that carries no
 * verification code — the other three (meta tag, uploaded .txt, emailed code)
 * all embed a number that would have to be transcribed correctly, and a single
 * wrong digit fails the check with no useful error.
 *
 * ---------------------------------------------------------------------------
 * TO TURN THIS OFF once eNamad has confirmed the domain:
 *
 *     define( 'ZANDI_ENAMAD_VERIFY', false );   // below
 *
 * That is the whole revert. Nothing else in the theme reads the constant.
 * ---------------------------------------------------------------------------
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/*
 * Whether the homepage title is currently pinned to the bare domain for
 * verification. Set to false the moment eNamad reports the domain as verified —
 * «آکادمی زندی — آموزش زبان فرانسه» is what belongs in a browser tab and a
 * search result, not a hostname.
 */
if ( ! defined( 'ZANDI_ENAMAD_VERIFY' ) ) {
	define( 'ZANDI_ENAMAD_VERIFY', true );
}

/**
 * The domain string eNamad expects to find as the homepage title.
 *
 * Derived from the site's own home URL rather than hard-coded, so it cannot
 * drift from the domain actually being verified. eNamad matches the bare host,
 * so the scheme, any `www.` and any trailing slash are stripped.
 *
 * @return string
 */
function zandi_enamad_title() {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );

	if ( ! $host ) {
		return '';
	}

	$host = preg_replace( '/^www\./i', '', $host );

	/**
	 * Filters the exact title eNamad should see.
	 *
	 * @param string $host Bare hostname, e.g. "zandiacademy.com".
	 */
	return (string) apply_filters( 'zandi_enamad_title', $host );
}

/**
 * Pins the homepage title to the bare domain while verification is running.
 *
 * Scoped to the front page: eNamad only reads the homepage, and leaving every
 * other page's title alone means search engines see nothing odd on the course
 * and section pages during the window this is switched on.
 *
 * Hooked at a very late priority on purpose. `pre_get_document_title` is the
 * short-circuit filter an SEO plugin uses too — Yoast and Rank Math both return
 * their own title at priority 10 — so running after them is what makes this win
 * if one is ever installed.
 *
 * @param string $title Title assembled so far, or '' when nothing has set one.
 * @return string
 */
function zandi_enamad_document_title( $title ) {
	if ( ! ZANDI_ENAMAD_VERIFY || ! is_front_page() ) {
		return $title;
	}

	$host = zandi_enamad_title();

	return $host ? $host : $title;
}
add_filter( 'pre_get_document_title', 'zandi_enamad_document_title', 9999 );

/**
 * Reminds whoever opens wp-admin that the homepage title is not the real one.
 *
 * Without this the switch is invisible: the site looks finished, and the only
 * symptom is a hostname sitting in the browser tab and in whatever Google
 * happens to crawl that week. The notice is the thing that stops this being
 * left on for a month.
 *
 * @return void
 */
function zandi_enamad_notice() {
	if ( ! ZANDI_ENAMAD_VERIFY || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<strong>نماد اعتماد:</strong>
			عنوان صفحه اصلی موقتاً روی
			<code><?php echo esc_html( zandi_enamad_title() ); ?></code>
			تنظیم شده تا مرحلهٔ «تایید عنوان» انجام شود.
		</p>
		<p>
			به محض اینکه eNamad دامنه را تایید کرد، این حالت باید غیرفعال شود —
			وگرنه در تب مرورگر و در نتایج گوگل به‌جای نام آکادمی، آدرس سایت دیده می‌شود.
			<?php if ( function_exists( 'zandi_support_url' ) ) : ?>
				<a href="<?php echo esc_url( zandi_support_url() ); ?>">اطلاع بده تا برگردانده شود</a>
			<?php endif; ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'zandi_enamad_notice' );
