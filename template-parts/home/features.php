<?php
/**
 * Why choose us.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="section section--mist" id="about" aria-labelledby="about-title">
	<div class="container">
		<?php
		zandi_section_heading(
			array(
				'id'          => 'about',
				'eyebrow'     => 'چرا آکادمی زندی',
				'title'       => 'یک آکادمی، یک زبان، تمام تمرکز',
				'description' => 'ما دوره‌های چندزبانه ارائه نمی‌دهیم. همه انرژی، اساتید و منابع آکادمی صرف یک چیز می‌شود: اینکه شما فرانسه را درست یاد بگیرید.',
			)
		);
		?>

		<div class="features__grid reveal-group">
			<?php foreach ( zandi_features() as $feature ) : ?>
				<div class="reveal reveal--scale">
					<article class="card card--interactive feature-card">
						<span class="icon-tile"><?php zandi_icon( $feature['icon'] ); ?></span>

						<h3 class="feature-card__title"><?php echo esc_html( $feature['title'] ); ?></h3>

						<p class="feature-card__text"><?php echo esc_html( $feature['description'] ); ?></p>

						<span class="feature-card__rule" aria-hidden="true"></span>
					</article>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
