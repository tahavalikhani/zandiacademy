<?php
/**
 * Featured courses.
 *
 * The whole card lifts on hover but only the button is a link target, so the
 * accessible name of the action stays specific ("مشاهده دوره — فرانسه از صفر").
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// Loaded bare from the homepage, or with flags from template-section.php.
$zandi_args = isset( $args ) && is_array( $args ) ? $args : array();
?>

<section class="section" id="courses" aria-labelledby="courses-title">
	<div class="container">
		<?php
		zandi_maybe_section_heading(
			$zandi_args,
			array(
				'id'          => 'courses',
				'eyebrow'     => 'دوره‌ها',
				'title'       => 'از حرف اول تا حرف زدن روان',
				'description' => 'سه سطح، هر کدوم یه مسیر مشخص. مطمئن نیستی از کدوم شروع کنی؟ توی تلگرام بپرس تا با هم پیداش کنیم.',
			)
		);
		?>

		<div class="courses__grid reveal-group">
			<?php foreach ( zandi_courses() as $course ) : ?>
				<div class="reveal reveal--scale">
					<article class="card card--interactive card--flush course-card">
						<div class="course-card__media">
							<?php
							/*
							 * Course media slot. With no image it draws a
							 * considered abstract composition instead of a grey
							 * box, so the grid looks finished before real
							 * photography exists — and the aspect ratio is
							 * reserved either way, so nothing shifts later.
							 */
							?>
							<div class="thumb thumb--<?php echo esc_attr( $course['tone'] ); ?>">
								<div class="thumb__art" aria-hidden="true">
									<?php zandi_engraving( 'thumb' ); ?>
									<?php if ( $course['level'] ) : ?>
										<span class="thumb__level"><?php echo esc_html( $course['level'] ); ?></span>
									<?php endif; ?>
								</div>
							</div>

							<?php if ( $course['badge'] ) : ?>
								<?php zandi_badge( $course['badge'], 'rouge', array( 'class' => 'course-card__badge' ) ); ?>
							<?php endif; ?>
						</div>

						<div class="course-card__body">
							<div class="course-card__meta">
								<?php if ( $course['level'] ) : ?>
									<?php zandi_badge( 'سطح ' . $course['level'], 'navy' ); ?>
								<?php endif; ?>

								<span class="course-card__meta-item">
									<?php zandi_icon( 'clock' ); ?>
									<?php echo esc_html( $course['duration'] ); ?>
								</span>

								<?php if ( $course['sessions'] ) : ?>
									<span class="course-card__meta-item">
										<?php zandi_icon( 'layers' ); ?>
										<?php echo esc_html( $course['sessions'] ); ?>
									</span>
								<?php endif; ?>
							</div>

							<h3 class="course-card__title"><?php echo zandi_bidi( $course['title'] ); ?></h3>

							<p class="course-card__text"><?php echo zandi_bidi( $course['description'] ); ?></p>

							<?php
							if ( $course['url'] ) {
								zandi_button(
									array(
										'label'    => 'مشاهده دوره',
										'sr_label' => 'مشاهده دوره ' . $course['title'],
										'url'      => $course['url'],
										'variant'  => 'secondary',
										'size'     => 'sm',
										'class'    => 'course-card__cta',
										'icon'     => zandi_arrow_forward(),
									)
								);
							} else {
								/* TODO: link once the course is on sale. */
								echo '<p class="course-card__soon">به‌زودی</p>';
							}
							?>
						</div>
					</article>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="courses__footer reveal">
			<p>مطمئن نیستید کدام دوره مناسب شماست؟ جلسه تعیین سطح رایگان پاسخ می‌دهد.</p>
			<?php
			zandi_button(
				array(
					'label'   => 'رزرو تعیین سطح رایگان',
					// Resolved, not bare: this partial also renders on /courses/,
					// where there is no #register on the page.
					'url'     => zandi_resolve_anchor( '#register' ),
					'variant' => 'secondary',
					'size'    => 'md',
					'icon'    => zandi_arrow_forward(),
				)
			);
			?>
		</div>
	</div>
</section>
