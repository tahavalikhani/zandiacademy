<?php
/**
 * The dictionary's side column.
 *
 * «جست‌وجوهای اخیر» ships `hidden`: the list lives in the visitor's own
 * browser (localStorage), so only dictionary.js can fill it, and with scripts
 * off there is nothing to show. Nothing is sent to the server, and no cookie
 * is set, so every page stays cacheable.
 *
 * `zandi_dictionary_aside` is where the course promotions the owner plans
 * will go — the hook renders nothing until something is attached to it.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_dictionary_copy();
?>
<section class="dict-card dict-recent" hidden>
	<h2 class="dict-card__title"><?php echo esc_html( $zandi_copy['recent_title'] ); ?></h2>
	<ul class="dict-recent__list"></ul>
	<p class="dict-card__note"><?php echo esc_html( $zandi_copy['recent_note'] ); ?></p>
</section>

<?php
/**
 * Fires in the dictionary's side column, after the recent searches.
 *
 * @param array $state What the page is showing — see zandi_dictionary_state().
 */
do_action( 'zandi_dictionary_aside', zandi_dictionary_state() );
