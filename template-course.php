<?php
/**
 * Course landing page — /courses/{slug}
 *
 * Section ordering only. Every partial receives the course array; per-course
 * content comes from inc/courses.php, so a fourth course needs no changes here.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = zandi_current_course();

if ( ! $course ) {
	return;
}

$args = array( 'course' => $course );

get_header( 'course' );
?>

<main id="main">
	<?php
	get_template_part( 'template-parts/course/hero', null, $args );
	get_template_part( 'template-parts/course/trust', null, $args );
	get_template_part( 'template-parts/course/intro-video', null, $args );
	get_template_part( 'template-parts/course/about-course', null, $args );
	get_template_part( 'template-parts/course/deliverables', null, $args );
	get_template_part( 'template-parts/course/method', null, $args );
	get_template_part( 'template-parts/course/sample-lesson', null, $args );
	get_template_part( 'template-parts/course/curriculum', null, $args );
	get_template_part( 'template-parts/course/fit', null, $args );
	get_template_part( 'template-parts/course/shima', null, $args );
	get_template_part( 'template-parts/course/testimonials', null, $args );
	get_template_part( 'template-parts/course/faq', null, $args );
	get_template_part( 'template-parts/course/support', null, $args );
	get_template_part( 'template-parts/course/closing', null, $args );
	get_template_part( 'template-parts/course/other-courses', null, $args );
	?>
</main>

<?php
get_footer( 'course' );
