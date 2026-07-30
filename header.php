<?php
/**
 * Document head and site header.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_contact = zandi_contact();
?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php
	/*
	 * Swap `no-js` for `js` before the first paint. Scroll-reveal starts at
	 * opacity 0 and the accordion starts collapsed, so without this the page
	 * would be blank for anyone whose JavaScript fails to load.
	 */
	?>
	<script>document.documentElement.className = document.documentElement.className.replace( 'no-js', 'js' );</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main">رفتن به محتوای اصلی</a>

<header class="site-header" id="site-header">
	<div class="container">
		<nav class="site-nav" aria-label="ناوبری اصلی">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) . ' — خانه' ); ?>">
				<?php
				if ( has_custom_logo() ) {
					the_custom_logo();
				} else {
					zandi_logo();
				}
				?>
			</a>

			<div class="menu-desktop">
				<?php zandi_primary_nav( 'desktop' ); ?>
			</div>

			<div class="site-nav__actions">
				<?php
				// The primary action stays visible at every width — it is the
				// whole point of the page, so it never hides behind the menu.
				// A signed-in student gets their panel instead of a signup they
				// have already completed.
				zandi_button(
					is_user_logged_in()
						? array(
							'label' => 'پنل من',
							'url'   => zandi_panel_url(),
							'size'  => 'sm',
						)
						: array(
							'label' => 'ثبت نام',
							'url'   => zandi_register_url(),
							'size'  => 'sm',
						)
				);
				?>

				<button
					type="button"
					class="menu-toggle"
					aria-expanded="false"
					aria-controls="menu-mobile"
					aria-label="باز کردن منو"
					data-label-open="باز کردن منو"
					data-label-close="بستن منو"
				>
					<span class="menu-toggle__open"><?php zandi_icon( 'menu' ); ?></span>
					<span class="menu-toggle__close"><?php zandi_icon( 'close' ); ?></span>
				</button>
			</div>
		</nav>
	</div>

	<div class="menu-mobile" id="menu-mobile">
		<div class="container menu-mobile__inner">
			<?php zandi_primary_nav( 'mobile' ); ?>

			<?php
			// TODO: no phone number yet, so the menu's call button is withheld
			// rather than rendered empty. It returns the moment zandi_contact()
			// carries a real number.
			if ( $zandi_contact['phone'] && $zandi_contact['phone_href'] ) {
				zandi_button(
					array(
						'label'       => $zandi_contact['phone'],
						'url'         => $zandi_contact['phone_href'],
						'variant'     => 'secondary',
						'size'        => 'md',
						'class'       => 'btn--block',
						'icon_before' => 'phone',
					)
				);
			}
			?>
		</div>
	</div>
</header>
