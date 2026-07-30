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
				<div class="reveal">
					<?php zandi_badge( $hero['badge'], 'rouge', array( 'dot' => true ) ); ?>
				</div>

				<h1 class="hero__title reveal" id="hero-title"><?php echo zandi_bidi( $hero['title'] ); ?></h1>

				<p class="hero__description reveal"><?php echo zandi_bidi( $hero['description'] ); ?></p>

				<div class="hero__actions reveal">
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

				<ul class="hero__highlights reveal">
					<?php foreach ( $hero['highlights'] as $highlight ) : ?>
						<li>
							<span class="hero__check"><?php zandi_icon( 'check', array( 'stroke' => 2.4 ) ); ?></span>
							<?php echo esc_html( $highlight ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="hero__visual-col">
				<?php
				/*
				 * Shima's portrait, with the product-style cards floating over
				 * it. The panel previously held a wordmark and the CEFR ladder
				 * over engraved guilloché; the engraving stays as the layer
				 * behind the photo, so a deployment without the image file still
				 * shows a designed panel rather than an empty box.
				 *
				 * The photo is above the fold, so it is eager and high priority —
				 * lazy-loading it would only delay the largest paint.
				 */
				$zandi_hero_photo = zandi_shima_photo( 'portrait' );
				?>
				<div class="hero-visual">
					<div class="hero-visual__glow" aria-hidden="true"></div>

					<div class="hero-visual__panel">
						<?php zandi_engraving( 'hero' ); ?>

						<?php if ( $zandi_hero_photo ) : ?>
							<img
								class="hero-visual__photo"
								src="<?php echo esc_url( $zandi_hero_photo ); ?>"
								alt="شیما زندی، مدرس و بنیان‌گذار آکادمی زندی"
								width="900"
								height="1125"
								decoding="async"
								fetchpriority="high"
							>
						<?php endif; ?>

						<?php zandi_tricolore(); ?>
					</div>

					<div class="float-card float-card--progress">
						<div class="float-card__head">
							<span class="float-card__label">پیشرفت این ترم</span>
							<span class="float-card__value"><?php echo esc_html( zandi_fa_digits( '82٪' ) ); ?></span>
						</div>
						<div class="float-card__track">
							<span class="float-card__bar"></span>
						</div>
						<p class="float-card__note">سطح A2 — آماده ورود به B1</p>
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

					<?php
					// No reviews have been collected yet, so a star rating here
					// would be invented. This is the one audience figure that is
					// real and checkable.
					?>
					<div class="float-card float-card--rating">
						<?php zandi_icon( 'users', array( 'class' => 'float-card__pill-icon' ) ); ?>
						<span class="float-card__rating-value"><?php echo esc_html( zandi_fa_digits( '142' ) ); ?> هزار دنبال‌کننده</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
