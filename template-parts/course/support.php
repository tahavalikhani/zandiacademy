<?php
/**
 * Support callout — full-width, inverted.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
?>

<section class="c-section" aria-labelledby="support-title">
	<div class="c-container">
		<div class="c-support reveal reveal--scale">
			<h2 class="c-support__title" id="support-title">تا آخر مسیر تنهات نمی‌ذارم 💬</h2>
			<p class="c-support__body">
				نصف کسایی که زبان رو ول می‌کنن، به خاطر سخت بودنش ول نمی‌کنن. به خاطر اینه که یه جایی گیر کردن و کسی نبوده جواب بده. اینجا هر وقت گیر کنی جواب می‌گیری، تصحیح تمرین هست و آخر مسیر خود من هستم.
			</p>
			<?php
			/*
			 * Enrols from here. It was `href="#enrol"`, which scrolled back to
			 * the top of a page the student had just read to the bottom of and
			 * asked them to find a second button.
			 */
			zandi_enrol_control( $course, array( 'label' => 'ثبت‌نام در دوره' ) );
			?>
		</div>
	</div>
</section>
