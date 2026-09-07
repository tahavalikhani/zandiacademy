<?php
/**
 * The standalone section pages: which exist, and where the retired ones go.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-sections.php`
 * runs it; a browser gets nothing.
 *
 * This is the first test to load `functions.php` itself rather than one of the
 * `inc/` files, because the section registry and the rewrite rules live there.
 * Four helpers are declared both in the stub and in `functions.php` — the stub
 * has always been the one the tests use — so the copy evaluated here has those
 * four renamed out of the way and its `require_once` lines dropped. It is a
 * blunt technique and it is confined to this file; nothing in the theme changed
 * to accommodate it.
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

// functions.php extends a wp-admin class the stub has no reason to carry.
if ( ! class_exists( 'Walker_Nav_Menu' ) ) {
	class Walker_Nav_Menu {
		public function start_lvl( &$o, $d = 0, $a = null ) {}
		public function end_lvl( &$o, $d = 0, $a = null ) {}
		public function start_el( &$o, $i, $d = 0, $a = null, $id = 0 ) {}
		public function end_el( &$o, $i, $d = 0, $a = null ) {}
	}
}

$zandi_src = file_get_contents( ZANDI_THEME . '/functions.php' );
$zandi_src = preg_replace( '/^require_once get_theme_file_path\(.*$/m', '', $zandi_src );

foreach ( array( 'zandi_course_url', 'zandi_is_rtl', 'zandi_pretty_permalinks', 'zandi_section_url' ) as $zandi_dupe ) {
	$zandi_src = preg_replace( '/^function\s+' . $zandi_dupe . '\b/m', 'function _stubbed_' . $zandi_dupe, $zandi_src );
}

eval( '?>' . $zandi_src );

require ZANDI_THEME . '/inc/content.php';

$pass = 0;
$fail = 0;

function check_true( $label, $got ) {
	global $pass, $fail;
	if ( $got ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n";
}

$sections = zandi_sections();
$retired  = zandi_retired_sections();

echo "\n— Which pages exist —\n";
check_true( 'three sections: ' . implode( ', ', array_keys( $sections ) ), array( 'courses', 'about', 'contact' ) === array_keys( $sections ) );
check_true( '/faq/ is no longer one of them', ! isset( $sections['faq'] ) );
check_true( '/method/ is no longer one of them', ! isset( $sections['method'] ) );
check_true( 'so the rewrite pattern cannot match them', ! preg_match( '#\b(faq|method)\b#', implode( '|', array_keys( $sections ) ) ) );
check_true( 'and the rules will be re-registered on deploy', (int) ZANDI_ROUTES_VERSION > 5 );

echo "\n— The questions live on /contact/ now —\n";
check_true( 'contact renders two partials', 2 === count( $sections['contact']['parts'] ) );
check_true( 'the contact cards come first, so the <h1> suppresses their heading and not the FAQ\'s', 'contact' === $sections['contact']['parts'][0] );
check_true( 'the questions come second', 'faq' === $sections['contact']['parts'][1] );
check_true( 'exactly one section renders them', 1 === count( array_filter( $sections, function ( $s ) { return in_array( 'faq', $s['parts'], true ); } ) ) );
check_true( 'the page describes itself as carrying them', false !== mb_strpos( $sections['contact']['meta'], 'سوال' ) );

echo "\n— The retired URLs still lead somewhere —\n";
check_true( 'both are mapped', isset( $retired['faq'], $retired['method'] ) );
check_true( '/faq/ → ' . $retired['faq'], '/contact/#faq' === $retired['faq'] );
check_true( '/method/ → ' . $retired['method'], '/#about' === $retired['method'] );
check_true( 'no live section is treated as retired', ! array_intersect( array_keys( $retired ), array_keys( $sections ) ) );
check_true( 'every destination is a path on this site, never an absolute URL', 2 === count( array_filter( $retired, function ( $d ) { return '/' === $d[0]; } ) ) );

/*
 * The anchors have to exist, or a 301 lands somewhere with nothing to show.
 * #about is the four method blocks; #faq is the questions.
 */
echo "\n— And the anchors they point at are real —\n";
check_true( '#about is on the homepage', false !== strpos( file_get_contents( ZANDI_THEME . '/template-parts/home/features.php' ), 'id="about"' ) );
check_true( '#faq is on the questions partial', false !== strpos( file_get_contents( ZANDI_THEME . '/template-parts/home/faq.php' ), 'id="faq"' ) );

echo "\n— Nothing links to a page that is gone —\n";
$zandi_links = '';
foreach ( array( 'inc/content.php', 'header.php', 'footer.php', 'template-section.php' ) as $zandi_file ) {
	$zandi_links .= file_get_contents( ZANDI_THEME . '/' . $zandi_file );
}
check_true( 'no nav or footer link builds a /faq/ URL', false === strpos( $zandi_links, "zandi_section_url( 'faq' )" ) );
check_true( 'no nav or footer link builds a /method/ URL', false === strpos( $zandi_links, "zandi_section_url( 'method' )" ) );
check_true( 'the menu is down to four items', 4 === count( zandi_navigation() ) );
check_true( 'and «روش تدریس» survives in the footer as an anchor', false !== strpos( $zandi_links, "zandi_resolve_anchor( '#about' )" ) );

/*
 * The FAQ partial invites a reader to /contact/. On /contact/ that invitation
 * points at the page they are reading, so it stands down — but only there.
 */
echo "\n— The FAQ's own invitation knows where it is —\n";
$zandi_faq_partial = file_get_contents( ZANDI_THEME . '/template-parts/home/faq.php' );
check_true( 'the partial asks whether it is already at the destination', false !== strpos( $zandi_faq_partial, 'zandi_is_the_dest' ) );
check_true( 'and the test is a URL comparison, not a hard-coded slug', false !== strpos( $zandi_faq_partial, 'zandi_support_url() === zandi_section_url(' ) );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail ? 1 : 0 );
