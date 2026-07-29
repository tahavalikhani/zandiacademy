<?php
/**
 * Teaching method — four blocks.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="c-section" id="method" aria-labelledby="method-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="method-title">چرا این روش با کلاس‌های معمولی فرق داره</h2>
		</div>

		<div class="c-cards c-cards--2 reveal-group">
			<?php foreach ( zandi_course_method() as $index => $block ) : ?>
				<article class="c-card reveal reveal--scale">
					<p class="c-card__num" dir="ltr"><?php echo esc_html( zandi_fa_digits( $index + 1 ) ); ?></p>
					<h3 class="c-card__title"><?php echo zandi_bidi( $block['title'] ); ?></h3>
					<p class="c-card__body"><?php echo zandi_bidi( $block['body'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
