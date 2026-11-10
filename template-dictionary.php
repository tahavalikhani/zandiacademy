<?php
/**
 * واژه‌نامه — /dictionary/ and every word under it.
 *
 * View selection only. What each address shows is worked out once by
 * zandi_dictionary_state() in inc/dictionary.php; every partial reads it.
 *
 *   home        the search box and two short lists
 *   fr          a French word: meanings, definitions, examples, present tense
 *   fa          a Persian word: its French equivalents
 *   conj        a verb's full conjugation
 *   choices     a search several words fit
 *   notfound    nothing fits (answers 404 when it is a word address)
 *   unavailable the data files are missing — an honest empty state
 *
 * No `.reveal` anywhere on these pages: the word IS the content above the
 * fold, and hiding it until a script runs is the slow-largest-paint mistake
 * CLAUDE.md records for the homepage hero.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_state = zandi_dictionary_state();
$zandi_word  = isset( $zandi_state['word'] ) ? $zandi_state['word'] : '';

get_header();
?>

<main id="main" class="dict-page" data-dict-view="<?php echo esc_attr( $zandi_state['view'] ); ?>"<?php echo $zandi_word && 'fa' !== $zandi_state['view'] ? ' data-dict-word="' . esc_attr( $zandi_word ) . '"' : ''; ?><?php echo 'fa' === $zandi_state['view'] ? ' data-dict-word-fa="' . esc_attr( $zandi_word ) . '"' : ''; ?>>
	<?php get_template_part( 'template-parts/dictionary/search' ); ?>

	<div class="container dict-layout">
		<div class="dict-layout__main">
			<?php
			switch ( $zandi_state['view'] ) {
				case 'fr':
					get_template_part( 'template-parts/dictionary/entry-fr' );
					break;
				case 'fa':
					get_template_part( 'template-parts/dictionary/entry-fa' );
					break;
				case 'conj':
					get_template_part( 'template-parts/dictionary/conjugation' );
					break;
				case 'choices':
				case 'notfound':
				case 'unavailable':
					get_template_part( 'template-parts/dictionary/not-found' );
					break;
				default:
					get_template_part( 'template-parts/dictionary/home' );
			}
			?>
		</div>

		<aside class="dict-layout__aside">
			<?php get_template_part( 'template-parts/dictionary/aside' ); ?>
		</aside>
	</div>

	<?php get_template_part( 'template-parts/dictionary/credit' ); ?>
</main>

<?php
get_footer();
