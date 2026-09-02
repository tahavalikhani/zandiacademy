<?php
/**
 * Renders both screens and fails on any PHP notice, warning or deprecation.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-render.php` runs it;
 * a browser gets nothing.
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

$theme = ZANDI_THEME;
require $theme . '/inc/courses.php';
require $theme . '/inc/template-tags.php';
require $theme . '/inc/auth.php';
require $theme . '/inc/placement.php';
require $theme . '/inc/students.php';

$pass = 0;
$fail = 0;

function check_true( $label, $got ) {
	global $pass, $fail;
	if ( $got ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n";
}

$user                     = new WP_User();
$user->ID                 = 7;
$user->display_name       = 'مریم رضایی';
$user->user_login         = '09121234567';
$user->user_email         = 'maryam@example.com';
$user->user_registered    = '2026-07-14 08:30:00';
$GLOBALS['stub_users'][7] = $user;

$answers = array();
foreach ( zandi_placement_questions() as $i => $q ) {
	// Right for the first twelve, «نمی‌دانم» for a few, blank for the rest.
	if ( $i < 12 ) {
		$answers[ (int) $q['id'] ] = (int) $q['answer'];
	} elseif ( $i < 16 ) {
		$answers[ (int) $q['id'] ] = (int) $q['idkIndex'];
	}
}

$result = zandi_placement_score( $answers, array( 'duration' => 640 ) );

$GLOBALS['stub_meta'][7] = array(
	'zandi_phone'              => array( '09121234567' ),
	'zandi_placement_result'   => array( $result ),
	'zandi_placement_level'    => array( $result['level'] ),
	'zandi_placement_history'  => array( array( array( 'level' => 'A1', 'time' => 1750000000 ), array( 'level' => $result['level'], 'time' => time() ) ) ),
	'zandi_course_owned'       => array( 'a1' ),
	'_money_spent'             => array( '2400000' ),
	'zandi_last_login'         => array( time() - 7200 ),
);

echo "\n— One student's page —\n";
ob_start();
zandi_students_render_detail( 7 );
$detail = ob_get_clean();

check_true( 'renders without a notice or warning', true );
check_true( 'names the student', false !== strpos( $detail, 'مریم رضایی' ) );
check_true( 'shows the level as an isolated code', (bool) preg_match( '/<span dir="ltr">' . preg_quote( $result['level'], '/' ) . '<\/span>/u', $detail ) );
check_true( 'marks the mobile cell left-to-right on the element', (bool) preg_match( '/<span class="zandi-field__value" dir="ltr">\s*<a href="tel:/u', $detail ) );
check_true( 'links the mobile for dialling', false !== strpos( $detail, 'tel:09121234567' ) );
check_true( 'links the email', false !== strpos( $detail, 'mailto:maryam@example.com' ) );
check_true( 'reviews every question in the bank', 30 === substr_count( $detail, 'zandi-review__row' ) );
check_true( 'shows the three skill bars', 3 === substr_count( $detail, 'zandi-bars__fill' ) );
check_true( 'shows a row per band', count( $result['bands'] ) === substr_count( $detail, 'zandi-bands__row' ) );
check_true( 'shows the retake history', false !== strpos( $detail, 'zandi-history' ) );
check_true( 'says so plainly when WooCommerce is off', false !== strpos( $detail, 'ووکامرس فعال نیست' ) );
check_true( 'offers the printable report', false !== strpos( $detail, 'student=7' ) );
check_true( 'never asks Gravatar for an avatar', false === strpos( $detail, 'gravatar' ) );
check_true( 'closes every element it opens', substr_count( $detail, '<div' ) === substr_count( $detail, '</div>' ) );

echo "\n— A student with nothing on record —\n";
$blank                     = new WP_User();
$blank->ID                 = 9;
$blank->display_name       = 'کاربر تازه';
$blank->user_login         = '09350000000';
$GLOBALS['stub_users'][9]  = $blank;
$GLOBALS['stub_meta'][9]   = array();

ob_start();
zandi_students_render_detail( 9 );
$empty = ob_get_clean();

check_true( 'renders without a notice or warning', true );
check_true( 'says they have not taken the test', false !== strpos( $empty, 'هنوز آزمون تعیین سطح نداده' ) );
check_true( 'says the mobile is not on record', false !== strpos( $empty, 'ثبت نشده' ) );
check_true( 'offers no report link', false === strpos( $empty, 'student=9' ) );

echo "\n— A user who is not a student —\n";
$staff                     = new WP_User();
$staff->ID                 = 1;
$staff->roles              = array( 'administrator' );
$GLOBALS['stub_users'][1]  = $staff;

ob_start();
zandi_students_render_detail( 1 );
$denied = ob_get_clean();

check_true( 'is not shown', false !== strpos( $denied, 'این دانشجو پیدا نشد' ) );

ob_start();
zandi_students_render_detail( 999 );
$missing = ob_get_clean();

check_true( 'a user id that does not exist is not shown either', false !== strpos( $missing, 'این دانشجو پیدا نشد' ) );

echo "\n— The list screen —\n";
$GLOBALS['stub_user_results'] = array( $user );
$GLOBALS['stub_user_total']   = 1;

ob_start();
zandi_students_render_list();
$list = ob_get_clean();

check_true( 'renders without a notice or warning', true );
check_true( 'shows the four tiles', 4 === substr_count( $list, 'zandi-tile__value' ) );
check_true( 'shows the revenue in Toman', false !== strpos( $list, '۹,۶۰۰,۰۰۰' ) );
check_true( 'offers the export', false !== strpos( $list, 'zandi_students_export' ) );
check_true( 'the export link is nonced', false !== strpos( $list, '_wpnonce' ) );
check_true( 'keeps the page parameter on the filter form', false !== strpos( $list, 'name="page" value="zandi-students"' ) );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
