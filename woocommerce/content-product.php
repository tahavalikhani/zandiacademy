<?php
/**
 * A product in the shop grid — the site's course card, not WooCommerce's.
 *
 * This is the one template worth duplicating rather than restyling. The course
 * card in template-parts/home/courses.php is built from `.card--interactive`,
 * `.thumb`, the engraving and zandi_button(), and none of that shape can be
 * reached from WooCommerce's `<li class="product">` with CSS alone. Copying the
 * markup keeps the shop grid and the homepage grid genuinely identical instead
 * of merely similar.
 *
 * Whatever the product knows, the linked course knows better: the level, the
 * session count and the subtitle all come from inc/courses.php when a SKU
 * matches, and only fall back to the product's own fields when it does not.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$zandi_slug   = zandi_product_course_slug( $product );
$zandi_course = $zandi_slug ? zandi_get_course( $zandi_slug ) : null;

// The course page is the real destination; the product page is the fallback.
$zandi_url = $zandi_course ? zandi_course_url( $zandi_slug ) : get_permalink( $product->get_id() );

$zandi_title = $zandi_course ? $zandi_course['short_name'] : $product->get_name();
$zandi_level = $zandi_course ? $zandi_course['level'] : '';
$zandi_text  = $zandi_course ? $zandi_course['subtitle'] : wp_strip_all_tags( $product->get_short_description() );

$zandi_owned = is_user_logged_in() && $zandi_slug && zandi_student_owns_course( get_current_user_id(), $zandi_slug );
?>

<?php
/*
 * No `.reveal` here, unlike the homepage grid this card is copied from.
 *
 * Scroll-reveal starts at opacity 0 and is undone by JavaScript. `.no-js`
 * covers the case where scripting is off, but not the one where it is on and
 * theme.js then fails to run — a bad deploy, an error thrown earlier in the
 * file — because `no-js` has already been swapped off <html> by then. On the
 * homepage that costs an animation. Here it would leave the catalogue
 * invisible and nothing on the site buyable, which is the same reasoning that
 * keeps `.reveal` off the account and panel pages.
 */
?>
<div class="course-card__slot">
	<article <?php wc_product_class( 'card card--interactive card--flush course-card', $product ); ?>>
		<div class="course-card__media">
			<div class="thumb thumb--navy">
				<div class="thumb__art" aria-hidden="true">
					<?php zandi_engraving( 'thumb' ); ?>
					<?php if ( $zandi_level ) : ?>
						<span class="thumb__level"><?php echo esc_html( $zandi_level ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $zandi_owned ) : ?>
				<?php zandi_badge( 'خریداری شده', 'navy', array( 'class' => 'course-card__badge' ) ); ?>
			<?php endif; ?>
		</div>

		<div class="course-card__body">
			<div class="course-card__meta">
				<?php if ( $zandi_level ) : ?>
					<?php zandi_badge( 'سطح ' . $zandi_level, 'navy' ); ?>
				<?php endif; ?>

				<?php if ( $zandi_course ) : ?>
					<span class="course-card__meta-item">
						<?php zandi_icon( 'clock' ); ?>
						<?php echo esc_html( $zandi_course['sessions_text'] ); ?>
					</span>

					<span class="course-card__meta-item">
						<?php zandi_icon( 'layers' ); ?>
						<?php echo esc_html( $zandi_course['hours_text'] ); ?>
					</span>
				<?php endif; ?>
			</div>

			<h3 class="course-card__title"><?php echo zandi_bidi( $zandi_title ); ?></h3>

			<?php if ( $zandi_text ) : ?>
				<p class="course-card__text"><?php echo zandi_bidi( $zandi_text ); ?></p>
			<?php endif; ?>

			<?php if ( $product->get_price_html() ) : ?>
				<p class="course-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
			<?php endif; ?>

			<?php
			zandi_button(
				array(
					'label'    => $zandi_owned ? 'رفتن به دوره' : 'مشاهده دوره',
					'sr_label' => ( $zandi_owned ? 'رفتن به دوره ' : 'مشاهده دوره ' ) . $zandi_title,
					'url'      => $zandi_owned ? zandi_panel_url() . '#my-courses' : $zandi_url,
					'variant'  => 'secondary',
					'size'     => 'sm',
					'class'    => 'course-card__cta',
					'icon'     => zandi_arrow_forward(),
				)
			);
			?>
		</div>
	</article>
</div>
