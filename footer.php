<?php
/**
 * Site footer.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_site    = zandi_site();
$zandi_contact = zandi_contact();

/*
 * Current Jalali year, so the copyright line never goes stale. wp_date() honours
 * the site's timezone and locale; the fallback covers installs without the
 * intl extension.
 */
$zandi_year = function_exists( 'wp_date' ) ? wp_date( 'Y' ) : gmdate( 'Y' );

if ( 'fa_IR' === get_locale() && class_exists( 'IntlDateFormatter' ) ) {
	$zandi_year = ( new IntlDateFormatter( 'fa_IR@calendar=persian', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, IntlDateFormatter::TRADITIONAL, 'yyyy' ) )
		->format( new DateTime( 'now', wp_timezone() ) );
} else {
	$zandi_year = zandi_fa_digits( $zandi_year );
}
?>

<footer class="site-footer" id="contact">
	<div class="container site-footer__inner">
		<div class="site-footer__top">
			<div class="site-footer__brand">
				<?php zandi_logo( true ); ?>

				<p class="site-footer__about"><?php echo esc_html( $zandi_site['description'] ); ?></p>

				<ul class="socials">
					<?php foreach ( zandi_socials() as $zandi_social ) : ?>
						<li>
							<a
								href="<?php echo esc_url( $zandi_social['url'] ); ?>"
								target="_blank"
								rel="noopener noreferrer"
								aria-label="<?php echo esc_attr( $zandi_social['label'] ); ?>"
							>
								<?php zandi_icon( $zandi_social['icon'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="site-footer__columns">
				<?php if ( has_nav_menu( 'footer' ) ) : ?>
					<nav class="footer-col" aria-label="منوی فوتر">
						<h2 class="footer-col__title">پیوندها</h2>
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'footer',
								'container'      => '',
								'menu_class'     => '',
								'depth'          => 1,
							)
						);
						?>
					</nav>
				<?php else : ?>
					<?php foreach ( zandi_footer_columns() as $zandi_column ) : ?>
						<nav class="footer-col" aria-label="<?php echo esc_attr( $zandi_column['title'] ); ?>">
							<h2 class="footer-col__title"><?php echo esc_html( $zandi_column['title'] ); ?></h2>
							<ul>
								<?php foreach ( $zandi_column['links'] as $zandi_link ) : ?>
									<li>
										<a href="<?php echo esc_url( zandi_resolve_anchor( $zandi_link['url'] ) ); ?>">
											<?php echo esc_html( $zandi_link['label'] ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</nav>
					<?php endforeach; ?>
				<?php endif; ?>

				<div class="footer-col">
					<h2 class="footer-col__title">تماس با ما</h2>
					<ul class="contact-list">
						<?php
						/*
						 * The page first, the channel second. Anyone following
						 * this link lands somewhere the academy controls, so the
						 * channel underneath can change without the footer
						 * needing an edit — see zandi_support_url().
						 */
						?>
						<li>
							<a href="<?php echo esc_url( zandi_support_url() ); ?>">
								<?php zandi_icon( 'chat' ); ?>
								<span>تماس با من</span>
							</a>
						</li>

						<?php if ( $zandi_contact['telegram'] ) : ?>
						<li>
							<a href="<?php echo esc_url( $zandi_contact['telegram'] ); ?>" target="_blank" rel="noopener noreferrer">
								<?php zandi_icon( 'telegram' ); ?>
								<span dir="ltr"><?php echo esc_html( $zandi_contact['telegram_name'] ); ?></span>
							</a>
						</li>
						<?php endif; ?>
						<?php if ( $zandi_contact['phone'] ) : ?>
						<li>
							<a href="<?php echo esc_url( $zandi_contact['phone_href'] ); ?>">
								<?php zandi_icon( 'phone' ); ?>
								<span dir="ltr"><?php echo esc_html( $zandi_contact['phone'] ); ?></span>
							</a>
						</li>
						<?php endif; ?>
						<?php if ( $zandi_contact['email'] ) : ?>
						<li>
							<a href="mailto:<?php echo esc_attr( $zandi_contact['email'] ); ?>">
								<?php zandi_icon( 'mail' ); ?>
								<span dir="ltr"><?php echo esc_html( $zandi_contact['email'] ); ?></span>
							</a>
						</li>
						<?php endif; ?>
						<?php if ( $zandi_contact['address'] ) : ?>
						<li>
							<?php zandi_icon( 'pin' ); ?>
							<span><?php echo esc_html( $zandi_contact['address'] ); ?></span>
						</li>
						<?php endif; ?>
						<li>
							<?php zandi_icon( 'clock' ); ?>
							<span><?php echo esc_html( $zandi_contact['hours'] ); ?></span>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<?php
		/*
		 * Trust badges — the ZarinPal seal now, نماد اعتماد when it arrives.
		 * Both come from zandi_trust_badges(), so this slot never needs editing
		 * again. The markup is a third party's and is printed unescaped for the
		 * same reason the Digits form is: escaping a badge is the same as not
		 * having one.
		 */
		$zandi_badges = zandi_trust_badges();

		if ( $zandi_badges ) :
			?>
			<div class="site-footer__trust">
				<?php foreach ( $zandi_badges as $zandi_slug => $zandi_badge ) : ?>
					<div class="site-footer__badge site-footer__badge--<?php echo esc_attr( $zandi_slug ); ?>">
						<?php echo $zandi_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Provider markup; see zandi_trust_badges(). ?>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
		endif;
		?>

		<div class="site-footer__bottom">
			<p>
				<?php
				printf(
					'© %1$s %2$s — تمامی حقوق محفوظ است.',
					esc_html( $zandi_year ),
					esc_html( $zandi_site['name'] )
				);
				?>
			</p>

			<p class="site-footer__tagline">
				<?php zandi_icon( 'globe' ); ?>
				<?php echo esc_html( $zandi_site['tagline'] ); ?>
			</p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
