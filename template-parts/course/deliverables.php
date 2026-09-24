<?php
/**
 * What you get on enrolment — four cards.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = isset( $args['course'] ) ? $args['course'] : array();

/*
 * Four across by default. A course whose cards run long asks for two, so the
 * longest one is not stretched to twice the height of its neighbours.
 */
$zandi_columns = ( isset( $course['deliverables_columns'] ) && 2 === (int) $course['deliverables_columns'] ) ? 2 : 4;
?>

<section class="c-section" id="deliverables" aria-labelledby="deliverables-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="deliverables-title">دقیقاً چی تحویل می‌گیری 📦</h2>
		</div>

		<div class="c-cards c-cards--<?php echo esc_attr( $zandi_columns ); ?> reveal-group">
			<?php foreach ( zandi_course_deliverables( $course ) as $card ) : ?>
				<article class="c-card reveal reveal--scale">
					<span class="c-card__icon"><?php zandi_icon( $card['icon'] ); ?></span>
					<h3 class="c-card__title"><?php echo zandi_bidi( $card['title'] ); ?></h3>
					<?php
					/*
					 * nl2br() on the OUTSIDE of zandi_bidi(), as on the review
					 * cards: the owner's line breaks stay, and a Latin run never
					 * matches across one.
					 */
					?>
					<p class="c-card__body"><?php echo nl2br( zandi_bidi( $card['body'] ) ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
