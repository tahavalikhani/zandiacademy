<?php
/**
 * Student testimonials.
 *
 * Real reviews only. With none collected yet the section shows an honest empty
 * state — see the note on zandi_testimonials() in inc/content.php.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// Loaded bare from the homepage, or with flags from template-section.php.
$zandi_args = isset( $args ) && is_array( $args ) ? $args : array();

$zandi_quote_mark = '<path d="M9.5 6.5C7 7.6 5.5 9.8 5.5 12.6v4.9h5.2v-5.2H8.3c0-2 .6-3.3 2.4-4.2l-1.2-1.6Zm8.4 0c-2.5 1.1-4 3.3-4 6.1v4.9h5.2v-5.2h-2.4c0-2 .6-3.3 2.4-4.2l-1.2-1.6Z"/>';

$zandi_testimonials = zandi_testimonials();
?>

<section class="section" id="testimonials" aria-labelledby="testimonials-title">
	<div class="container">
		<?php
		zandi_maybe_section_heading(
			$zandi_args,
			array(
				'id'          => 'testimonials',
				'eyebrow'     => 'نظرات زبان‌آموزها',
				'title'       => 'زبان‌آموزهای من چی می‌گن',
			)
		);
		?>

		<?php if ( empty( $zandi_testimonials ) ) : ?>
			<?php /* TODO: real reviews needed before launch — see zandi_testimonials(). */ ?>
			<div class="empty-note reveal">
				<p class="empty-note__title">نظرات زبان‌آموزها به‌زودی اینجا منتشر می‌شود</p>
				<p class="empty-note__body">در حال جمع‌آوری تجربه‌های واقعی زبان‌آموزهای دوره‌ها هستیم.</p>
			</div>
		<?php else : ?>
			<div class="carousel reveal" data-carousel>
				<ul class="carousel__track" tabindex="0" role="region" aria-label="نظرات زبان‌آموزهای آکادمی زندی">
					<?php foreach ( $zandi_testimonials as $zandi_index => $zandi_item ) : ?>
						<li class="carousel__item">
							<figure class="card card--interactive testimonial">
								<div class="testimonial__head">
									<svg class="testimonial__quote-mark" viewBox="0 0 24 24" aria-hidden="true">
										<?php echo $zandi_quote_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal path. ?>
									</svg>
									<?php zandi_rating( (int) $zandi_item['rating'] ); ?>
								</div>

								<blockquote class="testimonial__quote"><?php echo esc_html( $zandi_item['quote'] ); ?></blockquote>

								<figcaption class="testimonial__footer">
									<?php zandi_avatar( $zandi_item['name'], array( 'index' => $zandi_index ) ); ?>
									<span class="testimonial__author">
										<span class="testimonial__name"><?php echo esc_html( $zandi_item['name'] ); ?></span>
										<span class="testimonial__role"><?php echo esc_html( $zandi_item['role'] ); ?></span>
									</span>
								</figcaption>
							</figure>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="carousel__controls">
					<button type="button" class="carousel__btn" data-carousel-prev aria-label="نمایش مورد قبلی" disabled>
						<?php zandi_icon( zandi_arrow_back() ); ?>
					</button>
					<button type="button" class="carousel__btn" data-carousel-next aria-label="نمایش مورد بعدی">
						<?php zandi_icon( zandi_arrow_forward() ); ?>
					</button>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
