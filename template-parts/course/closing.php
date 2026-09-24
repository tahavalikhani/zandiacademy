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

			<?php
			/*
			 * The last thing on the page, so it has to be the shortest path to
			 * checkout — not a jump back to the hero. The price stays on the
			 * label: this is the point of commitment and the number should not
			 * be a surprise on the next screen.
			 *
			 * Only when there is a price to show. A course that is not on sale
			 * has none yet, and «۰ تومان» on a button is worse than no number.
			 */
			zandi_enrol_control(
				$course,
				array(
					'label' => zandi_course_on_sale( $course['slug'] )
						? sprintf(
							/* translators: 1: course name, 2: price in Toman. */
							'%1$s · %2$s تومان',
							$course['cta_primary'],
							zandi_price_toman( $course['price_toman'] )
						)
						: $course['cta_primary'],
				)
			);
			?>
		</div>
	</div>
</section>
