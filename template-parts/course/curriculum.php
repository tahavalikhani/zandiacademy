<?php
/**
 * Curriculum accordion.
 *
 * TODO: the real syllabus for all three levels is still outstanding. Until it
 * arrives the section renders its empty state rather than invented sections —
 * a fabricated curriculum on a sales page is worse than an honest gap.
 *
 * Populate `curriculum` in inc/courses.php as:
 *   array( array( 'title' => 'بخش ۱: …', 'lessons' => array(
 *       array( 'title' => '…', 'duration' => '۱۵ دقیقه' ), … ) ), … )
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course     = $args['course'];
$curriculum = $course['curriculum'];
?>

<section class="c-section" id="curriculum" aria-labelledby="curriculum-title">
	<div class="c-container">
		<div class="c-section__head">
			<h2 class="c-section__title" id="curriculum-title">توی این دوره چی می‌خونیم</h2>
		</div>

		<?php if ( empty( $curriculum ) ) : ?>
			<div class="c-empty">
				<p class="c-empty__title">سرفصل‌های کامل به‌زودی منتشر می‌شود</p>
				<p class="c-empty__body">
					فهرست جلسه‌به‌جلسه این دوره در حال آماده‌سازی است. اگر سوالی درباره محتوای دوره داری، توی تلگرام بپرس.
				</p>
			</div>
		<?php else : ?>
			<div class="accordion" data-accordion>
				<?php foreach ( $curriculum as $index => $chapter ) : ?>
					<?php
					$is_open    = 0 === $index;
					$trigger_id = 'curriculum-trigger-' . $index;
					$panel_id   = 'curriculum-panel-' . $index;
					?>
					<div class="accordion__item<?php echo $is_open ? ' is-open' : ''; ?>">
						<h3>
							<button
								type="button"
								class="accordion__trigger"
								id="<?php echo esc_attr( $trigger_id ); ?>"
								aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
								aria-controls="<?php echo esc_attr( $panel_id ); ?>"
							>
								<span><?php echo esc_html( $chapter['title'] ); ?></span>
								<span class="accordion__chevron"><?php zandi_icon( 'chevronDown' ); ?></span>
							</button>
						</h3>

						<div
							class="accordion__panel"
							id="<?php echo esc_attr( $panel_id ); ?>"
							role="region"
							aria-labelledby="<?php echo esc_attr( $trigger_id ); ?>"
						>
							<div>
								<ul class="accordion__answer">
									<?php foreach ( $chapter['lessons'] as $lesson ) : ?>
										<li>
											<span>▸ <?php echo esc_html( $lesson['title'] ); ?></span>
											<span> · <?php echo esc_html( $lesson['duration'] ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
