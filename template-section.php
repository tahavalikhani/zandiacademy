<?php
/**
 * Standalone section page — /{section}/
 *
 * One template for دوره‌ها, روش تدریس, درباره من, سوالات متداول and تماس. Each
 * page renders the same homepage partials it owns, so the content has a single
 * source and the page cannot drift out of step with the landing page.
 *
 * Sections and their partials are declared in zandi_sections() (functions.php).
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_section = zandi_current_section();

if ( ! $zandi_section ) {
	return;
}

get_header();
?>

<main id="main">
	<div class="page-hero">
		<div class="container">
			<h1 class="page-hero__title"><?php echo zandi_bidi( $zandi_section['title'] ); ?></h1>

			<?php if ( $zandi_section['lead'] ) : ?>
				<p class="page-hero__lead"><?php echo zandi_bidi( $zandi_section['lead'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php
	/*
	 * Each partial carries its own <section> heading. On a standalone page the
	 * <h1> above already says the same thing, so the first partial repeating it
	 * reads as a stutter — `suppress_heading` skips it. Later partials keep
	 * their headings, since those name different content.
	 */
	foreach ( $zandi_section['parts'] as $zandi_index => $zandi_part ) {
		get_template_part(
			'template-parts/home/' . $zandi_part,
			null,
			array(
				'on_section_page'  => true,
				'suppress_heading' => ( 0 === $zandi_index ),
			)
		);
	}

	// Every section page closes on the same invitation as the homepage.
	if ( ! in_array( 'contact', $zandi_section['parts'], true ) ) {
		get_template_part( 'template-parts/home/cta' );
	}
	?>
</main>

<?php
get_footer();
