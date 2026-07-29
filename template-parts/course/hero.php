<?php
/**
 * Hero with the sticky course-info card.
 *
 * The card sits opposite the copy on desktop and sticks while the page scrolls,
 * so the price and the enrol button are never more than a glance away. Below
 * 1024px it drops under the copy and stops sticking — a sticky card on a 360px
 * screen would eat the viewport.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
?>

<section class="c-hero" id="register" aria-labelledby="hero-title">
	<div class="c-container">
		<div class="c-hero__grid">

			<div class="c-hero__copy reveal">
				<p class="c-eyebrow">
					<?php
					/*
					 * The level code is Latin, so it is isolated and set in the
					 * display face; the surrounding Persian keeps its own font.
					 */
					$parts = explode( '·', $course['eyebrow'] );
					echo esc_html( trim( $parts[0] ) );
					?>
					<span dir="ltr" class="latin">·&nbsp;<?php echo esc_html( $course['level'] ); ?></span>
				</p>

				<h1 class="c-hero__title" id="hero-title"><?php echo zandi_bidi( $course['title'] ); ?></h1>

				<p class="c-hero__subtitle"><?php echo zandi_bidi( $course['subtitle'] ); ?></p>

				<div class="c-hero__actions">
					<a class="c-btn c-btn--primary" href="#enrol"><?php echo esc_html( $course['cta_primary'] ); ?></a>
					<a class="c-btn c-btn--ghost" href="#sample-lesson">نمونه تدریس رو ببین ▶</a>
				</div>
			</div>

			<aside class="c-infocard reveal reveal--scale" aria-labelledby="infocard-title">
				<h2 class="c-infocard__title" id="infocard-title">اطلاعات دوره</h2>

				<ul class="c-infocard__list">
					<?php foreach ( zandi_course_info_rows( $course ) as $row ) : ?>
						<li>
							<span class="c-infocard__icon" aria-hidden="true"><?php echo esc_html( $row['icon'] ); ?></span>
							<span><?php echo esc_html( $row['text'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="c-infocard__prices">
					<p class="c-price">
						<?php echo esc_html( zandi_price_toman( $course['price_toman'] ) ); ?>
						<span class="c-price__unit">تومان</span>
					</p>
					<p class="c-price__alt">
						یا <span dir="ltr" class="latin"><?php echo esc_html( zandi_fa_digits( $course['price_euro'] ) ); ?>&nbsp;€</span>
					</p>

					<?php /* TODO: replace with the real ZarinPal checkout handoff. */ ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="enrol">
						<input type="hidden" name="action" value="zandi_enrol">
						<input type="hidden" name="course" value="<?php echo esc_attr( $course['slug'] ); ?>">
						<?php wp_nonce_field( 'zandi_enrol', 'zandi_enrol_nonce' ); ?>
						<button type="submit" class="c-btn c-btn--primary c-btn--block">ثبت‌نام در دوره</button>
					</form>

					<div class="c-infocard__pay">
						<span>پرداخت از ایران: درگاه بانکی</span>
						<span>پرداخت از خارج: کارت به کارت</span>
					</div>
				</div>
			</aside>

		</div>
	</div>
</section>
