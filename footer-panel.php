<?php
/**
 * Footer for the account pages and the student panel.
 *
 * Deliberately minimal — a signed-in student does not need the marketing
 * footer, and the account pages should not offer a dozen ways to wander off
 * mid-signup. Loaded via get_footer( 'panel' ).
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_site    = zandi_site();
$zandi_contact = zandi_contact();

$zandi_year = function_exists( 'wp_date' ) ? wp_date( 'Y' ) : gmdate( 'Y' );

if ( 'fa_IR' === get_locale() && class_exists( 'IntlDateFormatter' ) ) {
	$zandi_year = ( new IntlDateFormatter( 'fa_IR@calendar=persian', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, IntlDateFormatter::TRADITIONAL, 'yyyy' ) )
		->format( new DateTime( 'now', wp_timezone() ) );
} else {
	$zandi_year = zandi_fa_digits( $zandi_year );
}
?>

<footer class="p-footer">
	<div class="c-container p-footer__inner">
		<p>© <?php echo esc_html( $zandi_year ); ?> <?php echo esc_html( $zandi_site['name'] ); ?></p>

		<p>
			<a href="<?php echo esc_url( $zandi_contact['telegram'] ); ?>" rel="noopener">پشتیبانی در تلگرام</a>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
