<?php
/**
 * The free placement test — /placement/
 *
 * State selection only; every partial owns its own markup, and the copy, the
 * scoring and the routing all live in inc/placement.php.
 *
 *   intro   the instructions, the sources and the start button
 *   test    the thirty questions
 *   result  the level, the breakdown and the course that suits it
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_placement_copy();

get_header();
?>

<main id="main">
	<?php
	if ( ! zandi_placement_questions() ) {

		/*
		 * The bank is missing or malformed. An honest empty state, not a fatal
		 * and not a half-drawn test: inc/data/questions.json is a file that can
		 * be lost by a partial deploy, and the rest of the site is fine.
		 */
		?>
		<div class="container pt-missing">
			<div class="empty-note">
				<p class="empty-note__title"><?php echo esc_html( $zandi_copy['missing_title'] ); ?></p>
				<p class="empty-note__body"><?php echo esc_html( $zandi_copy['missing_body'] ); ?></p>
			</div>
		</div>
		<?php

	} else {

		switch ( zandi_placement_state() ) {
			case 'test':
				get_template_part( 'template-parts/placement/test' );
				break;

			case 'result':
				get_template_part( 'template-parts/placement/result' );
				break;

			default:
				get_template_part( 'template-parts/placement/intro' );
		}
	}
	?>
</main>

<?php
get_footer();
