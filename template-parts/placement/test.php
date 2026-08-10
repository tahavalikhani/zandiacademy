<?php
/**
 * Placement test — the thirty questions.
 *
 * ONE FORM, EVERY QUESTION IN IT. JavaScript hides all but the current step and
 * adds the progress bar; without JavaScript the same markup is a long form with
 * one submit button, and it scores identically. Nothing here depends on a script
 * to be readable, which is the theme's rule and also the difference between a
 * student on a bad connection getting a level and getting a blank page.
 *
 * The form posts to itself and is handled on `template_redirect` by
 * zandi_placement_handle_submit(), the same way the auth forms are — so a
 * failure comes back to this page with a message instead of dying at
 * admin-post.php.
 *
 * WHY THERE IS AN ORDER FIELD. Each question's real options are shuffled per
 * render, so a radio carries its POSITION on screen rather than the option's own
 * index — otherwise every correct answer would be `value="0"` and the answer key
 * would be in the markup. The hidden order field is the map back, and the
 * signature beside it is what stops a visitor rewriting that map to point every
 * position at the correct option.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy      = zandi_placement_copy();
$zandi_questions = zandi_placement_questions();
$zandi_total     = count( $zandi_questions );

$zandi_order   = zandi_placement_build_order();
$zandi_encoded = zandi_placement_encode_order( $zandi_order );

/*
 * The interstitial is placed by reading the bank, not by hard-coding «before
 * question 17». Move a question between bands and the card follows it.
 */
$zandi_switch_at = 0;

foreach ( $zandi_questions as $zandi_index => $zandi_question ) {
	if ( isset( $zandi_question['stemLang'] ) && 'fr' === $zandi_question['stemLang'] ) {
		$zandi_switch_at = $zandi_index;
		break;
	}
}

$zandi_step = 0;
?>

<div class="pt-test">
	<div class="container pt-test__inner">

		<form
			class="pt-form"
			method="post"
			action="<?php echo esc_url( zandi_placement_url() ); ?>"
			data-pt-form
		>
			<?php wp_nonce_field( 'zandi_placement', 'zandi_placement_nonce' ); ?>

			<input type="hidden" name="zandi_placement_order" value="<?php echo esc_attr( $zandi_encoded ); ?>">
			<input type="hidden" name="zandi_placement_sig" value="<?php echo esc_attr( zandi_placement_sign_order( $zandi_encoded ) ); ?>">
			<input type="hidden" name="zandi_placement_duration" value="0" data-pt-duration>

			<?php
			/*
			 * `hidden` until the script takes over. Shown with JavaScript off it
			 * would read «سوال ۱ از ۳۰» above a page holding all thirty.
			 */
			?>
			<div class="pt-bar" data-pt-bar hidden>
				<div class="pt-bar__track">
					<div class="pt-bar__fill" data-pt-fill style="inline-size: 0%"></div>
				</div>

				<?php
				// The counter's wording is copy and belongs in PHP; the script
				// only fills the two numbers into it.
				?>
				<p
					class="pt-bar__label"
					data-pt-label
					data-pt-template="<?php echo esc_attr( $zandi_copy['progress'] ); ?>"
				></p>
			</div>

			<p class="pt-nojs"><?php echo zandi_bidi( $zandi_copy['nojs_note'] ); ?></p>

			<ol class="pt-steps">
				<?php
				foreach ( $zandi_questions as $zandi_index => $zandi_question ) :

					if ( $zandi_switch_at && $zandi_index === $zandi_switch_at ) :
						?>
						<li class="pt-step pt-step--switch" data-pt-step="<?php echo esc_attr( $zandi_step ); ?>" tabindex="-1">
							<div class="card pt-switch">
								<span class="pt-switch__icon" aria-hidden="true"><?php zandi_icon( 'globe' ); ?></span>
								<h2 class="pt-switch__title"><?php echo zandi_bidi( $zandi_copy['switch_title'] ); ?></h2>
								<p class="pt-switch__text"><?php echo zandi_bidi( $zandi_copy['switch_body'] ); ?></p>

								<?php
								zandi_button(
									array(
										'label' => $zandi_copy['switch_action'],
										'size'  => 'md',
										'icon'  => zandi_arrow_forward(),
										'class' => 'pt-next',
										'attrs' => array(
											'hidden'     => 'hidden',
											'data-pt-next' => '',
										),
									)
								);
								?>
							</div>
						</li>
						<?php
						++$zandi_step;
					endif;

					get_template_part(
						'template-parts/placement/question',
						null,
						array(
							'question' => $zandi_question,
							'order'    => isset( $zandi_order[ (int) $zandi_question['id'] ] ) ? $zandi_order[ (int) $zandi_question['id'] ] : array(),
							'number'   => $zandi_index + 1,
							'total'    => $zandi_total,
							'step'     => $zandi_step,
							'is_last'  => ( $zandi_index + 1 ) === $zandi_total,
						)
					);

					++$zandi_step;

				endforeach;
				?>
			</ol>

			<div class="pt-submit" data-pt-submit>
				<?php
				zandi_button(
					array(
						'label' => $zandi_copy['submit'],
						'type'  => 'submit',
						'size'  => 'lg',
						'icon'  => zandi_arrow_forward(),
						'class' => 'btn--block btn--block-mobile',
					)
				);
				?>
			</div>
		</form>
	</div>
</div>
