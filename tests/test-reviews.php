<?php
/**
 * The student reviews — that they are intact, in the right order, and safe to
 * render in a right-to-left page.
 *
 * COMMAND LINE ONLY. `php tests/test-reviews.php`.
 *
 * THIS FILE EXISTS BECAUSE ITS PREDECESSORS DID NOT. CLAUDE.md cited a
 * `verify-quotes.php` and a `verify-sort.php` that were never in the repo —
 * they were scratchpad scripts that died with the session that wrote them,
 * exactly as an earlier `test-support.php` had. A check nobody can run is not a
 * check, so the reviews are pinned here, in the repository, next to the code.
 *
 * The hashes are the point. These are other people's words, published with
 * their names on them; a later edit that tidies someone's spelling or trims a
 * sentence to fit a card is a misquote. Changing a hash has to be a deliberate
 * act, and the only honest reason to change one is that the student sent new
 * text.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

$theme = ZANDI_THEME;
require_once $theme . '/inc/content.php';
require_once $theme . '/inc/courses.php';
require_once $theme . '/inc/icons.php';
require_once $theme . '/inc/template-tags.php';

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;

	if ( $got === $want ) {
		$pass++;
		return;
	}

	$fail++;
	printf( "FAIL  %s\n        got  %s\n        want %s\n", $label, var_export( $got, true ), var_export( $want, true ) );
}

/* ==========================================================================
   1. The words themselves
   ========================================================================== */

$expected = array(
	'ملیکا قزاقی'        => '49b106e185143f18527ecb79ab001c59',
	'شادان حیدرپور'      => 'e61cade9d4fe9d53ee82661e1f6f5d10',
	'مهدیه ولیزاده'      => '17592eea4e2b40e17862f5b702b60fcd',
	'سوگل دمرلی'         => '53a5954d9852bc25cedd20dda4f8f149',
	'مونیره زارگلی‌جوان' => '4b34abb4b535ba997437c327c764382f',
	'آیسان فتاحی'        => '1f2544aed1ee3dd6c916c2d4b0f4bfba',
	'نگار موسوی‌نژادیان' => '6fa3abf650adfd220f5ad4667ada590d',
	'صالحه شریفی'        => '390f8e3cbce31373f3ca573cf28e74f3',
	'نگین بیگی'          => '2105fda1eb7570c3ff8cb348c4616f21',
	'پریسا رفیع‌زاده'    => '3238183320bae0aadb16ecbbceef8915',
);

$items = zandi_testimonials();
$byname = array();

foreach ( $items as $item ) {
	$byname[ $item['name'] ] = $item;
}

check( 'every pinned review is still present', count( array_intersect_key( $byname, $expected ) ), count( $expected ) );

foreach ( $expected as $name => $hash ) {
	if ( ! isset( $byname[ $name ] ) ) {
		printf( "FAIL  review missing entirely: %s\n", $name );
		$fail++;
		continue;
	}

	check( 'verbatim: ' . $name, md5( $byname[ $name ]['quote'] ), $hash );
}

// A new review is fine; it just has to be pinned here too, or the next edit to
// it goes unnoticed.
check( 'no review is unpinned', count( $items ), count( $expected ) );

foreach ( $items as $item ) {
	check( 'has a name: ' . $item['name'], '' !== trim( $item['name'] ), true );
	check( 'has a quote: ' . $item['name'], '' !== trim( $item['quote'] ), true );
}

check( 'no duplicate reviewers', count( array_unique( wp_list_pluck( $items, 'name' ) ) ), count( $items ) );

/* ==========================================================================
   2. Order — returning students lead, given order kept inside each group
   ========================================================================== */

$groups = array_map(
	static function ( $t ) {
		return count( $t['courses'] ) > 1 ? 0 : 1;
	},
	$items
);

$sorted = $groups;
sort( $sorted );

check( 'students who bought more than one course lead', $groups, $sorted );

// The owner asked on 15 September 2026 that مهدیه not be the first card. She
// bought two courses, so she sorts into the leading group and only her position
// in the source array keeps her off the front.
$names = wp_list_pluck( $items, 'name' );
check( 'مهدیه is in the list', in_array( 'مهدیه ولیزاده', $names, true ), true );
check( 'مهدیه is not the first card', 0 === array_search( 'مهدیه ولیزاده', $names, true ), false );

/* ==========================================================================
   3. The badge — levels only for a student who bought more than one course
   ========================================================================== */

foreach ( $items as $item ) {
	$badge = zandi_testimonial_levels( $item );

	if ( count( $item['courses'] ) < 2 ) {
		check( 'no level on a single-course card: ' . $item['name'], $badge, '' );
		continue;
	}

	check( 'badge is Latin codes only: ' . $item['name'], (bool) preg_match( '/^[A-Za-z0-9 ·]+$/u', $badge ), true );
	check( 'badge names every course: ' . $item['name'], substr_count( $badge, '·' ), count( $item['courses'] ) - 1 );
}

check( 'two courses  -> two codes',  zandi_testimonial_levels( array( 'courses' => array( 'a1', 'a2' ) ) ), 'A1 · A2' );
check( 'three courses -> three',     zandi_testimonial_levels( array( 'courses' => array( 'a1', 'a2', 'b1' ) ) ), 'A1 · A2 · B1' );
check( 'unknown slug is skipped',    zandi_testimonial_levels( array( 'courses' => array( 'a1', 'c9', 'b1' ) ) ), 'A1 · B1' );
check( 'one real + one unknown -> none (a lone code is a level)',
	zandi_testimonial_levels( array( 'courses' => array( 'a1', 'c9' ) ) ), '' );
check( 'missing key -> no badge', zandi_testimonial_levels( array() ), '' );

/* ==========================================================================
   4. Bidi — students write French inside Persian sentences
   ========================================================================== */

$with_latin = 0;

foreach ( $items as $item ) {
	if ( ! preg_match( '/[A-Za-z]/', $item['quote'] ) ) {
		continue;
	}

	$with_latin++;
	$rendered = zandi_bidi( $item['quote'] );

	check( 'Latin isolated in: ' . $item['name'], (bool) strpos( $rendered, '<span dir="ltr" class="latin-run">' ), true );

	// Every Latin run in the source must come back inside an isolate. A run
	// left bare is one that will reorder against its neighbours on screen.
	preg_match_all( '/[A-Za-z][A-Za-z0-9.]*/', $item['quote'], $runs );

	foreach ( array_unique( $runs[0] ) as $run ) {
		check(
			sprintf( '«%s» isolated in %s', $run, $item['name'] ),
			(bool) preg_match( '/<span dir="ltr" class="latin-run">[^<]*' . preg_quote( $run, '/' ) . '/u', $rendered ),
			true
		);
	}
}

check( 'reviews containing Latin were actually found', $with_latin > 0, true );

// The specific shapes the students actually wrote, which a regex change could
// silently break: a conjunction glued to a code, a lowercase code, and a
// sub-level carrying a dot.
check( 'وB1 — conjunction glued to a code',
	(bool) preg_match( '/و<span dir="ltr" class="latin-run">B1<\/span>/u', zandi_bidi( 'پکیج A2 وB1 تهیه کردم' ) ), true );
check( 'b1 — lowercase code',
	(bool) preg_match( '/<span dir="ltr" class="latin-run">b1<\/span>/u', zandi_bidi( 'ترتیب A2 و b1 رو' ) ), true );
check( 'A1.2 — a dot inside the run, not ending it',
	(bool) preg_match( '/<span dir="ltr" class="latin-run">A1\.2<\/span>/u', zandi_bidi( 'من تا A1.2 رو خوندم' ) ), true );

/* ==========================================================================
   5. The rendered card
   ========================================================================== */

ob_start();
zandi_testimonials_carousel();
$html = ob_get_clean();

check( 'the carousel renders', '' !== trim( $html ), true );
check( 'one card per review', substr_count( $html, 'class="card testimonial' ), count( $items ) );
check( 'quotes are bidi-isolated in the markup too', substr_count( $html, 'class="latin-run"' ) > 0, true );
check( 'the badge element carries dir="ltr"', (bool) strpos( $html, '<span class="testimonial__badge" dir="ltr">' ), true );
check( 'returning students get the tinted card', substr_count( $html, 'testimonial--returning' ), count( array_filter( $groups, static function ( $g ) { return 0 === $g; } ) ) );
check( 'no rating is rendered', strpos( $html, 'class="rating' ), false );
check( 'the region is on the wrapper, not the list', (bool) preg_match( '/<div class="carousel[^"]*" role="region"/', $html ), true );
check( 'the list keeps its list role', (bool) preg_match( '/<ul class="carousel__track" tabindex="0">/', $html ), true );

// Each expander has to be tellable apart by a screen reader.
preg_match_all( '/<span class="screen-reader-text">([^<]+)<\/span>/', $html, $sr );
check( 'every expander is named for its student', count( array_unique( $sr[1] ) ), count( $items ) );

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail ? 1 : 0 );
