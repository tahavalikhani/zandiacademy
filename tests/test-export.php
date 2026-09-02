<?php
/**
 * Runs the CSV export and prints what a spreadsheet would receive.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-export.php` runs it;
 * a browser gets nothing.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

function check_admin_referer( $a ) { return true; }
function nocache_headers() {}
function wp_die( $m ) { echo $m; exit( 1 ); }

$theme = ZANDI_THEME;
require $theme . '/inc/courses.php';
require $theme . '/inc/template-tags.php';
require $theme . '/inc/auth.php';
require $theme . '/inc/placement.php';
require $theme . '/inc/students.php';

$user                     = new WP_User();
$user->ID                 = 7;
$user->display_name       = '=cmd|calc';                 // a name that is a formula
$user->user_login         = '09121234567';
$user->user_email         = 'maryam@example.com';
$user->user_registered    = '2026-07-14 08:30:00';
$GLOBALS['stub_users'][7] = $user;

$answers = array();
foreach ( zandi_placement_questions() as $i => $q ) {
	if ( $i < 12 ) {
		$answers[ (int) $q['id'] ] = (int) $q['answer'];
	}
}
$result = zandi_placement_score( $answers, array( 'duration' => 600 ) );

$GLOBALS['stub_meta'][7] = array(
	'zandi_phone'            => array( '09121234567' ),
	'zandi_placement_result' => array( $result ),
	'zandi_placement_level'  => array( $result['level'] ),
	'zandi_course_owned'     => array( 'a1', 'a2' ),
	'_money_spent'           => array( '2400000' ),
	'zandi_last_login'       => array( 1755000000 ),
);

$GLOBALS['stub_user_results'] = array( $user );

zandi_students_export();
