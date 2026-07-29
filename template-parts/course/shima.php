<?php
/**
 * About Shima.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$shima = zandi_about_shima();
?>

<section class="c-section" id="about-shima" aria-labelledby="shima-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="shima-title"><?php echo esc_html( $shima['title'] ); ?></h2>
		</div>

		<div class="c-about">
			<div class="c-prose reveal">
				<?php foreach ( $shima['body'] as $paragraph ) : ?>
					<p><?php echo zandi_bidi( $paragraph ); ?></p>
				<?php endforeach; ?>
			</div>

			<div class="c-about__photo reveal reveal--scale">
				<?php if ( $shima['photo'] ) : ?>
					<img src="<?php echo esc_url( $shima['photo'] ); ?>" alt="شیما زندی" loading="lazy" decoding="async">
				<?php else : ?>
					<?php /* TODO: photograph of Shima needed. */ ?>
					<?php zandi_icon( 'user' ); ?>
					<span>عکس شیما به‌زودی</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
