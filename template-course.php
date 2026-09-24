<?php
/**
 * Course landing page — /courses/{slug}
 *
 * Section ordering only. Every partial receives the course array; per-course
 * content comes from inc/courses.php, so a fourth course needs no changes here.
 *
 * Uses the site header and footer, not a set of its own. These pages once had
 * header-course.php / footer-course.php with their own logo, navigation and
 * palette, which made opening a course feel like leaving the site — and left no
 * route back except the logo.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = zandi_current_course();

if ( ! $course ) {
	return;
}

$args = array( 'course' => $course );

get_header();
?>

<main id="main">
	<?php
	get_template_part( 'template-parts/course/hero', null, $args );
	get_template_part( 'template-parts/course/trust', null, $args );
	get_template_part( 'template-parts/course/intro-video', null, $args );

	/*
	 * A cue between each pair of content sections — see zandi_scroll_cue().
	 *
	 * Every section here ends on a finished-looking card, so the bottom of one
	 * reads as the bottom of the page and people stop scrolling. The arrows run
	 * through the middle of the page only: not above it, where there is
	 * obviously more, and not after an enrol block, where the arrow would be
	 * pushing people past the ask.
	 *
	 * Which sections, and in what order, is the course's own business — the
	 * conversation course has no syllabus and carries a comparison instead.
	 * See zandi_course_sections().
	 */
	$zandi_middle = zandi_course_sections( $course );

	foreach ( $zandi_middle as $zandi_index => $zandi_part ) {
		get_template_part( 'template-parts/course/' . $zandi_part, null, $args );

		if ( $zandi_index < count( $zandi_middle ) - 1 ) {
			zandi_scroll_cue();
		}
	}

	/*
	 * «تا آخر مسیر تنهات نمی‌ذارم» sat below the FAQ until 15 September 2026 and
	 * the owner moved it here, directly after the reviews. That is the moment it
	 * is worth the most: somebody has just read three people saying the teaching
	 * worked, and the next thing they see is the way in — rather than a screen
	 * of questions first, which is where a reader who was ready stops being
	 * ready.
	 *
	 * A cue leads INTO it, because the reviews end on a finished-looking card
	 * like everything else. None follows it: an arrow under an enrol block
	 * points past the only thing on the page that matters.
	 */
	zandi_scroll_cue();
	get_template_part( 'template-parts/course/support', null, $args );

	get_template_part( 'template-parts/course/faq', null, $args );
	get_template_part( 'template-parts/course/closing', null, $args );
	get_template_part( 'template-parts/course/other-courses', null, $args );
	?>
</main>

<?php
get_footer();
