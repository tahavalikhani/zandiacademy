<?php
/**
 * Example sentences: the French, then its Persian translation.
 *
 * Each sentence is set on its own element with its own direction — a whole
 * French sentence needs `dir="ltr"` on the ELEMENT, not on a span inside it,
 * or it hangs off the wrong edge (CLAUDE.md, the placement test's rule).
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_examples = isset( $args['examples'] ) ? (array) $args['examples'] : array();
?>
<ul class="dict-examples">
	<?php foreach ( $zandi_examples as $zandi_example ) : ?>
		<li class="dict-example">
			<p class="dict-example__fr" lang="fr" dir="ltr"><?php echo esc_html( $zandi_example[0] ); ?></p>
			<p class="dict-example__fa"><?php echo zandi_bidi( $zandi_example[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside zandi_bidi(). ?></p>
		</li>
	<?php endforeach; ?>
</ul>
