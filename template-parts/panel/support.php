<?php
/**
 * Support — Telegram, because that is where it actually happens.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy    = zandi_panel_copy();
$zandi_contact = zandi_contact();
?>

<section class="panel-section" id="support" aria-labelledby="support-title">
	<h2 class="panel-section__title" id="support-title"><?php echo esc_html( $zandi_copy['support_title'] ); ?></h2>

	<div class="card panel-card">
		<p><?php echo esc_html( $zandi_copy['support_body'] ); ?></p>

		<?php
		zandi_button(
			array(
				'label' => $zandi_copy['support_cta'],
				'url'   => $zandi_contact['telegram'],
				'size'  => 'md',
				'attrs' => array( 'rel' => 'noopener' ),
			)
		);
		?>

		<p class="panel-card__note" dir="ltr"><?php echo esc_html( $zandi_contact['telegram_name'] ); ?></p>
	</div>
</section>
