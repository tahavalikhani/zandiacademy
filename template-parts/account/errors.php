<?php
/**
 * The error list above an auth form.
 *
 * Rendered as a live region so a screen reader announces the failure without
 * the student having to hunt for it, and marked up as a list because there can
 * be several at once.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_errors = zandi_auth_errors();

if ( ! $zandi_errors->has_errors() ) {
	return;
}
?>

<div class="auth__errors" role="alert">
	<ul>
		<?php foreach ( $zandi_errors->get_error_messages() as $zandi_message ) : ?>
			<li><?php echo esc_html( $zandi_message ); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
