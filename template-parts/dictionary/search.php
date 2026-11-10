<?php
/**
 * The search band at the top of every dictionary page.
 *
 * A plain GET form: with scripts off it still searches, and the server
 * redirects a hit to its word. dictionary.js adds suggestions while typing and
 * the row of French letters — both ship `hidden` and are revealed only by the
 * script that makes them work (CLAUDE.md, «A control that only works with
 * JavaScript ships hidden»).
 *
 * The suggestion files are static JSON beside the theme, read by the browser
 * straight from the web server: typing never runs PHP.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy  = zandi_dictionary_copy();
$zandi_state = zandi_dictionary_state();
$zandi_value = '';

if ( isset( $zandi_state['query'] ) ) {
	$zandi_value = $zandi_state['query'];
} elseif ( isset( $zandi_state['word'] ) ) {
	$zandi_value = $zandi_state['word'];
}

$zandi_is_home = 'home' === $zandi_state['view'];
$zandi_trail   = array();

if ( ! $zandi_is_home ) {
	$zandi_trail = array(
		array(
			'label' => 'خانه',
			'url'   => home_url( '/' ),
		),
		array(
			'label' => $zandi_copy['eyebrow'],
			'url'   => zandi_dictionary_url(),
		),
	);

	if ( 'conj' === $zandi_state['view'] ) {
		$zandi_trail[] = array(
			'label' => $zandi_state['word'],
			'url'   => zandi_dictionary_url( $zandi_state['word'] ),
		);
		$zandi_trail[] = array( 'label' => $zandi_copy['conj_title'] );
	} elseif ( isset( $zandi_state['word'] ) ) {
		$zandi_trail[] = array( 'label' => $zandi_state['word'] );
	}
}

$zandi_letters = array( 'é', 'è', 'ê', 'à', 'â', 'ç', 'ô', 'û', 'ù', 'î', 'ï', 'ë', 'œ' );
?>
<section class="dict-hero<?php echo $zandi_is_home ? ' dict-hero--home' : ''; ?>">
	<div class="container">
		<?php if ( $zandi_is_home ) : ?>
			<p class="dict-hero__eyebrow"><?php echo esc_html( $zandi_copy['eyebrow'] ); ?></p>
			<h1 class="dict-hero__title"><?php echo esc_html( $zandi_copy['title'] ); ?></h1>
			<p class="dict-hero__lead"><?php echo esc_html( $zandi_copy['lead'] ); ?></p>
		<?php elseif ( count( $zandi_trail ) > 2 ) : ?>
			<?php zandi_breadcrumb( $zandi_trail ); ?>
		<?php endif; ?>

		<form
			class="dict-search"
			role="search"
			method="get"
			action="<?php echo esc_url( zandi_pretty_permalinks() ? zandi_dictionary_url() : home_url( '/' ) ); ?>"
			data-suggest-base="<?php echo esc_url( get_theme_file_uri( 'assets/dictionary/' ) ); ?>"
			data-suggest-ver="<?php echo esc_attr( zandi_dictionary_data_version() ); ?>"
			data-word-url="<?php echo esc_url( zandi_dictionary_url( '__WORD__' ) ); ?>"
		>
			<?php if ( ! zandi_pretty_permalinks() ) : ?>
				<input type="hidden" name="zandi_dictionary" value="1">
			<?php endif; ?>

			<label class="dict-search__label" for="dict-q"><?php echo esc_html( $zandi_copy['search_label'] ); ?></label>

			<div class="dict-search__row">
				<div class="dict-search__field">
					<?php zandi_icon( 'search', array( 'class' => 'dict-search__icon' ) ); ?>
					<input
						id="dict-q"
						class="dict-search__input"
						type="search"
						name="q"
						value="<?php echo esc_attr( $zandi_value ); ?>"
						dir="auto"
						autocomplete="off"
						autocapitalize="off"
						spellcheck="false"
						enterkeyhint="search"
						role="combobox"
						aria-autocomplete="list"
						aria-expanded="false"
						aria-controls="dict-suggest"
						aria-describedby="dict-hint"
						required
					>
				</div>
				<button class="btn btn--primary btn--lg dict-search__submit" type="submit"><?php echo esc_html( $zandi_copy['search_button'] ); ?></button>
			</div>

			<ul id="dict-suggest" class="dict-suggest" role="listbox" aria-label="<?php echo esc_attr( $zandi_copy['suggest_label'] ); ?>" hidden></ul>

			<div class="dict-accents" role="group" aria-label="<?php echo esc_attr( $zandi_copy['accents_label'] ); ?>" hidden>
				<?php foreach ( $zandi_letters as $zandi_letter ) : ?>
					<button class="dict-accents__key" type="button" lang="fr" data-letter="<?php echo esc_attr( $zandi_letter ); ?>"><?php echo esc_html( $zandi_letter ); ?></button>
				<?php endforeach; ?>
			</div>

			<p id="dict-hint" class="dict-search__hint"><?php echo zandi_bidi( $zandi_copy['search_hint'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside zandi_bidi(). ?></p>
		</form>
	</div>
</section>
