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
?>

<section class="section" id="courses" aria-labelledby="courses-title">
	<div class="container">
		<?php
		zandi_section_heading(
			array(
				'id'          => 'courses',
				'eyebrow'     => 'دوره‌ها',
				'title'       => 'از اولین کلمه تا مدرک بین‌المللی',
				'description' => 'هر دوره بر اساس چارچوب اروپایی CEFR طراحی شده و در پایان آن دقیقاً می‌دانید در چه سطحی هستید و قدم بعدی چیست.',
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
									<span class="thumb__level"><?php echo esc_html( $course['level'] ); ?></span>
								</div>
							</div>

							<?php if ( $course['badge'] ) : ?>
								<?php zandi_badge( $course['badge'], 'rouge', array( 'class' => 'course-card__badge' ) ); ?>
							<?php endif; ?>
						</div>

						<div class="course-card__body">
							<div class="course-card__meta">
								<?php zandi_badge( 'سطح ' . $course['level'], 'navy' ); ?>

								<span class="course-card__meta-item">
									<?php zandi_icon( 'clock' ); ?>
									<?php echo esc_html( $course['duration'] ); ?>
								</span>

								<span class="course-card__meta-item">
									<?php zandi_icon( 'layers' ); ?>
									<?php echo esc_html( $course['sessions'] ); ?>
								</span>
							</div>

							<h3 class="course-card__title"><?php echo esc_html( $course['title'] ); ?></h3>

							<p class="course-card__text"><?php echo esc_html( $course['description'] ); ?></p>

							<?php
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
					'url'     => '#register',
					'variant' => 'secondary',
					'size'    => 'md',
					'icon'    => zandi_arrow_forward(),
				)
			);
			?>
		</div>
	</div>
</section>
