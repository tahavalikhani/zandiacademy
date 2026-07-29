<?php
/**
 * Homepage hero.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$hero   = zandi_hero();
$levels = array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' );
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

				<h1 class="hero__title reveal" id="hero-title"><?php echo esc_html( $hero['title'] ); ?></h1>

				<p class="hero__description reveal"><?php echo esc_html( $hero['description'] ); ?></p>

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
				 * Hero artwork: a navy panel engraved with concentric guilloché
				 * arcs — the geometry of French banknotes and Sèvres porcelain
				 * rather than a landmark — with product-style cards floating
				 * over it. Entirely SVG and DOM, so it costs no image bytes and
				 * stays sharp at every density.
				 */
				?>
				<div class="hero-visual">
					<div class="hero-visual__glow" aria-hidden="true"></div>

					<div class="hero-visual__panel">
						<?php
						zandi_engraving( 'hero' );
						zandi_tricolore();
						?>

						<div class="hero-visual__body">
							<div class="hero-visual__wordmark" dir="ltr">
								<span class="hero-visual__eyebrow">Académie Zandi</span>
								<p class="hero-visual__greeting">Bonjour&nbsp;!</p>
								<p class="hero-visual__sub">On commence&nbsp;?</p>
							</div>

							<div class="hero-visual__ladder">
								<div class="hero-visual__levels" dir="ltr">
									<?php foreach ( $levels as $index => $level ) : ?>
										<span class="<?php echo $index <= 2 ? 'is-reached' : ''; ?>"><?php echo esc_html( $level ); ?></span>
									<?php endforeach; ?>
								</div>
								<p class="hero-visual__caption">مسیر استاندارد اروپایی، از پایه تا تسلط کامل</p>
							</div>
						</div>
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
							<span class="float-card__live-title">کلاس مکالمه زنده</span>
						</div>
						<p class="float-card__meta"><?php echo esc_html( zandi_fa_digits( '5' ) ); ?> زبان‌آموز · استاد سمیه زندی</p>
						<div class="float-card__meta-row">
							<?php zandi_icon( 'chat' ); ?>
							<span><?php echo esc_html( zandi_fa_digits( '45' ) ); ?> دقیقه گفت‌وگو</span>
						</div>
					</div>

					<div class="float-card float-card--rating">
						<?php zandi_rating( 5 ); ?>
						<span class="float-card__rating-value"><?php echo esc_html( zandi_fa_digits( '4٫9' ) . ' از ' . zandi_fa_digits( '5' ) ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
