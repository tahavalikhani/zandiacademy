<?php
/**
 * A French word: what it means in Persian, how it sounds, how it is used.
 *
 * One page per spelling. A spelling with two parts of speech («livre» the noun
 * and «livre» the other noun, «pas» the noun and the adverb) is one page with a
 * block for each, the way a printed dictionary sets them.
 *
 * Every Persian meaning links to its own Persian page, so a reader can go from
 * «maison» to «خانه» and on to «chez» without typing.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy  = zandi_dictionary_copy();
$zandi_state = zandi_dictionary_state();
$zandi_page  = $zandi_state['page'];
$zandi_word  = $zandi_state['word'];
?>
<article class="dict-entry">
	<header class="dict-entry__head">
		<h1 class="dict-entry__word" lang="fr" dir="ltr"><?php echo esc_html( $zandi_word ); ?></h1>
		<button class="dict-listen" type="button" data-say="<?php echo esc_attr( $zandi_word ); ?>" hidden>
			<?php zandi_icon( 'speaker', array( 'class' => 'dict-listen__icon' ) ); ?>
			<span class="screen-reader-text"><?php echo esc_html( $zandi_copy['listen'] ); ?></span>
		</button>
	</header>

	<?php if ( ! empty( $zandi_state['from'] ) ) : ?>
		<p class="dict-entry__from">
			<?php
			printf(
				esc_html( $zandi_copy['from_note'] ),
				'<span lang="fr" dir="ltr">' . esc_html( $zandi_state['from'] ) . '</span>',
				'<span lang="fr" dir="ltr">' . esc_html( $zandi_word ) . '</span>'
			);
			?>
		</p>
	<?php endif; ?>

	<?php foreach ( $zandi_page['b'] as $zandi_block ) : ?>
		<?php
		$zandi_gender  = isset( $zandi_block['g'] ) ? $zandi_block['g'] : '';
		$zandi_label   = zandi_dictionary_pos_label( $zandi_block['p'], $zandi_gender );
		$zandi_article = 'noun' === $zandi_block['p'] ? zandi_dictionary_article( $zandi_word, $zandi_gender ) : '';
		?>
		<section class="dict-block">
			<h2 class="dict-block__head">
				<?php if ( $zandi_label ) : ?>
					<span class="dict-tag"><?php echo esc_html( $zandi_label ); ?></span>
				<?php endif; ?>
				<?php if ( $zandi_article ) : ?>
					<span class="dict-block__with-article" lang="fr" dir="ltr"><?php echo esc_html( $zandi_article . $zandi_word ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $zandi_block['i'] ) ) : ?>
					<span class="dict-ipa" dir="ltr" lang="fr-Latn-fonipa">/<?php echo esc_html( $zandi_block['i'] ); ?>/</span>
				<?php endif; ?>
			</h2>

			<ol class="dict-senses">
				<?php foreach ( $zandi_block['m'] as $zandi_group ) : ?>
					<li class="dict-sense">
						<p class="dict-sense__fa">
							<?php
							$zandi_links = array();

							foreach ( $zandi_group[0] as $zandi_fa ) {
								$zandi_links[] = '<a href="' . esc_url( zandi_dictionary_url( $zandi_fa ) ) . '">' . esc_html( $zandi_fa ) . '</a>';
							}

							echo implode( '، ', $zandi_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each part escaped above.
							?>
						</p>
						<?php if ( '' !== $zandi_group[1] ) : ?>
							<p class="dict-sense__def" lang="fr" dir="ltr"><?php echo esc_html( $zandi_group[1] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>

			<?php if ( ! empty( $zandi_block['f'] ) ) : ?>
				<p class="dict-forms">
					<span class="dict-forms__label"><?php echo esc_html( $zandi_copy['forms'] ); ?>:</span>
					<?php foreach ( $zandi_block['f'] as $zandi_form ) : ?>
						<span class="dict-forms__item">
							<span class="dict-forms__kind"><?php echo esc_html( 'pl' === $zandi_form[1] ? $zandi_copy['plural'] : $zandi_copy['feminine'] ); ?></span>
							<span lang="fr" dir="ltr"><?php echo esc_html( $zandi_form[0] ); ?></span>
						</span>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $zandi_block['d'] ) ) : ?>
				<details class="dict-defs">
					<summary><?php echo esc_html( $zandi_copy['definitions'] ); ?></summary>
					<ol class="dict-defs__list" lang="fr" dir="ltr">
						<?php foreach ( $zandi_block['d'] as $zandi_def ) : ?>
							<li><?php echo esc_html( $zandi_def ); ?></li>
						<?php endforeach; ?>
					</ol>
				</details>
			<?php endif; ?>
		</section>
	<?php endforeach; ?>

	<?php if ( ! empty( $zandi_page['x'] ) ) : ?>
		<section class="dict-section">
			<h2 class="dict-section__title"><?php echo esc_html( $zandi_copy['examples'] ); ?></h2>
			<?php get_template_part( 'template-parts/dictionary/examples', null, array( 'examples' => $zandi_page['x'] ) ); ?>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $zandi_page['c'] ) ) : ?>
		<section class="dict-section dict-present">
			<div class="dict-present__head">
				<h2 class="dict-section__title">
					<?php echo esc_html( $zandi_copy['present_title'] ); ?>
					<span class="dict-section__sub" lang="fr" dir="ltr">(présent)</span>
				</h2>
				<a class="dict-present__link" href="<?php echo esc_url( zandi_dictionary_url( $zandi_word, true ) ); ?>"><?php echo esc_html( $zandi_copy['conj_link'] ); ?></a>
			</div>
			<ul class="dict-conj__rows dict-conj__rows--two" dir="ltr" lang="fr">
				<?php foreach ( $zandi_page['c']['present'] as $zandi_row ) : ?>
					<li class="dict-conj__row"><?php echo zandi_dictionary_conj_form( $zandi_row[0], $zandi_row[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! empty( $zandi_page['c']['aux'] ) || ! empty( $zandi_page['c']['pp'] ) ) : ?>
				<p class="dict-present__meta">
					<?php if ( ! empty( $zandi_page['c']['aux'] ) ) : ?>
						<span><?php echo esc_html( $zandi_copy['aux'] ); ?>: <b lang="fr" dir="ltr"><?php echo esc_html( $zandi_page['c']['aux'] ); ?></b></span>
					<?php endif; ?>
					<?php if ( ! empty( $zandi_page['c']['pp'] ) ) : ?>
						<span><?php echo esc_html( $zandi_copy['pp'] ); ?>: <b lang="fr" dir="ltr"><?php echo esc_html( $zandi_page['c']['pp'] ); ?></b></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</article>
