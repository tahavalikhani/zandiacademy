<?php
/**
 * Homepage hero.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$hero = zandi_hero();
?>

<section class="hero" id="home" aria-labelledby="hero-title">
	<div class="hero__bg" aria-hidden="true">
		<div class="hero__bg-wash"></div>
		<span class="hero__bg-navy"></span>
		<span class="hero__bg-rouge"></span>
	</div>

	<div class="container">
		<div class="hero__inner">
			<div class="hero__content">
				<?php if ( $hero['badge'] ) : ?>
					<div>
						<?php zandi_badge( $hero['badge'], 'rouge', array( 'dot' => true ) ); ?>
					</div>
				<?php endif; ?>

				<?php
				/*
				 * NO `.reveal` ON THESE THREE, AND IT IS NOT AN OVERSIGHT.
				 *
				 * `.reveal` is `opacity: 0` until theme.js adds `is-visible`,
				 * and theme.js is deferred — so the headline, the paragraph and
				 * the buttons were invisible from first paint until the whole
				 * document had parsed and the script had run. That window is
				 * exactly the one Largest Contentful Paint is measured in, so
				 * the site was reporting a slow largest paint for text that had
				 * been ready the entire time.
				 *
				 * The `no-js` rule in style.css does not cover this. It saves
				 * the page when scripts are switched off; it does nothing in the
				 * ordinary case where scripts are on and simply have not run
				 * yet, which is every first visit.
				 *
				 * A scroll-reveal on content that is above the fold never
				 * animates anyway — it is already in view when the observer
				 * starts, so it goes straight to `is-visible`. Nothing is lost
				 * visually. This is the same reasoning that keeps `.reveal` off
				 * the account and placement pages.
				 *
				 * The highlighter sweep is untouched: it is one CSS animation on
				 * the inner span, needs no JavaScript, and is switched off under
				 * prefers-reduced-motion.
				 */
				?>
				<h1 class="hero__title" id="hero-title"><span class="hero__mark"><?php echo zandi_bidi( $hero['title'] ); ?></span></h1>

				<p class="hero__description"><?php echo zandi_bidi( $hero['description'] ); ?></p>

				<div class="hero__actions">
					<?php
					zandi_button(
						array(
							'label' => $hero['primary']['label'],
							'url'   => $hero['primary']['url'],
							'size'  => 'lg',
							'class' => 'btn--block btn--block-mobile',
							'icon'  => zandi_arrow_forward(),
						)
					);

					zandi_button(
						array(
							'label'   => $hero['secondary']['label'],
							'url'     => $hero['secondary']['url'],
							'variant' => 'secondary',
							'size'    => 'lg',
							'class'   => 'btn--block btn--block-mobile',
						)
					);
					?>
				</div>

				<?php if ( $hero['highlights'] ) : ?>
					<ul class="hero__highlights reveal">
						<?php foreach ( $hero['highlights'] as $highlight ) : ?>
							<li>
								<span class="hero__check"><?php zandi_icon( 'check', array( 'stroke' => 2.4 ) ); ?></span>
								<?php echo esc_html( $highlight ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="hero__visual-col">
				<?php
				/*
				 * Shima's portrait, shown whole. The panel takes its shape from
				 * the file rather than imposing one, so nothing is cropped away —
				 * it previously forced 1:1 above 640px and cut the top and bottom
				 * off the frame.
				 *
				 * Above the fold, so it is eager and high priority; lazy-loading
				 * it would only delay the largest paint.
				 *
				 * And, for the same reason, it is the one image on the site
				 * that most needs a srcset: this file is 1282px wide and a
				 * phone draws it about 350px wide. See zandi_image_srcset().
				 */
				$zandi_hero_photo  = zandi_shima_photo( 'portrait' );
				$zandi_hero_srcset = zandi_image_srcset( 'assets/images/shima.webp' );
				?>
				<div class="hero-visual">
					<div class="hero-visual__glow" aria-hidden="true"></div>

					<?php
					/*
					 * Nothing is laid over the photograph. The guilloché engraving
					 * and the navy scrim that used to sit here showed through it
					 * and read as a rendering fault rather than a flourish.
					 */
					?>
					<div class="hero-visual__panel<?php echo $zandi_hero_photo ? ' hero-visual__panel--photo' : ''; ?>">
						<?php if ( $zandi_hero_photo ) : ?>
							<img
								class="hero-visual__photo"
								src="<?php echo esc_url( $zandi_hero_photo ); ?>"
								<?php if ( $zandi_hero_srcset ) : ?>
									srcset="<?php echo esc_attr( $zandi_hero_srcset ); ?>"
									<?php
									/*
									 * The column is capped at 34rem and centred
									 * below 1024px, and is one half of the grid
									 * above it — which lands at roughly the same
									 * width. One breakpoint describes both.
									 */
									?>
									sizes="(min-width: 640px) 34rem, calc(100vw - 2.5rem)"
								<?php endif; ?>
								alt="شیما زندی، مدرس و بنیان‌گذار آکادمی زندی"
								width="1282"
								height="1639"
								decoding="async"
								fetchpriority="high"
							>
						<?php endif; ?>

						<?php zandi_tricolore(); ?>
					</div>

					<div class="float-card float-card--live">
						<div class="float-card__live-head">
							<span class="float-card__pulse" aria-hidden="true">
								<span></span>
								<span></span>
							</span>
							<?php
							// The courses are recorded, not live — the FAQ says so
							// outright. What is genuinely live is the end-of-course
							// interview, so that is what this card shows.
							?>
							<span class="float-card__live-title">مصاحبه پایان دوره</span>
						</div>
						<p class="float-card__meta">رودررو با شیما زندی، توی گوگل میت</p>
						<div class="float-card__meta-row">
							<?php zandi_icon( 'chat' ); ?>
							<span><?php echo esc_html( zandi_fa_digits( '15' ) ); ?> دقیقه فرانسه حرف می‌زنیم</span>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</section>
