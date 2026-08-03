<?php
/**
 * Introduction video.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = isset( $args['course'] ) ? $args['course'] : array();
$video  = zandi_course_video( isset( $course['slug'] ) ? $course['slug'] : '', 'intro' );
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
				'src'    => $video['src'],
				'file'   => $video['file'],
				'link'   => $video['link'],
				'poster' => $video['poster'],
				'title'  => 'ویدیوی معرفی دوره',
				'note'   => 'ویدیوی معرفی به‌زودی اینجا قرار می‌گیرد',
				'class'  => 'reveal reveal--scale',
			)
		);
		?>
	</div>
</section>
