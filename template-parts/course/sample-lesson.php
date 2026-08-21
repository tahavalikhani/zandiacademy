<?php
/**
 * Sample lesson video.
 *
 * Uploaded to the Media Library as `course-{slug}-sample.mp4`. See
 * template-parts/course/intro-video.php for the whole arrangement.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
$video  = zandi_course_video( $course['slug'], 'sample' );
$meta   = zandi_course_video_meta( $course['slug'], 'sample' );
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
				'file'    => $video,
				'poster'  => $video ? zandi_course_video_poster( $course['slug'], 'sample' ) : '',
				'title'   => $meta['name'],
				'note'    => 'نمونه جلسه به‌زودی اینجا قرار می‌گیرد',
				'caption' => 'نمونه‌های بیشتر توی هایلایت زرد اینستاگرام آکادمی هست.',
				'class'   => 'reveal reveal--scale',
			)
		);
		?>
	</div>
</section>
