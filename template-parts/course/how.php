<?php
/**
 * «هر جلسه چطوری پیش می‌ره» — what the videos of a conversation course are like.
 *
 * The owner wrote this as four paragraphs. The middle two each describe one
 * kind of video, so they are drawn as two cards — the same card the page uses
 * for «دقیقاً چی تحویل می‌گیری» — with a title lifted from each paragraph's own
 * first sentence. The first paragraph is the lead, and the last is a nudge
 * towards the sample lesson, so it ends in a link to it.
 *
 * Every word is the owner's, from the course's `how` entry in inc/courses.php.
 * A course without that entry renders nothing here.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course    = $args['course'];
$zandi_how = ! empty( $course['how'] ) ? (array) $course['how'] : array();

if ( ! $zandi_how ) {
	return;
}
?>

<section class="c-section" id="how" aria-labelledby="how-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="how-title"><?php echo zandi_bidi( $zandi_how['title'] ); ?></h2>

			<?php if ( ! empty( $zandi_how['lead'] ) ) : ?>
				<p class="c-section__lead"><?php echo zandi_bidi( $zandi_how['lead'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $zandi_how['kinds'] ) ) : ?>
			<div class="c-cards c-cards--2 c-how__kinds reveal-group">
				<?php foreach ( $zandi_how['kinds'] as $zandi_kind ) : ?>
					<article class="c-card reveal reveal--scale">
						<span class="c-card__icon"><?php zandi_icon( $zandi_kind['icon'] ); ?></span>
						<h3 class="c-card__title"><?php echo zandi_bidi( $zandi_kind['title'] ); ?></h3>
						<p class="c-card__body"><?php echo nl2br( zandi_bidi( $zandi_kind['body'] ) ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $zandi_how['outro'] ) ) : ?>
			<div class="c-how__outro reveal">
				<?php if ( ! empty( $zandi_how['outro_title'] ) ) : ?>
					<p class="c-how__tease"><?php echo zandi_bidi( $zandi_how['outro_title'] ); ?></p>
				<?php endif; ?>

				<p class="c-how__body"><?php echo zandi_bidi( $zandi_how['outro'] ); ?></p>

				<?php if ( ! empty( $zandi_how['outro_cta'] ) ) : ?>
					<?php
					/*
					 * Ghost, like the hero's «نمونه تدریس رو ببین ▶»: it is
					 * something to look at, and the page keeps its one filled
					 * button for enrolling.
					 */
					?>
					<a class="c-btn c-btn--ghost" href="#sample-lesson"><?php echo esc_html( $zandi_how['outro_cta'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
