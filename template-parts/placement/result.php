<?php
/**
 * Placement test — the result.
 *
 * THE LEVEL IS NEVER BEHIND A FORM. No phone number, no email, no account: the
 * student sees their level the moment they finish. Putting it behind a field
 * would read as a trap and would cost more trust than the numbers are worth —
 * which is the specification's position and the site's.
 *
 * What is asked for afterwards is an account, and only because an account is
 * where the result gets kept. There is no SMS integration behind a «گزارش کامل
 * را پیامک کنیم؟» field, so no such field is drawn.
 *
 * The result arrives by redirect and is read from a one-day transient by the
 * token in the URL, so refreshing does not re-submit thirty answers and the back
 * button behaves.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy   = zandi_placement_copy();
$zandi_token  = isset( $_GET['r'] ) ? sanitize_text_field( wp_unslash( $_GET['r'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The random token is itself the credential.
$zandi_result = zandi_placement_fetch( $zandi_token );

if ( ! $zandi_result ) :
	?>
	<div class="container pt-expired">
		<div class="empty-note">
			<p class="empty-note__title"><?php echo esc_html( $zandi_copy['expired_title'] ); ?></p>
			<p class="empty-note__body"><?php echo esc_html( $zandi_copy['expired_body'] ); ?></p>

			<p class="panel-empty__action">
				<?php
				zandi_button(
					array(
						'label' => $zandi_copy['start'],
						'url'   => zandi_placement_url( 'start' ),
					)
				);
				?>
			</p>
		</div>
	</div>
	<?php
	return;
endif;

$zandi_level   = $zandi_result['level'];
$zandi_rows    = zandi_placement_result_rows();
$zandi_row     = isset( $zandi_rows[ $zandi_level ] ) ? $zandi_rows[ $zandi_level ] : array();
$zandi_label   = isset( $zandi_row['label'] ) ? $zandi_row['label'] : $zandi_level;
$zandi_lead    = isset( $zandi_row['headline'] ) ? $zandi_row['headline'] : '';
$zandi_suggest = zandi_placement_recommendation( $zandi_level );
$zandi_minutes = $zandi_result['duration'] ? max( 1, (int) round( $zandi_result['duration'] / 60 ) ) : 0;

// «هنوز به A1 نرسیده» is a sentence, «A1+» is a code. Same chip, two sizes.
$zandi_long = ! zandi_placement_level_is_code( $zandi_label );
?>

<div class="pt-result">
	<div class="container">

		<header class="pt-result__head">
			<?php zandi_badge( $zandi_copy['result_eyebrow'], 'rouge', array( 'dot' => true ) ); ?>

			<p class="pt-result__label"><?php echo esc_html( $zandi_copy['result_title'] ); ?></p>

			<p class="pt-level<?php echo $zandi_long ? ' pt-level--long' : ''; ?>">
				<?php
				/*
				 * The guilloché, the same engraving the hero and the CTA panel
				 * use. It is the site's own French cue — banknote geometry, not
				 * a flag or a tower — and it costs no request and no new colour.
				 */
				zandi_engraving( 'thumb' );
				?>
				<span class="pt-level__code"><?php echo zandi_placement_level_label( $zandi_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?></span>
			</p>

			<?php if ( $zandi_lead ) : ?>
				<h1 class="pt-result__headline"><?php echo zandi_bidi( $zandi_lead ); ?></h1>
			<?php endif; ?>

			<p class="pt-result__meta">
				<span>
					<?php zandi_icon( 'check' ); ?>
					<?php
					printf(
						/* translators: 1: correct answers, 2: total questions. */
						esc_html( $zandi_copy['result_correct'] ),
						esc_html( zandi_fa_digits( $zandi_result['correct'] ) ),
						esc_html( zandi_fa_digits( $zandi_result['total'] ) )
					);
					?>
				</span>

				<?php if ( $zandi_minutes ) : ?>
					<span>
						<?php zandi_icon( 'clock' ); ?>
						<?php printf( esc_html( $zandi_copy['result_time'] ), esc_html( zandi_fa_digits( $zandi_minutes ) ) ); ?>
					</span>
				<?php endif; ?>
			</p>
		</header>

		<?php
		/*
		 * Said out loud rather than buried: a student who never once admitted to
		 * not knowing something has probably guessed, and the scorer has already
		 * withheld their half-level for it. Better they hear why.
		 */
		?>
		<p class="pt-confidence">
			<?php zandi_icon( 'lifebuoy' ); ?>
			<?php
			echo esc_html(
				$zandi_result['idk']
					? sprintf( $zandi_copy['result_idk'], zandi_fa_digits( $zandi_result['idk'] ) )
					: $zandi_copy['result_idk_zero']
			);
			?>
		</p>

		<?php if ( ! empty( $zandi_result['gap'] ) && $zandi_result['gap_band'] ) : ?>
			<div class="card pt-gap" role="note">
				<span class="pt-gap__icon" aria-hidden="true"><?php zandi_icon( 'trending' ); ?></span>
				<div>
					<h2 class="pt-gap__title"><?php echo zandi_bidi( $zandi_copy['gap_title'] ); ?></h2>
					<p class="pt-gap__text">
						<?php echo zandi_bidi( sprintf( $zandi_copy['gap_body'], $zandi_result['gap_band'] ) ); ?>
					</p>
				</div>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * Sharing sits here, above the breakdown, and not at the foot of the
		 * page. This is the moment the number has just landed and the student
		 * still has a reaction to pass on; by the bottom of a long result page
		 * that moment is spent.
		 *
		 * `hidden` until the script confirms there is a share sheet or a
		 * clipboard to use, so a browser with neither never shows a button that
		 * does nothing. placement.css has the author rule that makes `hidden`
		 * actually hide a .btn.
		 */
		?>
		<div class="pt-share">
			<?php
			zandi_button(
				array(
					'label'       => $zandi_copy['share'],
					'size'        => 'md',
					'icon_before' => 'sparkles',
					'attrs'       => array(
						'hidden'         => 'hidden',
						'data-pt-share'  => zandi_placement_share_text( $zandi_result, $zandi_label ),
						'data-pt-shared' => $zandi_copy['shared'],
					),
				)
			);
			?>

			<p class="pt-share__hint" hidden data-pt-share-hint><?php echo zandi_bidi( $zandi_copy['share_hint'] ); ?></p>

			<?php
			// The same cue the course pages use between sections: the result is
			// long and every block below ends on a finished-looking card, so the
			// bottom of one reads as the bottom of the page.
			zandi_scroll_cue();
			?>
		</div>

		<div class="pt-breakdown">
			<section class="card pt-panel" aria-labelledby="pt-bands-title">
				<h2 class="pt-panel__title" id="pt-bands-title">
					<?php zandi_icon( 'trending' ); ?>
					<?php echo zandi_bidi( $zandi_copy['bands_title'] ); ?>
				</h2>

				<ul class="pt-bands">
					<?php foreach ( $zandi_result['bands'] as $zandi_band ) : ?>
						<?php
						$zandi_fill  = $zandi_band['count'] ? ( $zandi_band['correct'] / $zandi_band['count'] ) * 100 : 0;
						$zandi_notch = $zandi_band['count'] ? ( $zandi_band['mastery'] / $zandi_band['count'] ) * 100 : 0;
						?>
						<li class="pt-band<?php echo $zandi_band['passed'] ? ' is-passed' : ''; ?>">
							<div class="pt-band__head">
								<span class="pt-band__id" dir="ltr">
									<?php
									/*
									 * Shape as well as colour. A cleared band is
									 * green AND carries a tick; a band that fell
									 * short is navy and carries nothing. Colour
									 * alone would say nothing to a student who
									 * cannot separate the two hues.
									 */
									if ( $zandi_band['passed'] ) {
										zandi_icon( 'check', array( 'class' => 'pt-band__tick', 'label' => 'حد نصاب گرفته شده' ) );
									}
									?>
									<?php echo esc_html( $zandi_band['id'] ); ?>
								</span>

								<span class="pt-band__score">
									<?php
									printf(
										/* translators: 1: correct answers, 2: questions in the band. */
										'%1$s از %2$s',
										esc_html( zandi_fa_digits( $zandi_band['correct'] ) ),
										esc_html( zandi_fa_digits( $zandi_band['count'] ) )
									);
									?>
								</span>
							</div>

							<?php
							/*
							 * The notch is the pass mark, drawn on the bar itself.
							 * A student can see at a glance not just how many they
							 * got but how many they needed, which is the number
							 * the level actually turns on.
							 */
							?>
							<div class="pt-meter" role="img" aria-label="<?php
								echo esc_attr(
									sprintf(
										'%1$s: %2$s از %3$s، حد نصاب %4$s',
										$zandi_band['id'],
										zandi_fa_digits( $zandi_band['correct'] ),
										zandi_fa_digits( $zandi_band['count'] ),
										zandi_fa_digits( $zandi_band['mastery'] )
									)
								);
							?>">
								<div class="pt-meter__fill" style="inline-size: <?php echo esc_attr( round( $zandi_fill, 2 ) ); ?>%"></div>
								<span class="pt-meter__notch" style="inset-inline-start: <?php echo esc_attr( round( $zandi_notch, 2 ) ); ?>%"></span>
							</div>

							<p class="pt-band__threshold">
								<?php printf( esc_html( $zandi_copy['mastery_label'] ), esc_html( zandi_fa_digits( $zandi_band['mastery'] ) ) ); ?>
							</p>
						</li>
					<?php endforeach; ?>
				</ul>

				<p class="pt-panel__note"><?php echo zandi_bidi( $zandi_copy['bands_note'] ); ?></p>
			</section>

			<section class="card pt-panel" aria-labelledby="pt-skills-title">
				<h2 class="pt-panel__title" id="pt-skills-title">
					<?php zandi_icon( 'target' ); ?>
					<?php echo zandi_bidi( $zandi_copy['skills_title'] ); ?>
				</h2>

				<ul class="pt-skills">
					<?php foreach ( $zandi_result['skills'] as $zandi_skill ) : ?>
						<li class="pt-skill">
							<div class="pt-skill__head">
								<span class="pt-skill__label">
									<?php zandi_icon( zandi_placement_skill_icon( $zandi_skill['id'] ) ); ?>
									<?php echo esc_html( $zandi_skill['label'] ); ?>
								</span>
								<span class="pt-skill__value"><?php echo esc_html( zandi_fa_digits( $zandi_skill['percent'] ) ); ?>٪</span>
							</div>

							<div class="pt-meter" role="img" aria-label="<?php
								echo esc_attr(
									sprintf(
										'%1$s: %2$s از %3$s',
										$zandi_skill['label'],
										zandi_fa_digits( $zandi_skill['correct'] ),
										zandi_fa_digits( $zandi_skill['total'] )
									)
								);
							?>">
								<div class="pt-meter__fill" style="inline-size: <?php echo esc_attr( (int) $zandi_skill['percent'] ); ?>%"></div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>

				<p class="pt-panel__note"><?php echo zandi_bidi( $zandi_copy['skills_note'] ); ?></p>
			</section>
		</div>

		<?php
		/*
		 * The recommendation. Every card here is a course that can be bought
		 * today; the levels the catalogue does not reach yet get an honest line
		 * and /contact/ instead of an invented waiting list.
		 */
		?>
		<section class="pt-next-step" aria-labelledby="pt-next-title">
			<h2 class="pt-block__title" id="pt-next-title">
				<?php zandi_icon( 'route' ); ?>
				قدم بعدی تو
			</h2>

			<?php if ( $zandi_suggest['lead'] ) : ?>
				<p class="pt-next-step__lead"><?php echo zandi_bidi( $zandi_suggest['lead'] ); ?></p>
			<?php endif; ?>

			<?php if ( $zandi_suggest['courses'] ) : ?>
				<ul class="pt-recs">
					<?php foreach ( $zandi_suggest['courses'] as $zandi_index => $zandi_course ) : ?>
						<?php $zandi_primary = 0 === $zandi_index; ?>
						<li class="card card--flush pt-rec<?php echo $zandi_primary ? ' pt-rec--primary' : ''; ?>">
							<div class="thumb thumb--navy pt-rec__thumb">
								<?php $zandi_cover = zandi_course_cover( $zandi_course['slug'] ); ?>

								<?php if ( $zandi_cover ) : ?>
									<img
										src="<?php echo esc_url( $zandi_cover ); ?>"
										alt="<?php echo esc_attr( $zandi_course['short_name'] ); ?>"
										width="800"
										height="500"
										loading="lazy"
										decoding="async"
									>
								<?php else : ?>
									<div class="thumb__art" aria-hidden="true">
										<?php zandi_engraving( 'thumb' ); ?>
										<span class="thumb__level"><?php echo esc_html( $zandi_course['level'] ); ?></span>
									</div>
								<?php endif; ?>
							</div>

							<div class="pt-rec__body">
								<div class="pt-rec__meta">
									<?php
									zandi_badge( 'سطح ' . $zandi_course['level'], 'navy' );

									if ( $zandi_primary ) {
										zandi_badge( 'پیشنهاد برای تو', 'rouge', array( 'dot' => true ) );
									}
									?>
								</div>

								<h3 class="pt-rec__title"><?php echo zandi_bidi( $zandi_course['short_name'] ); ?></h3>

								<p class="pt-rec__text"><?php echo zandi_bidi( $zandi_course['subtitle'] ); ?></p>

								<p class="pt-rec__facts">
									<span><?php zandi_icon( 'clock' ); ?><?php echo esc_html( $zandi_course['sessions_text'] ); ?></span>
									<span><?php zandi_icon( 'layers' ); ?><?php echo esc_html( $zandi_course['hours_text'] ); ?></span>
								</p>

								<?php
								zandi_button(
									array(
										'label'    => 'مشاهده دوره',
										'sr_label' => 'مشاهده دوره ' . $zandi_course['short_name'],
										'url'      => zandi_course_url( $zandi_course['slug'] ),
										'variant'  => $zandi_primary ? 'primary' : 'secondary',
										'size'     => 'md',
										'icon'     => zandi_arrow_forward(),
									)
								);
								?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $zandi_suggest['contact'] ) ) : ?>
				<div class="card pt-contact">
					<span class="pt-contact__icon" aria-hidden="true"><?php zandi_icon( 'chat' ); ?></span>
					<p class="pt-contact__text">سطحت از چیزی که الان روی سایت هست جلوتره. از صفحه تماس بگو دنبال چی هستی تا با هم تصمیم بگیریم.</p>

					<?php
					zandi_button(
						array(
							'label'   => 'صفحه تماس',
							'url'     => zandi_support_url(),
							'variant' => 'secondary',
							'size'    => 'md',
							'icon'    => zandi_arrow_forward(),
						)
					);
					?>
				</div>
			<?php endif; ?>
		</section>

		<?php
		/*
		 * The report, offered after «قدم بعدی تو» — the point at which the
		 * student has read everything the page has and the next useful thing is
		 * to take it away with them.
		 *
		 * Signed in, the button goes straight to the document. Signed out it
		 * goes to signup carrying the report as its return address, so they
		 * come back to their own result rather than to an empty panel — and
		 * the sitting itself is claimed by cookie in zandi_placement_claim(),
		 * which is what makes the promise true even through Digits.
		 */
		$zandi_report_url = is_user_logged_in()
			? zandi_placement_report_url( $zandi_token )
			: zandi_register_url( zandi_placement_report_url( $zandi_token ) );
		?>
		<section class="pt-report-cta" aria-labelledby="pt-report-title">
			<span class="pt-report-cta__icon" aria-hidden="true"><?php zandi_icon( 'clipboard' ); ?></span>

			<div class="pt-report-cta__body">
				<h2 class="pt-report-cta__title" id="pt-report-title"><?php echo zandi_bidi( $zandi_copy['report_cta'] ); ?></h2>
				<p class="pt-report-cta__text"><?php echo zandi_bidi( $zandi_copy['report_cta_hint'] ); ?></p>
			</div>

			<?php
			zandi_button(
				array(
					'label' => $zandi_copy['report_cta'],
					'url'   => $zandi_report_url,
					'size'  => 'md',
					'icon'  => zandi_arrow_forward(),
				)
			);
			?>
		</section>

		<div class="pt-after">
			<?php if ( is_user_logged_in() ) : ?>
				<p class="pt-after__saved">
					<?php zandi_icon( 'check' ); ?>
					<?php echo esc_html( $zandi_copy['saved'] ); ?>
					<a href="<?php echo esc_url( zandi_panel_url() ); ?>">پنل من</a>
				</p>
			<?php else : ?>
				<div class="card pt-save">
					<span class="pt-save__icon" aria-hidden="true"><?php zandi_icon( 'user' ); ?></span>
					<p class="pt-save__text"><?php echo zandi_bidi( $zandi_copy['save_prompt'] ); ?></p>

					<?php
					// Same return address as the report button above, so whichever
					// one they press they come back to their own result.
					zandi_button(
						array(
							'label'   => $zandi_copy['save_action'],
							'url'     => zandi_register_url( zandi_placement_report_url( $zandi_token ) ),
							'variant' => 'secondary',
							'size'    => 'md',
						)
					);
					?>
				</div>
			<?php endif; ?>

			<?php
			/*
			 * No «دوباره آزمون بده» here. A student has just this second
			 * finished the test; offering to run it again is the one thing they
			 * are certain not to want, and on a result page it quietly invites
			 * retaking until the number looks better. The panel keeps a retake
			 * button, where a result may be months old and taking it again is
			 * the point.
			 */
			?>
			<p class="pt-after__note"><?php echo zandi_bidi( $zandi_copy['honesty_body'] ); ?></p>
		</div>
	</div>
</div>
