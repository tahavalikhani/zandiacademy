<?php
/**
 * Student reviews.
 *
 * Real reviews only, and the same list the course pages show — the markup is in
 * zandi_testimonials_carousel(), this file is the section chrome around it.
 *
 * The heading names no level and no course on purpose. These reviews render
 * unchanged on the homepage and on all three course pages, so «نظرات این دوره»
 * would be false on every page but one.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// Loaded bare from the homepage, or with flags from template-section.php.
$zandi_args = isset( $args ) && is_array( $args ) ? $args : array();

$zandi_copy = zandi_testimonials_copy();
?>

<section class="section" id="testimonials" aria-labelledby="testimonials-title">
	<div class="container">
		<?php
		zandi_maybe_section_heading(
			$zandi_args,
			array(
				'id'      => 'testimonials',
				'eyebrow' => $zandi_copy['eyebrow'],
				'title'   => $zandi_copy['title'],
			)
		);

		zandi_testimonials_carousel();
		?>
	</div>
</section>
