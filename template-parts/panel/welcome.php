<?php
/**
 * Panel greeting.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_panel_copy();
$zandi_user = $args['user'];

// display_name falls back to the login, which for a student is their phone
// number — greeting someone by their own phone number would be absurd.
$zandi_name = $zandi_user->display_name;

if ( ! $zandi_name || zandi_is_valid_phone( $zandi_name ) ) {
	$zandi_name = $zandi_user->first_name;
}
?>

<section class="panel-welcome" aria-labelledby="panel-title">
	<?php zandi_badge( $zandi_copy['eyebrow'], 'rouge' ); ?>

	<h1 class="panel-welcome__title" id="panel-title">
		<?php
		echo $zandi_name
			? zandi_bidi( sprintf( $zandi_copy['greeting'], $zandi_name ) )
			: 'سلام';
		?>
	</h1>

	<p class="panel-welcome__lead"><?php echo esc_html( $zandi_copy['intro'] ); ?></p>

	<nav class="panel-tabs" aria-label="<?php echo esc_attr( $zandi_copy['eyebrow'] ); ?>">
		<?php foreach ( zandi_panel_nav() as $zandi_item ) : ?>
			<a href="<?php echo esc_url( $zandi_item['url'] ); ?>"><?php echo esc_html( $zandi_item['label'] ); ?></a>
		<?php endforeach; ?>
	</nav>
</section>
