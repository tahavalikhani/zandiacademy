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

function zandi_section_url( $section ) { return 'https://example.test/' . $section . '/'; }

/*
 * Through the theme's own filter, so the test exercises the real
 * zandi_student_courses() rather than a stand-in for it.
 */
add_filter( 'zandi_student_courses', 'zandi_stub_courses' );

$GLOBALS['stub_courses'] = array(
	array(
		'slug'    => 'a1',
		'title'   => 'دوره پایه A1',
		'level'   => 'A1',
		'url'     => 'https://example.test/courses/a1/',
		'licence' => '6a8c8958b75ba2ea2a05d44ea9d73cf945215602da812ed9c409605797eac71daef50fc357013c880b2e7cdcc61c991a36c2ee83ce031777741b01854f88a15a4442c0fa3aed9ee6b40be7e1',
		'player'  => 'https://example.test/player/',
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
check_true( 'both icons come from the registry', 2 === substr_count( $html, '<svg viewBox="0 0 24 24"' ) );

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

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
