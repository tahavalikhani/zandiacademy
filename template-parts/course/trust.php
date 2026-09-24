<?php
/**
 * Trust bar — a thin band directly under the hero.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = isset( $args['course'] ) ? $args['course'] : array();
?>

<section class="c-trust" aria-label="آکادمی زندی در یک نگاه">
	<div class="c-container">
		<ul class="c-trust__list reveal">
			<?php foreach ( zandi_course_trust_items( $course ) as $item ) : ?>
				<li>
					<span class="c-trust__dot" aria-hidden="true"></span>
					<span><?php echo esc_html( $item ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
