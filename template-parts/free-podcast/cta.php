<?php
/**
 * The closing band: the same button again, for somebody who scrolled past it.
 *
 * Rendered for signed-out visitors only — see template-free-podcast.php. The
 * one place the podcast's own purple is a ground on this page, with the lime
 * button on it the way the cover pairs the two; everything above sits on cream.
 *
 * Centred, so `.fp-band` is named in style.css's text-align restore list —
 * without it the plugin-fighting `text-align: start` rule flattens it.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_free_podcast_copy();
$zandi_days = zandi_fa_digits( (string) zandi_free_podcast_days() );
?>

<section class="fp-band-section" aria-labelledby="fp-band-title">
	<div class="container">
		<div class="fp-band">
			<?php zandi_engraving( 'cta' ); ?>

			<p class="fp-band__kicker" dir="ltr" lang="fr"><?php echo esc_html( $zandi_copy['band_kicker'] ); ?></p>
			<h2 class="fp-band__title" id="fp-band-title"><?php echo esc_html( $zandi_copy['band_title'] ); ?></h2>

			<?php
			zandi_button(
				array(
					'label'   => sprintf( $zandi_copy['cta'], $zandi_days ),
					'url'     => zandi_free_podcast_signup_url(),
					'variant' => 'gift-lime',
					'size'    => 'lg',
					'icon'    => zandi_arrow_forward(),
					'class'   => 'fp-cta',
				)
			);
			?>
		</div>
	</div>
</section>
