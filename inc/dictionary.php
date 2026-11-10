<?php
/**
 * واژه‌نامه — the French ⇄ Persian dictionary at /dictionary/.
 *
 * WHAT IT IS MADE OF
 *
 * Three free sources, joined offline by tools/dictionary/build.py and committed
 * as small JSON shards under inc/data/dictionary/:
 *
 *   - French Wiktionary: the French words, their parts of speech, gender,
 *     pronunciation (IPA), French definitions, full conjugation tables, and
 *     the Persian translations its editors wrote.
 *   - English Wiktionary: Persian meanings for French words through one
 *     English sense's translation table (the build script explains the rule).
 *   - Tatoeba: example sentences with a Persian translation.
 *
 * Wiktionary is CC BY-SA and Tatoeba CC BY 2.0 FR. Both require credit, and the
 * CC BY-SA data stays CC BY-SA — every dictionary page carries the attribution
 * line from zandi_dictionary_copy(). Removing it is a licence breach.
 *
 * NOTHING HERE CALLS ANOTHER SERVER
 *
 * The owner's one condition was that the dictionary must not slow the site.
 * So no request leaves the server, ever: a page reads ONE shard of a few tens
 * of KB from disk, decodes it, and renders. The rest of the site does not load
 * this file's CSS or JS and never touches the data. Suggestions while typing
 * are static JSON files under assets/dictionary/, fetched by the browser
 * straight from the web server with no PHP at all. Pronunciation uses the
 * browser's own French voice, so there is no audio file and no third-party
 * host (upload.wikimedia.org cannot be relied on from Iran).
 *
 * Pages set no cookie, so LiteSpeed caches every word page like any other
 * anonymous page.
 *
 * THE ROUTE
 *
 *   /dictionary/                        search box and two short lists
 *   /dictionary/?q=prends               a search: redirects to the word
 *   /dictionary/{word}/                 a French word, or a Persian one
 *   /dictionary/{verb}/conjugation/     the full conjugation of a verb
 *
 * Declared in all three places in functions.php, with a query-string fallback
 * through zandi_dictionary_url(), and given a title filter below — the rules in
 * CLAUDE.md for every route.
 *
 * Built unlinked and noindex, like /placement/ was: zandi_dictionary_noindex()
 * is the one line that launches it.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. The route
 * ====================================================================== */

/**
 * The slug the dictionary answers on.
 *
 * @return string
 */
function zandi_dictionary_slug() {
	return (string) apply_filters( 'zandi_dictionary_slug', 'dictionary' );
}

/**
 * Whether the current request is any dictionary page.
 *
 * @return bool
 */
function zandi_is_dictionary() {
	return (bool) get_query_var( 'zandi_dictionary' );
}

/**
 * The word in the address, decoded, or ''.
 *
 * WordPress hands the rewrite capture over either percent-encoded or decoded
 * depending on which of its two matches succeeded, and slashed either way, so
 * both are undone here rather than at every caller.
 *
 * @return string
 */
function zandi_dictionary_request_word() {
	$word = wp_unslash( (string) get_query_var( 'zandi_dict_word' ) );

	if ( false !== strpos( $word, '%' ) ) {
		$word = rawurldecode( $word );
	}

	return zandi_dictionary_clean_query( $word );
}

/**
 * Whether the address asks for a conjugation table.
 *
 * @return bool
 */
function zandi_dictionary_request_conj() {
	return (bool) get_query_var( 'zandi_dict_conj' );
}

/**
 * Trims a typed word to something safe to look up and print.
 *
 * @param string $text Raw text.
 * @return string
 */
function zandi_dictionary_clean_query( $text ) {
	$text = wp_strip_all_tags( (string) $text );
	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = trim( (string) $text );

	return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 60 ) : substr( $text, 0, 120 );
}

/**
 * The URL of the dictionary, a word in it, or a verb's conjugation.
 *
 * `add_query_arg()` does not urlencode (CLAUDE.md), so the plain-permalink
 * branch builds its query string by hand with rawurlencode().
 *
 * @param string $word Word, or '' for the dictionary's front page.
 * @param bool   $conj Whether to link the conjugation table.
 * @return string
 */
function zandi_dictionary_url( $word = '', $conj = false ) {
	$word = (string) $word;

	if ( zandi_pretty_permalinks() ) {
		$path = '/' . zandi_dictionary_slug() . '/';

		if ( '' !== $word ) {
			$path .= rawurlencode( $word ) . '/';

			if ( $conj ) {
				$path .= 'conjugation/';
			}
		}

		return home_url( $path );
	}

	$query = 'zandi_dictionary=1';

	if ( '' !== $word ) {
		$query .= '&zandi_dict_word=' . rawurlencode( $word );

		if ( $conj ) {
			$query .= '&zandi_dict_conj=1';
		}
	}

	return home_url( '/?' . $query );
}

/**
 * Whether the dictionary is still hidden from search engines.
 *
 * True while the owner looks it over. Flip the default to false — and add a
 * menu tab and sitemap entries — to launch it: every word page is then a page
 * people find by searching «معنی prendre».
 *
 * @return bool
 */
function zandi_dictionary_noindex() {
	return (bool) apply_filters( 'zandi_dictionary_noindex', true );
}

/* =========================================================================
 * 2. Normalisation — MUST match tools/dictionary/build.py
 *
 * A key is how a word is filed, not how it is shown: «Élève», «eleve» and
 * «élève» are one key, so a student without a French keyboard still finds the
 * word. The build script files every word under the same function in Python;
 * tests/test-dictionary.php reads keys the build wrote and checks these agree.
 * ====================================================================== */

/**
 * The French lookup key: lower case, accents off, typographic apostrophe → '.
 *
 * @param string $text Text.
 * @return string
 */
function zandi_dict_norm_fr( $text ) {
	$text = trim( (string) $text );
	$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	$text = str_replace( array( 'œ', 'æ' ), array( 'oe', 'ae' ), $text );
	$text = strtr(
		$text,
		array(
			'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
			'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
			'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n',
			'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
			'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
			'’' => "'", '‘' => "'", 'ʼ' => "'",
		)
	);

	return (string) preg_replace( '/\s+/u', ' ', $text );
}

/**
 * Persian as it is shown: no harakat, Persian ی and ک, no bracketed notes.
 *
 * @param string $text Text.
 * @return string
 */
function zandi_dict_clean_fa( $text ) {
	$text = (string) preg_replace( '/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', (string) $text );
	$text = str_replace( array( 'ي', 'ك', 'ى' ), array( 'ی', 'ک', 'ی' ), $text );
	$text = (string) preg_replace( '/\([^)]*\)/u', '', $text );
	$text = (string) preg_replace( '/[\s\x{00A0}]+/u', ' ', $text );

	// Not trim(): it works on bytes, and «،» and «؛» are two bytes each.
	return (string) preg_replace( '/^[ ،,;؛\-]+|[ ،,;؛\-]+$/u', '', $text );
}

/**
 * The Persian lookup key: cleaned, then spaces and ZWNJ removed, so «می‌خواهم»
 * typed with a space, a ZWNJ or neither files in one place.
 *
 * @param string $text Text.
 * @return string
 */
function zandi_dict_norm_fa( $text ) {
	$text = zandi_dict_clean_fa( $text );
	$text = str_replace( array( 'أ', 'إ', 'ة', 'ۀ' ), array( 'ا', 'ا', 'ه', 'ه' ), $text );

	return str_replace( array( "\u{200C}", ' ' ), '', $text );
}

/**
 * Whether text is Persian (any Arabic-script letter in it).
 *
 * @param string $text Text.
 * @return bool
 */
function zandi_dict_is_persian( $text ) {
	return (bool) preg_match( '/\p{Arabic}/u', (string) $text );
}

/**
 * The shard a French key is filed in: its first two letters, a–z, else «_».
 *
 * @param string $key Normalised key.
 * @return string
 */
function zandi_dict_fr_shard( $key ) {
	$two = function_exists( 'mb_substr' ) ? mb_substr( $key . '__', 0, 2, 'UTF-8' ) : substr( $key . '__', 0, 2 );
	$out = '';

	foreach ( preg_split( '//u', $two, -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
		$out .= ( $char >= 'a' && $char <= 'z' && 1 === strlen( $char ) ) ? $char : '_';
	}

	return $out;
}

/**
 * The shard a Persian key is filed in: its first letter's code point in hex.
 *
 * @param string $key Normalised key.
 * @return string
 */
function zandi_dict_fa_shard( $key ) {
	if ( '' === $key ) {
		return '0000';
	}

	$first = function_exists( 'mb_substr' ) ? mb_substr( $key, 0, 1, 'UTF-8' ) : $key;
	$code  = function_exists( 'mb_ord' ) ? mb_ord( $first, 'UTF-8' ) : 0;

	return sprintf( '%04x', (int) $code );
}

/* =========================================================================
 * 3. Reading the data
 *
 * One shard per lookup, decoded once per request. The shard name is computed,
 * never taken from the request, and is checked against a pattern anyway before
 * it reaches the filesystem.
 * ====================================================================== */

/**
 * Reads one data file, memoised for the request.
 *
 * @param string $kind  'fr', 'fa' or 'conj', or 'meta'.
 * @param string $shard Shard name ('' for meta).
 * @return array
 */
function zandi_dict_read( $kind, $shard = '' ) {
	static $cache = array();

	$id = $kind . '/' . $shard;

	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}

	$cache[ $id ] = array();

	if ( 'meta' === $kind ) {
		$relative = 'inc/data/dictionary/meta.json';
	} elseif ( in_array( $kind, array( 'fr', 'conj' ), true ) && preg_match( '/^[a-z_]{2}$/', $shard ) ) {
		$relative = 'inc/data/dictionary/' . $kind . '/' . $shard . '.json';
	} elseif ( 'fa' === $kind && preg_match( '/^[0-9a-f]{4}$/', $shard ) ) {
		$relative = 'inc/data/dictionary/fa/' . $shard . '.json';
	} else {
		return $cache[ $id ];
	}

	$path = get_theme_file_path( $relative );

	if ( ! is_readable( $path ) ) {
		return $cache[ $id ];
	}

	$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local theme file.

	$cache[ $id ] = is_array( $data ) ? $data : array();

	return $cache[ $id ];
}

/**
 * Whether the data is installed at all.
 *
 * @return bool
 */
function zandi_dict_available() {
	$meta = zandi_dict_read( 'meta' );

	return ! empty( $meta['counts']['fr'] );
}

/**
 * The French pages filed under the same key as a typed word.
 *
 * @param string $word Word as typed.
 * @return array[] Pages, most frequent first.
 */
function zandi_dict_fr_pages( $word ) {
	$key   = zandi_dict_norm_fr( $word );
	$shard = zandi_dict_read( 'fr', zandi_dict_fr_shard( $key ) );

	return isset( $shard['e'][ $key ] ) ? (array) $shard['e'][ $key ] : array();
}

/**
 * The French page for exactly this word, or null.
 *
 * @param string $word Word.
 * @return array|null
 */
function zandi_dict_fr_page( $word ) {
	foreach ( zandi_dict_fr_pages( $word ) as $page ) {
		if ( $page['w'] === $word ) {
			return $page;
		}
	}

	return null;
}

/**
 * The words a typed form belongs to: «prends» → prendre, «maisons» → maison.
 *
 * @param string $word Word as typed.
 * @return string[]
 */
function zandi_dict_form_lemmas( $word ) {
	$key   = zandi_dict_norm_fr( $word );
	$shard = zandi_dict_read( 'fr', zandi_dict_fr_shard( $key ) );

	return isset( $shard['f'][ $key ] ) ? array_map( 'strval', (array) $shard['f'][ $key ] ) : array();
}

/**
 * The Persian page for a word, or null.
 *
 * @param string $word Word as typed.
 * @return array|null
 */
function zandi_dict_fa_page( $word ) {
	$key   = zandi_dict_norm_fa( $word );
	$shard = zandi_dict_read( 'fa', zandi_dict_fa_shard( $key ) );

	return isset( $shard[ $key ] ) ? (array) $shard[ $key ] : null;
}

/**
 * A verb's full conjugation, or null.
 *
 * @param string $word Infinitive.
 * @return array|null
 */
function zandi_dict_conjugation( $word ) {
	$shard = zandi_dict_read( 'conj', zandi_dict_fr_shard( zandi_dict_norm_fr( $word ) ) );

	return isset( $shard[ $word ] ) ? (array) $shard[ $word ] : null;
}

/**
 * Where a typed word should go.
 *
 * @param string $query Word as typed.
 * @return array{type:string,word?:string,words?:string[],from?:string}
 *               type is 'fr' (exact page), 'fa', 'form' (a form of one word),
 *               'choices' (several words fit) or 'none'.
 */
function zandi_dict_resolve( $query ) {
	$query = zandi_dictionary_clean_query( $query );

	if ( '' === $query ) {
		return array( 'type' => 'none' );
	}

	if ( zandi_dict_is_persian( $query ) ) {
		$page = zandi_dict_fa_page( $query );

		return $page ? array( 'type' => 'fa', 'word' => (string) $page['w'] ) : array( 'type' => 'none' );
	}

	$pages = zandi_dict_fr_pages( $query );

	foreach ( $pages as $page ) {
		if ( $page['w'] === $query ) {
			return array( 'type' => 'fr', 'word' => $query );
		}
	}

	$words = array();

	foreach ( $pages as $page ) {
		$words[] = (string) $page['w'];
	}

	foreach ( zandi_dict_form_lemmas( $query ) as $lemma ) {
		if ( ! in_array( $lemma, $words, true ) ) {
			$words[] = $lemma;
		}
	}

	if ( 1 === count( $words ) ) {
		$is_form = ! $pages;

		return $is_form
			? array( 'type' => 'form', 'word' => $words[0], 'from' => $query )
			: array( 'type' => 'fr', 'word' => $words[0] );
	}

	return $words ? array( 'type' => 'choices', 'words' => $words ) : array( 'type' => 'none' );
}

/**
 * A few words that start like the query, for a page that found nothing.
 *
 * Reads the same static suggestion file the browser uses.
 *
 * @param string $query Word as typed.
 * @param int    $limit How many.
 * @return array[] [word, gloss, lemma] rows.
 */
function zandi_dict_suggestions( $query, $limit = 8 ) {
	$persian = zandi_dict_is_persian( $query );
	$key     = $persian ? zandi_dict_norm_fa( $query ) : zandi_dict_norm_fr( $query );

	if ( '' === $key ) {
		return array();
	}

	$file = $persian ? 'fa-' . zandi_dict_fa_shard( $key ) : 'fr-' . zandi_dict_fr_shard( $key );

	if ( ! preg_match( '/^(fr-[a-z_]{2}|fa-[0-9a-f]{4})$/', $file ) ) {
		return array();
	}

	$path = get_theme_file_path( 'assets/dictionary/' . $file . '.json' );

	if ( ! is_readable( $path ) ) {
		return array();
	}

	$rows = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local theme file.
	$out  = array();

	// Shorten the key until something matches, so «prendrons» still offers prendre.
	$length = function_exists( 'mb_strlen' ) ? mb_strlen( $key ) : strlen( $key );

	for ( $cut = $length; $cut >= 2 && ! $out; $cut-- ) {
		$prefix = function_exists( 'mb_substr' ) ? mb_substr( $key, 0, $cut ) : substr( $key, 0, $cut );

		foreach ( (array) $rows as $row ) {
			$row_key = $persian ? zandi_dict_norm_fa( $row[0] ) : zandi_dict_norm_fr( $row[0] );

			if ( 0 === strpos( $row_key, $prefix ) && '' === $row[2] ) {
				$out[] = $row;

				if ( count( $out ) >= $limit ) {
					break;
				}
			}
		}
	}

	return $out;
}

/* =========================================================================
 * 4. The page's state
 * ====================================================================== */

/**
 * What this dictionary request is showing, worked out once.
 *
 * @return array{view:string,word?:string,page?:array,conj?:array,words?:string[],query?:string,from?:string}
 *               view is 'home', 'fr', 'fa', 'conj', 'choices', 'notfound'
 *               or 'unavailable'.
 */
function zandi_dictionary_state() {
	static $state = null;
	static $signature = null;

	/*
	 * Memoised per request, keyed by what the request asks, so a second call
	 * with the same address is free and a test that changes the address gets
	 * a fresh answer.
	 */
	$asked = wp_json_encode(
		array(
			zandi_dictionary_request_word(),
			zandi_dictionary_request_conj(),
			isset( $_GET['q'] ) ? (string) $_GET['q'] : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput -- Only compared, never used.
			isset( $_GET['from'] ) ? (string) $_GET['from'] : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput -- Only compared, never used.
		)
	);

	if ( null !== $state && $signature === $asked ) {
		return $state;
	}

	$signature = $asked;

	if ( ! zandi_dict_available() ) {
		$state = array( 'view' => 'unavailable' );

		return $state;
	}

	$word = zandi_dictionary_request_word();

	if ( '' === $word ) {
		$query = isset( $_GET['q'] ) ? zandi_dictionary_clean_query( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A read-only search.

		if ( '' === $query ) {
			$state = array( 'view' => 'home' );

			return $state;
		}

		$found = zandi_dict_resolve( $query );
		$state = 'choices' === $found['type']
			? array( 'view' => 'choices', 'words' => $found['words'], 'query' => $query )
			: array( 'view' => 'notfound', 'query' => $query );

		return $state;
	}

	if ( zandi_dict_is_persian( $word ) ) {
		$page  = zandi_dict_fa_page( $word );
		$state = $page
			? array( 'view' => 'fa', 'word' => (string) $page['w'], 'page' => $page )
			: array( 'view' => 'notfound', 'query' => $word );

		return $state;
	}

	$page = zandi_dict_fr_page( $word );

	if ( ! $page ) {
		$found = zandi_dict_resolve( $word );
		$state = 'choices' === $found['type']
			? array( 'view' => 'choices', 'words' => $found['words'], 'query' => $word )
			: array( 'view' => 'notfound', 'query' => $word );

		return $state;
	}

	if ( zandi_dictionary_request_conj() ) {
		$conj  = zandi_dict_conjugation( $word );
		$state = $conj
			? array( 'view' => 'conj', 'word' => $word, 'page' => $page, 'conj' => $conj )
			: array( 'view' => 'notfound', 'query' => $word );

		return $state;
	}

	$from  = isset( $_GET['from'] ) ? zandi_dictionary_clean_query( sanitize_text_field( wp_unslash( $_GET['from'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
	$state = array( 'view' => 'fr', 'word' => $word, 'page' => $page );

	// Only repeat a «from» that really is a form of this word.
	if ( '' !== $from && in_array( $word, zandi_dict_form_lemmas( $from ), true ) ) {
		$state['from'] = $from;
	}

	return $state;
}

/**
 * Sends a search, or a word typed in a form other than its own, to its page.
 *
 * A search box that answers on its own URL would give every typed word a page
 * of its own at /dictionary/?q=…, none canonical. So a search that finds one
 * word is a redirect to that word's page, and «Maison» or «eleve» typed into
 * the address bar is a 301 to «maison» and «élève». A form («prends») goes to
 * its word with ?from=prends so the page can say what happened.
 *
 * @return void
 */
function zandi_dictionary_redirect() {
	if ( ! zandi_is_dictionary() || ! zandi_dict_available() ) {
		return;
	}

	$word = zandi_dictionary_request_word();

	if ( '' === $word ) {
		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A read-only search.

		if ( '' === trim( $query ) ) {
			return;
		}

		$found = zandi_dict_resolve( $query );

		if ( in_array( $found['type'], array( 'fr', 'fa', 'form' ), true ) ) {
			$url = zandi_dictionary_url( $found['word'] );

			if ( 'form' === $found['type'] ) {
				$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'from=' . rawurlencode( $found['from'] );
			}

			wp_safe_redirect( $url, 302 );
			exit;
		}

		return;
	}

	if ( zandi_dict_is_persian( $word ) ) {
		$page = zandi_dict_fa_page( $word );

		if ( $page && $page['w'] !== $word ) {
			wp_safe_redirect( zandi_dictionary_url( (string) $page['w'] ), 301 );
			exit;
		}

		return;
	}

	if ( zandi_dict_fr_page( $word ) ) {
		return;
	}

	$found = zandi_dict_resolve( $word );

	if ( 'fr' === $found['type'] ) {
		wp_safe_redirect( zandi_dictionary_url( $found['word'], zandi_dictionary_request_conj() ), 301 );
		exit;
	}

	if ( 'form' === $found['type'] && ! zandi_dictionary_request_conj() ) {
		$url = zandi_dictionary_url( $found['word'] );
		wp_safe_redirect( $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'from=' . rawurlencode( $found['from'] ), 302 );
		exit;
	}
}
add_action( 'template_redirect', 'zandi_dictionary_redirect', 5 );

/**
 * A word the dictionary does not have answers 404, not 200.
 *
 * Runs after zandi_prepare_virtual_page() has set every virtual page to 200.
 * A «not found» page that answers 200 is a soft 404 to a search engine, and
 * thousands of mistyped words would each look like a thin page of the site.
 *
 * @return void
 */
function zandi_dictionary_status() {
	if ( ! zandi_is_dictionary() ) {
		return;
	}

	$state = zandi_dictionary_state();

	if ( 'notfound' === $state['view'] && '' !== zandi_dictionary_request_word() ) {
		status_header( 404 );
	}
}
add_action( 'wp', 'zandi_dictionary_status', 20 );

/* =========================================================================
 * 5. Copy
 * ====================================================================== */

/**
 * Every string the dictionary prints.
 *
 * @return array<string,mixed>
 */
function zandi_dictionary_copy() {
	return apply_filters(
		'zandi_dictionary_copy',
		array(
			'title'          => 'واژه‌نامه فرانسه به فارسی',
			'eyebrow'        => 'واژه‌نامه',
			'lead'           => 'معنی واژه‌های فرانسوی به فارسی، و برعکس — با تلفظ، مثال و صرف کامل فعل‌ها.',
			'meta'           => 'واژه‌نامه رایگان فرانسه به فارسی و فارسی به فرانسه آکادمی زندی، با تلفظ، جمله‌ی نمونه و صرف کامل افعال فرانسوی.',
			'search_label'   => 'یک واژه‌ی فرانسوی یا فارسی بنویس',
			'search_hint'    => 'فرانسوی یا فارسی، فرقی نمی‌کنه. شکل صرف‌شده هم پیدا می‌شه: «prends» تو رو به prendre می‌بره.',
			'search_button'  => 'جست‌وجو',
			'accents_label'  => 'حروف فرانسوی',
			'suggest_label'  => 'پیشنهادها',
			'common_title'   => 'واژه‌های پرکاربرد',
			'verbs_title'    => 'صرف افعال پرکاربرد',
			'recent_title'   => 'جست‌وجوهای اخیر',
			'recent_note'    => 'فقط روی همین مرورگر ذخیره می‌شه.',
			'meanings'       => 'معنی‌ها',
			'definitions'    => 'تعریف به فرانسوی',
			'examples'       => 'جمله‌های نمونه',
			'forms'          => 'شکل‌های دیگر',
			'plural'         => 'جمع',
			'feminine'       => 'مؤنث',
			'listen'         => 'شنیدن تلفظ',
			'present_title'  => 'صرف در زمان حال',
			'conj_link'      => 'صرف کامل در همه‌ی زمان‌ها',
			'conj_title'     => 'صرف فعل',
			'conj_more'      => 'زمان‌های دیگر',
			'aux'            => 'فعل کمکی',
			'pp'             => 'اسم مفعول',
			'ppr'            => 'اسم فاعل',
			'back_to_word'   => 'معنی این فعل',
			'from_note'      => '«%1$s» یکی از شکل‌های %2$s است.',
			'fa_lead'        => 'معادل‌های فرانسوی، پرکاربردترین اول.',
			'fa_also'        => 'به معنی',
			'choices_title'  => 'کدوم واژه؟',
			'choices_lead'   => 'چند واژه به «%s» می‌خوره:',
			'notfound_title' => 'این واژه رو پیدا نکردیم',
			'notfound_lead'  => '«%s» هنوز در این واژه‌نامه نیست. شاید یکی از این‌ها منظورت باشه:',
			'notfound_none'  => '«%s» هنوز در این واژه‌نامه نیست. املای واژه رو یک بار دیگه نگاه کن، یا شکل ساده‌ترش رو بنویس.',
			'unavailable'    => 'واژه‌نامه در حال به‌روزرسانیه. چند دقیقه‌ی دیگه دوباره سر بزن.',
			'stats'          => '%1$s واژه‌ی فرانسوی، %2$s واژه‌ی فارسی و %3$s فعل با صرف کامل.',
			'credit'         => 'منبع: معنی‌ها، تعریف‌ها، تلفظ و صرف افعال از <a href="https://fr.wiktionary.org/" rel="noopener">ویکی‌واژه</a> (مجوز <a href="https://creativecommons.org/licenses/by-sa/4.0/deed.fa" rel="license noopener">CC BY-SA</a>)؛ جمله‌های نمونه از <a href="https://tatoeba.org/" rel="noopener">Tatoeba</a> (مجوز <a href="https://creativecommons.org/licenses/by/2.0/fr/" rel="license noopener">CC BY 2.0 FR</a>). این داده‌ها با همان مجوز در دسترس‌اند.',
			'credit_note'    => 'معنی‌ها از ویکی‌واژه گرفته شده‌اند و ممکنه برای بعضی واژه‌ها کامل نباشند.',
		)
	);
}

/**
 * A part of speech in Persian, with the noun's gender when it is known.
 *
 * @param string $pos    Wiktionary part of speech.
 * @param string $gender 'm', 'f', 'mf' or ''.
 * @return string
 */
function zandi_dictionary_pos_label( $pos, $gender = '' ) {
	$labels = array(
		'noun'     => 'اسم',
		'verb'     => 'فعل',
		'adj'      => 'صفت',
		'adv'      => 'قید',
		'prep'     => 'حرف اضافه',
		'pron'     => 'ضمیر',
		'conj'     => 'حرف ربط',
		'intj'     => 'صوت',
		'phrase'   => 'عبارت',
		'det'      => 'صفت اشاره و ملکی',
		'article'  => 'حرف تعریف',
		'num'      => 'عدد',
		'particle' => 'ادات',
		'proverb'  => 'ضرب‌المثل',
	);
	$genders = array(
		'm'  => 'مذکر',
		'f'  => 'مؤنث',
		'mf' => 'مذکر و مؤنث',
	);

	$label = isset( $labels[ $pos ] ) ? $labels[ $pos ] : '';

	if ( $label && 'noun' === $pos && isset( $genders[ $gender ] ) ) {
		$label .= ' · ' . $genders[ $gender ];
	}

	return $label;
}

/**
 * The French article that shows a noun's gender: «la maison», «le livre».
 *
 * Returns '' when the gender is unknown or both, rather than guessing.
 *
 * @param string $word   Noun.
 * @param string $gender 'm' or 'f'.
 * @return string
 */
function zandi_dictionary_article( $word, $gender ) {
	if ( 'm' !== $gender && 'f' !== $gender ) {
		return '';
	}

	$first = zandi_dict_norm_fr( function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1 ) : substr( $word, 0, 1 ) );

	if ( in_array( $first, array( 'a', 'e', 'i', 'o', 'u', 'y' ), true ) ) {
		return 'l’'; // h muet or aspiré cannot be told from the spelling, so h gets le/la.
	}

	return 'm' === $gender ? 'le ' : 'la ';
}

/**
 * The tenses, in the order a learner meets them, with a line on each.
 *
 * The first seven are drawn open; the rest sit under «زمان‌های دیگر».
 *
 * @return array<string,array{fa:string,fr:string,use:string,main:bool}>
 */
function zandi_dictionary_tenses() {
	return apply_filters(
		'zandi_dictionary_tenses',
		array(
			'present'                     => array( 'fa' => 'حال ساده', 'fr' => 'Présent', 'use' => 'کاری که الان یا همیشه انجام می‌شه', 'main' => true ),
			'passe_compose'               => array( 'fa' => 'گذشته‌ی نقلی', 'fr' => 'Passé composé', 'use' => 'کاری که در گذشته انجام شد و تمام شد', 'main' => true ),
			'imparfait'                   => array( 'fa' => 'گذشته‌ی استمراری', 'fr' => 'Imparfait', 'use' => 'توصیف گذشته و عادت‌های گذشته', 'main' => true ),
			'futur'                       => array( 'fa' => 'آینده‌ی ساده', 'fr' => 'Futur simple', 'use' => 'کاری که بعداً انجام می‌شه', 'main' => true ),
			'conditionnel'                => array( 'fa' => 'شرطی', 'fr' => 'Conditionnel présent', 'use' => 'آرزو، پیشنهاد و درخواست مؤدبانه', 'main' => true ),
			'subjonctif'                  => array( 'fa' => 'التزامی', 'fr' => 'Subjonctif présent', 'use' => 'بعد از خواستن، لازم بودن و احساس‌ها', 'main' => true ),
			'imperatif'                   => array( 'fa' => 'امری', 'fr' => 'Impératif', 'use' => 'دستور دادن و خواهش کردن', 'main' => true ),
			'plus_que_parfait'            => array( 'fa' => 'گذشته‌ی بعید', 'fr' => 'Plus-que-parfait', 'use' => 'کاری که قبل از یک کار گذشته‌ی دیگه انجام شده بود', 'main' => false ),
			'futur_anterieur'             => array( 'fa' => 'آینده‌ی کامل', 'fr' => 'Futur antérieur', 'use' => 'کاری که تا یک زمان در آینده تمام شده', 'main' => false ),
			'conditionnel_passe'          => array( 'fa' => 'شرطی گذشته', 'fr' => 'Conditionnel passé', 'use' => 'کاری که می‌شد انجام بشه و نشد', 'main' => false ),
			'subjonctif_passe'            => array( 'fa' => 'التزامی گذشته', 'fr' => 'Subjonctif passé', 'use' => 'التزامی برای کاری که تمام شده', 'main' => false ),
			'passe_simple'                => array( 'fa' => 'گذشته‌ی ساده‌ی ادبی', 'fr' => 'Passé simple', 'use' => 'در نوشته‌ها و داستان‌ها، نه در گفت‌وگو', 'main' => false ),
			'passe_anterieur'             => array( 'fa' => 'گذشته‌ی پیشین', 'fr' => 'Passé antérieur', 'use' => 'ادبی', 'main' => false ),
			'subjonctif_imparfait'        => array( 'fa' => 'التزامی گذشته‌ی استمراری', 'fr' => 'Subjonctif imparfait', 'use' => 'ادبی', 'main' => false ),
			'subjonctif_plus_que_parfait' => array( 'fa' => 'التزامی گذشته‌ی بعید', 'fr' => 'Subjonctif plus-que-parfait', 'use' => 'ادبی', 'main' => false ),
			'imperatif_passe'             => array( 'fa' => 'امری گذشته', 'fr' => 'Impératif passé', 'use' => 'کمیاب', 'main' => false ),
		)
	);
}

/**
 * One conjugated form as markup: the pronoun muted, the verb set in weight.
 *
 * «j’» is joined to its verb with no space; every other pronoun takes one.
 *
 * @param string $pronoun Subject pronoun, '' for the imperative.
 * @param string $form    Verb form.
 * @return string Escaped HTML.
 */
function zandi_dictionary_conj_form( $pronoun, $form ) {
	$pronoun = (string) $pronoun;
	$joiner  = ( '' === $pronoun || '’' === substr( $pronoun, -3 ) ) ? '' : ' ';

	return ( '' !== $pronoun ? '<span class="dict-conj__pron">' . esc_html( $pronoun ) . '</span>' . $joiner : '' )
		. '<b class="dict-conj__form">' . esc_html( (string) $form ) . '</b>';
}

/* =========================================================================
 * 6. Head and title
 * ====================================================================== */

/**
 * The page's title, as the browser tab and a search result show it.
 *
 * @return string
 */
function zandi_dictionary_page_title() {
	$copy  = zandi_dictionary_copy();
	$state = zandi_dictionary_state();

	switch ( $state['view'] ) {
		case 'fr':
			return sprintf( 'معنی %s به فارسی', $state['word'] );
		case 'fa':
			return sprintf( '«%s» به فرانسوی', $state['word'] );
		case 'conj':
			return sprintf( 'صرف فعل %s', $state['word'] );
		case 'notfound':
		case 'choices':
			return $copy['notfound_title'];
		default:
			return $copy['title'];
	}
}

/**
 * Sets the browser title on every dictionary page.
 *
 * @param array $parts Title parts.
 * @return array
 */
function zandi_dictionary_title( $parts ) {
	if ( zandi_is_dictionary() ) {
		$parts['title'] = zandi_dictionary_page_title();
	}

	return $parts;
}
add_filter( 'document_title_parts', 'zandi_dictionary_title' );

/**
 * A one-line description for a word page, from its own meanings.
 *
 * @return string
 */
function zandi_dictionary_description() {
	$copy  = zandi_dictionary_copy();
	$state = zandi_dictionary_state();

	if ( 'fr' === $state['view'] || 'conj' === $state['view'] ) {
		$meanings = array();

		foreach ( $state['page']['b'] as $block ) {
			foreach ( $block['m'] as $group ) {
				foreach ( $group[0] as $fa ) {
					$meanings[] = $fa;
				}
			}
		}

		$meanings = implode( '، ', array_slice( array_unique( $meanings ), 0, 5 ) );

		return 'conj' === $state['view']
			? sprintf( 'صرف کامل فعل فرانسوی %1$s (%2$s) در همه‌ی زمان‌ها، با توضیح فارسی هر زمان.', $state['word'], $meanings )
			: sprintf( 'معنی %1$s به فارسی: %2$s — با تلفظ و جمله‌ی نمونه.', $state['word'], $meanings );
	}

	if ( 'fa' === $state['view'] ) {
		$words = array();

		foreach ( $state['page']['fr'] as $item ) {
			$words[] = $item[0];
		}

		return sprintf( '«%1$s» به فرانسوی: %2$s.', $state['word'], implode( '، ', array_slice( $words, 0, 6 ) ) );
	}

	return $copy['meta'];
}

/**
 * The address this page should be known by.
 *
 * @return string
 */
function zandi_dictionary_canonical() {
	$state = zandi_dictionary_state();

	if ( in_array( $state['view'], array( 'fr', 'fa' ), true ) ) {
		return zandi_dictionary_url( $state['word'] );
	}

	if ( 'conj' === $state['view'] ) {
		return zandi_dictionary_url( $state['word'], true );
	}

	return zandi_dictionary_url();
}

/**
 * Head tags for the dictionary.
 *
 * The robots tag is printed whether or not an SEO plugin is installed — the
 * same rule as /placement/ and /podcast/ — because a search result or a
 * misspelling must never be indexed, and the dictionary as a whole is noindex
 * until the owner launches it.
 *
 * @return void
 */
function zandi_dictionary_head() {
	if ( ! zandi_is_dictionary() ) {
		return;
	}

	$state = zandi_dictionary_state();

	if ( zandi_dictionary_noindex() || in_array( $state['view'], array( 'notfound', 'choices', 'unavailable' ), true ) ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}

	if ( function_exists( 'zandi_seo_plugin_active' ) && zandi_seo_plugin_active() ) {
		return;
	}

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( zandi_dictionary_description() ) );
	printf( '<link rel="canonical" href="%s">' . "\n", esc_url( zandi_dictionary_canonical() ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( zandi_dictionary_page_title() ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( zandi_dictionary_description() ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( zandi_dictionary_canonical() ) );
}
add_action( 'wp_head', 'zandi_dictionary_head', 3 );

/* =========================================================================
 * 7. Template and assets
 * ====================================================================== */

/**
 * Routes every dictionary address to its one template.
 *
 * @param string $template Template path chosen by WordPress.
 * @return string
 */
function zandi_dictionary_template( $template ) {
	if ( ! zandi_is_dictionary() ) {
		return $template;
	}

	return get_theme_file_path( 'template-dictionary.php' );
}
add_filter( 'template_include', 'zandi_dictionary_template' );

/**
 * The dictionary's stylesheet and script, on dictionary pages and nowhere else.
 *
 * @return void
 */
function zandi_dictionary_assets() {
	if ( ! zandi_is_dictionary() ) {
		return;
	}

	wp_enqueue_style(
		'zandi-dictionary',
		get_theme_file_uri( 'assets/css/dictionary.css' ),
		array( 'zandi-style', 'zandi-rtl' ),
		zandi_asset_version( 'assets/css/dictionary.css' )
	);

	wp_enqueue_script(
		'zandi-dictionary',
		get_theme_file_uri( 'assets/js/dictionary.js' ),
		array(),
		zandi_asset_version( 'assets/js/dictionary.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'zandi_dictionary_assets', 20 );

/**
 * The version string the browser appends to suggestion files.
 *
 * meta.json is rewritten by every build, so its mtime moves exactly when the
 * suggestion files do — the same mtime rule as every other asset here.
 *
 * @return string
 */
function zandi_dictionary_data_version() {
	return (string) zandi_asset_version( 'inc/data/dictionary/meta.json' );
}
