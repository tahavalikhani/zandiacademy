<?php
/**
 * «دوره‌های من».
 *
 * Empty until enrolment is connected. The empty state is the honest one — it
 * says there is nothing yet and points at the catalogue, rather than drawing a
 * placeholder course card that implies a purchase that never happened.
 *
 * When WooCommerce and the SpotPlayer plugin are live, `zandi_student_courses()`
 * starts returning entries and the licence block below renders for each.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy    = zandi_panel_copy();
$zandi_courses = zandi_student_courses( $args['user']->ID );
?>

<section class="c-section c-section--tight" id="my-courses" aria-labelledby="my-courses-title">
	<h2 class="c-section__title" id="my-courses-title"><?php echo esc_html( $zandi_copy['courses_title'] ); ?></h2>

	<?php if ( ! $zandi_courses ) : ?>

		<div class="c-empty">
			<p class="c-empty__title"><?php echo esc_html( $zandi_copy['courses_empty'] ); ?></p>
			<p class="c-empty__body"><?php echo esc_html( $zandi_copy['courses_empty_body'] ); ?></p>

			<a class="c-btn c-btn--primary" href="<?php echo esc_url( home_url( '/courses/a1/' ) ); ?>">
				<?php echo esc_html( $zandi_copy['courses_cta'] ); ?>
			</a>
		</div>

	<?php else : ?>

		<ul class="p-courses">
			<?php foreach ( $zandi_courses as $zandi_course ) : ?>
				<li class="p-course">
					<div class="p-course__head">
						<h3 class="p-course__title"><?php echo zandi_bidi( $zandi_course['title'] ); ?></h3>

						<?php if ( ! empty( $zandi_course['level'] ) ) : ?>
							<span class="p-course__level" dir="ltr"><?php echo esc_html( $zandi_course['level'] ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $zandi_course['licence'] ) ) : ?>
						<div class="p-licence">
							<span class="p-licence__label">کلید لایسنس اسپات پلیر</span>
							<code class="p-licence__key" dir="ltr"><?php echo esc_html( $zandi_course['licence'] ); ?></code>
						</div>
					<?php endif; ?>

					<div class="p-course__actions">
						<?php if ( ! empty( $zandi_course['player'] ) ) : ?>
							<a class="c-btn c-btn--primary c-btn--sm" href="<?php echo esc_url( $zandi_course['player'] ); ?>" rel="noopener">دانلود پلیر</a>
						<?php endif; ?>

						<?php if ( ! empty( $zandi_course['url'] ) ) : ?>
							<a class="c-btn c-btn--ghost c-btn--sm" href="<?php echo esc_url( $zandi_course['url'] ); ?>">صفحه دوره</a>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>

	<?php endif; ?>
</section>
