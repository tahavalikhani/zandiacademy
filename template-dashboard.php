<?php
/**
 * The student panel — /panel/
 *
 * Section ordering only. Access is enforced in inc/auth.php on
 * `template_redirect`, which has already bounced anyone signed out to /login/
 * by the time this file runs.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_user = wp_get_current_user();

if ( ! $zandi_user->exists() ) {
	return;
}

$args = array( 'user' => $zandi_user );

get_header();
?>

<main id="main">
	<div class="container">
		<?php
		get_template_part( 'template-parts/panel/welcome', null, $args );
		get_template_part( 'template-parts/panel/courses', null, $args );

		// Renders nothing until the student has actually sat the placement test.
		get_template_part( 'template-parts/panel/placement', null, $args );

		/*
		 * Support sits above the interview note and the account details on
		 * purpose. With enrolment not yet wired, this is often the only section
		 * on the page with something to act on — and the tab row in
		 * welcome.php is built to this order. See zandi_panel_nav().
		 */
		get_template_part( 'template-parts/panel/support', null, $args );

		get_template_part( 'template-parts/panel/interview', null, $args );
		get_template_part( 'template-parts/panel/profile', null, $args );
		?>
	</div>
</main>

<?php
get_footer();
