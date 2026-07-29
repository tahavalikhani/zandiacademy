<?php
/**
 * Search form.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_search_id = 'search-' . wp_unique_id();
?>

<form role="search" method="get" class="field" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $zandi_search_id ); ?>">جست‌وجو در سایت</label>

	<input
		class="field__control"
		type="search"
		id="<?php echo esc_attr( $zandi_search_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="جست‌وجو…"
	>

	<?php
	zandi_button(
		array(
			'label' => 'جست‌وجو',
			'type'  => 'submit',
			'size'  => 'md',
		)
	);
	?>
</form>
