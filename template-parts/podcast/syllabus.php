<?php
/**
 * سرفصل — the chapters, as the accordion the course pages already use.
 *
 * MARKUP IS SHARED WITH THE COURSE SYLLABUS AND THE FAQ. Same `.accordion`
 * classes, same `data-accordion` hook, same script — the owner asked for «the
 * format of the courses' سرفصل section», and the honest way to give her that is
 * to use it rather than to build a second accordion that has to be kept in step
 * with the first. Those base rules live in style.css, which every page loads,
 * so this needed no new JavaScript and no copied CSS.
 *
 * `data-accordion="multi"` for the same reason the course syllabus uses it: the
 * FAQ closes the others when you open one, and a syllabus must not, because
 * comparing two chapters is the point of having them listed.
 *
 * With JavaScript off every chapter renders open and readable — the panels are
 * closed by a class, not by `hidden`.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy     = zandi_podcast_copy();
$zandi_chapters = zandi_podcast_chapters();

/*
 * Counted, never typed — the rule the course syllabus states: «۹۸ موضوع»
 * printed beside ninety-nine of them is the kind of small wrongness that makes
 * a reader doubt the rest of the page.
 */
$zandi_topics = 0;

foreach ( $zandi_chapters as $zandi_chapter ) {
	$zandi_topics += count( (array) $zandi_chapter['items'] );
}
?>

<section class="section podcast-syllabus" id="syllabus" aria-labelledby="podcast-syllabus-title">
	<div class="container">
		<?php
		zandi_section_heading(
			array(
				'title'       => $zandi_copy['chapters_title'],
				'description' => $zandi_copy['chapters_lead'],
				'id'          => 'podcast-syllabus',
			)
		);
		?>

		<?php if ( ! $zandi_chapters ) : ?>
			<?php /* if/else, not `return` — see the note in episodes.php. */ ?>
			<div class="empty-note podcast-empty">
				<p class="empty-note__title"><?php echo esc_html( $zandi_copy['chapters_soon'] ); ?></p>
				<p class="empty-note__body"><?php echo esc_html( $zandi_copy['chapters_soon_body'] ); ?></p>
			</div>
		<?php else : ?>

			<?php
			/*
			 * Two figures, both counted. A list rather than a sentence, so a
			 * screen reader reads two facts instead of one run-on line and the
			 * separator is drawn by CSS instead of typed as text the bidi
			 * algorithm would push around.
			 */
			?>
			<ul class="podcast-syllabus__stats">
				<li><?php echo esc_html( zandi_fa_digits( (string) count( $zandi_chapters ) ) . ' ' . $zandi_copy['chapters_stats_chapters'] ); ?></li>
				<li><?php echo esc_html( zandi_fa_digits( (string) $zandi_topics ) . ' ' . $zandi_copy['chapter_topics'] ); ?></li>
			</ul>

			<div class="accordion accordion--syllabus podcast-syllabus__accordion" data-accordion="multi">
				<?php foreach ( $zandi_chapters as $zandi_i => $zandi_chapter ) : ?>
					<?php
					// The first chapter is open on arrival; the rest are shut.
					$zandi_open       = 0 === $zandi_i;
					$zandi_trigger_id = 'podcast-chapter-trigger-' . $zandi_i;
					$zandi_panel_id   = 'podcast-chapter-panel-' . $zandi_i;
					$zandi_items      = (array) $zandi_chapter['items'];
					$zandi_count      = count( $zandi_items );
					?>
					<div class="accordion__item<?php echo $zandi_open ? ' is-open' : ''; ?>">
						<h3>
							<button
								type="button"
								class="accordion__trigger podcast-chapter__trigger"
								id="<?php echo esc_attr( $zandi_trigger_id ); ?>"
								aria-expanded="<?php echo $zandi_open ? 'true' : 'false'; ?>"
								aria-controls="<?php echo esc_attr( $zandi_panel_id ); ?>"
							>
								<span class="podcast-chapter__num" aria-hidden="true"><?php echo esc_html( zandi_fa_digits( (string) ( $zandi_i + 1 ) ) ); ?></span>

								<span class="podcast-chapter__title"><?php echo zandi_bidi( $zandi_chapter['title'] ); ?></span>

								<?php
								/*
								 * Read out in full for assistive tech. The
								 * visible chip is a bare numeral, which on its
								 * own announces as «۹» with no unit.
								 */
								?>
								<span class="podcast-chapter__count">
									<span aria-hidden="true"><?php echo esc_html( zandi_fa_digits( (string) $zandi_count ) . ' ' . $zandi_copy['chapter_topics'] ); ?></span>
									<span class="screen-reader-text"><?php echo esc_html( zandi_fa_digits( (string) $zandi_count ) . ' ' . $zandi_copy['chapter_topics'] ); ?></span>
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
								<ul class="podcast-chapter__list">
									<?php foreach ( $zandi_items as $zandi_item ) : ?>
										<li class="podcast-chapter__item">
											<span class="podcast-chapter__bullet" aria-hidden="true"><?php zandi_icon( 'check' ); ?></span>
											<span><?php echo zandi_bidi( $zandi_item ); ?></span>
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
