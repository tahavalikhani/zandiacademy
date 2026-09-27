<?php
/**
 * /free-podcast/ — days of پادکست Bonjour Monjour for signing up from this page.
 *
 * WHAT THE OFFER IS, EXACTLY. Somebody who creates an account FROM THIS PAGE
 * gets zandi_free_podcast_days() of podcast access, once. Somebody who creates
 * an account anywhere else — the header, a course page, the placement test, the
 * checkout — gets nothing. The owner's words, 27 September 2026: the gift is
 * for signing up here, and only here.
 *
 * HOW «FROM THIS PAGE» IS KNOWN, WHEN THE THEME DOES NOT OWN THE FORM.
 *
 * Digits renders the signup form and submits it over AJAX, so there is no
 * hidden field the theme could add that says «came from the gift page». But the
 * theme already had to solve the same problem for the return address, and did:
 * the gift page's button links to /register/?redirect_to=…/free-podcast/, the
 * address is remembered in a cookie when /register/ is drawn, and inside
 * Digits' own signup request zandi_login_destination() reads it back from the
 * request, from the referer's query string, or from that cookie. The gift asks
 * the same function the same question: is the journey happening now the one
 * that ends on /free-podcast/?
 *
 * On top of that, zandi_free_podcast_signup_qualifies() insists the form was on
 * an auth page or on this page. The cookie lives thirty minutes, and a visitor
 * who looked at the gift page and then created an account at the checkout must
 * not collect it on the strength of a cookie from a journey they abandoned.
 *
 * WHAT IS WRITTEN, AND WHERE IT BECOMES ACCESS. Two user-meta rows, once:
 * when, and how many days — see zandi_podcast_record_gift() in inc/podcast.php.
 * zandi_podcast_compute_expiry() treats that record as one more grant beside
 * the paid orders, so the gift stacks exactly like a purchase and the bot is
 * told through the same push. There is no second entitlement system here.
 *
 * NOT zandi_podcast_manual_until. That meta is a floor: max() of two dates.
 * Written there, a gift would swallow the remainder of itself the moment the
 * student bought a plan, instead of the plan starting after it.
 *
 * COST. Nothing here runs on an ordinary page view. The grant runs on
 * user_register, which is an event; the page's stylesheet loads on this route
 * only; the page has no JavaScript; and a signed-out visitor gets a page the
 * host can cache, with no cookie set on it.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. The route
 *
 * Declared in all three places in functions.php — zandi_register_routes(),
 * zandi_query_vars() and zandi_parse_request() — and addressed only through
 * zandi_free_podcast_url(), which falls back to a query string when
 * permalinks are «ساده».
 * ====================================================================== */

/**
 * The slug the gift page answers on.
 *
 * @return string
 */
function zandi_free_podcast_slug() {
	return (string) apply_filters( 'zandi_free_podcast_slug', 'free-podcast' );
}

/**
 * Whether the current request is the gift page.
 *
 * @return bool
 */
function zandi_is_free_podcast() {
	return (bool) get_query_var( 'zandi_free_podcast' );
}

/**
 * The canonical URL of the gift page.
 *
 * @return string
 */
function zandi_free_podcast_url() {
	return zandi_pretty_permalinks()
		? home_url( '/' . zandi_free_podcast_slug() . '/' )
		: home_url( '/?zandi_free_podcast=1' );
}

/**
 * Whether a URL is the gift page, whichever form it was written in.
 *
 * Campaign tags do not make it another page — the link the owner posts on
 * Instagram may well carry utm_source. Written the way zandi_is_account_url()
 * is: a subdirectory install's prefix is stripped, and with «ساده» permalinks
 * the route is a query string.
 *
 * @param string $url URL to test.
 * @return bool
 */
function zandi_is_free_podcast_url( $url ) {
	$url = zandi_safe_destination( (string) $url );

	if ( '' === $url ) {
		return false;
	}

	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	$base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

	if ( '' !== $base && 0 === strpos( $path, $base ) ) {
		$path = trim( substr( $path, strlen( $base ) ), '/' );
	}

	if ( '' === $path ) {
		return '1' === zandi_url_param( $url, 'zandi_free_podcast' );
	}

	return zandi_free_podcast_slug() === $path;
}

/**
 * Whether the page is kept out of search results.
 *
 * Follows the podcast page by default: this page exists to send people to that
 * one, and it would be odd for the gift to rank while the thing it gives away
 * is deliberately hidden. When zandi_podcast_noindex() opens, this opens too.
 *
 * @return bool
 */
function zandi_free_podcast_noindex() {
	return (bool) apply_filters( 'zandi_free_podcast_noindex', zandi_podcast_noindex() );
}

/* =========================================================================
 * 2. The offer, and the owner's switch
 * ====================================================================== */

/**
 * How many days a signup from this page is worth.
 *
 * Read at the moment of signup and stored with the gift, so changing this later
 * changes the offer, never a gift already given.
 *
 * @return int
 */
function zandi_free_podcast_days() {
	return max( 1, (int) apply_filters( 'zandi_free_podcast_days', 7 ) );
}

/**
 * The option behind the owner's on/off switch.
 *
 * @return string
 */
function zandi_free_podcast_option() {
	return 'zandi_free_podcast_open';
}

/**
 * Whether the offer is running.
 *
 * On unless the owner has switched it off under تنظیمات ← همگانی — the option
 * does not exist until that screen is first saved, and a missing option means
 * the offer she asked for is live the moment the theme is uploaded.
 *
 * Closing it changes the future only: nobody new receives the gift, the page
 * sends visitors to /podcast/, and every gift already given runs its full length.
 *
 * @return bool
 */
function zandi_free_podcast_open() {
	return (bool) apply_filters( 'zandi_free_podcast_open', '0' !== (string) get_option( zandi_free_podcast_option(), '1' ) );
}

/**
 * Stores the switch as '1' or '0' and nothing else.
 *
 * An unticked checkbox is simply absent from the POST, and options.php hands
 * the callback null for it — which has to mean «off», not «unchanged».
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function zandi_free_podcast_sanitize_open( $value ) {
	return '1' === (string) $value ? '1' : '0';
}

/**
 * Puts the switch on تنظیمات ← همگانی.
 *
 * The core Settings API rather than a screen of the theme's own: core draws the
 * form, checks the nonce, checks the capability (manage_options) and saves, so
 * there is no handler here to get wrong.
 *
 * @return void
 */
function zandi_free_podcast_register_setting() {
	register_setting(
		'general',
		zandi_free_podcast_option(),
		array(
			'type'              => 'string',
			'sanitize_callback' => 'zandi_free_podcast_sanitize_open',
			'default'           => '1',
			'show_in_rest'      => false,
		)
	);

	add_settings_field(
		zandi_free_podcast_option(),
		'هدیه‌ی پادکست رایگان',
		'zandi_free_podcast_render_setting',
		'general',
		'default',
		array( 'label_for' => zandi_free_podcast_option() )
	);
}
add_action( 'admin_init', 'zandi_free_podcast_register_setting' );

/**
 * Draws the switch.
 *
 * @return void
 */
function zandi_free_podcast_render_setting() {
	$option = zandi_free_podcast_option();
	?>
	<label for="<?php echo esc_attr( $option ); ?>">
		<input type="checkbox" id="<?php echo esc_attr( $option ); ?>" name="<?php echo esc_attr( $option ); ?>" value="1"<?php echo zandi_free_podcast_open() ? ' checked' : ''; ?>>
		<?php
		echo esc_html(
			sprintf(
				'باز است — هر کسی از صفحه‌ی پادکست رایگان ثبت‌نام کنه، %s روز پادکست هدیه می‌گیره.',
				zandi_fa_digits( (string) zandi_free_podcast_days() )
			)
		);
		?>
	</label>
	<p class="description">
		<?php echo esc_html( 'تیکش رو برداری، هدیه برای ثبت‌نام‌های تازه بسته می‌شه و صفحه به صفحه‌ی پادکست می‌ره. هدیه‌هایی که قبلاً داده شده تا آخرش ادامه دارن.' ); ?>
		<a href="<?php echo esc_url( zandi_free_podcast_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( 'دیدن صفحه' ); ?></a>
	</p>
	<?php
}

/* =========================================================================
 * 3. Who receives it
 * ====================================================================== */

/**
 * Whether the account being created right now was created from this page.
 *
 * Runs INSIDE the signup request — with Digits an AJAX call from /register/.
 * Every condition is required:
 *
 *   nobody signed in    the owner adding a student under کاربران ← افزودن
 *                       fires user_register too, and that student signed up
 *                       nowhere — the same guard zandi_persist_intent_on_register()
 *                       uses
 *   not the checkout    WooCommerce creating an account while taking an order
 *                       is not somebody answering this offer
 *   the journey ends    zandi_login_destination() resolves to /free-podcast/.
 *   here                It prefers the journey happening now — redirect_to on
 *                       the request, then on the referer — over the cookie, so
 *                       somebody who looked at the gift page and then signed up
 *                       from a course page is heading for the course, not here
 *   the form was on     an auth page, this page, or no usable referer at all —
 *   /register/ or here  none, or one stripped to the bare origin by a referrer
 *                       policy. A cookie from an abandoned visit must not reach
 *                       a signup made on the checkout or in a popup on some
 *                       other page
 *
 * @return bool
 */
function zandi_free_podcast_signup_qualifies() {
	if ( is_user_logged_in() ) {
		return false;
	}

	if ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) {
		return false;
	}

	if ( ! zandi_is_free_podcast_url( zandi_login_destination() ) ) {
		return false;
	}

	$referer = (string) wp_get_raw_referer();

	/*
	 * No referer at all happens only under a `no-referrer` policy, and then the
	 * cookie — set on /register/, which only the gift page's journey points at
	 * /free-podcast/ — is the evidence left. An off-site referer on a signup
	 * request is never legitimate and gets nothing.
	 */
	if ( '' === $referer ) {
		return true;
	}

	$referer = zandi_safe_destination( esc_url_raw( $referer ) );

	if ( '' === $referer ) {
		return false;
	}

	return zandi_is_account_url( $referer )
		|| zandi_is_free_podcast_url( $referer )
		|| zandi_free_podcast_is_bare_origin( $referer );
}

/**
 * Whether a referer is the site's address with nothing after it.
 *
 * WHAT A `strict-origin` OR `origin` REFERRER POLICY LEAVES OF /register/. A
 * security plugin or a host header that sets one strips every referer down to
 * https://site/ — same-origin requests included — and then the signup request
 * cannot say which page its form was on. Refusing it would refuse the gift to
 * EVERY signup from this page on such a site, the worst failure available, so
 * it is treated like a missing referer and the cookie set on /register/ decides.
 * The narrow price: a Digits popup on the homepage itself, within thirty minutes
 * of an abandoned visit to /register/ from here, would qualify too.
 *
 * @param string $url Validated referer.
 * @return bool
 */
function zandi_free_podcast_is_bare_origin( $url ) {
	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	$base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

	return $path === $base && '' === (string) wp_parse_url( $url, PHP_URL_QUERY );
}

/**
 * Gives the gift to one account, if it may have it.
 *
 * Idempotent: an account holds one gift at most, and zandi_podcast_record_gift()
 * refuses a second. The sync afterwards recomputes the expiry from every grant
 * the account has and tells the bot — the same path a paid order takes.
 *
 * @param int $user_id Student.
 * @return bool Whether a gift was given.
 */
function zandi_free_podcast_grant( $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id || ! zandi_free_podcast_open() || zandi_is_staff( $user_id ) ) {
		return false;
	}

	if ( ! zandi_podcast_record_gift( $user_id, zandi_free_podcast_days() ) ) {
		return false;
	}

	zandi_podcast_sync( $user_id );

	/**
	 * Fires when a signup from /free-podcast/ has received its gift.
	 *
	 * @param int $user_id Student.
	 */
	do_action( 'zandi_free_podcast_granted', $user_id );

	return true;
}

/**
 * user_register, for somebody signing up from this page.
 *
 * Priority 20: after zandi_persist_intent_on_register() at 5, which reads the
 * same request, and independent of it — neither changes what the other sees.
 *
 * @param int $user_id New user.
 * @return void
 */
function zandi_free_podcast_on_register( $user_id ) {
	if ( zandi_free_podcast_signup_qualifies() ) {
		zandi_free_podcast_grant( $user_id );
	}
}
add_action( 'user_register', 'zandi_free_podcast_on_register', 20 );

/* =========================================================================
 * 4. The page
 * ====================================================================== */

/**
 * Where the visitor stands, which decides what the page says.
 *
 *   guest         signed out: the offer and the button
 *   gifted        has the gift, and access is live
 *   gift_over     had the gift, and it has run out
 *   not_eligible  signed in, never had it — an older account, or one made
 *                 somewhere else on the site
 *
 * @param int $user_id Current user, 0 when signed out.
 * @return string
 */
function zandi_free_podcast_state( $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return 'guest';
	}

	if ( ! zandi_podcast_gift_grant( $user_id ) ) {
		return 'not_eligible';
	}

	return zandi_podcast_has_access( $user_id ) ? 'gifted' : 'gift_over';
}

/**
 * Every string on the page.
 *
 * NOTHING HERE MENTIONS A PHONE NUMBER, and that is the owner's instruction:
 * the page says what they get, and the signup form asks for the number when
 * they get there. Told up front, it reads as the price.
 *
 * `%s` is the day count wherever it appears — never type the number in, for
 * the reason zandi_podcast_copy() gives about «۳۰».
 *
 * @return array<string,string>
 */
function zandi_free_podcast_copy() {
	return apply_filters(
		'zandi_free_podcast_copy',
		array(
			'title'          => '%s روز پادکست فرانسه رایگان',
			'meta'           => 'ثبت‌نام کن و %s روز پادکست فرانسه Bonjour Monjour رو رایگان گوش بده؛ ۱۰۰ قسمت کوتاه با متن کامل.',
			'eyebrow'        => 'هدیه‌ی ثبت‌نام',
			'headline'       => '%s روز پادکست فرانسه،',
			'headline_mark'  => 'رایگان.',
			'lead'           => 'ثبت‌نام کن و با پادکست Bonjour Monjour هر روز فرانسه گوش بده.',
			'cta'            => '%s روز رایگانم رو فعال کن',
			'cta_note'       => 'بدون پرداخت · بدون تمدید خودکار',
			'cards_label'    => 'توی این هدیه چی هست',
			'card_hours'     => 'آموزش صوتی',
			'card_episodes'  => 'هر کدوم %s',
			'card_text'      => 'برای هر قسمت',
			'card_days'      => '%s روز',
			'card_days_note' => 'کاملاً رایگان',
			'band_kicker'    => "Allez\u{00A0}!",
			'band_title'     => 'هدیه‌ت منتظرته.',

			/*
			 * The illustration: decorative, hidden from assistive tech, and French
			 * on purpose. French puts a space before ! and ?, and it is a no-break
			 * space so the mark never wraps onto a line of its own.
			 */
			'ticket_kind'    => 'BILLET · CADEAU',
			'ticket_for'     => 'Bon pour',
			'ticket_days'    => '%s jours',
			'ticket_line'    => '%s روز پادکست رایگان',
			'cover_kind'     => 'PODCAST',
			'bubble_hello'   => "Bonjour\u{00A0}!",
			'bubble_howru'   => "Ça va\u{00A0}?",

			// Signed in, with the gift.
			'gifted_badge'   => 'هدیه‌ت فعال شد',
			'gifted_title'   => '%s روز پادکست،',
			'gifted_mark'    => 'مال تو.',
			'gifted_until'   => 'فعال تا',
			'connect_title'  => 'یه قدم مونده: تلگرامت رو وصل کن',
			'connect_body'   => 'یه بار این دکمه رو بزن تا ربات بفهمه کدوم حساب تلگرام مال توئه. بعدش درِ گروه برات باز می‌شه.',
			'connected_body' => 'از گروه خصوصی پادکست گوش بده؛ هر وقت خواستی، همه‌چی توی پنلت هم هست.',
			'panel_link'     => 'برو به پنل من',

			// Signed in, gift used up.
			'over_title'     => 'هدیه‌ت تموم شده.',
			'over_body'      => 'اگه خوشت اومد، از صفحه‌ی پادکست اشتراک بگیر و از همون‌جایی که بودی ادامه بده.',

			// Signed in, never had it.
			'other_title'    => 'این هدیه مال حساب‌های تازه‌ست.',
			'other_body'     => 'تو از قبل حساب داری؛ اشتراک پادکست رو می‌تونی از صفحه‌ی پادکست بگیری.',
			'plans_cta'      => 'دیدن اشتراک‌ها',
		)
	);
}

/**
 * One of the podcast's facts, by the icon it is filed under, or null.
 *
 * The cards on this page print numbers the podcast page already states — the
 * episode count, the hours, the transcripts — and read them from
 * zandi_podcast_facts() so the two pages cannot disagree. The icon is the key
 * because it is the one field of a fact that is not copy.
 *
 * @param string $icon Icon name in the fact.
 * @return array<string,string>|null
 */
function zandi_free_podcast_fact( $icon ) {
	foreach ( zandi_podcast_facts() as $fact ) {
		if ( isset( $fact['icon'] ) && $icon === $fact['icon'] ) {
			return $fact;
		}
	}

	return null;
}

/**
 * The four cards under the headline.
 *
 * A card whose fact has gone missing from zandi_podcast_facts() is dropped
 * rather than printed empty — the day card never is, because the offer is its
 * own fact.
 *
 * @return array<int,array{icon:string,tone:string,value:string,note:string}>
 */
function zandi_free_podcast_cards() {
	$copy  = zandi_free_podcast_copy();
	$days  = zandi_fa_digits( (string) zandi_free_podcast_days() );
	$cards = array();

	$hours = zandi_free_podcast_fact( 'clock' );
	if ( $hours ) {
		$cards[] = array( 'icon' => 'clock', 'tone' => 'lime', 'value' => $hours['value'], 'note' => $copy['card_hours'] );
	}

	$episodes = zandi_free_podcast_fact( 'layers' );
	if ( $episodes ) {
		$cards[] = array(
			'icon'  => 'headphones',
			'tone'  => 'lilac',
			'value' => trim( $episodes['value'] . ' ' . $episodes['label'] ),
			'note'  => '' !== (string) $episodes['note'] ? sprintf( $copy['card_episodes'], $episodes['note'] ) : '',
		);
	}

	$text = zandi_free_podcast_fact( 'clipboard' );
	if ( $text ) {
		$cards[] = array( 'icon' => 'clipboard', 'tone' => 'sky', 'value' => $text['label'], 'note' => $copy['card_text'] );
	}

	$cards[] = array( 'icon' => 'gift', 'tone' => 'peach', 'value' => sprintf( $copy['card_days'], $days ), 'note' => $copy['card_days_note'] );

	return apply_filters( 'zandi_free_podcast_cards', $cards );
}

/**
 * Where the signup buttons go.
 *
 * /register/ carrying this page as the destination. That destination is both
 * how the student gets back here to connect Telegram and — through
 * zandi_login_destination() — how the signup request knows the gift is owed.
 *
 * @return string
 */
function zandi_free_podcast_signup_url() {
	return zandi_register_url( zandi_free_podcast_url() );
}

/**
 * Closes the page when the offer is off, and keeps personal copies out of caches.
 *
 * Closed: a 302 to /podcast/, temporary because the owner may run the offer
 * again. A page promising a gift the signup would no longer give is worse than
 * no page.
 *
 * Signed in: the page shows that person's expiry and their connect button, so
 * it must never be served to anybody else — zandi_do_not_cache(), which is the
 * one LiteSpeed actually reads. A signed-out visitor gets the same page as
 * every other signed-out visitor, and it stays cacheable.
 *
 * @return void
 */
function zandi_free_podcast_request() {
	if ( ! zandi_is_free_podcast() ) {
		return;
	}

	if ( ! zandi_free_podcast_open() ) {
		wp_safe_redirect( zandi_podcast_url(), 302 );
		exit;
	}

	if ( is_user_logged_in() ) {
		zandi_do_not_cache( 'zandi free podcast' );
	}
}
add_action( 'template_redirect', 'zandi_free_podcast_request', 8 );
