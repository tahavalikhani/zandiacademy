<?php
/**
 * دوره مکالمه A1: a course page anybody may read and nobody may buy — yet.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-conversation.php`
 * runs it; a browser gets nothing.
 *
 * The owner's instruction on 24 September 2026 was one sentence: do not let
 * them get the new course. So most of this file is about the doors — every way
 * a purchase could start — and proves each one is shut, including the ones the
 * page never shows: a hand-built POST, and a product linked early in wp-admin
 * that WooCommerce would otherwise happily sell from its own product page.
 *
 * The rest pins the page the owner wrote: her comparison, her FAQ in place of
 * the shared one, and no price anywhere, because there is none yet and «۰
 * تومان» would read as free.
 *
 * It loads inc/woocommerce.php — the first test to do so — with the stub's
 * zandi_woo_active() renamed out of the way, the same technique
 * test-sections.php uses on functions.php.
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

if ( ! class_exists( 'Walker_Nav_Menu' ) ) {
	class Walker_Nav_Menu {}
}

/* A product, as much of one as the enrol path asks about. */
class WC_Product {
	public $id;
	public function __construct( $id ) { $this->id = (int) $id; }
	public function get_id() { return $this->id; }
	public function is_purchasable() { return (bool) apply_filters( 'woocommerce_is_purchasable', true, $this ); }
	public function is_in_stock() { return true; }
}

function wc_get_product( $id ) { return $id ? new WC_Product( $id ) : false; }
function wp_verify_nonce( $nonce, $action ) { return 'good' === $nonce; }
function status_header( $code ) {}
function nocache_headers() {}
function get_query_template( $type ) { return ''; }
function is_front_page() { return false; }
function is_singular() { return false; }
function comments_open() { return false; }

$GLOBALS['stub_logged_in'] = false;
$GLOBALS['stub_is_admin']  = false;

// functions.php, for the no-WooCommerce enrol handler and the routing helpers.
$zandi_src = file_get_contents( ZANDI_THEME . '/functions.php' );
$zandi_src = preg_replace( '/^require_once get_theme_file_path\(.*$/m', '', $zandi_src );

foreach ( array( 'zandi_course_url', 'zandi_is_rtl', 'zandi_pretty_permalinks', 'zandi_section_url' ) as $zandi_dupe ) {
	$zandi_src = preg_replace( '/^function\s+' . $zandi_dupe . '\b/m', 'function _stubbed_' . $zandi_dupe, $zandi_src );
}

eval( '?>' . $zandi_src );

foreach ( array( 'content', 'courses', 'panel', 'icons', 'template-tags', 'auth', 'placement', 'podcast' ) as $zandi_file ) {
	require ZANDI_THEME . '/inc/' . $zandi_file . '.php';
}

$zandi_src = file_get_contents( ZANDI_THEME . '/inc/woocommerce.php' );
$zandi_src = preg_replace( '/^function\s+zandi_woo_active\b/m', 'function _stubbed_zandi_woo_active', $zandi_src );

/*
 * The bridge returns early when WooCommerce is off, before its hooks register
 * — which is right in production and is exactly what this test needs to get
 * past. On for the load, off again after, so only the functions that ask see
 * WooCommerce.
 */
$GLOBALS['stub_woo_active'] = true;
eval( '?>' . $zandi_src );
$GLOBALS['stub_woo_active'] = false;

$pass = 0;
$fail = 0;

function check_true( $label, $got ) {
	global $pass, $fail;
	if ( $got ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n";
}

function render_part( $name, $course ) {
	ob_start();
	get_template_part( 'template-parts/course/' . $name, null, array( 'course' => $course ) );
	return (string) ob_get_clean();
}

function redirect_of( $callback ) {
	try {
		call_user_func( $callback );
	} catch ( Zandi_Stub_Redirect $e ) {
		return $e->getMessage();
	}

	return '';
}

$conv = zandi_get_course( 'conversation-a1' );

echo "\n— The course exists, and is not on sale —\n";
check_true( 'it is in the catalogue', is_array( $conv ) );
check_true( 'at /courses/conversation-a1/', 'https://example.test/courses/conversation-a1/' === zandi_course_url( 'conversation-a1' ) );
check_true( 'it is marked coming soon', ! empty( $conv['coming_soon'] ) );
check_true( 'so it is not on sale', false === zandi_course_on_sale( 'conversation-a1' ) );
check_true( 'while A1, A2 and B1 still are', zandi_course_on_sale( 'a1' ) && zandi_course_on_sale( 'a2' ) && zandi_course_on_sale( 'b1' ) );
check_true( 'and a course that does not exist is not', false === zandi_course_on_sale( 'a3' ) );
check_true( 'it belongs to the conversation family', 'conversation' === $conv['family'] );

echo "\n— Every door to a purchase is shut —\n";

// Link a purchasable product to every course, as the owner might on launch eve.
add_filter(
	'zandi_course_product_id',
	function ( $id, $slug ) {
		return 'conversation-a1' === $slug ? 901 : ( 'a1' === $slug ? 101 : 0 );
	},
	10,
	2
);
update_post_meta( 901, '_zandi_course', 'conversation-a1' );
update_post_meta( 101, '_zandi_course', 'a1' );

check_true( 'the control says soon — even with a product linked', 'soon' === zandi_course_enrol_state( 'conversation-a1' ) );
check_true( 'while A1, with the same arrangement, sells', 'buy' === zandi_course_enrol_state( 'a1' ) );

ob_start();
zandi_enrol_control( $conv, array( 'label' => 'ثبت‌نام در دوره', 'block' => true, 'id' => 'enrol' ) );
$zandi_soon = (string) ob_get_clean();

check_true( 'no form is drawn for it', false === strpos( $zandi_soon, '<form' ) );
check_true( 'nor a link to arrange it by message', false === strpos( $zandi_soon, '<a class="c-btn' ) );
check_true( 'it is a status, not a button', (bool) preg_match( '/<p class="c-btn c-btn--soon c-btn--block" id="enrol">/', $zandi_soon ) );
check_true( 'and says so: «ثبت‌نام به‌زودی»', false !== strpos( $zandi_soon, 'ثبت‌نام به‌زودی' ) );
check_true( 'the #enrol target survives, so the header\'s «ثبت نام» lands on the answer', false !== strpos( $zandi_soon, 'id="enrol"' ) );
check_true( 'the gift the owner wrote under it is kept', false !== strpos( $zandi_soon, 'c-perk' ) );

ob_start();
zandi_enrol_control( zandi_get_course( 'a1' ), array( 'label' => 'ثبت‌نام در دوره' ) );
$zandi_buy = (string) ob_get_clean();
check_true( 'A1 still gets its enrol form', false !== strpos( $zandi_buy, '<form' ) && false === strpos( $zandi_buy, 'c-btn--soon' ) );

$_POST = array( 'zandi_enrol_nonce' => 'good', 'course' => 'conversation-a1' );
$zandi_to = redirect_of( 'zandi_woo_handle_enrol' );
check_true( 'a hand-built POST is turned away: ' . $zandi_to, false !== strpos( $zandi_to, 'enrol=soon' ) );
check_true( 'back to the course page, not to a checkout', 0 === strpos( $zandi_to, 'https://example.test/courses/conversation-a1/' ) );

$zandi_to = redirect_of( 'zandi_handle_enrol' );
check_true( 'and the same with WooCommerce off: ' . $zandi_to, false !== strpos( $zandi_to, 'enrol=soon' ) );
check_true( 'which is not «pending» — nobody is told to arrange it by message', false === strpos( $zandi_to, 'enrol=pending' ) );
$_POST = array();

check_true( 'the bounce says why', 'ثبت‌نام این دوره هنوز باز نشده.' === ( function () {
	$_GET['enrol'] = 'soon';
	$zandi_note    = zandi_enrol_notice();
	unset( $_GET['enrol'] );
	return $zandi_note;
} )() );

$GLOBALS['stub_woo_active'] = true;
check_true( 'its product cannot be bought from /shop/ either', false === zandi_woo_block_unreleased( true, new WC_Product( 901 ) ) );
check_true( 'while A1\'s can', true === zandi_woo_block_unreleased( true, new WC_Product( 101 ) ) );
check_true( 'and a product that is not a course is left alone', true === zandi_woo_block_unreleased( true, new WC_Product( 555 ) ) );
$GLOBALS['stub_woo_active'] = false;

echo "\n— The lists that sell leave it out; the catalogue keeps it —\n";
$zandi_home = zandi_courses( false );
$zandi_all  = zandi_courses( true );
$zandi_urls = function ( $cards ) { return array_filter( array_column( $cards, 'url' ) ); };

check_true( 'the homepage lists only what can be bought', ! in_array( zandi_course_url( 'conversation-a1' ), $zandi_urls( $zandi_home ), true ) );
check_true( '/courses/ lists it, with a link to its page', in_array( zandi_course_url( 'conversation-a1' ), $zandi_urls( $zandi_all ), true ) );

$zandi_card = current( array_filter( $zandi_all, function ( $c ) { return zandi_course_url( 'conversation-a1' ) === $c['url']; } ) );
check_true( 'marked «به‌زودی»', 'به‌زودی' === $zandi_card['badge'] );
check_true( 'after everything that can be bought', array_search( $zandi_card, $zandi_all, true ) > 2 );
check_true( '«از صفر شروع کن» still marks A1', 'از صفر شروع کن' === $zandi_all[0]['badge'] && 'دوره پایه A1' === $zandi_all[0]['title'] );
check_true( 'every card carries its slug — the cover srcset reads it', count( $zandi_all ) - count( zandi_upcoming_courses() ) === count( array_filter( array_column( $zandi_all, 'slug' ) ) ) );
check_true( 'the old «مکالمه A1 · A2 · B1» card now announces the other two', in_array( 'دوره مکالمه A2 · B1', array_column( zandi_upcoming_courses(), 'title' ), true ) );

$zandi_footer = zandi_footer_columns();
check_true( 'the footer lists what can be bought', ! in_array( 'دوره مکالمه A1', array_column( $zandi_footer[0]['links'], 'label' ), true ) && 3 === count( $zandi_footer[0]['links'] ) );

$zandi_next = zandi_panel_next_course(
	array(
		array( 'slug' => 'a1' ),
		array( 'slug' => 'a2' ),
		array( 'slug' => 'b1' ),
	)
);
check_true( 'the panel never suggests it as «قدم بعدی»', null === $zandi_next );

echo "\n— The page is the owner's —\n";
check_true( 'no syllabus and no method block; a comparison and «how» instead', array( 'about-course', 'compare', 'how', 'deliverables', 'sample-lesson', 'fit', 'shima', 'testimonials' ) === zandi_course_sections( $conv ) );
check_true( 'A1 keeps the page it had', array( 'about-course', 'deliverables', 'method', 'sample-lesson', 'curriculum', 'fit', 'shima', 'testimonials' ) === zandi_course_sections( zandi_get_course( 'a1' ) ) );

$zandi_hero = render_part( 'hero', $conv );
check_true( 'the hero names no price', false === strpos( $zandi_hero, 'تومان' ) && false === strpos( $zandi_hero, '€' ) );
check_true( 'and says nothing about how to pay', false === strpos( $zandi_hero, 'پرداخت' ) && false === strpos( $zandi_hero, 'هماهنگ' ) );
check_true( 'its info card has no handouts row — this course has homework', false === strpos( $zandi_hero, 'جزوه' ) && false !== strpos( $zandi_hero, 'ویدیوهای ۵ تا ۲۵ دقیقه‌ای' ) );

$zandi_closing = render_part( 'closing', $conv );
check_true( 'the closing block names no price either', false === strpos( $zandi_closing, 'تومان' ) );

$zandi_faq = render_part( 'faq', $conv );
check_true( 'the FAQ is the owner\'s twelve', 12 === substr_count( $zandi_faq, 'accordion__item' ) );
check_true( 'and none of the shared ones — «۶ ماه» is not true of this course', false === strpos( $zandi_faq, '۶ ماه' ) );
check_true( 'A1 still gets the shared questions', false !== strpos( render_part( 'faq', zandi_get_course( 'a1' ) ), '۶ ماه' ) );

check_true( 'the trust bar says «موضوع‌محور»', false !== strpos( render_part( 'trust', $conv ), 'موضوع‌محور' ) );
check_true( 'the jumps go to the comparison, not a syllabus', false !== strpos( render_part( 'intro-video', $conv ), 'href="#compare"' ) && false === strpos( render_part( 'intro-video', $conv ), 'href="#curriculum"' ) );
check_true( 'the four deliverables are two across', false !== strpos( render_part( 'deliverables', $conv ), 'c-cards--2' ) );
check_true( 'and keep the owner\'s line breaks', false !== strpos( render_part( 'deliverables', $conv ), '<br />' ) );
check_true( 'A1\'s stay four across', false !== strpos( render_part( 'deliverables', zandi_get_course( 'a1' ) ), 'c-cards--4' ) );
check_true( 'Shima says why she built this one', false !== strpos( render_part( 'shima', $conv ), 'دوره مکالمه رو برای کسایی ساختم' ) );
check_true( 'and A1 keeps its own words', false !== strpos( render_part( 'shima', zandi_get_course( 'a1' ) ), 'راهنمای تور' ) );

echo "\n— The comparison —\n";
$zandi_cmp = render_part( 'compare', $conv );
$zandi_fam = zandi_course_families();

check_true( 'it is where the jump points', false !== strpos( $zandi_cmp, 'id="compare"' ) );
check_true( 'two columns', 2 === substr_count( $zandi_cmp, 'class="c-compare__col' ) );
check_true( 'six points in each, the owner\'s', 6 === count( $zandi_fam['main']['items'] ) && 6 === count( $zandi_fam['conversation']['items'] ) && 12 === substr_count( $zandi_cmp, 'c-compare__tick' ) );
check_true( 'the conversation column is this page\'s, so it is the navy one', (bool) preg_match( '/c-compare__col c-compare__col--current"\s+aria-labelledby="compare-conversation"/', $zandi_cmp ) );
check_true( 'and only that one', 1 === substr_count( $zandi_cmp, 'c-compare__col--current' ) );
check_true( 'the main courses come first — the reading start, so the arrow reads forward', strpos( $zandi_cmp, 'compare-main' ) < strpos( $zandi_cmp, 'compare-conversation' ) );
check_true( 'joined by the owner\'s «یه قدم جلوتر»', false !== strpos( $zandi_cmp, 'یه قدم جلوتر' ) );
check_true( 'no ✓/✗ grid claiming the main courses lack anything', false === strpos( $zandi_cmp, '✗' ) && false === strpos( $zandi_cmp, 'c-mark--no' ) );
check_true( 'two steps under it', 2 === substr_count( $zandi_cmp, '<li class="c-path__step' ) );
check_true( 'the first goes to A1', false !== strpos( $zandi_cmp, 'href="https://example.test/courses/a1/"' ) );
check_true( 'the second is this page, marked «همین دوره»', false !== strpos( $zandi_cmp, 'c-path__step--here' ) && false !== strpos( $zandi_cmp, 'همین دوره' ) );
check_true( 'step numbers are Persian', false !== strpos( $zandi_cmp, '>۱</span>' ) && false !== strpos( $zandi_cmp, '>۲</span>' ) );
check_true( 'the link to A1 has its own name for a screen reader', false !== strpos( $zandi_cmp, 'مشاهده دوره دوره پایه A1' ) );

// What is drawn: the screen-reader-only names are read, never laid out.
$zandi_drawn = preg_replace( '/<span class="screen-reader-text">.*?<\/span>/s', '', $zandi_cmp );
preg_match_all( '/\b[A-B][12]\b/', wp_strip_all_tags_for_test( $zandi_drawn ), $zandi_codes );
preg_match_all( '/<span dir="ltr" class="latin-run">[^<]*[A-B][12][^<]*<\/span>/', $zandi_drawn, $zandi_isolated );
check_true( 'every level code in it sits in its own isolate (' . count( $zandi_codes[0] ) . ')', count( $zandi_codes[0] ) > 0 && count( $zandi_codes[0] ) === count( $zandi_isolated[0] ) );

$zandi_how = render_part( 'how', $conv );
check_true( '«how a session goes» draws the two kinds of video', 2 === substr_count( $zandi_how, 'class="c-card ' ) );
check_true( 'and ends at the sample lesson', false !== strpos( $zandi_how, 'href="#sample-lesson"' ) );
check_true( 'a course without that entry draws nothing', '' === trim( render_part( 'how', zandi_get_course( 'a1' ) ) ) );

echo "\n— The stylesheets —\n";
$zandi_css   = preg_replace( '#/\*.*?\*/#s', '', file_get_contents( ZANDI_THEME . '/assets/css/courses.css' ) );
$zandi_style = file_get_contents( ZANDI_THEME . '/style.css' );

preg_match( '/21\. The comparison.*?22\. /s', file_get_contents( ZANDI_THEME . '/assets/css/courses.css' ), $zandi_block );
check_true( 'the comparison uses no red', isset( $zandi_block[0] ) && false === strpos( $zandi_block[0], 'rouge' ) && false === stripos( $zandi_block[0], '#c8102e' ) );
check_true( 'the soon control cannot look pressable', (bool) preg_match( '/\.c-btn--soon \{[^}]*cursor:\s*default/s', $zandi_css ) );
check_true( 'the centred pieces are in style.css\'s restore list', false !== strpos( $zandi_style, '.c-how__outro,' ) && false !== strpos( $zandi_style, '.course-page .c-path__title,' ) && false !== strpos( $zandi_style, '.course-page .c-compare__bridge-label,' ) );

echo "\n" . ( $fail ? "$pass passed, $fail failed\n" : "$pass passed, 0 failed\n" );
exit( $fail ? 1 : 0 );

function wp_strip_all_tags_for_test( $html ) {
	return strip_tags( $html );
}
