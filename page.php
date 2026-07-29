<?php
/**
 * Static pages.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="page-hero">
			<div class="container">
				<h1 class="page-hero__title"><?php the_title(); ?></h1>
			</div>
		</div>

		<div class="section">
			<div class="container">
				<article <?php post_class( 'entry reveal' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="entry__thumb">
							<?php the_post_thumbnail( 'large' ); ?>
						</figure>
					<?php endif; ?>

					<div class="entry__content">
						<?php
						the_content();

						wp_link_pages(
							array(
								'before' => '<div class="pagination"><div class="nav-links">',
								'after'  => '</div></div>',
							)
						);
						?>
					</div>
				</article>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>
		</div>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
