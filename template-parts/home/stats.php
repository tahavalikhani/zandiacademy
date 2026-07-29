<?php
/**
 * Trust band.
 *
 * Sits directly under the hero so the numbers read as part of the first
 * impression.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="stats" aria-label="آکادمی زندی در یک نگاه">
	<div class="container">
		<div class="stats__grid reveal-group">
			<?php foreach ( zandi_stats() as $stat ) : ?>
				<div class="reveal reveal--scale">
					<div class="stat" data-count-to="<?php echo esc_attr( $stat['value'] ); ?>">
						<span class="icon-tile"><?php zandi_icon( $stat['icon'] ); ?></span>

						<p class="stat__value">
							<span class="stat__number"><?php echo esc_html( zandi_fa_number( $stat['value'] ) ); ?></span>
							<?php if ( $stat['suffix'] ) : ?>
								<span class="stat__suffix"><?php echo esc_html( zandi_fa_digits( $stat['suffix'] ) ); ?></span>
							<?php endif; ?>
						</p>

						<p class="stat__label"><?php echo esc_html( $stat['label'] ); ?></p>

						<?php if ( $stat['caption'] ) : ?>
							<p class="stat__caption"><?php echo esc_html( $stat['caption'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
