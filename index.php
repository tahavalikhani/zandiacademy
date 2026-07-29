<?php
/**
 * The fallback template.
 *
 * WordPress falls back here for the blog index, archives and search results —
 * anything without a more specific template. All three render as a post grid
 * with a page hero on top, so the site keeps one visual language throughout.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main">
	<div class="page-hero">
		<div class="container">
			<?php if ( is_search() ) : ?>
				<h1 class="page-hero__title">
					<?php
					printf(
						'نتایج جست‌وجو برای «%s»',
						esc_html( get_search_query() )
					);
					?>
				</h1>
			<?php elseif ( is_archive() ) : ?>
				<h1 class="page-hero__title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
				<?php the_archive_description( '<div class="page-hero__meta"><span>', '</span></div>' ); ?>
			<?php else : ?>
				<h1 class="page-hero__title"><?php echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) ?: 'مجله آکادمی' ); ?></h1>
			<?php endif; ?>
		</div>
	</div>

	<div class="section">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="post-grid reveal-group">
					<?php
					while ( have_posts() ) :
						the_post();
						zandi_post_card();
					endwhile;
					?>
				</div>

				<div class="pagination">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 1,
							'prev_text' => zandi_get_icon( zandi_arrow_back() ),
							'next_text' => zandi_get_icon( zandi_arrow_forward() ),
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="no-results reveal">
					<h2>چیزی پیدا نشد</h2>
					<p>هنوز نوشته‌ای در این بخش منتشر نشده است. می‌توانید عبارت دیگری را جست‌وجو کنید یا به صفحه اصلی برگردید.</p>
					<?php
					get_search_form();

					zandi_button(
						array(
							'label'   => 'بازگشت به خانه',
							'url'     => home_url( '/' ),
							'variant' => 'secondary',
							'size'    => 'md',
							'icon'    => zandi_arrow_forward(),
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</main>

<?php
get_footer();
