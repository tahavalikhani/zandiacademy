<?php
/**
 * The free episodes.
 *
 * `preload="none"` is what makes this cheaper than it looks: zero bytes of
 * audio are fetched until somebody presses play, so three episodes on the page
 * cost nothing to a visitor who only reads. Do not raise it to `metadata` —
 * that fetches a header from every file on every page view, from Iran, for
 * visitors who will never listen.
 *
 * Renders nothing at all when no file has been uploaded yet, rather than
 * drawing three dead players. A play button that does nothing reads as a broken
 * site; an absent section reads as a page that simply has other things on it.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy     = zandi_podcast_copy();
$zandi_episodes = zandi_podcast_available_episodes();

if ( ! $zandi_episodes ) {
	return;
}
?>

<section class="section podcast-episodes" id="episodes" aria-labelledby="podcast-episodes-title">
	<div class="container">
		<?php
		zandi_section_heading(
			array(
				'title'       => $zandi_copy['episodes_title'],
				'description' => $zandi_copy['episodes_lead'],
				'id'          => 'podcast-episodes',
			)
		);
		?>

		<ul class="podcast-episodes__list">
			<?php foreach ( $zandi_episodes as $zandi_episode ) : ?>
				<li class="card podcast-episode">
					<h3 class="podcast-episode__title"><?php echo zandi_bidi( $zandi_episode['title'] ); ?></h3>

					<?php if ( ! empty( $zandi_episode['summary'] ) ) : ?>
						<p class="podcast-episode__summary"><?php echo zandi_bidi( $zandi_episode['summary'] ); ?></p>
					<?php endif; ?>

					<?php
					/*
					 * `controls` and nothing else. A custom player would be a
					 * second set of buttons to keep accessible, and the browser's
					 * own is already keyboard-operable and translated.
					 */
					?>
					<audio
						class="podcast-episode__player"
						controls
						preload="none"
						src="<?php echo esc_url( $zandi_episode['url'] ); ?>"
					>
						<a href="<?php echo esc_url( $zandi_episode['url'] ); ?>"><?php echo esc_html( $zandi_episode['title'] ); ?></a>
					</audio>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
