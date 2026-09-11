<?php
/**
 * پادکست Bonjour Monjour — selling it, and remembering who paid.
 *
 * WHAT THIS FILE OWNS, AND WHAT IT DELIBERATELY DOES NOT
 *
 * It owns the /podcast/ route, the copy, the link between a WooCommerce product
 * and a number of days, and the answer to one question: until when is this
 * student allowed inside the Telegram group? It does not own the group. A bot
 * on a German host does that, because an Iranian server cannot reach
 * api.telegram.org — measured on 11 September 2026, cURL error 7, connection
 * refused. This file tells the bot; the bot acts.
 *
 * WHY THE SITE PUSHES AND IS NEVER ASKED
 *
 * The obvious design is the other way round: the bot receives a join request and
 * asks the site whether that person has paid. It cannot. zandiacademy.com
 * answers 503 to requests from datacentre addresses — measured three times, and
 * the bot's host is a datacentre. The site can reach the bot, though (404 in
 * 1.5s, which is the bot's deliberate answer to a request with no key), so every
 * fact travels outward from here and the bot keeps its own copy.
 *
 * That has a consequence worth stating plainly: the bot enforces from a copy, so
 * it keeps removing expired members even while the site is unreachable, and the
 * site keeps selling even while the bot is down. Neither can take the other out.
 *
 * WHY EXPIRY IS DERIVED AND NOT STORED
 *
 * `zandi_podcast_expires` is a mirror, like `zandi_course_owned` before it — the
 * orders are the record. Walking them in date order reproduces the expiry
 * exactly, because each renewal extends from whichever is later, the moment it
 * was paid or the date already owed:
 *
 *     expiry = 0
 *     for each paid order, oldest first:
 *         expiry = max( paid_at, expiry ) + days
 *
 * So a refund recomputes correctly, a replayed sync is a no-op, and nothing
 * drifts. The one thing orders cannot express is a subscription that predates
 * the website — the ~70 people already in the group from the AradBot era — so
 * those get a manual floor in a second meta key, and the effective date is
 * whichever of the two is later. The floor is additional, never a replacement:
 * it can extend a date, never shorten one somebody paid for.
 *
 * SECRETS
 *
 * The shared key lives in wp-config.php as ZANDI_BOT_SECRET and never in this
 * repository. Without it the bridge is inert and says so in wp-admin rather than
 * failing quietly.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. The route
 *
 * Unlinked on purpose, exactly like /placement/ was: no menu item, no footer
 * column, noindex, and the owner tests it at the URL before it is wired to
 * anything. zandi_podcast_noindex() is the single line that opens it.
 * ====================================================================== */

/**
 * The slug the podcast page answers on.
 *
 * @return string
 */
function zandi_podcast_slug() {
	return (string) apply_filters( 'zandi_podcast_slug', 'podcast' );
}

/**
 * Whether the current request is the podcast page.
 *
 * @return bool
 */
function zandi_is_podcast() {
	return (bool) get_query_var( 'zandi_podcast' );
}

/**
 * The canonical URL of the podcast page.
 *
 * Falls back to a query string when permalinks are «ساده», for the same reason
 * every other route helper here does: without it the link 404s at the web
 * server before PHP has a chance to answer.
 *
 * @return string
 */
function zandi_podcast_url() {
	return zandi_pretty_permalinks()
		? home_url( '/' . zandi_podcast_slug() . '/' )
		: home_url( '/?zandi_podcast=1' );
}

/**
 * Whether the page is still hidden from search engines.
 *
 * True while the page is under review and unlinked. Flip this one line — and
 * add the menu item — when it is ready to be announced. Nothing else changes.
 *
 * @return bool
 */
function zandi_podcast_noindex() {
	return (bool) apply_filters( 'zandi_podcast_noindex', true );
}

/* =========================================================================
 * 2. The product
 *
 * A plan is a WooCommerce product carrying a number of days. The number is the
 * link, not the title and not the SKU: a title is edited for marketing reasons
 * and a SKU can be cleared in one click, and either would silently change what
 * somebody's money buys.
 * ====================================================================== */

/**
 * The post meta key holding a product's days of access.
 *
 * @return string
 */
function zandi_podcast_days_meta_key() {
	return (string) apply_filters( 'zandi_podcast_days_meta_key', '_zandi_podcast_days' );
}

/**
 * How many days of podcast access a product grants, or 0 if it is not one.
 *
 * @param WC_Product|int $product Product or ID.
 * @return int
 */
function zandi_podcast_product_days( $product ) {
	$id = is_object( $product ) && method_exists( $product, 'get_id' ) ? (int) $product->get_id() : (int) $product;

	if ( ! $id ) {
		return 0;
	}

	return max( 0, (int) get_post_meta( $id, zandi_podcast_days_meta_key(), true ) );
}

/**
 * The plans as they are quoted, for a site with no products wired up yet.
 *
 * These are the prices the owner confirmed in writing on 11 September 2026, in
 * تومان. They are the fallback only: once a product carries a price, the
 * product wins, because the shop is what actually charges the card and a page
 * quoting a different number from the checkout is worse than no page.
 *
 * There is no 12-month plan because the owner has not set one. Do not invent a
 * price for it.
 *
 * THE SIX-MONTH NOTE IS NOT «کمترین هزینه برای هر ماه», and that is arithmetic
 * rather than wording. At these prices six months works out at ۳۳۱٬۶۶۷ a month
 * and three months at ۳۳۰٬۰۰۰ — the three-month plan is the cheaper one per
 * month, by a little. The page carried the claim anyway until 11 September
 * 2026. What IS true of six months is the largest total saving against buying
 * monthly (۳٬۵۴۰٬۰۰۰ − ۱٬۹۹۰٬۰۰۰), so that is what it says now. If the prices
 * change, check the claim again before trusting it; this is also why the cards
 * print no «per month» column.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_podcast_plans() {
	return apply_filters(
		'zandi_podcast_plans',
		array(
			array(
				'key'         => 'm1',
				'label'       => 'یک ماهه',
				'days'        => 30,
				'price_toman' => 590000,
				'note'        => '',
			),
			/*
			 * `featured` marks the one card the page lifts, and this is the
			 * plan that earns it on the arithmetic rather than on a hunch: at
			 * these prices three months works out at ۳۳۰٬۰۰۰ a month against
			 * ۵۹۰٬۰۰۰ for one and ۳۳۱٬۶۶۷ for six, so it really is the lowest
			 * monthly cost on offer. The tag says only that. If the prices
			 * change, check which plan it belongs on before moving it.
			 */
			array(
				'key'         => 'm3',
				'label'       => 'سه ماهه',
				'days'        => 90,
				'price_toman' => 990000,
				'note'        => 'از سه بار خرید ماهانه به‌صرفه‌تره',
				'featured'    => true,
			),
			array(
				'key'         => 'm6',
				'label'       => 'شش ماهه',
				'days'        => 180,
				'price_toman' => 1990000,
				'note'        => 'بیشترین صرفه‌جویی نسبت به خرید ماهانه',
			),
		)
	);
}

/**
 * The product that sells a given number of days, if one exists.
 *
 * Memoised per request: the plan list is rendered twice on the page and once
 * more in the panel, and this would otherwise be a meta query each time.
 *
 * @param int $days Days of access.
 * @return int Product ID, or 0.
 */
function zandi_podcast_product_for_days( $days ) {
	static $map = null;

	if ( null === $map ) {
		$map = array();

		if ( function_exists( 'wc_get_products' ) ) {
			/*
			 * meta_query with EXISTS, not a bare meta_key. WC_Product_Query
			 * does not treat a lone meta_key as a filter, so the query came
			 * back with the first twenty products in the shop regardless of
			 * whether they were podcast plans — harmless while the shop is
			 * small, and quietly wrong the moment there are more than twenty
			 * products and the plans are not among the first of them.
			 */
			$products = wc_get_products(
				array(
					'status'     => 'publish',
					'limit'      => 20,
					'return'     => 'objects',
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded and memoised.
						array(
							'key'     => zandi_podcast_days_meta_key(),
							'compare' => 'EXISTS',
						),
					),
				)
			);

			foreach ( (array) $products as $product ) {
				$product_days = zandi_podcast_product_days( $product );

				if ( $product_days ) {
					$map[ $product_days ] = (int) $product->get_id();
				}
			}
		}
	}

	return isset( $map[ (int) $days ] ) ? (int) $map[ (int) $days ] : 0;
}

/**
 * A plan's price, preferring what the shop will actually charge.
 *
 * @param array<string,mixed> $plan One row from zandi_podcast_plans().
 * @return int Price in تومان.
 */
function zandi_podcast_plan_price( $plan ) {
	$product_id = zandi_podcast_product_for_days( $plan['days'] );

	if ( $product_id && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $product_id );

		if ( $product && '' !== $product->get_price() ) {
			return (int) round( (float) $product->get_price() );
		}
	}

	return (int) $plan['price_toman'];
}

/**
 * Where a plan's button goes.
 *
 * STRAIGHT TO CHECKOUT, NOT TO THE CART. A course button has always taken the
 * student to payment in one step, and landing on a basket instead is a screen
 * that asks «are you sure?» after they have already decided — the commonest
 * place a digital sale is lost.
 *
 * The trick is that `add-to-cart` is a query argument WooCommerce honours on
 * ANY front-end request, not only on the cart page. Pointing it at the checkout
 * URL adds the product and renders payment in the same load. That keeps the
 * markup a plain link, so the plan cards need no form and no nonce, and the
 * page's own templates do not have to know how the shop works.
 *
 * No product means the contact page: a button that leads nowhere is worse than
 * an honest «بپرس».
 *
 * @param array<string,mixed> $plan One row from zandi_podcast_plans().
 * @return string
 */
function zandi_podcast_plan_url( $plan ) {
	$product_id = zandi_podcast_product_for_days( $plan['days'] );

	if ( $product_id && function_exists( 'wc_get_checkout_url' ) ) {
		return add_query_arg( 'add-to-cart', $product_id, wc_get_checkout_url() );
	}

	return zandi_support_url();
}

/**
 * One plan in the basket, never two, and never two of one.
 *
 * A GET `add-to-cart` does not clear what is already there, so clicking three
 * plans in turn would arrive at checkout asking for all three, and clicking one
 * twice would ask for sixty days at double the price. Both are support tickets
 * rather than sales.
 *
 * The course flow solves this by emptying the cart inside its own POST handler.
 * There is no handler here — the button is a link, deliberately — so the
 * tidying happens after WooCommerce has added the item instead. Same outcome,
 * and the plan cards stay plain markup.
 *
 * Anything already in the basket goes too. That is the site's established
 * behaviour: zandi_woo_handle_enrol() empties the cart before adding a course,
 * because one thing at a time is how this shop sells.
 *
 * @param string $cart_key   Key of the item just added.
 * @param int    $product_id Product added.
 * @return void
 */
function zandi_podcast_solo_cart( $cart_key, $product_id ) {
	if ( ! zandi_podcast_product_days( $product_id ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	foreach ( array_keys( WC()->cart->get_cart() ) as $key ) {
		if ( $key !== $cart_key ) {
			WC()->cart->remove_cart_item( $key );
		}
	}

	WC()->cart->set_quantity( $cart_key, 1, false );
}
add_action( 'woocommerce_add_to_cart', 'zandi_podcast_solo_cart', 20, 2 );

/**
 * Whether any plan can actually be bought right now.
 *
 * @return bool
 */
function zandi_podcast_purchasable() {
	foreach ( zandi_podcast_plans() as $plan ) {
		if ( zandi_podcast_product_for_days( $plan['days'] ) ) {
			return true;
		}
	}

	return false;
}

/* =========================================================================
 * 3. Entitlement
 * ====================================================================== */

/**
 * The user meta key mirroring the expiry date.
 *
 * @return string
 */
function zandi_podcast_expires_meta_key() {
	return 'zandi_podcast_expires';
}

/**
 * The user meta key holding a hand-entered floor under the expiry date.
 *
 * For the members who were already in the group before the site sold anything.
 * It can only ever extend an expiry, never cut one short — see
 * zandi_podcast_compute_expiry().
 *
 * @return string
 */
function zandi_podcast_manual_meta_key() {
	return 'zandi_podcast_manual_until';
}

/**
 * How long after expiry somebody is still let in.
 *
 * A day, at the owner's choice. It costs nothing — the sweep compares one
 * number — and it turns «I was thrown out» into «I was reminded», which is the
 * difference between a renewal and a complaint.
 *
 * @return int Seconds.
 */
function zandi_podcast_grace() {
	return (int) apply_filters( 'zandi_podcast_grace', DAY_IN_SECONDS );
}

/**
 * The stacking rule, on its own so it can be proved.
 *
 * Each grant extends from whichever is later: the moment it was paid, or the
 * date already owed. That single line is what makes early renewal add to a
 * subscription instead of burning the remainder of it, and it is the piece most
 * worth having a test for — the failure mode is silently short-changing
 * somebody who renewed early, which nobody notices until they complain.
 *
 * @param array<int,array{paid_at:int,days:int}> $grants Oldest first.
 * @return int Unix timestamp, 0 for none.
 */
function zandi_podcast_stack( $grants ) {
	$expiry = 0;

	foreach ( (array) $grants as $grant ) {
		$days = max( 0, (int) ( $grant['days'] ?? 0 ) );

		if ( ! $days ) {
			continue;
		}

		$start  = max( (int) ( $grant['paid_at'] ?? 0 ), $expiry );
		$expiry = $start + ( $days * DAY_IN_SECONDS );
	}

	return $expiry;
}

/**
 * Works out when a student's access runs out, from the orders themselves.
 *
 * Walks every paid order oldest first, extending from whichever is later: the
 * moment that order was paid, or the date already owed. That is what makes
 * early renewal stack instead of burn, and what makes this safe to run again
 * and again — the answer only changes when the orders do.
 *
 * @param int $user_id Student.
 * @return int Unix timestamp, or 0 for somebody who has never had access.
 */
function zandi_podcast_compute_expiry( $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return 0;
	}

	$grants = array();

	if ( function_exists( 'wc_get_orders' ) && function_exists( 'zandi_woo_paid_statuses' ) ) {
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => zandi_woo_paid_statuses(),
				'limit'       => -1,
				'orderby'     => 'date',
				'order'       => 'ASC',
			)
		);

		foreach ( (array) $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$paid_at = $order->get_date_paid() ? $order->get_date_paid() : $order->get_date_created();
			$paid_at = $paid_at ? (int) $paid_at->getTimestamp() : 0;

			foreach ( $order->get_items() as $item ) {
				$days = zandi_podcast_product_days( (int) $item->get_product_id() );

				if ( ! $days ) {
					continue;
				}

				/*
				 * Quantity counts. Somebody who buys two six-month plans in one
				 * order has paid for a year, and silently giving them six months
				 * would be taking their money for nothing.
				 */
				$days *= max( 1, (int) $item->get_quantity() );

				$grants[] = array( 'paid_at' => $paid_at, 'days' => $days );
			}
		}
	}

	$manual = (int) get_user_meta( $user_id, zandi_podcast_manual_meta_key(), true );

	return max( zandi_podcast_stack( $grants ), $manual );
}

/**
 * Rebuilds the mirror, and tells the bot if anything moved.
 *
 * Rebuilt wholesale rather than patched, for the same reason
 * zandi_sync_owned_courses() is: a mirror that is only ever recomputed from the
 * record cannot drift into a state nobody can explain.
 *
 * @param int  $user_id Student.
 * @param bool $push    Whether to notify the bot. False while bulk-importing.
 * @return int The expiry it settled on.
 */
function zandi_podcast_sync( $user_id, $push = true ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return 0;
	}

	$expiry = zandi_podcast_compute_expiry( $user_id );
	$stored = (int) get_user_meta( $user_id, zandi_podcast_expires_meta_key(), true );

	if ( $expiry === $stored ) {
		return $expiry;
	}

	if ( $expiry ) {
		update_user_meta( $user_id, zandi_podcast_expires_meta_key(), $expiry );
	} else {
		delete_user_meta( $user_id, zandi_podcast_expires_meta_key() );
	}

	/**
	 * Fires when a student's podcast access date changes.
	 *
	 * @param int $user_id Student.
	 * @param int $expiry  New expiry, 0 for none.
	 * @param int $stored  What it was before.
	 */
	do_action( 'zandi_podcast_access_changed', $user_id, $expiry, $stored );

	if ( $push ) {
		zandi_podcast_push( $user_id );
	}

	return $expiry;
}

/**
 * Rebuilds the mirror whenever an order's status moves.
 *
 * Every transition, not only the ones into a paid status: a refund has to take
 * the days away as surely as the payment granted them.
 *
 * @param int           $order_id Order ID.
 * @param string        $from     Old status.
 * @param string        $to       New status.
 * @param WC_Order|null $order    Order, when the hook passes one.
 * @return void
 */
function zandi_podcast_sync_on_order( $order_id, $from = '', $to = '', $order = null ) {
	if ( ! $order instanceof WC_Order && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
	}

	if ( ! $order instanceof WC_Order ) {
		return;
	}

	zandi_podcast_sync( $order->get_customer_id() );
}
add_action( 'woocommerce_order_status_changed', 'zandi_podcast_sync_on_order', 20, 4 );

/**
 * When a student's access runs out.
 *
 * Reads the mirror, and computes live if there is none — so a student looking
 * at their own page always sees the truth even if a sync was missed.
 *
 * @param int $user_id Student.
 * @return int Unix timestamp, 0 for none.
 */
function zandi_podcast_expires( $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return 0;
	}

	$stored = get_user_meta( $user_id, zandi_podcast_expires_meta_key(), true );

	if ( '' === $stored || null === $stored ) {
		return zandi_podcast_compute_expiry( $user_id );
	}

	return (int) $stored;
}

/**
 * Where a student stands: 'none', 'active', 'grace' or 'expired'.
 *
 * @param int $user_id Student.
 * @return string
 */
function zandi_podcast_state( $user_id ) {
	$expiry = zandi_podcast_expires( $user_id );

	if ( ! $expiry ) {
		return 'none';
	}

	$now = time();

	if ( $expiry > $now ) {
		return 'active';
	}

	return ( $expiry + zandi_podcast_grace() ) > $now ? 'grace' : 'expired';
}

/**
 * Whether the bot should let this person stay in the group.
 *
 * Grace counts as inside. This is the single sentence the whole feature turns
 * on, and it is deliberately one function so the site and the bot can never
 * disagree about what "paid up" means.
 *
 * @param int $user_id Student.
 * @return bool
 */
function zandi_podcast_has_access( $user_id ) {
	return in_array( zandi_podcast_state( $user_id ), array( 'active', 'grace' ), true );
}

/**
 * Whole days left, for the panel.
 *
 * Rounded up, because somebody with eleven hours left has a day left in every
 * sense that matters to them.
 *
 * @param int $user_id Student.
 * @return int
 */
function zandi_podcast_days_left( $user_id ) {
	$expiry = zandi_podcast_expires( $user_id );

	if ( ! $expiry || $expiry <= time() ) {
		return 0;
	}

	return (int) ceil( ( $expiry - time() ) / DAY_IN_SECONDS );
}

/* =========================================================================
 * 4. Telegram identity
 *
 * A bot cannot look somebody up by phone number — there is no such API, at any
 * price. So the student has to introduce themselves exactly once, by tapping a
 * link that carries a token the bot can verify. That single fact is why this
 * section exists and why every student does one «اتصال تلگرام» step.
 * ====================================================================== */

/**
 * The user meta key holding a student's Telegram id.
 *
 * Written by the bot's bind call, never by the student.
 *
 * @return string
 */
function zandi_podcast_telegram_meta_key() {
	return 'zandi_telegram_id';
}

/**
 * A student's Telegram id, or 0 if they have never connected.
 *
 * @param int $user_id Student.
 * @return int
 */
function zandi_podcast_telegram_id( $user_id ) {
	return (int) get_user_meta( (int) $user_id, zandi_podcast_telegram_meta_key(), true );
}

/**
 * The key shared with the bot, from wp-config.php.
 *
 * Never in this repository. Define it as ZANDI_BOT_SECRET, matching the
 * 'bridge_secret' in the bot's own config.php.
 *
 * @return string
 */
function zandi_podcast_secret() {
	return defined( 'ZANDI_BOT_SECRET' ) ? (string) ZANDI_BOT_SECRET : '';
}

/**
 * The bot's address, from wp-config.php.
 *
 * @return string
 */
function zandi_podcast_bot_url() {
	return defined( 'ZANDI_BOT_URL' ) ? untrailingslashit( (string) ZANDI_BOT_URL ) : '';
}

/**
 * Whether the site can talk to the bot at all.
 *
 * @return bool
 */
function zandi_podcast_bridge_ready() {
	return '' !== zandi_podcast_secret() && '' !== zandi_podcast_bot_url();
}

/**
 * A signed token proving "this Telegram account belongs to this student".
 *
 * FORMAT IS CONSTRAINED BY TELEGRAM, NOT BY TASTE. A deep-link start parameter
 * may hold at most 64 characters and only A-Z a-z 0-9 underscore and hyphen —
 * no dots, no equals, so neither a dotted payload nor base64 survives. Hence
 * `<user>-<expires>-<32 hex>`, which is 48 characters for a five-digit user id
 * and is made only of permitted characters.
 *
 * The bot verifies it with the same key rather than asking the site, because
 * the site refuses requests from datacentre addresses and the bot lives in one.
 *
 * @param int $user_id Student.
 * @return string Empty when the bridge is not configured.
 */
function zandi_podcast_bind_token( $user_id ) {
	$user_id = (int) $user_id;
	$secret  = zandi_podcast_secret();

	if ( ! $user_id || '' === $secret ) {
		return '';
	}

	$expires = time() + (int) apply_filters( 'zandi_podcast_bind_ttl', 30 * MINUTE_IN_SECONDS );
	$payload = $user_id . '-' . $expires;

	return $payload . '-' . substr( hash_hmac( 'sha256', $payload, $secret ), 0, 32 );
}

/**
 * Checks a token and returns the student it names.
 *
 * Kept here so the test harness can prove the bot's copy of this logic agrees
 * with the site's. hash_equals() rather than ===, because a token is a
 * credential and a timing difference is a slow way of guessing one.
 *
 * @param string $token Token.
 * @return int User ID, or 0.
 */
function zandi_podcast_read_bind_token( $token ) {
	$secret = zandi_podcast_secret();

	if ( '' === $secret || ! preg_match( '/^(\d+)-(\d+)-([0-9a-f]{32})$/', (string) $token, $m ) ) {
		return 0;
	}

	$payload = $m[1] . '-' . $m[2];

	if ( ! hash_equals( substr( hash_hmac( 'sha256', $payload, $secret ), 0, 32 ), $m[3] ) ) {
		return 0;
	}

	return (int) $m[2] > time() ? (int) $m[1] : 0;
}

/**
 * The link that connects a student's Telegram account to their account here.
 *
 * @param int $user_id Student.
 * @return string Empty when the bridge is not configured.
 */
function zandi_podcast_connect_url( $user_id ) {
	$token = zandi_podcast_bind_token( $user_id );
	$bot   = (string) apply_filters( 'zandi_podcast_bot_username', 'bonjourmonjour_bot' );

	return $token ? 'https://t.me/' . rawurlencode( $bot ) . '?start=' . $token : '';
}

/* =========================================================================
 * 5. The bridge
 * ====================================================================== */

/**
 * Tells the bot what this student is owed.
 *
 * KEYED ON THE WordPress USER ID, NOT ON THE TELEGRAM ID, and that is the whole
 * reason this works at all.
 *
 * The obvious version sends the Telegram id — but the site never learns one.
 * A student introduces themselves to the BOT by tapping a signed deep link, so
 * it is the bot that ends up holding the pair, and the bot cannot tell us,
 * because zandiacademy.com refuses requests from datacentre addresses and the
 * bot lives in one. Keying on the Telegram id meant this function returned
 * false for every student who had not connected yet, and then never ran again
 * when they did — access paid for and silently never granted.
 *
 * So the site sends `user_id → expires` and the bot already holds
 * `user_id → telegram_id` from the bind. Each side knows one half and neither
 * has to ask the other, which is the only arrangement the network allows.
 *
 * Outbound only, and non-blocking: `blocking => false` means checkout does not
 * wait on a server in Germany to answer. If the request is lost, the nightly
 * full sync repairs it — which is why there is a nightly full sync.
 *
 * The body is signed rather than merely sent over HTTPS, so the bot can tell a
 * genuine update from anyone who found the URL.
 *
 * @param int $user_id Student.
 * @return bool Whether the request was dispatched.
 */
function zandi_podcast_push( $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id || ! zandi_podcast_bridge_ready() ) {
		return false;
	}

	$body = wp_json_encode(
		array(
			'user_id' => $user_id,
			'expires' => zandi_podcast_expires( $user_id ),
			'grace'   => zandi_podcast_grace(),
			'sent_at' => time(),
		)
	);

	$response = wp_remote_post(
		zandi_podcast_bot_url() . '/?sync=1',
		array(
			'timeout'  => 8,
			'blocking' => false,
			'headers'  => array(
				'Content-Type'   => 'application/json',
				'X-Zandi-Signature' => hash_hmac( 'sha256', (string) $body, zandi_podcast_secret() ),
			),
			'body'     => $body,
		)
	);

	return ! is_wp_error( $response );
}

/* =========================================================================
 * 6. WooCommerce wiring
 * ====================================================================== */

/**
 * A subscription must be re-purchasable, and a course must not be.
 *
 * zandi_woo_block_repurchase() stops anyone buying a product they already own,
 * which is right for a course and fatal for a renewal — the student whose
 * access is about to lapse would find the button gone at exactly the moment
 * they wanted it. This runs after it and puts podcast products back.
 *
 * @param bool       $purchasable Whether the product can be bought.
 * @param WC_Product $product     Product.
 * @return bool
 */
function zandi_podcast_allow_renewal( $purchasable, $product ) {
	return zandi_podcast_product_days( $product ) ? true : $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'zandi_podcast_allow_renewal', 20, 2 );

/**
 * The «روز دسترسی» field on the product editor's عمومی tab.
 *
 * A plain number, because that is what the entitlement is. Leaving it empty
 * means the product is not a podcast plan at all, which is the correct default
 * for every other product in the shop.
 *
 * @return void
 */
function zandi_podcast_product_field() {
	if ( ! function_exists( 'woocommerce_wp_text_input' ) ) {
		return;
	}

	woocommerce_wp_text_input(
		array(
			'id'                => zandi_podcast_days_meta_key(),
			'label'             => 'روز دسترسی پادکست',
			'description'       => 'چند روز دسترسی به گروه پادکست می‌دهد؟ مثلاً ۳۰. خالی یعنی این محصول پادکست نیست.',
			'desc_tip'          => true,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
		)
	);
}
add_action( 'woocommerce_product_options_general_product_data', 'zandi_podcast_product_field' );

/**
 * Saves it.
 *
 * @param int $product_id Product ID.
 * @return void
 */
function zandi_podcast_save_product_field( $product_id ) {
	$key = zandi_podcast_days_meta_key();

	// Nonce is checked by WooCommerce before this hook fires.
	$days = isset( $_POST[ $key ] ) ? (int) wp_unslash( $_POST[ $key ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( $days > 0 ) {
		update_post_meta( $product_id, $key, $days );
	} else {
		delete_post_meta( $product_id, $key );
	}
}
add_action( 'woocommerce_process_product_meta', 'zandi_podcast_save_product_field' );

/* =========================================================================
 * 7. Copy
 *
 * Every Persian string on the page, behind a filter, the way inc/content.php
 * does it. Nothing below is written into a template.
 * ====================================================================== */

/**
 * The podcast's own facts.
 *
 * Supplied by the owner on 10 September 2026. Facts only: there is no rating,
 * no student count and no testimonial here, because none was given.
 *
 * `icon` names a glyph in the registry in inc/icons.php and is OPTIONAL: a row
 * without one renders as text, so a filter that adds a fifth fact does not have
 * to know about icons to work.
 *
 * @return array<string,mixed>
 */
function zandi_podcast_facts() {
	return apply_filters(
		'zandi_podcast_facts',
		array(
			array( 'icon' => 'layers', 'label' => 'قسمت', 'value' => '۱۰۰', 'note' => 'حدود ۱۵ دقیقه' ),
			array( 'icon' => 'clock', 'label' => 'مجموع', 'value' => '+۱۶ ساعت', 'note' => 'آموزش' ),
			array( 'icon' => 'clipboard', 'label' => 'متن کامل', 'value' => 'داره', 'note' => 'کلمه‌ها، فعل‌ها و جمله‌ها' ),
			array( 'icon' => 'user', 'label' => 'مدرس', 'value' => 'خانم پوران', 'note' => '' ),
		)
	);
}

/**
 * The cover, from the Media Library first and the theme second.
 *
 * Media Library first so it can be swapped from wp-admin without a deploy;
 * the theme file second so the page is never coverless on a fresh install.
 * Same order, and the same reasoning, as zandi_course_video_poster().
 *
 * @return string URL, or '' when there is no cover anywhere.
 */
function zandi_podcast_cover() {
	$uploaded = zandi_media( 'podcast-cover' );

	if ( ! empty( $uploaded['url'] ) ) {
		return (string) $uploaded['url'];
	}

	$path = 'assets/images/podcast-cover.webp';

	return file_exists( get_theme_file_path( $path ) ) ? get_theme_file_uri( $path ) : '';
}

/**
 * The cheapest plan, for the line beside the hero button.
 *
 * A button reading «خرید اشتراک» with no number beside it sends people to the
 * bottom of the page to find out what it costs, and some of them simply leave
 * instead. Five words answers it without moving the decision up the page.
 *
 * @return int تومان.
 */
function zandi_podcast_starting_price() {
	$prices = array_map( 'zandi_podcast_plan_price', zandi_podcast_plans() );

	return $prices ? (int) min( $prices ) : 0;
}

/**
 * The free episodes.
 *
 * THE ONLY PART OF A SIXTEEN-HOUR PRODUCT GOOGLE CAN EVER SEE. Everything paid
 * for lives in a Telegram group, which is invisible to a crawler, so these two
 * or three files are the entire searchable footprint of the podcast. They are
 * worth naming properly — «قسمت ۱: سلام و احوالپرسی» rather than «نمونه ۱».
 *
 * Audio lives in the Media Library, never in this repository: one episode is
 * larger than the whole theme packed, and git keeps every version of a binary
 * for good. Upload `podcast-free-1.mp3` through رسانه ← افزودن and it appears;
 * a row whose file is not uploaded yet simply does not render, so the page is
 * never a play button that does nothing.
 *
 * @return array<int,array<string,string>>
 */
function zandi_podcast_episodes() {
	return apply_filters(
		'zandi_podcast_episodes',
		array(
			array(
				'slug'    => 'podcast-free-1',
				'title'   => 'قسمت رایگان ۱',
				'summary' => '',
			),
			array(
				'slug'    => 'podcast-free-2',
				'title'   => 'قسمت رایگان ۲',
				'summary' => '',
			),
			array(
				'slug'    => 'podcast-free-3',
				'title'   => 'قسمت رایگان ۳',
				'summary' => '',
			),
		)
	);
}

/**
 * The free episodes that actually have a file behind them.
 *
 * @return array<int,array<string,string>>
 */
function zandi_podcast_available_episodes() {
	$out = array();

	foreach ( zandi_podcast_episodes() as $episode ) {
		$media = zandi_media( $episode['slug'] );

		if ( empty( $media['url'] ) ) {
			continue;
		}

		$episode['url']  = (string) $media['url'];
		$episode['mime'] = (string) ( $media['mime'] ?? 'audio/mpeg' );
		$out[]           = $episode;
	}

	return $out;
}

/**
 * سرفصل — the chapters, as the owner sent them on 11 September 2026.
 *
 * FOURTEEN CHAPTERS, NINETY-EIGHT TOPICS, and not one of them typed here twice:
 * the counts on the page are `count()` of these arrays, never prose, for the
 * reason the course syllabus gives — «۹ موضوع» printed beside ten of them is
 * the kind of small wrongness that makes a reader doubt the rest of the page.
 *
 * Shape of a row:
 *
 *     array(
 *         'title' => 'فصل یک',
 *         'items' => array( 'سلام و احوالپرسی…', … ),
 *     )
 *
 * This replaced an earlier `episodes` + `summary` shape that nothing ever had
 * data for. The owner asked for the course pages' accordion — a chapter you tap
 * to reveal its topics — and that wants a list, not a sentence.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_podcast_chapters() {
	return apply_filters(
		'zandi_podcast_chapters',
		array(
			array(
				'title' => 'فصل یک',
				'items' => array(
					'سلام و احوالپرسی و انواع خداحافظی',
					'معرفی خود و خانواده',
					'بیان ملیت',
					'دعوت کردن، پذیرفتن و رد کردن',
					'مهمان و خوش‌آمدگویی، هدیه و پذیرایی',
					'شام، میز غذا، تشکر کردن، تقاضا کردن، تبریک گفتن و آرزو کردن',
					'بیان علایق',
					'بیان کارهای روزانه از بیدار شدن تا به تختخواب رفتن',
					'سفر و تعطیلات',
				),
			),
			array(
				'title' => 'فصل دو',
				'items' => array(
					'معرفی یک خانواده و نسبت‌ها',
					'انواع کارهای خانه و خانه‌داری',
					'ارتباط تلفنی',
					'مکالمه در نانوایی',
					'مواد غذایی و سبزیجات',
					'رنگ‌ها',
					'خرید لباس و کفش',
					'مایو و لباس ورزشی',
					'میوه‌ها، سوپ، سبزیجات و سالاد میوه',
					'لباس و جواهرات',
				),
			),
			array(
				'title' => 'فصل سه',
				'items' => array(
					'اجزای صورت و لباس',
					'وسایل آرایشگری',
					'سالن آرایشگاه و آماده شدن',
					'جاده و آب‌وهوا',
					'حمل‌ونقل و وسایل عمومی',
					'خانه و محل سکونت',
					'آپارتمان و وسایل خانه',
					'طوفان و دریا',
				),
			),
			array(
				'title' => 'فصل چهار',
				'items' => array(
					'اعضای بدن',
					'سلامتی',
					'معلولیت',
					'آشپزی و در آشپزخانه',
					'اتاق و اتاق خواب',
					'تصادف',
					'در انتظار بچه',
					'داروخانه و پزشکی',
					'مشاغل',
					'مکالمه مشاغل',
					'بیکاری و بازنشستگی',
				),
			),
			array(
				'title' => 'فصل پنج',
				'items' => array(
					'ادامه مشاغل',
					'بیان سال، تاریخ، ساعت و روز',
					'ورزش',
					'منظره برفی',
					'فاکتور',
					'جشن و مراسم',
					'مرخصی',
				),
			),
			array(
				'title' => 'فصل شش',
				'items' => array(
					'حمل‌ونقل',
					'وسایل حمل‌ونقل عمومی',
					'بلیط خریدن',
					'رانندگی',
					'عجله داشتن',
					'کافه جدید',
				),
			),
			array(
				'title' => 'فصل هفت',
				'items' => array(
					'پول و بانک',
					'مشکلات گیشه اتوماتیک',
					'درآمد و مالیات',
					'حساب بانکی',
					'عملیات بانکی',
					'پول و زندگی',
				),
			),
			array(
				'title' => 'فصل هشت',
				'items' => array(
					'مواد غذایی',
					'مقدارها',
					'خریدها و خرید کردن',
					'درخواست قیمت',
					'بازار',
				),
			),
			array(
				'title' => 'فصل نه',
				'items' => array(
					'فعالیت‌ها',
					'بازی‌ها و ورزش‌ها',
					'انواع ورزش‌ها',
					'تنیس',
					'مسابقات و ورزش‌های فردی',
					'داستان خرگوش کوچولو',
				),
			),
			array(
				'title' => 'فصل ده',
				'items' => array(
					'مدرسه و آموزش',
					'راهنمایی و دبیرستان',
					'دانشگاه',
					'تحصیلات',
					'هنر و فرهنگ',
				),
			),
			/*
			 * FLAGGED, NOT INVENTED. The owner's list had no heading here —
			 * just a bare «:» between ده and دوازده — and the nine topics under
			 * it are character-for-character the ones under فصل یک. That reads
			 * like a paste slip rather than a chapter that really repeats the
			 * first one. It is kept exactly as sent, because guessing what
			 * belongs in chapter eleven of somebody else's podcast is the kind
			 * of invention CLAUDE.md rules out, and the numbering would jump
			 * from ده to دوازده without it. Replace the items the moment the
			 * real list arrives; nothing else has to change.
			 */
			array(
				'title' => 'فصل یازده',
				'items' => array(
					'سلام و احوالپرسی و انواع خداحافظی',
					'معرفی خود و خانواده',
					'بیان ملیت',
					'دعوت کردن، پذیرفتن و رد کردن',
					'مهمان و خوش‌آمدگویی، هدیه و پذیرایی',
					'شام، میز غذا، تشکر کردن، تقاضا کردن، تبریک گفتن و آرزو کردن',
					'بیان علایق',
					'بیان کارهای روزانه از بیدار شدن تا به تختخواب رفتن',
					'سفر و تعطیلات',
				),
			),
			array(
				'title' => 'فصل دوازده',
				'items' => array(
					'تئاتر و هنر',
					'موزیک و رقص',
					'رویای بچگی',
					'کار و موفقیت',
					'داستان برنارد',
				),
			),
			array(
				'title' => 'فصل سیزده',
				'items' => array(
					'میوه‌ها و سبزیجات',
					'ماجراجویی خرگوش کوچولو',
					'زنبورها و طبیعت',
					'یک روز در طبیعت',
					'رز سامی',
				),
			),
			array(
				'title' => 'فصل چهارده',
				'items' => array(
					'معرفی ایران',
					'شرق ایران',
					'غرب ایران',
					'شمال ایران',
					'جنوب ایران',
					'پرسپولیس',
				),
			),
		)
	);
}

/* =========================================================================
 * متن پادکست — the transcripts
 * ====================================================================== */

/**
 * Every transcript, keyed by the slug its audio is uploaded under.
 *
 * Loaded from inc/data/ rather than written here: they run to a couple of
 * thousand characters each and this file is the entitlement logic. The
 * directory carries its own .htaccess, which matters because the theme is
 * web-served — the same reason the question bank lives there.
 *
 * Read once per request. The file is only touched on /podcast/, so a page that
 * shows no transcript never pays for it.
 *
 * @return array<string,string>
 */
function zandi_podcast_transcripts() {
	static $all = null;

	if ( null !== $all ) {
		return $all;
	}

	$path = get_theme_file_path( 'inc/data/podcast-transcripts.php' );
	$all  = file_exists( $path ) ? (array) require $path : array();

	return $all;
}

/**
 * One transcript, or '' when that episode has none yet.
 *
 * Episode three has no text at the time of writing, and the disclosure under it
 * simply does not render — a «متن پادکست» button that opens an empty panel is
 * worse than no button.
 *
 * @param string $slug Episode slug.
 * @return string Raw text, markers and all.
 */
function zandi_podcast_transcript( $slug ) {
	$all = zandi_podcast_transcripts();

	return isset( $all[ $slug ] ) ? (string) $all[ $slug ] : '';
}

/**
 * Whether a run of text is wholly French.
 *
 * Delegates to the placement test's detector rather than repeating the regex.
 * That helper is generic — it asks «Latin letters and no Arabic ones» — and it
 * only lives in inc/placement.php because that is where the need first came up.
 * Moving it would mean editing a file this work has no business in.
 *
 * @param string $text Text to inspect.
 * @return bool
 */
function zandi_podcast_is_french( $text ) {
	if ( function_exists( 'zandi_placement_is_french' ) ) {
		return zandi_placement_is_french( $text );
	}

	return (bool) preg_match( '/\p{Latin}/u', $text ) && ! preg_match( '/\p{Arabic}/u', $text );
}

/**
 * The direction attributes the ELEMENT holding this line needs.
 *
 * On the element, never on a span inside it. A French sentence wrapped in an
 * isolated span inside a right-to-left paragraph gets its characters in the
 * right order and its block still right-aligned, so the sentence hangs off the
 * wrong edge — and a transcript is forty of those in a row. Putting direction
 * on the block makes `text-align: start` resolve to left for the French and to
 * right for the Persian around it, with no physical alignment anywhere.
 *
 * @param string $text Line of the transcript.
 * @return string Attribute string with a leading space, or ''.
 */
function zandi_podcast_dir_attrs( $text ) {
	return zandi_podcast_is_french( $text ) ? ' dir="ltr" lang="fr"' : '';
}

/**
 * Escapes one line for output.
 *
 * A wholly French line is escaped and left to the `dir="ltr"` on its element. A
 * line that mixes the two goes through zandi_bidi(), which isolates the Latin
 * run so the bidi algorithm cannot reorder it against the Persian.
 *
 * @param string $text Line of the transcript.
 * @return string Escaped HTML.
 */
function zandi_podcast_text( $text ) {
	return zandi_podcast_is_french( $text ) ? esc_html( $text ) : zandi_bidi( $text );
}

/**
 * Turns a transcript's markers into typed blocks.
 *
 * The owner writes these in a plain-text shape with four markers — see the
 * header of inc/data/podcast-transcripts.php. Parsing them here means she can
 * paste the next transcript in the same shape and it renders, instead of
 * somebody hand-writing list markup for two thousand characters of French.
 *
 * ORDER MATTERS in the checks below: «●●» has to be tested before «●», or every
 * group lead is read as an ordinary bullet.
 *
 * @param string $raw Raw transcript.
 * @return array<int,array{type:string,text:string}>
 */
function zandi_podcast_transcript_blocks( $raw ) {
	$blocks = array();

	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );

		if ( '' === $line ) {
			// A blank line closes the group above it; runs of them count once.
			if ( $blocks && 'break' !== end( $blocks )['type'] ) {
				$blocks[] = array( 'type' => 'break', 'text' => '' );
			}

			continue;
		}

		if ( 0 === strpos( $line, '■' ) ) {
			$type = 'title';
			$line = ltrim( substr( $line, strlen( '■' ) ) );
		} elseif ( 0 === strpos( $line, '●●' ) ) {
			$type = 'lead';
			$line = ltrim( substr( $line, strlen( '●●' ) ) );
		} elseif ( 0 === strpos( $line, '●' ) ) {
			$type = 'item';
			$line = ltrim( substr( $line, strlen( '●' ) ) );
		} elseif ( 0 === strpos( $line, '○' ) ) {
			$type = 'subitem';
			$line = ltrim( substr( $line, strlen( '○' ) ) );
		} else {
			$type = 'text';
		}

		if ( '' === $line ) {
			continue;
		}

		$blocks[] = array( 'type' => $type, 'text' => $line );
	}

	// A trailing separator would render as an empty group.
	while ( $blocks && 'break' === end( $blocks )['type'] ) {
		array_pop( $blocks );
	}

	return $blocks;
}

/**
 * Prints a parsed transcript.
 *
 * Bullets are gathered into real <ul> runs rather than printed as a flat stack
 * of paragraphs: «● Dater de» and the two examples under it are a list, and a
 * screen reader that announces «list, ۳ items» is telling the student something
 * the visual bullets already tell everybody else.
 *
 * Every line gets its direction on its OWN element — see zandi_podcast_dir_attrs().
 * A transcript is forty French sentences in a right-to-left page, and getting
 * this wrong does not mangle one word, it right-aligns the entire text.
 *
 * @param string $raw Raw transcript.
 * @return void
 */
function zandi_podcast_render_transcript( $raw ) {
	$blocks = zandi_podcast_transcript_blocks( $raw );

	if ( ! $blocks ) {
		return;
	}

	$open_list = false;

	foreach ( $blocks as $block ) {
		$is_item = in_array( $block['type'], array( 'item', 'subitem' ), true );

		// Close the run as soon as something that is not a bullet turns up.
		if ( $open_list && ! $is_item ) {
			echo '</ul>';
			$open_list = false;
		}

		if ( $is_item && ! $open_list ) {
			echo '<ul class="pod-tr__list">';
			$open_list = true;
		}

		$dir  = zandi_podcast_dir_attrs( $block['text'] );
		$text = zandi_podcast_text( $block['text'] );

		switch ( $block['type'] ) {
			case 'title':
				printf( '<h4 class="pod-tr__title"%s>%s</h4>', $dir, $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				break;

			case 'lead':
				printf( '<p class="pod-tr__lead"%s>%s</p>', $dir, $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				break;

			case 'item':
				printf( '<li class="pod-tr__item"%s>%s</li>', $dir, $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				break;

			case 'subitem':
				printf( '<li class="pod-tr__item pod-tr__item--sub"%s>%s</li>', $dir, $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				break;

			case 'break':
				echo '<hr class="pod-tr__break" aria-hidden="true">';
				break;

			default:
				printf( '<p class="pod-tr__text"%s>%s</p>', $dir, $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		}
	}

	if ( $open_list ) {
		echo '</ul>';
	}
}

/**
 * Everything the page says.
 *
 * @return array<string,string>
 */
function zandi_podcast_copy() {
	return apply_filters(
		'zandi_podcast_copy',
		array(
			'eyebrow'        => 'پادکست',
			'title'          => 'پادکست Bonjour Monjour',
			'lead'           => 'هر قسمت پر از مکالمه‌های کاربردی و نکته‌های گرامری و فرهنگیه. همون چیزهایی که کمک می‌کنه راحت‌تر و با اعتماد به نفس بیشتر فرانسه حرف بزنی.',
			'meta'           => 'پادکست فرانسه Bonjour Monjour؛ ۱۰۰ قسمت کوتاه با متن کامل، برای تقویت مکالمه و شنیدار.',
			'facts_title'    => 'توش چیه',
			'plans_title'    => 'اشتراک',
			'plans_lead'     => 'قسمت‌ها توی یه گروه تلگرام خصوصی منتشر می‌شن. اشتراک رو که بگیری، درِ گروه برات باز می‌شه.',
			'plan_cta'       => 'خرید اشتراک',
			'plan_soon'      => 'به‌زودی',
			'plan_featured'  => 'به‌صرفه‌ترین',
			'expiry_note'    => 'وقتی اشتراکت تموم بشه، اگه تمدید نکنی دسترسیت بسته می‌شه.',
			'toman'          => 'تومان',
			'hero_cta'       => 'خرید اشتراک',
			'hero_from'      => 'از',
			'hero_listen'    => 'اول گوش بده',
			'cover_alt'      => 'کاور پادکست Bonjour Monjour',
			'episodes_title' => 'قسمت‌های رایگان',
			'episodes_lead'  => 'چند قسمت کامل، بدون خرید. گوش بده و ببین به دردت می‌خوره یا نه.',
			'transcript_show' => 'متن پادکست',
			'play'            => 'پخش',
			'pause'           => 'توقف',
			'seek'            => 'جابه‌جایی توی قسمت',
			'episodes_soon'  => 'قسمت‌های رایگان به‌زودی همین‌جا می‌آن.',
			'episodes_soon_body' => 'چند قسمت کامل می‌ذارم که قبل از خرید گوش بدی و ببینی به دردت می‌خوره یا نه.',
			'chapters_title' => 'سرفصل‌ها',
			'chapters_lead'  => 'روی هر فصل بزن تا ببینی توش چی یاد می‌گیری.',
			'chapter_count'  => 'قسمت',
			'chapter_topics' => 'موضوع',
			'chapters_stats_chapters' => 'فصل',
			'chapters_soon'  => 'سرفصل‌ها به‌زودی اینجا قرار می‌گیره.',
			'chapters_soon_body' => 'دارم فصل‌ها رو مرتب می‌کنم تا دقیق بدونی توی هر بخش چی یاد می‌گیری.',
			'how_title'      => 'چطور کار می‌کنه',
			'how_steps'      => array(
				'اشتراک رو از همین صفحه می‌گیری.',
				'از پنل کاربریت تلگرامت رو وصل می‌کنی. یه بار، همین اول.',
				'ربات درِ گروه رو برات باز می‌کنه و قسمت‌ها همون‌جان.',
			),
			'panel_title'    => 'پادکست من',
			'panel_none'     => 'هنوز اشتراک پادکست نداری.',
			'panel_none_body' => '۱۰۰ قسمت کوتاه با متن کامل، توی یه گروه تلگرام خصوصی. هر قسمت حدود ۱۵ دقیقه.',
			'panel_none_cta' => 'دیدن اشتراک‌ها',
			'panel_active'   => 'اشتراکت فعاله',
			'panel_until'    => 'فعال تا',
			'panel_left'     => 'روز مونده',
			'panel_grace'    => 'اشتراکت تموم شده — امروز آخرین فرصت تمدیده.',
			'panel_expired'  => 'اشتراکت تموم شده و دسترسیت بسته شده.',
			'panel_renew'    => 'تمدید اشتراک',
			'panel_connect'  => 'اتصال به تلگرام',
			'panel_connect_note' => 'یه بار این دکمه رو بزن تا ربات بفهمه کدوم حساب تلگرام مال توئه. تا وصلش نکنی نمی‌تونه راهت بده — و اگه قبلاً زدی، دوباره زدنش هیچ اشکالی نداره.',
			'panel_connected'    => 'تلگرامت وصله',
		)
	);
}
