<?php
/**
 * /dictionary/: filing keys, lookups, redirects and every view rendered.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP, so every file in
 * it has to assume a stranger can request it. `php tests/test-dictionary.php`
 * runs it; a browser gets nothing.
 *
 * It runs against the REAL data in inc/data/dictionary/, so it also proves the
 * build: the PHP filing keys must find what tools/dictionary/build.py filed in
 * Python, character for character, or words silently vanish.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

set_error_handler(
	function ( $no, $str, $file, $line ) {
		throw new ErrorException( $str . ' — ' . basename( $file ) . ':' . $line, 0, $no, $file, $line );
	}
);

// Core functions the shared stub does not carry.
function wp_strip_all_tags( $text ) { return trim( strip_tags( (string) $text ) ); }
function status_header( $code ) { $GLOBALS['stub_status'] = $code; }
function wp_kses( $html, $allowed ) { return $html; }
function get_header() {}
function get_footer() {}
function zandi_asset_version( $path = '' ) { return '1'; }
function __return_false() { return false; }

require ZANDI_THEME . '/inc/content.php';
require ZANDI_THEME . '/inc/icons.php';
require ZANDI_THEME . '/inc/template-tags.php';
require ZANDI_THEME . '/inc/dictionary.php';

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;
	if ( $got === $want ) { ++$pass; echo "  ok   $label\n"; return; }
	++$fail; echo "  FAIL $label\n       got:  " . var_export( $got, true ) . "\n       want: " . var_export( $want, true ) . "\n";
}

function check_true( $label, $got ) {
	check( $label, (bool) $got, true );
}

/**
 * Sets up a request the way the rewrite rules would.
 *
 * @param string $word Word in the address ('' for none).
 * @param bool   $conj The conjugation sub-page.
 * @param array  $get  Query string.
 */
function visit( $word = '', $conj = false, $get = array() ) {
	$GLOBALS['stub_query_vars'] = array( 'zandi_dictionary' => '1' );

	if ( '' !== $word ) {
		$GLOBALS['stub_query_vars']['zandi_dict_word'] = $word;
	}

	if ( $conj ) {
		$GLOBALS['stub_query_vars']['zandi_dict_conj'] = '1';
	}

	$_GET = $get;
	unset( $GLOBALS['stub_redirect'], $GLOBALS['stub_status'] );
}

/**
 * Runs the redirect hook and returns where it sent the visitor, or ''.
 */
function redirect_target() {
	try {
		zandi_dictionary_redirect();
	} catch ( Zandi_Stub_Redirect $r ) {
		return $GLOBALS['stub_redirect'];
	}

	return '';
}

/**
 * Renders the whole template and returns the markup.
 */
function render() {
	ob_start();
	include ZANDI_THEME . '/template-dictionary.php';

	return ob_get_clean();
}

echo "The data is installed\n";
$meta = zandi_dict_read( 'meta' );
check_true( 'meta.json reads', zandi_dict_available() );
check_true( 'thousands of French words', $meta['counts']['fr'] > 3000 );
check_true( 'thousands of Persian words', $meta['counts']['fa'] > 3000 );
check_true( 'hundreds of conjugated verbs', $meta['counts']['verbs'] > 200 );

echo "\nFiling keys agree with the build\n";
check( 'accents and case fold', zandi_dict_norm_fr( 'Élève' ), 'eleve' );
check( 'œ unfolds', zandi_dict_norm_fr( 'Œuf' ), 'oeuf' );
check( 'typographic apostrophe', zandi_dict_norm_fr( 'aujourd’hui' ), "aujourd'hui" );
check( 'harakat removed', zandi_dict_clean_fa( 'خَانَه' ), 'خانه' );
check( 'Arabic ي and ك', zandi_dict_norm_fa( 'كتاب' ), zandi_dict_norm_fa( 'کتاب' ) );
check( 'ZWNJ and space file together', zandi_dict_norm_fa( 'می‌خواهم' ), zandi_dict_norm_fa( 'می خواهم' ) );
check( 'Persian trim is not byte-wise', zandi_dict_clean_fa( '،خانه؛' ), 'خانه' );
check( 'French shard', zandi_dict_fr_shard( 'maison' ), 'ma' );
check( 'one-letter shard pads', zandi_dict_fr_shard( 'a' ), 'a_' );
check( 'apostrophe shard', zandi_dict_fr_shard( "l'eau" ), 'l_' );
check( 'Persian shard is the first letter', zandi_dict_fa_shard( zandi_dict_norm_fa( 'خانه' ) ), '062e' );
check( 'a shard name cannot climb out', zandi_dict_read( 'fr', '../' ), array() );
check( 'nor can a kind', zandi_dict_read( '../inc', 'ma' ), array() );

// Every key in a sample of shards must be the PHP key of its own word.
$mismatch = array();
foreach ( glob( ZANDI_THEME . '/inc/data/dictionary/fr/*.json' ) as $i => $path ) {
	if ( $i % 7 ) {
		continue;
	}
	$shard = json_decode( file_get_contents( $path ), true );
	foreach ( $shard['e'] as $key => $pages ) {
		foreach ( $pages as $page ) {
			if ( zandi_dict_norm_fr( $page['w'] ) !== (string) $key || zandi_dict_fr_shard( (string) $key ) !== basename( $path, '.json' ) ) {
				$mismatch[] = $page['w'];
			}
		}
	}
}
check( 'every French word filed where PHP looks', array_slice( $mismatch, 0, 5 ), array() );

$mismatch = array();
foreach ( glob( ZANDI_THEME . '/inc/data/dictionary/fa/*.json' ) as $path ) {
	foreach ( json_decode( file_get_contents( $path ), true ) as $key => $page ) {
		if ( zandi_dict_norm_fa( $page['w'] ) !== (string) $key || zandi_dict_fa_shard( (string) $key ) !== basename( $path, '.json' ) ) {
			$mismatch[] = $page['w'];
		}
	}
}
check( 'every Persian word filed where PHP looks', array_slice( $mismatch, 0, 5 ), array() );

echo "\nLookups\n";
check_true( 'maison is a word', zandi_dict_fr_page( 'maison' ) );
check( 'maison means خانه', zandi_dict_fr_page( 'maison' )['b'][0]['m'][0][0][0], 'خانه' );
check( 'maison is feminine', zandi_dict_fr_page( 'maison' )['b'][0]['g'], 'f' );
check_true( 'خانه is a word', zandi_dict_fa_page( 'خانه' ) );
check_true( 'خانه gives maison', in_array( 'maison', array_column( zandi_dict_fa_page( 'خانه' )['fr'], 0 ), true ) );
check( 'prends → prendre', zandi_dict_resolve( 'prends' ), array( 'type' => 'form', 'word' => 'prendre', 'from' => 'prends' ) );
check( 'Maison → maison', zandi_dict_resolve( 'Maison' ), array( 'type' => 'fr', 'word' => 'maison' ) );
check( 'a Persian search', zandi_dict_resolve( 'خانه' )['type'], 'fa' );
check( 'nonsense finds nothing', zandi_dict_resolve( 'zzqqxx' ), array( 'type' => 'none' ) );
check( 'empty finds nothing', zandi_dict_resolve( '   ' ), array( 'type' => 'none' ) );

$conj = zandi_dict_conjugation( 'aller' );
check_true( 'aller conjugates', $conj );
check( 'aller takes être', $conj['aux'], 'être' );
check( 'je vais', $conj['t']['present'][0], array( 'je', 'vais' ) );
check( 'nous allons', $conj['t']['present'][3], array( 'nous', 'allons' ) );
check( 'avoir takes avoir', zandi_dict_conjugation( 'manger' )['aux'], 'avoir' );
check( 'j’ joins without a space', zandi_dictionary_conj_form( 'j’', 'irai' ), '<span class="dict-conj__pron">j’</span><b class="dict-conj__form">irai</b>' );
check( 'je takes a space', zandi_dictionary_conj_form( 'je', 'vais' ), '<span class="dict-conj__pron">je</span> <b class="dict-conj__form">vais</b>' );
check( 'the article for a vowel', zandi_dictionary_article( 'eau', 'f' ), 'l’' );
check( 'the article for a feminine', zandi_dictionary_article( 'maison', 'f' ), 'la ' );
check( 'no article for an unknown gender', zandi_dictionary_article( 'élève', 'mf' ), '' );

echo "\nURLs\n";
check( 'front page', zandi_dictionary_url(), 'https://example.test/dictionary/' );
check( 'an accented word is encoded', zandi_dictionary_url( 'élève' ), 'https://example.test/dictionary/%C3%A9l%C3%A8ve/' );
check( 'conjugation', zandi_dictionary_url( 'aller', true ), 'https://example.test/dictionary/aller/conjugation/' );
$GLOBALS['stub_options']['permalink_structure'] = '';
check( 'plain permalinks keep the word encoded', zandi_dictionary_url( 'élève', true ), 'https://example.test/?zandi_dictionary=1&zandi_dict_word=%C3%A9l%C3%A8ve&zandi_dict_conj=1' );
unset( $GLOBALS['stub_options']['permalink_structure'] );

echo "\nRedirects\n";
visit( '', false, array( 'q' => 'maison' ) );
check( 'a search that finds a word goes to it', redirect_target(), 'https://example.test/dictionary/maison/' );
visit( '', false, array( 'q' => 'prends' ) );
check( 'a form goes to its word, saying so', redirect_target(), 'https://example.test/dictionary/prendre/?from=prends' );
visit( '', false, array( 'q' => 'خانه' ) );
check( 'a Persian search goes to its page', redirect_target(), 'https://example.test/dictionary/' . rawurlencode( 'خانه' ) . '/' );
visit( 'Maison' );
check( 'a capital in the address is a 301 to the word', redirect_target(), 'https://example.test/dictionary/maison/' );
visit( rawurlencode( 'maison' ) );
check( 'an encoded address is decoded first', redirect_target(), '' );
visit( 'maison' );
check( 'the word itself does not redirect', redirect_target(), '' );
visit( '', false, array( 'q' => 'zzqqxx' ) );
check( 'nothing found stays to say so', redirect_target(), '' );

echo "\nEvery view renders\n";
visit();
$html = render();
check_true( 'home has the search form', false !== strpos( $html, 'class="dict-search"' ) );
check_true( 'home lists common words', false !== strpos( $html, 'dict-chips' ) );
check_true( 'the licence credit is on the page', false !== strpos( $html, 'CC BY-SA' ) && false !== strpos( $html, 'Tatoeba' ) );
check_true( 'the suggestion list ships hidden', (bool) preg_match( '/<ul id="dict-suggest"[^>]* hidden>/', $html ) );
check_true( 'the French letters ship hidden', (bool) preg_match( '/class="dict-accents"[^>]* hidden>/', $html ) );

visit( 'maison' );
$html = render();
check_true( 'maison: the word is the h1', false !== strpos( $html, '<h1 class="dict-entry__word" lang="fr" dir="ltr">maison</h1>' ) );
check_true( 'maison: «la maison»', false !== strpos( $html, 'la maison' ) );
check_true( 'maison: meaning links to its Persian page', false !== strpos( $html, rawurlencode( 'خانه' ) ) );
check_true( 'maison: the listen button ships hidden', (bool) preg_match( '/class="dict-listen"[^>]*hidden>/', $html ) );
check_true( 'maison: an example sentence', false !== strpos( $html, 'dict-example__fr' ) );
check_true( 'maison: no .reveal', false === strpos( $html, 'reveal' ) );
check( 'maison: title', zandi_dictionary_page_title(), 'معنی maison به فارسی' );

visit( 'prendre', false, array( 'from' => 'prends' ) );
$html = render();
check_true( 'prendre: says it came from prends', false !== strpos( $html, 'dict-entry__from' ) );
check_true( 'prendre: the present tense', false !== strpos( $html, 'prenons' ) );
check_true( 'prendre: links its conjugation', false !== strpos( $html, '/dictionary/prendre/conjugation/' ) );
visit( 'prendre', false, array( 'from' => 'maison' ) );
check_true( 'a «from» that is not a form of the word is ignored', false === strpos( render(), 'dict-entry__from' ) );

visit( 'aller', true );
$html = render();
check_true( 'aller conjugation: the h1', false !== strpos( $html, 'dict-conj__title' ) );
check_true( 'aller conjugation: je suis allé', false !== strpos( $html, 'suis allé' ) );
check_true( 'aller conjugation: the rarer tenses fold away', false !== strpos( $html, '<details class="dict-more">' ) );
check( 'aller conjugation: title', zandi_dictionary_page_title(), 'صرف فعل aller' );

visit( 'خانه' );
$html = render();
check_true( 'خانه: lists maison', false !== strpos( $html, '/dictionary/maison/' ) );
check( 'خانه: title', zandi_dictionary_page_title(), '«خانه» به فرانسوی' );

visit( 'zzqqxx' );
zandi_dictionary_status();
$html = render();
check( 'an unknown word answers 404', isset( $GLOBALS['stub_status'] ) ? $GLOBALS['stub_status'] : 200, 404 );
check_true( 'and still shows the search', false !== strpos( $html, 'class="dict-search"' ) );

visit( 'maisonx' );
$html = render();
check_true( 'a near miss offers the word', false !== strpos( $html, '/dictionary/maison/' ) );

visit( 'maison', true );
check( 'a noun has no conjugation page', zandi_dictionary_state()['view'], 'notfound' );

echo "\nHead\n";
visit( 'maison' );
ob_start();
zandi_dictionary_head();
$head = ob_get_clean();
check_true( 'noindex while unlaunched', false !== strpos( $head, 'noindex' ) );
check_true( 'canonical is the word', false !== strpos( $head, '<link rel="canonical" href="https://example.test/dictionary/maison/">' ) );
check_true( 'description names a meaning', false !== strpos( $head, 'خانه' ) );
add_filter( 'zandi_dictionary_noindex', '__return_false' );
ob_start();
zandi_dictionary_head();
check_true( 'launched: a word page is indexable', false === strpos( ob_get_clean(), 'noindex' ) );
visit( 'zzqqxx' );
ob_start();
zandi_dictionary_head();
check_true( 'launched: a miss is still noindex', false !== strpos( ob_get_clean(), 'noindex' ) );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
