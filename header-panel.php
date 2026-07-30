<?php
/**
 * Header for the account pages and the student panel.
 *
 * Slimmer than header-course.php: no marketing navigation, because someone who
 * is signing in or looking at their courses is not browsing. Shares the course
 * pages' palette and primitives — see zandi_uses_course_palette() in
 * functions.php for why `.course-page` is on the body of a page that is not a
 * course.
 *
 * Loaded via get_header( 'panel' ).
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_site  = zandi_site();
$zandi_route = zandi_account_route();
?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>document.documentElement.className = document.documentElement.className.replace( 'no-js', 'js' );</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main">رفتن به محتوای اصلی</a>

<header class="c-header p-header" id="panel-header">
	<div class="c-container">
		<nav class="c-header__inner" aria-label="ناوبری اصلی">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="c-logo">
				<span><?php echo esc_html( $zandi_site['name'] ); ?></span>
				<span class="c-logo__sub">FRANÇAIS</span>
			</a>

			<div class="c-header__actions">
				<?php if ( is_user_logged_in() ) : ?>
					<a class="p-header__link" href="<?php echo esc_url( zandi_panel_url() ); ?>">پنل من</a>
					<a class="c-btn c-btn--ghost c-btn--sm" href="<?php echo esc_url( zandi_logout_url() ); ?>">خروج</a>
				<?php elseif ( 'register' === $zandi_route ) : ?>
					<a class="c-btn c-btn--ghost c-btn--sm" href="<?php echo esc_url( zandi_login_url() ); ?>">ورود</a>
				<?php else : ?>
					<a class="c-btn c-btn--ghost c-btn--sm" href="<?php echo esc_url( zandi_register_url() ); ?>">ثبت‌نام</a>
				<?php endif; ?>
			</div>
		</nav>
	</div>
</header>
