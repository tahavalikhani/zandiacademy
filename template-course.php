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
	 * obviously more, and not below «سوالات متداول», where the next things are
	 * the two enrol blocks and an arrow would be pushing people past the ask.
	 */
	$zandi_middle = array(
		'about-course',
		'deliverables',
		'method',
		'sample-lesson',
		'curriculum',
		'fit',
		'shima',
		'testimonials',
		'faq',
	);

	foreach ( $zandi_middle as $zandi_index => $zandi_part ) {
		get_template_part( 'template-parts/course/' . $zandi_part, null, $args );

		if ( $zandi_index < count( $zandi_middle ) - 1 ) {
			zandi_scroll_cue();
		}
	}

	get_template_part( 'template-parts/course/support', null, $args );
	get_template_part( 'template-parts/course/closing', null, $args );
	get_template_part( 'template-parts/course/other-courses', null, $args );
	?>
</main>

<?php
get_footer();
