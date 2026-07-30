<?php
/**
 * Learning journey.
 *
 * Horizontal from `lg` up, vertical below — both drawn from the same ordered
 * list, so the reading order stays correct in every layout and for assistive
 * tech.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// Loaded bare from the homepage, or with flags from template-section.php.
$zandi_args = isset( $args ) && is_array( $args ) ? $args : array();
?>

<section class="section section--mist" id="journey" aria-labelledby="journey-title">
	<div class="container">
		<?php
		zandi_maybe_section_heading(
			$zandi_args,
			array(
				'id'          => 'journey',
				'eyebrow'     => 'مسیر یادگیری',
				'title'       => 'از اینجا تا حرف زدن، قدم به قدم',
				'description' => 'از روز اول می‌دونی توی هر مرحله چه اتفاقی می‌افته و آخرش کجا وایستادی.',
			)
		);
		?>

		<div class="timeline">
			<?php
			/*
			 * Two rails rather than one responsive element: the vertical rail is
			 * offset to the icon column (1.625rem = half the 3.25rem marker),
			 * while the horizontal one spans the full row.
			 */
			?>
			<span class="timeline__rail" aria-hidden="true"></span>
			<span class="timeline__rail timeline__rail--horizontal" aria-hidden="true"></span>

			<ol class="timeline__list reveal-group">
				<?php foreach ( zandi_journey() as $index => $step ) : ?>
					<li class="timeline__step reveal">
						<span class="timeline__marker">
							<?php zandi_icon( $step['icon'] ); ?>
							<span class="timeline__number"><?php echo esc_html( zandi_fa_digits( $index + 1 ) ); ?></span>
						</span>

						<div class="timeline__body">
							<h3 class="timeline__title"><?php echo esc_html( $step['title'] ); ?></h3>
							<p class="timeline__text"><?php echo esc_html( $step['description'] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</section>
