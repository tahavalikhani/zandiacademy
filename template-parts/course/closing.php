<?php
/**
 * Closing call to action.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
?>

<section class="c-section" aria-labelledby="closing-title">
	<div class="c-container">
		<div class="c-closing reveal">
			<h2 class="c-closing__title" id="closing-title"><?php echo zandi_bidi( $course['closing_title'] ); ?></h2>
			<p class="c-closing__body"><?php echo zandi_bidi( $course['closing_body'] ); ?></p>

			<a class="c-btn c-btn--primary" href="#enrol">
				<?php
				printf(
					/* translators: 1: course name, 2: price in Toman. */
					'%1$s · %2$s تومان',
					esc_html( $course['cta_primary'] ),
					esc_html( zandi_price_toman( $course['price_toman'] ) )
				);
				?>
			</a>
		</div>
	</div>
</section>
