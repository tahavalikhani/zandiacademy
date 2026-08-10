<?php
/**
 * Placement test — the page before the test.
 *
 * Everything a student should know before the first question: how long it
 * takes, what «نمی‌دانم» is for, where the language switches, that there is no
 * way back, that the result is free and immediate — and, because the owner
 * asked for both, an honest note on how far the result can be trusted and a
 * polite request not to look the answers up.
 *
 * The academic sources are on this page rather than tucked into a footnote. A
 * free level test is exactly the kind of thing a visitor assumes was made up in
 * an afternoon, and the citation list is the fastest way to show it was not.
 *
 * NO REGISTRATION BEFORE THE TEST. The specification is explicit that a signup
 * wall here halves the number of people who start, and the site has nothing to
 * gain from a student who never finds out their level.
 *
 * NO `.reveal` ON THIS PAGE, for the same reason the account and panel pages
 * carry none: scroll-reveal starts at opacity 0 and is undone by JavaScript, so
 * a page that needs a script to become visible can fail shut. These are the
 * instructions someone has to read before they can start — they are not
 * decoration, and they appear whether theme.js loads or not.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy  = zandi_placement_copy();
$zandi_error = isset( $_GET['e'] ) ? sanitize_key( wp_unslash( $_GET['e'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presentation-only flag set by our own redirect.
?>

<div class="page-hero">
	<div class="container">
		<?php
		zandi_breadcrumb(
			array(
				array(
					'label' => 'خانه',
					'url'   => home_url( '/' ),
				),
				array( 'label' => $zandi_copy['nav_label'] ),
			)
		);
		?>

		<div class="pt-hero">
			<?php zandi_badge( $zandi_copy['eyebrow'], 'rouge', array( 'dot' => true ) ); ?>

			<h1 class="page-hero__title"><?php echo zandi_bidi( $zandi_copy['title'] ); ?></h1>
			<p class="page-hero__lead"><?php echo zandi_bidi( $zandi_copy['lead'] ); ?></p>
		</div>
	</div>
</div>

<div class="section">
	<div class="container pt-intro">

		<?php if ( 'expired' === $zandi_error || 'invalid' === $zandi_error ) : ?>
			<div class="pt-notice" role="status">
				<?php zandi_icon( 'lifebuoy', array( 'class' => 'pt-notice__icon' ) ); ?>
				<p>
					<?php
					echo esc_html(
						'expired' === $zandi_error
							? $zandi_copy['expired_body']
							: $zandi_copy['invalid_body']
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<section class="pt-rules" aria-labelledby="pt-rules-title">
			<h2 class="pt-block__title" id="pt-rules-title"><?php echo zandi_bidi( $zandi_copy['rules_title'] ); ?></h2>

			<ul class="pt-rules__list">
				<?php foreach ( $zandi_copy['rules'] as $zandi_rule ) : ?>
					<li class="card pt-rule">
						<span class="pt-rule__icon" aria-hidden="true">
							<?php zandi_icon( $zandi_rule['icon'] ); ?>
						</span>

						<div class="pt-rule__body">
							<h3 class="pt-rule__title"><?php echo zandi_bidi( $zandi_rule['title'] ); ?></h3>
							<p class="pt-rule__text"><?php echo zandi_bidi( $zandi_rule['body'] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<div class="pt-asks">
			<?php
			/*
			 * Two cards, deliberately side by side. One says the result is not a
			 * certificate; the other asks the student not to cheat. They are the
			 * same argument seen from two ends — the number is only worth
			 * anything if it describes the person reading it.
			 */
			?>
			<section class="card pt-ask" aria-labelledby="pt-honesty-title">
				<span class="pt-ask__icon" aria-hidden="true"><?php zandi_icon( 'target' ); ?></span>
				<h2 class="pt-ask__title" id="pt-honesty-title"><?php echo zandi_bidi( $zandi_copy['honesty_title'] ); ?></h2>
				<p class="pt-ask__text"><?php echo zandi_bidi( $zandi_copy['honesty_body'] ); ?></p>
			</section>

			<section class="card pt-ask pt-ask--fair" aria-labelledby="pt-fair-title">
				<span class="pt-ask__icon" aria-hidden="true"><?php zandi_icon( 'heart' ); ?></span>
				<h2 class="pt-ask__title" id="pt-fair-title"><?php echo zandi_bidi( $zandi_copy['fair_title'] ); ?></h2>
				<p class="pt-ask__text"><?php echo zandi_bidi( $zandi_copy['fair_body'] ); ?></p>
			</section>
		</div>

		<?php
		/*
		 * <details> rather than the theme's accordion: this one block does not
		 * justify a JavaScript dependency, and a native disclosure widget is
		 * open to a screen reader and to Ctrl+F whether scripts run or not.
		 */
		?>
		<details class="pt-sources">
			<summary class="pt-sources__summary">
				<span class="pt-sources__label">
					<?php zandi_icon( 'clipboard', array( 'class' => 'pt-sources__icon' ) ); ?>
					<?php echo zandi_bidi( $zandi_copy['sources_title'] ); ?>
				</span>
				<?php zandi_icon( 'chevronDown', array( 'class' => 'pt-sources__chevron' ) ); ?>
			</summary>

			<div class="pt-sources__body">
				<p class="pt-sources__lead"><?php echo zandi_bidi( $zandi_copy['sources_lead'] ); ?></p>

				<?php foreach ( zandi_placement_sources() as $zandi_group ) : ?>
					<div class="pt-source">
						<h3 class="pt-source__title">
							<?php zandi_icon( $zandi_group['icon'] ); ?>
							<?php echo zandi_bidi( $zandi_group['title'] ); ?>
						</h3>

						<?php
						/*
						 * A citation and its Persian gloss are two elements with
						 * two directions, never one mixed line. See the note on
						 * zandi_placement_sources() for what that costs — a
						 * bibliographic reference is the worst case the bidi
						 * algorithm has, and «Ev@lang» came out «lang@Ev».
						 */
						?>
						<ul class="pt-source__list">
							<?php foreach ( $zandi_group['items'] as $zandi_item ) : ?>
								<li>
									<span class="pt-source__cite"<?php echo zandi_placement_dir_attrs( $zandi_item['cite'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
										<?php echo zandi_placement_content( $zandi_item['cite'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
									</span>

									<?php if ( $zandi_item['note'] ) : ?>
										<span class="pt-source__note"><?php echo zandi_bidi( $zandi_item['note'] ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		</details>

		<div class="pt-start">
			<?php
			zandi_button(
				array(
					'label' => $zandi_copy['start'],
					'url'   => zandi_placement_url( 'start' ),
					'size'  => 'lg',
					'icon'  => zandi_arrow_forward(),
					'class' => 'btn--block btn--block-mobile',
				)
			);
			?>

			<p class="pt-start__note"><?php echo zandi_bidi( $zandi_copy['start_note'] ); ?></p>
		</div>
	</div>
</div>
