<?php
/**
 * The reassurance column beside the sign-in / sign-up card.
 *
 * Renders *after* the card in the DOM on purpose. On a phone it therefore falls
 * below the form instead of pushing it off the first screen, and a keyboard
 * reaches the number field before any of this. The grid in panel.css does not
 * reorder them, so source order and reading order stay the same thing.
 *
 * Nothing here carries `.reveal`: an auth page must not depend on a script to
 * become visible.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_benefits = zandi_auth_benefits();

if ( empty( $zandi_benefits['items'] ) ) {
	return;
}
?>

<aside class="auth-aside" aria-labelledby="auth-aside-title">
	<h2 class="auth-aside__title" id="auth-aside-title"><?php echo esc_html( $zandi_benefits['title'] ); ?></h2>

	<ul class="auth-aside__list">
		<?php foreach ( $zandi_benefits['items'] as $zandi_item ) : ?>
			<li class="auth-aside__item">
				<span class="auth-aside__icon" aria-hidden="true">
					<?php zandi_icon( $zandi_item['icon'] ); ?>
				</span>

				<span class="auth-aside__text">
					<span class="auth-aside__label"><?php echo esc_html( $zandi_item['title'] ); ?></span>
					<span class="auth-aside__body"><?php echo esc_html( $zandi_item['body'] ); ?></span>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( ! empty( $zandi_benefits['note'] ) ) : ?>
		<p class="auth-aside__note"><?php echo esc_html( $zandi_benefits['note'] ); ?></p>
	<?php endif; ?>

	<?php /* /contact/, so the channel behind it can change — zandi_support_url(). */ ?>
	<p class="auth-aside__help">
		<a href="<?php echo esc_url( zandi_support_url() ); ?>">
			<?php zandi_icon( 'chat' ); ?>
			<span>مشکلی پیش اومد؟ از صفحه تماس بپرس</span>
		</a>
	</p>
</aside>
