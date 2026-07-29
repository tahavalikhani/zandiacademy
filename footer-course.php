<?php
/**
 * Footer for course landing pages.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_site = zandi_site();

$zandi_year = function_exists( 'wp_date' ) ? wp_date( 'Y' ) : gmdate( 'Y' );

if ( 'fa_IR' === get_locale() && class_exists( 'IntlDateFormatter' ) ) {
	$zandi_year = ( new IntlDateFormatter( 'fa_IR@calendar=persian', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, IntlDateFormatter::TRADITIONAL, 'yyyy' ) )
		->format( new DateTime( 'now', wp_timezone() ) );
} else {
	$zandi_year = zandi_fa_digits( $zandi_year );
}

/* TODO: real destinations needed for every link below, plus social handles. */
$zandi_footer_cols = array(
	array(
		'title' => 'آکادمی',
		'links' => array( 'دوره‌ها', 'وبلاگ', 'پادکست', 'درباره من' ),
	),
	array(
		'title' => 'ارتباط',
		'links' => array( 'تماس', 'قوانین', 'اینستاگرام', 'تلگرام', 'یوتیوب' ),
	),
);
?>

<footer class="c-footer">
	<div class="c-container">
		<div class="c-footer__grid">
			<div>
				<span class="c-logo">
					<span><?php echo esc_html( $zandi_site['name'] ); ?></span>
					<span class="c-logo__sub">FRANÇAIS</span>
				</span>
				<p class="c-footer__about">
					آکادمی زبان فرانسه زندی، فرانسه رو همون‌طور که واقعاً حرف زده می‌شه به فارسی‌زبان‌ها یاد می‌ده. مستقیم از پاریس.
				</p>
			</div>

			<?php foreach ( $zandi_footer_cols as $zandi_col ) : ?>
				<nav aria-label="<?php echo esc_attr( $zandi_col['title'] ); ?>">
					<h2 class="c-footer__title"><?php echo esc_html( $zandi_col['title'] ); ?></h2>
					<ul class="c-footer__list">
						<?php foreach ( $zandi_col['links'] as $zandi_link ) : ?>
							<li><a href="#"><?php echo esc_html( $zandi_link ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endforeach; ?>
		</div>

		<div class="c-footer__bottom">
			<p>© <?php echo esc_html( $zandi_year ); ?> <?php echo esc_html( $zandi_site['name'] ); ?> — تمامی حقوق محفوظ است.</p>
			<p>تدریس از پاریس</p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
