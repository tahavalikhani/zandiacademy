<?php
/**
 * Nothing fits, several words fit, or the data is missing.
 *
 * A word address that finds nothing answers 404 (zandi_dictionary_status()),
 * but the page is still the dictionary with its search box, not the theme's
 * 404 — somebody who mistyped one letter should be one click from the word.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy  = zandi_dictionary_copy();
$zandi_state = zandi_dictionary_state();
$zandi_query = isset( $zandi_state['query'] ) ? $zandi_state['query'] : '';
$zandi_rows  = array();

if ( 'choices' === $zandi_state['view'] ) {
	foreach ( $zandi_state['words'] as $zandi_word ) {
		$zandi_page   = zandi_dict_fr_page( $zandi_word );
		$zandi_rows[] = array( $zandi_word, $zandi_page ? $zandi_page['b'][0]['m'][0][0][0] : '' );
	}
} elseif ( 'notfound' === $zandi_state['view'] ) {
	foreach ( zandi_dict_suggestions( $zandi_query ) as $zandi_row ) {
		$zandi_rows[] = array( $zandi_row[0], $zandi_row[1] );
	}
}
?>
<div class="dict-entry dict-none">
	<?php if ( 'unavailable' === $zandi_state['view'] ) : ?>
		<p class="dict-entry__lead"><?php echo esc_html( $zandi_copy['unavailable'] ); ?></p>
	<?php else : ?>
		<h1 class="dict-none__title"><?php echo esc_html( 'choices' === $zandi_state['view'] ? $zandi_copy['choices_title'] : $zandi_copy['notfound_title'] ); ?></h1>
		<p class="dict-entry__lead">
			<?php
			$zandi_template = 'choices' === $zandi_state['view'] ? $zandi_copy['choices_lead'] : ( $zandi_rows ? $zandi_copy['notfound_lead'] : $zandi_copy['notfound_none'] );
			printf( esc_html( $zandi_template ), '<span dir="auto">' . esc_html( $zandi_query ) . '</span>' );
			?>
		</p>

		<?php if ( $zandi_rows ) : ?>
			<ul class="dict-list">
				<?php foreach ( $zandi_rows as $zandi_row ) : ?>
					<li>
						<a class="dict-list__item" href="<?php echo esc_url( zandi_dictionary_url( $zandi_row[0] ) ); ?>">
							<span class="dict-list__word" dir="auto"><?php echo esc_html( $zandi_row[0] ); ?></span>
							<?php if ( '' !== $zandi_row[1] ) : ?>
								<span class="dict-list__gloss" dir="auto"><?php echo esc_html( $zandi_row[1] ); ?></span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>
</div>
