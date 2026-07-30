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

<section class="c-section c-section--tight" id="support" aria-labelledby="support-title">
	<h2 class="c-section__title" id="support-title"><?php echo esc_html( $zandi_copy['support_title'] ); ?></h2>

	<div class="c-card p-card p-card--accent">
		<p><?php echo esc_html( $zandi_copy['support_body'] ); ?></p>

		<a class="c-btn c-btn--primary" href="<?php echo esc_url( $zandi_contact['telegram'] ); ?>" rel="noopener">
			<?php echo esc_html( $zandi_copy['support_cta'] ); ?>
		</a>

		<p class="p-card__note" dir="ltr"><?php echo esc_html( $zandi_contact['telegram_name'] ); ?></p>
	</div>
</section>
