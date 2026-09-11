<?php
/**
 * The three subscription plans.
 *
 * Prices come from zandi_podcast_plan_price(), which prefers whatever the
 * product is actually set to charge over the figure written in the array. A
 * page quoting one number while the checkout charges another is worse than a
 * page with no prices on it, and the two drift apart the first time somebody
 * runs a discount.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy     = zandi_podcast_copy();
$zandi_plans    = zandi_podcast_plans();
$zandi_can_buy  = zandi_podcast_purchasable();
?>

<section class="section podcast-plans" id="plans" aria-labelledby="podcast-plans-title">
	<div class="container">
		<?php
		zandi_section_heading(
			array(
				'title'       => $zandi_copy['plans_title'],
				'description' => $zandi_copy['plans_lead'],
				'id'          => 'podcast-plans',
			)
		);
		?>

		<ul class="podcast-plans__list">
			<?php foreach ( $zandi_plans as $zandi_plan ) : ?>
				<?php $zandi_price = zandi_podcast_plan_price( $zandi_plan ); ?>

				<li class="card podcast-plan">
					<h3 class="podcast-plan__label"><?php echo esc_html( $zandi_plan['label'] ); ?></h3>

					<p class="podcast-plan__price">
						<span class="podcast-plan__amount"><?php echo esc_html( zandi_fa_digits( number_format_i18n( $zandi_price ) ) ); ?></span>
						<span class="podcast-plan__currency"><?php echo esc_html( $zandi_copy['toman'] ); ?></span>
					</p>

					<?php if ( ! empty( $zandi_plan['note'] ) ) : ?>
						<p class="podcast-plan__note"><?php echo esc_html( $zandi_plan['note'] ); ?></p>
					<?php endif; ?>

					<?php
					/*
					 * With no product wired up there is nothing to add to a
					 * cart, so the button says «به‌زودی» and is not a link.
					 * The alternative — a live-looking button that lands on an
					 * empty cart — reads as a broken shop.
					 */
					if ( $zandi_can_buy ) {
						zandi_button(
							array(
								'label'    => $zandi_copy['plan_cta'],
								'url'      => zandi_podcast_plan_url( $zandi_plan ),
								'size'     => 'md',
								'class'    => 'podcast-plan__cta',
								'sr_label' => $zandi_copy['plan_cta'] . ' — ' . $zandi_plan['label'],
							)
						);
					} else {
						printf(
							'<p class="podcast-plan__soon">%s</p>',
							esc_html( $zandi_copy['plan_soon'] )
						);
					}
					?>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="podcast-plans__fine"><?php echo esc_html( $zandi_copy['expiry_note'] ); ?></p>
	</div>
</section>
