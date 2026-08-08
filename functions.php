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

define( 'ZANDI_VERSION', '1.2.0' );

/*
 * Bumped whenever a rewrite rule changes, so zandi_maybe_flush_rewrites() knows
 * to re-register the routes. Without this, updating the theme over git leaves
 * stale rules in the database and every custom URL 404s.
 */
define( 'ZANDI_ROUTES_VERSION', '4' );

/**
 * A cache-busting version string for one asset, from its own timestamp.
 *
 * ZANDI_VERSION alone is not enough and the failure is quiet. Every stylesheet
 * was enqueued as `?ver=1.1.0`, and that constant sat unchanged across a long
 * run of commits — so browsers, the host's cache and any CDN in front of it all
 * kept serving whichever copy of style.css they had first seen. Templates are
 * PHP and updated instantly, so a deploy looked half-applied: new copy on the
 * page, old layout around it, and nothing to point at.
 *
 * Appending the file's own mtime means the URL changes exactly when the file
 * does, and nobody has to remember to bump anything.
 *
 * @param string $relative_path Path within the theme, e.g. 'assets/css/panel.css'.
 * @return string
 */
function zandi_asset_version( $relative_path = '' ) {
	if ( '' === $relative_path ) {
		return ZANDI_VERSION;
	}

	$path = get_theme_file_path( $relative_path );

	// A missing file is not worth a fatal; fall back to the constant.
	if ( ! file_exists( $path ) ) {
		return ZANDI_VERSION;
	}

	return ZANDI_VERSION . '.' . (string) filemtime( $path );
}

/**
 * A theme file's URL, versioned the same way.
 *
 * wp_enqueue_* takes the version separately, so stylesheets and scripts use
 * zandi_asset_version() directly. Anything printed as a bare `href` — a
 * favicon, a touch icon — has nowhere to put it, so it goes in the query
 * string here.
 *
 * Favicons are the worst offender for this: browsers cache them far longer and
 * far more stubbornly than a stylesheet, and a stale one survives a hard
 * refresh. Replacing the mark without changing the URL means the old icon sits
 * in the tab for weeks.
 *
 * @param string $relative_path Path within the theme, e.g. 'assets/favicon.svg'.
 * @return string
 */
function zandi_asset_uri( $relative_path ) {
	return add_query_arg( 'ver', zandi_asset_version( $relative_path ), get_theme_file_uri( $relative_path ) );
}

require_once get_theme_file_path( 'inc/content.php' );
require_once get_theme_file_path( 'inc/courses.php' );
require_once get_theme_file_path( 'inc/panel.php' );
require_once get_theme_file_path( 'inc/icons.php' );
require_once get_theme_file_path( 'inc/template-tags.php' );
require_once get_theme_file_path( 'inc/auth.php' );
require_once get_theme_file_path( 'inc/performance.php' );

/*
 * Loaded unconditionally; the file returns early on its own if WooCommerce is
 * not active, so the theme runs identically with the plugin off.
 */
require_once get_theme_file_path( 'inc/woocommerce.php' );

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
	wp_enqueue_style( 'zandi-style', get_stylesheet_uri(), array(), zandi_asset_version( 'style.css' ) );

	/*
	 * Always loaded. This academy publishes only in Persian, so the layout is
	 * right-to-left whatever the WordPress locale happens to be set to — relying
	 * on is_rtl() meant an install left on en_US rendered the whole site
	 * left-to-right, with icons and list markers on the wrong side.
	 */
	wp_enqueue_style(
		'zandi-rtl',
		get_theme_file_uri( 'rtl.css' ),
		array( 'zandi-style' ),
		zandi_asset_version( 'rtl.css' )
	);

	// Must come after both stylesheets so the Peyda :root override wins.
	$zandi_peyda = zandi_peyda_styles();

	if ( $zandi_peyda ) {
		wp_add_inline_style( 'zandi-rtl', $zandi_peyda );
	}

	/*
	 * Deferred, not just footer-placed. Nothing on the page depends on
	 * JavaScript — the theme ships `no-js` and swaps it before first paint — so
	 * parsing this can wait until the document is done.
	 *
	 * The array form needs WP 6.3+; older versions read it as truthy and simply
	 * keep the script in the footer, which is what it did before.
	 */
	wp_enqueue_script(
		'zandi-theme',
		get_theme_file_uri( 'assets/js/theme.js' ),
		array(),
		zandi_asset_version( 'assets/js/theme.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'zandi_enqueue_assets' );

/**
 * Guarantees dir="rtl" on the <html> element.
 *
 * language_attributes() only emits a direction when is_rtl() is true, which
 * tracks the WordPress site language. This site is Persian-only, so the
 * direction is a property of the theme, not of a setting an admin might change.
 *
 * @param string $output The language attributes string.
 * @return string
 */
function zandi_force_rtl_attribute( $output ) {
	if ( false === strpos( $output, 'dir=' ) ) {
		$output .= ' dir="rtl"';
	}

	return $output;
}
add_filter( 'language_attributes', 'zandi_force_rtl_attribute' );

/**
 * The theme's own direction test.
 *
 * Used instead of is_rtl() wherever the theme picks a direction-dependent asset
 * (arrow glyphs, mainly), so those stay correct on a non-Persian install.
 *
 * @return bool
 */
function zandi_is_rtl() {
	return (bool) apply_filters( 'zandi_is_rtl', true );
}

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
 * CSS weight to candidate filenames, in priority order.
 *
 * fontiran ships the web build as `PeydaWeb-*`; the desktop build is `Peyda-*`.
 * Both names are accepted so the files can be dropped in unrenamed.
 *
 * Use the **Font Family** web build, not `PeydaFaNum`: that variant substitutes
 * Persian digits for Latin ones in the font itself, which would corrupt CEFR
 * codes like A1/B2 — the same trap as Vazirmatn's `ss01`, which this theme
 * deliberately disables.
 *
 * Only the weights the stylesheets actually ask for are declared. style.css and
 * courses.css use 400, 500, 600 and 700, and CSS weight matching resolves 500
 * down to 400 — so ExtraLight (200) and Black (900) were never fetched by any
 * browser. Declaring them cost two dead @font-face blocks in the inline CSS on
 * every page. Add a line back here if the design starts using that weight; the
 * files are already in assets/fonts/peyda/.
 *
 * @return array<int,array<int,string>>
 */
function zandi_peyda_weights() {
	return array(
		400 => array( 'PeydaWeb-Regular', 'Peyda-Regular' ),
		600 => array( 'PeydaWeb-SemiBold', 'Peyda-SemiBold' ),
		700 => array( 'PeydaWeb-Bold', 'Peyda-Bold' ),
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

	foreach ( zandi_peyda_weights() as $weight => $candidates ) {
		foreach ( $candidates as $file ) {
			if ( file_exists( get_theme_file_path( $dir . $file . '.woff2' ) ) ) {
				$static[ $dir . $file . '.woff2' ] = $weight;
				break;
			}
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
 * Attached to `zandi-style` with wp_add_inline_style() rather than printed on
 * wp_head. WordPress prints enqueued stylesheets on wp_head at priority 8, so
 * an earlier hook put this block *above* style.css — and style.css sets
 * --font-persian to Vazirmatn in :root, at equal specificity, so it won.
 * The site quietly stayed on Vazirmatn. Inline styles registered against a
 * handle always print immediately after that handle's own tag.
 *
 * Declared only when the files exist — an @font-face for a missing file would
 * cost a 404 on every page load. Vazirmatn stays in the stack behind Peyda so a
 * weight Peyda does not cover never drops to a system font.
 *
 * @return string Inline CSS, or '' when Peyda is not installed.
 */
function zandi_peyda_styles() {
	$files = zandi_peyda_files();

	if ( ! $files ) {
		return '';
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

	/*
	 * The emoji families sit between Peyda and Vazirmatn deliberately. Peyda has
	 * no emoji glyphs, so without them a single 🇫🇷 in the copy makes the browser
	 * fetch the whole 108 KB Vazirmatn file looking for one — and Vazirmatn does
	 * not have it either.
	 */
	return $faces . ":root{--font-persian:'Peyda',var(--font-emoji),'Vazirmatn','IRANSansX','IRANSans',Tahoma,system-ui,sans-serif}";
}

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
 * Three files, which is the whole set: `.ico` for the browsers that still ask
 * for one and for a bare /favicon.ico request, `.svg` for everything modern —
 * one file at any size — and the 180px PNG iOS wants when a student saves the
 * site to their home screen. The pack's android-chrome PNGs are not installed:
 * they are only read through a web app manifest, and the theme ships none, so
 * they would be two files nobody ever requests.
 *
 * The `has_site_icon()` guard stays. A Site Icon set in
 * ظاهر ← سفارشی‌سازی ← هویت سایت makes WordPress print its own tags, and two
 * competing sets is how a browser ends up showing the old mark from cache.
 *
 * @return void
 */
function zandi_meta_tags() {
	echo '<meta name="theme-color" content="#1B365D">' . "\n";

	if ( has_site_icon() ) {
		return;
	}

	printf(
		'<link rel="icon" href="%s" sizes="any">' . "\n",
		esc_url( zandi_asset_uri( 'assets/favicon.ico' ) )
	);

	printf(
		'<link rel="icon" type="image/svg+xml" href="%s">' . "\n",
		esc_url( zandi_asset_uri( 'assets/favicon.svg' ) )
	);

	printf(
		'<link rel="apple-touch-icon" href="%s">' . "\n",
		esc_url( zandi_asset_uri( 'assets/apple-touch-icon.png' ) )
	);
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

	$current = zandi_current_nav_url();

	foreach ( zandi_fallback_nav() as $item ) {
		$url     = zandi_resolve_anchor( $item['url'] );
		$classes = 'menu-item' . ( $current && untrailingslashit( $url ) === $current ? ' is-active' : '' );

		printf(
			'<li class="%1$s"><a href="%2$s">%3$s%4$s</a></li>',
			esc_attr( $classes ),
			esc_url( $url ),
			esc_html( $item['label'] ),
			$link_after // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon registry.
		);
	}

	echo '</ul>';
}

/**
 * The nav URL matching the page being viewed, for marking the active item.
 *
 * wp_nav_menu() gets this from core as `current-menu-item`; the fallback nav has
 * to work it out, and a course page counts as being under دوره‌ها.
 *
 * @return string Untrailingslashed URL, or '' when nothing should be marked.
 */
function zandi_current_nav_url() {
	if ( zandi_current_course() ) {
		return untrailingslashit( zandi_section_url( 'courses' ) );
	}

	$section = zandi_current_section();

	if ( $section ) {
		return untrailingslashit( zandi_section_url( $section['slug'] ) );
	}

	if ( is_front_page() ) {
		return untrailingslashit( home_url( '/' ) );
	}

	return '';
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
 * Where the header's primary action should point.
 *
 * On a course page the enrol form is on the page already, so sending the visitor
 * to the homepage's booking section would walk them away from the thing they
 * came to buy.
 *
 * @return string
 */
function zandi_header_cta_url() {
	// Someone already signed in has no use for a signup link.
	if ( is_user_logged_in() ) {
		return zandi_panel_url();
	}

	// On a course page the CTA buys a course, which is a different action.
	if ( zandi_current_course() ) {
		return '#enrol';
	}

	/*
	 * A button labelled «ثبت نام» should reach the page that creates an account.
	 * It used to scroll to the homepage's closing CTA section, which was right
	 * while that section held the booking form and wrong the moment real
	 * accounts existed.
	 */
	return zandi_register_url();
}

/**
 * The label on the header's primary action.
 *
 * @return string
 */
function zandi_header_cta_label() {
	if ( is_user_logged_in() ) {
		return 'پنل من';
	}

	return 'ثبت نام';
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
 * Routing
 *
 * Two families of URL, both served from theme data with no wp-admin setup:
 *
 *   /courses/{slug}   one template for every course (inc/courses.php)
 *   /{section}/       standalone pages for the nav sections (zandi_sections())
 *
 * Adding a fourth course is a single entry in inc/courses.php — no new route,
 * template or file.
 * ====================================================================== */

/**
 * Registers every rewrite rule the theme owns.
 *
 * The course rule is registered first and is more specific than the section
 * rule, so `/courses/a1` cannot be swallowed by `/courses`.
 *
 * @return void
 */
function zandi_register_routes() {
	$sections = implode( '|', array_keys( zandi_sections() ) );

	/*
	 * Both go in at 'top', and each 'top' insertion goes to the front — so the
	 * *last* one registered ends up highest. The course rule is registered last
	 * on purpose, so /courses/a1 is tested before the bare-section rule.
	 *
	 * No leading ^ on either pattern: core interpolates the rule into "#^$match#"
	 * itself, and "^^courses/…" is a pattern nobody should have to read twice.
	 */
	$accounts = implode( '|', zandi_account_routes() );

	add_rewrite_rule( '(' . $sections . ')/?$', 'index.php?zandi_section=$matches[1]', 'top' );
	add_rewrite_rule( '(' . $accounts . ')/?$', 'index.php?zandi_account=$matches[1]', 'top' );
	add_rewrite_rule( 'courses/([^/]+)/?$', 'index.php?zandi_course=$matches[1]', 'top' );
}
add_action( 'init', 'zandi_register_routes' );

/**
 * Whitelists the theme's query vars.
 *
 * The `query_vars` filter is the documented way to do this. add_rewrite_tag()
 * was used here before, which registers the var but *also* adds the tag to
 * WP_Rewrite's permastruct token list, where nothing uses it — a side effect
 * this theme has no reason to buy.
 *
 * @param string[] $vars Recognised public query vars.
 * @return string[]
 */
function zandi_query_vars( $vars ) {
	$vars[] = 'zandi_course';
	$vars[] = 'zandi_section';
	$vars[] = 'zandi_account';

	return $vars;
}
add_filter( 'query_vars', 'zandi_query_vars' );

/**
 * Resolves the theme's URLs without relying on the rewrite rules in the database.
 *
 * Rewrite rules are the fast path, but they are also the fragile one: they live
 * in an option, they are wiped by other plugins flushing, and they do nothing at
 * all when Settings → Permalinks is left on «ساده» (plain), where WordPress has
 * no rules to match and every pretty URL 404s. Reading the path here means
 * /courses/a1 resolves on a stock install with no admin step and no flush.
 *
 * Runs on parse_request, before the main query is built, so WP_Query sees the
 * query var as though a rewrite rule had produced it.
 *
 * @param WP $wp Current WordPress environment instance.
 * @return void
 */
function zandi_parse_request( $wp ) {
	if ( isset( $wp->query_vars['zandi_course'] )
		|| isset( $wp->query_vars['zandi_section'] )
		|| isset( $wp->query_vars['zandi_account'] ) ) {
		return; // A rewrite rule already matched.
	}

	$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );
	$path = trim( $path, '/' );

	// Subdirectory installs carry a prefix that is not part of the route.
	$base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

	if ( '' !== $base && 0 === strpos( $path, $base ) ) {
		$path = trim( substr( $path, strlen( $base ) ), '/' );
	}

	if ( '' === $path ) {
		return;
	}

	if ( preg_match( '#^courses/([^/]+)$#', $path, $matches ) ) {
		$wp->query_vars['zandi_course'] = sanitize_key( $matches[1] );

		return;
	}

	$slug = sanitize_key( $path );

	/*
	 * Account routes are matched before sections and are never shadowed by a
	 * published Page: /login/ has to reach the form even if someone has made a
	 * page called «ورود», because the alternative is a student who cannot sign
	 * in and no obvious reason why.
	 */
	if ( in_array( $slug, zandi_account_routes(), true ) ) {
		$wp->query_vars['zandi_account'] = $slug;

		return;
	}

	$sections = zandi_sections();

	if ( ! isset( $sections[ $slug ] ) ) {
		return;
	}

	/*
	 * A real Page always wins. If the owner ever publishes a page at /contact/,
	 * that is the page they meant people to see — the theme has no business
	 * shadowing it, and silently doing so would be near-impossible to debug.
	 */
	if ( get_page_by_path( $slug ) ) {
		return;
	}

	$wp->query_vars['zandi_section'] = $slug;
}
add_action( 'parse_request', 'zandi_parse_request' );

/**
 * Serves the theme's virtual pages as a plain 200.
 *
 * These URLs carry no post, so the main query resolves to the blog index and
 * WordPress may mark it a 404 when the blog is empty. Runs on `wp`, after
 * WP::handle_404() has had its say, so the status set here is the one that
 * ships.
 *
 * Note what this deliberately does *not* do: claim is_singular. An earlier
 * version did, and core's body_class() reads $wp_query->get_queried_object()
 * ->post_type on a singular query — null here, so every section page emitted a
 * PHP warning.
 *
 * @return void
 */
function zandi_prepare_virtual_page() {
	if ( ! zandi_current_course() && ! zandi_current_section() && ! zandi_account_route() ) {
		return;
	}

	global $wp_query;

	$wp_query->is_home = false;
	$wp_query->is_404  = false;

	status_header( 200 );
}
add_action( 'wp', 'zandi_prepare_virtual_page' );

/**
 * Keeps redirect_canonical() away from the virtual pages.
 *
 * The main query looks like the blog index to core, so canonical redirection
 * would happily bounce /courses/a1 to the site root.
 *
 * @param string $redirect_url  Proposed redirect.
 * @param string $requested_url Requested URL.
 * @return string|false
 */
function zandi_block_canonical_redirect( $redirect_url, $requested_url = '' ) {
	if ( zandi_current_course() || zandi_current_section() || zandi_account_route() ) {
		return false;
	}

	return $redirect_url;
}
add_filter( 'redirect_canonical', 'zandi_block_canonical_redirect', 10, 2 );

/**
 * Flushes rewrite rules whenever the theme's routes change.
 *
 * `after_switch_theme` only fires on activation, so a theme updated in place —
 * over git, or by uploading files — kept serving the old rules and every custom
 * URL returned 404 until an admin re-saved the permalink settings by hand. This
 * compares a stored version against ZANDI_ROUTES_VERSION and re-registers when
 * they differ, which is the one flush that actually happens in practice.
 *
 * flush_rewrite_rules() is expensive, so it runs only on an actual mismatch.
 *
 * @return void
 */
function zandi_maybe_flush_routes() {
	if ( get_option( 'zandi_routes_version' ) === ZANDI_ROUTES_VERSION ) {
		return;
	}

	flush_rewrite_rules();
	update_option( 'zandi_routes_version', ZANDI_ROUTES_VERSION );
}
add_action( 'init', 'zandi_maybe_flush_routes', 99 );

/**
 * Flushes on activation too, so a fresh install works on first load.
 *
 * @return void
 */
function zandi_flush_rewrites() {
	zandi_register_routes();
	flush_rewrite_rules();
	update_option( 'zandi_routes_version', ZANDI_ROUTES_VERSION );
}
add_action( 'after_switch_theme', 'zandi_flush_rewrites' );

/**
 * The standalone section pages, keyed by URL slug.
 *
 * Each one renders the homepage partials it owns, so there is a single source
 * for the content — the page and the landing-page section stay in step.
 *
 * @return array<string,array<string,mixed>>
 */
function zandi_sections() {
	return apply_filters(
		'zandi_sections',
		array(
			'courses' => array(
				'title'    => 'دوره‌ها',
				'lead'     => 'سه سطح، هر کدوم یه مسیر مشخص. مطمئن نیستی از کدوم شروع کنی؟ از صفحه تماس بپرس تا با هم پیداش کنیم.',
				'parts'    => array( 'courses', 'journey' ),
				'meta'     => 'دوره‌های زبان فرانسه آکادمی زندی؛ سطح پایه A1، متوسط A2 و پیشرفته B1 با تدریس شیما زندی از پاریس.',
			),
			'method'  => array(
				'title'    => 'روش تدریس',
				'lead'     => 'چهار چیزی که باعث می‌شه این‌بار وسط راه ولش نکنی.',
				'parts'    => array( 'features', 'journey' ),
				'meta'     => 'روش تدریس آکادمی زندی: مکالمه‌محور، فرانسه‌ای که واقعاً حرف زده می‌شه، با ریتم خودت و پشتیبانی ۲۴ ساعته.',
			),
			'about'   => array(
				'title'    => 'درباره من',
				'lead'     => '',
				'parts'    => array( 'teachers', 'stats' ),
				'meta'     => 'شیما زندی، مدرس زبان فرانسه و بنیان‌گذار آکادمی زندی، ساکن پاریس.',
			),
			'faq'     => array(
				'title'    => 'سوالات متداول',
				'lead'     => 'اگر جوابت اینجا نبود، از صفحه تماس بپرس. هر ساعتی از شبانه‌روز جواب می‌گیری.',
				'parts'    => array( 'faq' ),
				'meta'     => 'پاسخ سوال‌های پرتکرار درباره دوره‌های زبان فرانسه آکادمی زندی: سطح، مدت، دسترسی و ثبت‌نام.',
			),
			'contact' => array(
				'title'    => 'تماس',
				'lead'     => 'هر سوالی داری از همین‌جا بپرس. جواب می‌گیری، هر ساعتی از شبانه‌روز که باشه.',
				'parts'    => array( 'contact' ),
				'meta'     => 'راه‌های ارتباط با آکادمی زندی و پشتیبانی ۲۴ ساعته دوره‌های زبان فرانسه.',
			),
		)
	);
}

/**
 * The section requested by the current URL, if any.
 *
 * @return array<string,mixed>|null
 */
function zandi_current_section() {
	$slug     = get_query_var( 'zandi_section' );
	$sections = zandi_sections();

	if ( ! $slug ) {
		return null;
	}

	$slug = sanitize_key( $slug );

	if ( ! isset( $sections[ $slug ] ) ) {
		return null;
	}

	$section         = $sections[ $slug ];
	$section['slug'] = $slug;

	return $section;
}

/**
 * The canonical URL for a section, used by the navigation and footer.
 *
 * @param string $slug Section slug.
 * @return string
 */
function zandi_section_url( $slug ) {
	if ( ! zandi_pretty_permalinks() ) {
		return home_url( '/?zandi_section=' . $slug );
	}

	return home_url( '/' . $slug . '/' );
}

/**
 * The canonical URL for a course page.
 *
 * @param string $slug Course slug.
 * @return string
 */
function zandi_course_url( $slug ) {
	if ( ! zandi_pretty_permalinks() ) {
		return home_url( '/?zandi_course=' . $slug );
	}

	return home_url( '/courses/' . $slug . '/' );
}

/**
 * Whether the site is configured for pretty permalinks.
 *
 * This decides the shape of every URL the theme prints, and it is not a detail.
 * With Settings → پیوندهای یکتا left on «ساده», WordPress writes no rewrite
 * block to .htaccess and stores no rewrite rules — so a link to /courses/a1 is
 * answered by Apache or nginx looking for a directory of that name on disk and
 * returning its own 404. PHP never runs, no theme hook fires, and nothing in
 * this file can intervene. Printing ?zandi_course=a1 instead keeps every link
 * working on such an install.
 *
 * @return bool
 */
function zandi_pretty_permalinks() {
	return (bool) get_option( 'permalink_structure' );
}

/**
 * Tells the owner, in wp-admin, when permalinks are the reason URLs look wrong.
 *
 * Without this the failure is silent and deeply confusing: the links work, but
 * they are query strings rather than the tidy /courses/a1 the theme is built
 * around. The notice states the exact click path.
 *
 * @return void
 */
function zandi_permalink_notice() {
	if ( zandi_pretty_permalinks() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<strong>آکادمی زندی:</strong>
			ساختار پیوندهای یکتا روی «ساده» است، پس نشانی‌هایی مثل
			<code>/courses/a1</code> کار نمی‌کنند و وب‌سرور خودش خطای ۴۰۴ می‌دهد.
			قالب فعلاً لینک‌ها را به شکل <code>?zandi_course=a1</code> می‌سازد تا سایت از کار نیفتد.
		</p>
		<p>
			برای درست شدن: <strong>تنظیمات → پیوندهای یکتا</strong> را باز کنید،
			<strong>«نام نوشته»</strong> را انتخاب کنید و <strong>ذخیره تغییرات</strong> را بزنید.
			<a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>">همین حالا انجامش بده</a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'zandi_permalink_notice' );

/**
 * Routes /{section}/ to the section template.
 *
 * @param string $template Template path chosen by WordPress.
 * @return string
 */
function zandi_section_template( $template ) {
	if ( ! get_query_var( 'zandi_section' ) ) {
		return $template;
	}

	if ( ! zandi_current_section() ) {
		return $template;
	}

	// Status and query flags are settled on `wp` by zandi_prepare_virtual_page().
	return get_theme_file_path( 'template-section.php' );
}
add_filter( 'template_include', 'zandi_section_template' );

/**
 * Title, description and canonical tags for a section page.
 *
 * @return void
 */
function zandi_section_head() {
	$section = zandi_current_section();

	if ( ! $section ) {
		return;
	}

	$site = zandi_site();
	$url  = zandi_section_url( $section['slug'] );

	printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $section['meta'] ) );
	printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $url ) );
	printf( "<meta property=\"og:type\" content=\"website\">\n" );
	printf( "<meta property=\"og:locale\" content=\"fa_IR\">\n" );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( $site['name'] ) );
	printf( "<meta property=\"og:title\" content=\"%s | %s\">\n", esc_attr( $section['title'] ), esc_attr( $site['name'] ) );
	printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $section['meta'] ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $url ) );
}
add_action( 'wp_head', 'zandi_section_head', 3 );

/**
 * Sets the browser title on section pages.
 *
 * @param array $parts Title parts.
 * @return array
 */
function zandi_section_title( $parts ) {
	$section = zandi_current_section();

	if ( $section ) {
		$parts['title'] = $section['title'];
	}

	return $parts;
}
add_filter( 'document_title_parts', 'zandi_section_title' );

/**
 * The course requested by the current URL, if any.
 *
 * @return array<string,mixed>|null
 */
function zandi_current_course() {
	/*
	 * Deliberately not memoised. It is called ~11 times per course request, but
	 * now that zandi_courses_data() is, each call is a get_query_var() and an
	 * isset() — while a static here would cache whatever the answer was the
	 * first time anything asked, including before parse_request has run.
	 */
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

	// Status and query flags are settled on `wp` by zandi_prepare_virtual_page().
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
	$url   = zandi_course_url( $course['slug'] );

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
		array( 'zandi-style', 'zandi-rtl' ),
		zandi_asset_version( 'assets/css/courses.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'zandi_course_assets', 20 );

/**
 * Loads the account stylesheet on /login/, /register/ and /panel/.
 *
 * These pages are built from the site's own primitives — .container, .section,
 * .card, .field and zandi_button() — so this file carries only the handful of
 * patterns that exist nowhere else: the auth card, the details list and the
 * licence block. It is not a second design system.
 *
 * @return void
 */
function zandi_panel_assets() {
	if ( ! zandi_account_route() ) {
		return;
	}

	wp_enqueue_style(
		'zandi-panel',
		get_theme_file_uri( 'assets/css/panel.css' ),
		array( 'zandi-style', 'zandi-rtl' ),
		zandi_asset_version( 'assets/css/panel.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'zandi_panel_assets', 20 );

/*
 * Playfair Display used to be preloaded here on every course page — 38 KB
 * competing with the Peyda preload for the critical path, for a face that only
 * ever sets a few Latin runs inside headings. It is not the LCP font and it is
 * not above the fold in any meaningful sense.
 *
 * The @font-face stays in courses.css and loads normally under
 * font-display: swap. Only the preload is gone. Peyda Regular keeps its preload
 * in zandi_preload_font() — that one does render the first paint.
 *
 * Playfair is still self-hosted rather than pulled from Google Fonts, which is
 * blocked in Iran; a blocked font request does not fail silently, it stalls.
 */

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

	$route = zandi_account_route();

	if ( $route ) {
		$classes[] = 'panel-page';
		$classes[] = 'panel-page--' . $route;
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
	$back  = zandi_get_course( $slug ) ? zandi_course_url( $slug ) : home_url( '/' );

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

/*
 * The free-consultation booking form was removed on 30 July 2026 and replaced
 * by real student accounts — see inc/auth.php. `zandi_handle_booking()`,
 * `zandi_booking_confirmed()` and the `zandi_booking_submitted` action went with
 * it; nothing in the theme fires that hook any more.
 */

/*
 * There was a zandi_body_classes() here adding `has-anchor-nav` on the front
 * page, which gated a scroll-spy in theme.js. Both are gone: the navigation
 * points at real pages now, so there is no in-page section for a spy to track.
 */
