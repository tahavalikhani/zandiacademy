<?php
/**
 * Placement test — the printable report.
 *
 * A document, not a web page. It is laid out for A4 and reads top to bottom in
 * one column, because the thing a student does with it is press print and save
 * a PDF — every phone and desktop browser can, and that is the whole mechanism.
 * No PDF library, no vendor directory, no build step: the browser already
 * shapes Persian correctly and already has the theme's fonts.
 *
 * THE MARK PRINTS BECAUSE IT IS INLINE SVG. Browsers strip background images
 * from printed pages by default, so nothing in this layout may depend on a
 * painted background — not the logo, not the watermark, not the level chip.
 * zandi_logo() has always emitted inline SVG so CSS could reach its stroke and
 * fill; that decision is what makes it printable here for free.
 *
 * ACCOUNT REQUIRED, by the owner's decision. The level, the bands and the
 * course recommendation stay free on the result page — the specification is
 * explicit that gating the *result* reads as a trap. This is the extra.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy   = zandi_placement_copy();
$zandi_result = zandi_placement_report_result();

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

$zandi_rows      = zandi_placement_result_rows();
$zandi_row       = isset( $zandi_rows[ $zandi_result['level'] ] ) ? $zandi_rows[ $zandi_result['level'] ] : array();
$zandi_label     = isset( $zandi_row['label'] ) ? $zandi_row['label'] : $zandi_result['level'];
$zandi_lead      = isset( $zandi_row['headline'] ) ? $zandi_row['headline'] : '';
$zandi_review    = zandi_placement_review( $zandi_result );
$zandi_plan      = zandi_placement_study_plan( $zandi_result );
$zandi_suggest   = zandi_placement_recommendation( $zandi_result['level'] );
$zandi_minutes   = $zandi_result['duration'] ? max( 1, (int) round( $zandi_result['duration'] / 60 ) ) : 0;
$zandi_long      = ! zandi_placement_level_is_code( $zandi_label );

/*
 * Whose name goes on the document. Normally the reader's own — but the owner
 * can open a student's report from the students screen in wp-admin, and it
 * would be worse than useless with her name at the top of it. See
 * zandi_placement_report_user(), which is what decides that the reader is
 * allowed to be looking at somebody else's result at all.
 */
$zandi_student   = zandi_placement_report_user();
$zandi_user      = $zandi_student ? get_userdata( $zandi_student ) : wp_get_current_user();
$zandi_name      = $zandi_user ? $zandi_user->display_name : '';

// display_name falls back to the login, which for a student is their phone
// number — printing someone's phone number as their name on a document is not
// something to do by accident.
if ( ! $zandi_name || zandi_is_valid_phone( $zandi_name ) ) {
	$zandi_name = ( $zandi_user && $zandi_user->first_name ) ? $zandi_user->first_name : $zandi_copy['report_anon'];
}
?>

<div class="pt-doc">

	<?php
	/*
	 * Screen only. It is the first thing on the page for someone who has just
	 * arrived and the last thing they need in the printed document, so it is
	 * removed by the print stylesheet rather than moved.
	 */
	?>
	<div class="pt-doc__bar">
		<button type="button" class="btn btn--primary btn--md" data-pt-print>
			<?php zandi_icon( 'clipboard', array( 'class' => 'btn__icon' ) ); ?>
			<?php echo esc_html( $zandi_copy['report_print'] ); ?>
		</button>

		<p class="pt-doc__bar-hint"><?php echo zandi_bidi( $zandi_copy['report_print_hint'] ); ?></p>
	</div>

	<article class="pt-doc__sheet">

		<?php
		/*
		 * The watermark. Inline SVG for the same reason as the logo — a CSS
		 * background would simply not print. aria-hidden because it is a
		 * texture, and it must never be read out between the heading and the
		 * result.
		 */
		?>
		<div class="pt-doc__watermark" aria-hidden="true">
			<svg viewBox="15.5 5 81 90" fill="none" focusable="false">
				<circle cx="68" cy="13" r="8" fill="currentColor" />
				<path
					d="M22,34 H82 L28,78 C46,93 78,93 90,70"
					stroke="currentColor"
					stroke-width="13"
					stroke-linecap="round"
					stroke-linejoin="round"
				/>
			</svg>
		</div>

		<header class="pt-doc__masthead">
			<div class="pt-doc__brand"><?php zandi_logo(); ?></div>

			<h1 class="pt-doc__title"><?php echo zandi_bidi( $zandi_copy['report_title'] ); ?></h1>
			<p class="pt-doc__subtitle"><?php echo zandi_bidi( $zandi_copy['report_subtitle'] ); ?></p>
		</header>

		<?php // Who, when, and a number to quote at support. ?>
		<dl class="pt-doc__meta">
			<div>
				<dt><?php echo esc_html( $zandi_copy['report_for'] ); ?></dt>
				<dd><?php echo zandi_bidi( $zandi_name ); ?></dd>
			</div>

			<div>
				<dt><?php echo esc_html( $zandi_copy['report_date'] ); ?></dt>
				<dd>
					<?php echo esc_html( zandi_placement_date( $zandi_result['time'] ) ); ?>
					<span class="pt-doc__gregorian" dir="ltr"><?php echo esc_html( wp_date( 'Y-m-d', $zandi_result['time'] ) ); ?></span>
				</dd>
			</div>

			<?php if ( $zandi_minutes ) : ?>
				<div>
					<dt><?php echo esc_html( $zandi_copy['report_duration'] ); ?></dt>
					<dd><?php printf( esc_html( $zandi_copy['report_minutes'] ), esc_html( zandi_fa_digits( $zandi_minutes ) ) ); ?></dd>
				</div>
			<?php endif; ?>

			<div>
				<dt><?php echo esc_html( $zandi_copy['report_ref'] ); ?></dt>
				<dd><code dir="ltr"><?php echo esc_html( zandi_placement_reference( $zandi_result ) ); ?></code></dd>
			</div>
		</dl>

		<!-- 1. Summary -->
		<section class="pt-doc__section pt-doc__section--summary">
			<h2 class="pt-doc__heading"><?php echo zandi_bidi( $zandi_copy['report_summary'] ); ?></h2>

			<div class="pt-doc__verdict">
				<p class="pt-doc__level<?php echo $zandi_long ? ' pt-doc__level--long' : ''; ?>">
					<?php echo zandi_placement_level_label( $zandi_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
				</p>

				<div class="pt-doc__verdict-body">
					<?php if ( $zandi_lead ) : ?>
						<p class="pt-doc__headline"><?php echo zandi_bidi( $zandi_lead ); ?></p>
					<?php endif; ?>

					<p class="pt-doc__score">
						<?php
						printf(
							/* translators: 1: correct answers, 2: total questions. */
							esc_html( $zandi_copy['result_correct'] ),
							esc_html( zandi_fa_digits( $zandi_result['correct'] ) ),
							esc_html( zandi_fa_digits( $zandi_result['total'] ) )
						);
						?>
						<?php if ( $zandi_result['idk'] ) : ?>
							· <?php printf( esc_html( '%s بار «نمی‌دانم»' ), esc_html( zandi_fa_digits( $zandi_result['idk'] ) ) ); ?>
						<?php endif; ?>
					</p>
				</div>
			</div>

			<table class="pt-doc__table">
				<caption class="screen-reader-text">نتیجهٔ هر بخش</caption>
				<thead>
					<tr>
						<th scope="col">بخش</th>
						<th scope="col">جواب درست</th>
						<th scope="col">حد نصاب</th>
						<th scope="col">وضعیت</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $zandi_result['bands'] as $zandi_band ) : ?>
						<tr class="<?php echo $zandi_band['passed'] ? 'is-passed' : ''; ?>">
							<th scope="row" dir="ltr"><?php echo esc_html( $zandi_band['id'] ); ?></th>
							<td><?php echo esc_html( zandi_fa_digits( $zandi_band['correct'] ) . ' از ' . zandi_fa_digits( $zandi_band['count'] ) ); ?></td>
							<td><?php echo esc_html( zandi_fa_digits( $zandi_band['mastery'] ) ); ?></td>
							<td><?php echo esc_html( $zandi_band['passed'] ? 'گرفته' : 'نگرفته' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<table class="pt-doc__table">
				<caption class="screen-reader-text">به تفکیک مهارت</caption>
				<thead>
					<tr>
						<th scope="col">مهارت</th>
						<th scope="col">جواب درست</th>
						<th scope="col">درصد</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $zandi_result['skills'] as $zandi_skill ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $zandi_skill['label'] ); ?></th>
							<td><?php echo esc_html( zandi_fa_digits( $zandi_skill['correct'] ) . ' از ' . zandi_fa_digits( $zandi_skill['total'] ) ); ?></td>
							<td><?php echo esc_html( zandi_fa_digits( $zandi_skill['percent'] ) ); ?>٪</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! empty( $zandi_result['gap'] ) && $zandi_result['gap_band'] ) : ?>
				<p class="pt-doc__note">
					<strong><?php echo zandi_bidi( $zandi_copy['gap_title'] ); ?>:</strong>
					<?php echo zandi_bidi( sprintf( $zandi_copy['gap_body'], $zandi_result['gap_band'] ) ); ?>
				</p>
			<?php endif; ?>
		</section>

		<!-- 2. Study plan -->
		<section class="pt-doc__section">
			<h2 class="pt-doc__heading"><?php echo zandi_bidi( $zandi_copy['report_plan'] ); ?></h2>

			<?php if ( ! $zandi_plan ) : ?>
				<p class="pt-doc__lead"><?php echo zandi_bidi( $zandi_copy['report_plan_empty'] ); ?></p>
			<?php else : ?>
				<p class="pt-doc__lead"><?php echo zandi_bidi( $zandi_copy['report_plan_lead'] ); ?></p>

				<?php foreach ( $zandi_plan as $zandi_group ) : ?>
					<div class="pt-doc__plan">
						<h3 class="pt-doc__plan-band">
							بخش <span dir="ltr"><?php echo esc_html( $zandi_group['id'] ); ?></span>
						</h3>

						<ul class="pt-doc__plan-list">
							<?php foreach ( $zandi_group['topics'] as $zandi_topic ) : ?>
								<li>
									<span class="pt-doc__plan-focus"<?php echo zandi_placement_dir_attrs( $zandi_topic['focus'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
										<?php echo zandi_placement_content( $zandi_topic['focus'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
									</span>
									<span class="pt-doc__plan-meta">
										<?php echo esc_html( $zandi_topic['skill'] ); ?>
										·
										<?php printf( esc_html( $zandi_copy['report_q'] ), esc_html( zandi_fa_digits( $zandi_topic['number'] ) ) ); ?>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>

		<!-- 3. Question by question -->
		<section class="pt-doc__section">
			<h2 class="pt-doc__heading"><?php echo zandi_bidi( $zandi_copy['report_review'] ); ?></h2>
			<p class="pt-doc__lead"><?php echo zandi_bidi( $zandi_copy['report_review_lead'] ); ?></p>

			<ol class="pt-doc__review">
				<?php foreach ( $zandi_review as $zandi_item ) : ?>
					<li class="pt-doc__item pt-doc__item--<?php echo esc_attr( $zandi_item['outcome'] ); ?>">
						<div class="pt-doc__item-head">
							<span class="pt-doc__item-no">
								<?php printf( esc_html( $zandi_copy['report_q'] ), esc_html( zandi_fa_digits( $zandi_item['number'] ) ) ); ?>
							</span>

							<span class="pt-doc__item-tags">
								<span dir="ltr"><?php echo esc_html( $zandi_item['band'] ); ?></span>
								<?php
								$zandi_skills = zandi_placement_skills();

								if ( isset( $zandi_skills[ $zandi_item['skill'] ] ) ) {
									echo ' · ' . esc_html( $zandi_skills[ $zandi_item['skill'] ] );
								}
								?>
							</span>

							<span class="pt-doc__item-outcome">
								<?php echo esc_html( $zandi_copy['report_outcome'][ $zandi_item['outcome'] ] ); ?>
							</span>
						</div>

						<?php if ( $zandi_item['focus'] ) : ?>
							<p class="pt-doc__item-focus"<?php echo zandi_placement_dir_attrs( $zandi_item['focus'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
								<?php echo zandi_placement_content( $zandi_item['focus'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
							</p>
						<?php endif; ?>

						<?php if ( $zandi_item['passage'] ) : ?>
							<p class="pt-doc__item-passage"<?php echo zandi_placement_dir_attrs( $zandi_item['passage'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
								<?php echo zandi_placement_content( $zandi_item['passage'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
							</p>
						<?php endif; ?>

						<?php
						// Same line-per-script treatment the test page uses, so a
						// Persian question about a French sentence reads here
						// exactly as it did during the test.
						?>
						<div class="pt-doc__item-stem"><?php echo zandi_placement_stem( $zandi_item['stem'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?></div>

						<?php if ( $zandi_item['prompt'] ) : ?>
							<p class="pt-doc__item-prompt"<?php echo zandi_placement_dir_attrs( $zandi_item['prompt'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
								<?php echo zandi_placement_content( $zandi_item['prompt'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
							</p>
						<?php endif; ?>

						<dl class="pt-doc__answers">
							<div>
								<dt><?php echo esc_html( $zandi_copy['report_your_answer'] ); ?></dt>
								<dd<?php echo zandi_placement_dir_attrs( $zandi_item['chosen'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
									<?php
									echo $zandi_item['chosen']
										? zandi_placement_content( $zandi_item['chosen'] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside.
										: esc_html( $zandi_copy['report_no_answer'] );
									?>
								</dd>
							</div>

							<?php if ( 'correct' !== $zandi_item['outcome'] ) : ?>
								<div>
									<dt><?php echo esc_html( $zandi_copy['report_right_answer'] ); ?></dt>
									<dd<?php echo zandi_placement_dir_attrs( $zandi_item['correct'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
										<?php echo zandi_placement_content( $zandi_item['correct'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
									</dd>
								</div>
							<?php endif; ?>
						</dl>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>

		<!-- 4. Next step -->
		<section class="pt-doc__section">
			<h2 class="pt-doc__heading">قدم بعدی تو</h2>

			<?php if ( $zandi_suggest['lead'] ) : ?>
				<p class="pt-doc__lead"><?php echo zandi_bidi( $zandi_suggest['lead'] ); ?></p>
			<?php endif; ?>

			<?php if ( $zandi_suggest['courses'] ) : ?>
				<ul class="pt-doc__courses">
					<?php foreach ( $zandi_suggest['courses'] as $zandi_index => $zandi_course ) : ?>
						<li>
							<h3 class="pt-doc__course-title">
								<?php echo zandi_bidi( $zandi_course['short_name'] ); ?>
								<?php if ( 0 === $zandi_index ) : ?>
									<span class="pt-doc__course-flag">پیشنهاد برای تو</span>
								<?php endif; ?>
							</h3>

							<p class="pt-doc__course-meta">
								<?php echo esc_html( $zandi_course['sessions_text'] ); ?>
								·
								<?php echo esc_html( $zandi_course['hours_text'] ); ?>
								·
								<span dir="ltr"><?php echo esc_url( zandi_course_url( $zandi_course['slug'] ) ); ?></span>
							</p>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $zandi_suggest['contact'] ) ) : ?>
				<p class="pt-doc__note">
					سطحت از چیزی که الان روی سایت هست جلوتره. از صفحهٔ تماس بگو دنبال چی هستی:
					<span dir="ltr"><?php echo esc_url( zandi_support_url() ); ?></span>
				</p>
			<?php endif; ?>
		</section>

		<!-- 5. Method -->
		<section class="pt-doc__section">
			<h2 class="pt-doc__heading"><?php echo zandi_bidi( $zandi_copy['report_method'] ); ?></h2>

			<?php foreach ( zandi_placement_method_notes() as $zandi_note ) : ?>
				<h3 class="pt-doc__sub"><?php echo zandi_bidi( $zandi_note['title'] ); ?></h3>
				<p class="pt-doc__body"><?php echo zandi_bidi( $zandi_note['body'] ); ?></p>
			<?php endforeach; ?>
		</section>

		<!-- 6. Limits -->
		<section class="pt-doc__section">
			<h2 class="pt-doc__heading"><?php echo zandi_bidi( $zandi_copy['report_limits'] ); ?></h2>

			<ul class="pt-doc__limits">
				<?php foreach ( zandi_placement_limits() as $zandi_limit ) : ?>
					<li><?php echo zandi_bidi( $zandi_limit ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<!-- 7. Sources -->
		<section class="pt-doc__section">
			<h2 class="pt-doc__heading"><?php echo zandi_bidi( $zandi_copy['report_sources'] ); ?></h2>

			<?php foreach ( zandi_placement_sources() as $zandi_group ) : ?>
				<h3 class="pt-doc__sub"><?php echo zandi_bidi( $zandi_group['title'] ); ?></h3>

				<ul class="pt-doc__refs">
					<?php foreach ( $zandi_group['items'] as $zandi_item ) : ?>
						<li>
							<span class="pt-doc__cite"<?php echo zandi_placement_dir_attrs( $zandi_item['cite'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
								<?php echo zandi_placement_content( $zandi_item['cite'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
							</span>
							<?php if ( $zandi_item['note'] ) : ?>
								<span class="pt-doc__cite-note"><?php echo zandi_bidi( $zandi_item['note'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</section>

		<footer class="pt-doc__foot">
			<p>
				<?php echo esc_html( zandi_site()['name'] ); ?>
				·
				<span dir="ltr"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
				·
				<span dir="ltr"><?php echo esc_html( zandi_placement_reference( $zandi_result ) ); ?></span>
			</p>
		</footer>
	</article>
</div>
