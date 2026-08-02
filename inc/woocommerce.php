<?php
/**
 * WooCommerce integration.
 *
 * WHAT THIS FILE IS FOR
 *
 * The academy sells three digital courses. WooCommerce is here to take money
 * and to remember who paid — nothing else. Everything a visitor reads about a
 * course still comes from inc/courses.php, and every course page is still
 * template-course.php. WooCommerce owns price and entitlement; the theme owns
 * design and copy. That split is the whole design of this file.
 *
 * WHY COURSES ARE NOT PRODUCTS
 *
 * A course is a rewrite rule and a PHP array, not a post — see
 * zandi_courses_data() and zandi_course_template(). Turning them into products
 * would mean rebuilding fourteen section partials against post meta and moving
 * copy out of the filtered getters this theme is built on. Instead each course
 * is *linked* to a product, and the link is what carries money.
 *
 * HOW THE LINK IS STORED, AND WHY IT IS NOT THE SKU
 *
 * The link lives in `_zandi_course` post meta, set from a dropdown on the
 * product editor's «عمومی» tab.
 *
 * It was the SKU first — course 'a1' to SKU 'zandi-a1' — and that was wrong in
 * a way worth recording. The SKU is a free-text field any admin can edit or
 * clear in one click, and clearing it did not fail loudly: prices reverted to
 * the hard-coded figures, the enrol button reverted to «pending», and
 * zandi_student_owns_course() started answering false — so a student who had
 * already paid lost the course from their panel. A field that easy to change
 * must not be able to revoke a purchase.
 *
 * The SKU is still read **once**, as a migration: a product carrying the old
 * `zandi-{slug}` SKU and no meta gets the meta written on first resolve. So an
 * install set up the old way keeps working and heals itself.
 *
 * Nothing breaks when a course has no product at all: prices fall back to the
 * hard-coded `price_toman` and the enrol button falls back to its old
 * behaviour. An admin notice says so rather than leaving it silent.
 *
 * WHAT IS DELIBERATELY SWITCHED OFF
 *
 * Reviews, shipping, coupons, weights, related products, sorting, result
 * counts, the sidebar and the product gallery zoom. A course is not a physical
 * good and every one of those controls invites the site to look like a shop.
 *
 * Everything here is behind `class_exists( 'WooCommerce' )` — the theme must
 * still render if the plugin is deactivated.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether WooCommerce is active and usable.
 *
 * Called on nearly every hook below, so it is worth being cheap and explicit
 * rather than relying on function_exists() of whichever helper is nearest.
 *
 * @return bool
 */
function zandi_woo_active() {
	return class_exists( 'WooCommerce' );
}

if ( ! zandi_woo_active() ) {
	return;
}

/* =========================================================================
 * 1. Theme support
 *
 * Declaring support is what stops WooCommerce loading its own page template
 * and lets woocommerce.php in the theme root take over. The gallery features
 * are left OFF on purpose: they pull in PhotoSwipe and flexslider, which is
 * ~40 KB of JavaScript to zoom a course thumbnail nobody needs to zoom.
 * ====================================================================== */

/**
 * Declares WooCommerce support and the shop's markup shape.
 *
 * @return void
 */
function zandi_woo_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 640,
			'single_image_width'    => 960,

			// The archive is a course grid, laid out by CSS grid, not by Woo.
			'product_grid'          => array(
				'default_columns' => 3,
				'min_columns'     => 1,
				'max_columns'     => 3,
			),
		)
	);
}
add_action( 'after_setup_theme', 'zandi_woo_setup' );

/* =========================================================================
 * 2. The course <-> product link
 * ====================================================================== */

/**
 * The meta key holding a product's course slug.
 *
 * @return string
 */
function zandi_course_meta_key() {
	/**
	 * Filters the post-meta key that links a product to a course.
	 *
	 * Changing this after products exist orphans the existing links; migrate
	 * the meta first.
	 *
	 * @param string $key Meta key. Default '_zandi_course'.
	 */
	return (string) apply_filters( 'zandi_course_meta_key', '_zandi_course' );
}

/**
 * The legacy SKU for a course, read once to migrate an older install.
 *
 * @param string $slug Course slug, e.g. 'a1'.
 * @return string
 */
function zandi_course_sku( $slug ) {
	/**
	 * Filters the legacy SKU a course is migrated from.
	 *
	 * Only consulted when a course has no product carrying the meta key. New
	 * links are stored as meta and never read back through here.
	 *
	 * @param string $sku  Default 'zandi-{slug}'.
	 * @param string $slug Course slug.
	 */
	return (string) apply_filters( 'zandi_course_sku', 'zandi-' . $slug, $slug );
}

/**
 * Every course slug that has a product, as slug => product ID.
 *
 * One query for the whole map rather than one per course, cached in an option
 * so an ordinary page load costs nothing. The cache is dropped whenever a
 * product is saved, trashed or deleted, so it cannot go stale behind an edit.
 *
 * @return array<string,int>
 */
function zandi_course_product_map() {
	static $map = null;

	if ( null !== $map ) {
		return $map;
	}

	if ( ! zandi_woo_active() ) {
		$map = array();

		return $map;
	}

	$cached = get_option( 'zandi_course_product_map', false );

	if ( is_array( $cached ) ) {
		$map = $cached;

		return $map;
	}

	$map = zandi_build_course_product_map();

	update_option( 'zandi_course_product_map', $map, false );

	return $map;
}

/**
 * Resolves the course/product map from the database.
 *
 * Reads the meta first. Any course still unlinked is then looked up by its
 * legacy SKU, and when that finds a product the meta is written — so the
 * migration happens once, on the first page load after this file ships, and
 * never again.
 *
 * @return array<string,int>
 */
function zandi_build_course_product_map() {
	$map  = array();
	$key  = zandi_course_meta_key();
	$rows = get_posts(
		array(
			'post_type'        => 'product',
			'post_status'      => array( 'publish', 'private', 'draft' ),
			'numberposts'      => 100,
			'meta_key'         => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Indexed lookup over a handful of products, cached in an option.
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	foreach ( $rows as $product_id ) {
		$slug = (string) get_post_meta( $product_id, $key, true );

		// First one wins, so a duplicated link cannot flip between page loads.
		if ( '' !== $slug && ! isset( $map[ $slug ] ) ) {
			$map[ $slug ] = (int) $product_id;
		}
	}

	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return $map;
	}

	foreach ( array_keys( zandi_courses_raw() ) as $slug ) {
		if ( isset( $map[ $slug ] ) ) {
			continue;
		}

		$product_id = (int) wc_get_product_id_by_sku( zandi_course_sku( $slug ) );

		if ( $product_id ) {
			update_post_meta( $product_id, $key, $slug );
			$map[ $slug ] = $product_id;
		}
	}

	return $map;
}

/**
 * Drops the cached map.
 *
 * @return void
 */
function zandi_flush_course_product_map() {
	delete_option( 'zandi_course_product_map' );
}
add_action( 'save_post_product', 'zandi_flush_course_product_map' );
add_action( 'deleted_post', 'zandi_flush_course_product_map' );
add_action( 'trashed_post', 'zandi_flush_course_product_map' );
add_action( 'untrashed_post', 'zandi_flush_course_product_map' );

/**
 * The product ID for a course, or 0 when none is linked yet.
 *
 * @param string $slug Course slug.
 * @return int Product ID, or 0.
 */
function zandi_course_product_id( $slug ) {
	/**
	 * Short-circuits the course/product lookup.
	 *
	 * Return a product ID to link a course some other way — a stored option, a
	 * different meta key — without touching this file.
	 *
	 * @param int    $product_id Zero to continue with the normal lookup.
	 * @param string $slug       Course slug.
	 */
	$filtered = (int) apply_filters( 'zandi_course_product_id', 0, $slug );

	if ( $filtered > 0 ) {
		return $filtered;
	}

	$map = zandi_course_product_map();

	return isset( $map[ $slug ] ) ? (int) $map[ $slug ] : 0;
}

/**
 * The course slug behind a product, or '' when the product is not a course.
 *
 * One meta read. This used to compare the product's SKU against every course in
 * turn, which meant loading zandi_courses_data() — and that fires the price
 * filter, which resolves all three products. It is hooked to
 * `woocommerce_is_purchasable`, so that ran per product per loop.
 *
 * @param int|WC_Product $product Product or product ID.
 * @return string Course slug, or ''.
 */
function zandi_product_course_slug( $product ) {
	/*
	 * PHP declares top-level functions at compile time, so the `return` at the
	 * top of this file stops the hooks registering but does NOT stop these
	 * functions existing. Anything that reaches a WooCommerce API directly has
	 * to say so itself, or a snippet calling this with the plugin off is a
	 * fatal rather than an empty answer.
	 */
	if ( ! zandi_woo_active() ) {
		return '';
	}

	$product_id = is_numeric( $product ) ? (int) $product : 0;

	if ( ! $product_id && $product instanceof WC_Product ) {
		$product_id = (int) $product->get_id();
	}

	if ( ! $product_id ) {
		return '';
	}

	$slug = (string) get_post_meta( $product_id, zandi_course_meta_key(), true );

	return isset( zandi_courses_raw()[ $slug ] ) ? $slug : '';
}

/**
 * Course data with the price filter bypassed.
 *
 * zandi_woo_live_prices() is hooked to `zandi_courses_data`, and it needs the
 * course list itself. Calling the filtered getter from inside the lookup path
 * is what made the old reverse lookup expensive; this returns the same keys
 * without the round trip.
 *
 * @return array<string,array<string,mixed>>
 */
function zandi_courses_raw() {
	static $raw = null;

	if ( null === $raw ) {
		remove_filter( 'zandi_courses_data', 'zandi_woo_live_prices', 20 );
		$raw = zandi_courses_data();
		add_filter( 'zandi_courses_data', 'zandi_woo_live_prices', 20 );
	}

	return $raw;
}

/**
 * The live price of a course, in Toman, taking WooCommerce as the truth.
 *
 * IMPORTANT — units. `price_toman` in inc/courses.php is Toman, and this
 * returns Toman, because the store is configured in Toman via the Persian
 * WooCommerce plugin. Gateways that settle in Rial multiply by ten themselves;
 * do NOT add a conversion here or the site bills at ten times or a tenth. See
 * docs/wordpress-iran-stack.md.
 *
 * Falls back to the hard-coded figure whenever no product is linked, so a
 * course page never renders a blank or a zero price.
 *
 * @param array<string,mixed> $course Course array from zandi_courses_data().
 * @return int Price in Toman.
 */
function zandi_course_price( $course ) {
	$fallback = isset( $course['price_toman'] ) ? (int) $course['price_toman'] : 0;

	if ( empty( $course['slug'] ) ) {
		return $fallback;
	}

	$product_id = zandi_course_product_id( $course['slug'] );

	if ( ! $product_id ) {
		return $fallback;
	}

	$product = wc_get_product( $product_id );

	if ( ! $product instanceof WC_Product ) {
		return $fallback;
	}

	$price = $product->get_price();

	return '' === $price ? $fallback : (int) $price;
}

/**
 * Makes WooCommerce the single source of price across the whole site.
 *
 * Four templates render a price — course/hero.php, course/closing.php,
 * course/other-courses.php and the card getter in inc/content.php — and every
 * one of them reads `$course['price_toman']`. Rewriting that one key at the
 * source updates all four with no template change at all, and means the owner
 * changes a price in محصولات rather than in a PHP file.
 *
 * Prices that have no linked product keep their hard-coded value, so a course
 * page never renders «۰ تومان» while a product is still being set up.
 *
 * @param array<string,array<string,mixed>> $courses Course data.
 * @return array<string,array<string,mixed>>
 */
function zandi_woo_live_prices( $courses ) {
	/*
	 * zandi_course_price() reads zandi_courses_data() nowhere, so there is no
	 * recursion — but this filter runs on every course lookup on the page, and
	 * the guard keeps a future change from turning that into one.
	 */
	static $running = false;

	if ( $running ) {
		return $courses;
	}

	$running = true;

	foreach ( $courses as $slug => $course ) {
		$product_id = zandi_course_product_id( $slug );

		if ( ! $product_id ) {
			continue;
		}

		$courses[ $slug ]['price_toman'] = zandi_course_price( $course );
	}

	$running = false;

	return $courses;
}
add_filter( 'zandi_courses_data', 'zandi_woo_live_prices', 20 );

/* -------------------------------------------------------------------------
 * The admin side of the link
 * ---------------------------------------------------------------------- */

/**
 * The course dropdown on the product editor's «عمومی» tab.
 *
 * Placed with the price fields rather than in a meta box of its own, because
 * this is part of deciding what the product *is* — and an admin setting a price
 * is the same admin who needs to say which course it belongs to.
 *
 * @return void
 */
function zandi_woo_course_field() {
	$options = array( '' => '— هیچ‌کدام (محصول معمولی) —' );

	foreach ( zandi_courses_raw() as $slug => $course ) {
		$label             = isset( $course['short_name'] ) ? $course['short_name'] : $slug;
		$options[ $slug ] = $label . ' (' . $slug . ')';
	}

	woocommerce_wp_select(
		array(
			'id'          => zandi_course_meta_key(),
			'label'       => 'دوره‌ی مرتبط',
			'description' => 'این محصول کدوم دوره‌ی سایته؟ قیمت، دسترسی دانشجو و دکمه‌ی ثبت‌نام همه از همین وصل می‌شن.',
			'desc_tip'    => true,
			'options'     => $options,
		)
	);
}
add_action( 'woocommerce_product_options_general_product_data', 'zandi_woo_course_field' );

/**
 * Saves the course dropdown.
 *
 * @param int $product_id Product being saved.
 * @return void
 */
function zandi_woo_save_course_field( $product_id ) {
	$key = zandi_course_meta_key();

	/*
	 * Nonce verification is WooCommerce's job here — this fires inside
	 * WC_Meta_Box_Product_Data::save(), which has already checked it.
	 */
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$slug = isset( $_POST[ $key ] ) ? sanitize_key( wp_unslash( $_POST[ $key ] ) ) : '';

	if ( '' === $slug || ! isset( zandi_courses_raw()[ $slug ] ) ) {
		delete_post_meta( $product_id, $key );
	} else {
		update_post_meta( $product_id, $key, $slug );
	}

	zandi_flush_course_product_map();
}
add_action( 'woocommerce_process_product_meta', 'zandi_woo_save_course_field' );

/**
 * Warns in wp-admin when a course has no product behind it.
 *
 * The whole reason the link moved off the SKU: an unlinked course fails
 * silently on the front end — the old price shows, the enrol button goes back
 * to «pending» and a student who paid stops seeing the course in their panel.
 * None of that is visible from the admin, so it is said out loud here.
 *
 * @return void
 */
function zandi_woo_unlinked_notice() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$screen = get_current_screen();

	// Only where it is actionable: the products list and the WooCommerce pages.
	if ( ! $screen || ! in_array( $screen->id, array( 'edit-product', 'product', 'woocommerce_page_wc-settings', 'dashboard' ), true ) ) {
		return;
	}

	$map     = zandi_course_product_map();
	$missing = array();

	foreach ( zandi_courses_raw() as $slug => $course ) {
		if ( empty( $map[ $slug ] ) ) {
			$missing[] = isset( $course['short_name'] ) ? $course['short_name'] : $slug;
		}
	}

	if ( ! $missing ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>%s</strong> %s</p><p>%s</p></div>',
		esc_html( 'آکادمی زندی:' ),
		esc_html( sprintf( 'این دوره‌ها هنوز به هیچ محصولی وصل نشدن و قابل خرید نیستن: %s.', implode( '، ', $missing ) ) ),
		esc_html( 'برای وصل کردن: محصولات ← ویرایش محصول ← بخش «اطلاعات محصول» ← تب «عمومی» ← «دوره‌ی مرتبط».' )
	);
}
add_action( 'admin_notices', 'zandi_woo_unlinked_notice' );

/* -------------------------------------------------------------------------
 * Cart and checkout on the classic templates
 *
 * WooCommerce now creates both pages holding a *block* — `wp:woocommerce/
 * checkout` — instead of the `[woocommerce_checkout]` shortcode. The block
 * renders its own React form from the Store API and honours none of the
 * server-side hooks a theme has always used: `woocommerce_checkout_fields`
 * does nothing to it, so the address fields cannot be removed, the account
 * details cannot be prefilled, and assets/css/shop.css styles markup that is
 * never emitted. It is why checkout arrived asking a student for a postcode
 * before selling them a video.
 *
 * WooCommerce's own answer is a "switch to classic" control buried in the
 * block editor's sidebar. Finding it is not something an owner should have to
 * do, so the swap happens here instead: once, guarded, and only when the page
 * really is just the block.
 *
 * This edits two pages the owner did not write. That is deliberate and worth
 * being uncomfortable about, so: it runs a single time, it refuses to touch a
 * page that has anything else in it, and WordPress keeps the previous content
 * as a revision, so it is undoable from برگه‌ها ← ویرایش ← نسخه‌های پیشین.
 * ---------------------------------------------------------------------- */

/**
 * The pages to convert, as WooCommerce page key => shortcode.
 *
 * @return array<string,string>
 */
function zandi_woo_classic_pages() {
	return array(
		'checkout' => '[woocommerce_checkout]',
		'cart'     => '[woocommerce_cart]',
	);
}

/**
 * Replaces the cart and checkout blocks with the classic shortcodes.
 *
 * @return void
 */
function zandi_woo_force_classic_pages() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	// Runs once. Delete the option to run it again after a WooCommerce reset.
	if ( 'done' === get_option( 'zandi_woo_classic_pages', '' ) ) {
		return;
	}

	$converted = array();

	foreach ( zandi_woo_classic_pages() as $key => $shortcode ) {
		$page_id = (int) wc_get_page_id( $key );

		if ( $page_id <= 0 ) {
			continue;
		}

		$page = get_post( $page_id );

		if ( ! $page ) {
			continue;
		}

		$content = (string) $page->post_content;

		// Already classic, or someone has already fixed it by hand.
		if ( false !== strpos( $content, $shortcode ) ) {
			continue;
		}

		if ( false === strpos( $content, 'wp:woocommerce/' . $key ) ) {
			continue;
		}

		/*
		 * Only convert a page that is nothing but the block. Anything else in
		 * there is the owner's, and silently replacing it would be the kind of
		 * damage that is noticed a week later.
		 */
		$stripped = trim( preg_replace( '#<!--\s*/?wp:[^>]*-->#', '', $content ) );

		if ( '' !== $stripped ) {
			continue;
		}

		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => $shortcode,
			)
		);

		$converted[] = $page->post_title;
	}

	update_option( 'zandi_woo_classic_pages', 'done', false );

	if ( $converted ) {
		update_option( 'zandi_woo_classic_pages_converted', $converted, false );
	}
}
add_action( 'admin_init', 'zandi_woo_force_classic_pages' );

/**
 * Says what was changed, once, so the edit is never silent.
 *
 * @return void
 */
function zandi_woo_classic_pages_notice() {
	$converted = get_option( 'zandi_woo_classic_pages_converted', array() );

	if ( ! $converted || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	delete_option( 'zandi_woo_classic_pages_converted' );

	printf(
		'<div class="notice notice-success is-dismissible"><p><strong>%s</strong> %s</p><p>%s</p></div>',
		esc_html( 'آکادمی زندی:' ),
		esc_html( sprintf( 'برگه‌های %s به حالت کلاسیک برگردونده شدن تا با طراحی سایت هماهنگ بشن و فیلدهای اضافی حذف بشن.', implode( ' و ', $converted ) ) ),
		esc_html( 'اگر می‌خوای برگردی: برگه‌ها ← ویرایش برگه ← نسخه‌های پیشین.' )
	);
}
add_action( 'admin_notices', 'zandi_woo_classic_pages_notice' );

/**
 * Whether a course can actually be bought right now.
 *
 * @param string $slug Course slug.
 * @return bool
 */
function zandi_course_purchasable( $slug ) {
	$product_id = zandi_course_product_id( $slug );

	if ( ! $product_id ) {
		return false;
	}

	$product = wc_get_product( $product_id );

	return $product instanceof WC_Product && $product->is_purchasable() && $product->is_in_stock();
}

/**
 * What the enrol control on a course page should offer.
 *
 * The button used to render unconditionally and post to a handler that, with no
 * product linked, redirected straight back to the same page. Nothing visible
 * happened, which reads as a broken site rather than as a course that is not on
 * sale yet — and it is the first thing anyone clicks.
 *
 * @param string $slug Course slug.
 * @return string 'buy', 'owned' or 'unavailable'.
 */
function zandi_course_enrol_state( $slug ) {
	if ( is_user_logged_in() && zandi_student_owns_course( get_current_user_id(), $slug ) ) {
		return 'owned';
	}

	return zandi_course_purchasable( $slug ) ? 'buy' : 'unavailable';
}

/**
 * The message shown after a bounced enrol attempt, if there is one.
 *
 * Read from the query string the enrol handler redirects with. Says what
 * happened instead of leaving a reloaded page to explain itself.
 *
 * @return string
 */
function zandi_enrol_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag.
	$state = isset( $_GET['enrol'] ) ? sanitize_key( wp_unslash( $_GET['enrol'] ) ) : '';

	$messages = array(
		'pending' => 'ثبت‌نام آنلاین این دوره هنوز فعال نشده. برای ثبت‌نام توی تلگرام پیام بده.',
		'failed'  => 'یه مشکلی توی اضافه کردن دوره پیش اومد. یک بار دیگه امتحان کن، اگر باز هم نشد توی تلگرام بگو.',
		'expired' => 'صفحه منقضی شده بود. یک بار صفحه رو تازه کن و دوباره روی ثبت‌نام بزن.',
		'nocart'  => 'سبد خرید باز نشد. یک بار دیگه امتحان کن، اگر باز هم نشد توی تلگرام بگو.',
	);

	return isset( $messages[ $state ] ) ? $messages[ $state ] : '';
}

/* =========================================================================
 * 3. Enrolling
 *
 * The enrol button already exists on every course page and already posts the
 * course slug to admin-post.php — see template-parts/course/hero.php, which is
 * NOT modified. The stub handler in functions.php is unhooked and replaced, so
 * the same button now fills a cart and goes to checkout. No markup changes,
 * which is the point: the UI does not know commerce arrived.
 * ====================================================================== */

/**
 * Swaps the stub enrol handler for the real one.
 *
 * Done by unhooking rather than by editing zandi_handle_enrol(), so
 * deactivating WooCommerce restores the original behaviour untouched.
 *
 * @return void
 */
function zandi_woo_take_over_enrol() {
	remove_action( 'admin_post_nopriv_zandi_enrol', 'zandi_handle_enrol' );
	remove_action( 'admin_post_zandi_enrol', 'zandi_handle_enrol' );

	add_action( 'admin_post_nopriv_zandi_enrol', 'zandi_woo_handle_enrol' );
	add_action( 'admin_post_zandi_enrol', 'zandi_woo_handle_enrol' );
}
add_action( 'init', 'zandi_woo_take_over_enrol', 20 );

/**
 * Puts a course in the cart and sends the student to checkout.
 *
 * A course is bought once and owned forever, so the cart is emptied first:
 * two of the same course in a basket is a support ticket, not a sale.
 *
 * @return void
 */
function zandi_woo_handle_enrol() {
	$nonce = isset( $_POST['zandi_enrol_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_enrol_nonce'] ) ) : '';
	$slug  = isset( $_POST['course'] ) ? sanitize_key( wp_unslash( $_POST['course'] ) ) : '';
	$back  = zandi_get_course( $slug ) ? zandi_course_url( $slug ) : home_url( '/' );

	if ( ! wp_verify_nonce( $nonce, 'zandi_enrol' ) ) {
		/*
		 * Usually a page cache serving a nonce minted for someone else, or one
		 * older than its 24-hour life. It used to redirect with no query string
		 * at all, which on screen was indistinguishable from the button doing
		 * nothing. Every exit below now names itself for the same reason.
		 */
		wp_safe_redirect( add_query_arg( 'enrol', 'expired', $back ) . '#enrol' );
		exit;
	}

	$product_id = zandi_course_product_id( $slug );

	// No product linked yet: behave exactly as the site did before WooCommerce.
	if ( ! $product_id || ! zandi_course_purchasable( $slug ) ) {
		/** This action is documented in functions.php */
		do_action( 'zandi_enrol_requested', $slug );

		wp_safe_redirect( add_query_arg( 'enrol', 'pending', $back ) . '#enrol' );
		exit;
	}

	// Already owned — send them to the course, not to a second payment.
	if ( is_user_logged_in() && zandi_student_owns_course( get_current_user_id(), $slug ) ) {
		wp_safe_redirect( zandi_panel_url() . '#my-courses' );
		exit;
	}

	/*
	 * THE CART IS NOT LOADED HERE BY DEFAULT.
	 *
	 * This handler runs on admin-post.php, which lives in wp-admin, so
	 * is_admin() is true — and WooCommerce initialises the session and the cart
	 * only for what it considers a frontend request:
	 *
	 *     if ( $this->is_request( 'frontend' ) ) {
	 *         $this->initialize_session();
	 *         $this->initialize_cart();
	 *     }
	 *
	 * So WC()->cart was null on every enrol, the guard below bailed out, and the
	 * redirect carried no query string — the button posted and the same page came
	 * back. It read as a broken button and was the reason nothing could be
	 * bought. wc_load_cart() is WooCommerce's own opt-in for exactly this case.
	 */
	if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
		wc_load_cart();
	}

	if ( ! WC()->cart ) {
		wp_safe_redirect( add_query_arg( 'enrol', 'nocart', $back ) . '#enrol' );
		exit;
	}

	WC()->cart->empty_cart();
	$added = WC()->cart->add_to_cart( $product_id );

	if ( ! $added ) {
		wp_safe_redirect( add_query_arg( 'enrol', 'failed', $back ) . '#enrol' );
		exit;
	}

	/**
	 * Fires once a course is in the cart and checkout is next.
	 *
	 * @param string $slug       Course slug.
	 * @param int    $product_id Linked product ID.
	 */
	do_action( 'zandi_enrol_started', $slug, $product_id );

	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}

/* =========================================================================
 * 4. What a student owns
 *
 * zandi_student_courses() in inc/panel.php is a filtered stub returning an
 * empty array, with a TODO pointing here. The panel already knows how to draw
 * a course row and a licence block, so filling the filter is the whole job.
 * ====================================================================== */

/**
 * Order statuses that count as "paid, they own it".
 *
 * `processing` is included because a digital course is delivered immediately;
 * waiting for `completed` would leave a paying student staring at an empty
 * panel until someone clicks a button in wp-admin.
 *
 * @return array<int,string>
 */
function zandi_woo_paid_statuses() {
	/**
	 * Filters the order statuses that count as paid.
	 *
	 * A course added here becomes visible in the student's panel, so only add a
	 * status that genuinely means the money arrived.
	 *
	 * @param array<int,string> $statuses Default array( 'processing', 'completed' ).
	 */
	return (array) apply_filters( 'zandi_woo_paid_statuses', array( 'processing', 'completed' ) );
}

/**
 * What a user has paid for: product IDs, and any licence keys against them.
 *
 * One order query answers both questions, cached per request. They used to be
 * two functions issuing the identical wc_get_orders() call, and only one of
 * them cached it — so every panel load ran the query twice.
 *
 * @param int $user_id User ID.
 * @return array{products:array<int,int>,licences:array<int,string>}
 */
function zandi_student_purchases( $user_id ) {
	$user_id = (int) $user_id;

	$empty = array(
		'products' => array(),
		'licences' => array(),
	);

	// See the note in zandi_product_course_slug() on why this guard is here.
	if ( ! $user_id || ! zandi_woo_active() ) {
		return $empty;
	}

	static $cache = array();

	if ( isset( $cache[ $user_id ] ) ) {
		return $cache[ $user_id ];
	}

	$orders = wc_get_orders(
		array(
			'customer_id' => $user_id,
			'status'      => zandi_woo_paid_statuses(),
			'limit'       => 50,
			'return'      => 'objects',
		)
	);

	$products    = array();
	$licences    = array();
	$licence_key = zandi_licence_meta_key();

	foreach ( $orders as $order ) {
		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();

			if ( ! $product_id ) {
				continue;
			}

			$products[ $product_id ] = $product_id;

			$licence = $item->get_meta( $licence_key );

			if ( $licence ) {
				$licences[ $product_id ] = (string) $licence;
			}
		}
	}

	$cache[ $user_id ] = array(
		'products' => array_values( $products ),
		'licences' => $licences,
	);

	return $cache[ $user_id ];
}

/**
 * Every product ID a user has paid for.
 *
 * @param int $user_id User ID.
 * @return array<int,int> Product IDs.
 */
function zandi_student_product_ids( $user_id ) {
	$purchases = zandi_student_purchases( $user_id );

	return $purchases['products'];
}

/**
 * Whether this user has paid for this course.
 *
 * @param int    $user_id User ID.
 * @param string $slug    Course slug.
 * @return bool
 */
function zandi_student_owns_course( $user_id, $slug ) {
	$product_id = zandi_course_product_id( $slug );

	return $product_id && in_array( $product_id, zandi_student_product_ids( $user_id ), true );
}

/**
 * The meta key a licence is stored under on the order item.
 *
 * Nothing writes this yet. When the SpotPlayer integration lands it writes here
 * and the panel starts showing keys with no further change — see the
 * `zandi_student_registered` precedent in inc/auth.php for the same pattern.
 *
 * @return string
 */
function zandi_licence_meta_key() {
	/**
	 * Filters the order-item meta key a SpotPlayer licence is stored under.
	 *
	 * Point this at whatever the SpotPlayer plugin writes and the panel starts
	 * showing its keys with no other change.
	 *
	 * @param string $key Meta key. Default '_zandi_spotplayer_licence'.
	 */
	return (string) apply_filters( 'zandi_licence_meta_key', '_zandi_spotplayer_licence' );
}

/**
 * Fills «دوره‌های من» from paid orders.
 *
 * Returns the exact shape zandi_student_courses() documents: title, level,
 * url, licence, player.
 *
 * @param array<int,array<string,string>> $courses Existing value.
 * @param int                             $user_id User ID.
 * @return array<int,array<string,string>>
 */
function zandi_woo_student_courses( $courses, $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return $courses;
	}

	$purchases = zandi_student_purchases( $user_id );
	$owned     = $purchases['products'];

	if ( ! $owned ) {
		return $courses;
	}

	$licences = $purchases['licences'];

	foreach ( zandi_courses_raw() as $slug => $course ) {
		$product_id = zandi_course_product_id( $slug );

		if ( ! $product_id || ! in_array( $product_id, $owned, true ) ) {
			continue;
		}

		$courses[] = array(
			// The panel template ignores `slug`; the SpotPlayer bridge in
			// section 10 needs it to find the product a licence belongs to.
			'slug'    => $slug,
			'title'   => isset( $course['short_name'] ) ? $course['short_name'] : $course['title'],
			'level'   => isset( $course['level'] ) ? $course['level'] : '',
			'url'     => zandi_course_url( $slug ),
			'licence' => isset( $licences[ $product_id ] ) ? $licences[ $product_id ] : '',
			/**
			 * Filters the player/download URL shown beside a course in the panel.
			 *
			 * Empty by default — the panel hides the link rather than showing a
			 * dead one. The SpotPlayer integration fills this in.
			 *
			 * @param string $url     Default ''.
			 * @param string $slug    Course slug.
			 * @param int    $user_id Student's user ID.
			 */
			'player'  => (string) apply_filters( 'zandi_spotplayer_url', '', $slug, $user_id ),
		);
	}

	return $courses;
}
add_filter( 'zandi_student_courses', 'zandi_woo_student_courses', 10, 2 );

/* =========================================================================
 * 5. One account, not two
 *
 * The site already has accounts: inc/auth.php puts them at /login/, /register/
 * and /panel/, keyed on the mobile number, with Digits owning the form.
 * WooCommerce ships a second set at /my-account/. Two login pages on one site
 * is how a student ends up with two accounts and one of them holding the
 * order — so WooCommerce's are redirected onto the theme's throughout.
 * ====================================================================== */

/**
 * Sends WooCommerce's account URLs at the theme's own pages.
 *
 * `/my-account/` itself is not deleted — WooCommerce needs the page to exist
 * for endpoints like order-received to resolve — it is redirected.
 *
 * @return void
 */
function zandi_woo_redirect_account() {
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_account_page' ) ) {
		return;
	}

	if ( ! is_account_page() ) {
		return;
	}

	/*
	 * Endpoints must keep working. order-received is the payment return URL —
	 * redirecting it would strand a student who has just paid — and
	 * lost-password is the one account screen the theme has no replacement for.
	 */
	if ( zandi_woo_is_kept_endpoint() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		wp_safe_redirect( zandi_panel_url() );
		exit;
	}

	wp_safe_redirect( zandi_login_url( zandi_panel_url() ) );
	exit;
}
add_action( 'template_redirect', 'zandi_woo_redirect_account', 5 );

/**
 * Whether the current account view is one the theme leaves alone.
 *
 * @return bool
 */
function zandi_woo_is_kept_endpoint() {
	/**
	 * Filters the WooCommerce account endpoints the theme does not redirect.
	 *
	 * order-received is the gateway's return URL and order-pay is its retry URL
	 * — redirecting either strands a student mid-payment. lost-password is the
	 * one account screen the theme has no replacement for.
	 *
	 * @param array<int,string> $endpoints Endpoint slugs left alone.
	 */
	$kept = (array) apply_filters(
		'zandi_woo_kept_endpoints',
		array( 'order-received', 'lost-password', 'view-order', 'order-pay' )
	);

	foreach ( $kept as $endpoint ) {
		if ( is_wc_endpoint_url( $endpoint ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Points every «حساب کاربری» link WooCommerce prints at the panel.
 *
 * @param string $url  Page URL.
 * @param int    $page Page ID.
 * @return string
 */
function zandi_woo_account_permalink( $url, $page ) {
	$account_id = (int) wc_get_page_id( 'myaccount' );

	if ( $account_id && (int) $page === $account_id ) {
		return zandi_panel_url();
	}

	return $url;
}
add_filter( 'woocommerce_get_page_permalink', 'zandi_woo_account_permalink', 10, 2 );

/**
 * After checkout, «حساب کاربری من» should mean the panel.
 *
 * @param string $url Default URL.
 * @return string
 */
function zandi_woo_login_redirect( $url ) {
	return zandi_panel_url();
}
add_filter( 'woocommerce_login_redirect', 'zandi_woo_login_redirect', 10, 1 );
add_filter( 'woocommerce_registration_redirect', 'zandi_woo_login_redirect', 10, 1 );

/**
 * Keeps checkout's «قبلاً حساب داشتی؟» pointing at the theme's login page.
 *
 * @return void
 */
function zandi_woo_checkout_login_link() {
	if ( is_user_logged_in() ) {
		return;
	}
	?>
	<p class="woo-login-hint">
		<?php echo esc_html( 'قبلاً حساب ساختی؟' ); ?>
		<a href="<?php echo esc_url( zandi_login_url( wc_get_checkout_url() ) ); ?>"><?php echo esc_html( 'وارد شو' ); ?></a>
	</p>
	<?php
}
add_action( 'woocommerce_before_checkout_form', 'zandi_woo_checkout_login_link', 5 );

/**
 * Suppresses WooCommerce's own login and coupon prompts at checkout.
 *
 * Its login form is a third auth surface, on a different trust model to Digits.
 * The link added above replaces it.
 *
 * @return void
 */
function zandi_woo_strip_checkout_prompts() {
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
}
add_action( 'init', 'zandi_woo_strip_checkout_prompts' );

/**
 * Cuts checkout down to what a digital course actually needs.
 *
 * WooCommerce's default billing block asks for company, country, province,
 * city, street address, apartment and postcode. Not one of them is used: the
 * product is virtual, shipping is off, and nothing is ever posted to anybody.
 * Asking a student to type an address before paying for a video course is a
 * form of friction with nothing on the other side of it.
 *
 * What is left is name, mobile and email — and for a signed-in student every
 * one of those is already known, so the form is a confirmation rather than an
 * interrogation.
 *
 * The mobile stays required. It is the join key between the account, the order
 * and the SpotPlayer licence — see zandi_user_phone() in inc/auth.php — and an
 * order without it cannot be matched to a student later.
 *
 * Email becomes optional. Sign-in is phone-only by design (Digits, no SMTP yet)
 * so a real student may genuinely not have one on file, and WooCommerce's
 * default would stop them at the last step of a purchase over a field the site
 * never promised to collect.
 *
 * @param array<string,mixed> $fields Checkout fields.
 * @return array<string,mixed>
 */
function zandi_woo_checkout_fields( $fields ) {
	/**
	 * Filters the billing fields removed from checkout.
	 *
	 * Add `billing_email` here to drop it too, or return an empty array to get
	 * WooCommerce's full address block back.
	 *
	 * @param array<int,string> $remove Field keys to unset.
	 */
	$remove = (array) apply_filters(
		'zandi_woo_checkout_remove_fields',
		array(
			'billing_company',
			'billing_country',
			'billing_state',
			'billing_city',
			'billing_address_1',
			'billing_address_2',
			'billing_postcode',
		)
	);

	foreach ( $remove as $key ) {
		unset( $fields['billing'][ $key ] );
	}

	// Nothing is shipped, so the whole block goes with it.
	$fields['shipping'] = array();

	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['required'] = true;
		$fields['billing']['billing_phone']['priority'] = 30;
		$fields['billing']['billing_phone']['label']    = 'شماره موبایل';
	}

	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['required'] = false;
		$fields['billing']['billing_email']['priority'] = 40;
		$fields['billing']['billing_email']['label']    = 'ایمیل (اختیاری)';
	}

	return zandi_woo_prefill_checkout( $fields );
}
add_filter( 'woocommerce_checkout_fields', 'zandi_woo_checkout_fields', 20 );

/**
 * Fills the remaining fields in from the signed-in student's account.
 *
 * @param array<string,mixed> $fields Checkout fields.
 * @return array<string,mixed>
 */
function zandi_woo_prefill_checkout( $fields ) {
	if ( ! is_user_logged_in() ) {
		return $fields;
	}

	$user  = wp_get_current_user();
	$phone = function_exists( 'zandi_user_phone' ) ? zandi_user_phone( $user->ID ) : '';

	$known = array(
		'billing_phone'      => $phone,
		'billing_email'      => $user->user_email,
		'billing_first_name' => $user->first_name ? $user->first_name : $user->display_name,
		'billing_last_name'  => $user->last_name,
	);

	foreach ( $known as $key => $value ) {
		if ( '' !== $value && isset( $fields['billing'][ $key ] ) ) {
			$fields['billing'][ $key ]['default'] = $value;
		}
	}

	return $fields;
}

/**
 * Keeps the order's phone number authoritative.
 *
 * A student can edit the checkout field, and an order whose phone does not
 * match the account is one the SpotPlayer licence cannot be traced back to.
 * The account's number wins.
 *
 * @param int $order_id Order being created.
 * @return void
 */
function zandi_woo_lock_order_phone( $order_id ) {
	if ( ! is_user_logged_in() || ! function_exists( 'zandi_user_phone' ) ) {
		return;
	}

	$phone = zandi_user_phone( get_current_user_id() );
	$order = wc_get_order( $order_id );

	if ( ! $phone || ! $order instanceof WC_Order || $order->get_billing_phone() === $phone ) {
		return;
	}

	$order->set_billing_phone( $phone );
	$order->save();
}
add_action( 'woocommerce_checkout_order_created', 'zandi_woo_lock_order_phone' );

/* =========================================================================
 * 6. Stripping the shop out of the shop
 *
 * Requirement by requirement: reviews, shipping, coupons, weights, related
 * products, sorting, result counts, the sidebar, the gallery. A course is a
 * video library, not a parcel.
 * ====================================================================== */

/**
 * Removes the furniture of a general store.
 *
 * @return void
 */
function zandi_woo_declutter() {
	/*
	 * The content wrappers. WooCommerce prints
	 * `<div id="primary"><main id="main" class="site-main">` here, and
	 * woocommerce.php in the theme root already supplies both — a second
	 * `id="main"` is invalid and breaks the skip link in header.php, which
	 * targets exactly that ID.
	 *
	 * This was two empty template overrides in woocommerce/global/. Unhooking
	 * is the same result with no files to fall out of step with a WooCommerce
	 * release.
	 */
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

	// Sidebar: the archive is a three-card grid, there is nothing to filter.
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

	// Sorting and «نمایش ۱–۳ از ۳ نتیجه» — noise on a catalogue of three.
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

	// Breadcrumbs: the theme prints its own via zandi_breadcrumb().
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

	// Related and upsell rows: unstyled card grids that break the page rhythm.
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );

	// Reviews and the meta row (SKU, category, tags).
	remove_action( 'woocommerce_after_single_product_summary', 'comments_template', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

	// The cart's cross-sell row.
	remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10 );
}
add_action( 'init', 'zandi_woo_declutter' );

/*
 * Reviews are switched off in two places because one is not enough: the tab
 * filter hides the UI, and comments_open() stops a review being posted by any
 * route that bypasses it.
 */
add_filter( 'woocommerce_product_tabs', 'zandi_woo_review_tab' );
add_filter( 'comments_open', 'zandi_woo_comments_open', 20, 2 );

/**
 * Drops the reviews tab, and the additional-information tab with it.
 *
 * Additional information is where weight and dimensions surface. A course has
 * neither, so the tab is either empty or wrong.
 *
 * @param array<string,mixed> $tabs Product tabs.
 * @return array<string,mixed>
 */
function zandi_woo_review_tab( $tabs ) {
	unset( $tabs['reviews'], $tabs['additional_information'] );

	return $tabs;
}

/**
 * Closes comments on products.
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function zandi_woo_comments_open( $open, $post_id ) {
	return 'product' === get_post_type( $post_id ) ? false : $open;
}

/**
 * Hides shipping, weight and dimension fields in the product editor.
 *
 * Cosmetic but worth it: every one of them is a field the owner might fill in
 * by accident, and a shipped course is a refund.
 *
 * @param array<string,mixed> $tabs Product data tabs.
 * @return array<string,mixed>
 */
function zandi_woo_product_data_tabs( $tabs ) {
	unset( $tabs['shipping'], $tabs['linked_product'] );

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'zandi_woo_product_data_tabs' );

/*
 * Shipping off at the source. WordPress ships __return_false for exactly this,
 * so there is no reason for the theme to carry its own copy of it.
 */
add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
add_filter( 'woocommerce_product_needs_shipping', '__return_false' );

/**
 * Switches coupons off.
 *
 * Requirement 14 says «unless already used». Nothing in this theme has ever
 * rendered a coupon field, so this starts off — and it is a filter, so turning
 * discounts on later is one line in a child theme or a snippet.
 *
 * @return bool
 */
function zandi_woo_coupons_enabled() {
	/**
	 * Filters whether coupons are available at checkout.
	 *
	 * Off because nothing in this theme has ever rendered a coupon field. Return
	 * true to turn discounts on; the checkout field styling is already in
	 * assets/css/shop.css.
	 *
	 * @param bool $enabled Default false.
	 */
	return (bool) apply_filters( 'zandi_woo_coupons', false );
}
add_filter( 'woocommerce_coupons_enabled', 'zandi_woo_coupons_enabled' );

/**
 * Every course is digital: virtual, downloadable, no stock to count.
 *
 * Set on the fly rather than trusted to the product editor, so a course cannot
 * be accidentally saved as a physical item that then asks for an address.
 *
 * @param bool       $virtual Whether the product is virtual.
 * @param WC_Product $product Product.
 * @return bool
 */
function zandi_woo_force_virtual( $virtual, $product ) {
	return zandi_product_course_slug( $product ) ? true : $virtual;
}
add_filter( 'woocommerce_product_is_virtual', 'zandi_woo_force_virtual', 10, 2 );

/**
 * Removes the "Sale!" flash.
 *
 * A red starburst over a course card is the single most shop-like element
 * WooCommerce ships.
 *
 * @return void
 */
function zandi_woo_no_sale_flash() {
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
}
add_action( 'init', 'zandi_woo_no_sale_flash' );

/**
 * Quantity is always one — a course is bought once.
 *
 * @return array<string,int>
 */
function zandi_woo_quantity_one() {
	return array(
		'min_value'   => 1,
		'max_value'   => 1,
		'input_value' => 1,
	);
}
add_filter( 'woocommerce_quantity_input_args', 'zandi_woo_quantity_one', 10, 1 );

/**
 * Stops the same course being bought twice.
 *
 * @param bool       $purchasable Whether the product can be bought.
 * @param WC_Product $product     Product.
 * @return bool
 */
function zandi_woo_block_repurchase( $purchasable, $product ) {
	if ( ! is_user_logged_in() ) {
		return $purchasable;
	}

	$slug = zandi_product_course_slug( $product );

	if ( $slug && zandi_student_owns_course( get_current_user_id(), $slug ) ) {
		return false;
	}

	return $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'zandi_woo_block_repurchase', 10, 2 );

/* =========================================================================
 * 7. Performance
 *
 * WooCommerce enqueues its stylesheets and its cart-fragments script on every
 * page of the site by default. On a homepage with no cart on it that is three
 * stylesheets and an admin-ajax request per visit, for nothing.
 * ====================================================================== */

/**
 * Whether the current request is a WooCommerce page at all.
 *
 * @return bool
 */
function zandi_is_woo_page() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

/**
 * Drops WooCommerce's own styles and scripts everywhere they are not needed.
 *
 * The theme restyles WooCommerce from its own tokens, so `woocommerce-general`
 * is dead weight even on shop pages — but `woocommerce-layout` and
 * `woocommerce-smallscreen` are left in place there, because they carry the
 * responsive table behaviour the cart depends on.
 *
 * @return void
 */
function zandi_woo_trim_assets() {
	// Dropped everywhere: the theme restyles WooCommerce from its own tokens.
	wp_dequeue_style( 'woocommerce-general' );

	if ( zandi_is_woo_page() ) {
		return;
	}

	wp_dequeue_style( 'woocommerce-layout' );
	wp_dequeue_style( 'woocommerce-smallscreen' );
	wp_dequeue_style( 'wc-blocks-style' );

	wp_dequeue_script( 'wc-cart-fragments' );
	wp_dequeue_script( 'woocommerce' );
	wp_dequeue_script( 'wc-add-to-cart' );

	// select2 is only ever needed by the country dropdown at checkout.
	wp_dequeue_style( 'select2' );
	wp_dequeue_script( 'select2' );
	wp_dequeue_script( 'selectWoo' );
}
add_action( 'wp_enqueue_scripts', 'zandi_woo_trim_assets', 99 );

/*
 * There was a zandi_woo_trim_head() here unhooking wc_gallery_noscript from
 * wp_head. Its docblock claimed to remove the generator tag and the block
 * styles, and it did neither — and the unhook itself was a no-op, because
 * WooCommerce only prints that <noscript> when the gallery features are
 * declared, which zandi_woo_setup() deliberately does not do.
 */

/* =========================================================================
 * 8. Chrome
 * ====================================================================== */

/**
 * Loads the shop stylesheet, and only on pages that need it.
 *
 * Depends on zandi-style and zandi-rtl exactly as panel.css does, so the
 * cascade order is the same on every page of the site.
 *
 * @return void
 */
function zandi_woo_assets() {
	if ( ! zandi_is_woo_page() ) {
		return;
	}

	wp_enqueue_style(
		'zandi-shop',
		get_theme_file_uri( 'assets/css/shop.css' ),
		array( 'zandi-style', 'zandi-rtl' ),
		zandi_asset_version( 'assets/css/shop.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'zandi_woo_assets', 20 );

/**
 * Adds a body class so the shop styles can scope themselves.
 *
 * `shop-page` mirrors `panel-page` and `course-page`; nothing in shop.css is
 * written without it.
 *
 * @param array<int,string> $classes Body classes.
 * @return array<int,string>
 */
function zandi_woo_body_class( $classes ) {
	if ( zandi_is_woo_page() ) {
		$classes[] = 'shop-page';
	}

	return $classes;
}
add_filter( 'body_class', 'zandi_woo_body_class' );

/**
 * The number of course cards per row on the archive.
 *
 * @return int
 */
function zandi_woo_columns() {
	return 3;
}
add_filter( 'loop_shop_columns', 'zandi_woo_columns' );

/**
 * Prices in Persian digits, matching zandi_price_toman() everywhere else.
 *
 * WooCommerce formats with Latin numerals; every other price on this site is
 * localised by zandi_fa_digits(). Two numbering systems on one page is the
 * exact bug the ss01 note in CLAUDE.md warns about.
 *
 * Front end only. wc_price() is also what wp-admin renders order totals,
 * reports and the product editor with, and an admin reading ۸٬۹۷۰٬۰۰۰ in the
 * orders list — or a script parsing it — is worse off than one reading Latin
 * numerals.
 *
 * @param string $formatted Formatted price HTML.
 * @return string
 */
function zandi_woo_persian_price( $formatted ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $formatted;
	}

	return zandi_fa_digits( $formatted );
}
add_filter( 'wc_price', 'zandi_woo_persian_price', 20, 1 );

/* =========================================================================
 * 9. Seams for what comes next
 *
 * Nothing below runs. They exist so the ZarinPal and SpotPlayer work has a
 * documented place to attach, rather than being wired into whichever file is
 * open at the time.
 * ====================================================================== */

/**
 * Fires when an order is paid for and the student owns the course.
 *
 * This is where SpotPlayer licence generation belongs:
 *
 *     add_action( 'zandi_course_purchased', function ( $order, $slug, $phone ) {
 *         $licence = my_spotplayer_create_licence( $slug, $phone );
 *         // store it on the order item under zandi_licence_meta_key()
 *     }, 10, 3 );
 *
 * The phone number is passed because it is the join key the SpotPlayer plugin
 * keys its licences to — see inc/auth.php.
 *
 * @param WC_Order $order Order.
 * @return void
 */
function zandi_woo_announce_purchase( $order ) {
	$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;

	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$user_id = (int) $order->get_customer_id();
	$phone   = function_exists( 'zandi_user_phone' ) && $user_id ? zandi_user_phone( $user_id ) : $order->get_billing_phone();

	foreach ( $order->get_items() as $item ) {
		$slug = zandi_product_course_slug( $item->get_product_id() );

		if ( ! $slug ) {
			continue;
		}

		/**
		 * Fires once per purchased course, after payment.
		 *
		 * @param WC_Order $order Order.
		 * @param string   $slug  Course slug.
		 * @param string   $phone Student's mobile number.
		 */
		do_action( 'zandi_course_purchased', $order, $slug, $phone );
	}
}
add_action( 'woocommerce_order_status_processing', 'zandi_woo_announce_purchase' );
add_action( 'woocommerce_order_status_completed', 'zandi_woo_announce_purchase' );

/* =========================================================================
 * 10. The Zandi SpotPlayer Integration plugin
 *
 * The plugin is a separate, self-contained piece of software: it talks to the
 * SpotPlayer API, stores licences in its own table, and exposes them through
 * global helper functions. The theme does not reach into it — it asks.
 *
 * TWO SYSTEMS THAT LOOK LIKE ONE, AND ARE NOT
 *
 * Both the theme and the plugin map a WooCommerce product to a "course", and
 * they are NOT the same mapping and must not be merged:
 *
 *   theme   `_zandi_course`                  product -> 'a1'  (a page on this
 *                                            site, its copy, its price)
 *   plugin  `_zandi_spotplayer_course_id`    product -> SpotPlayer's own course
 *                                            ID (a video library over there)
 *
 * One product carries both. Setting one does not set the other.
 *
 * WHERE THE LICENCE APPEARS
 *
 * The plugin registers a WooCommerce My Account endpoint of its own
 * (`/my-account/my-courses/`). This theme redirects every account page to
 * /panel/ — see zandi_woo_redirect_account() and the note above it — so that
 * endpoint is unreachable by design, and that is the right outcome: there is
 * one place a student looks for their courses, and it is the panel.
 *
 * What that costs is the wiring below. The panel fills its licence and player
 * fields from order-item meta, which the plugin never writes — it writes to its
 * own table. Without this bridge the panel would show a course with an empty
 * licence block forever, and nothing would say why.
 * ====================================================================== */

/**
 * Whether the SpotPlayer plugin is installed and exposing its helpers.
 *
 * Checked on the helper rather than on a class, because the helper is the
 * documented public surface — the namespaced classes are its internals.
 *
 * @return bool
 */
function zandi_spotplayer_active() {
	return function_exists( 'zandi_spotplayer_get_user_licenses' );
}

/**
 * Fills the panel's licence and player fields from the plugin.
 *
 * Runs after zandi_woo_student_courses() has decided *which* courses a student
 * owns — that stays the theme's answer, based on paid orders — and only
 * supplies the two fields the plugin is the authority on.
 *
 * A licence that is still pending is deliberately left blank: the panel hides
 * an empty licence block, which is better than showing a student a key that
 * does not work yet.
 *
 * @param array<int,array<string,string>> $courses Courses from the theme.
 * @param int                             $user_id Student's user ID.
 * @return array<int,array<string,string>>
 */
function zandi_spotplayer_fill_licences( $courses, $user_id ) {
	if ( ! $courses || ! zandi_spotplayer_active() ) {
		return $courses;
	}

	$licences = zandi_spotplayer_licences_by_product( (int) $user_id );

	if ( ! $licences ) {
		return $courses;
	}

	foreach ( $courses as $index => $course ) {
		if ( empty( $course['slug'] ) ) {
			continue;
		}

		$product_id = zandi_course_product_id( $course['slug'] );

		if ( ! $product_id || empty( $licences[ $product_id ] ) ) {
			continue;
		}

		$licence = $licences[ $product_id ];

		$courses[ $index ]['licence'] = $licence['code'];
		$courses[ $index ]['player']  = $licence['download'];
	}

	return $courses;
}
add_filter( 'zandi_student_courses', 'zandi_spotplayer_fill_licences', 20, 2 );

/**
 * The plugin's licences for a user, flattened and keyed by product ID.
 *
 * @param int $user_id Student's user ID.
 * @return array<int,array{code:string,download:string}>
 */
function zandi_spotplayer_licences_by_product( $user_id ) {
	if ( ! $user_id || ! zandi_spotplayer_active() ) {
		return array();
	}

	static $cache = array();

	if ( isset( $cache[ $user_id ] ) ) {
		return $cache[ $user_id ];
	}

	$out = array();

	foreach ( zandi_spotplayer_get_user_licenses( $user_id ) as $licence ) {
		$product_id = (int) $licence->product_id;

		if ( ! $product_id ) {
			continue;
		}

		/*
		 * Only an active licence is shown. `is_active()` is the plugin's own
		 * test, so a status it adds later is honoured without a change here.
		 */
		if ( method_exists( $licence, 'is_active' ) && ! $licence->is_active() ) {
			continue;
		}

		$download = isset( $licence->download_url ) ? (string) $licence->download_url : '';

		/*
		 * SpotPlayer returns `url` as a bare path — `/5e07…/91d0…/` — so what
		 * the plugin stored is not linkable on its own. Joining it here rather
		 * than at storage time means the host stays a setting, not a value
		 * baked into every row.
		 */
		if ( '' !== $download && ! preg_match( '#^https?://#i', $download ) ) {
			$download = zandi_spotplayer_dl_host() . '/' . ltrim( $download, '/' );
		}

		$out[ $product_id ] = array(
			// display_code() prefers the activation code and falls back to the key.
			'code'     => method_exists( $licence, 'display_code' ) ? (string) $licence->display_code() : (string) $licence->license_key,
			'download' => $download,
		);
	}

	$cache[ $user_id ] = $out;

	return $out;
}

/* -------------------------------------------------------------------------
 * Speaking SpotPlayer's actual schema
 *
 * The plugin builds its request body from a flat map of field names, which is
 * the right design for an API whose shape is unknown. SpotPlayer's is not flat:
 *
 *     {
 *       "course":    ["5d2ee35bcddc092a304ae5eb"],   <- an ARRAY
 *       "name":      "customer",
 *       "watermark": { "texts": [ { "text": "09121112266" } ] }
 *     }
 *
 * `course` as a bare string and `watermark` as a bare string are both rejected,
 * and no combination of field names in the settings screen can express either.
 * The plugin documents `zandi_spotplayer_license_request_payload` for exactly
 * this, so the reshaping happens here rather than by editing the plugin — which
 * would be undone by its next update.
 *
 * Reference: https://spotplayer.ir/help/api/docs
 * ---------------------------------------------------------------------- */

/**
 * Rewrites the outgoing payload into the shape SpotPlayer documents.
 *
 * @param array<string,mixed> $payload   Payload the plugin assembled.
 * @param array<string,mixed> $canonical Pre-mapping values, before renaming.
 * @param object              $customer  Buyer identity.
 * @return array<string,mixed>
 */
function zandi_spotplayer_shape_payload( $payload, $canonical, $customer = null ) {
	$course_id = isset( $canonical['course_id'] ) ? (string) $canonical['course_id'] : '';

	if ( '' === $course_id ) {
		return $payload;
	}

	// One licence per course here, but the field is a list either way.
	$payload['course'] = array( $course_id );

	/*
	 * The watermark is what deters re-uploads, so it is the mobile number —
	 * the same number that is the account, the order and the licence's join
	 * key. SpotPlayer's own WooCommerce plugin uses it for the same reason.
	 */
	$watermark = '';

	if ( isset( $canonical['phone'] ) && '' !== $canonical['phone'] ) {
		$watermark = (string) $canonical['phone'];
	} elseif ( isset( $canonical['watermark'] ) ) {
		$watermark = (string) $canonical['watermark'];
	}

	if ( '' !== $watermark ) {
		$payload['watermark'] = array(
			'texts' => array(
				array( 'text' => $watermark ),
			),
		);
	}

	if ( isset( $canonical['name'] ) && '' !== $canonical['name'] ) {
		$payload['name'] = (string) $canonical['name'];
	}

	/*
	 * `payload` comes back as a GET parameter when a student is sent from the
	 * player to the course's support page, so the order ID is the useful thing
	 * to put in it — it identifies the purchase without a lookup.
	 */
	if ( ! empty( $canonical['reference'] ) ) {
		$payload['payload'] = (string) $canonical['reference'];
	}

	// Fields the flat map may have sent that SpotPlayer does not accept.
	unset( $payload['course_id'], $payload['device_count'], $payload['reference'] );

	return $payload;
}
add_filter( 'zandi_spotplayer_license_request_payload', 'zandi_spotplayer_shape_payload', 10, 3 );

/**
 * The host that serves a licence's video-download page.
 *
 * SpotPlayer returns `url` as a path with no domain — `/5e07…/91d0…/` — so it
 * has to be joined to a host before it can be a link. Filterable because the
 * docs allow parking your own domain on it with a CNAME.
 *
 * @return string
 */
function zandi_spotplayer_dl_host() {
	/**
	 * Filters the SpotPlayer download host.
	 *
	 * @param string $host Default 'https://dl.spotplayer.ir'.
	 */
	return (string) apply_filters( 'zandi_spotplayer_dl_host', 'https://dl.spotplayer.ir' );
}
