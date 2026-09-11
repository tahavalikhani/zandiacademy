<?php
/**
 * The free episodes: a playlist, a designed player, and the text under each.
 *
 * WHY THE NATIVE PLAYER IS STILL IN THE MARKUP.
 *
 * `<audio controls>` renders a different bar in every browser — Chrome's grey
 * pill, Safari's translucent one, Firefox's third thing — and none of them
 * belong on this page. So theme.js builds a player out of the theme's own
 * parts. But it does that by ENHANCING this element, not replacing it: the
 * `controls` attribute ships in the HTML and the script removes it as its first
 * act. With no JavaScript the browser's own bar is still there and the episode
 * still plays, which is the whole progressive-enhancement rule — the custom UI
 * ships `hidden` and is revealed only by the script that makes it work.
 *
 * `preload="none"` is what makes this cheaper than it looks: zero bytes of
 * audio are fetched until somebody presses play, so three episodes cost nothing
 * to a visitor who only reads. Do not raise it to `metadata` — that fetches a
 * header from every file on every page view, from Iran, for visitors who will
 * never listen.
 *
 * The transcript is a <details>, which is the one disclosure that needs no
 * script at all: closed by default, keyboard-operable, and announced as a
 * disclosure by every screen reader. It renders only for an episode that has
 * text — a «متن پادکست» button that opens an empty panel is worse than none.
 *
 * WITH NOTHING UPLOADED THE SECTION SAYS SO rather than disappearing. It used
 * to return early, on the reasoning that an absent section reads as a page that
 * has other things on it. True of a finished page and false of this one: the
 * podcast page is four sections and two were silently missing, so the owner
 * opened it and could not tell whether the design had failed or the uploads
 * had. What is NOT drawn is a dead player.
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
		<ul class="podcast-playlist">
			<?php foreach ( $zandi_episodes as $zandi_episode ) : ?>
				<?php
				++$zandi_index;

				$zandi_transcript = zandi_podcast_transcript( $zandi_episode['slug'] );
				$zandi_audio_id   = 'pod-audio-' . $zandi_index;
				$zandi_title_id   = 'pod-title-' . $zandi_index;
				?>
				<li class="card podcast-track">
					<div class="podcast-track__row">
						<?php
						/*
						 * The number is decoration over an ordered idea, not an
						 * <ol>: the section shows whichever files exist, so
						 * «۱ ۲ ۳» would be a lie the moment episode two is the
						 * only one uploaded. aria-hidden keeps it out of the
						 * announcement, where the title is already unique.
						 */
						?>
						<span class="podcast-track__num" aria-hidden="true"><?php echo esc_html( zandi_fa_digits( (string) $zandi_index ) ); ?></span>

						<div class="podcast-track__text">
							<h3 class="podcast-track__title" id="<?php echo esc_attr( $zandi_title_id ); ?>"><?php echo zandi_bidi( $zandi_episode['title'] ); ?></h3>

							<?php if ( ! empty( $zandi_episode['summary'] ) ) : ?>
								<p class="podcast-track__summary"><?php echo zandi_bidi( $zandi_episode['summary'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>

					<?php
					/*
					 * `data-player` is what theme.js looks for, and the two
					 * labels it swaps between live here rather than in the
					 * script — the same rule the licence copy button follows,
					 * so no user-facing string ends up somewhere no filter can
					 * reach it.
					 */
					?>
					<div
						class="podcast-player"
						data-player
						data-label-play="<?php echo esc_attr( $zandi_copy['play'] ); ?>"
						data-label-pause="<?php echo esc_attr( $zandi_copy['pause'] ); ?>"
					>
						<audio
							class="podcast-player__audio"
							id="<?php echo esc_attr( $zandi_audio_id ); ?>"
							controls
							preload="none"
							src="<?php echo esc_url( $zandi_episode['url'] ); ?>"
							aria-labelledby="<?php echo esc_attr( $zandi_title_id ); ?>"
						>
							<a href="<?php echo esc_url( $zandi_episode['url'] ); ?>"><?php echo esc_html( $zandi_episode['title'] ); ?></a>
						</audio>

						<?php
						/*
						 * Hidden until theme.js confirms it can drive the audio
						 * element. `hidden` alone would not hold it — `[hidden]`
						 * is a user-agent rule and any author `display` beats
						 * it — so podcast.css carries a scoped `display: none`
						 * to back it up.
						 */
						?>
						<div class="podcast-player__ui" hidden>
							<button
								type="button"
								class="podcast-player__toggle"
								aria-controls="<?php echo esc_attr( $zandi_audio_id ); ?>"
								aria-label="<?php echo esc_attr( $zandi_copy['play'] . ' — ' . $zandi_episode['title'] ); ?>"
							>
								<span class="podcast-player__icon podcast-player__icon--play"><?php zandi_icon( 'play', array( 'fill' => 'currentColor', 'stroke' => 0 ) ); ?></span>
								<span class="podcast-player__icon podcast-player__icon--pause" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><rect x="7" y="5.5" width="3.6" height="13" rx="1.2"/><rect x="13.4" y="5.5" width="3.6" height="13" rx="1.2"/></svg>
								</span>
							</button>

							<div class="podcast-player__bar">
								<?php
								/*
								 * A range input, not a styled <div>. It is the
								 * one scrubber that is keyboard-operable and
								 * announces its position without a line of ARIA
								 * — arrow keys seek, Home and End jump to the
								 * ends, and a screen reader reads a percentage.
								 */
								?>
								<input
									class="podcast-player__seek"
									type="range"
									min="0"
									max="100"
									value="0"
									step="0.1"
									aria-label="<?php echo esc_attr( $zandi_copy['seek'] ); ?>"
								>

								<p class="podcast-player__times">
									<span class="podcast-player__current"><?php echo esc_html( zandi_fa_digits( '۰:۰۰' ) ); ?></span>
									<span class="podcast-player__duration"></span>
								</p>
							</div>
						</div>
					</div>

					<?php if ( '' !== $zandi_transcript ) : ?>
						<details class="podcast-transcript">
							<summary class="podcast-transcript__toggle">
								<span class="podcast-transcript__icon" aria-hidden="true"><?php zandi_icon( 'clipboard' ); ?></span>
								<span><?php echo esc_html( $zandi_copy['transcript_show'] ); ?></span>
								<span class="podcast-transcript__chevron" aria-hidden="true"><?php zandi_icon( 'chevronDown' ); ?></span>
							</summary>

							<div class="podcast-transcript__body">
								<?php zandi_podcast_render_transcript( $zandi_transcript ); ?>
							</div>
						</details>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</section>
