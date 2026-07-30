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

<section class="panel-section" id="my-courses" aria-labelledby="my-courses-title">
	<h2 class="panel-section__title" id="my-courses-title"><?php echo esc_html( $zandi_copy['courses_title'] ); ?></h2>

	<?php if ( ! $zandi_courses ) : ?>

		<div class="empty-note">
			<p class="empty-note__title"><?php echo esc_html( $zandi_copy['courses_empty'] ); ?></p>
			<p class="empty-note__body"><?php echo esc_html( $zandi_copy['courses_empty_body'] ); ?></p>

			<p class="panel-empty__action">
				<?php
				zandi_button(
					array(
						'label' => $zandi_copy['courses_cta'],
						'url'   => zandi_section_url( 'courses' ),
						'size'  => 'md',
					)
				);
				?>
			</p>
		</div>

	<?php else : ?>

		<ul class="panel-courses">
			<?php foreach ( $zandi_courses as $zandi_course ) : ?>
				<li class="card panel-course">
					<div class="panel-course__head">
						<h3 class="panel-course__title"><?php echo zandi_bidi( $zandi_course['title'] ); ?></h3>

						<?php
						if ( ! empty( $zandi_course['level'] ) ) {
							zandi_badge( $zandi_course['level'], 'navy' );
						}
						?>
					</div>

					<?php if ( ! empty( $zandi_course['licence'] ) ) : ?>
						<div class="panel-licence">
							<span class="panel-licence__label">کلید لایسنس اسپات پلیر</span>
							<code class="panel-licence__key" dir="ltr"><?php echo esc_html( $zandi_course['licence'] ); ?></code>
						</div>
					<?php endif; ?>

					<div class="panel-course__actions">
						<?php
						if ( ! empty( $zandi_course['player'] ) ) {
							zandi_button(
								array(
									'label' => 'دانلود پلیر',
									'url'   => $zandi_course['player'],
									'size'  => 'sm',
									'attrs' => array( 'rel' => 'noopener' ),
								)
							);
						}

						if ( ! empty( $zandi_course['url'] ) ) {
							zandi_button(
								array(
									'label'   => 'صفحه دوره',
									'url'     => $zandi_course['url'],
									'variant' => 'secondary',
									'size'    => 'sm',
								)
							);
						}
						?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>

	<?php endif; ?>
</section>
