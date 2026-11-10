<?php
/**
 * The dictionary's front page under the search box: what it holds, and two
 * short lists to start from.
 *
 * Both lists are chosen by the build script from how often each word occurs in
 * Tatoeba's French sentences — not picked by hand, so they stay true when the
 * data is rebuilt.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_dictionary_copy();
$zandi_meta = zandi_dict_read( 'meta' );
?>
<div class="dict-home">
	<?php if ( ! empty( $zandi_meta['counts'] ) ) : ?>
		<p class="dict-home__stats">
			<?php
			printf(
				esc_html( $zandi_copy['stats'] ),
				esc_html( zandi_fa_number( (int) $zandi_meta['counts']['fr'] ) ),
				esc_html( zandi_fa_number( (int) $zandi_meta['counts']['fa'] ) ),
				esc_html( zandi_fa_number( (int) $zandi_meta['counts']['verbs'] ) )
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $zandi_meta['common'] ) ) : ?>
		<section class="dict-section">
			<h2 class="dict-section__title"><?php echo esc_html( $zandi_copy['common_title'] ); ?></h2>
			<ul class="dict-chips">
				<?php foreach ( $zandi_meta['common'] as $zandi_item ) : ?>
					<li>
						<a class="dict-chip" href="<?php echo esc_url( zandi_dictionary_url( $zandi_item[0] ) ); ?>">
							<span class="dict-chip__word" lang="fr" dir="ltr"><?php echo esc_html( $zandi_item[0] ); ?></span>
							<span class="dict-chip__gloss"><?php echo esc_html( $zandi_item[1] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $zandi_meta['verbs'] ) ) : ?>
		<section class="dict-section">
			<h2 class="dict-section__title"><?php echo esc_html( $zandi_copy['verbs_title'] ); ?></h2>
			<ul class="dict-chips">
				<?php foreach ( $zandi_meta['verbs'] as $zandi_item ) : ?>
					<li>
						<a class="dict-chip" href="<?php echo esc_url( zandi_dictionary_url( $zandi_item[0], true ) ); ?>">
							<span class="dict-chip__word" lang="fr" dir="ltr"><?php echo esc_html( $zandi_item[0] ); ?></span>
							<span class="dict-chip__gloss"><?php echo esc_html( $zandi_item[1] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</div>
