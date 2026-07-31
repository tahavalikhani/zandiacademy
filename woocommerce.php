<?php
/**
 * The shop, wearing the site's chrome — /shop/ and /product/{slug}/
 *
 * A `woocommerce.php` in the theme root replaces WooCommerce's own page
 * template for the product archive and the single product, the same way
 * template-section.php serves the standalone section pages. Cart, checkout and
 * order-received are ordinary WordPress pages holding shortcodes, so they come
 * through page.php and already inherit this chrome.
 *
 * Ordering only. Everything inside `woocommerce_content()` is WooCommerce's own
 * markup, restyled by assets/css/shop.css rather than duplicated here — there
 * is far less to break on a plugin update that way, and it is the same call the
 * Digits form got in assets/css/panel.css.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

get_header();

$zandi_is_product = is_singular( 'product' );

/*
 * On a single product the hero would repeat the product title that
 * WooCommerce is about to print, so only the archive gets one. The breadcrumb
 * still runs on both — it is the only way back to the catalogue.
 */
$zandi_trail = array(
	array(
		'label' => 'خانه',
		'url'   => home_url( '/' ),
	),
);

if ( $zandi_is_product ) {
	$zandi_trail[] = array(
		'label' => 'دوره‌ها',
		'url'   => zandi_section_url( 'courses' ),
	);
	$zandi_trail[] = array( 'label' => get_the_title() );
} else {
	$zandi_trail[] = array( 'label' => 'دوره‌ها' );
}
?>

<main id="main">
	<div class="page-hero">
		<div class="container">
			<?php zandi_breadcrumb( $zandi_trail ); ?>

			<?php if ( ! $zandi_is_product ) : ?>
				<h1 class="page-hero__title"><?php echo zandi_bidi( 'دوره‌ها' ); ?></h1>
				<p class="page-hero__lead"><?php echo zandi_bidi( 'سه سطح، از حرف اول تا جایی که فرانسه زبان خودت بشه. هر دوره یک بار خریداری می‌شه و دسترسیش همیشگیه.' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="section">
		<div class="container">
			<?php woocommerce_content(); ?>
		</div>
	</div>
</main>

<?php
get_footer();
