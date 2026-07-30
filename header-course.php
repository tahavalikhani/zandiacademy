<?php
/**
 * Header for course landing pages.
 *
 * Separate from the site header because the course pages run their own palette
 * and navigation. Loaded via get_header( 'course' ).
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_site = zandi_site();
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

<header class="c-header" id="course-header">
	<div class="c-container">
		<nav class="c-header__inner" aria-label="ناوبری اصلی">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="c-logo">
				<span><?php echo esc_html( $zandi_site['name'] ); ?></span>
				<span class="c-logo__sub">FRANÇAIS</span>
			</a>

			<div class="c-nav">
				<?php foreach ( zandi_course_nav() as $zandi_item ) : ?>
					<a href="<?php echo esc_url( $zandi_item['url'] ); ?>"><?php echo esc_html( $zandi_item['label'] ); ?></a>
				<?php endforeach; ?>
			</div>

			<div class="c-header__actions">
				<?php if ( is_user_logged_in() ) : ?>
					<a class="c-btn c-btn--primary c-btn--sm" href="<?php echo esc_url( zandi_panel_url() ); ?>">پنل من</a>
				<?php else : ?>
					<a class="c-btn c-btn--primary c-btn--sm" href="<?php echo esc_url( zandi_login_url() ); ?>">ورود / ثبت‌نام</a>
				<?php endif; ?>

				<button
					type="button"
					class="c-menu-toggle"
					aria-expanded="false"
					aria-controls="course-mobile-nav"
					aria-label="باز کردن منو"
					data-label-open="باز کردن منو"
					data-label-close="بستن منو"
					data-course-menu
				>
					<span class="c-menu-toggle__open"><?php zandi_icon( 'menu' ); ?></span>
					<span class="c-menu-toggle__close"><?php zandi_icon( 'close' ); ?></span>
				</button>
			</div>
		</nav>
	</div>

	<div class="c-mobile-nav" id="course-mobile-nav">
		<div class="c-container">
			<?php foreach ( zandi_course_nav() as $zandi_item ) : ?>
				<a href="<?php echo esc_url( $zandi_item['url'] ); ?>"><?php echo esc_html( $zandi_item['label'] ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
</header>
