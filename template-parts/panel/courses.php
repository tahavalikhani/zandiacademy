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

/*
 * ONCE, NOT ONCE PER CARD. zandi_podcast_state() reads a user-meta mirror, and
 * when that mirror is missing — a student whose access has never been synced —
 * it falls through to zandi_podcast_compute_expiry(), which runs a
 * wc_get_orders() query. Asked inside the loop below that is one order query
 * per course a student owns, on a page that already has enough to do. The
 * answer cannot change between two cards of the same render anyway.
 *
 * Null when the podcast feature is not loaded, and null for a student who owns
 * nothing — there are no cards to draw a gift on, so there is nothing to ask.
 */
$zandi_podcast = ( $zandi_courses && function_exists( 'zandi_podcast_state' ) ) ? zandi_podcast_state( $args['user']->ID ) : null;
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

					<?php
					/*
					 * THE BONUS, directly under the licence, because that is
					 * where the owner asked for it and because it is the right
					 * place: the licence is the first thing a student looks for
					 * after paying, so it is the one block on this page that is
					 * certain to be read.
					 *
					 * THE SAME COMPONENT AS THE COURSE PAGE'S, TURNED DOWN — and
					 * that is the instruction rather than a shortcut. Over there
					 * it is a four-line card with a champagne ground, because it
					 * is selling to somebody who has not paid and has to be
					 * noticed while they scroll. Here the student already owns
					 * it, so the only live question is whether it is on and what
					 * to do next: one line for what it is, one for the state,
					 * one link. Navy, no warm ground, nothing set large.
					 *
					 * The badge and its glyph are shared with the course page so
					 * the two are recognisably one feature. It is an icon from
					 * the registry, never an emoji — the 🎁 that used to be here
					 * is exactly what the owner rejected on the course page.
					 *
					 * The link is the in-page anchor to «پادکست من» below, where
					 * the Telegram step is. Without that step the bonus is days
					 * of access to a group the bot will not open.
					 */
					$zandi_gift = isset( $zandi_course['podcast_days'] ) ? (int) $zandi_course['podcast_days'] : 0;

					if ( $zandi_gift && null !== $zandi_podcast && function_exists( 'zandi_podcast_copy' ) ) :
						$zandi_pod = zandi_podcast_copy();
						?>
						<div class="panel-gift">
							<p class="panel-gift__title">
								<span class="panel-gift__badge"><?php zandi_icon( 'gift', array( 'stroke' => 1.6 ) ); ?></span>
								<?php echo esc_html( sprintf( $zandi_pod['perk_panel_title'], zandi_fa_digits( (string) $zandi_gift ) ) ); ?>
							</p>

							<p class="panel-gift__note">
								<?php
								/*
								 * Read from the same zandi_podcast_state() the
								 * podcast card below uses, so the two can never
								 * tell a student two different things on one
								 * screen. 'grace' counts as still in — the bot
								 * lets them stay for the day.
								 */
								echo esc_html(
									in_array( $zandi_podcast, array( 'active', 'grace' ), true )
										? $zandi_pod['perk_panel_on']
										: $zandi_pod['perk_panel_off']
								);
								?>
							</p>

							<a class="panel-gift__link" href="#my-podcast"><?php echo esc_html( $zandi_pod['perk_panel_cta'] ); ?></a>
						</div>
						<?php
					endif;
					?>

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

						/*
						 * The class's own group, second: it is where a student
						 * goes next after the player, and ahead of the sales
						 * page they have already bought from.
						 *
						 * Absent rather than dead when a course has no group —
						 * zandi_course_group_url() returns '' and this skips.
						 */
						if ( ! empty( $zandi_course['group'] ) ) {
							zandi_button(
								array(
									'label'       => $zandi_copy['course_group'],
									'sr_label'    => sprintf( $zandi_copy['course_group_sr'], $zandi_course['title'] ),
									'url'         => $zandi_course['group'],
									'variant'     => 'secondary',
									'size'        => 'sm',
									'class'       => 'panel-course__group',
									'icon_before' => 'telegram',
									'attrs'       => array(
										'target' => '_blank',
										'rel'    => 'noopener noreferrer',
									),
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
