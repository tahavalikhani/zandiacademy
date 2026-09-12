<?php
/**
 * Renders the panel's course card and checks the licence copy control.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-panel.php` runs
 * it; a browser gets nothing.
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

require ZANDI_THEME . '/inc/courses.php';
require ZANDI_THEME . '/inc/icons.php';
require ZANDI_THEME . '/inc/template-tags.php';
require ZANDI_THEME . '/inc/panel.php';

/*
 * The panel's course card grew a bundle block on 12 September 2026 and it reads
 * zandi_podcast_copy() and zandi_podcast_state(), so both have to be here or the
 * block silently never renders and its checks pass for the wrong reason.
 *
 * The partial also guards them with function_exists(), which this cannot
 * exercise — a loaded function cannot be unloaded. The guard is there because a
 * panel that fatals over a sibling feature is a student locked out of their own
 * licence, and the days-absent path below is the half of it that is testable.
 */
require ZANDI_THEME . '/inc/placement.php';
require ZANDI_THEME . '/inc/podcast.php';

/*
 * Through the theme's own filter, so the test exercises the real
 * zandi_student_courses() rather than a stand-in for it.
 */
add_filter( 'zandi_student_courses', 'zandi_stub_courses' );

$GLOBALS['stub_courses'] = array(
	array(
		'slug'         => 'a1',
		'title'        => 'دوره پایه A1',
		'level'        => 'A1',
		'url'          => 'https://example.test/courses/a1/',
		'licence'      => '6a8c8958b75ba2ea2a05d44ea9d73cf945215602da812ed9c409605797eac71daef50fc357013c880b2e7cdcc61c991a36c2ee83ce031777741b01854f88a15a4442c0fa3aed9ee6b40be7e1',
		'player'       => 'https://example.test/player/',

		// The bundle, as zandi_woo_student_courses() resolves it.
		'podcast_days' => 30,
	),
);


function zandi_stub_courses() { return $GLOBALS['stub_courses']; }

$pass = 0;
$fail = 0;

function check_true( $label, $got ) {
	global $pass, $fail;
	if ( $got ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n";
}

function render_courses() {
	$user     = new WP_User();
	$user->ID = 7;
	$args     = array( 'user' => $user );

	ob_start();
	include ZANDI_THEME . '/template-parts/panel/courses.php';

	return ob_get_clean();
}

$copy = zandi_panel_copy();
$html = render_courses();

echo "\n— The licence card —\n";
check_true( 'renders without a notice or warning', true );
check_true( 'prints the key in full', false !== strpos( $html, $GLOBALS['stub_courses'][0]['licence'] ) );
check_true( 'the key runs left to right', (bool) preg_match( '/<code class="panel-licence__key" dir="ltr">/', $html ) );
check_true( 'there is a copy button', false !== strpos( $html, 'panel-licence__copy' ) );
check_true( 'the button ships hidden', (bool) preg_match( '/class="[^"]*panel-licence__copy[^"]*"[^>]*\shidden/s', $html ) );
check_true( 'it is a button, not a link', (bool) preg_match( '/<button\s+type="button"[^>]*panel-licence__copy/s', $html ) );
check_true( 'it carries both labels', false !== strpos( $html, $copy['licence_copy'] ) && false !== strpos( $html, $copy['licence_copied'] ) );
check_true( 'the done label is second, so CSS can show one at a time', strpos( $html, 'panel-licence__state--idle' ) < strpos( $html, 'panel-licence__state--done' ) );
check_true( 'it names the course for a screen reader', false !== strpos( $html, 'کپی کردن کلید لایسنس دوره پایه A1' ) );
check_true( 'it announces the change politely', false !== strpos( $html, 'aria-live="polite"' ) );
// Scoped to the button: the card holds other icons now.
preg_match( '/<button[^>]*panel-licence__copy.*?<\/button>/s', $html, $zandi_button_html );
check_true( 'both of the button\'s icons come from the registry', 2 === substr_count( isset( $zandi_button_html[0] ) ? $zandi_button_html[0] : '', '<svg viewBox="0 0 24 24"' ) );

echo "\n— The bundle, under the licence —\n";
$pod = zandi_podcast_copy();

check_true( 'the gift block renders', false !== strpos( $html, 'panel-gift' ) );
check_true( 'it names the gift and its length', false !== strpos( $html, sprintf( $pod['perk_panel_title'], '۳۰' ) ) );
check_true( 'the day count is in Persian digits', false === strpos( $html, '30 روز اشتراک' ) );

/*
 * It sits under the licence and above the buttons. The owner asked for it
 * there, and the reason it matters is that the licence is the one block on this
 * page a student who has just paid is certain to read.
 */
check_true(
	'it comes after the licence',
	strpos( $html, 'panel-licence' ) < strpos( $html, 'panel-gift' )
);
check_true(
	'and before the course buttons',
	strpos( $html, 'panel-gift' ) < strpos( $html, 'panel-course__actions' )
);

/*
 * The link is the in-page anchor to «پادکست من», where the Telegram step is.
 * Without that step the gift is days of access to a group the bot will not
 * open, so a block that announced it and pointed nowhere would read as broken.
 */
check_true( 'it points at the podcast section of this same page', false !== strpos( $html, 'href="#my-podcast"' ) );

/*
 * NOT PURPLE. assets/css/podcast.css records why the panel's own podcast card
 * is navy: one coloured card in a column of navy ones reads as a rendering
 * fault. The course page's strip is purple because it is selling; this is read
 * by somebody who has already paid.
 */
$panel_css = preg_replace( '#/\*.*?\*/#s', '', file_get_contents( ZANDI_THEME . '/assets/css/panel.css' ) );
preg_match_all( '/\.panel-gift[^{]*\{[^}]*\}/s', $panel_css, $gift_rules );
$gift_css = implode( "\n", $gift_rules[0] );

check_true( 'the panel has rules for the block at all', '' !== trim( $gift_css ) );

/*
 * NAVY, NOT THE COURSE PAGE'S CHAMPAGNE. The card on a course page is warm
 * because it has to be noticed while somebody scrolls a sales page; this one is
 * read by a student who already paid, and a warm card in /panel/'s column of
 * navy ones reads as a rendering fault — the rule podcast.css already records.
 */
foreach ( array( '#fdf8f0', '#eee0c8', '#9a6f24', '#f6ead4' ) as $warm ) {
	check_true( 'the panel block does not paint with ' . $warm, false === stripos( $gift_css, $warm ) );
}

/*
 * It shares the course page's badge, though, so a student recognises the thing
 * they were promised. Same glyph, same filled disc, navy instead of gold.
 */
check_true( 'it draws the shared gift badge', false !== strpos( $html, 'panel-gift__badge' ) );
check_true( 'the badge glyph is the registry\'s, not an emoji', '' !== zandi_get_icon( 'gift' ) );
check_true(
	'and no emoji survives anywhere in the card',
	! preg_match( '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $html )
);

/*
 * A course with no gift draws nothing — no empty box, no «۰ روز». That is the
 * path a fourth course added without a `podcast_days` entry takes.
 */
unset( $GLOBALS['stub_courses'][0]['podcast_days'] );
$plain = render_courses();
$GLOBALS['stub_courses'][0]['podcast_days'] = 30;

check_true( 'a course with no gift draws no block', false === strpos( $plain, 'panel-gift' ) );
check_true( 'and still draws its licence', false !== strpos( $plain, 'panel-licence__key' ) );

echo "\n— How to open the course —\n";
check_true( 'the install steps are shown', false !== strpos( $html, 'panel-licence__steps' ) );
preg_match( '/<ol class="panel-licence__steps">(.*?)<\/ol>/s', $html, $zandi_steps_html );
check_true(
	'every step in the copy is rendered — ' . count( $copy['licence_steps'] ),
	count( $copy['licence_steps'] ) === substr_count( isset( $zandi_steps_html[1] ) ? $zandi_steps_html[1] : '', '<li>' )
);
check_true( 'they are always visible, not behind a disclosure', false === strpos( $html, '<details' ) );
check_true( 'they never name a control inside the player itself', ! preg_match( '/\b(دکمه|گزینه|منو|تب)\b/u', implode( ' ', $copy['licence_steps'] ) ) );
check_true( 'their numbers are drawn in Persian', (bool) preg_match( '/\.panel-licence__steps li \{[^}]*list-style-type:\s*persian/', preg_replace( '#/\*.*?\*/#s', '', file_get_contents( ZANDI_THEME . '/assets/css/panel.css' ) ) ) );

echo "\n— The class's study group —\n";
$GLOBALS['stub_courses'][0]['group'] = zandi_course_group_url( 'a1' );
$zandi_group_html                    = render_courses();

check_true( 'every course in the catalogue has a group', 3 === count( array_filter( array_map( 'zandi_course_group_url', array_keys( zandi_courses_data() ) ) ) ) );
check_true( 'each one is its own link, not a shared address', 3 === count( array_unique( array_map( 'zandi_course_group_url', array_keys( zandi_courses_data() ) ) ) ) );
check_true( 'the URL reaches the card', false !== strpos( $zandi_group_html, zandi_course_group_url( 'a1' ) ) );
check_true( 'the label comes from the copy filter', false !== strpos( $zandi_group_html, $copy['course_group'] ) );

// Scoped to the button, so a stray attribute elsewhere cannot pass this.
preg_match( '/<a[^>]*panel-course__group.*?<\/a>/s', $zandi_group_html, $zandi_grp );
$zandi_grp = isset( $zandi_grp[0] ) ? $zandi_grp[0] : '';

check_true( 'it opens in a new tab', false !== strpos( $zandi_grp, 'target="_blank"' ) );
check_true( 'and cannot reach back through window.opener', false !== strpos( $zandi_grp, 'rel="noopener noreferrer"' ) );
check_true( 'it names the course for a screen reader', false !== strpos( $zandi_grp, 'گروه تلگرام دوره پایه A1' ) );
check_true( 'it carries an icon from the registry', 1 === substr_count( $zandi_grp, '<svg viewBox="0 0 24 24"' ) );
check_true( 'it sits after the player and before the course page', strpos( $zandi_group_html, 'panel-course__group' ) > strpos( $zandi_group_html, $copy['course_player'] ) && strpos( $zandi_group_html, 'panel-course__group' ) < strpos( $zandi_group_html, $copy['course_page'] ) );

/*
 * The link is a paid student's, so it must not leak onto a public page. The
 * catalogue is where it lives; nothing that renders for a stranger may read it.
 */
$zandi_public = '';
foreach ( glob( ZANDI_THEME . '/template-parts/{home,course}/*.php', GLOB_BRACE ) as $zandi_file ) {
	$zandi_public .= file_get_contents( $zandi_file );
}
check_true( 'no public template reads a group URL', false === strpos( $zandi_public, 'group_url' ) && false === strpos( $zandi_public, "['group']" ) );

// A course with no group must lose the button, not render a dead one.
$GLOBALS['stub_courses'][0]['group'] = '';
check_true( 'a course without a group shows no button', false === strpos( render_courses(), 'panel-course__group' ) );
$GLOBALS['stub_courses'][0]['group'] = zandi_course_group_url( 'a1' );

echo "\n— «قدم بعدی» —\n";
check_true( 'a student who owns A1 is pointed at A2', false !== strpos( $html, 'دوره متوسط A2' ) && false !== strpos( $html, 'panel-next' ) );
check_true( 'the card links to the course page, never to a checkout', false === strpos( $html, 'add-to-cart' ) && false === strpos( $html, 'checkout' ) );

$zandi_all = zandi_courses_data();
$GLOBALS['stub_courses'] = array();
foreach ( $zandi_all as $slug => $course ) {
	$GLOBALS['stub_courses'][] = array( 'slug' => $slug, 'title' => $course['short_name'], 'level' => '', 'url' => '#', 'licence' => 'x', 'player' => '' );
}
check_true( 'a student who owns every course is shown nothing', false === strpos( render_courses(), 'panel-next' ) );

$GLOBALS['stub_courses'] = array();
check_true( 'a student who owns nothing is shown the catalogue, not a next step', false === strpos( render_courses(), 'panel-next' ) );

$GLOBALS['stub_courses'] = array( array( 'slug' => 'b1', 'title' => 'دوره پیشرفته B1', 'level' => 'B1', 'url' => '#', 'licence' => 'x', 'player' => '' ) );
check_true( 'owning only B1 points at the gap below it, not off the end', false !== strpos( render_courses(), 'دوره پایه A1' ) );

echo "\n— Copy lives in PHP, not in markup or in the script —\n";
$template = file_get_contents( ZANDI_THEME . '/template-parts/panel/courses.php' );
$script   = file_get_contents( ZANDI_THEME . '/assets/js/theme.js' );

// Persian outside a PHP comment block is a literal that escaped the filter.
$stripped = preg_replace( '#/\*.*?\*/#s', '', $template );

check_true( 'no Persian literal left in the template', ! preg_match( '/[\x{0600}-\x{06FF}]/u', $stripped ) );
/*
 * Narrow on purpose. theme.js does hold Persian already — the digit tables, and
 * «نمایش»/«پنهان» on the password toggle, which predate the copy convention and
 * are the same inconsistency this change fixed in the panel. The invariant
 * being defended here is that the copy button did not add another one.
 */
check_true( 'the copy button adds no Persian to theme.js', false === strpos( preg_replace( '#/\*.*?\*/#s', '', $script ), 'کپی' ) );
check_true( 'the button label comes from the copy filter', false !== strpos( $template, "\$zandi_copy['licence_copy']" ) );

echo "\n— The CSS that makes `hidden` actually hide a .btn —\n";
$css = file_get_contents( ZANDI_THEME . '/assets/css/panel.css' );
check_true( 'an author rule backs up the hidden attribute', (bool) preg_match( '/\.panel-licence__copy\[hidden\]\s*\{\s*display:\s*none/', $css ) );
// Comments stripped first, or the rule's own explanation of what it avoids
// reads as the thing it avoids.
$rules = preg_replace( '#/\*.*?\*/#s', '', $css );
check_true( 'it is scoped to this control, not to everything in .panel-page', ! preg_match( '/\.panel-page\s+\[hidden\]/', $rules ) );
check_true( 'the done state is hidden by default', (bool) preg_match( '/\.panel-licence__state--done,/', $rules ) );
check_true( 'the two states share one grid cell, so the button cannot resize', (bool) preg_match( '/\.panel-licence__state\s*\{[^}]*grid-area:\s*1 \/ 1/', $rules ) );

echo "\n— Every stylesheet balances its braces —\n";

/*
 * There is no build step here, so nothing else would ever notice an unclosed
 * rule — and a stylesheet does not fail loudly when one is missing. It keeps
 * parsing, swallowing every rule after the unclosed one as garbage, and the
 * page renders with a chunk of its styling silently absent. That is exactly
 * what a merge produced on this file: `.panel-licence__copy .btn__icon` lost
 * its closing brace, and everything below it — including the `word-break` that
 * wraps the licence — stopped applying, giving the panel a page that scrolled
 * sideways on a phone.
 */
foreach ( glob( ZANDI_THEME . '/{style.css,rtl.css,assets/css/*.css}', GLOB_BRACE ) as $sheet ) {
	$rules = preg_replace( '#/\*.*?\*/#s', '', file_get_contents( $sheet ) );

	check_true(
		basename( $sheet ) . ' — ' . substr_count( $rules, '{' ) . ' rules opened, ' . substr_count( $rules, '}' ) . ' closed',
		substr_count( $rules, '{' ) === substr_count( $rules, '}' )
	);
}

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
