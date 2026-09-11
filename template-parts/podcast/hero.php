<?php
/**
 * The podcast's opening block and its four facts.
 *
 * No `.reveal` anywhere in here, and that is about speed rather than taste.
 * `.reveal` is opacity 0 until deferred theme.js runs, so anything above the
 * fold wearing it is invisible for the whole window in which Largest
 * Contentful Paint is measured — text that was ready the entire time, reported
 * as a slow paint. home/hero.php and course/hero.php are clean for the same
 * reason; keep this one that way too.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy  = zandi_podcast_copy();
$zandi_facts = zandi_podcast_facts();
?>

<div class="page-hero">
	<div class="container">
		<?php
		zandi_breadcrumb(
			array(
				array( 'label' => 'خانه', 'url' => home_url( '/' ) ),
				array( 'label' => $zandi_copy['title'] ),
			)
		);
		?>

		<div class="page-hero__inner">
			<p class="page-hero__eyebrow"><?php echo esc_html( $zandi_copy['eyebrow'] ); ?></p>

			<?php
			/*
			 * zandi_bidi(), not esc_html(): the title carries a French phrase
			 * inside a Persian page, and a bare Latin island in a right-to-left
			 * paragraph is laid out against its neighbours — «Bonjour Monjour»
			 * comes out reversed against the words either side of it.
			 */
			?>
			<h1 class="page-hero__title"><?php echo zandi_bidi( $zandi_copy['title'] ); ?></h1>
			<p class="page-hero__lead"><?php echo zandi_bidi( $zandi_copy['lead'] ); ?></p>
		</div>

		<?php if ( $zandi_facts ) : ?>
			<dl class="podcast-facts" aria-label="<?php echo esc_attr( $zandi_copy['facts_title'] ); ?>">
				<?php foreach ( $zandi_facts as $zandi_fact ) : ?>
					<div class="podcast-facts__item">
						<dt class="podcast-facts__label"><?php echo esc_html( $zandi_fact['label'] ); ?></dt>
						<dd class="podcast-facts__value">
							<?php echo zandi_bidi( $zandi_fact['value'] ); ?>
							<?php if ( ! empty( $zandi_fact['note'] ) ) : ?>
								<span class="podcast-facts__note"><?php echo esc_html( $zandi_fact['note'] ); ?></span>
							<?php endif; ?>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>
</div>
