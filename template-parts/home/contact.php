<?php
/**
 * Contact.
 *
 * THE ONE PLACE ON THE SITE THAT NAMES A SUPPORT CHANNEL.
 *
 * Every «از صفحه تماس بپرس» elsewhere points here through zandi_support_url(),
 * and none of that copy names an app. So moving support — Telegram to WhatsApp,
 * WhatsApp to a form — is an edit to zandi_contact() and the cards below, not
 * to forty strings spread across sixteen files, which is what it used to be.
 *
 * The heading deliberately does not name the channel either: it describes the
 * page, which stays true whatever the cards end up holding.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// Loaded bare from the homepage, or with flags from template-section.php.
$zandi_args = isset( $args ) && is_array( $args ) ? $args : array();

$zandi_contact = zandi_contact();
?>

<section class="section" id="contact-details" aria-labelledby="contact-details-title">
	<div class="container">
		<?php
		zandi_maybe_section_heading(
			$zandi_args,
			array(
				'id'          => 'contact-details',
				'eyebrow'     => 'ارتباط با من',
				'title'       => 'راه‌های ارتباط با من',
				'description' => 'هر سوالی داری بپرس — از انتخاب دوره تا جزئیاتش. اینجا کسی بابت سوال ساده پرسیدن قضاوت نمی‌شه.',
			)
		);
		?>

		<div class="contact-cards reveal-group">
			<?php if ( $zandi_contact['telegram'] ) : ?>
				<a class="contact-card reveal reveal--scale" href="<?php echo esc_url( $zandi_contact['telegram'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="icon-tile"><?php zandi_icon( 'telegram' ); ?></span>
					<span class="contact-card__label">تلگرام</span>
					<span class="contact-card__value" dir="ltr"><?php echo esc_html( $zandi_contact['telegram_name'] ); ?></span>
					<span class="contact-card__note">پشتیبانی ۲۴ ساعته</span>
				</a>
			<?php endif; ?>

			<?php if ( $zandi_contact['instagram'] ) : ?>
				<a class="contact-card reveal reveal--scale" href="<?php echo esc_url( $zandi_contact['instagram'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="icon-tile"><?php zandi_icon( 'instagram' ); ?></span>
					<span class="contact-card__label">اینستاگرام</span>
					<span class="contact-card__value" dir="ltr">@shima_zandi.fr</span>
					<span class="contact-card__note">تمرین روزانه و نمونه تدریس</span>
				</a>
			<?php endif; ?>

			<?php if ( $zandi_contact['email'] ) : ?>
				<a class="contact-card reveal reveal--scale" href="mailto:<?php echo esc_attr( $zandi_contact['email'] ); ?>">
					<span class="icon-tile"><?php zandi_icon( 'mail' ); ?></span>
					<span class="contact-card__label">ایمیل</span>
					<span class="contact-card__value" dir="ltr"><?php echo esc_html( $zandi_contact['email'] ); ?></span>
					<span class="contact-card__note">برای سوال‌های غیرفوری</span>
				</a>
			<?php endif; ?>
		</div>

		<?php /* TODO: a public email address is still needed — see zandi_contact(). */ ?>
	</div>
</section>
