<?php
/**
 * Student testimonials.
 *
 * The carousel is a native scroll-snap shelf, so touch, trackpad, keyboard and
 * screen-reader navigation all work without JavaScript re-implementing them.
 * The arrow buttons are a convenience layer on top.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$quote_mark = '<path d="M9.5 6.5C7 7.6 5.5 9.8 5.5 12.6v4.9h5.2v-5.2H8.3c0-2 .6-3.3 2.4-4.2l-1.2-1.6Zm8.4 0c-2.5 1.1-4 3.3-4 6.1v4.9h5.2v-5.2h-2.4c0-2 .6-3.3 2.4-4.2l-1.2-1.6Z"/>';
?>

<section class="section" id="testimonials" aria-labelledby="testimonials-title">
	<div class="container">
		<?php
		zandi_section_heading(
			array(
				'id'          => 'testimonials',
				'eyebrow'     => 'نظرات زبان‌آموزان',
				'title'       => 'کسانی که مسیر را تمام کرده‌اند',
				'description' => 'بیش از دو هزار نفر با آکادمی زندی به فرانسه رسیده‌اند. چند نفرشان تجربه‌شان را نوشته‌اند.',
			)
		);
		?>

		<div class="carousel reveal" data-carousel>
			<ul class="carousel__track" tabindex="0" role="region" aria-label="نظرات زبان‌آموزان آکادمی زندی">
				<?php foreach ( zandi_testimonials() as $index => $testimonial ) : ?>
					<li class="carousel__item">
						<figure class="card card--interactive testimonial">
							<div class="testimonial__head">
								<svg class="testimonial__quote-mark" viewBox="0 0 24 24" aria-hidden="true">
									<?php echo $quote_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal path. ?>
								</svg>
								<?php zandi_rating( (int) $testimonial['rating'] ); ?>
							</div>

							<blockquote class="testimonial__quote"><?php echo esc_html( $testimonial['quote'] ); ?></blockquote>

							<figcaption class="testimonial__footer">
								<?php zandi_avatar( $testimonial['name'], array( 'index' => $index ) ); ?>
								<span class="testimonial__author">
									<span class="testimonial__name"><?php echo esc_html( $testimonial['name'] ); ?></span>
									<span class="testimonial__role"><?php echo esc_html( $testimonial['role'] ); ?></span>
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
	</div>
</section>
