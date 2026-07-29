<?php
/**
 * Closing call to action.
 *
 * The form posts to admin-post.php, so it degrades to a normal round trip with
 * JavaScript off; theme.js upgrades it to a fetch when available. Wire the
 * handler in `zandi_handle_booking()` (functions.php) to a CRM, an email or a
 * form plugin — it is the only network seam in the theme.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$cta = zandi_final_cta();
?>

<section class="cta" id="register" aria-labelledby="register-title">
	<div class="container">
		<div class="cta__panel reveal reveal--scale">
			<?php
			zandi_engraving( 'cta' );
			zandi_tricolore();
			?>

			<div class="cta__inner">
				<div class="cta__content">
					<h2 class="cta__title" id="register-title"><?php echo esc_html( $cta['title'] ); ?></h2>

					<p class="cta__description"><?php echo esc_html( $cta['description'] ); ?></p>

					<div class="cta__actions">
						<?php
						zandi_button(
							array(
								'label'   => $cta['primary']['label'],
								'url'     => $cta['primary']['url'],
								'variant' => 'on-dark',
								'size'    => 'lg',
								'class'   => 'btn--block btn--block-mobile',
								'icon'    => zandi_arrow_forward(),
							)
						);

						zandi_button(
							array(
								'label'   => $cta['secondary']['label'],
								'url'     => $cta['secondary']['url'],
								'variant' => 'outline-on-dark',
								'size'    => 'lg',
								'class'   => 'btn--block btn--block-mobile',
							)
						);
						?>
					</div>
				</div>

				<div class="cta__form-wrap<?php echo zandi_booking_confirmed() ? ' is-submitted' : ''; ?>" data-booking>
					<form
						class="cta__form"
						method="post"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						novalidate
					>
						<input type="hidden" name="action" value="zandi_booking">
						<?php wp_nonce_field( 'zandi_booking', 'zandi_booking_nonce' ); ?>

						<p class="cta__form-title">رزرو جلسه مشاوره رایگان</p>

						<p class="field field--on-dark">
							<label class="field__label" for="zandi-name">نام و نام خانوادگی</label>
							<input
								class="field__control"
								type="text"
								id="zandi-name"
								name="zandi_name"
								autocomplete="name"
								placeholder="مثلاً سارا مرادی"
								required
							>
						</p>

						<p class="field field--on-dark">
							<label class="field__label" for="zandi-phone">شماره تماس</label>
							<input
								class="field__control"
								type="tel"
								id="zandi-phone"
								name="zandi_phone"
								inputmode="tel"
								autocomplete="tel"
								placeholder="<?php echo esc_attr( zandi_fa_digits( '09120000000' ) ); ?>"
								aria-describedby="zandi-phone-hint"
								required
							>
							<span class="field__hint" id="zandi-phone-hint">شماره شما فقط برای هماهنگی جلسه استفاده می‌شود.</span>
						</p>

						<?php
						zandi_button(
							array(
								'label'   => 'رزرو جلسه رایگان',
								'variant' => 'on-dark',
								'size'    => 'md',
								'type'    => 'submit',
								'class'   => 'btn--block',
							)
						);
						?>

						<p class="cta__reassurance"><?php echo esc_html( $cta['reassurance'] ); ?></p>
					</form>

					<div class="cta__success" role="status">
						<span class="cta__success-icon"><?php zandi_icon( 'check', array( 'stroke' => 2.2 ) ); ?></span>
						<strong>درخواست شما ثبت شد</strong>
						<p>مشاور آموزشی در کمتر از یک روز کاری با شما تماس می‌گیرد.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
