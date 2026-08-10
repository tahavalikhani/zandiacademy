<?php
/**
 * Syllabus — an accordion of sections, each holding its topics.
 *
 * The data lives in inc/courses.php under the course's `curriculum` key, not
 * here. That is where every other string on this page comes from, it is the
 * file the next person will open to edit a course, and it is what lets A2 and
 * B1 drop their own syllabus in without this template changing at all.
 *
 * MARKUP IS SHARED WITH THE FAQ ACCORDION. Same classes, same `data-accordion`
 * hook, same script — so this needed no new JavaScript file and no second
 * accordion implementation to keep in sync. The one difference is
 * `data-accordion="multi"`: the FAQ closes the others when you open one, and a
 * syllabus should not, because comparing two sections is the whole point.
 *
 * With JavaScript off every section renders open and readable — the panels are
 * closed by a class, not by `hidden`.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course     = $args['course'];
$curriculum = $course['curriculum'];

/*
 * Counted, never typed. The stats line used to be prose, and prose drifts the
 * first time a topic is added — «۳۴ موضوع» beside thirty-five of them is the
 * kind of small wrongness that makes a reader doubt the rest of the page.
 */
$zandi_topic_count = 0;

foreach ( $curriculum as $zandi_section ) {
	$zandi_topic_count += count( $zandi_section['items'] );
}
?>

<section class="c-section" id="curriculum" aria-labelledby="curriculum-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="curriculum-title">
				<?php
				echo zandi_bidi(
					sprintf(
						/* translators: %s: CEFR level, e.g. A1. */
						'سرفصل دوره %s',
						$course['level']
					)
				);
				?>
			</h2>

			<?php if ( $curriculum ) : ?>
				<?php
				/*
				 * A list, not a sentence: each figure is its own item, so a
				 * screen reader reads three facts rather than one run-on line,
				 * and the separators are drawn by CSS instead of typed as text
				 * that the bidi algorithm would push around.
				 */
				?>
				<?php
				/*
				 * Two figures, both counted. A «از A1 تا A2» third item used to
				 * sit here and was dropped at the owner's request: the heading
				 * above already names the level, and restating it as a journey
				 * read as marketing beside two plain numbers.
				 */
				?>
				<ul class="c-syllabus__stats">
					<li><?php echo esc_html( zandi_fa_digits( count( $curriculum ) ) . ' بخش' ); ?></li>
					<li><?php echo esc_html( zandi_fa_digits( $zandi_topic_count ) . ' موضوع' ); ?></li>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( empty( $curriculum ) ) : ?>
			<div class="c-empty reveal">
				<p class="c-empty__title">سرفصل‌های کامل به‌زودی منتشر می‌شود</p>
				<p class="c-empty__body">
					فهرست جلسه‌به‌جلسه این دوره در حال آماده‌سازی است. اگر سوالی درباره محتوای دوره داری، از صفحه تماس بپرس.
				</p>
			</div>
		<?php else : ?>
			<div class="accordion accordion--syllabus reveal" data-accordion="multi">
				<?php foreach ( $curriculum as $zandi_index => $zandi_part ) : ?>
					<?php
					// The first section is open on arrival; the rest are shut.
					$zandi_open       = 0 === $zandi_index;
					$zandi_trigger_id = 'syllabus-trigger-' . $zandi_index;
					$zandi_panel_id   = 'syllabus-panel-' . $zandi_index;
					$zandi_count      = count( $zandi_part['items'] );
					?>
					<div class="accordion__item<?php echo $zandi_open ? ' is-open' : ''; ?>">
						<h3>
							<button
								type="button"
								class="accordion__trigger c-syllabus__trigger"
								id="<?php echo esc_attr( $zandi_trigger_id ); ?>"
								aria-expanded="<?php echo $zandi_open ? 'true' : 'false'; ?>"
								aria-controls="<?php echo esc_attr( $zandi_panel_id ); ?>"
							>
								<span class="c-syllabus__num" aria-hidden="true">
									<?php echo esc_html( $zandi_part['number'] ); ?>
								</span>

								<span class="c-syllabus__head">
									<span class="c-syllabus__title">
										<?php if ( ! empty( $zandi_part['icon'] ) ) : ?>
											<span class="c-syllabus__icon" aria-hidden="true"><?php zandi_icon( $zandi_part['icon'] ); ?></span>
										<?php endif; ?>
										<?php echo zandi_bidi( $zandi_part['title'] ); ?>
									</span>

									<span class="c-syllabus__summary"><?php echo zandi_bidi( $zandi_part['summary'] ); ?></span>

									<?php
									/*
									 * Read out in full for assistive tech. The
									 * visible chip is a bare numeral, which on
									 * its own announces as "۵" with no unit.
									 */
									?>
									<span class="c-syllabus__count">
										<span aria-hidden="true"><?php echo esc_html( zandi_fa_digits( $zandi_count ) ); ?></span>
										<span class="screen-reader-text"><?php echo esc_html( zandi_fa_digits( $zandi_count ) . ' موضوع' ); ?></span>
										<span aria-hidden="true">موضوع</span>
									</span>
								</span>

								<span class="accordion__chevron"><?php zandi_icon( 'chevronDown' ); ?></span>
							</button>
						</h3>

						<div
							class="accordion__panel"
							id="<?php echo esc_attr( $zandi_panel_id ); ?>"
							role="region"
							aria-labelledby="<?php echo esc_attr( $zandi_trigger_id ); ?>"
						>
							<div>
								<ul class="c-syllabus__list">
									<?php foreach ( $zandi_part['items'] as $zandi_item ) : ?>
										<?php
										list( $zandi_item_title, $zandi_item_body ) = $zandi_item;
										?>
										<li class="c-syllabus__item">
											<span class="c-syllabus__bullet" aria-hidden="true"><?php zandi_icon( 'check' ); ?></span>
											<span>
												<span class="c-syllabus__item-title"><?php echo zandi_bidi( $zandi_item_title ); ?></span>
												<span class="c-syllabus__item-body"><?php echo zandi_bidi( $zandi_item_body ); ?></span>
											</span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php
			/*
			 * Immediately under the list, not down beside the button: it is a
			 * correction to what the reader has just been looking at, and it
			 * stops being one the moment it is far enough away to be missed.
			 */
			?>
			<p class="c-syllabus__note reveal"><?php echo zandi_bidi( zandi_curriculum_note() ); ?></p>

			<div class="c-syllabus__cta reveal">
				<?php
				/*
				 * Enrols from here rather than scrolling back to the card at the
				 * top — the same call the support and closing blocks make.
				 */
				zandi_enrol_control( $course, array( 'label' => 'ثبت‌نام در دوره' ) );
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
