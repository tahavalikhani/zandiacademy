<?php
/**
 * Zandi Academy theme setup.
 *
 * A classic PHP theme: no build step, no bundler, no npm. Styles are plain CSS
 * and the only JavaScript is one small progressive-enhancement file.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

define( 'ZANDI_VERSION', '1.0.0' );

require_once get_theme_file_path( 'inc/content.php' );
require_once get_theme_file_path( 'inc/courses.php' );
require_once get_theme_file_path( 'inc/icons.php' );
require_once get_theme_file_path( 'inc/template-tags.php' );

/**
 * Theme supports, menus and image sizes.
 *
 * @return void
 */
function zandi_setup() {
	load_theme_textdomain( 'zandi', get_theme_file_path( 'languages' ) );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 40,
			'width'       => 160,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => 'منوی اصلی',
			'footer'  => 'منوی فوتر',
		)
	);

	// The editor should show text the same way the front end does.
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'zandi_setup' );

/**
 * Content width used by oEmbeds and wide images.
 *
 * @return void
 */
function zandi_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'zandi_content_width', 736 );
}
add_action( 'after_setup_theme', 'zandi_content_width', 0 );

/**
 * Enqueues styles and scripts.
 *
 * `style.css` is direction-neutral wherever CSS logical properties allow it;
 * `rtl.css` carries only what cannot be expressed logically (gradient angles,
 * physical transforms) and loads on top when the locale is right-to-left.
 *
 * @return void
 */
function zandi_enqueue_assets() {
	wp_enqueue_style( 'zandi-style', get_stylesheet_uri(), array(), ZANDI_VERSION );

	if ( is_rtl() ) {
		wp_enqueue_style(
			'zandi-rtl',
			get_theme_file_uri( 'rtl.css' ),
			array( 'zandi-style' ),
			ZANDI_VERSION
		);
	}

	wp_enqueue_script(
		'zandi-theme',
		get_theme_file_uri( 'assets/js/theme.js' ),
		array(),
		ZANDI_VERSION,
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'zandi_enqueue_assets' );

/* =========================================================================
 * Persian typeface
 *
 * Vazirmatn ships with the theme (SIL Open Font Licence, free to redistribute).
 *
 * Peyda is the preferred face but is commercial software sold exclusively by
 * fontiran.com, so it cannot be committed here. The theme detects the licensed
 * files in assets/fonts/peyda/ and switches over automatically — see the README
 * in that folder.
 * ====================================================================== */

/**
 * The Peyda weights the theme will use, mapped to their CSS weight.
 *
 * @return array<string,int>
 */
function zandi_peyda_weights() {
	return array(
		'Peyda-Regular'  => 400,
		'Peyda-Medium'   => 500,
		'Peyda-SemiBold' => 600,
		'Peyda-Bold'     => 700,
	);
}

/**
 * Which Peyda files are actually present.
 *
 * Returns the variable font when it exists, otherwise whichever static weights
 * have been dropped in. An empty array means Peyda is not installed.
 *
 * @return array{variable?:string,static?:array<string,int>}
 */
function zandi_peyda_files() {
	if ( ! apply_filters( 'zandi_use_peyda', true ) ) {
		return array();
	}

	static $found = null;

	if ( null !== $found ) {
		return $found;
	}

	$found = array();
	$dir   = 'assets/fonts/peyda/';

	if ( file_exists( get_theme_file_path( $dir . 'Peyda-Variable.woff2' ) ) ) {
		$found = array( 'variable' => $dir . 'Peyda-Variable.woff2' );

		return $found;
	}

	$static = array();

	foreach ( zandi_peyda_weights() as $file => $weight ) {
		if ( file_exists( get_theme_file_path( $dir . $file . '.woff2' ) ) ) {
			$static[ $dir . $file . '.woff2' ] = $weight;
		}
	}

	if ( $static ) {
		$found = array( 'static' => $static );
	}

	return $found;
}

/**
 * Whether Peyda is installed and should be used.
 *
 * @return bool
 */
function zandi_has_peyda() {
	return (bool) zandi_peyda_files();
}

/**
 * Declares Peyda and promotes it to the body face.
 *
 * Emitted inline and only when the files exist — declaring an @font-face for a
 * missing file would cost a 404 on every page load. Vazirmatn stays in the
 * stack behind it, so a weight Peyda does not cover never drops to a system
 * font.
 *
 * @return void
 */
function zandi_peyda_styles() {
	$files = zandi_peyda_files();

	if ( ! $files ) {
		return;
	}

	$faces = '';

	if ( isset( $files['variable'] ) ) {
		$faces = sprintf(
			"@font-face{font-family:'Peyda';src:url('%s') format('woff2-variations');font-weight:100 900;font-style:normal;font-display:swap}",
			esc_url( get_theme_file_uri( $files['variable'] ) )
		);
	} else {
		foreach ( $files['static'] as $path => $weight ) {
			$faces .= sprintf(
				"@font-face{font-family:'Peyda';src:url('%s') format('woff2');font-weight:%d;font-style:normal;font-display:swap}",
				esc_url( get_theme_file_uri( $path ) ),
				(int) $weight
			);
		}
	}

	printf(
		"<style id=\"zandi-peyda\">%s:root{--font-persian:'Peyda','Vazirmatn','IRANSansX','IRANSans',Tahoma,system-ui,sans-serif}</style>\n",
		$faces // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URLs escaped above, rest is fixed markup.
	);
}
add_action( 'wp_head', 'zandi_peyda_styles', 2 );

/**
 * Preloads the variable font.
 *
 * One file covers weights 100–900, so a single preload removes the webfont from
 * the critical path without the usual per-weight request fan-out.
 *
 * @return void
 */
function zandi_preload_font() {
	$files = zandi_peyda_files();

	// Preload the face the page will actually render in. With static weights,
	// only Regular is preloaded — preloading four files would cost more than it
	// saves.
	if ( isset( $files['variable'] ) ) {
		$preload = $files['variable'];
	} elseif ( isset( $files['static'] ) ) {
		$regular = array_search( 400, $files['static'], true );
		$preload = $regular ? $regular : key( $files['static'] );
	} else {
		$preload = 'assets/fonts/Vazirmatn-Variable.woff2';
	}

	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( get_theme_file_uri( $preload ) )
	);
}
add_action( 'wp_head', 'zandi_preload_font', 1 );

/**
 * Adds the theme colour and favicon when no site icon is set.
 *
 * @return void
 */
function zandi_meta_tags() {
	echo '<meta name="theme-color" content="#1B365D">' . "\n";

	if ( ! has_site_icon() ) {
		printf(
			'<link rel="icon" type="image/svg+xml" href="%s">' . "\n",
			esc_url( get_theme_file_uri( 'assets/favicon.svg' ) )
		);
	}
}
add_action( 'wp_head', 'zandi_meta_tags', 2 );

/**
 * Widget areas.
 *
 * @return void
 */
function zandi_widgets_init() {
	register_sidebar(
		array(
			'name'          => 'ستون کناری',
			'id'            => 'sidebar-1',
			'description'   => 'ابزارک‌های نمایش‌داده‌شده در کنار نوشته‌ها و برگه‌ها.',
			'before_widget' => '<section id="%1$s" class="widget card %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'zandi_widgets_init' );

/**
 * Nav menu walker that mirrors the homepage link styling.
 *
 * Core's markup is kept; this only trims the class soup to what the stylesheet
 * actually targets.
 */
class Zandi_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Starts the element output.
	 *
	 * @param string   $output Passed by reference. Used to append additional content.
	 * @param WP_Post  $item   Menu item data object.
	 * @param int      $depth  Depth of menu item.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 * @param int      $id     Current item ID.
	 * @return void
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = array( 'menu-item' );

		if ( in_array( 'current-menu-item', (array) $item->classes, true ) ) {
			$classes[] = 'is-active';
		}

		$output .= sprintf( '<li class="%s">', esc_attr( implode( ' ', $classes ) ) );
		$output .= sprintf(
			'<a href="%1$s">%2$s%3$s</a>',
			esc_url( $item->url ),
			esc_html( $item->title ),
			isset( $args->link_after ) ? $args->link_after : ''
		);
	}

	/**
	 * Ends the element output.
	 *
	 * @param string   $output Passed by reference. Used to append additional content.
	 * @param WP_Post  $item   Menu item data object.
	 * @param int      $depth  Depth of menu item.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 * @return void
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

/**
 * Renders the primary navigation, falling back to the on-page anchors when no
 * menu has been assigned yet — so a fresh install still looks finished.
 *
 * @param string $context 'desktop'|'mobile'.
 * @return void
 */
function zandi_primary_nav( $context = 'desktop' ) {
	$is_mobile   = 'mobile' === $context;
	$list_class  = $is_mobile ? 'menu-mobile__list' : 'menu-desktop__list';
	$link_after  = $is_mobile ? zandi_get_icon( zandi_arrow_forward() ) : '';

	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => '',
				'menu_class'     => $list_class,
				'depth'          => 1,
				'walker'         => new Zandi_Nav_Walker(),
				'link_after'     => $link_after,
			)
		);
		return;
	}

	printf( '<ul class="%s">', esc_attr( $list_class ) );

	foreach ( zandi_fallback_nav() as $item ) {
		printf(
			'<li class="menu-item" data-target="%1$s"><a href="%2$s">%3$s%4$s</a></li>',
			esc_attr( ltrim( $item['url'], '#' ) ),
			esc_url( zandi_resolve_anchor( $item['url'] ) ),
			esc_html( $item['label'] ),
			$link_after // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon registry.
		);
	}

	echo '</ul>';
}

/**
 * Resolves an on-page anchor against the front page.
 *
 * The homepage sections are anchors, so `#courses` must become
 * `https://example.com/#courses` on any page that is not the front page —
 * otherwise the link does nothing away from home.
 *
 * @param string $url Anchor or absolute URL.
 * @return string
 */
function zandi_resolve_anchor( $url ) {
	if ( 0 !== strpos( $url, '#' ) ) {
		return $url;
	}

	if ( is_front_page() ) {
		return $url;
	}

	return home_url( '/' ) . $url;
}

/**
 * Trims the automatic excerpt to a length that suits the card layout.
 *
 * @param int $length Default length in words.
 * @return int
 */
function zandi_excerpt_length( $length ) {
	return is_admin() ? $length : 28;
}
add_filter( 'excerpt_length', 'zandi_excerpt_length' );

/**
 * Replaces the excerpt ellipsis with a Persian-friendly one.
 *
 * @param string $more The "read more" string.
 * @return string
 */
function zandi_excerpt_more( $more ) {
	return is_admin() ? $more : '…';
}
add_filter( 'excerpt_more', 'zandi_excerpt_more' );

/* =========================================================================
 * Course landing pages — /courses/{slug}
 *
 * One rewrite rule and one template serve every course. Adding a fourth course
 * is a single entry in inc/courses.php; no new route, template or file.
 * ====================================================================== */

/**
 * Registers the /courses/{slug} route.
 *
 * @return void
 */
function zandi_course_rewrite() {
	add_rewrite_tag( '%zandi_course%', '([^&/]+)' );
	add_rewrite_rule( '^courses/([^/]+)/?$', 'index.php?zandi_course=$matches[1]', 'top' );
}
add_action( 'init', 'zandi_course_rewrite' );

/**
 * Flushes rewrite rules once when the theme is activated.
 *
 * Without this the course URLs 404 until permalinks are re-saved by hand.
 *
 * @return void
 */
function zandi_flush_rewrites() {
	zandi_course_rewrite();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'zandi_flush_rewrites' );

/**
 * The course requested by the current URL, if any.
 *
 * @return array<string,mixed>|null
 */
function zandi_current_course() {
	$slug = get_query_var( 'zandi_course' );

	return $slug ? zandi_get_course( sanitize_key( $slug ) ) : null;
}

/**
 * Routes /courses/{slug} to the course template, and unknown slugs to a 404.
 *
 * @param string $template Template path chosen by WordPress.
 * @return string
 */
function zandi_course_template( $template ) {
	if ( ! get_query_var( 'zandi_course' ) ) {
		return $template;
	}

	if ( ! zandi_current_course() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

		return get_query_template( '404' );
	}

	// A course page is a real page, not the blog index.
	global $wp_query;
	$wp_query->is_home     = false;
	$wp_query->is_singular = true;

	return get_theme_file_path( 'template-course.php' );
}
add_filter( 'template_include', 'zandi_course_template' );

/**
 * Title, description and Open Graph tags for a course page.
 *
 * @return void
 */
function zandi_course_head() {
	$course = zandi_current_course();

	if ( ! $course ) {
		return;
	}

	$site  = zandi_site();
	$title = sprintf( '%s | %s', $course['short_name'], $site['name'] );
	$url   = home_url( '/courses/' . $course['slug'] . '/' );

	printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $course['meta_description'] ) );
	printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $url ) );

	printf( "<meta property=\"og:type\" content=\"website\">\n" );
	printf( "<meta property=\"og:locale\" content=\"fa_IR\">\n" );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( $site['name'] ) );
	printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $title ) );
	printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $course['meta_description'] ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $url ) );

	printf( "<meta name=\"twitter:card\" content=\"summary_large_image\">\n" );
	printf( "<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr( $title ) );
	printf( "<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr( $course['meta_description'] ) );

	// TODO: og:image needs a real 1200×630 share card per course.
}
add_action( 'wp_head', 'zandi_course_head', 3 );

/**
 * Sets the browser title on course pages.
 *
 * @param array $parts Title parts.
 * @return array
 */
function zandi_course_title( $parts ) {
	$course = zandi_current_course();

	if ( $course ) {
		$parts['title'] = $course['short_name'];
	}

	return $parts;
}
add_filter( 'document_title_parts', 'zandi_course_title' );

/**
 * Loads the course stylesheet and fonts, on course pages only.
 *
 * The course pages use their own palette and a Latin display face, neither of
 * which the rest of the site needs — so neither is paid for elsewhere.
 *
 * @return void
 */
function zandi_course_assets() {
	if ( ! zandi_current_course() ) {
		return;
	}

	wp_enqueue_style(
		'zandi-courses',
		get_theme_file_uri( 'assets/css/courses.css' ),
		array( 'zandi-style' ),
		ZANDI_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'zandi_course_assets', 20 );

/**
 * Preloads the Latin display face on course pages.
 *
 * Playfair Display is self-hosted rather than pulled from Google Fonts, which
 * is blocked in Iran — a blocked font request does not fail silently, it stalls
 * the page.
 *
 * @return void
 */
function zandi_course_preload_font() {
	if ( ! zandi_current_course() ) {
		return;
	}

	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( get_theme_file_uri( 'assets/fonts/PlayfairDisplay-Variable.woff2' ) )
	);
}
add_action( 'wp_head', 'zandi_course_preload_font', 1 );

/**
 * Adds a body class on course pages.
 *
 * The course palette is scoped under `.course-page` so it never leaks into the
 * rest of the site.
 *
 * @param array $classes Body classes.
 * @return array
 */
function zandi_course_body_class( $classes ) {
	$course = zandi_current_course();

	if ( $course ) {
		$classes[] = 'course-page';
		$classes[] = 'course-page--' . $course['slug'];
	}

	return $classes;
}
add_filter( 'body_class', 'zandi_course_body_class' );

/**
 * Handles the "notify me" email capture on upcoming-course cards.
 *
 * Placeholder: validates and redirects, storing nothing yet.
 *
 * TODO: connect to a mailing list once one exists.
 *
 * @return void
 */
function zandi_handle_notify() {
	$nonce = isset( $_POST['zandi_notify_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_notify_nonce'] ) ) : '';
	$back  = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	if ( ! wp_verify_nonce( $nonce, 'zandi_notify' ) ) {
		wp_safe_redirect( add_query_arg( 'notify', 'error', $back ) . '#other-courses' );
		exit;
	}

	$email = isset( $_POST['zandi_email'] ) ? sanitize_email( wp_unslash( $_POST['zandi_email'] ) ) : '';

	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'notify', 'invalid', $back ) . '#other-courses' );
		exit;
	}

	/**
	 * Fires when a visitor asks to be told about an upcoming course.
	 *
	 * @param string $email Subscriber email.
	 */
	do_action( 'zandi_notify_requested', $email );

	wp_safe_redirect( add_query_arg( 'notify', 'ok', $back ) . '#other-courses' );
	exit;
}
add_action( 'admin_post_nopriv_zandi_notify', 'zandi_handle_notify' );
add_action( 'admin_post_zandi_notify', 'zandi_handle_notify' );

/**
 * Handles the course enrolment button.
 *
 * Placeholder: the payment gateway is not connected yet. ZarinPal is the chosen
 * gateway — see docs/wordpress-iran-stack.md — but there is no merchant account
 * yet, so this records intent and returns the visitor to the page.
 *
 * TODO: replace with the real WooCommerce/ZarinPal checkout handoff.
 *
 * @return void
 */
function zandi_handle_enrol() {
	$nonce = isset( $_POST['zandi_enrol_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_enrol_nonce'] ) ) : '';
	$slug  = isset( $_POST['course'] ) ? sanitize_key( wp_unslash( $_POST['course'] ) ) : '';
	$back  = zandi_get_course( $slug ) ? home_url( '/courses/' . $slug . '/' ) : home_url( '/' );

	if ( ! wp_verify_nonce( $nonce, 'zandi_enrol' ) ) {
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Fires when a visitor clicks enrol, before any payment exists.
	 *
	 * @param string $slug Course slug.
	 */
	do_action( 'zandi_enrol_requested', $slug );

	wp_safe_redirect( add_query_arg( 'enrol', 'pending', $back ) . '#register' );
	exit;
}
add_action( 'admin_post_nopriv_zandi_enrol', 'zandi_handle_enrol' );
add_action( 'admin_post_zandi_enrol', 'zandi_handle_enrol' );

/**
 * Handles the consultation booking form.
 *
 * This is the theme's only network seam. It validates and fires an action, then
 * redirects back with a flag so the page can confirm without JavaScript. Hook
 * `zandi_booking_submitted` to send an email, call a CRM or hand off to a form
 * plugin.
 *
 * @return void
 */
function zandi_handle_booking() {
	$nonce = isset( $_POST['zandi_booking_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_booking_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'zandi_booking' ) ) {
		wp_safe_redirect( add_query_arg( 'booking', 'error', home_url( '/' ) ) . '#register' );
		exit;
	}

	$name  = isset( $_POST['zandi_name'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_name'] ) ) : '';
	$phone = isset( $_POST['zandi_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['zandi_phone'] ) ) : '';

	if ( '' === $name || '' === $phone ) {
		wp_safe_redirect( add_query_arg( 'booking', 'invalid', home_url( '/' ) ) . '#register' );
		exit;
	}

	/**
	 * Fires after a valid consultation booking is received.
	 *
	 * @param string $name  Applicant name.
	 * @param string $phone Applicant phone number.
	 */
	do_action( 'zandi_booking_submitted', $name, $phone );

	wp_safe_redirect( add_query_arg( 'booking', 'ok', home_url( '/' ) ) . '#register' );
	exit;
}
add_action( 'admin_post_nopriv_zandi_booking', 'zandi_handle_booking' );
add_action( 'admin_post_zandi_booking', 'zandi_handle_booking' );

/**
 * Whether the booking form should render its confirmation state.
 *
 * @return bool
 */
function zandi_booking_confirmed() {
	return isset( $_GET['booking'] ) && 'ok' === $_GET['booking']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag.
}

/**
 * Adds a body class while the front page is being displayed, so the header can
 * run its scroll-spy only where on-page anchors exist.
 *
 * @param array $classes Body classes.
 * @return array
 */
function zandi_body_classes( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'has-anchor-nav';
	}

	return $classes;
}
add_filter( 'body_class', 'zandi_body_classes' );
