<?php
/**
 * «حساب من» — what the academy holds about this student, and the way out.
 *
 * Read-only for now. Editing would need either a verified number (OTP) or an
 * email round trip, and neither exists yet; sending changes through Telegram is
 * honest and is where support already happens.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy  = zandi_panel_copy();
$zandi_user  = $args['user'];
$zandi_phone = zandi_user_phone( $zandi_user->ID );

$zandi_rows = array(
	array(
		'label' => $zandi_copy['profile_name'],
		'value' => $zandi_user->display_name,
		'ltr'   => false,
	),
	array(
		'label' => $zandi_copy['profile_phone'],
		'value' => $zandi_phone ? zandi_format_phone( $zandi_phone ) : '',
		'ltr'   => true,
	),
	array(
		'label' => $zandi_copy['profile_email'],
		'value' => $zandi_user->user_email,
		'ltr'   => true,
	),
);
?>

<section class="c-section c-section--tight" id="my-account" aria-labelledby="my-account-title">
	<h2 class="c-section__title" id="my-account-title"><?php echo esc_html( $zandi_copy['profile_title'] ); ?></h2>

	<div class="c-card p-card">
		<dl class="p-details">
			<?php foreach ( $zandi_rows as $zandi_row ) : ?>
				<div class="p-details__row">
					<dt><?php echo esc_html( $zandi_row['label'] ); ?></dt>
					<dd<?php echo $zandi_row['value'] && $zandi_row['ltr'] ? ' dir="ltr"' : ''; ?>>
						<?php
						echo $zandi_row['value']
							? esc_html( $zandi_row['value'] )
							: '<span class="p-details__empty">' . esc_html( $zandi_copy['profile_empty'] ) . '</span>';
						?>
					</dd>
				</div>
			<?php endforeach; ?>
		</dl>

		<p class="p-card__note"><?php echo esc_html( $zandi_copy['profile_note'] ); ?></p>

		<a class="c-btn c-btn--ghost c-btn--sm" href="<?php echo esc_url( zandi_logout_url() ); ?>">
			<?php echo esc_html( $zandi_copy['logout'] ); ?>
		</a>
	</div>
</section>
