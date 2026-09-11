<?php
/**
 * The podcast subscription: the maths, the states, and the token.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-podcast.php`
 * runs it; a browser gets nothing.
 *
 * Three things here are worth more than the rest.
 *
 * The STACKING RULE, because getting it wrong short-changes somebody who
 * renewed early and nobody finds out until they complain.
 *
 * The GRACE BOUNDARY, because «expired» and «still let in» are two different
 * answers on the same day and the bot acts on the second one.
 *
 * The TOKEN'S SHAPE, because Telegram's deep-link parameter accepts at most 64
 * characters and only A-Z a-z 0-9 _ - . A dot or an equals sign in there does
 * not error — the parameter is silently truncated or dropped, the bot sees no
 * token, and the student is told to connect their account again forever.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

define( 'ZANDI_BOT_SECRET', 'test-secret-not-the-real-one-0000000000' );
define( 'ZANDI_BOT_URL', 'https://bot.example.test' );

require ZANDI_THEME . '/inc/content.php';
require ZANDI_THEME . '/inc/courses.php';
require ZANDI_THEME . '/inc/icons.php';
require ZANDI_THEME . '/inc/template-tags.php';
require ZANDI_THEME . '/inc/auth.php';
require ZANDI_THEME . '/inc/panel.php';
require ZANDI_THEME . '/inc/placement.php';
require ZANDI_THEME . '/inc/podcast.php';

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;
	if ( $got === $want ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n       got:  " . var_export( $got, true ) . "\n       want: " . var_export( $want, true ) . "\n";
}

function check_true( $label, $got ) {
	global $pass, $fail;
	if ( $got ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n";
}

$day = 86400;

echo "\n— The route —\n";
check( 'the slug is /podcast/', zandi_podcast_slug(), 'podcast' );
$GLOBALS['stub_options']['permalink_structure'] = '/%postname%/';
check( 'pretty permalinks give a tidy URL', zandi_podcast_url(), 'https://example.test/podcast/' );
$GLOBALS['stub_options']['permalink_structure'] = '';
check( 'and «ساده» still resolves, instead of 404ing at the web server', zandi_podcast_url(), 'https://example.test/?zandi_podcast=1' );
$GLOBALS['stub_options']['permalink_structure'] = '/%postname%/';
check_true( 'the page is noindex while it is unlinked', zandi_podcast_noindex() );

echo "\n— The stacking rule —\n";
$t0 = 1757000000;

check( 'nothing bought is no access', zandi_podcast_stack( array() ), 0 );

check(
	'one 30-day plan runs 30 days from payment',
	zandi_podcast_stack( array( array( 'paid_at' => $t0, 'days' => 30 ) ) ),
	$t0 + ( 30 * $day )
);

check(
	'renewing EARLY adds to the remainder rather than burning it',
	zandi_podcast_stack(
		array(
			array( 'paid_at' => $t0, 'days' => 30 ),
			array( 'paid_at' => $t0 + ( 10 * $day ), 'days' => 30 ),
		)
	),
	$t0 + ( 60 * $day )
);

check(
	'renewing LATE runs from the day it was paid, not from the lapsed date',
	zandi_podcast_stack(
		array(
			array( 'paid_at' => $t0, 'days' => 30 ),
			array( 'paid_at' => $t0 + ( 100 * $day ), 'days' => 30 ),
		)
	),
	$t0 + ( 130 * $day )
);

check(
	'renewing on the exact day it lapses leaves no gap',
	zandi_podcast_stack(
		array(
			array( 'paid_at' => $t0, 'days' => 30 ),
			array( 'paid_at' => $t0 + ( 30 * $day ), 'days' => 30 ),
		)
	),
	$t0 + ( 60 * $day )
);

check(
	'a zero-day product is not a subscription and grants nothing',
	zandi_podcast_stack( array( array( 'paid_at' => $t0, 'days' => 0 ) ) ),
	0
);

echo "\n— Where a student stands —\n";
$user = 7;

check( 'no purchase is «none»', zandi_podcast_state( $user ), 'none' );
check_true( 'and «none» is not access', ! zandi_podcast_has_access( $user ) );

update_user_meta( $user, zandi_podcast_expires_meta_key(), time() + ( 5 * $day ) );
check( 'a live date is «active»', zandi_podcast_state( $user ), 'active' );
check( 'and five days left reads as five', zandi_podcast_days_left( $user ), 5 );

update_user_meta( $user, zandi_podcast_expires_meta_key(), time() - 3600 );
check( 'an hour past the date is «grace»', zandi_podcast_state( $user ), 'grace' );
check_true( 'and grace still lets you in — this is what the bot acts on', zandi_podcast_has_access( $user ) );
check( 'a day left in grace shows zero days, not a negative one', zandi_podcast_days_left( $user ), 0 );

update_user_meta( $user, zandi_podcast_expires_meta_key(), time() - ( 2 * $day ) );
check( 'past the grace period is «expired»', zandi_podcast_state( $user ), 'expired' );
check_true( 'and expired is not access', ! zandi_podcast_has_access( $user ) );

echo "\n— The manual floor, for the AradBot members —\n";
$old = 11;
update_user_meta( $old, zandi_podcast_manual_meta_key(), time() + ( 20 * $day ) );
check( 'somebody with no order but a hand-entered date is active', zandi_podcast_state( $old ), 'active' );
delete_user_meta( $old, zandi_podcast_manual_meta_key() );

echo "\n— The connect token —\n";
$token = zandi_podcast_bind_token( 42 );

check( 'a good token names the student it was made for', zandi_podcast_read_bind_token( $token ), 42 );
check_true( 'it fits in Telegram\'s 64-character start parameter', strlen( $token ) <= 64 );
check_true( 'and uses only the characters that parameter accepts', (bool) preg_match( '/^[A-Za-z0-9_-]+$/', $token ) );
check( 'a token with one character changed is refused', zandi_podcast_read_bind_token( substr( $token, 0, -1 ) . ( 'a' === substr( $token, -1 ) ? 'b' : 'a' ) ), 0 );
check( 'a token claiming another student is refused', zandi_podcast_read_bind_token( '99' . substr( $token, 2 ) ), 0 );
check( 'nonsense is refused', zandi_podcast_read_bind_token( 'hello' ), 0 );
check( 'an empty token is refused', zandi_podcast_read_bind_token( '' ), 0 );

/*
 * An expired token has to be refused even though its signature is perfect —
 * otherwise a link from a screenshot six months old still opens the group.
 */
$stale = '42-' . ( time() - 60 );
$stale = $stale . '-' . substr( hash_hmac( 'sha256', $stale, ZANDI_BOT_SECRET ), 0, 32 );
check( 'a correctly signed but expired token is refused', zandi_podcast_read_bind_token( $stale ), 0 );

check( 'the connect link is a deep link into the bot', zandi_podcast_connect_url( 42 ), 'https://t.me/bonjourmonjour_bot?start=' . $token );

echo "\n— Prices —\n";
$plans = zandi_podcast_plans();
check( 'three plans, as the owner priced them', count( $plans ), 3 );
check( 'one month is ۵۹۰٬۰۰۰ تومان', zandi_podcast_plan_price( $plans[0] ), 590000 );
check( 'three months is ۹۹۰٬۰۰۰', zandi_podcast_plan_price( $plans[1] ), 990000 );
check( 'six months is ۱٬۹۹۰٬۰۰۰', zandi_podcast_plan_price( $plans[2] ), 1990000 );
check( 'and the days match the labels', array_column( $plans, 'days' ), array( 30, 90, 180 ) );

echo "\n— What the page has, and what it is still waiting for —\n";
/*
 * The media is uploaded through wp-admin and cannot be checked from here, so
 * the episodes and the cover still assert the empty case: a section must render
 * its «به‌زودی» state rather than an empty heading or a dead player.
 *
 * The سرفصل used to assert emptiness too. It stopped being true on 11 September
 * 2026 when the owner sent fourteen chapters, and a test that pins the ABSENCE
 * of content silently becomes a test that forbids adding it.
 */
check( 'the starting price is the cheapest plan, for the hero line', zandi_podcast_starting_price(), 590000 );
check( 'three free episode slots are defined', count( zandi_podcast_episodes() ), 3 );
check( 'but none render until a file is actually uploaded', zandi_podcast_available_episodes(), array() );
check( 'and there is no cover until one is supplied', zandi_podcast_cover(), '' );

echo "\n— سرفصل —\n";
$chapters = zandi_podcast_chapters();
$topics   = 0;

foreach ( $chapters as $chapter ) {
	$topics += count( (array) $chapter['items'] );
}

check( 'fourteen chapters, as the owner sent them', count( $chapters ), 14 );
check( 'ninety-eight topics across them', $topics, 98 );

/*
 * The page prints count() of these arrays rather than a typed figure, so this
 * is really asserting that the shape the template reads is the shape the data
 * has. A row missing `items` is a fatal on a live page.
 */
$shape_ok = true;

foreach ( $chapters as $chapter ) {
	if ( empty( $chapter['title'] ) || empty( $chapter['items'] ) || ! is_array( $chapter['items'] ) ) {
		$shape_ok = false;
	}
}

check( 'every chapter has a title and a non-empty item list', $shape_ok, true );

echo "\n— متن پادکست —\n";
check( 'two transcripts, for the first two episodes', count( zandi_podcast_transcripts() ), 2 );
check( 'episode three has none yet, and asks for none', zandi_podcast_transcript( 'podcast-free-3' ), '' );
check( 'an unknown slug is not an error', zandi_podcast_transcript( 'nope' ), '' );

$blocks = zandi_podcast_transcript_blocks( zandi_podcast_transcript( 'podcast-free-1' ) );
$types  = array_count_values( array_column( $blocks, 'type' ) );

check( 'the ■ marker becomes a title', isset( $types['title'] ) && 1 === $types['title'], true );
check( 'the ● markers become items', isset( $types['item'] ) && $types['item'] > 20, true );
check( 'the ○ markers become sub-items', isset( $types['subitem'] ) && 7 === $types['subitem'], true );

/*
 * The markers must be stripped from the text, or every bullet renders with a
 * second bullet typed inside it.
 */
$leftover = false;

foreach ( $blocks as $block ) {
	if ( preg_match( '/^[■●○]/u', $block['text'] ) ) {
		$leftover = true;
	}
}

check( 'no marker survives into the rendered text', $leftover, false );

/*
 * «●●» has to be tested before «●» in the parser. If it is not, every group
 * lead is read as an ordinary bullet and this comes back as 0.
 */
$two = zandi_podcast_transcript_blocks( zandi_podcast_transcript( 'podcast-free-2' ) );
$lead = array_values( array_filter( $two, function ( $b ) { return 'lead' === $b['type']; } ) );

check( 'a ●● group lead is not read as an ordinary bullet', count( $lead ), 2 );
check( 'and its text is stripped of both marks', $lead[0]['text'], 'À partir de' );

check( 'a French line is marked as French', zandi_podcast_dir_attrs( 'Dater de' ), ' dir="ltr" lang="fr"' );
check( 'a Persian line is left to the page direction', zandi_podcast_dir_attrs( 'سلام و احوالپرسی' ), '' );

echo "\n— A subscription must be re-purchasable —\n";
/*
 * zandi_woo_block_repurchase() stops anybody buying a product they already own,
 * which is right for a course and fatal for a renewal: the student whose access
 * is about to lapse would find the button gone at the moment they wanted it.
 */
check_true( 'a podcast product stays purchasable even when owned', zandi_podcast_allow_renewal( false, 0 ) === false );

echo "\n";
echo $fail ? "FAILED: $fail\n" : "All $pass checks passed.\n";
exit( $fail ? 1 : 0 );
