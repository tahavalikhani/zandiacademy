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

					<?php
					/*
					 * A course with no licence is not an error and must not be
					 * silent. The licence is created by a background job after
					 * payment, so there is a window — usually seconds — where the
					 * student owns the course and the key does not exist yet.
					 *
					 * This block used to render only when a licence was present,
					 * which meant that window showed a card with no key, no
					 * download button and no explanation. Someone who has just
					 * paid reads that as a purchase that failed.
					 */
					?>
					<?php if ( ! empty( $zandi_course['licence'] ) ) : ?>
						<div class="panel-licence">
							<div class="panel-licence__head">
								<span class="panel-licence__label"><?php echo esc_html( $zandi_copy['licence_label'] ); ?></span>

								<?php
								/*
								 * SHIPS HIDDEN, AND theme.js REVEALS IT — but only
								 * once it knows the browser can actually write to the
								 * clipboard. With scripts off, or in a browser with no
								 * clipboard access, there is no button rather than one
								 * that does nothing when pressed. The key itself is
								 * plain selectable text either way, so nothing is lost.
								 *
								 * Two labels, one shown at a time, because the string
								 * «کپی شد» belongs in PHP behind the copy filter like
								 * every other word on the site — not inside theme.js
								 * where nothing could translate or change it.
								 */
								?>
								<button
									type="button"
									class="btn btn--secondary btn--sm panel-licence__copy"
									aria-live="polite"
									aria-label="<?php echo esc_attr( sprintf( $zandi_copy['licence_sr'], $zandi_course['title'] ) ); ?>"
									hidden
								>
									<span class="panel-licence__state panel-licence__state--idle">
										<?php zandi_icon( 'clipboard', array( 'class' => 'btn__icon' ) ); ?>
										<?php echo esc_html( $zandi_copy['licence_copy'] ); ?>
									</span>
									<span class="panel-licence__state panel-licence__state--done">
										<?php zandi_icon( 'check', array( 'class' => 'btn__icon' ) ); ?>
										<?php echo esc_html( $zandi_copy['licence_copied'] ); ?>
									</span>
								</button>
							</div>

							<code class="panel-licence__key" dir="ltr"><?php echo esc_html( $zandi_course['licence'] ); ?></code>

							<?php
							/*
							 * Always open, not behind a disclosure. Three short
							 * lines are cheaper to read than a control is to
							 * press, and this is precisely what a student needs
							 * at the moment they first see a key they have never
							 * been given instructions for.
							 */
							?>
							<div class="panel-licence__help">
								<p class="panel-licence__help-title"><?php echo esc_html( $zandi_copy['licence_help'] ); ?></p>

								<ol class="panel-licence__steps">
									<?php foreach ( (array) $zandi_copy['licence_steps'] as $zandi_step ) : ?>
										<li><?php echo zandi_bidi( $zandi_step ); ?></li>
									<?php endforeach; ?>
								</ol>
							</div>
						</div>
					<?php else : ?>
						<div class="panel-licence panel-licence--pending">
							<span class="panel-licence__label"><?php echo esc_html( $zandi_copy['licence_pending'] ); ?></span>
							<p class="panel-licence__note"><?php echo zandi_bidi( $zandi_copy['licence_pending_body'] ); ?></p>
						</div>
					<?php endif; ?>

					<div class="panel-course__actions">
						<?php
						if ( ! empty( $zandi_course['player'] ) ) {
							zandi_button(
								array(
									'label' => $zandi_copy['course_player'],
									'url'   => $zandi_course['player'],
									'size'  => 'sm',
									'attrs' => array( 'rel' => 'noopener' ),
								)
							);
						}

						if ( ! empty( $zandi_course['url'] ) ) {
							zandi_button(
								array(
									'label'   => $zandi_copy['course_page'],
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

		<?php
		$zandi_next = zandi_panel_next_course( $zandi_courses );

		if ( $zandi_next ) :
			?>
			<div class="card panel-next">
				<div class="panel-next__body">
					<p class="panel-next__title"><?php echo esc_html( $zandi_copy['next_title'] ); ?></p>
					<p class="panel-next__lead"><?php echo zandi_bidi( sprintf( $zandi_copy['next_body'], $zandi_next['title'] ) ); ?></p>
				</div>

				<?php
				zandi_button(
					array(
						'label'    => $zandi_copy['next_cta'],
						'sr_label' => sprintf( '%1$s — %2$s', $zandi_copy['next_cta'], $zandi_next['title'] ),
						'url'      => $zandi_next['url'],
						'size'     => 'sm',
						'icon'     => zandi_arrow_forward(),
					)
				);
				?>
			</div>
		<?php endif; ?>

	<?php endif; ?>
</section>
