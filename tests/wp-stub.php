<?php
/**
 * A thin stand-in for the WordPress functions the students screen touches,
 * so the new code can be run outside an install. Not a WordPress emulator —
 * just enough to exercise it.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. the test files require it;
 * a browser gets nothing.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

/*
 * ABSPATH has to be a real directory: zandi_students_render_list() requires
 * wp-admin/includes/class-wp-list-table.php out of it. The class itself comes
 * from this file, so the scaffolding is an empty placeholder built in the
 * system temp directory rather than committed into the theme.
 */
$zandi_stub_root = rtrim( sys_get_temp_dir(), '/' ) . '/zandi-wp-stub/';

if ( ! is_dir( $zandi_stub_root . 'wp-admin/includes' ) ) {
	mkdir( $zandi_stub_root . 'wp-admin/includes', 0777, true );
}

file_put_contents( $zandi_stub_root . 'wp-admin/includes/class-wp-list-table.php', '<?php // WP_List_Table is defined in tests/wp-stub.php.' );

define( 'ZANDI_THEME', dirname( __DIR__ ) );
define( 'ABSPATH', $zandi_stub_root );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['stub_meta']    = array();
$GLOBALS['stub_options'] = array();
$GLOBALS['stub_actions'] = array();
$GLOBALS['stub_caps']    = true;

function add_action( $h, $c, $p = 10, $a = 1 ) { $GLOBALS['stub_actions'][] = array( $h, $c ); }
function add_filter( $h, $c, $p = 10, $a = 1 ) { $GLOBALS['stub_actions'][] = array( $h, $c ); }
function remove_filter( $h, $c, $p = 10 ) {}
function do_action( $h ) {}
function apply_filters( $h, $v ) { return $v; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $t ) { return esc_html( $t ); }
function esc_url( $u ) { return $u; }
function esc_url_raw( $u ) { return $u; }
function wp_kses_post( $t ) { return $t; }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_key( $t ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $t ) ); }
function wp_unslash( $t ) { return $t; }
function absint( $n ) { return abs( (int) $n ); }
function number_format_i18n( $n ) { return number_format( $n ); }
function get_user_meta( $id, $key, $single = false ) {
	$v = isset( $GLOBALS['stub_meta'][ $id ][ $key ] ) ? $GLOBALS['stub_meta'][ $id ][ $key ] : array();
	return $single ? ( $v ? $v[0] : '' ) : $v;
}
function update_user_meta( $id, $key, $value ) { $GLOBALS['stub_meta'][ $id ][ $key ] = array( $value ); }
function add_user_meta( $id, $key, $value ) { $GLOBALS['stub_meta'][ $id ][ $key ][] = $value; }
function delete_user_meta( $id, $key ) { unset( $GLOBALS['stub_meta'][ $id ][ $key ] ); }
function get_option( $k, $d = false ) { return isset( $GLOBALS['stub_options'][ $k ] ) ? $GLOBALS['stub_options'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['stub_options'][ $k ] = $v; }
function get_transient( $k ) { return false; }
function set_transient( $k, $v, $t ) {}
function delete_transient( $k ) {}
function current_user_can( $cap ) { return $GLOBALS['stub_caps']; }
function get_current_user_id() { return 0; }
function wp_date( $f, $t = null ) { return gmdate( $f, $t ); }
function wp_timezone() { return new DateTimeZone( 'UTC' ); }
function get_theme_file_path( $p = '' ) { return ZANDI_THEME . '/' . $p; }
function get_theme_file_uri( $p = '' ) { return 'https://example.test/' . $p; }
function add_query_arg( $args, $url = '' ) {
	if ( ! is_array( $args ) ) { $args = array( $args => func_get_arg( 1 ) ); $url = func_get_arg( 2 ); }
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $args );
}
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function wp_nonce_url( $u, $a ) { return $u . '&_wpnonce=stub'; }
function get_edit_user_link( $id ) { return admin_url( 'user-edit.php?user_id=' . $id ); }
function selected( $a, $b, $e = true ) { return $a === $b ? ' selected' : ''; }
function submit_button() {}
function wp_list_pluck( $rows, $field ) { return array_map( function ( $r ) use ( $field ) { return is_object( $r ) ? $r->$field : $r[ $field ]; }, $rows ); }
function cache_users( $ids ) {}
function wp_generate_password( $l = 12, $s = true ) { return substr( str_repeat( 'abc123', 10 ), 0, $l ); }
function wp_hash( $d ) { return md5( $d ); }
function is_user_logged_in() { return true; }
function is_admin() { return true; }
function wp_doing_ajax() { return false; }
function is_feed() { return false; }
function get_userdata( $id ) { return isset( $GLOBALS['stub_users'][ $id ] ) ? $GLOBALS['stub_users'][ $id ] : false; }
function get_user_by( $f, $v ) { return false; }
function get_users( $args ) { return array(); }
function wc_get_orders( $args ) { return array(); }
function class_exists_stub() {}

/** A stand-in for the bits of WP_User the screen reads. */
class WP_User {
	public $ID = 0;
	public $display_name = '';
	public $user_login = '';
	public $user_email = '';
	public $user_registered = '2026-08-01 09:00:00';
	public $first_name = '';
	public $roles = array( 'subscriber' );
}

/** Enough of WP_List_Table to instantiate the subclass and call its columns. */
class WP_List_Table {
	public $items = array();
	public $_column_headers = array();
	protected $_args = array();
	public function __construct( $args = array() ) { $this->_args = $args; }
	public function get_items_per_page( $o, $d = 20 ) { return $d; }
	public function get_pagenum() { return 1; }
	public function set_pagination_args( $a ) {}
	public function row_actions( $a, $always = false ) { return '<div class="row-actions">' . implode( ' | ', $a ) . '</div>'; }
	public function has_items() { return (bool) $this->items; }
	public function search_box( $t, $i ) {}
	public function display() {}
}

function wp_json_encode( $v ) { return json_encode( $v, JSON_UNESCAPED_UNICODE ); }

/** Enough of WP_User_Query for the tiles and the backfill. */
class WP_User_Query {
	public $args = array();
	public function __construct( $args = array() ) { $this->args = $args; }
	public function get_results() {
		$rows = isset( $GLOBALS['stub_user_results'] ) ? $GLOBALS['stub_user_results'] : array();

		// The real query returns integers for fields => ids, and the backfill
		// depends on that, so the stub has to honour it too.
		if ( isset( $this->args['fields'] ) && 'ids' === $this->args['fields'] ) {
			return array_map( function ( $u ) { return is_object( $u ) ? (int) $u->ID : (int) $u; }, $rows );
		}

		return $rows;
	}
	public function get_total() { return isset( $GLOBALS['stub_user_total'] ) ? $GLOBALS['stub_user_total'] : 0; }
}

/** Enough of $wpdb for the revenue sum. */
class Stub_WPDB {
	public $usermeta = 'wp_usermeta';
	public function prepare( $sql ) { return $sql; }
	public function get_var( $sql ) { return 9600000; }
}

$GLOBALS['wpdb'] = new Stub_WPDB();

/* WooCommerce is deliberately absent: this is the degraded path. */
function zandi_woo_active() { return false; }
function zandi_student_courses( $user_id = 0 ) { return array(); }

/* Defined in functions.php, which the render test does not load. */
function zandi_pretty_permalinks() { return true; }
function user_trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
