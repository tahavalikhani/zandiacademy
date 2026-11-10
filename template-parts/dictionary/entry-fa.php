<?php
/**
 * A Persian word: its French equivalents, commonest first.
 *
 * Under each equivalent go the OTHER Persian meanings of that French word.
 * That line is what tells a reader which «خانه» each one is: «maison» also
 * means منزل، کاشانه, while «foyer» also means کانون. It is the dictionary's
 * own data read back the other way — nothing on this page is written for it.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy  = zandi_dictionary_copy();
$zandi_state = zandi_dictionary_state();
$zandi_page  = $zandi_state['page'];
?>
<article class="dict-entry dict-entry--fa">
	<header class="dict-entry__head">
		<h1 class="dict-entry__word"><?php echo esc_html( $zandi_page['w'] ); ?></h1>
	</header>
	<p class="dict-entry__lead"><?php echo esc_html( $zandi_copy['fa_lead'] ); ?></p>

	<ul class="dict-equivs">
		<?php foreach ( $zandi_page['fr'] as $zandi_item ) : ?>
			<?php
			list( $zandi_fr, $zandi_pos, $zandi_gender, $zandi_also ) = $zandi_item;
			$zandi_label   = zandi_dictionary_pos_label( $zandi_pos, $zandi_gender );
			$zandi_article = 'noun' === $zandi_pos ? zandi_dictionary_article( $zandi_fr, $zandi_gender ) : '';
			?>
			<li class="dict-equiv">
				<div class="dict-equiv__head">
					<a class="dict-equiv__word" href="<?php echo esc_url( zandi_dictionary_url( $zandi_fr ) ); ?>" lang="fr" dir="ltr">
						<?php if ( $zandi_article ) : ?>
							<span class="dict-equiv__article"><?php echo esc_html( $zandi_article ); ?></span>
						<?php endif; ?>
						<?php echo esc_html( $zandi_fr ); ?>
					</a>
					<?php if ( $zandi_label ) : ?>
						<span class="dict-tag"><?php echo esc_html( $zandi_label ); ?></span>
					<?php endif; ?>
					<button class="dict-listen dict-listen--sm" type="button" data-say="<?php echo esc_attr( $zandi_fr ); ?>" hidden>
						<?php zandi_icon( 'speaker', array( 'class' => 'dict-listen__icon' ) ); ?>
						<span class="screen-reader-text"><?php echo esc_html( $zandi_copy['listen'] . ' ' . $zandi_fr ); ?></span>
					</button>
				</div>
				<?php if ( $zandi_also ) : ?>
					<p class="dict-equiv__also"><?php echo esc_html( $zandi_copy['fa_also'] . ': ' . implode( '، ', $zandi_also ) ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( ! empty( $zandi_page['x'] ) ) : ?>
		<section class="dict-section">
			<h2 class="dict-section__title"><?php echo esc_html( $zandi_copy['examples'] ); ?></h2>
			<?php get_template_part( 'template-parts/dictionary/examples', null, array( 'examples' => $zandi_page['x'] ) ); ?>
		</section>
	<?php endif; ?>
</article>
