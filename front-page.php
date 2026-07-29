<?php
/**
 * The homepage.
 *
 * Section ordering, nothing else — each partial owns its own data and spacing.
 *
 * WordPress uses this template automatically for the site's front page. If the
 * front page is set to show a static page instead, that page's content is
 * appended below the sections so editors still have somewhere to write.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main">
	<?php
	get_template_part( 'template-parts/home/hero' );
	get_template_part( 'template-parts/home/stats' );
	get_template_part( 'template-parts/home/features' );
	get_template_part( 'template-parts/home/courses' );
	get_template_part( 'template-parts/home/journey' );
	get_template_part( 'template-parts/home/teachers' );
	get_template_part( 'template-parts/home/testimonials' );
	get_template_part( 'template-parts/home/faq' );
	get_template_part( 'template-parts/home/cta' );

	// Editor-authored content, when the front page is a static page with a body.
	if ( is_page() && have_posts() ) :
		while ( have_posts() ) :
			the_post();

			if ( trim( get_the_content() ) ) :
				?>
				<div class="section">
					<div class="container">
						<div class="entry">
							<div class="entry__content">
								<?php the_content(); ?>
							</div>
						</div>
					</div>
				</div>
				<?php
			endif;
		endwhile;
	endif;
	?>
</main>

<?php
get_footer();
