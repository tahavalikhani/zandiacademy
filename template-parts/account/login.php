<?php
/**
 * The sign-in form.
 *
 * Posts to itself so it can re-render with the typed number still in place and
 * the error beside it — see the note at the top of inc/auth.php. Works with
 * JavaScript off; nothing here is enhanced, and nothing carries `.reveal`,
 * because a login form that needs a script to become visible can fail shut.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy     = zandi_login_copy();
$zandi_fields   = zandi_auth_fields();
$zandi_contact  = zandi_contact();
$zandi_redirect = zandi_auth_redirect_target();

// Empty means no OTP plugin is active and the built-in form should be drawn.
$zandi_provider_form = zandi_auth_form_markup( 'login' );

// Per-field messages. The summary above the form still lists all of them.
$zandi_err_identifier = zandi_auth_field_error( 'identifier' );
$zandi_err_password   = zandi_auth_field_error( 'password' );
?>

<section class="auth" aria-labelledby="auth-title">
	<div class="card auth__card">
		<?php zandi_badge( $zandi_copy['eyebrow'], 'rouge' ); ?>

		<h1 class="auth__title" id="auth-title"><?php echo esc_html( $zandi_copy['title'] ); ?></h1>
		<p class="auth__lead"><?php echo esc_html( $zandi_copy['description'] ); ?></p>

		<?php
		get_template_part( 'template-parts/account/errors' );

		// Says why this page appeared, when it appeared on the way to the
		// placement report. Renders nothing otherwise. Both auth pages carry
		// it — someone who already has an account arrives here from /register/.
		zandi_placement_auth_notice();
		?>

		<?php if ( '' !== $zandi_provider_form ) : ?>
			<div class="auth__provider">
				<?php
				/*
				 * An OTP plugin owns the flow. The theme keeps the card, the
				 * heading and the page chrome and hands the form itself over —
				 * see zandi_auth_form_markup().
				 */
				echo $zandi_provider_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The provider's own markup.
				?>
			</div>
		<?php else : ?>
			<form class="auth__form" method="post" action="<?php echo esc_url( zandi_login_url() ); ?>" data-auth-form novalidate>
				<?php wp_nonce_field( 'zandi_login', 'zandi_login_nonce' ); ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $zandi_redirect ); ?>">

				<p class="field<?php echo '' !== $zandi_err_identifier ? ' field--invalid' : ''; ?>">
					<label class="field__label" for="zandi-identifier"><?php echo esc_html( $zandi_fields['identifier'] ); ?></label>
					<input
						class="field__control"
						type="tel"
						id="zandi-identifier"
						name="zandi_identifier"
						inputmode="tel"
						autocomplete="username"
						dir="ltr"
						placeholder="<?php echo esc_attr( zandi_fa_digits( '09120000000' ) ); ?>"
						value="<?php echo esc_attr( zandi_posted( 'zandi_identifier' ) ); ?>"
						<?php if ( '' !== $zandi_err_identifier ) : ?>
							aria-invalid="true" aria-describedby="zandi-identifier-error"
						<?php endif; ?>
						required
					>
					<?php if ( '' !== $zandi_err_identifier ) : ?>
						<span class="field__error" id="zandi-identifier-error"><?php echo esc_html( $zandi_err_identifier ); ?></span>
					<?php endif; ?>
				</p>

				<p class="field<?php echo '' !== $zandi_err_password ? ' field--invalid' : ''; ?>">
					<label class="field__label" for="zandi-password"><?php echo esc_html( $zandi_fields['password'] ); ?></label>

					<?php /* The button is placed by CSS and only revealed once JS is confirmed. */ ?>
					<span class="field__wrap" dir="ltr">
						<input
							class="field__control field__control--with-toggle"
							type="password"
							id="zandi-password"
							name="zandi_password"
							autocomplete="current-password"
							dir="ltr"
							<?php if ( '' !== $zandi_err_password ) : ?>
								aria-invalid="true" aria-describedby="zandi-password-error"
							<?php endif; ?>
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
				</p>

				<label class="auth__check">
					<input type="checkbox" name="zandi_remember" value="1" checked>
					<span><?php echo esc_html( $zandi_fields['remember'] ); ?></span>
				</label>

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

		<?php /* Signing up is a separate page, so say so on both paths. */ ?>
		<p class="auth__aside">
			<?php echo esc_html( $zandi_copy['alt_prompt'] ); ?>
			<a href="<?php echo esc_url( zandi_register_url() ); ?>"><?php echo esc_html( $zandi_copy['alt_action'] ); ?></a>
		</p>

		<?php /* /contact/, not a messaging app — see zandi_support_url(). */ ?>
		<p class="auth__note<?php echo '' !== $zandi_provider_form ? ' auth__note--standalone' : ''; ?>">
			<a href="<?php echo esc_url( zandi_support_url() ); ?>"><?php echo esc_html( $zandi_copy['forgot'] ); ?></a>
		</p>
	</div>
</section>
