<?php
/**
 * Support.
 *
 * Sends students to /contact/ rather than naming a messaging app, so the
 * channel behind it can change without touching the panel — see
 * zandi_support_url().
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_panel_copy();
?>

<section class="panel-section" id="support" aria-labelledby="support-title">
	<h2 class="panel-section__title" id="support-title"><?php echo esc_html( $zandi_copy['support_title'] ); ?></h2>

	<div class="card panel-card">
		<p><?php echo esc_html( $zandi_copy['support_body'] ); ?></p>

		<?php
		zandi_button(
			array(
				'label' => $zandi_copy['support_cta'],
				'url'   => zandi_support_url(),
				'size'  => 'md',
			)
		);
		?>
	</div>
</section>
