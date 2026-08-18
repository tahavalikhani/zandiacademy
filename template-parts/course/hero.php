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
		<?php
		zandi_breadcrumb(
			array(
				array(
					'label' => 'خانه',
					'url'   => home_url( '/' ),
				),
				array(
					'label' => 'دوره‌ها',
					'url'   => zandi_section_url( 'courses' ),
				),
				array( 'label' => $course['short_name'] ),
			)
		);
		?>

		<div class="c-hero__grid">

			<?php
			/*
			 * No `.reveal` on either half of this hero, for the reason spelled
			 * out in template-parts/home/hero.php: `.reveal` is `opacity: 0`
			 * until deferred theme.js adds `is-visible`, so the course title —
			 * the largest paint on the page — stayed invisible until the whole
			 * document had parsed and the script had run.
			 *
			 * Both halves are above the fold, where a scroll-reveal never
			 * animates anyway: the observer finds them already in view and goes
			 * straight to the finished state.
			 */
			?>
			<div class="c-hero__copy">
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
					<?php
					/*
					 * Reading, not buying. This button used to say «ثبت‌نام در
					 * دوره …» and jump to the card a few centimetres away —
					 * asking someone to commit before they had been told
					 * anything. It now opens the page proper: #about-course is
					 * the first section that actually describes the course.
					 * The card below still sells; this one informs.
					 */
					?>
					<a class="c-btn c-btn--primary" href="#about-course">✦ اطلاعات کامل ✦</a>
					<a class="c-btn c-btn--ghost" href="#sample-lesson">نمونه تدریس رو ببین ▶</a>
				</div>
			</div>

			<aside class="c-infocard" aria-labelledby="infocard-title">
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

					<?php
					$zandi_enrol  = zandi_course_enrol_state( $course['slug'] );
					$zandi_notice = zandi_enrol_notice();

					/*
					 * The bounce message and the control cannot both be #enrol.
					 * They were, and a duplicate id means the browser picks one
					 * — so a student redirected back with «سبد خرید باز نشد»
					 * could land on the button instead of the explanation. When
					 * there is something to say, the message is the target.
					 */
					if ( '' !== $zandi_notice ) :
						?>
						<p class="c-enrol-note" role="status" id="enrol"><?php echo esc_html( $zandi_notice ); ?></p>
						<?php
					endif;

					zandi_enrol_control(
						$course,
						array(
							'label' => 'ثبت‌نام در دوره',
							'block' => true,
							'id'    => '' === $zandi_notice ? 'enrol' : '',
						)
					);
					?>

					<div class="c-infocard__pay">
						<?php if ( 'buy' === $zandi_enrol ) : ?>
							<span>پرداخت از ایران: درگاه بانکی</span>
							<span>پرداخت از خارج: کارت به کارت</span>
						<?php else : ?>
							<span>برای ثبت‌نام و پرداخت، از صفحه تماس هماهنگ می‌کنیم.</span>
						<?php endif; ?>
					</div>
				</div>
			</aside>

		</div>
	</div>
</section>
