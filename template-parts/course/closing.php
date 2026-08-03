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
			 */
			zandi_enrol_control(
				$course,
				array(
					'label' => sprintf(
						/* translators: 1: course name, 2: price in Toman. */
						'%1$s · %2$s تومان',
						$course['cta_primary'],
						zandi_price_toman( $course['price_toman'] )
					),
				)
			);
			?>
		</div>
	</div>
</section>
