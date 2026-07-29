<?php
/**
 * Other courses.
 *
 * The course the visitor is already on is omitted.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course  = $args['course'];
$others  = zandi_courses_data();
$notify  = isset( $_GET['notify'] ) ? sanitize_key( wp_unslash( $_GET['notify'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag.
unset( $others[ $course['slug'] ] );
?>

<section class="c-section" id="other-courses" aria-labelledby="other-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="other-title">دوره‌های دیگر آکادمی</h2>
		</div>

		<div class="c-other reveal-group">
			<?php foreach ( $others as $other ) : ?>
				<article class="c-other__card reveal reveal--scale">
					<p class="c-other__level" dir="ltr"><?php echo esc_html( $other['level'] ); ?></p>
					<h3 class="c-other__title"><?php echo zandi_bidi( $other['short_name'] ); ?></h3>
					<p class="c-other__price">
						<?php echo esc_html( zandi_price_toman( $other['price_toman'] ) ); ?> تومان
					</p>
					<a class="c-other__link" href="<?php echo esc_url( home_url( '/courses/' . $other['slug'] . '/' ) ); ?>">
						<span>مشاهده دوره</span>
						<?php zandi_icon( zandi_arrow_forward() ); ?>
					</a>
				</article>
			<?php endforeach; ?>

			<?php foreach ( zandi_upcoming_courses() as $index => $soon ) : ?>
				<article class="c-other__card c-other__card--soon reveal reveal--scale">
					<span class="c-soon-badge">به‌زودی 🔜</span>
					<h3 class="c-other__title"><?php echo zandi_bidi( $soon['title'] ); ?></h3>
					<p class="c-other__price">
						هنوز آماده نشده، ولی نزدیکه. ایمیلت رو بذار تا اولین نفری باشی که خبردار می‌شه.
					</p>

					<?php /* TODO: connect to a mailing list; this only validates and redirects. */ ?>
					<form class="c-notify" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="zandi_notify">
						<?php wp_nonce_field( 'zandi_notify', 'zandi_notify_nonce' ); ?>

						<label class="screen-reader-text" for="notify-email-<?php echo esc_attr( $index ); ?>">
							ایمیل برای اطلاع از <?php echo zandi_bidi( $soon['title'] ); ?>
						</label>
						<input
							class="c-notify__input"
							type="email"
							id="notify-email-<?php echo esc_attr( $index ); ?>"
							name="zandi_email"
							placeholder="ایمیل"
							dir="ltr"
							required
						>
						<button type="submit" class="c-btn c-btn--ghost c-btn--sm">خبرم کن</button>
					</form>

					<?php if ( 'ok' === $notify ) : ?>
						<p class="c-notify__msg" role="status">ایمیلت ثبت شد. خبرت می‌کنیم.</p>
					<?php elseif ( 'invalid' === $notify ) : ?>
						<p class="c-notify__msg" role="status">ایمیل معتبر نیست.</p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
