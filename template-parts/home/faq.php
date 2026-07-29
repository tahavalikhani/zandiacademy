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
						'description' => 'اگر جوابت اینجا نبود، توی تلگرام بپرس. هر ساعتی از شبانه‌روز جواب می‌گیری.',
						'align'       => 'start',
					)
				);

				echo '<div class="reveal">';
				zandi_button(
					array(
						'label'       => 'پرسیدن سوال دیگر',
						'url'         => 'https://t.me/zandiacademy_fr',
						'variant'     => 'secondary',
						'size'        => 'md',
						'icon_before' => 'chat',
					)
				);
				echo '</div>';
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
