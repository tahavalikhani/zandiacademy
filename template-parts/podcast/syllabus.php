<?php
/**
 * سرفصل — the chapters.
 *
 * Chapters rather than a hundred episode titles: eight rows answer «توش چیه؟»
 * in one screen, where a flat hundred is a wall nobody reads.
 *
 * The rows sit inside one card. Loose on the page they were eight hairlines
 * floating on white with nothing holding them together, which on a long page
 * reads as leftover markup rather than as a contents list.
 *
 * WITH NO CHAPTERS SET IT SAYS SO — see the note at the top of episodes.php,
 * which this follows for the same reason. There are deliberately no sample
 * chapters behind that state: eight invented «فصل ۱: …» rows would be the kind
 * of made-up content CLAUDE.md rules out, and the owner would have to delete
 * every one of them before writing the real list.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy     = zandi_podcast_copy();
$zandi_chapters = zandi_podcast_chapters();
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
		<ol class="card podcast-syllabus__list">
			<?php foreach ( $zandi_chapters as $zandi_chapter ) : ?>
				<li class="podcast-chapter">
					<div class="podcast-chapter__head">
						<h3 class="podcast-chapter__title"><?php echo zandi_bidi( $zandi_chapter['title'] ); ?></h3>

						<?php if ( ! empty( $zandi_chapter['episodes'] ) ) : ?>
							<span class="podcast-chapter__count">
								<?php
								echo esc_html(
									zandi_fa_digits( (string) (int) $zandi_chapter['episodes'] )
									. ' ' . $zandi_copy['chapter_count']
								);
								?>
							</span>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $zandi_chapter['summary'] ) ) : ?>
						<p class="podcast-chapter__summary"><?php echo zandi_bidi( $zandi_chapter['summary'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php endif; ?>
	</div>
</section>
