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
	get_template_part( 'template-parts/podcast/hero' );
	get_template_part( 'template-parts/podcast/plans' );
	get_template_part( 'template-parts/podcast/how' );
	?>
</main>

<?php
get_footer();
