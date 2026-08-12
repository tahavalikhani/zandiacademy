<?php
/**
 * eNamad (نماد اعتماد الکترونیکی) — site ownership verification.
 *
 * eNamad proves you control the domain before it issues the trust seal. It
 * offers four methods; this file implements the **title** one (تایید عنوان),
 * which asks for the homepage's <title> to read exactly the verification number
 * while their crawler checks, and says the title may be restored afterwards.
 *
 * The instruction is easy to misread, and was: the sentence names the site
 * address in a link — «صفحه اصلی سایت که در آدرس zandiacademy.com قرار دارد» —
 * and then gives the number to use. The link is naming *which page* to change,
 * not what to change it to. Setting the title to the domain fails with
 * «عنوان صفحه شما به عدد … تغییر نیافته است».
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
 * Whether the homepage title is currently pinned to the verification code. Set
 * to false the moment eNamad reports the domain as verified — «آکادمی زندی —
 * آموزش زبان فرانسه» is what belongs in a browser tab and a search result, not
 * an eight-digit number.
 */
if ( ! defined( 'ZANDI_ENAMAD_VERIFY' ) ) {
	define( 'ZANDI_ENAMAD_VERIFY', true );
}

/*
 * The code eNamad issued for this domain, exactly as it must appear in the
 * <title>. Read from the panel at reg2.enamad.ir; it is the same number that
 * appears in the meta-tag and .txt-filename methods.
 *
 * ASCII digits on purpose. The eNamad panel *displays* it as ۷۱۱۱۶۰۷۴ because
 * that page renders Latin digits in a Persian face — the same font-feature
 * substitution this theme disables for CEFR codes — but the value their crawler
 * compares against is the plain number.
 *
 * If verification keeps failing, check this string against the panel first.
 */
if ( ! defined( 'ZANDI_ENAMAD_CODE' ) ) {
	define( 'ZANDI_ENAMAD_CODE', '71116074' );
}

/**
 * The exact string eNamad expects to find as the homepage title.
 *
 * @return string
 */
function zandi_enamad_title() {
	/**
	 * Filters the verification string, so the code can be corrected without a
	 * theme edit if eNamad reissues it.
	 *
	 * @param string $code The eNamad verification code.
	 */
	return (string) apply_filters( 'zandi_enamad_title', ZANDI_ENAMAD_CODE );
}

/**
 * Pins the homepage title to the verification code while the check is running.
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

	$code = zandi_enamad_title();

	return '' !== $code ? $code : $title;
}
add_filter( 'pre_get_document_title', 'zandi_enamad_document_title', 9999 );

/**
 * Reminds whoever opens wp-admin that the homepage title is not the real one.
 *
 * Without this the switch is invisible: the site looks finished, and the only
 * symptom is an eight-digit number sitting in the browser tab and in whatever
 * Google happens to crawl that week. The notice is the thing that stops this
 * being left on for a month.
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
			عنوان صفحه اصلی موقتاً روی کد
			<code><?php echo esc_html( zandi_enamad_title() ); ?></code>
			تنظیم شده تا مرحلهٔ «تایید عنوان» انجام شود.
		</p>
		<p>
			اگر این عدد با چیزی که در پنل eNamad نوشته فرق دارد، تایید انجام نمی‌شود.
			به محض اینکه دامنه تایید شد، این حالت باید غیرفعال شود — وگرنه در تب مرورگر
			و در نتایج گوگل به‌جای نام آکادمی، یک عدد دیده می‌شود.
			<?php if ( function_exists( 'zandi_support_url' ) ) : ?>
				<a href="<?php echo esc_url( zandi_support_url() ); ?>">اطلاع بده تا برگردانده شود</a>
			<?php endif; ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'zandi_enamad_notice' );
