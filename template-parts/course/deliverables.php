<?php
/**
 * What you get on enrolment — four cards.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="c-section" id="deliverables" aria-labelledby="deliverables-title">
	<div class="c-container">
		<div class="c-section__head">
			<h2 class="c-section__title" id="deliverables-title">دقیقاً چی تحویل می‌گیری 📦</h2>
		</div>

		<div class="c-cards c-cards--4">
			<?php foreach ( zandi_course_deliverables() as $card ) : ?>
				<article class="c-card">
					<span class="c-card__icon"><?php zandi_icon( $card['icon'] ); ?></span>
					<h3 class="c-card__title"><?php echo zandi_bidi( $card['title'] ); ?></h3>
					<p class="c-card__body"><?php echo zandi_bidi( $card['body'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
