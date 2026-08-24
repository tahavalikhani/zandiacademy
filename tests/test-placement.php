<?php
/**
 * The placement test's routing, and the one path that could hijack it.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-placement.php`
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

echo "\n— Where each button in the panel points —\n";
check( 'the retake button asks for the test, not the intro', zandi_placement_url( 'start' ), 'https://example.test/placement/?start=1' );
check( 'and ?start=1 is the state that renders the test', zandi_placement_state_for( array( 'start' => '1' ) ), 'test' );
check( 'the report button asks for the report', zandi_placement_report_url(), 'https://example.test/placement/?report=1' );
check( 'and ?report=1 renders the report', zandi_placement_state_for( array( 'report' => '1' ) ), 'report' );
check( 'a bare visit is the intro', zandi_placement_state_for( array() ), 'intro' );
check( 'a token is a result', zandi_placement_state_for( array( 'r' => 'abc' ) ), 'result' );
check( 'a report carrying a token is still the report', zandi_placement_state_for( array( 'report' => '1', 'r' => 'abc' ) ), 'report' );

/** Runs zandi_placement_state() against a given query string. */
function zandi_placement_state_for( $query ) {
	$_GET = $query;
	$state = zandi_placement_state();
	$_GET = array();

	return $state;
}

echo "\n— A leftover intent must never hijack the placement page —\n";

/*
 * zandi_placement_resume_intent() runs on template_redirect for EVERY page, and
 * sends a signed-in student to the report a signup was interrupted on the way
 * to. Anywhere else that is the point of it. On /placement/ it is always wrong:
 * the student has just chosen a state, and «دوباره آزمون بده» asking for
 * ?start=1 would land on a months-old report instead of a fresh test — a button
 * that looks like it does nothing.
 */
function zandi_resume_from( $on_placement, $uri ) {
	$GLOBALS['stub_is_admin']  = false;
	$GLOBALS['stub_redirect']  = null;
	$GLOBALS['stub_query_vars'] = array( 'zandi_placement' => $on_placement ? '1' : '' );

	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_SERVER['REQUEST_URI']    = $uri;
	$_COOKIE[ zandi_placement_intent_cookie() ] = 'https://example.test/placement/?report=1';

	try {
		zandi_placement_resume_intent();
	} catch ( Zandi_Stub_Redirect $e ) {
		return $e->getMessage();
	}

	return '';
}

check(
	'off the placement page it still does its job',
	zandi_resume_from( false, '/panel/' ),
	'https://example.test/placement/?report=1'
);

check(
	'but never on the test itself',
	zandi_resume_from( true, '/placement/?start=1' ),
	''
);

check_true(
	'and it spends the cookie there, so nobody is bounced later',
	! isset( $_COOKIE[ zandi_placement_intent_cookie() ] )
);

check(
	'nor on the intro',
	zandi_resume_from( true, '/placement/' ),
	''
);

check(
	'nor on the report it points at, which would be a redirect loop',
	zandi_resume_from( true, '/placement/?report=1' ),
	''
);

echo "\n— The question bank still scores —\n";
$questions = zandi_placement_questions();
check_true( 'the bank loads', count( $questions ) > 0 );

$perfect = array();
foreach ( $questions as $q ) { $perfect[ (int) $q['id'] ] = (int) $q['answer']; }
$top = zandi_placement_score( $perfect );
$bands = zandi_placement_bands();
$top_band = end( $bands );
check( 'every answer right reaches the top band', $top['level'], $top_band['id'] );
check( 'and counts them all', $top['correct'], count( $questions ) );

$none = zandi_placement_score( array() );
check( 'no answers at all is below A1', $none['level'], 'pre-A1' );
check( 'and nothing is counted as answered', $none['answered'], 0 );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
