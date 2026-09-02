<?php
/**
 * Introduction video.
 *
 * The clip itself is not in this repository — it is uploaded to the Media
 * Library as `course-{slug}-intro.mp4` and resolved by zandi_course_video().
 * Until one exists for this course the helper returns '' and zandi_video()
 * draws its «به‌زودی» placeholder, so a course with no video recorded yet
 * renders exactly as it did before.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
$video  = zandi_course_video( $course['slug'], 'intro' );
$meta   = zandi_course_video_meta( $course['slug'], 'intro' );
?>

<section class="c-section" id="intro" aria-labelledby="intro-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="intro-title">یک دقیقه وقت بذار، بذار خودم برات بگم 👋</h2>
			<p class="c-section__lead">
				توی این ویدیو کوتاه توضیح می‌دم دوره چطور پیش می‌ره و چرا این روش با چیزی که تا حالا امتحان کردی فرق داره.
			</p>
		</div>

		<?php
		zandi_video(
			array(
				'file'   => $video,
				'poster' => $video ? zandi_course_video_poster( $course['slug'], 'intro' ) : '',
				'title'  => $meta['name'],
				'note'   => 'ویدیوی معرفی به‌زودی اینجا قرار می‌گیرد',
				'class'  => 'reveal reveal--scale',
			)
		);
		?>
	</div>
</section>
