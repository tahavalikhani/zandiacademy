<?php
/**
 * Not found — 404.
 *
 * This file did not exist until now, and its absence was not harmless.
 * zandi_course_template() (functions.php) answers an unknown course slug by
 * calling get_query_template( '404' ), which returns an empty string when the
 * theme ships no 404.php. `template_include` then yields '' and WordPress
 * includes nothing at all: the response was a 404 status with a completely
 * empty document — no <title>, no heading, no header, no footer. A crawler
 * that follows one stale link to /courses/a3 sees a blank page on the domain.
 *
 * No `.reveal` anywhere on this page, for the same reason the account and
 * placement pages carry none: scroll-reveal starts at opacity 0 and is undone
 * by JavaScript. Someone arriving here already followed a link that failed, and
 * a page that needs a script to become visible can fail shut on top of it.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_not_found = zandi_not_found();

get_header();
?>

<main id="main">
	<div class="page-hero">
		<div class="container">
			<h1 class="page-hero__title"><?php echo zandi_bidi( $zandi_not_found['title'] ); ?></h1>

			<?php if ( $zandi_not_found['description'] ) : ?>
				<p class="page-hero__lead"><?php echo zandi_bidi( $zandi_not_found['description'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="section">
		<div class="container">
			<div class="no-results">
				<h2>از کجا ادامه بدیم؟</h2>

				<p>سه تا دوره داریم — پایه، متوسط و پیشرفته. اگر دنبال چیز مشخصی بودی و پیداش نکردی، بپرس.</p>

				<?php
				/*
				 * No search box, deliberately — index.php puts one in its own
				 * empty state and this page does not copy it. There is no blog
				 * yet, so a search from here lands on an empty results page:
				 * a second dead end after the one that brought them here. The
				 * two links are the actual answer.
				 */
				zandi_button(
					array(
						'label' => $zandi_not_found['primary']['label'],
						'url'   => $zandi_not_found['primary']['url'],
						'size'  => 'md',
					)
				);

				zandi_button(
					array(
						'label'   => $zandi_not_found['secondary']['label'],
						'url'     => $zandi_not_found['secondary']['url'],
						'variant' => 'secondary',
						'size'    => 'md',
					)
				);
				?>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
