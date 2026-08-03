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
$zandi_open      = zandi_registration_open();

/*
 * Same resolver the login page uses. Without this /register/ fell back to the
 * theme's own password form while /login/ showed Digits — a student would sign
 * up with a password and then be unable to sign in, because the login form
 * wants a code.
 */
$zandi_provider_form = zandi_auth_form_markup( 'register' );

// Per-field messages. The summary above the form still lists all of them.
$zandi_err_name     = zandi_auth_field_error( 'name' );
$zandi_err_phone    = zandi_auth_field_error( 'phone' );
$zandi_err_email    = zandi_auth_field_error( 'email' );
$zandi_err_password = zandi_auth_field_error( 'password' );
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
					'label' => 'تماس با من',
					'url'   => zandi_support_url(),
					'size'  => 'md',
					'class' => 'btn--block',
				)
			);
			?>

		<?php elseif ( '' !== $zandi_provider_form ) : ?>
			<div class="auth__provider">
				<?php echo $zandi_provider_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The provider's own markup. ?>
			</div>

		<?php else : ?>
			<form class="auth__form" method="post" action="<?php echo esc_url( zandi_register_url() ); ?>" data-auth-form novalidate>
				<?php wp_nonce_field( 'zandi_register', 'zandi_register_nonce' ); ?>

				<p class="field<?php echo '' !== $zandi_err_name ? ' field--invalid' : ''; ?>">
					<label class="field__label" for="zandi-name"><?php echo esc_html( $zandi_fields['name'] ); ?></label>
					<input
						class="field__control"
						type="text"
						id="zandi-name"
						name="zandi_name"
						autocomplete="name"
						value="<?php echo esc_attr( zandi_posted( 'zandi_name' ) ); ?>"
						<?php if ( '' !== $zandi_err_name ) : ?>
							aria-invalid="true" aria-describedby="zandi-name-error"
						<?php endif; ?>
						required
					>
					<?php if ( '' !== $zandi_err_name ) : ?>
						<span class="field__error" id="zandi-name-error"><?php echo esc_html( $zandi_err_name ); ?></span>
					<?php endif; ?>
				</p>

				<p class="field<?php echo '' !== $zandi_err_phone ? ' field--invalid' : ''; ?>">
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
						aria-describedby="zandi-phone-hint<?php echo '' !== $zandi_err_phone ? ' zandi-phone-error' : ''; ?>"
						<?php echo '' !== $zandi_err_phone ? 'aria-invalid="true"' : ''; ?>
						required
					>
					<?php if ( '' !== $zandi_err_phone ) : ?>
						<span class="field__error" id="zandi-phone-error"><?php echo esc_html( $zandi_err_phone ); ?></span>
					<?php endif; ?>
					<span class="field__hint" id="zandi-phone-hint"><?php echo esc_html( $zandi_fields['phone_hint'] ); ?></span>
				</p>

				<p class="field<?php echo '' !== $zandi_err_email ? ' field--invalid' : ''; ?>">
					<label class="field__label" for="zandi-email">
						<?php echo esc_html( $zandi_fields['email'] ); ?>
					</label>
					<input
						class="field__control"
						type="email"
						id="zandi-email"
						name="zandi_email"
						autocomplete="email"
						dir="ltr"
						value="<?php echo esc_attr( zandi_posted( 'zandi_email' ) ); ?>"
						aria-describedby="zandi-email-hint<?php echo '' !== $zandi_err_email ? ' zandi-email-error' : ''; ?>"
						<?php echo '' !== $zandi_err_email ? 'aria-invalid="true"' : ''; ?>
					>
					<?php if ( '' !== $zandi_err_email ) : ?>
						<span class="field__error" id="zandi-email-error"><?php echo esc_html( $zandi_err_email ); ?></span>
					<?php endif; ?>
					<span class="field__hint" id="zandi-email-hint"><?php echo esc_html( $zandi_fields['email_hint'] ); ?></span>
				</p>

				<p class="field<?php echo '' !== $zandi_err_password ? ' field--invalid' : ''; ?>">
					<label class="field__label" for="zandi-password"><?php echo esc_html( $zandi_fields['password'] ); ?></label>

					<span class="field__wrap" dir="ltr">
						<input
							class="field__control field__control--with-toggle"
							type="password"
							id="zandi-password"
							name="zandi_password"
							autocomplete="new-password"
							dir="ltr"
							minlength="8"
							aria-describedby="zandi-password-hint<?php echo '' !== $zandi_err_password ? ' zandi-password-error' : ''; ?>"
							<?php echo '' !== $zandi_err_password ? 'aria-invalid="true"' : ''; ?>
							required
						>
						<button
							class="field__toggle"
							type="button"
							data-password-toggle="zandi-password"
							aria-controls="zandi-password"
							aria-pressed="false"
						>نمایش</button>
					</span>

					<?php if ( '' !== $zandi_err_password ) : ?>
						<span class="field__error" id="zandi-password-error"><?php echo esc_html( $zandi_err_password ); ?></span>
					<?php endif; ?>
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
