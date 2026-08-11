<?php
/**
 * «تعیین سطح» — the student's saved placement result.
 *
 * RENDERS NOTHING UNTIL THERE IS A RESULT. A student who has never sat the test
 * sees no card, no empty state and no invitation — the test is not announced
 * anywhere on the site yet, and a panel section advertising it would be the one
 * place it leaked out. The moment the test is launched, this is where the
 * «آزمون بده» empty state belongs.
 *
 * The result is written by zandi_placement_save() on submit, so it is already
 * here by the time the student follows the link out of the result page.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_result = zandi_placement_latest( $args['user']->ID );

if ( ! $zandi_result ) {
	return;
}

$zandi_copy  = zandi_placement_copy();
$zandi_rows  = zandi_placement_result_rows();
$zandi_row   = isset( $zandi_rows[ $zandi_result['level'] ] ) ? $zandi_rows[ $zandi_result['level'] ] : array();
$zandi_label = isset( $zandi_row['label'] ) ? $zandi_row['label'] : $zandi_result['level'];
$zandi_lead  = isset( $zandi_row['headline'] ) ? $zandi_row['headline'] : '';

// Same rule as the result page: a code gets the big type, a sentence does not.
$zandi_long = ! zandi_placement_level_is_code( $zandi_label );
?>

<section class="panel-section" id="my-level" aria-labelledby="my-level-title">
	<h2 class="panel-section__title" id="my-level-title"><?php echo esc_html( $zandi_copy['panel_title'] ); ?></h2>

	<div class="card panel-card">
		<div class="panel-level">
			<p class="panel-level__chip<?php echo $zandi_long ? ' panel-level__chip--long' : ''; ?>">
				<?php echo zandi_placement_level_label( $zandi_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
			</p>

			<div class="panel-level__body">
				<?php if ( $zandi_lead ) : ?>
					<p class="panel-level__headline"><?php echo zandi_bidi( $zandi_lead ); ?></p>
				<?php endif; ?>

				<p class="panel-level__date">
					<?php printf( esc_html( $zandi_copy['panel_date'] ), esc_html( zandi_placement_date( $zandi_result['time'] ) ) ); ?>
				</p>
			</div>

			<?php if ( ! empty( $zandi_result['skills'] ) ) : ?>
				<ul class="panel-level__skills">
					<?php foreach ( $zandi_result['skills'] as $zandi_skill ) : ?>
						<li class="pt-skill">
							<div class="pt-skill__head">
								<span class="pt-skill__label">
									<?php zandi_icon( zandi_placement_skill_icon( $zandi_skill['id'] ) ); ?>
									<?php echo esc_html( $zandi_skill['label'] ); ?>
								</span>
								<span class="pt-skill__value"><?php echo esc_html( zandi_fa_digits( $zandi_skill['percent'] ) ); ?>٪</span>
							</div>

							<div class="pt-meter" role="img" aria-label="<?php
								echo esc_attr(
									sprintf(
										'%1$s: %2$s از %3$s',
										$zandi_skill['label'],
										zandi_fa_digits( $zandi_skill['correct'] ),
										zandi_fa_digits( $zandi_skill['total'] )
									)
								);
							?>">
								<div class="pt-meter__fill" style="inline-size: <?php echo esc_attr( (int) $zandi_skill['percent'] ); ?>%"></div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="panel-level__actions">
			<?php
			$zandi_suggest = zandi_placement_recommendation( $zandi_result['level'] );

			if ( ! empty( $zandi_suggest['courses'] ) ) {
				$zandi_course = $zandi_suggest['courses'][0];

				zandi_button(
					array(
						'label' => $zandi_copy['panel_action'],
						'url'   => zandi_course_url( $zandi_course['slug'] ),
						'size'  => 'sm',
						'icon'  => zandi_arrow_forward(),
					)
				);
			}

			/*
			 * No token here: the transient expired long ago. The report reads
			 * the saved result instead — see zandi_placement_report_result().
			 */
			zandi_button(
				array(
					'label'       => $zandi_copy['report_cta'],
					'url'         => zandi_placement_report_url(),
					'variant'     => 'secondary',
					'size'        => 'sm',
					'icon_before' => 'clipboard',
				)
			);

			zandi_button(
				array(
					'label'       => $zandi_copy['panel_retake'],
					'url'         => zandi_placement_url( 'start' ),
					'variant'     => 'secondary',
					'size'        => 'sm',
					'icon_before' => 'repeat',
				)
			);
			?>
		</div>

		<p class="panel-card__note"><?php echo zandi_bidi( $zandi_copy['honesty_body'] ); ?></p>
	</div>
</section>
