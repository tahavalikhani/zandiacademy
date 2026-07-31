<?php
/**
 * The empty basket.
 *
 * WooCommerce's default is a grey info bar reading «سبد خرید شما در حال حاضر
 * خالی است» with a button to a shop. This uses `.empty-note` — the same shape
 * «دوره‌های من» draws when a student owns nothing yet — so the two empty states
 * on the site are the same empty state, and it points at the catalogue the
 * student actually wants.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kept so plugins hooked here still run — WooCommerce fires this to let
 * extensions add their own notices to an empty basket.
 */
do_action( 'woocommerce_cart_is_empty' );
?>

<div class="empty-note">
	<p class="empty-note__title">سبد خریدت خالیه.</p>
	<p class="empty-note__body">هنوز دوره‌ای انتخاب نکردی. سه سطح هست و هر کدوم از یه جای مسیر شروع می‌شه.</p>

	<p class="panel-empty__action">
		<?php
		zandi_button(
			array(
				'label' => 'دیدن دوره‌ها',
				'url'   => zandi_section_url( 'courses' ),
				'size'  => 'md',
			)
		);
		?>
	</p>
</div>
