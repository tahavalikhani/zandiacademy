<?php
/**
 * The gift page's opening block: the offer, or — signed in — where they stand.
 *
 * FOUR STATES, ONE LAYOUT. Signed out it is the offer and the button. Signed in
 * with the gift it is the date it runs to and the one thing left to do, which
 * is connecting Telegram — without that the bot cannot let them into the group,
 * and a student who does not know it thinks the gift was a lie. Signed in
 * without the gift, or with it spent, it says so plainly and points at the
 * plans rather than repeating an offer they cannot take.
 *
 * THE ILLUSTRATION IS FOR LAPTOPS ONLY. The owner's call on 27 September 2026:
 * on a phone the cover, the ticket and the two bubbles crowd the screen, so
 * free-podcast.css hides the whole block under 640px. It is decorative and
 * aria-hidden at every width, so nothing is lost to a screen reader either.
 *
 * dir="ltr" SITS ON THE INNER BOX, NEVER ON A POSITIONED ONE. inset-inline-*
 * resolves against the element's own direction, so a wrapper positioned inside
 * this right-to-left block would swap edges the moment it became ltr. Each
 * floating piece is therefore a positioned wrapper holding an ltr card.
 *
 * No `.reveal` anywhere: this is above the fold, and `.reveal` is invisible
 * until deferred theme.js runs — the window Largest Contentful Paint is
 * measured in.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy    = zandi_free_podcast_copy();
$zandi_state   = isset( $args['state'] ) ? (string) $args['state'] : 'guest';
$zandi_user_id = isset( $args['user_id'] ) ? (int) $args['user_id'] : 0;
$zandi_gift    = $zandi_user_id ? zandi_podcast_gift_grant( $zandi_user_id ) : null;

// The days on a gift already given are the ones it was given with, not today's offer.
$zandi_days_n  = $zandi_gift ? (int) $zandi_gift['days'] : zandi_free_podcast_days();
$zandi_days    = zandi_fa_digits( (string) $zandi_days_n );
$zandi_forward = zandi_arrow_forward();
?>

<section class="fp-hero" aria-labelledby="fp-title">
	<div class="container fp-hero__grid">
		<div class="fp-hero__text">

			<?php if ( 'guest' === $zandi_state ) : ?>

				<?php zandi_badge( $zandi_copy['eyebrow'], 'gift', array( 'icon' => 'gift' ) ); ?>

				<h1 class="fp-title" id="fp-title">
					<span class="fp-title__line"><?php echo esc_html( sprintf( $zandi_copy['headline'], $zandi_days ) ); ?></span>
					<span class="fp-mark"><?php echo esc_html( $zandi_copy['headline_mark'] ); ?></span>
				</h1>

				<p class="fp-lead"><?php echo zandi_bidi( $zandi_copy['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- zandi_bidi() escapes. ?></p>

				<?php
				zandi_button(
					array(
						'label'   => sprintf( $zandi_copy['cta'], $zandi_days ),
						'url'     => zandi_free_podcast_signup_url(),
						'variant' => 'gift',
						'size'    => 'lg',
						'icon'    => $zandi_forward,
						'class'   => 'fp-cta',
					)
				);
				?>

				<p class="fp-note"><?php echo esc_html( $zandi_copy['cta_note'] ); ?></p>

			<?php elseif ( 'gifted' === $zandi_state ) : ?>

				<?php
				$zandi_expires   = zandi_podcast_expires( $zandi_user_id );
				$zandi_connected = (bool) zandi_podcast_telegram_id( $zandi_user_id );
				$zandi_connect   = zandi_podcast_connect_link( $zandi_user_id );
				$zandi_podcast   = zandi_podcast_copy();
				?>

				<?php zandi_badge( $zandi_copy['gifted_badge'], 'success', array( 'icon' => 'check' ) ); ?>

				<h1 class="fp-title" id="fp-title">
					<span class="fp-title__line"><?php echo esc_html( sprintf( $zandi_copy['gifted_title'], $zandi_days ) ); ?></span>
					<span class="fp-mark"><?php echo esc_html( $zandi_copy['gifted_mark'] ); ?></span>
				</h1>

				<?php if ( $zandi_expires ) : ?>
					<p class="fp-lead fp-until">
						<?php echo esc_html( $zandi_copy['gifted_until'] ); ?>
						<time datetime="<?php echo esc_attr( gmdate( 'Y-m-d', $zandi_expires ) ); ?>"><?php echo esc_html( zandi_placement_date( $zandi_expires ) ); ?></time>
					</p>
				<?php endif; ?>

				<?php if ( $zandi_connected ) : ?>

					<div class="fp-step">
						<h2 class="fp-step__title"><?php echo esc_html( $zandi_podcast['panel_connected'] ); ?></h2>
						<p class="fp-step__body"><?php echo esc_html( $zandi_copy['connected_body'] ); ?></p>
						<?php
						zandi_button(
							array(
								'label' => $zandi_copy['panel_link'],
								'url'   => zandi_panel_url() . '#my-podcast',
								'size'  => 'md',
								'icon'  => $zandi_forward,
							)
						);
						?>
					</div>

				<?php elseif ( $zandi_connect ) : ?>

					<div class="fp-step">
						<h2 class="fp-step__title"><?php echo esc_html( $zandi_copy['connect_title'] ); ?></h2>
						<p class="fp-step__body"><?php echo esc_html( $zandi_copy['connect_body'] ); ?></p>
						<div class="fp-step__actions">
							<?php
							zandi_button(
								array(
									'label'       => $zandi_podcast['panel_connect'],
									'url'         => $zandi_connect,
									'size'        => 'lg',
									'icon_before' => 'telegram',
								)
							);
							?>
							<a class="fp-link" href="<?php echo esc_url( zandi_panel_url() . '#my-podcast' ); ?>"><?php echo esc_html( $zandi_copy['panel_link'] ); ?></a>
						</div>
					</div>

				<?php else : ?>

					<?php
					/*
					 * The bridge is not configured on this install, so there is no
					 * link to hand out. The panel says what the student can do, and
					 * the gift is recorded either way.
					 */
					zandi_button(
						array(
							'label' => $zandi_copy['panel_link'],
							'url'   => zandi_panel_url() . '#my-podcast',
							'size'  => 'lg',
							'icon'  => $zandi_forward,
						)
					);
					?>

				<?php endif; ?>

			<?php else : ?>

				<?php
				$zandi_over = 'gift_over' === $zandi_state;
				zandi_badge( $zandi_copy['eyebrow'], 'gift', array( 'icon' => 'gift' ) );
				?>

				<h1 class="fp-title fp-title--quiet" id="fp-title"><?php echo esc_html( $zandi_over ? $zandi_copy['over_title'] : $zandi_copy['other_title'] ); ?></h1>

				<p class="fp-lead"><?php echo esc_html( $zandi_over ? $zandi_copy['over_body'] : $zandi_copy['other_body'] ); ?></p>

				<div class="fp-step__actions">
					<?php
					zandi_button(
						array(
							'label' => $zandi_copy['plans_cta'],
							'url'   => zandi_podcast_url(),
							'size'  => 'lg',
							'icon'  => $zandi_forward,
						)
					);
					?>
					<a class="fp-link" href="<?php echo esc_url( zandi_panel_url() ); ?>"><?php echo esc_html( $zandi_copy['panel_link'] ); ?></a>
				</div>

			<?php endif; ?>

		</div>

		<div class="fp-visual" aria-hidden="true">
			<span class="fp-dot fp-dot--lime"></span>
			<span class="fp-dot fp-dot--lilac"></span>
			<span class="fp-dot fp-dot--ring"></span>

			<div class="fp-cover-wrap fp-float fp-float--slow">
				<div class="fp-cover" dir="ltr">
					<div class="fp-cover__top">
						<?php zandi_icon( 'mic', array( 'class' => 'fp-cover__mic', 'stroke' => 1.75 ) ); ?>
						<div class="fp-eq">
							<span></span><span></span><span></span><span></span><span></span>
						</div>
					</div>
					<div>
						<div class="fp-cover__kind"><?php echo esc_html( $zandi_copy['cover_kind'] ); ?></div>
						<div class="fp-cover__name">Bonjour<br>Monjour</div>
					</div>
				</div>
			</div>

			<div class="fp-ticket-wrap fp-float">
				<div class="fp-ticket" dir="ltr">
					<svg class="fp-ticket__rings" viewBox="0 0 380 190" preserveAspectRatio="xMidYMid slice">
						<g fill="none" stroke="#fff" stroke-opacity="0.1">
							<?php foreach ( array( 40, 72, 106, 142, 180 ) as $zandi_r ) : ?>
								<circle cx="244" cy="184" r="<?php echo esc_attr( $zandi_r ); ?>" />
							<?php endforeach; ?>
						</g>
					</svg>
					<div class="fp-ticket__main">
						<div class="fp-ticket__kind"><?php echo esc_html( $zandi_copy['ticket_kind'] ); ?></div>
						<div>
							<div class="fp-ticket__for"><?php echo esc_html( $zandi_copy['ticket_for'] ); ?></div>
							<div class="fp-ticket__days"><?php echo esc_html( sprintf( $zandi_copy['ticket_days'], $zandi_days_n ) ); ?><i class="fp-ticket__dot"></i></div>
						</div>
						<div class="fp-ticket__line" dir="rtl"><?php echo esc_html( sprintf( $zandi_copy['ticket_line'], $zandi_days ) ); ?></div>
					</div>
					<div class="fp-ticket__stub">
						<?php zandi_icon( 'headphones', array( 'class' => 'fp-ticket__icon', 'stroke' => 1.75 ) ); ?>
						<div class="fp-ticket__no">N°</div>
						<div class="fp-ticket__num">007</div>
					</div>
					<i class="fp-ticket__notch fp-ticket__notch--top"></i>
					<i class="fp-ticket__notch fp-ticket__notch--bottom"></i>
				</div>
			</div>

			<div class="fp-bubble-wrap fp-bubble-wrap--hello fp-float">
				<div class="fp-bubble" dir="ltr" lang="fr"><?php echo esc_html( $zandi_copy['bubble_hello'] ); ?></div>
			</div>
			<div class="fp-bubble-wrap fp-bubble-wrap--howru fp-float fp-float--slow">
				<div class="fp-bubble fp-bubble--lime" dir="ltr" lang="fr"><?php echo esc_html( $zandi_copy['bubble_howru'] ); ?></div>
			</div>
		</div>
	</div>
</section>
