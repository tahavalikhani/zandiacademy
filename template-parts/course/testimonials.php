<?php
/**
 * Student reviews on a course page.
 *
 * THE SAME REVIEWS AS EVERY OTHER PAGE. This was a two-up grid fed by its own
 * list until 3 September 2026, on the assumption that a review of the A1 course
 * belonged on the A1 page. The owner settled the opposite: a review is about the
 * teaching, so someone who wrote after finishing A1 is worth reading on the B1
 * page too. One list, one component — see zandi_testimonials_carousel().
 *
 * The section keeps the course page's own chrome (.c-section / .c-container) so
 * the vertical rhythm matches the sections around it. The card inside is the
 * shared one from style.css, which loads on every page; nothing here needs
 * courses.css.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_testimonials_copy();
?>

<section class="c-section" id="testimonials" aria-labelledby="testimonials-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="testimonials-title"><?php echo esc_html( $zandi_copy['title'] ); ?></h2>
		</div>

		<?php zandi_testimonials_carousel( zandi_course_testimonials() ); ?>
	</div>
</section>
