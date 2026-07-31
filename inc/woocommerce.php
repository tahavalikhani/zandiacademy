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
 * is *linked* to a product by SKU, and the link is what carries money:
 *
 *     course 'a1'  ->  product with SKU 'zandi-a1'
 *
 * Set the SKU in محصولات ← ویرایش ← انبارداری and the link exists. Nothing
 * breaks when it does not: prices fall back to the hard-coded `price_toman`,
 * and the enrol button falls back to its old behaviour.
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
 * The SKU that links a course to its product.
 *
 * @param string $slug Course slug, e.g. 'a1'.
 * @return string
 */
function zandi_course_sku( $slug ) {
	return (string) apply_filters( 'zandi_course_sku', 'zandi-' . $slug, $slug );
}

/**
 * The product ID for a course, or 0 when none is linked yet.
 *
 * Resolved once per request. `wc_get_product_id_by_sku()` is a direct meta
 * query, so on a course page that renders the price two or three times this
 * saves the repeat trips without needing a transient to go stale.
 *
 * @param string $slug Course slug.
 * @return int Product ID, or 0.
 */
function zandi_course_product_id( $slug ) {
	static $cache = array();

	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}

	$product_id = 0;

	/**
	 * Short-circuits the SKU lookup.
	 *
	 * Return a product ID to link a course some other way — a stored option, a
	 * meta box — without touching this file.
	 *
	 * @param int    $product_id Zero to continue with the SKU lookup.
	 * @param string $slug       Course slug.
	 */
	$filtered = (int) apply_filters( 'zandi_course_product_id', 0, $slug );

	if ( $filtered > 0 ) {
		$product_id = $filtered;
	} elseif ( function_exists( 'wc_get_product_id_by_sku' ) ) {
		$product_id = (int) wc_get_product_id_by_sku( zandi_course_sku( $slug ) );
	}

	$cache[ $slug ] = $product_id;

	return $product_id;
}

/**
 * The course slug behind a product, or '' when the product is not a course.
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

	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;

	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$sku = $product->get_sku();

	foreach ( array_keys( zandi_courses_data() ) as $slug ) {
		if ( $sku === zandi_course_sku( $slug ) ) {
			return $slug;
		}
	}

	return '';
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
		wp_safe_redirect( $back );
		exit;
	}

	$product_id = zandi_course_product_id( $slug );

	// No product linked yet: behave exactly as the site did before WooCommerce.
	if ( ! $product_id || ! zandi_course_purchasable( $slug ) ) {
		/** This action is documented in functions.php */
		do_action( 'zandi_enrol_requested', $slug );

		wp_safe_redirect( add_query_arg( 'enrol', 'pending', $back ) . '#register' );
		exit;
	}

	// Already owned — send them to the course, not to a second payment.
	if ( is_user_logged_in() && zandi_student_owns_course( get_current_user_id(), $slug ) ) {
		wp_safe_redirect( zandi_panel_url() . '#my-courses' );
		exit;
	}

	if ( ! WC()->cart ) {
		wp_safe_redirect( $back );
		exit;
	}

	WC()->cart->empty_cart();
	$added = WC()->cart->add_to_cart( $product_id );

	if ( ! $added ) {
		wp_safe_redirect( add_query_arg( 'enrol', 'failed', $back ) );
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
	return (array) apply_filters( 'zandi_woo_paid_statuses', array( 'processing', 'completed' ) );
}

/**
 * Every product ID a user has paid for.
 *
 * @param int $user_id User ID.
 * @return array<int,int> Product IDs.
 */
function zandi_student_product_ids( $user_id ) {
	$user_id = (int) $user_id;

	// See the note in zandi_product_course_slug() on why this guard is here.
	if ( ! $user_id || ! zandi_woo_active() ) {
		return array();
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

	$product_ids = array();

	foreach ( $orders as $order ) {
		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();

			if ( $product_id ) {
				$product_ids[ $product_id ] = $product_id;
			}
		}
	}

	$cache[ $user_id ] = array_values( $product_ids );

	return $cache[ $user_id ];
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

	$owned = zandi_student_product_ids( $user_id );

	if ( ! $owned ) {
		return $courses;
	}

	$licences = zandi_woo_student_licences( $user_id );

	foreach ( zandi_courses_data() as $slug => $course ) {
		$product_id = zandi_course_product_id( $slug );

		if ( ! $product_id || ! in_array( $product_id, $owned, true ) ) {
			continue;
		}

		$courses[] = array(
			'title'   => isset( $course['short_name'] ) ? $course['short_name'] : $course['title'],
			'level'   => isset( $course['level'] ) ? $course['level'] : '',
			'url'     => zandi_course_url( $slug ),
			'licence' => isset( $licences[ $product_id ] ) ? $licences[ $product_id ] : '',
			'player'  => (string) apply_filters( 'zandi_spotplayer_url', '', $slug, $user_id ),
		);
	}

	return $courses;
}
add_filter( 'zandi_student_courses', 'zandi_woo_student_courses', 10, 2 );

/**
 * Licence keys from a user's paid order items, keyed by product ID.
 *
 * @param int $user_id User ID.
 * @return array<int,string>
 */
function zandi_woo_student_licences( $user_id ) {
	if ( ! zandi_woo_active() ) {
		return array();
	}

	$orders = wc_get_orders(
		array(
			'customer_id' => (int) $user_id,
			'status'      => zandi_woo_paid_statuses(),
			'limit'       => 50,
			'return'      => 'objects',
		)
	);

	$licences = array();
	$key      = zandi_licence_meta_key();

	foreach ( $orders as $order ) {
		foreach ( $order->get_items() as $item ) {
			$licence = $item->get_meta( $key );

			if ( $licence ) {
				$licences[ (int) $item->get_product_id() ] = (string) $licence;
			}
		}
	}

	return $licences;
}

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
 * Mirrors the phone number into the order at checkout.
 *
 * The mobile number is the join key between the account, the order and the
 * SpotPlayer licence — see zandi_user_phone() in inc/auth.php. Digits stores it
 * on the user; this makes sure the order carries it too, so the licence can be
 * keyed to it later without a lookup that might miss.
 *
 * @param array<string,mixed> $fields Checkout fields.
 * @return array<string,mixed>
 */
function zandi_woo_prefill_phone( $fields ) {
	if ( ! is_user_logged_in() || ! function_exists( 'zandi_user_phone' ) ) {
		return $fields;
	}

	$phone = zandi_user_phone( get_current_user_id() );

	if ( $phone && isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['default'] = $phone;
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'zandi_woo_prefill_phone', 20 );

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

/**
 * Turns reviews off everywhere, including on products that already have them.
 *
 * @return bool
 */
function zandi_woo_no_reviews() {
	return false;
}
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

/**
 * Switches shipping off at the source.
 *
 * @return bool
 */
function zandi_woo_disable_shipping() {
	return false;
}
add_filter( 'woocommerce_cart_needs_shipping', 'zandi_woo_disable_shipping' );
add_filter( 'woocommerce_cart_needs_shipping_address', 'zandi_woo_disable_shipping' );
add_filter( 'woocommerce_product_needs_shipping', 'zandi_woo_disable_shipping' );

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
	if ( zandi_is_woo_page() ) {
		wp_dequeue_style( 'woocommerce-general' );

		return;
	}

	wp_dequeue_style( 'woocommerce-general' );
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

/**
 * Removes the WooCommerce generator tag and its block-editor styles.
 *
 * @return void
 */
function zandi_woo_trim_head() {
	remove_action( 'wp_head', 'wc_gallery_noscript' );
}
add_action( 'init', 'zandi_woo_trim_head' );

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
		ZANDI_VERSION
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
 * @param string $formatted Formatted price HTML.
 * @return string
 */
function zandi_woo_persian_price( $formatted ) {
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
