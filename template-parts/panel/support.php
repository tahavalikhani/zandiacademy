<?php
/**
 * Support.
 *
 * The one page on the site where a student is given the IDs directly. Everywhere
 * else «بپرس» goes to /contact/ through zandi_support_url(), and that stays
 * true — but a student who is already signed in and stuck mid-lesson should not
 * have to make a second hop to find out who to message.
 *
 * TWO DESTINATIONS, AND THEY ARE NOT THE SAME. Support answers about courses,
 * enrolment and levels; Shima's own account is for the actual French. Both come
 * from zandi_contact() — nothing here knows a channel by name, so moving either
 * one is still a single edit to that array.
 *
 * The public broadcast channel is deliberately NOT here. It is content, not a
 * reply, and offering it to someone who needs help is what this section used to
 * do wrong.
 *
 * No .reveal: panel sections carry none. Scroll-reveal starts at opacity 0 and
 * is undone by JavaScript, and a support card that needs a script to appear
 * fails shut at exactly the moment someone is looking for it.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy    = zandi_panel_copy();
$zandi_contact = zandi_contact();

/*
 * Built as data so the markup below is written once. `icon` is a key in the
 * registry in inc/icons.php — no new asset is loaded for either row.
 */
$zandi_channels = array(
	array(
		'url'   => $zandi_contact['support'],
		'name'  => $zandi_contact['support_name'],
		'label' => $zandi_contact['support_label'],
		'note'  => $zandi_contact['support_note'],
		'icon'  => 'lifebuoy',
	),
	array(
		'url'   => $zandi_contact['teacher'],
		'name'  => $zandi_contact['teacher_name'],
		'label' => $zandi_contact['teacher_label'],
		'note'  => $zandi_contact['teacher_note'],
		'icon'  => 'telegram',
	),
);

$zandi_channels = array_values(
	array_filter(
		$zandi_channels,
		static function ( $channel ) {
			return '' !== $channel['url'] && '' !== $channel['name'];
		}
	)
);

if ( ! $zandi_channels ) {
	return;
}
?>

<section class="panel-section" id="support" aria-labelledby="support-title">
	<h2 class="panel-section__title" id="support-title"><?php echo esc_html( $zandi_copy['support_title'] ); ?></h2>

	<div class="card panel-card panel-support">
		<p class="panel-support__lead"><?php echo esc_html( $zandi_copy['support_lead'] ); ?></p>

		<ul class="panel-support__list">
			<?php foreach ( $zandi_channels as $zandi_channel ) : ?>
				<li>
					<a
						class="panel-support__link"
						href="<?php echo esc_url( $zandi_channel['url'] ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<span class="icon-tile"><?php zandi_icon( $zandi_channel['icon'] ); ?></span>

						<span class="panel-support__body">
							<span class="panel-support__label"><?php echo esc_html( $zandi_channel['label'] ); ?></span>

							<?php
							/*
							 * dir="ltr" for the ORDER — «@tav_1089» is a neutral
							 * @, Latin letters, an underscore and digits, and in
							 * an RTL line those reorder so the @ ends up
							 * trailing.
							 *
							 * The direction has to sit on this element, but the
							 * element must NOT be the one the layout positions:
							 * a stretched box with dir="ltr" resolves
							 * `text-align: start` to its own left edge, so the
							 * ID would go flush left under a label flush right.
							 * It is an inline-block inside a plain RTL wrapper
							 * instead — the wrapper places it by the page's
							 * direction, the box lays out its own text
							 * internally. Same fix as the placement report.
							 *
							 * The digits stay Latin. A username is not a number
							 * and never goes through zandi_fa_digits().
							 */
							?>
							<span class="panel-support__id" dir="ltr"><?php echo esc_html( $zandi_channel['name'] ); ?></span>

							<span class="panel-support__note"><?php echo esc_html( $zandi_channel['note'] ); ?></span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="panel-card__note"><?php echo esc_html( $zandi_copy['support_body'] ); ?></p>
	</div>
</section>
