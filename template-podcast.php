<?php
/**
 * پادکست Bonjour Monjour — /podcast/
 *
 * Section ordering only. Every partial owns its markup and every string comes
 * from zandi_podcast_copy(); the entitlement, the plans and the route all live
 * in inc/podcast.php.
 *
 * Deliberately not linked from anywhere yet — no menu item, no footer column,
 * noindex. The owner tests it at this URL first and wires it up afterwards.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="podcast-page">
	<?php
	/*
	 * The plans sit LAST, and the hero carries an anchor down to them. Choosing
	 * between three durations is a decision somebody makes after they know what
	 * they are buying — but the offer has to be visible in the first screen or
	 * there is no reason to keep reading. Hence a button at the top that moves,
	 * rather than a price list that interrupts.
	 *
	 * «چطور کار می‌کند» is NOT in this list: it renders inside the plans
	 * section, directly above the cards. Connecting a Telegram account is a
	 * step nobody expects after paying, and an unexpected step after the money
	 * is where a refund request comes from — so it is said beforehand, at the
	 * moment it matters, rather than in a band of its own further up the page.
	 */
	get_template_part( 'template-parts/podcast/hero' );
	get_template_part( 'template-parts/podcast/episodes' );
	get_template_part( 'template-parts/podcast/syllabus' );
	get_template_part( 'template-parts/podcast/plans' );
	?>
</main>

<?php
get_footer();
