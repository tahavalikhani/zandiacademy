<?php
/**
 * Exercises the students screen's data layer: the CSV injection guard, the
 * level whitelist, the placement mirror and tally, the report's capability
 * gate, and every column the table renders.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-students.php` runs it;
 * a browser gets nothing.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

$theme = ZANDI_THEME;
chdir( $theme );

require $theme . '/inc/courses.php';
require $theme . '/inc/template-tags.php';
require $theme . '/inc/auth.php';
require $theme . '/inc/placement.php';
require $theme . '/inc/students.php';
require $theme . '/inc/class-zandi-students-table.php';

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;

	if ( $got === $want ) {
		++$pass;
		echo "  ok   $label\n";
		return;
	}

	++$fail;
	echo "  FAIL $label\n       got:  " . var_export( $got, true ) . "\n       want: " . var_export( $want, true ) . "\n";
}

function check_true( $label, $got ) {
	global $pass, $fail;
	if ( $got ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n";
}

echo "\n— CSV cells (formula injection) —\n";
check( 'a plain name passes through', zandi_students_csv_cell( 'شیما زندی' ), 'شیما زندی' );
check( 'an = formula is defused', zandi_students_csv_cell( '=cmd|calc' ), "'=cmd|calc" );
check( 'a + formula is defused', zandi_students_csv_cell( '+1+1' ), "'+1+1" );
check( 'a - formula is defused', zandi_students_csv_cell( '-2+3' ), "'-2+3" );
check( 'an @ formula is defused', zandi_students_csv_cell( '@SUM(A1)' ), "'@SUM(A1)" );
check( 'a leading tab is defused', zandi_students_csv_cell( "\tx" ), "'\tx" );
check( 'an empty cell stays empty', zandi_students_csv_cell( '' ), '' );
check( 'a phone keeps its leading zero', zandi_students_csv_cell( '09121234567' ), '09121234567' );

echo "\n— Levels offered by the filter —\n";
$levels = zandi_students_levels();
check( 'starts below A1', $levels[0], 'pre-A1' );
check_true( 'includes the half steps', in_array( 'A1+', $levels, true ) && in_array( 'A2+', $levels, true ) );
$bands = zandi_placement_bands();
$top   = end( $bands );
check( 'ends on the top band', end( $levels ), $top['id'] );
check_true( 'does not offer a half step above the top band', ! in_array( $top['id'] . '+', $levels, true ) );
check( 'one entry per band, plus a half step for all but the top, plus pre-A1', count( $levels ), count( $bands ) * 2 );
echo '       ' . implode( ' · ', $levels ) . "\n";

echo "\n— The flat mirror —\n";
$result = zandi_placement_score( array( 1 => 0, 2 => 0, 3 => 0 ), array( 'duration' => 300 ) );
zandi_placement_mirror( 7, $result );
check( 'level is mirrored', get_user_meta( 7, 'zandi_placement_level', true ), $result['level'] );
check( 'score is mirrored', (int) get_user_meta( 7, 'zandi_placement_score', true ), (int) $result['correct'] );
check_true( 'time is mirrored', (int) get_user_meta( 7, 'zandi_placement_time', true ) > 0 );
zandi_placement_mirror( 8, array() );
check( 'an empty result writes nothing', get_user_meta( 8, 'zandi_placement_level', true ), '' );

echo "\n— The sitting tally —\n";
zandi_placement_tally( array( 'level' => 'A2' ), 0 );
zandi_placement_tally( array( 'level' => 'A2' ), 5 );
zandi_placement_tally( array( 'level' => 'B1' ), 0 );
$tally = zandi_placement_tally_data();
check( 'counts every sitting', $tally['total'], 3 );
check( 'counts the ones with no account', $tally['guests'], 2 );
check( 'counts per level', $tally['levels']['A2'], 2 );
check_true( 'records when it started', $tally['since'] > 0 );

echo "\n— The report is not readable for someone else without the capability —\n";
$_GET['student'] = '7';
$GLOBALS['stub_caps'] = true;
check( 'the owner may name a student', zandi_placement_report_user(), 7 );
$GLOBALS['stub_caps'] = false;
check( 'a student may not', zandi_placement_report_user(), 0 );
unset( $_GET['student'] );
$GLOBALS['stub_caps'] = true;
check( 'no parameter, no student', zandi_placement_report_user(), 0 );

echo "\n— The level filter is a whitelist —\n";
$_REQUEST['zandi_level'] = 'A1+';
check( 'a real level passes', zandi_students_filter_level(), 'A1+' );
$_REQUEST['zandi_level'] = 'A1 ';
check( 'a plus decoded as a space is recovered', zandi_students_filter_level(), 'A1+' );
$_REQUEST['zandi_level'] = 'none';
check( 'the no-test filter passes', zandi_students_filter_level(), 'none' );
$_REQUEST['zandi_level'] = 'C2';
check( 'a level the bank does not have is dropped', zandi_students_filter_level(), '' );
$_REQUEST['zandi_level'] = "<script>alert(1)</script>";
check( 'anything else is dropped', zandi_students_filter_level(), '' );
unset( $_REQUEST['zandi_level'] );
check( 'no filter, no value', zandi_students_filter_level(), '' );

echo "\n— Table columns —\n";
$table = new Zandi_Students_Table();

$user                  = new WP_User();
$user->ID              = 7;
$user->display_name    = 'مریم رضایی';
$user->user_login      = '09121234567';
$user->user_registered = '2026-07-14 08:30:00';
$GLOBALS['stub_users'][7] = $user;

$GLOBALS['stub_meta'][7]['zandi_phone']         = array( '09121234567' );
$GLOBALS['stub_meta'][7]['zandi_placement_level'] = array( 'A2+' );
$GLOBALS['stub_meta'][7]['zandi_course_owned']  = array( 'a1', 'a2' );
$GLOBALS['stub_meta'][7]['_money_spent']        = array( '2400000' );
$GLOBALS['stub_meta'][7]['zandi_last_login']    = array( time() - 3600 );
$GLOBALS['stub_meta'][7]['zandi_placement_result'] = array( $result );

$columns = array_keys( $table->get_columns() );
check( 'eight columns', count( $columns ), 8 );

foreach ( $columns as $column ) {
	$method = 'column_' . $column;
	$out    = $table->$method( $user );
	check_true( "column «{$column}» renders something", is_string( $out ) && '' !== $out );
	echo "       {$column}: " . preg_replace( '/\s+/', ' ', trim( strip_tags( $out ) ) ) . "\n";
}

$level_cell = $table->column_level( $user );
check_true( 'the A2+ chip isolates the code so it does not read +A2', false !== strpos( $level_cell, '<span dir="ltr">A2+</span>' ) );

$sortable = $table->get_sortable_columns();
check( 'only two columns sort', array_keys( $sortable ), array( 'name', 'joined' ) );
check_true( 'neither sorts on user meta', ! in_array( 'meta_value', array_column( $sortable, 0 ), true ) );

echo "\n— Copy —\n";
$copy = zandi_students_copy();
check_true( 'every key the export header asks for exists', isset( $copy['col_name'], $copy['col_phone'], $copy['col_email'], $copy['col_level'], $copy['score_title'], $copy['answered'], $copy['blank'], $copy['col_courses'], $copy['col_paid'], $copy['col_joined'], $copy['col_seen'] ) );
check_true( 'no support channel is named anywhere in the copy', ! preg_match( '/t\.me|telegram|whatsapp|instagram/i', wp_json_encode( $copy ) ) );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
