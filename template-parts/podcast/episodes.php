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
 * WITH NOTHING UPLOADED IT SAYS SO, rather than disappearing.
 *
 * It used to return early, on the reasoning that an absent section reads as a
 * page that simply has other things on it. That is true of a finished page and
 * false of this one: /podcast/ is four sections and two of them were silently
 * missing, so the owner opened her own page and could not tell whether the
 * design had failed or the uploads had. A dated «به‌زودی» is honest, keeps the
 * page's shape, and disappears the moment a file exists.
 *
 * What is NOT drawn is a dead player. A play button that does nothing reads as
 * a broken site, which is worse than either.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy     = zandi_podcast_copy();
$zandi_episodes = zandi_podcast_available_episodes();
$zandi_index    = 0;
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

		<?php if ( ! $zandi_episodes ) : ?>
			<?php
			/*
			 * if/else rather than an early `return`. A template part is
			 * `require`d, so returning here would stop the include and the
			 * closing </div></section> below would never print — unclosed tags
			 * on the one state the page is actually in today.
			 */
			?>
			<div class="empty-note podcast-empty">
				<p class="empty-note__title"><?php echo esc_html( $zandi_copy['episodes_soon'] ); ?></p>
				<p class="empty-note__body"><?php echo esc_html( $zandi_copy['episodes_soon_body'] ); ?></p>
			</div>
		<?php else : ?>
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
		<?php endif; ?>
	</div>
</section>
