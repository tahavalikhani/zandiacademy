<?php
/**
 * The podcast's opening block: cover, promise, facts, and the way to the price.
 *
 * WHY THE COVER AND THE TITLE ARE SIBLINGS AND NOT NESTED.
 *
 * They sit in one grid with named areas, so the same three boxes read as a
 * podcast-app row on a phone — small square, title beside it — and as a poster
 * beside its copy on a laptop. Nesting the cover inside either column would
 * mean rendering the image twice to get both, and two <img> of the owner's
 * artwork is two things to keep in step for one that is ever visible.
 *
 * The button is an anchor to #plans rather than a checkout link. The plans sit
 * at the end because choosing between three durations is a decision somebody
 * makes after they know what they are buying — but the *offer* has to be
 * visible in the first screen, or a visitor has no reason to keep reading. The
 * price beside it answers the question the button raises; without it people
 * scroll to the bottom just to find out, and some leave instead.
 *
 * No `.reveal` anywhere, and that is about speed rather than taste. `.reveal`
 * is opacity 0 until deferred theme.js runs, so anything above the fold wearing
 * it is invisible through the whole window in which Largest Contentful Paint is
 * measured — text that was ready the entire time, reported as a slow paint.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy     = zandi_podcast_copy();
$zandi_facts    = zandi_podcast_facts();
$zandi_cover    = zandi_podcast_cover();
$zandi_from     = zandi_podcast_starting_price();
$zandi_episodes = zandi_podcast_available_episodes();
?>

<div class="page-hero podcast-hero">
	<div class="container">
		<?php
		zandi_breadcrumb(
			array(
				array( 'label' => 'خانه', 'url' => home_url( '/' ) ),
				array( 'label' => $zandi_copy['title'] ),
			)
		);
		?>

		<div class="podcast-hero__grid<?php echo $zandi_cover ? '' : ' podcast-hero__grid--nocover'; ?>">

			<?php if ( $zandi_cover ) : ?>
				<div class="podcast-hero__cover">
					<?php
					/*
					 * The owner's artwork, uncropped — she frames her images and
					 * the layout adapts. `width` and `height` reserve the box
					 * before the file arrives, so the title beside it does not
					 * jump when it does.
					 */
					?>
					<img
						src="<?php echo esc_url( $zandi_cover ); ?>"
						alt="<?php echo esc_attr( $zandi_copy['cover_alt'] ); ?>"
						width="600"
						height="600"
						loading="eager"
						decoding="async"
					>
				</div>
			<?php endif; ?>

			<div class="podcast-hero__head">
				<?php zandi_badge( $zandi_copy['eyebrow'], 'pod' ); ?>

				<?php
				/*
				 * zandi_bidi(), not esc_html(): the title carries a French name
				 * inside a Persian page, and a bare Latin island in a
				 * right-to-left paragraph is laid out against its neighbours —
				 * the two words come out in the wrong order.
				 */
				?>
				<h1 class="page-hero__title podcast-hero__title"><?php echo zandi_bidi( $zandi_copy['title'] ); ?></h1>
			</div>

			<div class="podcast-hero__rest">
				<p class="page-hero__lead podcast-hero__lead"><?php echo zandi_bidi( $zandi_copy['lead'] ); ?></p>

				<div class="podcast-hero__actions">
					<?php
					zandi_button(
						array(
							'label' => $zandi_copy['hero_cta'],
							'url'   => '#plans',
							'size'  => 'lg',
						)
					);
					?>

					<?php if ( $zandi_from ) : ?>
						<p class="podcast-hero__from">
							<?php echo esc_html( $zandi_copy['hero_from'] ); ?>
							<span class="podcast-hero__from-amount"><?php echo esc_html( zandi_price_toman( $zandi_from ) ); ?></span>
							<?php echo esc_html( $zandi_copy['toman'] ); ?>
						</p>
					<?php endif; ?>
				</div>

				<?php
				/*
				 * The quiet second door, and only when there is something behind
				 * it. A «گوش بده» link pointing at an empty section is worse
				 * than no link at all.
				 */
				if ( $zandi_episodes ) :
					?>
					<p class="podcast-hero__listen">
						<a href="#episodes">
							<?php zandi_icon( 'play', array( 'fill' => 'currentColor', 'stroke' => 0 ) ); ?>
							<span><?php echo esc_html( $zandi_copy['hero_listen'] ); ?></span>
						</a>
					</p>
				<?php endif; ?>
			</div>

		</div>

		<?php if ( $zandi_facts ) : ?>
			<dl class="podcast-facts" aria-label="<?php echo esc_attr( $zandi_copy['facts_title'] ); ?>">
				<?php foreach ( $zandi_facts as $zandi_fact ) : ?>
					<div class="podcast-facts__item">
						<?php
						/*
						 * Decoration over a label that already says the same
						 * thing, so it is hidden from assistive tech — which is
						 * what zandi_icon() does by default when given no
						 * `label`. Optional in the data: a fact without an icon
						 * simply has no glyph.
						 */
						if ( ! empty( $zandi_fact['icon'] ) ) :
							?>
							<span class="podcast-facts__icon"><?php zandi_icon( $zandi_fact['icon'] ); ?></span>
						<?php endif; ?>

						<dt class="podcast-facts__label"><?php echo esc_html( $zandi_fact['label'] ); ?></dt>
						<dd class="podcast-facts__value">
							<?php echo zandi_bidi( $zandi_fact['value'] ); ?>
							<?php if ( ! empty( $zandi_fact['note'] ) ) : ?>
								<span class="podcast-facts__note"><?php echo esc_html( $zandi_fact['note'] ); ?></span>
							<?php endif; ?>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>
</div>
