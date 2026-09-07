<?php
/**
 * Frequently asked questions.
 *
 * Each header is a real <button> carrying aria-expanded / aria-controls, and
 * each panel is a labelled region — so it works with a keyboard and a screen
 * reader without extra wiring. The open/close animation is CSS grid-rows, which
 * animates to content height without measuring anything in JavaScript.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// Loaded bare from the homepage, or with flags from template-section.php.
$zandi_args = isset( $args ) && is_array( $args ) ? $args : array();

/*
 * «اگر جوابت اینجا نبود، از صفحه تماس بپرس» and the button under it both send
 * a reader to /contact/. Since 7 September 2026 this partial also renders ON
 * /contact/, directly beneath the contact cards — where that line tells someone
 * to go to the page they are already reading, and the button links to itself.
 *
 * The test is the honest one rather than a slug comparison: is the place we
 * would send them the place they already are? It answers false on the homepage
 * and on every course page, where the invitation is still worth making.
 */
$zandi_section     = function_exists( 'zandi_current_section' ) ? zandi_current_section() : null;
$zandi_is_the_dest = $zandi_section && zandi_support_url() === zandi_section_url( $zandi_section['slug'] );
?>

<section class="section section--mist" id="faq" aria-labelledby="faq-title">
	<div class="container">
		<div class="faq__layout">
			<div class="faq__aside">
				<?php
				zandi_maybe_section_heading(
					$zandi_args,
					array(
						'id'          => 'faq',
						'eyebrow'     => 'سوالات پرتکرار',
						'title'       => 'هر چی ممکنه بپرسی',
						'description' => $zandi_is_the_dest
							? 'اگر جوابت اینجا نبود، از همین بالا بهم پیام بده. هر ساعتی از شبانه‌روز جواب می‌گیری.'
							: 'اگر جوابت اینجا نبود، از صفحه تماس بپرس. هر ساعتی از شبانه‌روز جواب می‌گیری.',
						'align'       => 'start',
					)
				);

				if ( ! $zandi_is_the_dest ) {
					echo '<div class="reveal">';
					zandi_button(
						array(
							'label'       => 'پرسیدن سوال دیگر',
							'url'         => zandi_support_url(),
							'variant'     => 'secondary',
							'size'        => 'md',
							'icon_before' => 'chat',
						)
					);
					echo '</div>';
				}
				?>
			</div>

			<div class="accordion reveal" data-accordion>
				<?php foreach ( zandi_faqs() as $index => $faq ) : ?>
					<?php
					$is_open    = 0 === $index;
					$trigger_id = 'faq-trigger-' . $index;
					$panel_id   = 'faq-panel-' . $index;
					?>
					<div class="accordion__item<?php echo $is_open ? ' is-open' : ''; ?>">
						<h3>
							<button
								type="button"
								class="accordion__trigger"
								id="<?php echo esc_attr( $trigger_id ); ?>"
								aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
								aria-controls="<?php echo esc_attr( $panel_id ); ?>"
							>
								<span class="accordion__question"><?php echo zandi_bidi( $faq['question'] ); ?></span>
								<span class="accordion__chevron"><?php zandi_icon( 'chevronDown' ); ?></span>
							</button>
						</h3>

						<div
							class="accordion__panel"
							id="<?php echo esc_attr( $panel_id ); ?>"
							role="region"
							aria-labelledby="<?php echo esc_attr( $trigger_id ); ?>"
						>
							<div>
								<p class="accordion__answer"><?php echo zandi_bidi( $faq['answer'] ); ?></p>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
