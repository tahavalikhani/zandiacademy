<?php
/**
 * Three steps, because the second one surprises people.
 *
 * Nobody expects to have to connect a Telegram account after paying, and an
 * unexplained step is where a buyer stops and asks for a refund. Saying it here,
 * before the money, costs three lines.
 *
 * The list marker is `list-style-type: persian` in the stylesheet, not
 * zandi_fa_digits(): the browser draws a counter and PHP cannot reach it, which
 * makes an ordered list the one place Latin digits can still leak onto a
 * Persian page.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_podcast_copy();
?>

<section class="section podcast-how" id="how" aria-labelledby="podcast-how-title">
	<div class="container">
		<?php
		zandi_section_heading(
			array(
				'title' => $zandi_copy['how_title'],
				'id'    => 'podcast-how',
			)
		);
		?>

		<ol class="podcast-how__steps">
			<?php foreach ( (array) $zandi_copy['how_steps'] as $zandi_step ) : ?>
				<li class="podcast-how__step"><?php echo zandi_bidi( $zandi_step ); ?></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
