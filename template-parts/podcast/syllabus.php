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
 * Renders nothing until zandi_podcast_chapters() has something in it. An empty
 * «سرفصل» heading is a promise the page does not keep, and a visitor reads it
 * as a section that failed to load rather than as one that is not written yet.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy     = zandi_podcast_copy();
$zandi_chapters = zandi_podcast_chapters();

if ( ! $zandi_chapters ) {
	return;
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
	</div>
</section>
