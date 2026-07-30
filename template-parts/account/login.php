<?php
/**
 * The sign-in form.
 *
 * Posts to itself so it can re-render with the typed number still in place and
 * the error beside it — see the note at the top of inc/auth.php. Works with
 * JavaScript off; nothing here is enhanced.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy      = zandi_login_copy();
$zandi_fields    = zandi_auth_fields();
$zandi_contact   = zandi_contact();
$zandi_shortcode = zandi_login_shortcode();
$zandi_redirect  = zandi_auth_redirect_target();
?>

<section class="p-auth" aria-labelledby="auth-title">
	<div class="p-auth__card">
		<p class="c-eyebrow"><?php echo esc_html( $zandi_copy['eyebrow'] ); ?></p>
		<h1 class="p-auth__title" id="auth-title"><?php echo esc_html( $zandi_copy['title'] ); ?></h1>
		<p class="p-auth__lead"><?php echo esc_html( $zandi_copy['description'] ); ?></p>

		<?php get_template_part( 'template-parts/account/errors' ); ?>

		<?php if ( $zandi_shortcode ) : ?>
			<?php
			/*
			 * An OTP plugin has been dropped in. The theme keeps the card and the
			 * heading and hands the form itself over — see zandi_login_shortcode().
			 */
			echo do_shortcode( $zandi_shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output is the plugin's own.
			?>
		<?php else : ?>
			<form class="p-form" method="post" action="<?php echo esc_url( zandi_login_url() ); ?>" novalidate>
				<?php wp_nonce_field( 'zandi_login', 'zandi_login_nonce' ); ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $zandi_redirect ); ?>">

				<p class="field">
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
						required
					>
				</p>

				<p class="field">
					<label class="field__label" for="zandi-password"><?php echo esc_html( $zandi_fields['password'] ); ?></label>
					<input
						class="field__control"
						type="password"
						id="zandi-password"
						name="zandi_password"
						autocomplete="current-password"
						dir="ltr"
						required
					>
				</p>

				<label class="p-check">
					<input type="checkbox" name="zandi_remember" value="1" checked>
					<span><?php echo esc_html( $zandi_fields['remember'] ); ?></span>
				</label>

				<button type="submit" class="c-btn c-btn--primary c-btn--block"><?php echo esc_html( $zandi_copy['submit'] ); ?></button>
			</form>
		<?php endif; ?>

		<p class="p-auth__aside">
			<?php echo esc_html( $zandi_copy['alt_prompt'] ); ?>
			<a href="<?php echo esc_url( zandi_register_url() ); ?>"><?php echo esc_html( $zandi_copy['alt_action'] ); ?></a>
		</p>

		<p class="p-auth__note">
			<a href="<?php echo esc_url( $zandi_contact['telegram'] ); ?>" rel="noopener"><?php echo esc_html( $zandi_copy['forgot'] ); ?></a>
		</p>
	</div>
</section>
