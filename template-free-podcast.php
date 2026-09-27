<?php
/**
 * پادکست رایگان — /free-podcast/
 *
 * Section ordering only. The offer, the states and every string live in
 * inc/free-podcast.php; the gift itself becomes access in inc/podcast.php.
 *
 * Short on purpose — the owner's brief was a headline, a few cards and a big
 * button, and nothing that reads like small print. The closing band repeats the
 * button for somebody who scrolled past the first one, and only to somebody who
 * can still use it: a signed-in visitor has nothing left to sign up for.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_user_id = get_current_user_id();
$zandi_state   = zandi_free_podcast_state( $zandi_user_id );

get_header();
?>

<main id="main" class="free-podcast-page free-podcast-page--<?php echo esc_attr( $zandi_state ); ?>">
	<?php
	get_template_part(
		'template-parts/free-podcast/hero',
		null,
		array(
			'state'   => $zandi_state,
			'user_id' => $zandi_user_id,
		)
	);

	get_template_part( 'template-parts/free-podcast/cards' );

	if ( 'guest' === $zandi_state ) {
		get_template_part( 'template-parts/free-podcast/cta' );
	}
	?>
</main>

<?php
get_footer();
