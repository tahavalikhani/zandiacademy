<?php
/**
 * Student testimonials.
 *
 * TODO: real reviews required. Five genuine ones with a name and city beat
 * twenty invented ones, and a fabricated review destroys trust outright if it
 * is ever noticed — so the section shows an honest empty state until they
 * arrive. Collect from the Telegram channel and Instagram DMs.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$testimonials = zandi_course_testimonials();
?>

<section class="c-section" id="testimonials" aria-labelledby="testimonials-title">
	<div class="c-container">
		<div class="c-section__head">
			<h2 class="c-section__title" id="testimonials-title">زبان‌آموزهای من چی می‌گن</h2>
		</div>

		<?php if ( empty( $testimonials ) ) : ?>
			<div class="c-empty">
				<p class="c-empty__title">نظرات زبان‌آموزها به‌زودی اینجا منتشر می‌شود</p>
				<p class="c-empty__body">
					در حال جمع‌آوری تجربه‌های واقعی زبان‌آموزهای دوره هستیم.
				</p>
			</div>
		<?php else : ?>
			<div class="c-cards c-cards--2">
				<?php foreach ( $testimonials as $item ) : ?>
					<figure class="c-card">
						<blockquote class="c-card__body">«&nbsp;<?php echo zandi_bidi( $item['quote'] ); ?>&nbsp;»</blockquote>
						<figcaption>
							<p class="c-card__title"><?php echo esc_html( $item['name'] ); ?></p>
							<p class="c-card__body">
								<?php echo esc_html( $item['role'] ); ?> · <?php echo esc_html( $item['city'] ); ?>
							</p>
							<?php zandi_rating( (int) $item['rating'] ); ?>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
