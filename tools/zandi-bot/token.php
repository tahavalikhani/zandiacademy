<?php
/**
 * The connect token, verified.
 *
 * This is one half of a contract with code on another continent. The website
 * signs a token with ZANDI_BOT_SECRET and hands it to a student as a deep link;
 * this bot reads it with the same key and learns which WordPress account tapped
 * it. Nothing calls anything: the site refuses requests from datacentre
 * addresses and this bot lives in one, so the signature has to travel WITH the
 * token rather than being checked by asking.
 *
 * It lives in its own file, free of side effects, for one reason: the theme's
 * test harness requires it directly and runs it against tokens minted by
 * zandi_podcast_bind_token(). If the two ever drift apart the failure is
 * invisible from either side — the student taps, nothing happens, and nobody
 * can see why — so it is proved on every test run instead.
 *
 * FORMAT IS DICTATED BY TELEGRAM: `<user>-<expires>-<32 hex>`. A deep-link
 * start parameter accepts at most 64 characters from A-Z a-z 0-9 _ - , so a
 * dotted payload or base64 is silently dropped rather than rejected.
 *
 * @package Zandi
 */

/**
 * Reads a connect token and returns the WordPress user it names.
 *
 * hash_equals() rather than ===, because a token is a credential and a timing
 * difference is a slow way of guessing one.
 *
 * @param string $token  Token from the deep link.
 * @param string $secret Shared key.
 * @return int User ID, or 0 when the token is malformed, forged or expired.
 */
function zandi_bot_read_token( $token, $secret ) {
	if ( '' === (string) $secret || ! preg_match( '/^(\d+)-(\d+)-([0-9a-f]{32})$/', (string) $token, $m ) ) {
		return 0;
	}

	$payload = $m[1] . '-' . $m[2];

	if ( ! hash_equals( substr( hash_hmac( 'sha256', $payload, (string) $secret ), 0, 32 ), $m[3] ) ) {
		return 0;
	}

	/*
	 * An expired token is refused even though its signature is perfect.
	 * Otherwise a link out of a six-month-old screenshot still opens the group.
	 */
	return (int) $m[2] > time() ? (int) $m[1] : 0;
}
