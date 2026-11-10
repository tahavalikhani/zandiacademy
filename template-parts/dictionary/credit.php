<?php
/**
 * The attribution line. REQUIRED BY THE DATA'S LICENCES — do not remove it.
 *
 * Wiktionary (CC BY-SA) and Tatoeba (CC BY 2.0 FR) both require credit on the
 * page that uses their content, and CC BY-SA requires saying the adapted data
 * is under the same licence. The copy holds two links of its own, so it goes
 * through wp_kses() with exactly the attributes it uses.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_dictionary_copy();
?>
<footer class="dict-credit">
	<div class="container">
		<p class="dict-credit__line">
			<?php
			echo wp_kses(
				$zandi_copy['credit'],
				array(
					'a' => array(
						'href' => true,
						'rel'  => true,
					),
				)
			);
			?>
		</p>
		<p class="dict-credit__note"><?php echo esc_html( $zandi_copy['credit_note'] ); ?></p>
	</div>
</footer>
