<?php
/**
 * What a student sees when they come back from the bank.
 *
 * Two outcomes, and WooCommerce treats them as the same page with a different
 * sentence at the top. They are not the same moment at all:
 *
 *   paid    — the most important thing to say is not «سفارش ثبت شد», it is
 *             where the course is now. So the receipt is secondary and the
 *             route into the panel is the primary action.
 *   failed  — nobody wants an order number. They want to know whether they
 *             were charged, and how to try again.
 *
 * The default template answers neither. It prints a status line and a table of
 * billing fields — most of which this checkout no longer collects.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// /contact/, not a messaging app — the channel behind it can change.
$zandi_support = function_exists( 'zandi_support_url' ) ? zandi_support_url() : home_url( '/contact/' );

if ( ! $order ) {
	?>
	<div class="woo-result">
		<h2 class="woo-result__title"><?php echo esc_html( 'سفارشی پیدا نشد' ); ?></h2>
		<p class="woo-result__body"><?php echo esc_html( 'این صفحه سفارشی برای نمایش نداره. اگر پرداخت کردی و اینجا رسیدی، از صفحه تماس بگو تا بررسی کنم.' ); ?></p>
	</div>
	<?php
	return;
}

$zandi_failed = $order->has_status( array( 'failed', 'cancelled' ) );
?>

<?php if ( $zandi_failed ) : ?>

	<div class="woo-result woo-result--failed">
		<span class="woo-result__mark" aria-hidden="true"><?php zandi_icon( 'close' ); ?></span>

		<h2 class="woo-result__title"><?php echo esc_html( 'پرداخت انجام نشد' ); ?></h2>

		<p class="woo-result__body">
			<?php echo esc_html( 'پولی از حسابت کم نشده. اگر مبلغ کسر شده، بانک ظرف ۷۲ ساعت خودش برمی‌گردونه.' ); ?>
		</p>

		<div class="woo-result__actions">
			<?php
			zandi_button(
				array(
					'label' => 'یک بار دیگه امتحان کن',
					'url'   => $order->get_checkout_payment_url(),
					'size'  => 'md',
				)
			);

			zandi_button(
				array(
					'label'   => 'پرسیدن سوال',
					'url'     => $zandi_support,
					'variant' => 'secondary',
					'size'    => 'md',
				)
			);
			?>
		</div>

		<p class="woo-result__meta">
			<?php
			printf(
				/* translators: %s: order number. */
				esc_html( 'شماره سفارش: %s' ),
				esc_html( zandi_fa_digits( $order->get_order_number() ) )
			);
			?>
		</p>
	</div>

<?php else : ?>

	<div class="woo-result woo-result--paid">
		<span class="woo-result__mark" aria-hidden="true"><?php zandi_icon( 'check' ); ?></span>

		<h2 class="woo-result__title"><?php echo esc_html( 'پرداختت انجام شد' ); ?></h2>

		<p class="woo-result__body">
			<?php echo esc_html( 'دوره‌ات فعال شد و از همین حالا توی پنل خودته. کلید لایسنس اسپات پلیر هم همون‌جاست.' ); ?>
		</p>

		<div class="woo-result__actions">
			<?php
			zandi_button(
				array(
					'label' => 'رفتن به دوره‌هام',
					'url'   => zandi_panel_url() . '#my-courses',
					'size'  => 'md',
				)
			);
			?>
		</div>

		<?php
		/*
		 * The receipt, below the action rather than above it. Kept short: the
		 * three facts a student might screenshot, not the billing table the
		 * default prints — this checkout collects no address to put in it.
		 */
		?>
		<dl class="woo-result__details">
			<div>
				<dt><?php echo esc_html( 'شماره سفارش' ); ?></dt>
				<dd><?php echo esc_html( zandi_fa_digits( $order->get_order_number() ) ); ?></dd>
			</div>

			<div>
				<dt><?php echo esc_html( 'تاریخ' ); ?></dt>
				<dd><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></dd>
			</div>

			<div>
				<dt><?php echo esc_html( 'مبلغ' ); ?></dt>
				<dd><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd>
			</div>

			<?php if ( $order->get_payment_method_title() ) : ?>
				<div>
					<dt><?php echo esc_html( 'روش پرداخت' ); ?></dt>
					<dd><?php echo esc_html( $order->get_payment_method_title() ); ?></dd>
				</div>
			<?php endif; ?>
		</dl>

		<p class="woo-result__meta">
			<?php echo esc_html( 'سؤالی داشتی؟' ); ?>
			<a href="<?php echo esc_url( $zandi_support ); ?>"><?php echo esc_html( 'از صفحه تماس بپرس' ); ?></a>
		</p>
	</div>

	<?php
	/*
	 * The licence card, when the SpotPlayer plugin has one ready. It renders
	 * its own copy and download buttons, so the theme asks rather than rebuilds.
	 * A licence issued asynchronously will not exist yet on this page load —
	 * the panel is where it always appears, which is why the button above goes
	 * there rather than promising it here.
	 */
	if ( function_exists( 'zandi_spotplayer_render_licenses' ) && function_exists( 'zandi_spotplayer_get_order_licenses' ) ) {
		$zandi_licences = zandi_spotplayer_get_order_licenses( $order->get_id() );

		if ( $zandi_licences ) {
			/*
			 * The helper takes an args array and RETURNS markup rather than
			 * echoing it, so both halves of that matter — passing the licences
			 * positionally would hand the whole list in as `$args` and print
			 * nothing at all.
			 */
			$zandi_licence_html = zandi_spotplayer_render_licenses(
				array(
					'licenses' => $zandi_licences,
					'context'  => 'thankyou',
				)
			);

			if ( '' !== $zandi_licence_html ) {
				echo '<div class="woo-result__licences">';
				echo $zandi_licence_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The plugin's own markup, escaped at source.
				echo '</div>';
			}
		}
	}
	?>

<?php endif; ?>

<?php
/**
 * Kept so the SpotPlayer plugin and any gateway hooked here still run.
 *
 * @param int $order_id Order ID.
 */
do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
do_action( 'woocommerce_thankyou', $order->get_id() );
