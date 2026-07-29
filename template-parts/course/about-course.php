<?php
/**
 * What this course is, plus the end-of-course outcomes.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
?>

<section class="c-section" id="about-course" aria-labelledby="about-course-title">
	<div class="c-container">
		<div class="c-section__head">
			<h2 class="c-section__title" id="about-course-title"><?php echo zandi_bidi( $course['about_title'] ); ?></h2>
		</div>

		<div class="c-prose">
			<?php foreach ( $course['about_body'] as $paragraph ) : ?>
				<p><?php echo zandi_bidi( $paragraph ); ?></p>
			<?php endforeach; ?>
		</div>

		<div class="c-outcomes">
			<h3 class="c-outcomes__title">در پایان این دوره می‌تونی:</h3>
			<ul class="c-outcomes__list">
				<?php foreach ( $course['outcomes'] as $outcome ) : ?>
					<li>
						<?php zandi_check(); ?>
						<span><?php echo zandi_bidi( $outcome ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
