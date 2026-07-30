<?php
/**
 * Sign-in and sign-up — /login/ and /register/
 *
 * Ordering only. The route is resolved in inc/auth.php, which has already run
 * on `template_redirect` and either redirected, or left its errors in
 * zandi_auth_errors() for the partial to render.
 *
 * Uses the site header and footer. These pages briefly had a header-panel.php
 * of their own; folding them into the site chrome is the same call the course
 * pages got, and for the same reason — signing in should not feel like leaving
 * zandiacademy.com.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_route = zandi_account_route();

get_header();
?>

<main id="main">
	<div class="section section--mist auth-ground">
		<div class="container">
			<div class="auth-layout">
				<?php
				/*
				 * Card first, aside second — in the markup as well as on screen.
				 * The grid places them side by side on a wide viewport without
				 * reordering anything, so tab order matches reading order and a
				 * phone gets the form before the sales pitch.
				 */
				get_template_part( 'template-parts/account/' . ( 'register' === $zandi_route ? 'register' : 'login' ) );
				get_template_part( 'template-parts/account/aside' );
				?>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
