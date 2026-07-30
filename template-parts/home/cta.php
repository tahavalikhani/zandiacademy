<?php
/**
 * Closing call to action.
 *
 * The free-consultation form that used to sit here is gone. It collected a name
 * and a phone number, stored neither, and promised a callback the academy does
 * not actually run — support happens in Telegram. The panel entry below replaces
 * it: a real account, on a real route.
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
								'class'   => 'btn--block-mobile',
								'icon'    => zandi_arrow_forward(),
							)
						);

						zandi_button(
							array(
								'label'   => $cta['secondary']['label'],
								'url'     => $cta['secondary']['url'],
								'variant' => 'outline-on-dark',
								'size'    => 'lg',
								'class'   => 'btn--block-mobile',
							)
						);
						?>
					</div>

					<p class="cta__reassurance"><?php echo esc_html( $cta['reassurance'] ); ?></p>

					<p class="cta__account">
						<?php echo esc_html( $cta['account_prompt'] ); ?>
						<a href="<?php echo esc_url( zandi_panel_url() ); ?>"><?php echo esc_html( $cta['account_action'] ); ?></a>
					</p>
				</div>
			</div>
		</div>
	</div>
</section>
