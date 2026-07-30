<?php
/**
 * Sign-in and sign-up — /login/ and /register/
 *
 * Ordering only. The route is resolved in inc/auth.php, which has already run
 * on `template_redirect` and either redirected, or left its errors in
 * zandi_auth_errors() for the partial to render.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_route = zandi_account_route();

get_header( 'panel' );
?>

<main id="main" class="p-main">
	<div class="c-container">
		<?php get_template_part( 'template-parts/account/' . ( 'register' === $zandi_route ? 'register' : 'login' ) ); ?>
	</div>
</main>

<?php
get_footer( 'panel' );
