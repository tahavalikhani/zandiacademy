<?php
/**
 * «پادکست من».
 *
 * Four states, and the one that used to render nothing is now the one that
 * matters most. A student with no subscription is the person most worth
 * talking to, and an absent section told them nothing at all — so «none» is an
 * empty state with a way to the podcast page, built from the same .empty-note
 * the courses section uses when a student owns nothing.
 *
 * The second state is the reason this card exists at all: a student can have
 * paid and still be locked out, because a bot cannot look anybody up by phone
 * number and has no way of knowing which Telegram account is theirs until they
 * say so. «اتصال به تلگرام» is that step. Without it a paid subscription looks
 * broken, and the complaint arrives as «پولم رفت».
 *
 * THE CONNECT BUTTON IS OFFERED EVEN TO SOMEBODY WHO HAS ALREADY PRESSED IT,
 * and that is deliberate rather than sloppy. The student introduces themselves
 * to the BOT, so it is the bot that ends up holding the pair — and the bot
 * cannot tell us, because the site refuses requests from datacentre addresses
 * and the bot lives in one. Rather than guess, the card keeps offering the
 * button and says pressing it again is harmless, which it is: binding is
 * idempotent. If the bot ever gains a way to report back, zandi_podcast_
 * telegram_id() starts answering and the confirmed state below appears on its
 * own with no further change here.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy    = zandi_podcast_copy();
$zandi_user_id = (int) $args['user']->ID;
$zandi_state   = zandi_podcast_state( $zandi_user_id );
?>

<section class="panel-section" id="my-podcast" aria-labelledby="my-podcast-title">
	<h2 class="panel-section__title" id="my-podcast-title"><?php echo esc_html( $zandi_copy['panel_title'] ); ?></h2>

	<?php if ( 'none' === $zandi_state ) : ?>

		<div class="empty-note">
			<p class="empty-note__title"><?php echo esc_html( $zandi_copy['panel_none'] ); ?></p>
			<p class="empty-note__body"><?php echo zandi_bidi( $zandi_copy['panel_none_body'] ); ?></p>

			<p class="panel-empty__action">
				<?php
				zandi_button(
					array(
						'label' => $zandi_copy['panel_none_cta'],
						'url'   => zandi_podcast_url(),
						'size'  => 'md',
					)
				);
				?>
			</p>
		</div>

	<?php else : ?>

		<?php
		$zandi_expires   = zandi_podcast_expires( $zandi_user_id );
		$zandi_left      = zandi_podcast_days_left( $zandi_user_id );
		$zandi_connected = (bool) zandi_podcast_telegram_id( $zandi_user_id );
		$zandi_connect   = zandi_podcast_connect_url( $zandi_user_id );
		?>

		<div class="card panel-podcast panel-podcast--<?php echo esc_attr( $zandi_state ); ?>">

			<?php if ( 'active' === $zandi_state ) : ?>

				<p class="panel-podcast__status"><?php echo esc_html( $zandi_copy['panel_active'] ); ?></p>

				<p class="panel-podcast__until">
					<span class="panel-podcast__until-label"><?php echo esc_html( $zandi_copy['panel_until'] ); ?></span>
					<?php
					/*
					 * Jalali, through the same helper the placement report uses,
					 * so one student never sees two calendars on one site.
					 */
					?>
					<time datetime="<?php echo esc_attr( gmdate( 'Y-m-d', $zandi_expires ) ); ?>"><?php echo esc_html( zandi_placement_date( $zandi_expires ) ); ?></time>
				</p>

				<p class="panel-podcast__left">
					<?php echo esc_html( zandi_fa_digits( (string) $zandi_left ) . ' ' . $zandi_copy['panel_left'] ); ?>
				</p>

			<?php elseif ( 'grace' === $zandi_state ) : ?>

				<p class="panel-podcast__status"><?php echo esc_html( $zandi_copy['panel_grace'] ); ?></p>

			<?php else : ?>

				<p class="panel-podcast__status"><?php echo esc_html( $zandi_copy['panel_expired'] ); ?></p>

			<?php endif; ?>

			<?php
			/*
			 * The connect block comes before the renew button on purpose.
			 * Somebody whose access is live but unconnected is the one person
			 * on this page with something they must do, and burying it under a
			 * sales button would be exactly backwards.
			 */
			if ( $zandi_connected ) :
				?>
				<p class="panel-podcast__connected"><?php echo esc_html( $zandi_copy['panel_connected'] ); ?></p>
			<?php elseif ( $zandi_connect ) : ?>
				<div class="panel-podcast__connect">
					<?php
					zandi_button(
						array(
							'label' => $zandi_copy['panel_connect'],
							'url'   => $zandi_connect,
							'size'  => 'md',
						)
					);
					?>
					<p class="panel-podcast__hint"><?php echo esc_html( $zandi_copy['panel_connect_note'] ); ?></p>
				</div>
			<?php endif; ?>

			<?php
			if ( 'active' !== $zandi_state && zandi_podcast_purchasable() ) {
				zandi_button(
					array(
						'label' => $zandi_copy['panel_renew'],
						'url'   => zandi_podcast_url(),
						'size'  => 'md',
						'class' => 'panel-podcast__renew',
					)
				);
			}
			?>
		</div>

	<?php endif; ?>
</section>
