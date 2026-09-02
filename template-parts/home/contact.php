<?php
/**
 * Contact.
 *
 * THE PUBLIC FACE OF zandi_contact(). Every «از صفحه تماس بپرس» elsewhere points
 * here through zandi_support_url(), and none of that copy names an app. So
 * moving support — Telegram to WhatsApp, WhatsApp to a form — is an edit to
 * zandi_contact(), not to forty strings spread across sixteen files, which is
 * what it used to be. Not one label or note below is written here; they all come
 * out of that array, so this file does not know a channel by name either.
 *
 * The heading deliberately does not name the channel either: it describes the
 * page, which stays true whatever the cards end up holding.
 *
 * SUPPORT LEADS, AND THE CHANNEL IS NOT SUPPORT. Until 2 September 2026 the only
 * card here was the broadcast channel, labelled «تلگرام» and promising
 * «پشتیبانی ۲۴ ساعته» — a promise a channel cannot keep, on a page whose whole
 * job is to be where someone goes when they need an answer. Support now has its
 * own card, first and spanning the row; the channel is honestly a channel.
 *
 * Shima's own Telegram is NOT here. It is in the student panel, which is what
 * having an account is for — see template-parts/panel/support.php.
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
			<?php if ( $zandi_contact['support'] ) : ?>
				<?php
				/*
				 * First and full-width. This is the card the page exists for;
				 * the two below it are places to follow along, not places
				 * anyone replies.
				 *
				 * dir="ltr" belongs on the element holding the ID, never on a
				 * span inside it — «@tav_1089» is a neutral @, Latin letters,
				 * an underscore and digits, which reorder in an RTL line. The
				 * digits stay Latin: a username is not a number, so it must not
				 * go through zandi_fa_digits().
				 */
				?>
				<a class="contact-card contact-card--primary reveal reveal--scale" href="<?php echo esc_url( $zandi_contact['support'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="icon-tile"><?php zandi_icon( 'lifebuoy' ); ?></span>
					<span class="contact-card__label"><?php echo esc_html( $zandi_contact['support_label'] ); ?></span>
					<span class="contact-card__value" dir="ltr"><?php echo esc_html( $zandi_contact['support_name'] ); ?></span>
					<span class="contact-card__note"><?php echo esc_html( $zandi_contact['support_note'] ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( $zandi_contact['instagram'] ) : ?>
				<a class="contact-card reveal reveal--scale" href="<?php echo esc_url( $zandi_contact['instagram'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="icon-tile"><?php zandi_icon( 'instagram' ); ?></span>
					<span class="contact-card__label">اینستاگرام</span>
					<span class="contact-card__value" dir="ltr"><?php echo esc_html( $zandi_contact['instagram_name'] ); ?></span>
					<span class="contact-card__note"><?php echo esc_html( $zandi_contact['instagram_note'] ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( $zandi_contact['telegram'] ) : ?>
				<?php /* A channel to follow. Not support, and no longer labelled as if it were. */ ?>
				<a class="contact-card reveal reveal--scale" href="<?php echo esc_url( $zandi_contact['telegram'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="icon-tile"><?php zandi_icon( 'telegram' ); ?></span>
					<span class="contact-card__label"><?php echo esc_html( $zandi_contact['telegram_label'] ); ?></span>
					<span class="contact-card__value" dir="ltr"><?php echo esc_html( $zandi_contact['telegram_name'] ); ?></span>
					<span class="contact-card__note"><?php echo esc_html( $zandi_contact['telegram_note'] ); ?></span>
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
