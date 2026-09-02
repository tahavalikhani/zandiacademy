<?php
/**
 * پنل دانشجوها — every student, in one screen, in wp-admin.
 *
 * The owner asked for one place to see who has signed up: names, mobile
 * numbers, what the placement test put them at and how much of it they
 * answered, what they have paid and which courses they own, when they joined
 * and when they were last here. All of that already existed on the install and
 * none of it was readable: phone numbers sit under any of four meta keys, a
 * placement result is a serialized array, and a purchase has to be reassembled
 * from WooCommerce orders.
 *
 * TWO CONSTRAINTS CAME WITH THE REQUEST, AND BOTH ARE STRUCTURAL HERE.
 *
 * 1. IT MUST NOT SLOW THE SITE. This file is required from functions.php only
 *    when is_admin() is true, so a visitor's page request never parses it,
 *    never registers a hook and never runs a query on its behalf. Nothing in
 *    it hooks a front-end action — no wp_enqueue_scripts, no template_redirect,
 *    no wp_head — and the stylesheet is enqueued on this one screen alone. The
 *    list-table class is required inside the render callback rather than at
 *    file scope, because WP_List_Table only exists inside wp-admin.
 *
 *    The queries are flat rather than per-row: one WP_User_Query for the page
 *    of students, one cache_users() that primes every one of their meta rows
 *    in a single query, and nothing after that. Every column below reads user
 *    meta that is already in memory by then — which is why the placement level
 *    and the courses owned are mirrored into scalar meta at the moment they
 *    change (see zandi_placement_mirror() and zandi_sync_owned_courses())
 *    instead of being recomputed here twenty times a page.
 *
 *    WHAT THIS FEATURE DOES ADD OUTSIDE WP-ADMIN, in full, so the claim can be
 *    checked rather than believed. Three writes, each on an event, none on a
 *    page view:
 *
 *      wp_login                        one update_user_meta() — inc/auth.php
 *      zandi_placement_completed       the mirror and the tally — inc/placement.php
 *      woocommerce_order_status_changed  the owned-courses mirror — inc/woocommerce.php
 *
 *    A visitor reading a course page triggers none of them. Someone signing in,
 *    finishing the test or paying for a course triggers one, once, inside a
 *    request that is already writing to the database.
 *
 * 2. ONLY THE OWNER MAY SEE IT. The capability is manage_options, deliberately
 *    not zandi_is_staff() / edit_posts: an editor hired to write posts has no
 *    business reading students' mobile numbers. It is checked again at the top
 *    of the render callback and again in the export handler, rather than being
 *    left to menu registration.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. Who may look, and at whom
 * ====================================================================== */

/*
 * zandi_students_capability() is defined in inc/auth.php, not here: the
 * printable placement report asks the same question on the front end, where
 * this file is not loaded.
 */

/**
 * The roles that count as a student.
 *
 * `subscriber` is what this theme and Digits create; `customer` is what
 * WooCommerce creates at checkout. Anything with edit_posts is staff and is not
 * a student — see zandi_is_staff() in inc/auth.php.
 *
 * @return array<int,string>
 */
function zandi_students_roles() {
	return (array) apply_filters( 'zandi_students_roles', array( 'subscriber', 'customer' ) );
}

/**
 * The screen's slug.
 *
 * @return string
 */
function zandi_students_slug() {
	return 'zandi-students';
}

/**
 * A URL into the screen.
 *
 * @param array<string,string|int> $args Optional query arguments.
 * @return string
 */
function zandi_students_url( $args = array() ) {
	$args = array_merge( array( 'page' => zandi_students_slug() ), $args );

	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

/**
 * The nonced URL that produces the CSV.
 *
 * @return string
 */
function zandi_students_export_url() {
	return wp_nonce_url(
		add_query_arg( 'action', 'zandi_students_export', admin_url( 'admin-post.php' ) ),
		'zandi_students_export'
	);
}

/* =========================================================================
 * 2. Copy
 *
 * Every Persian string on the screen, behind a filter — the same arrangement as
 * zandi_panel_copy() in inc/panel.php. Nothing is hard-coded into markup.
 * ====================================================================== */

/**
 * Copy for the students screen.
 *
 * @return array<string,mixed>
 */
function zandi_students_copy() {
	return apply_filters(
		'zandi_students_copy',
		array(
			/* ---- chrome ---- */
			'menu'              => 'دانشجوها',
			'title'             => 'دانشجوها',
			'subtitle'          => 'هر کسی که در سایت حساب ساخته — سطح، دوره‌ها و پرداختش، یک‌جا.',
			'search'            => 'جستجو',
			'search_hint'       => 'نام یا شماره موبایل',
			'export'            => 'خروجی اکسل',
			'export_hint'       => 'همهٔ دانشجوها در یک فایل، باز شدنی با اکسل.',
			'back'              => '→ بازگشت به فهرست',
			'edit_user'         => 'ویرایش کاربر',
			'view_report'       => 'گزارش کامل و قابل چاپ',
			'empty'             => 'هنوز هیچ‌کس در سایت حساب نساخته.',
			'empty_search'      => 'با این جستجو یا فیلتر، دانشجویی پیدا نشد.',
			'not_found'         => 'این دانشجو پیدا نشد.',
			'denied'            => 'دسترسی به این صفحه فقط برای مدیر سایت است.',

			/* ---- the table ---- */
			'col_name'          => 'نام',
			'col_phone'         => 'موبایل',
			'col_email'         => 'ایمیل',
			'col_level'         => 'سطح',
			'col_score'         => 'نمرهٔ آزمون',
			'col_courses'       => 'دوره‌ها',
			'col_paid'          => 'پرداختی',
			'col_joined'        => 'عضویت',
			'col_seen'          => 'آخرین ورود',
			'no_level'          => 'آزمون نداده',
			'nothing'           => '—',
			'never'             => 'هرگز',
			'not_set'           => 'ثبت نشده',
			'toman'             => 'تومان',
			'score'             => '%1$s از %2$s',
			'blanks'            => '%s بی‌جواب',
			'students_count'    => '%s دانشجو',

			/* ---- filters ---- */
			'filter_all_levels' => 'همهٔ سطح‌ها',
			'filter_all_courses' => 'همهٔ دوره‌ها',
			'filter_no_test'    => 'آزمون نداده‌ها',
			'filter_apply'      => 'فیلتر',

			/* ---- tiles ---- */
			'tile_students'     => 'کل دانشجوها',
			'tile_new'          => 'عضو جدید (۳۰ روز)',
			'tile_tests'        => 'آزمون تعیین سطح',
			'tile_revenue'      => 'مجموع پرداخت‌ها',
			'tile_tests_note'   => '%s سیتینگ بدون حساب',
			'tile_tests_users'  => '%s دانشجوی آزمون‌داده',
			'tile_revenue_note' => 'جمع پرداختی همهٔ حساب‌ها بر اساس سفارش‌های پرداخت‌شدهٔ ووکامرس. چون تسویه فقط با حساب انجام می‌شود، سفارش بی‌حساب وجود ندارد.',

			/* ---- one student ---- */
			'profile'           => 'مشخصات',
			'placement'         => 'آزمون تعیین سطح',
			'purchases'         => 'دوره‌ها و پرداخت',
			'field_name'        => 'نام',
			'field_phone'       => 'موبایل',
			'field_email'       => 'ایمیل',
			'field_joined'      => 'تاریخ عضویت',
			'field_seen'        => 'آخرین ورود',
			'field_id'          => 'شناسهٔ کاربر',
			'no_placement'      => 'هنوز آزمون تعیین سطح نداده.',
			'no_purchases'      => 'هنوز هیچ دوره‌ای نخریده.',
			'level'             => 'سطح',
			'score_title'       => 'نمره',
			'answered'          => 'جواب‌داده',
			'blank'             => 'بی‌جواب',
			'idk'               => '«نمی‌دانم»',
			'duration'          => 'مدت آزمون',
			'minutes'           => '%s دقیقه',
			'taken_at'          => 'تاریخ آزمون',
			'skills'            => 'مهارت‌ها',
			'bands'             => 'بخش‌های آزمون',
			'band_passed'       => 'گرفته',
			'band_failed'       => 'نگرفته',
			'band_score'        => '%1$s از %2$s — حد نصاب %3$s',
			'gap'               => 'بخشی بالاتر را گرفته ولی بخش %s را نه — یعنی دانشش تکه‌تکه است، نه یک‌دست.',
			'review'            => 'مرور سوال‌به‌سوال',
			'plan'              => 'چه چیزی را باید مرور کند',
			'plan_empty'        => 'در هیچ بخشی نکتهٔ جامانده‌ای ندارد.',
			'history'           => 'دفعه‌های قبلی',
			'history_row'       => '%1$s — %2$s',
			'their_answer'      => 'جوابش',
			'right_answer'      => 'جواب درست',
			'outcome'           => array(
				'correct' => 'درست',
				'wrong'   => 'غلط',
				'idk'     => 'نمی‌دانم',
				'blank'   => 'بی‌جواب',
			),
			'licence'           => 'کد لایسنس اسپات‌پلیر',
			'no_licence'        => 'هنوز صادر نشده',
			'orders'            => 'سفارش‌ها',
			'order_number'      => 'سفارش',
			'order_status'      => 'وضعیت',
			'order_total'       => 'مبلغ',
			'order_date'        => 'تاریخ',
			'total_paid'        => 'مجموع پرداختی',
			'woo_off'           => 'ووکامرس فعال نیست، بنابراین اطلاعات خرید و پرداخت نمایش داده نمی‌شود.',
		)
	);
}

/**
 * One string out of the copy array.
 *
 * @param string $key     Copy key.
 * @param string $default Optional fallback.
 * @return string
 */
function zandi_students_text( $key, $default = '' ) {
	$copy = zandi_students_copy();

	return isset( $copy[ $key ] ) && is_string( $copy[ $key ] ) ? $copy[ $key ] : $default;
}

/* =========================================================================
 * 3. The menu, the screen and its one stylesheet
 * ====================================================================== */

/**
 * Registers the screen.
 *
 * A top-level menu rather than a submenu under کاربران: this is the screen the
 * owner opens most days, and the WordPress users list — which shows a login
 * name and an email and nothing else useful here — is not where she thinks to
 * look for a student.
 *
 * @return void
 */
function zandi_students_menu() {
	$hook = add_menu_page(
		zandi_students_text( 'title' ),
		zandi_students_text( 'menu' ),
		zandi_students_capability(),
		zandi_students_slug(),
		'zandi_students_render',
		'dashicons-welcome-learn-more',
		26
	);

	if ( $hook ) {
		add_action( 'load-' . $hook, 'zandi_students_screen_options' );
	}
}
add_action( 'admin_menu', 'zandi_students_menu' );

/**
 * Gives the screen wp-admin's own «تنظیمات صفحه» rows-per-page control.
 *
 * @return void
 */
function zandi_students_screen_options() {
	add_screen_option(
		'per_page',
		array(
			'label'   => zandi_students_text( 'menu' ),
			'default' => 20,
			'option'  => 'zandi_students_per_page',
		)
	);
}

/**
 * Persists that control's value.
 *
 * Registered at file scope, not inside the admin_menu callback: core runs
 * set_screen_options() early in the request, and a filter added later than that
 * silently never applies.
 *
 * @param mixed  $status Default false.
 * @param string $option Option name.
 * @param int    $value  Submitted value.
 * @return int
 */
function zandi_students_save_screen_option( $status, $option, $value ) {
	return max( 1, min( 200, (int) $value ) );
}
add_filter( 'set_screen_option_zandi_students_per_page', 'zandi_students_save_screen_option', 10, 3 );

/**
 * The screen's stylesheet — on this screen and nowhere else.
 *
 * Versioned by its own mtime through zandi_asset_version(), like every other
 * asset in the theme. See the note in CLAUDE.md about ZANDI_VERSION never
 * moving.
 *
 * @param string $hook Current admin page's hook suffix.
 * @return void
 */
function zandi_students_assets( $hook ) {
	if ( 'toplevel_page_' . zandi_students_slug() !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'zandi-admin-students',
		get_theme_file_uri( 'assets/css/admin-students.css' ),
		array(),
		zandi_asset_version( 'assets/css/admin-students.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'zandi_students_assets' );

/**
 * Renders whichever of the two views was asked for.
 *
 * @return void
 */
function zandi_students_render() {
	if ( ! current_user_can( zandi_students_capability() ) ) {
		wp_die( esc_html( zandi_students_text( 'denied' ) ), '', array( 'response' => 403 ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view switch.
	$student = isset( $_GET['student'] ) ? absint( wp_unslash( $_GET['student'] ) ) : 0;

	if ( $student ) {
		zandi_students_render_detail( $student );

		return;
	}

	zandi_students_render_list();
}

/* =========================================================================
 * 4. Reading one student
 *
 * Every getter here is a read of user meta that cache_users() has already
 * primed, so calling them once per row costs no queries.
 * ====================================================================== */

/**
 * A student's latest placement result, repairing the flat mirror if it is missing.
 *
 * @param int $user_id Student.
 * @return array<string,mixed>|null
 */
function zandi_students_result( $user_id ) {
	$result = zandi_placement_latest( $user_id );

	if ( $result && '' === (string) get_user_meta( $user_id, 'zandi_placement_level', true ) ) {
		zandi_placement_mirror( $user_id, $result );
	}

	return $result;
}

/**
 * A student's level, from the flat mirror.
 *
 * @param int $user_id Student.
 * @return string CEFR code, or '' when they have not taken the test.
 */
function zandi_students_level( $user_id ) {
	return (string) get_user_meta( $user_id, 'zandi_placement_level', true );
}

/**
 * The course slugs a student owns, from the mirror written on order changes.
 *
 * @param int $user_id Student.
 * @return array<int,string>
 */
function zandi_students_owned_courses( $user_id ) {
	$slugs = get_user_meta( $user_id, 'zandi_course_owned' );

	return is_array( $slugs ) ? array_values( array_unique( array_filter( array_map( 'strval', $slugs ) ) ) ) : array();
}

/**
 * What a student has paid, in Toman.
 *
 * TOMAN, NOT RIAL — the store is configured in Toman through the Persian
 * WooCommerce plugin and gateways that settle in Rial multiply by ten
 * themselves. See the unit note on zandi_course_price(). Do not convert here.
 *
 * WooCommerce maintains this per customer in `_money_spent` and clears it when
 * an order changes, so the usual path is the primed meta read and no query at
 * all. The fallback runs at most once per customer, ever: WC_Customer computes
 * the figure and writes it back to that same meta.
 *
 * @param int $user_id Student.
 * @return float
 */
function zandi_students_spent( $user_id ) {
	$stored = get_user_meta( $user_id, '_money_spent', true );

	if ( '' !== $stored && null !== $stored ) {
		return (float) $stored;
	}

	if ( ! zandi_woo_active() || ! class_exists( 'WC_Customer' ) ) {
		return 0.0;
	}

	try {
		$customer = new WC_Customer( (int) $user_id );
	} catch ( Exception $e ) {
		return 0.0;
	}

	return (float) $customer->get_total_spent();
}

/**
 * «۲۱ از ۳۰», in Persian digits.
 *
 * @param array<string,mixed> $result Scored result.
 * @return string
 */
function zandi_students_score_label( $result ) {
	if ( ! $result ) {
		return '';
	}

	return sprintf(
		zandi_students_text( 'score' ),
		zandi_fa_digits( isset( $result['correct'] ) ? (int) $result['correct'] : 0 ),
		zandi_fa_digits( isset( $result['total'] ) ? (int) $result['total'] : 0 )
	);
}

/**
 * The levels the filter offers, in the order the test walks them.
 *
 * Built from the question bank's own bands rather than typed out, so adding a
 * band to inc/data/questions.json extends the filter with it.
 *
 * @return array<int,string>
 */
function zandi_students_levels() {
	$levels = array( 'pre-A1' );

	foreach ( zandi_placement_bands() as $band ) {
		$levels[] = (string) $band['id'];
		$levels[] = $band['id'] . '+';
	}

	// The top band's half step is never awarded: mastering it returns the code.
	array_pop( $levels );

	return $levels;
}

/**
 * The level filter's value, validated against the levels that exist.
 *
 * A WHITELIST, AND IT HAS TO BE ONE. «A1+» is a legal level and `+` is the
 * urlencoding of a space, so any hop that decodes the query string one time too
 * many turns the filter into «A1 » and it silently matches nothing. Browsers
 * encode a form's `+` as `%2B` and WordPress carries the raw query string
 * through pagination, so the round trip is correct today — but a filter that
 * quietly returns an empty list when it breaks is a bad thing to leave resting
 * on that. Matching against zandi_students_levels() both validates the input
 * and recovers the plus.
 *
 * @return string A level, 'none' for students who have not sat the test, or ''.
 */
function zandi_students_filter_level() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, and whitelisted below.
	$raw = isset( $_REQUEST['zandi_level'] ) ? (string) wp_unslash( $_REQUEST['zandi_level'] ) : '';

	/*
	 * The space goes back to being a plus BEFORE anything trims it.
	 * sanitize_text_field() trims, and the plus that needs recovering is the
	 * last character of «A1+» — so sanitising first destroys the very evidence
	 * this is here to act on, and «A1 » quietly becomes «A1»: a filter that
	 * looks like it worked and shows the wrong students. Taking the raw value
	 * first is safe because the only way out of this function is a strict match
	 * against a list the theme built itself.
	 */
	$raw = sanitize_text_field( str_replace( ' ', '+', $raw ) );

	if ( 'none' === $raw ) {
		return 'none';
	}

	return in_array( $raw, zandi_students_levels(), true ) ? $raw : '';
}

/* =========================================================================
 * 5. The one-time backfill
 *
 * The two mirrors this screen reads are written when a test is scored and when
 * an order changes status. Neither of those has happened yet for an account
 * that existed before this file did, so the first look at a student has to fill
 * them in.
 *
 * It is bounded and it terminates: a pass touches at most 25 accounts, and it
 * flags every account it touches whether or not it found anything, so no
 * account is ever looked at twice.
 *
 * TWENTY-FIVE, NOT TWO HUNDRED, and the number is chosen rather than picked.
 * Rebuilding the owned-courses mirror costs one order query per student — it
 * reuses zandi_student_purchases(), which is the path that is already right
 * about refunds and paid statuses, rather than a second implementation invented
 * for a job that runs once. Twenty-five of those is a screen that opens
 * normally; two hundred is one that might not open at all. A backlog simply
 * takes a few visits to clear, and every student is correct from the visit that
 * catches them.
 * ====================================================================== */

/**
 * Fills the mirrors for students who have never been seen by this screen.
 *
 * @return void
 */
function zandi_students_backfill() {
	$query = new WP_User_Query(
		array(
			'role__in'    => zandi_students_roles(),
			'number'      => 25,
			'fields'      => 'ids',
			'count_total' => false,
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Runs at most once per student, ever.
				array(
					'key'     => 'zandi_students_synced',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	$ids = $query->get_results();

	if ( ! $ids ) {
		return;
	}

	cache_users( $ids );

	foreach ( $ids as $user_id ) {
		$user_id = (int) $user_id;
		$result  = zandi_placement_latest( $user_id );

		if ( $result ) {
			zandi_placement_mirror( $user_id, $result );
		}

		if ( function_exists( 'zandi_sync_owned_courses' ) ) {
			zandi_sync_owned_courses( $user_id );
		}

		update_user_meta( $user_id, 'zandi_students_synced', 1 );
	}
}

/* =========================================================================
 * 6. The four numbers above the table
 *
 * Cached for fifteen minutes and dropped whenever a student signs up or an
 * order changes status, so the screen never pays for them twice in a sitting.
 * ====================================================================== */

/**
 * The summary tiles.
 *
 * @return array<string,int|float>
 */
function zandi_students_tiles() {
	$cached = get_transient( 'zandi_students_tiles' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;

	$total = new WP_User_Query(
		array(
			'role__in'    => zandi_students_roles(),
			'number'      => 1,
			'fields'      => 'ids',
			'count_total' => true,
		)
	);

	$recent = new WP_User_Query(
		array(
			'role__in'    => zandi_students_roles(),
			'number'      => 1,
			'fields'      => 'ids',
			'count_total' => true,
			'date_query'  => array(
				array( 'after' => '30 days ago' ),
			),
		)
	);

	$tested = new WP_User_Query(
		array(
			'role__in'    => zandi_students_roles(),
			'number'      => 1,
			'fields'      => 'ids',
			'count_total' => true,
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Cached for fifteen minutes.
				array(
					'key'     => 'zandi_placement_level',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	$tally = zandi_placement_tally_data();

	// WooCommerce's own per-customer lifetime figure, summed. One query.
	$revenue = (float) $wpdb->get_var(
		$wpdb->prepare( "SELECT SUM(meta_value) FROM {$wpdb->usermeta} WHERE meta_key = %s", '_money_spent' )
	);

	$tiles = array(
		'students' => (int) $total->get_total(),
		'recent'   => (int) $recent->get_total(),
		'tested'   => (int) $tested->get_total(),
		'sittings' => (int) $tally['total'],
		'guests'   => (int) $tally['guests'],
		'revenue'  => $revenue,
	);

	set_transient( 'zandi_students_tiles', $tiles, 15 * MINUTE_IN_SECONDS );

	return $tiles;
}

/**
 * Drops the cached tiles.
 *
 * Both hooks only ever fire inside wp-admin from here, because this file is
 * only loaded there — a student signing up on the front end is picked up when
 * the fifteen minutes are up, which is what the expiry is for. Registering
 * these on the front end too would mean loading this whole file on every public
 * request to keep a dashboard number fresh, and that trade is the wrong way
 * round.
 *
 * @return void
 */
function zandi_students_flush_tiles() {
	delete_transient( 'zandi_students_tiles' );
}
add_action( 'user_register', 'zandi_students_flush_tiles' );
add_action( 'woocommerce_order_status_changed', 'zandi_students_flush_tiles' );

/* =========================================================================
 * 7. The list
 * ====================================================================== */

/**
 * Renders the list screen — tiles, filters, table.
 *
 * WP_List_Table lives in wp-admin and does not exist on the front end, so both
 * it and the subclass are required here rather than at file scope.
 *
 * @return void
 */
function zandi_students_render_list() {
	zandi_students_backfill();

	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once get_theme_file_path( 'inc/class-zandi-students-table.php' );

	$copy  = zandi_students_copy();
	$table = new Zandi_Students_Table();

	$table->prepare_items();
	?>
	<div class="wrap zandi-students">
		<h1 class="wp-heading-inline"><?php echo esc_html( $copy['title'] ); ?></h1>

		<a class="page-title-action" href="<?php echo esc_url( zandi_students_export_url() ); ?>" title="<?php echo esc_attr( $copy['export_hint'] ); ?>">
			<?php echo esc_html( $copy['export'] ); ?>
		</a>

		<hr class="wp-header-end">

		<p class="zandi-students__lead"><?php echo esc_html( $copy['subtitle'] ); ?></p>

		<?php zandi_students_render_tiles(); ?>

		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( zandi_students_slug() ); ?>">
			<?php
			$table->search_box( $copy['search'], 'zandi-students' );
			$table->display();
			?>
		</form>
	</div>
	<?php
}

/**
 * The four summary tiles.
 *
 * @return void
 */
function zandi_students_render_tiles() {
	$copy  = zandi_students_copy();
	$tiles = zandi_students_tiles();

	/*
	 * The sitting tally counts everyone who finished the test, including the
	 * ones with no account — which is the honest number — but it only starts
	 * from the day it was added. Until it has anything in it, the count of
	 * students holding a result is the better answer.
	 */
	$sittings   = $tiles['sittings'] ? $tiles['sittings'] : $tiles['tested'];
	$tests_note = $tiles['sittings']
		? sprintf( $copy['tile_tests_note'], zandi_fa_digits( $tiles['guests'] ) )
		: sprintf( $copy['tile_tests_users'], zandi_fa_digits( $tiles['tested'] ) );
	?>
	<div class="zandi-tiles">
		<div class="zandi-tile">
			<span class="zandi-tile__label"><?php echo esc_html( $copy['tile_students'] ); ?></span>
			<span class="zandi-tile__value"><?php echo esc_html( zandi_fa_digits( $tiles['students'] ) ); ?></span>
		</div>

		<div class="zandi-tile">
			<span class="zandi-tile__label"><?php echo esc_html( $copy['tile_new'] ); ?></span>
			<span class="zandi-tile__value"><?php echo esc_html( zandi_fa_digits( $tiles['recent'] ) ); ?></span>
		</div>

		<div class="zandi-tile">
			<span class="zandi-tile__label"><?php echo esc_html( $copy['tile_tests'] ); ?></span>
			<span class="zandi-tile__value"><?php echo esc_html( zandi_fa_digits( $sittings ) ); ?></span>
			<span class="zandi-tile__note"><?php echo esc_html( $tests_note ); ?></span>
		</div>

		<div class="zandi-tile" title="<?php echo esc_attr( $copy['tile_revenue_note'] ); ?>">
			<span class="zandi-tile__label"><?php echo esc_html( $copy['tile_revenue'] ); ?></span>
			<span class="zandi-tile__value">
				<?php echo esc_html( zandi_price_toman( $tiles['revenue'] ) ); ?>
				<small><?php echo esc_html( $copy['toman'] ); ?></small>
			</span>
		</div>
	</div>
	<?php
}

/* =========================================================================
 * 8. One student
 * ====================================================================== */

/**
 * Renders everything known about one student.
 *
 * @param int $user_id Student.
 * @return void
 */
function zandi_students_render_detail( $user_id ) {
	$copy = zandi_students_copy();
	$user = get_userdata( $user_id );

	/*
	 * Only a student. The screen shows mobile numbers and test answers, and
	 * turning it into a viewer for any user ID — another administrator's
	 * account included — is not what it is for.
	 */
	if ( ! $user || ! array_intersect( zandi_students_roles(), (array) $user->roles ) ) {
		?>
		<div class="wrap zandi-students">
			<h1><?php echo esc_html( $copy['title'] ); ?></h1>
			<div class="notice notice-warning"><p><?php echo esc_html( $copy['not_found'] ); ?></p></div>
			<p><a href="<?php echo esc_url( zandi_students_url() ); ?>"><?php echo esc_html( $copy['back'] ); ?></a></p>
		</div>
		<?php
		return;
	}

	$phone  = zandi_user_phone( $user->ID );
	$level  = zandi_students_level( $user->ID );
	$result = zandi_students_result( $user->ID );
	$seen   = zandi_last_login( $user->ID );
	?>
	<div class="wrap zandi-students zandi-student">
		<p class="zandi-student__back"><a href="<?php echo esc_url( zandi_students_url() ); ?>"><?php echo esc_html( $copy['back'] ); ?></a></p>

		<div class="zandi-student__head">
			<?php /* Not get_avatar(): Gravatar is not reliably reachable from Iran, so the initial is drawn in CSS instead of waiting on an image that may never arrive. */ ?>
			<span class="zandi-initial" aria-hidden="true"><?php echo esc_html( zandi_first_char( $user->display_name ? $user->display_name : $user->user_login ) ); ?></span>

			<div>
				<h1><?php echo esc_html( $user->display_name ); ?></h1>
				<p class="zandi-student__level">
					<?php
					echo $level
						? wp_kses_post( zandi_placement_level_label( $level ) )
						: esc_html( $copy['no_level'] );
					?>
				</p>
			</div>

			<p class="zandi-student__actions">
				<a class="button" href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html( $copy['edit_user'] ); ?></a>

				<?php if ( $result ) : ?>
					<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( add_query_arg( 'student', $user->ID, zandi_placement_report_url() ) ); ?>">
						<?php echo esc_html( $copy['view_report'] ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>

		<div class="zandi-card">
			<h2><?php echo esc_html( $copy['profile'] ); ?></h2>

			<div class="zandi-fields">
				<?php
				zandi_students_field( $copy['field_name'], $user->display_name );

				zandi_students_field(
					$copy['field_phone'],
					$phone ? zandi_format_phone( $phone ) : $copy['not_set'],
					array(
						'ltr'  => true,
						'link' => $phone ? 'tel:' . $phone : '',
					)
				);

				zandi_students_field(
					$copy['field_email'],
					$user->user_email ? $user->user_email : $copy['not_set'],
					array(
						'ltr'  => true,
						'link' => $user->user_email ? 'mailto:' . $user->user_email : '',
					)
				);

				zandi_students_field( $copy['field_joined'], zandi_placement_date( strtotime( $user->user_registered ) ) );
				zandi_students_field( $copy['field_seen'], $seen ? zandi_placement_date( $seen ) : $copy['never'] );
				zandi_students_field( $copy['field_id'], zandi_fa_digits( $user->ID ) );
				?>
			</div>
		</div>

		<?php
		zandi_students_detail_placement( $user, $result );
		zandi_students_detail_purchases( $user );
		?>
	</div>
	<?php
}

/**
 * One labelled value in the مشخصات card.
 *
 * @param string               $label Field label.
 * @param string               $value Field value, unescaped.
 * @param array<string,mixed>  $args  'ltr' to mark the value's own element
 *                                    left-to-right — on the ELEMENT, never on a
 *                                    span inside it — and 'link' to wrap it.
 * @return void
 */
function zandi_students_field( $label, $value, $args = array() ) {
	$ltr  = ! empty( $args['ltr'] );
	$link = isset( $args['link'] ) ? (string) $args['link'] : '';
	?>
	<div class="zandi-field">
		<span class="zandi-field__label"><?php echo esc_html( $label ); ?></span>
		<span class="zandi-field__value"<?php echo $ltr ? ' dir="ltr"' : ''; ?>>
			<?php if ( $link ) : ?>
				<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $value ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $value ); ?>
			<?php endif; ?>
		</span>
	</div>
	<?php
}

/**
 * The placement card: level, score, skills, bands, review and study plan.
 *
 * Every piece of this is computed by inc/placement.php from the stored result —
 * zandi_placement_review() and zandi_placement_study_plan() are the same
 * functions the student's own printable report calls.
 *
 * @param WP_User                  $user   Student.
 * @param array<string,mixed>|null $result Their latest result.
 * @return void
 */
function zandi_students_detail_placement( $user, $result ) {
	$copy = zandi_students_copy();
	?>
	<div class="zandi-card">
		<h2><?php echo esc_html( $copy['placement'] ); ?></h2>
	<?php
	if ( ! $result ) {
		echo '<p class="zandi-empty">' . esc_html( $copy['no_placement'] ) . '</p></div>';

		return;
	}

	$total    = isset( $result['total'] ) ? (int) $result['total'] : 0;
	$answered = isset( $result['answered'] ) ? (int) $result['answered'] : 0;
	$minutes  = ! empty( $result['duration'] ) ? max( 1, (int) round( $result['duration'] / 60 ) ) : 0;
	$review   = zandi_placement_review( $result );
	$plan     = zandi_placement_study_plan( $result );
	?>
		<div class="zandi-fields">
			<?php
			zandi_students_field( $copy['score_title'], zandi_students_score_label( $result ) );
			zandi_students_field( $copy['answered'], zandi_fa_digits( $answered ) );
			zandi_students_field( $copy['blank'], zandi_fa_digits( max( 0, $total - $answered ) ) );
			zandi_students_field( $copy['idk'], zandi_fa_digits( isset( $result['idk'] ) ? (int) $result['idk'] : 0 ) );
			zandi_students_field( $copy['duration'], $minutes ? sprintf( $copy['minutes'], zandi_fa_digits( $minutes ) ) : $copy['nothing'] );
			zandi_students_field( $copy['taken_at'], zandi_placement_date( isset( $result['time'] ) ? (int) $result['time'] : 0 ) );
			?>
		</div>

		<?php if ( ! empty( $result['gap'] ) && ! empty( $result['gap_band'] ) ) : ?>
			<p class="zandi-note"><?php echo esc_html( sprintf( $copy['gap'], $result['gap_band'] ) ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $result['skills'] ) ) : ?>
			<h3><?php echo esc_html( $copy['skills'] ); ?></h3>

			<ul class="zandi-bars">
				<?php foreach ( (array) $result['skills'] as $skill ) : ?>
					<li>
						<span class="zandi-bars__label"><?php echo esc_html( $skill['label'] ); ?></span>
						<span class="zandi-bars__track"><span class="zandi-bars__fill" style="inline-size: <?php echo esc_attr( (int) $skill['percent'] ); ?>%"></span></span>
						<span class="zandi-bars__value">
							<?php
							echo esc_html(
								sprintf(
									$copy['score'],
									zandi_fa_digits( (int) $skill['correct'] ),
									zandi_fa_digits( (int) $skill['total'] )
								)
							);
							?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $result['bands'] ) ) : ?>
			<h3><?php echo esc_html( $copy['bands'] ); ?></h3>

			<ul class="zandi-bands">
				<?php foreach ( (array) $result['bands'] as $band ) : ?>
					<li class="zandi-bands__row is-<?php echo $band['passed'] ? 'passed' : 'failed'; ?>">
						<span class="zandi-bands__id" dir="ltr"><?php echo esc_html( $band['id'] ); ?></span>
						<span class="zandi-bands__state"><?php echo esc_html( $band['passed'] ? $copy['band_passed'] : $copy['band_failed'] ); ?></span>
						<span class="zandi-bands__score">
							<?php
							echo esc_html(
								sprintf(
									$copy['band_score'],
									zandi_fa_digits( (int) $band['correct'] ),
									zandi_fa_digits( (int) $band['count'] ),
									zandi_fa_digits( (int) $band['mastery'] )
								)
							);
							?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<h3><?php echo esc_html( $copy['plan'] ); ?></h3>

		<?php if ( ! $plan ) : ?>
			<p class="zandi-empty"><?php echo esc_html( $copy['plan_empty'] ); ?></p>
		<?php else : ?>
			<ul class="zandi-plan">
				<?php foreach ( $plan as $band ) : ?>
					<li>
						<span class="zandi-plan__band" dir="ltr"><?php echo esc_html( $band['id'] ); ?></span>
						<ul>
							<?php foreach ( $band['topics'] as $topic ) : ?>
								<li><?php echo esc_html( $topic['focus'] ); ?> <span class="zandi-plan__skill"><?php echo esc_html( $topic['skill'] ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<details class="zandi-review">
			<summary><?php echo esc_html( $copy['review'] ); ?></summary>

			<ol class="zandi-review__list">
				<?php foreach ( $review as $row ) : ?>
					<li class="zandi-review__row is-<?php echo esc_attr( $row['outcome'] ); ?>">
						<p class="zandi-review__stem"<?php echo zandi_placement_dir_attrs( $row['stem'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns a fixed attribute string. ?>>
							<?php echo zandi_placement_content( $row['stem'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by zandi_placement_content(). ?>
						</p>

						<p class="zandi-review__answers">
							<span class="zandi-review__outcome"><?php echo esc_html( isset( $copy['outcome'][ $row['outcome'] ] ) ? $copy['outcome'][ $row['outcome'] ] : '' ); ?></span>

							<?php if ( '' !== $row['chosen'] ) : ?>
								<span><?php echo esc_html( $copy['their_answer'] ); ?>: <b dir="ltr"><?php echo esc_html( $row['chosen'] ); ?></b></span>
							<?php endif; ?>

							<?php if ( 'correct' !== $row['outcome'] ) : ?>
								<span><?php echo esc_html( $copy['right_answer'] ); ?>: <b dir="ltr"><?php echo esc_html( $row['correct'] ); ?></b></span>
							<?php endif; ?>
						</p>
					</li>
				<?php endforeach; ?>
			</ol>
		</details>

		<?php
		$history = get_user_meta( $user->ID, 'zandi_placement_history', true );
		$history = is_array( $history ) ? array_reverse( $history ) : array();

		if ( count( $history ) > 1 ) :
			?>
			<h3><?php echo esc_html( $copy['history'] ); ?></h3>

			<ul class="zandi-history">
				<?php foreach ( $history as $sitting ) : ?>
					<li>
						<span dir="ltr"><?php echo esc_html( isset( $sitting['level'] ) ? $sitting['level'] : '' ); ?></span>
						<span><?php echo esc_html( zandi_placement_date( isset( $sitting['time'] ) ? (int) $sitting['time'] : 0 ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The courses-and-payment card.
 *
 * Reads live rather than from the mirror: this is one student, so
 * zandi_student_courses() — the same call the student's own panel makes, with
 * the SpotPlayer licence filter already attached — is both cheaper to trust and
 * impossible to have gone stale.
 *
 * @param WP_User $user Student.
 * @return void
 */
function zandi_students_detail_purchases( $user ) {
	$copy = zandi_students_copy();
	?>
	<div class="zandi-card">
		<h2><?php echo esc_html( $copy['purchases'] ); ?></h2>
	<?php
	if ( ! zandi_woo_active() ) {
		echo '<p class="zandi-empty">' . esc_html( $copy['woo_off'] ) . '</p></div>';

		return;
	}

	$courses = zandi_student_courses( $user->ID );
	$orders  = wc_get_orders(
		array(
			'customer_id' => $user->ID,
			'limit'       => 20,
			'orderby'     => 'date',
			'order'       => 'DESC',
		)
	);
	?>
		<?php if ( ! $courses ) : ?>
			<p class="zandi-empty"><?php echo esc_html( $copy['no_purchases'] ); ?></p>
		<?php else : ?>
			<ul class="zandi-courses">
				<?php foreach ( $courses as $course ) : ?>
					<li>
						<a href="<?php echo esc_url( $course['url'] ); ?>"><?php echo esc_html( $course['title'] ); ?></a>
						<span class="zandi-courses__licence">
							<?php echo esc_html( $copy['licence'] ); ?>:
							<?php if ( ! empty( $course['licence'] ) ) : ?>
								<code dir="ltr"><?php echo esc_html( $course['licence'] ); ?></code>
							<?php else : ?>
								<?php echo esc_html( $copy['no_licence'] ); ?>
							<?php endif; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="zandi-fields">
			<?php
			zandi_students_field(
				$copy['total_paid'],
				zandi_price_toman( zandi_students_spent( $user->ID ) ) . ' ' . $copy['toman']
			);
			?>
		</div>

		<?php if ( $orders ) : ?>
			<h3><?php echo esc_html( $copy['orders'] ); ?></h3>

			<table class="widefat striped zandi-orders">
				<thead>
					<tr>
						<th><?php echo esc_html( $copy['order_number'] ); ?></th>
						<th><?php echo esc_html( $copy['order_status'] ); ?></th>
						<th><?php echo esc_html( $copy['order_total'] ); ?></th>
						<th><?php echo esc_html( $copy['order_date'] ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $orders as $order ) : ?>
						<?php $edit = method_exists( $order, 'get_edit_order_url' ) ? $order->get_edit_order_url() : ''; ?>
						<tr>
							<td>
								<?php if ( $edit ) : ?>
									<a href="<?php echo esc_url( $edit ); ?>">#<?php echo esc_html( zandi_fa_digits( $order->get_order_number() ) ); ?></a>
								<?php else : ?>
									#<?php echo esc_html( zandi_fa_digits( $order->get_order_number() ) ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
							<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
							<td>
								<?php
								$date = $order->get_date_created();
								echo esc_html( $date ? zandi_placement_date( $date->getTimestamp() ) : '' );
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/* =========================================================================
 * 9. The export
 * ====================================================================== */

/**
 * Streams every student as a CSV Excel can open.
 *
 * Three things are load-bearing and all three have bitten someone before:
 *
 * - THE BOM. Without the three bytes at the front, Excel reads the file in the
 *   system code page and every Persian name becomes mojibake. LibreOffice asks;
 *   Excel does not.
 * - THE INJECTION GUARD. Names and emails are typed by the people in the file.
 *   A cell that starts with `=`, `+`, `-` or `@` is a formula to a spreadsheet,
 *   and one that starts with `=cmd|…` is a remote-code-execution attempt on
 *   whoever opens it. Every value goes through zandi_students_csv_cell().
 * - LATIN DIGITS AND ISO DATES. The screen is where the owner reads Persian
 *   digits and Jalali dates; the spreadsheet is where she sorts, filters and
 *   adds up, and it can do none of those things to «۲ شهریور ۱۴۰۵».
 *
 * Users are read two hundred at a time so the memory profile stays flat no
 * matter how many there are.
 *
 * @return void
 */
function zandi_students_export() {
	if ( ! current_user_can( zandi_students_capability() ) ) {
		wp_die( esc_html( zandi_students_text( 'denied' ) ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( 'zandi_students_export' );

	$copy     = zandi_students_copy();
	$filename = 'zandi-students-' . gmdate( 'Y-m-d' ) . '.csv';

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );

	$out = fopen( 'php://output', 'w' );

	// Before a single other byte, or Excel never sees it.
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writing to the response, not the filesystem.

	fputcsv(
		$out,
		array(
			$copy['col_name'],
			$copy['col_phone'],
			$copy['col_email'],
			$copy['col_level'],
			$copy['score_title'],
			$copy['answered'],
			$copy['blank'],
			$copy['col_courses'],
			$copy['col_paid'],
			$copy['col_joined'],
			$copy['col_seen'],
		)
	);

	$paged = 1;

	do {
		$query = new WP_User_Query(
			array(
				'role__in'    => zandi_students_roles(),
				'number'      => 200,
				'paged'       => $paged,
				'orderby'     => 'registered',
				'order'       => 'DESC',
				'fields'      => 'all',
				'count_total' => false,
			)
		);

		$users = $query->get_results();

		if ( ! $users ) {
			break;
		}

		cache_users( wp_list_pluck( $users, 'ID' ) );

		foreach ( $users as $user ) {
			$result   = zandi_placement_latest( $user->ID );
			$total    = $result && isset( $result['total'] ) ? (int) $result['total'] : 0;
			$answered = $result && isset( $result['answered'] ) ? (int) $result['answered'] : 0;
			$seen     = zandi_last_login( $user->ID );
			$courses  = array();

			foreach ( zandi_students_owned_courses( $user->ID ) as $slug ) {
				$course = zandi_get_course( $slug );

				// short_name is the course's NAME — «دوره پایه A1». `title` is its
				// marketing headline, which is a sentence with an emoji in it and
				// has no business in a spreadsheet column.
				$courses[] = ( $course && ! empty( $course['short_name'] ) ) ? $course['short_name'] : $slug;
			}

			fputcsv(
				$out,
				array_map(
					'zandi_students_csv_cell',
					array(
						$user->display_name,
						zandi_user_phone( $user->ID ),
						$user->user_email,
						zandi_students_level( $user->ID ),
						$result ? (int) ( isset( $result['correct'] ) ? $result['correct'] : 0 ) . '/' . $total : '',
						$result ? $answered : '',
						$result ? max( 0, $total - $answered ) : '',
						implode( ' + ', $courses ),
						(int) zandi_students_spent( $user->ID ),
						gmdate( 'Y-m-d', strtotime( $user->user_registered ) ),
						$seen ? gmdate( 'Y-m-d', $seen ) : '',
					)
				)
			);
		}

		++$paged;
	} while ( count( $users ) === 200 );

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the response stream.
	exit;
}
add_action( 'admin_post_zandi_students_export', 'zandi_students_export' );

/**
 * Defuses a spreadsheet formula hiding in a value someone typed.
 *
 * @param mixed $value Cell value.
 * @return string
 */
function zandi_students_csv_cell( $value ) {
	$value = (string) $value;

	if ( '' === $value ) {
		return '';
	}

	return false !== strpos( "=+-@\t\r", $value[0] ) ? "'" . $value : $value;
}
