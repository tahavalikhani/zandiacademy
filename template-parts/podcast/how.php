<?php
/**
 * Three steps, because the second one surprises people.
 *
 * Nobody expects to have to connect a Telegram account after paying, and an
 * unexplained step is where a buyer stops and asks for a refund. Saying it
 * here, before the money, costs three lines.
 *
 * RENDERS A STRIP, NOT A SECTION, and is included from plans.php rather than
 * from the page template. It used to be a full band of its own between the
 * syllabus and the prices — a heading, three sentences, and the vertical
 * padding of a whole section — which made the page longer while separating the
 * explanation from the thing it explains. Sitting directly above the cards it
 * is read at the moment it matters and costs about a fifth of the height.
 *
 * The numbers are drawn by CSS counters rather than zandi_fa_digits(): the
 * browser draws a marker PHP cannot reach, which makes an ordered list the one
 * place Latin digits can still leak onto a Persian page.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_podcast_copy();

if ( empty( $zandi_copy['how_steps'] ) ) {
	return;
}
?>

<div class="podcast-how">
	<h3 class="podcast-how__title">
		<?php zandi_icon( 'telegram' ); ?>
		<span><?php echo esc_html( $zandi_copy['how_title'] ); ?></span>
	</h3>

	<ol class="podcast-how__steps">
		<?php foreach ( (array) $zandi_copy['how_steps'] as $zandi_step ) : ?>
			<li class="podcast-how__step"><?php echo zandi_bidi( $zandi_step ); ?></li>
		<?php endforeach; ?>
	</ol>
</div>
