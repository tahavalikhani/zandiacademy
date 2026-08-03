<?php
/**
 * Sample lesson video.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="c-section" id="sample-lesson" aria-labelledby="sample-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="sample-title">ببین کلاس واقعاً چه شکلیه</h2>
			<p class="c-section__lead">
				اینم یه تیکه از یکی از جلسه‌های دوره. بدون تدوین و بدون تعارف، دقیقاً همونی که می‌بینی.
			</p>
		</div>

		<?php
		zandi_video(
			array(
				'title'   => 'نمونه جلسه دوره',
				'note'    => 'نمونه جلسه به‌زودی اینجا قرار می‌گیرد',
				'caption' => 'نمونه‌های بیشتر توی هایلایت زرد اینستاگرام آکادمی هست.',
				'class'   => 'reveal reveal--scale',
			)
		);
		?>
	</div>
</section>
