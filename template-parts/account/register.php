<?php
/**
 * The sign-up form.
 *
 * Phone-first: the mobile number is the account, so it is the second field and
 * email is optional. Spam is caught by a honeypot rather than a captcha —
 * Google reCAPTCHA does not load in Iran.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy      = zandi_register_copy();
$zandi_fields    = zandi_auth_fields();
$zandi_contact   = zandi_contact();
$zandi_shortcode = zandi_register_shortcode();
$zandi_open      = zandi_registration_open();
?>

<section class="auth" aria-labelledby="auth-title">
	<div class="card auth__card">
		<?php zandi_badge( $zandi_copy['eyebrow'], 'rouge' ); ?>

		<h1 class="auth__title" id="auth-title"><?php echo esc_html( $zandi_copy['title'] ); ?></h1>
		<p class="auth__lead"><?php echo esc_html( $zandi_copy['description'] ); ?></p>

		<?php get_template_part( 'template-parts/account/errors' ); ?>

		<?php if ( ! $zandi_open ) : ?>
			<?php
			/*
			 * Settings → General → «هر کسی می‌تواند ثبت‌نام کند» is still off.
			 * The form is withheld rather than shown and then refused.
			 */
			?>
			<p class="auth__closed"><?php echo esc_html( $zandi_copy['closed'] ); ?></p>

			<?php
			zandi_button(
				array(
					'label' => 'باز کردن تلگرام',
					'url'   => $zandi_contact['telegram'],
					'size'  => 'md',
					'class' => 'btn--block',
					'attrs' => array( 'rel' => 'noopener' ),
				)
			);
			?>

		<?php elseif ( $zandi_shortcode ) : ?>
			<?php echo do_shortcode( $zandi_shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output is the plugin's own. ?>

		<?php else : ?>
			<form class="auth__form" method="post" action="<?php echo esc_url( zandi_register_url() ); ?>" novalidate>
				<?php wp_nonce_field( 'zandi_register', 'zandi_register_nonce' ); ?>

				<p class="field">
					<label class="field__label" for="zandi-name"><?php echo esc_html( $zandi_fields['name'] ); ?></label>
					<input
						class="field__control"
						type="text"
						id="zandi-name"
						name="zandi_name"
						autocomplete="name"
						value="<?php echo esc_attr( zandi_posted( 'zandi_name' ) ); ?>"
						required
					>
				</p>

				<p class="field">
					<label class="field__label" for="zandi-phone"><?php echo esc_html( $zandi_fields['phone'] ); ?></label>
					<input
						class="field__control"
						type="tel"
						id="zandi-phone"
						name="zandi_phone"
						inputmode="tel"
						autocomplete="tel"
						dir="ltr"
						placeholder="<?php echo esc_attr( zandi_fa_digits( '09120000000' ) ); ?>"
						value="<?php echo esc_attr( zandi_posted( 'zandi_phone' ) ); ?>"
						aria-describedby="zandi-phone-hint"
						required
					>
					<span class="field__hint" id="zandi-phone-hint"><?php echo esc_html( $zandi_fields['phone_hint'] ); ?></span>
				</p>

				<p class="field">
					<label class="field__label" for="zandi-email"><?php echo esc_html( $zandi_fields['email'] ); ?></label>
					<input
						class="field__control"
						type="email"
						id="zandi-email"
						name="zandi_email"
						autocomplete="email"
						dir="ltr"
						value="<?php echo esc_attr( zandi_posted( 'zandi_email' ) ); ?>"
						aria-describedby="zandi-email-hint"
					>
					<span class="field__hint" id="zandi-email-hint"><?php echo esc_html( $zandi_fields['email_hint'] ); ?></span>
				</p>

				<p class="field">
					<label class="field__label" for="zandi-password"><?php echo esc_html( $zandi_fields['password'] ); ?></label>
					<input
						class="field__control"
						type="password"
						id="zandi-password"
						name="zandi_password"
						autocomplete="new-password"
						dir="ltr"
						minlength="8"
						aria-describedby="zandi-password-hint"
						required
					>
					<span class="field__hint" id="zandi-password-hint"><?php echo esc_html( $zandi_fields['password_hint'] ); ?></span>
				</p>

				<?php
				/*
				 * Honeypot. Hidden from people, invisible to assistive tech and
				 * skipped by the tab order — anything that fills it is a bot.
				 */
				?>
				<p class="auth__hp" aria-hidden="true">
					<label for="zandi-website">این فیلد را خالی بگذارید</label>
					<input type="text" id="zandi-website" name="zandi_website" tabindex="-1" autocomplete="off" value="">
				</p>

				<?php
				zandi_button(
					array(
						'label' => $zandi_copy['submit'],
						'type'  => 'submit',
						'size'  => 'md',
						'class' => 'btn--block',
					)
				);
				?>
			</form>
		<?php endif; ?>

		<p class="auth__aside">
			<?php echo esc_html( $zandi_copy['alt_prompt'] ); ?>
			<a href="<?php echo esc_url( zandi_login_url() ); ?>"><?php echo esc_html( $zandi_copy['alt_action'] ); ?></a>
		</p>
	</div>
</section>
