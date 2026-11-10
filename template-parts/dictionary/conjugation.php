<?php
/**
 * A verb's full conjugation, from French Wiktionary's own tables.
 *
 * The seven tenses a learner meets first are drawn open; the literary and
 * compound rest sit in a <details> below, closed — they are there for the
 * reader who needs them and out of the way of the one who does not.
 *
 * Each tense carries its Persian name and one line on when it is used. The
 * forms themselves are a `dir="ltr"` list, so «j’ai pris» reads left to right
 * on a right-to-left page.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy   = zandi_dictionary_copy();
$zandi_state  = zandi_dictionary_state();
$zandi_word   = $zandi_state['word'];
$zandi_conj   = $zandi_state['conj'];
$zandi_tenses = zandi_dictionary_tenses();

$zandi_meanings = array();

foreach ( $zandi_state['page']['b'] as $zandi_block ) {
	if ( 'verb' === $zandi_block['p'] ) {
		foreach ( $zandi_block['m'] as $zandi_group ) {
			$zandi_meanings = array_merge( $zandi_meanings, $zandi_group[0] );
		}
	}
}

$zandi_meanings = array_slice( array_unique( $zandi_meanings ), 0, 4 );

$zandi_render = function ( $name ) use ( $zandi_conj, $zandi_tenses ) {
	if ( empty( $zandi_conj['t'][ $name ] ) || ! isset( $zandi_tenses[ $name ] ) ) {
		return;
	}

	$tense = $zandi_tenses[ $name ];
	?>
	<section class="dict-tense">
		<h3 class="dict-tense__title">
			<?php echo esc_html( $tense['fa'] ); ?>
			<span class="dict-tense__fr" lang="fr" dir="ltr"><?php echo esc_html( $tense['fr'] ); ?></span>
		</h3>
		<p class="dict-tense__use"><?php echo esc_html( $tense['use'] ); ?></p>
		<ul class="dict-conj__rows" dir="ltr" lang="fr">
			<?php foreach ( $zandi_conj['t'][ $name ] as $row ) : ?>
				<li class="dict-conj__row"><?php echo zandi_dictionary_conj_form( $row[0], $row[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?></li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
};
?>
<article class="dict-entry dict-conj">
	<header class="dict-entry__head">
		<h1 class="dict-entry__word dict-conj__title">
			<?php echo esc_html( $zandi_copy['conj_title'] ); ?>
			<span lang="fr" dir="ltr"><?php echo esc_html( $zandi_word ); ?></span>
		</h1>
		<button class="dict-listen" type="button" data-say="<?php echo esc_attr( $zandi_word ); ?>" hidden>
			<?php zandi_icon( 'speaker', array( 'class' => 'dict-listen__icon' ) ); ?>
			<span class="screen-reader-text"><?php echo esc_html( $zandi_copy['listen'] ); ?></span>
		</button>
	</header>

	<p class="dict-conj__meta">
		<?php if ( $zandi_meanings ) : ?>
			<span><?php echo esc_html( implode( '، ', $zandi_meanings ) ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $zandi_conj['aux'] ) ) : ?>
			<span><?php echo esc_html( $zandi_copy['aux'] ); ?>: <b lang="fr" dir="ltr"><?php echo esc_html( $zandi_conj['aux'] ); ?></b></span>
		<?php endif; ?>
		<?php if ( ! empty( $zandi_conj['pp'] ) ) : ?>
			<span><?php echo esc_html( $zandi_copy['pp'] ); ?>: <b lang="fr" dir="ltr"><?php echo esc_html( $zandi_conj['pp'] ); ?></b></span>
		<?php endif; ?>
		<?php if ( ! empty( $zandi_conj['ppr'] ) ) : ?>
			<span><?php echo esc_html( $zandi_copy['ppr'] ); ?>: <b lang="fr" dir="ltr"><?php echo esc_html( $zandi_conj['ppr'] ); ?></b></span>
		<?php endif; ?>
	</p>
	<p><a class="dict-conj__back" href="<?php echo esc_url( zandi_dictionary_url( $zandi_word ) ); ?>"><?php echo esc_html( $zandi_copy['back_to_word'] ); ?></a></p>

	<div class="dict-tenses">
		<?php
		foreach ( $zandi_tenses as $zandi_name => $zandi_tense ) {
			if ( $zandi_tense['main'] ) {
				$zandi_render( $zandi_name );
			}
		}
		?>
	</div>

	<details class="dict-more">
		<summary><?php echo esc_html( $zandi_copy['conj_more'] ); ?></summary>
		<div class="dict-tenses">
			<?php
			foreach ( $zandi_tenses as $zandi_name => $zandi_tense ) {
				if ( ! $zandi_tense['main'] ) {
					$zandi_render( $zandi_name );
				}
			}
			?>
		</div>
	</details>
</article>
