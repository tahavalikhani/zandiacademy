<?php
/**
 * The three subscription plans, and the three steps that come before them.
 *
 * Prices come from zandi_podcast_plan_price(), which prefers whatever the
 * product is actually set to charge over the figure written in the array. A
 * page quoting one number while the checkout charges another is worse than a
 * page with no prices on it, and the two drift apart the first time somebody
 * runs a discount.
 *
 * WHAT A CARD SAYS, AND WHAT IT DELIBERATELY DOES NOT.
 *
 * Each card carries the duration, the price, the owner's own note and the
 * button. There is no «محبوب‌ترین» ribbon and no card lifted above the others:
 * the saving is already in the prices, and a highlighted middle card is a
 * persuasion pattern this site does not use anywhere else.
 *
 * There is also no «per month» figure, and that is arithmetic rather than
 * taste — at the owner's prices the six-month plan works out at ۳۳۱٬۶۶۷ a
 * month against the three-month plan's ۳۳۰٬۰۰۰, so a monthly column would
 * quietly argue against the longest plan. See the note on the six-month row in
 * zandi_podcast_plans().
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy    = zandi_podcast_copy();
$zandi_plans   = zandi_podcast_plans();
$zandi_can_buy = zandi_podcast_purchasable();
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

		/*
		 * Before the prices, not in a band of its own further up the page —
		 * see the note at the top of how.php.
		 */
		get_template_part( 'template-parts/podcast/how' );
		?>

		<ul class="podcast-plans__list">
			<?php foreach ( $zandi_plans as $zandi_plan ) : ?>
				<?php
				$zandi_price    = zandi_podcast_plan_price( $zandi_plan );
				$zandi_featured = ! empty( $zandi_plan['featured'] );
				?>

				<li class="card podcast-plan<?php echo $zandi_featured ? ' podcast-plan--featured' : ''; ?>">
					<?php
					/*
					 * The tag is a claim, so it has to be one that survives
					 * checking: «به‌صرفه‌ترین» is the lowest cost per month of
					 * the three, which is arithmetic on the owner's own prices
					 * — see the note beside `featured` in zandi_podcast_plans().
					 * It is not «محبوب‌ترین», which nothing here could know.
					 */
					if ( $zandi_featured ) :
						?>
						<span class="podcast-plan__tag"><?php echo esc_html( $zandi_copy['plan_featured'] ); ?></span>
					<?php endif; ?>

					<h3 class="podcast-plan__label"><?php echo esc_html( $zandi_plan['label'] ); ?></h3>

					<p class="podcast-plan__price">
						<span class="podcast-plan__amount"><?php echo esc_html( zandi_price_toman( $zandi_price ) ); ?></span>
						<span class="podcast-plan__currency"><?php echo esc_html( $zandi_copy['toman'] ); ?></span>
					</p>

					<?php
					/*
					 * The note is optional in the data and the one-month plan
					 * has none, so the slot is held open rather than collapsed.
					 * Without it the three cards' buttons sat at three
					 * different heights.
					 */
					?>
					<p class="podcast-plan__note"><?php echo esc_html( isset( $zandi_plan['note'] ) ? $zandi_plan['note'] : '' ); ?></p>

					<?php
					/*
					 * With no product wired up there is nothing to add to a
					 * cart, so the button says «به‌زودی» and is not a link. The
					 * alternative — a live-looking button that lands on an
					 * empty cart — reads as a broken shop.
					 */
					if ( $zandi_can_buy ) {
						zandi_button(
							array(
								'label'    => $zandi_copy['plan_cta'],
								'url'      => zandi_podcast_plan_url( $zandi_plan ),
								'size'     => 'md',
								'class'    => 'btn--block podcast-plan__cta',
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

		<p class="podcast-plans__fine">
			<?php zandi_icon( 'repeat' ); ?>
			<span><?php echo esc_html( $zandi_copy['expiry_note'] ); ?></span>
		</p>
	</div>
</section>
