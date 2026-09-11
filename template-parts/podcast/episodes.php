<?php
/**
 * The free episodes, as a playlist rather than a row of cards.
 *
 * Three cards in a grid gave each episode a whole box to hold a title and a
 * player, which on a laptop is three near-empty rectangles and on a phone is
 * three screens of scrolling. A playlist is what a podcast looks like
 * everywhere else, it puts the episodes in an order, and it costs a third of
 * the height.
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

$zandi_index = 0;
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

		<ul class="card podcast-playlist">
			<?php foreach ( $zandi_episodes as $zandi_episode ) : ?>
				<?php ++$zandi_index; ?>
				<li class="podcast-track">
					<?php
					/*
					 * The number is decoration over an ordered idea, not an
					 * <ol>: the section shows whichever files exist, so «۱ ۲ ۳»
					 * would be a lie the moment episode two is the only one
					 * uploaded. aria-hidden keeps it out of the announcement,
					 * where the title is already unique.
					 */
					?>
					<span class="podcast-track__num" aria-hidden="true"><?php echo esc_html( zandi_fa_digits( (string) $zandi_index ) ); ?></span>

					<div class="podcast-track__text">
						<h3 class="podcast-track__title"><?php echo zandi_bidi( $zandi_episode['title'] ); ?></h3>

						<?php if ( ! empty( $zandi_episode['summary'] ) ) : ?>
							<p class="podcast-track__summary"><?php echo zandi_bidi( $zandi_episode['summary'] ); ?></p>
						<?php endif; ?>
					</div>

					<?php
					/*
					 * `controls` and nothing else. A custom player would be a
					 * second set of buttons to keep accessible, and the
					 * browser's own is already keyboard-operable and
					 * translated.
					 */
					?>
					<audio
						class="podcast-track__player"
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
