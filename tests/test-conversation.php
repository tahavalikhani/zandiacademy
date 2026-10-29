<?php
/**
 * The conversation courses: A1 and A2 on sale, B1 readable and not for sale.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-conversation.php`
 * runs it; a browser gets nothing.
 *
 * The owner's instruction on 24 September 2026 was one sentence: do not let
 * them get the new course. On 23 October 2026 she opened A1 and A2 and kept B1
 * «به‌زودی». So this file proves both halves: A1 and A2 sell once a product is
 * linked — and print no «۰ تومان» before it is — while every door to B1 stays
 * shut, including the ones the page never shows: a hand-built POST, and a
 * product linked early in wp-admin that WooCommerce would otherwise happily
 * sell from its own product page.
 *
 * The rest pins the pages the owner wrote: her comparison, her FAQ in place of
 * the shared one, no support or interview, and no detail borrowed from A1.
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
if ( ! function_exists( '__return_true' ) ) {
	function __return_true() { return true; }
}
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

foreach ( array( 'content', 'courses', 'panel', 'icons', 'template-tags', 'auth', 'placement', 'podcast', 'seo' ) as $zandi_file ) {
	require ZANDI_THEME . '/inc/' . $zandi_file . '.php';
}

// The sitemap provider extends a core class; an empty one is all it needs here.
if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) {
	class WP_Sitemaps_Provider {}
}
require ZANDI_THEME . '/inc/class-zandi-sitemap-provider.php';

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

// Every string in a course entry, however deeply nested.
$zandi_strings_of = function ( $value ) {
	$out = array();

	array_walk_recursive(
		$value,
		function ( $leaf ) use ( &$out ) {
			if ( is_string( $leaf ) ) {
				$out[] = $leaf;
			}
		}
	);

	return $out;
};

$soonc = zandi_get_course( 'conversation-b1' );

/*
 * Which courses have a product linked. A filter rather than fixed IDs, so one
 * check can unlink a course and see what a page does before the owner has
 * made its product in wp-admin.
 */
$GLOBALS['zandi_linked'] = array(
	'a1'              => 101,
	'conversation-a1' => 901,
	'conversation-a2' => 902,
	'conversation-b1' => 903,
);
add_filter(
	'zandi_course_product_id',
	function ( $id, $slug ) {
		return isset( $GLOBALS['zandi_linked'][ $slug ] ) ? $GLOBALS['zandi_linked'][ $slug ] : 0;
	},
	10,
	2
);
foreach ( $GLOBALS['zandi_linked'] as $zandi_slug => $zandi_pid ) {
	update_post_meta( $zandi_pid, '_zandi_course', $zandi_slug );
}

echo "\n— مکالمه A1 and A2 are on sale; مکالمه B1 is not —\n";
check_true( 'A1 is in the catalogue', is_array( $conv ) );
check_true( 'at /courses/conversation-a1/', 'https://example.test/courses/conversation-a1/' === zandi_course_url( 'conversation-a1' ) );
check_true( 'A1 and A2 are not «coming soon» any more', empty( $conv['coming_soon'] ) && empty( zandi_get_course( 'conversation-a2' )['coming_soon'] ) );
check_true( 'so both are on sale', zandi_course_on_sale( 'conversation-a1' ) && zandi_course_on_sale( 'conversation-a2' ) );
check_true( 'B1 still is coming soon, and not on sale', ! empty( $soonc['coming_soon'] ) && false === zandi_course_on_sale( 'conversation-b1' ) );
check_true( 'the main courses are on sale as before', zandi_course_on_sale( 'a1' ) && zandi_course_on_sale( 'a2' ) && zandi_course_on_sale( 'b1' ) );
check_true( 'and a course that does not exist is not', false === zandi_course_on_sale( 'a3' ) );
check_true( 'all three belong to the conversation family', 'conversation' === $conv['family'] && 'conversation' === $soonc['family'] );
check_true( 'the search snippet stops saying «به‌زودی» for A1 and A2', false === strpos( $conv['meta_description'], 'به‌زودی' ) && false === strpos( zandi_get_course( 'conversation-a2' )['meta_description'], 'به‌زودی' ) );
check_true( 'and keeps saying it for B1', false !== strpos( $soonc['meta_description'], 'به‌زودی' ) );

echo "\n— With a product linked, A1 and A2 sell —\n";
foreach ( array( 'conversation-a1', 'conversation-a2' ) as $zandi_slug ) {
	ob_start();
	zandi_enrol_control( zandi_get_course( $zandi_slug ), array( 'label' => 'ثبت‌نام در دوره', 'block' => true, 'id' => 'enrol' ) );
	$zandi_buy = (string) ob_get_clean();

	check_true( "$zandi_slug: the control is «buy»", 'buy' === zandi_course_enrol_state( $zandi_slug ) );
	check_true( "$zandi_slug: an enrol form posting this course", false !== strpos( $zandi_buy, '<form' ) && false !== strpos( $zandi_buy, 'name="course" value="' . $zandi_slug . '"' ) && false === strpos( $zandi_buy, 'c-btn--soon' ) );
	check_true( "$zandi_slug: and no podcast gift under it — that is the main courses'", false === strpos( $zandi_buy, 'c-perk' ) );
}

$GLOBALS['stub_woo_active'] = true;
check_true( 'their products can be bought from their own pages', true === zandi_woo_block_unreleased( true, new WC_Product( 901 ) ) && true === zandi_woo_block_unreleased( true, new WC_Product( 902 ) ) );
$GLOBALS['stub_woo_active'] = false;

echo "\n— The owner's prices, and no dead button before a product is linked —\n";

/*
 * The owner's prices of 29 October 2026, held the way the main courses hold
 * theirs: the figure the page shows until a product is linked, after which the
 * product's price wins. A product still has to exist for the button to sell.
 */
check_true( 'مکالمه A1 is ۳٬۹۹۷٬۰۰۰ تومان', 3997000 === (int) $conv['price_toman'] );
check_true( 'مکالمه A2 is ۴٬۹۹۷٬۰۰۰ تومان', 4997000 === (int) zandi_get_course( 'conversation-a2' )['price_toman'] );
check_true( 'مکالمه B1 is ۵٬۹۹۷٬۰۰۰ تومان, kept for its launch', 5997000 === (int) $soonc['price_toman'] );

unset( $GLOBALS['zandi_linked']['conversation-a2'] );
$zandi_a2 = zandi_get_course( 'conversation-a2' );
check_true( 'with no product the state is «unavailable», as for a main course', 'unavailable' === zandi_course_enrol_state( 'conversation-a2' ) );
check_true( 'so the button sends people to the contact page, not into a bounce', false !== strpos( render_part( 'hero', $zandi_a2 ), 'ثبت‌نام با هماهنگی' ) );
check_true( 'and the hero still shows her price', false !== strpos( render_part( 'hero', $zandi_a2 ), '۴,۹۹۷,۰۰۰' ) );
check_true( 'with no euro line, since none was given', false === strpos( render_part( 'hero', $zandi_a2 ), '€' ) );
check_true( 'the other-courses row shows both prices and never «۰ تومان»', false !== strpos( render_part( 'other-courses', zandi_get_course( 'a1' ) ), '۳,۹۹۷,۰۰۰ تومان' ) && false !== strpos( render_part( 'other-courses', zandi_get_course( 'a1' ) ), '۴,۹۹۷,۰۰۰ تومان' ) && ! preg_match( '/>\s*۰ تومان/u', render_part( 'other-courses', zandi_get_course( 'a1' ) ) ) );
$GLOBALS['zandi_linked']['conversation-a2'] = 902;

// A level with no price at all still prints none — the guard stays for the next one.
$zandi_unpriced                = $conv;
$zandi_unpriced['price_toman'] = 0;
check_true( 'a course priced 0 prints no «۰ تومان»', false === strpos( render_part( 'hero', $zandi_unpriced ), 'تومان' ) && false === strpos( render_part( 'closing', $zandi_unpriced ), 'تومان' ) );

// What the page draws once the product's price arrives — zandi_woo_live_prices() writes it here.
$zandi_priced                = $conv;
$zandi_priced['price_toman'] = 2900000;
$zandi_hero                  = render_part( 'hero', $zandi_priced );
check_true( 'with a price, the hero shows it', false !== strpos( $zandi_hero, '۲,۹۰۰,۰۰۰' ) && false !== strpos( $zandi_hero, 'تومان' ) );
check_true( 'and no euro line, since none was given', false === strpos( $zandi_hero, '€' ) );
check_true( 'the closing button carries it too', false !== strpos( render_part( 'closing', $zandi_priced ), '۲,۹۰۰,۰۰۰ تومان' ) );
check_true( 'zandi_course_has_price() says no to 0 and yes to a price', ! zandi_course_has_price( $zandi_unpriced ) && zandi_course_has_price( $zandi_priced ) && ! zandi_course_has_price( $zandi_priced, 'euro' ) && zandi_course_has_price( zandi_get_course( 'a1' ), 'euro' ) );

echo "\n— Every door to buying مکالمه B1 is shut —\n";

check_true( 'the control says soon — even with a product linked', 'soon' === zandi_course_enrol_state( 'conversation-b1' ) );
check_true( 'while A1, with the same arrangement, sells', 'buy' === zandi_course_enrol_state( 'a1' ) );

ob_start();
zandi_enrol_control( $soonc, array( 'label' => 'ثبت‌نام در دوره', 'block' => true, 'id' => 'enrol' ) );
$zandi_soon = (string) ob_get_clean();

check_true( 'no form is drawn for it', false === strpos( $zandi_soon, '<form' ) );
check_true( 'nor a link to arrange it by message', false === strpos( $zandi_soon, '<a class="c-btn' ) );
check_true( 'it is a status, not a button', (bool) preg_match( '/<p class="c-btn c-btn--soon c-btn--block" id="enrol">/', $zandi_soon ) );
check_true( 'and says so: «ثبت‌نام به‌زودی»', false !== strpos( $zandi_soon, 'ثبت‌نام به‌زودی' ) );
check_true( 'the #enrol target survives, so the header\'s «ثبت نام» lands on the answer', false !== strpos( $zandi_soon, 'id="enrol"' ) );
check_true( 'and no podcast gift under it', false === strpos( $zandi_soon, 'c-perk' ) );

$_POST = array( 'zandi_enrol_nonce' => 'good', 'course' => 'conversation-b1' );
$zandi_to = redirect_of( 'zandi_woo_handle_enrol' );
check_true( 'a hand-built POST is turned away: ' . $zandi_to, false !== strpos( $zandi_to, 'enrol=soon' ) );
check_true( 'back to the course page, not to a checkout', 0 === strpos( $zandi_to, 'https://example.test/courses/conversation-b1/' ) );

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
check_true( 'its product cannot be bought from /shop/ either', false === zandi_woo_block_unreleased( true, new WC_Product( 903 ) ) );
check_true( 'while A1\'s can', true === zandi_woo_block_unreleased( true, new WC_Product( 101 ) ) );
check_true( 'and a product that is not a course is left alone', true === zandi_woo_block_unreleased( true, new WC_Product( 555 ) ) );
$GLOBALS['stub_woo_active'] = false;

$zandi_hero = render_part( 'hero', $soonc );
check_true( 'its hero names no price', false === strpos( $zandi_hero, 'تومان' ) && false === strpos( $zandi_hero, '€' ) );
check_true( 'and says nothing about how to pay', false === strpos( $zandi_hero, 'پرداخت' ) && false === strpos( $zandi_hero, 'هماهنگ' ) );
check_true( 'nor does its closing block', false === strpos( render_part( 'closing', $soonc ), 'تومان' ) );

echo "\n— The lists that sell carry A1 and A2; the catalogue keeps B1 —\n";
$zandi_home = zandi_courses( false );
$zandi_all  = zandi_courses( true );
$zandi_urls = function ( $cards ) { return array_filter( array_column( $cards, 'url' ) ); };

check_true( 'the homepage lists the five that can be bought, in order', array( 'a1', 'a2', 'b1', 'conversation-a1', 'conversation-a2' ) === array_column( $zandi_home, 'slug' ) );
check_true( 'but not B1', ! in_array( zandi_course_url( 'conversation-b1' ), $zandi_urls( $zandi_home ), true ) );
check_true( '/courses/ lists B1, with a link to its page', in_array( zandi_course_url( 'conversation-b1' ), $zandi_urls( $zandi_all ), true ) );

$zandi_card = current( array_filter( $zandi_all, function ( $c ) { return zandi_course_url( 'conversation-b1' ) === $c['url']; } ) );
check_true( 'marked «به‌زودی»', 'به‌زودی' === $zandi_card['badge'] );
check_true( 'after everything that can be bought', array_search( $zandi_card, $zandi_all, true ) > 4 );
check_true( 'A1 and A2 carry no badge — they are simply on sale', array( '', '' ) === array_values( array_column( array_filter( $zandi_all, function ( $c ) { return in_array( $c['slug'] ?? '', array( 'conversation-a1', 'conversation-a2' ), true ); } ), 'badge' ) ) );
check_true( '«از صفر شروع کن» still marks A1', 'از صفر شروع کن' === $zandi_all[0]['badge'] && 'دوره پایه A1' === $zandi_all[0]['title'] );
check_true( 'every card carries its slug — the cover srcset reads it', count( $zandi_all ) - count( zandi_upcoming_courses() ) === count( array_filter( array_column( $zandi_all, 'slug' ) ) ) );
check_true( 'no «دوره مکالمه …» announcement card is left — every level has a page', array() === array_filter( array_column( zandi_upcoming_courses(), 'title' ), function ( $t ) { return false !== strpos( $t, 'مکالمه' ); } ) );

$zandi_footer = array_column( zandi_footer_columns()[0]['links'], 'label' );
check_true( 'the footer lists the five that can be bought', 5 === count( $zandi_footer ) && in_array( 'دوره مکالمه A1', $zandi_footer, true ) && in_array( 'دوره مکالمه A2', $zandi_footer, true ) && ! in_array( 'دوره مکالمه B1', $zandi_footer, true ) );

$zandi_owns = function ( $slugs ) {
	return zandi_panel_next_course( array_map( function ( $s ) { return array( 'slug' => $s ); }, $slugs ) );
};
$zandi_next = $zandi_owns( array( 'a1', 'a2', 'b1' ) );
check_true( 'the panel suggests مکالمه A1 to a student who owns the three main courses', is_array( $zandi_next ) && 'conversation-a1' === ( $zandi_next['slug'] ?? '' ) );
check_true( 'and never B1, which cannot be bought', null === $zandi_owns( array( 'a1', 'a2', 'b1', 'conversation-a1', 'conversation-a2' ) ) );

echo "\n— The page is the owner's —\n";
check_true( 'no syllabus and no method block; a comparison and «how» instead', array( 'about-course', 'compare', 'how', 'deliverables', 'sample-lesson', 'fit', 'shima', 'testimonials' ) === zandi_course_sections( $conv ) );
check_true( 'A1 keeps the page it had', array( 'about-course', 'deliverables', 'method', 'sample-lesson', 'curriculum', 'fit', 'shima', 'testimonials' ) === zandi_course_sections( zandi_get_course( 'a1' ) ) );

$zandi_hero = render_part( 'hero', $conv );
check_true( 'its info card has no handouts row — this course has homework', false === strpos( $zandi_hero, 'جزوه' ) && false !== strpos( $zandi_hero, 'ویدیوهای ۵ تا ۲۵ دقیقه‌ای' ) );

$zandi_faq = render_part( 'faq', $conv );
check_true( 'the FAQ is the owner\'s eleven — twelve until the interview went', 11 === substr_count( $zandi_faq, 'accordion__item' ) );
check_true( 'and none of the shared ones — «۶ ماه» is not true of this course', false === strpos( $zandi_faq, '۶ ماه' ) );
check_true( 'A1 still gets the shared questions', false !== strpos( render_part( 'faq', zandi_get_course( 'a1' ) ), '۶ ماه' ) );

check_true( 'the trust bar says «موضوع‌محور»', false !== strpos( render_part( 'trust', $conv ), 'موضوع‌محور' ) );
check_true( 'the jumps go to the comparison, not a syllabus', false !== strpos( render_part( 'intro-video', $conv ), 'href="#compare"' ) && false === strpos( render_part( 'intro-video', $conv ), 'href="#curriculum"' ) );
check_true( 'the two deliverables are two across', false !== strpos( render_part( 'deliverables', $conv ), 'c-cards--2' ) && 2 === substr_count( render_part( 'deliverables', $conv ), '<article class="c-card' ) );
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

echo "\n— No support, no interview, on any conversation course —\n";

/*
 * The owner, 17 October 2026: «دوره‌های مکالمه مصاحبه و پشتیبانی ندارن». Both
 * came over from the main courses when the template was written, in six places.
 * This renders every part of each page except the reviews — those are the
 * students' own words about the main courses, verbatim, and shared site-wide.
 */
$zandi_promises = '/پشتیبانی ۲۴|پشتیبانی ۲۴\/۷|مصاحبه|گوگل میت|تنهات نمی‌ذارم/u';

foreach ( array( 'conversation-a1', 'conversation-a2', 'conversation-b1' ) as $zandi_slug ) {
	$zandi_c    = zandi_get_course( $zandi_slug );
	$zandi_page = '';

	foreach ( array_merge( array( 'hero', 'trust', 'intro-video' ), array_diff( zandi_course_sections( $zandi_c ), array( 'testimonials' ) ), array( 'support', 'faq', 'closing' ) ) as $zandi_part ) {
		$zandi_page .= render_part( $zandi_part, $zandi_c );
	}

	preg_match_all( $zandi_promises, $zandi_page, $zandi_found );
	check_true( "$zandi_slug: the page promises neither" . ( $zandi_found[0] ? ' — found ' . implode( ', ', array_unique( $zandi_found[0] ) ) : '' ), array() === $zandi_found[0] );
	check_true( "$zandi_slug: nor does its data", array() === array_filter( $zandi_strings_of( $zandi_c ), function ( $t ) use ( $zandi_promises ) { return (bool) preg_match( $zandi_promises, $t ); } ) );
	check_true( "$zandi_slug: no «تا آخر مسیر تنهات نمی‌ذارم» callout", '' === trim( render_part( 'support', $zandi_c ) ) );
	check_true( "$zandi_slug: but still a way in — the hero and the closing block", false !== strpos( render_part( 'hero', $zandi_c ), 'id="enrol"' ) && false !== strpos( render_part( 'closing', $zandi_c ), 'class="c-btn' ) );
}

check_true( 'the main courses keep both', false !== strpos( render_part( 'support', zandi_get_course( 'a1' ) ), 'تنهات نمی‌ذارم' ) && false !== strpos( render_part( 'hero', zandi_get_course( 'a1' ) ), 'مصاحبه' ) );

echo "\n— The thirty podcast days are for the main courses only —\n";

/*
 * The owner, 29 October 2026: «Thats only for main courses». The template once
 * carried the main courses' 30, so buying a conversation course granted a
 * month of پادکست Bonjour Monjour and every button on its page said so.
 */
foreach ( array( 'conversation-a1', 'conversation-a2', 'conversation-b1' ) as $zandi_slug ) {
	$zandi_c    = zandi_get_course( $zandi_slug );
	$zandi_page = render_part( 'hero', $zandi_c ) . render_part( 'closing', $zandi_c );

	check_true( "$zandi_slug: grants no podcast days", 0 === zandi_course_podcast_days( $zandi_slug ) );
	check_true( "$zandi_slug: and no button on its page offers them", false === strpos( $zandi_page, 'c-perk' ) && false === strpos( $zandi_page, 'Bonjour Monjour' ) );
}

foreach ( array( 'a1', 'a2', 'b1' ) as $zandi_slug ) {
	check_true( "$zandi_slug: still grants thirty", 30 === zandi_course_podcast_days( $zandi_slug ) );
}

check_true( 'and its page still says so under the button', false !== strpos( render_part( 'hero', zandi_get_course( 'a1' ) ), 'c-perk' ) );

echo "\n— مکالمه A2 and B1: public, like A1 —\n";

/*
 * Drafts from 24 September to 17 October 2026, carrying the owner's X's. She
 * asked for them on the site like A1 before every detail was in, so a detail
 * not given yet is left out — nothing is borrowed from A1 and no X is shown.
 */
foreach ( array( 'conversation-a2' => 'A2', 'conversation-b1' => 'B1' ) as $zandi_slug => $zandi_level ) {
	$zandi_c = zandi_get_course( $zandi_slug );

	check_true( "$zandi_level: in the catalogue", is_array( $zandi_c ) );
	check_true( "$zandi_level: a conversation course, on the same template", 'conversation' === $zandi_c['family'] && zandi_course_sections( $zandi_c ) === zandi_course_sections( $conv ) );
	check_true( "$zandi_level: named for its level", 'دوره مکالمه ' . $zandi_level === $zandi_c['short_name'] && $zandi_level === $zandi_c['level'] );
	check_true( "$zandi_level: not a draft", ! zandi_course_is_draft( $zandi_slug ) );
	if ( 'A2' === $zandi_level ) {
		check_true( 'A2: on sale, like A1', zandi_course_on_sale( $zandi_slug ) && 'buy' === zandi_course_enrol_state( $zandi_slug ) );
	} else {
		check_true( 'B1: still «به‌زودی»', ! empty( $zandi_c['coming_soon'] ) && false === zandi_course_on_sale( $zandi_slug ) && 'soon' === zandi_course_enrol_state( $zandi_slug ) );
	}
	check_true( "$zandi_level: says the main course need not come first", false !== strpos( $zandi_c['subtitle'], 'لازم نیست اول دوره' ) );

	$zandi_hero  = render_part( 'hero', $zandi_c );
	$zandi_about = render_part( 'about-course', $zandi_c );
	check_true( "$zandi_level: the «آموزش‌ها» card still says what the videos are", false !== strpos( render_part( 'deliverables', $zandi_c ), 'آموزش‌ها در قالب ویدیو هستن' ) );

	if ( 'A2' === $zandi_level ) {
		// The owner's text of 21 October 2026.
		check_true( 'A2: «ویدیوهای ۵ تا ۲۵ دقیقه‌ای», as she gave it', 'ویدیوهای ۵ تا ۲۵ دقیقه‌ای' === $zandi_c['hours_text'] && false !== strpos( $zandi_hero, 'ویدیوهای ۵ تا ۲۵ دقیقه‌ای' ) );
		check_true( 'A2: so the info card has its four rows', 4 === substr_count( $zandi_hero, '<span class="c-infocard__icon"' ) );
		check_true( 'A2: and the «آموزش‌ها» card names the minutes', false !== strpos( render_part( 'deliverables', $zandi_c ), 'از ۵ دقیقه تا ۲۵ دقیقه' ) );
		check_true( 'A2: six outcomes, ending on the step to B1', 6 === substr_count( $zandi_about, '<li>' ) && false !== strpos( $zandi_about, 'وارد دوره پیشرفته <span dir="ltr" class="latin-run">B1</span> بشی' ) );
		check_true( 'A2: «imparfait» sits in its own isolate', false !== strpos( $zandi_about, '<span dir="ltr" class="latin-run">imparfait</span>' ) );

		$zandi_how = render_part( 'how', $zandi_c );
		check_true( 'A2: the topics card is hers, not A1\'s examples', false !== strpos( $zandi_how, 'آزمون استخدام' ) && false === strpos( $zandi_how, 'اجاره کردن خونه' ) );
		check_true( 'A2: with its line break kept', false !== strpos( $zandi_how, '<br />' ) && false !== strpos( $zandi_how, 'تجربه زندگیم در پاریس' ) );
		check_true( 'A2: the lead goes on to name the formats', false !== strpos( $zandi_how, 'از پس خودت بربیای. مثل همه دوره‌های مکالمه من' ) && false !== strpos( $zandi_how, 'کالوکیشن' ) );
		check_true( 'A2: still two cards', 2 === substr_count( $zandi_how, 'class="c-card ' ) );
	} else {
		// Not given yet: the video lengths and the outcomes.
		check_true( "$zandi_level: no length is claimed — none is given yet", '' === $zandi_c['hours_text'] && false === strpos( $zandi_hero, 'دقیقه' ) && false === strpos( $zandi_hero, '⏱️' ) );
		check_true( "$zandi_level: and A1's «۵ تا ۲۵» is not borrowed anywhere", false === strpos( serialize( $zandi_c ), '۲۵' ) );
		check_true( "$zandi_level: the info card keeps its three true rows", 3 === substr_count( $zandi_hero, '<span class="c-infocard__icon"' ) );
		check_true( "$zandi_level: no outcomes box over an empty list", false === strpos( $zandi_about, 'c-outcomes' ) && false !== strpos( $zandi_about, 'حرف زدن مثل هر مهارت' ) );
		check_true( "$zandi_level: the family's topics card and lead", false !== strpos( render_part( 'how', $zandi_c ), 'اجاره کردن خونه' ) && false === strpos( render_part( 'how', $zandi_c ), 'کالوکیشن' ) );
	}

	$zandi_cmp = render_part( 'compare', $zandi_c );
	check_true( "$zandi_level: the two courses are drawn side by side, not as steps", false !== strpos( $zandi_cmp, 'c-path__steps--together' ) && false === strpos( $zandi_cmp, '<ol class="c-path__steps"' ) );
	check_true( "$zandi_level: joined by a «+», not an arrow that says «then»", false !== strpos( $zandi_cmp, 'c-compare__arrow--plus' ) && false === strpos( $zandi_cmp, 'c-compare__arrow--across' ) );
	check_true( "$zandi_level: the gutter says «با هم»", false !== strpos( $zandi_cmp, 'c-compare__bridge-label">با هم<' ) );
	check_true( "$zandi_level: the main course at the same level is a link", false !== strpos( $zandi_cmp, 'href="https://example.test/courses/' . strtolower( $zandi_level ) . '/"' ) );
	check_true( "$zandi_level: and this page is «همین دوره»", false !== strpos( $zandi_cmp, 'همین دوره' ) );

	$zandi_faq = render_part( 'faq', $zandi_c );
	check_true( "$zandi_level: its FAQ is the family's, level questions first", 0 === strpos( $zandi_c['faq'][0]['q'], 'فرق این دوره با دوره' ) );
	if ( 'A2' === $zandi_level ) {
		check_true( 'A2: eleven questions, «ویدیوها چقدرن؟» among them', 11 === substr_count( $zandi_faq, 'accordion__item' ) && false !== strpos( $zandi_faq, 'از ۵ دقیقه تا ۲۵ دقیقه' ) );
	} else {
		check_true( "$zandi_level: ten — no «ویدیوها چقدرن؟» yet", 10 === substr_count( $zandi_faq, 'accordion__item' ) && false === strpos( $zandi_faq, 'ویدیوها چقدرن' ) );
	}

	$GLOBALS['stub_query_vars']['zandi_course'] = $zandi_slug;
	ob_start();
	zandi_course_head();
	check_true( "$zandi_level: its head does not say noindex", false === strpos( (string) ob_get_clean(), 'noindex' ) );
}

$GLOBALS['stub_query_vars']['zandi_course'] = 'conversation-a1';
ob_start();
zandi_course_head();
check_true( 'مکالمه A1 is not a draft, and is indexable', false === strpos( (string) ob_get_clean(), 'noindex' ) );
unset( $GLOBALS['stub_query_vars']['zandi_course'] );

$zandi_listed = array_column( zandi_courses( true ), 'slug' );
check_true( '/courses/ lists all three conversation levels, in order', array( 'conversation-a1', 'conversation-a2', 'conversation-b1' ) === array_values( array_intersect( $zandi_listed, array( 'conversation-a1', 'conversation-a2', 'conversation-b1' ) ) ) );
check_true( 'after the three that can be bought', array( 'a1', 'a2', 'b1' ) === array_slice( $zandi_listed, 0, 3 ) );
check_true( 'the homepage lists only what can be bought — B1 is not there', ! in_array( 'conversation-b1', array_column( zandi_courses( false ), 'slug' ), true ) );

$zandi_other = render_part( 'other-courses', zandi_get_course( 'a1' ) );
check_true( 'the other-courses row has all three', false !== strpos( $zandi_other, 'conversation-a1' ) && false !== strpos( $zandi_other, 'conversation-a2' ) && false !== strpos( $zandi_other, 'conversation-b1' ) );
check_true( 'only B1 marked «به‌زودی», and no «۰ تومان» on the two for sale', 1 === substr_count( $zandi_other, '<span class="c-soon-badge">به‌زودی</span>' ) && ! preg_match( '/>\s*۰ تومان/u', $zandi_other ) );

$zandi_map = new ReflectionMethod( 'Zandi_Sitemap_Provider', 'zandi_urls' );
$zandi_map->setAccessible( true );
$zandi_sitemap = function () use ( $zandi_map ) {
	return $zandi_map->invoke( ( new ReflectionClass( 'Zandi_Sitemap_Provider' ) )->newInstanceWithoutConstructor() );
};
$zandi_urls_listed = $zandi_sitemap();
check_true( 'the sitemap lists all three', in_array( zandi_course_url( 'conversation-a1' ), $zandi_urls_listed, true ) && in_array( zandi_course_url( 'conversation-a2' ), $zandi_urls_listed, true ) && in_array( zandi_course_url( 'conversation-b1' ), $zandi_urls_listed, true ) );

check_true( 'مکالمه A1\'s comparison names all three conversation levels', false !== strpos( render_part( 'compare', $conv ), 'مکالمه <span dir="ltr" class="latin-run">B1</span>' ) );
check_true( 'and keeps its numbered steps and its arrow', false !== strpos( render_part( 'compare', $conv ), '<ol class="c-path__steps"' ) && false !== strpos( render_part( 'compare', $conv ), 'c-compare__arrow--across' ) );
check_true( 'and its «۵ تا ۲۵» everywhere it said it', false !== strpos( render_part( 'hero', $conv ), 'ویدیوهای ۵ تا ۲۵ دقیقه‌ای' ) && false !== strpos( render_part( 'faq', $conv ), 'ویدیوها چقدرن' ) );
check_true( 'and its own topics card and lead, untouched by A2\'s', false !== strpos( render_part( 'how', $conv ), 'اجاره کردن خونه' ) && false === strpos( render_part( 'how', $conv ), 'کالوکیشن' ) );

echo "\n— A draft is still kept out of sight, for the next page that needs it —\n";

/*
 * No course is a draft today, so one is made one through the filter — the
 * mechanism is what A2 and B1 sat behind for three weeks.
 */
$zandi_make_draft = function ( $draft, $slug ) {
	return 'conversation-b1' === $slug ? true : $draft;
};
add_filter( 'zandi_course_is_draft', $zandi_make_draft, 10, 2 );

$GLOBALS['stub_query_vars']['zandi_course'] = 'conversation-b1';
ob_start();
zandi_course_head();
check_true( 'a draft\'s head says noindex', false !== strpos( (string) ob_get_clean(), '<meta name="robots" content="noindex, follow">' ) );
add_filter( 'zandi_seo_plugin_active', '__return_true' );
ob_start();
zandi_course_head();
check_true( 'and still does with an SEO plugin installed', false !== strpos( (string) ob_get_clean(), 'noindex' ) );
remove_filter( 'zandi_seo_plugin_active', '__return_true' );
unset( $GLOBALS['stub_query_vars']['zandi_course'] );

check_true( 'it is not on /courses/', ! in_array( 'conversation-b1', array_column( zandi_courses( true ), 'slug' ), true ) );
check_true( 'nor in the other-courses row', false === strpos( render_part( 'other-courses', zandi_get_course( 'a1' ) ), 'conversation-b1' ) );
check_true( 'nor in the sitemap', ! in_array( zandi_course_url( 'conversation-b1' ), $zandi_sitemap(), true ) );

remove_filter( 'zandi_course_is_draft', $zandi_make_draft, 10 );
check_true( 'and is back the moment it is not', in_array( 'conversation-b1', array_column( zandi_courses( true ), 'slug' ), true ) );

echo "\n— No X reaches a published page —\n";

/*
 * The X's are placeholders the owner will fill. The day somebody deletes a
 * `draft` line with an X still in the entry, the page goes public showing it —
 * so this walks every string of every course that is not a draft.
 */

foreach ( zandi_courses_data() as $zandi_slug => $zandi_c ) {
	$zandi_x = array_filter(
		$zandi_strings_of( $zandi_c ),
		function ( $text ) {
			return (bool) preg_match( '/(?<![\p{L}])X(?![\p{L}])/u', $text );
		}
	);

	if ( zandi_course_is_draft( $zandi_slug ) ) {
		check_true( "$zandi_slug is a draft and still has " . count( $zandi_x ) . ' X to fill', count( $zandi_x ) > 0 );
	} else {
		check_true( "$zandi_slug is published and carries no X", 0 === count( $zandi_x ) );
	}
}

echo "\n— A product made for a conversation course cannot take a main course's page —\n";

/*
 * 29 October 2026: the main A2 page showed ۴٬۹۹۷٬۰۰۰ تومان. The مکالمه A2
 * product had been made with WooCommerce's «کپی» of the A2 product, the copy
 * carried A2's link, and the newest product linked to a course is the one the
 * page uses. These run the real map and the real notice, not the
 * zandi_course_product_id filter the checks above link products through.
 */
if ( ! function_exists( 'get_current_screen' ) ) {
	function get_current_screen() { return (object) array( 'id' => 'edit-product' ); }
}
if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $id ) { return isset( $GLOBALS['zandi_titles'][ $id ] ) ? $GLOBALS['zandi_titles'][ $id ] : ''; }
}
if ( ! function_exists( 'get_edit_post_link' ) ) {
	function get_edit_post_link( $id ) { return 'https://example.test/wp-admin/post.php?post=' . $id . '&action=edit'; }
}

$zandi_notices = function () {
	ob_start();
	zandi_woo_link_clash_notice();
	return (string) ob_get_clean();
};

check_true( 'a «کپی» of a product leaves its course link behind', in_array( '_zandi_course', apply_filters( 'woocommerce_duplicate_product_exclude_meta', array(), array( '_zandi_course', '_price' ) ), true ) );

$GLOBALS['zandi_titles']     = array( 500 => 'دوره متوسط A2', 501 => 'مکالمه A2', 400 => 'دوره پایه A1' );
$GLOBALS['stub_product_ids'] = array( 501, 500, 400 );
update_post_meta( 400, '_zandi_course', 'a1' );
update_post_meta( 500, '_zandi_course', 'a2' );
update_post_meta( 501, '_zandi_course', 'a2' );

check_true( 'both A2 products are seen as claiming A2', array( 'a2' => array( 501, 500 ), 'a1' => array( 400 ) ) === zandi_course_product_claims() );
check_true( 'and the newest one is the one the page would use', 501 === zandi_build_course_product_map()['a2'] );

$zandi_out = $zandi_notices();
check_true( 'wp-admin says so, as an error', 1 === substr_count( $zandi_out, 'notice-error' ) && false !== strpos( $zandi_out, 'دوره متوسط A2' ) );
check_true( 'naming both products, each a link to fix it', false !== strpos( $zandi_out, 'post=500&action=edit' ) && false !== strpos( $zandi_out, 'post=501&action=edit' ) );
check_true( 'and nothing about A1, which has one product', false === strpos( $zandi_out, 'post=400' ) );

update_post_meta( 501, '_zandi_course', 'conversation-a2' );
check_true( 'with the copy pointed at مکالمه A2, A2 has its own product back', 500 === zandi_build_course_product_map()['a2'] && 501 === zandi_build_course_product_map()['conversation-a2'] );
check_true( 'and the warning goes', '' === $zandi_notices() );

$GLOBALS['stub_caps'] = false;
update_post_meta( 501, '_zandi_course', 'a2' );
check_true( 'nobody without the shop\'s capability is shown it', '' === $zandi_notices() );
$GLOBALS['stub_caps']        = true;
$GLOBALS['stub_product_ids'] = array();

echo "\n" . ( $fail ? "$pass passed, $fail failed\n" : "$pass passed, 0 failed\n" );
exit( $fail ? 1 : 0 );

function wp_strip_all_tags_for_test( $html ) {
	return strip_tags( $html );
}
