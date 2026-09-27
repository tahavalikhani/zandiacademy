<?php
/**
 * The four cards: what the gift holds, as numbers rather than a paragraph.
 *
 * The figures are read from zandi_podcast_facts() through
 * zandi_free_podcast_cards(), so this page and /podcast/ cannot disagree about
 * how long the podcast is. The fourth card is the offer itself.
 *
 * A list, because it is one: four facts, and a screen reader says so.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy  = zandi_free_podcast_copy();
$zandi_cards = zandi_free_podcast_cards();

if ( ! $zandi_cards ) {
	return;
}
?>

<section class="fp-cards-section" aria-label="<?php echo esc_attr( $zandi_copy['cards_label'] ); ?>">
	<div class="container">
		<ul class="fp-cards">
			<?php foreach ( $zandi_cards as $zandi_card ) : ?>
				<li class="fp-card">
					<span class="fp-card__icon fp-card__icon--<?php echo esc_attr( $zandi_card['tone'] ); ?>">
						<?php zandi_icon( $zandi_card['icon'], array( 'stroke' => 1.9 ) ); ?>
					</span>
					<p class="fp-card__value"><?php echo zandi_bidi( $zandi_card['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- zandi_bidi() escapes. ?></p>
					<?php if ( '' !== (string) $zandi_card['note'] ) : ?>
						<p class="fp-card__note"><?php echo esc_html( $zandi_card['note'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
