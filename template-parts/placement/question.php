<?php
/**
 * Placement test — one question.
 *
 * DIRECTION IS THE WHOLE JOB OF THIS FILE. A French sentence dropped into a
 * right-to-left paragraph is reordered by the bidi algorithm: the full stop of
 * «Je ne parle pas français.» jumps to the front of the line, an apostrophe
 * splits a word in half, and «— Tu connais Julie ?» loses its dash to the wrong
 * end. So every fragment is asked what direction it wants —
 * zandi_placement_dir_attrs() puts it on the element, not on a span inside it,
 * because only the element can make `text-align: start` resolve to left.
 *
 * A radio's value is its POSITION on screen, never the option's own index. The
 * options were shuffled server-side; the map back is the signed hidden field on
 * the form. See inc/placement.php.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_question = $args['question'];
$zandi_order    = $args['order'];
$zandi_id       = (int) $zandi_question['id'];
$zandi_copy     = zandi_placement_copy();
$zandi_name     = sprintf( 'q[%d]', $zandi_id );
$zandi_legend   = sprintf( 'pt-q-%d-legend', $zandi_id );
?>

<li
	class="pt-step pt-step--question"
	data-pt-step="<?php echo esc_attr( $args['step'] ); ?>"
	data-pt-question="<?php echo esc_attr( $args['number'] ); ?>"
	tabindex="-1"
>
	<div class="card pt-q">
		<fieldset class="pt-q__fieldset">
			<legend class="pt-q__legend" id="<?php echo esc_attr( $zandi_legend ); ?>">
				<span class="pt-q__count">
					<?php
					printf(
						/* translators: 1: question number, 2: total questions. */
						esc_html( $zandi_copy['progress'] ),
						esc_html( zandi_fa_digits( $args['number'] ) ),
						esc_html( zandi_fa_digits( $args['total'] ) )
					);
					?>
				</span>

				<span class="pt-q__stem"<?php echo zandi_placement_dir_attrs( $zandi_question['stem'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
					<?php echo zandi_placement_content( $zandi_question['stem'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
				</span>
			</legend>

			<?php if ( ! empty( $zandi_question['passage'] ) ) : ?>
				<div class="pt-passage"<?php echo zandi_placement_dir_attrs( $zandi_question['passage'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
					<?php echo zandi_placement_content( $zandi_question['passage'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $zandi_question['prompt'] ) ) : ?>
				<p class="pt-prompt"<?php echo zandi_placement_dir_attrs( $zandi_question['prompt'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
					<?php echo zandi_placement_content( $zandi_question['prompt'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
				</p>
			<?php endif; ?>

			<div class="pt-options">
				<?php
				foreach ( $zandi_order as $zandi_position => $zandi_option ) :
					if ( ! isset( $zandi_question['options'][ $zandi_option ] ) ) {
						continue;
					}

					$zandi_text  = $zandi_question['options'][ $zandi_option ];
					$zandi_input = sprintf( 'pt-q-%1$d-o%2$d', $zandi_id, $zandi_position );

					// «نمی‌دانم» is pinned last by the shuffle and is styled apart
					// from the three real options, so choosing it never feels like
					// giving a wrong answer.
					$zandi_is_idk = isset( $zandi_question['idkIndex'] ) && (int) $zandi_question['idkIndex'] === (int) $zandi_option;
					?>
					<label class="pt-option<?php echo $zandi_is_idk ? ' pt-option--idk' : ''; ?>" for="<?php echo esc_attr( $zandi_input ); ?>">
						<input
							type="radio"
							class="pt-option__input"
							id="<?php echo esc_attr( $zandi_input ); ?>"
							name="<?php echo esc_attr( $zandi_name ); ?>"
							value="<?php echo esc_attr( $zandi_position ); ?>"
							required
						>

						<span class="pt-option__marker" aria-hidden="true"><?php zandi_icon( 'check' ); ?></span>

						<span class="pt-option__text"<?php echo zandi_placement_dir_attrs( $zandi_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string. ?>>
							<?php echo zandi_placement_content( $zandi_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
						</span>
					</label>
					<?php
				endforeach;
				?>
			</div>
		</fieldset>

		<?php
		/*
		 * The last question has no «بعدی»: the submit button below the list is
		 * what finishes the test, with scripts and without them.
		 */
		if ( ! $args['is_last'] ) {
			zandi_button(
				array(
					'label' => $zandi_copy['next'],
					'size'  => 'md',
					'icon'  => zandi_arrow_forward(),
					'class' => 'pt-next',
					'attrs' => array(
						'hidden'       => 'hidden',
						'data-pt-next' => '',
					),
				)
			);
		}
		?>
	</div>
</li>
