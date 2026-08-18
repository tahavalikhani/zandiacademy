<?php
/**
 * Homepage copy.
 *
 * Voice: the same one as the course pages — second person singular («تو»),
 * warm, direct, no institutional Persian. If a sentence would sound at home in
 * a bank brochure, it is wrong for this brand.
 *
 * FACTS ONLY. Everything here is drawn from what the academy actually offers.
 * Where a real figure or detail is missing the value is left empty and marked
 * TODO — it is not invented. An earlier draft of this file carried fabricated
 * student counts, invented instructors and a placeholder Tehran address; all of
 * it has been removed.
 *
 * NEVER claim: a money-back guarantee, installment payments, or a completion
 * certificate. The academy offers none of the three.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Academy identity.
 *
 * @return array{name:string,tagline:string,description:string}
 */
function zandi_site() {
	return apply_filters(
		'zandi_site',
		array(
			'name'        => 'آکادمی زندی',
			'tagline'     => 'آموزش زبان فرانسه',
			'description' => 'آکادمی زبان فرانسه زندی، فرانسه رو همون‌طور که واقعاً حرف زده می‌شه به فارسی‌زبان‌ها یاد می‌ده. مستقیم از پاریس.',
		)
	);
}

/**
 * The homepage's title and meta description.
 *
 * Separate from zandi_site() because these two strings have a different job.
 * zandi_site() is how the academy describes itself; these are what a person
 * sees in a list of ten Google results, and they have to carry the phrase that
 * person typed — «آموزش زبان فرانسه آنلاین».
 *
 * THE HERO <h1> IS NOT THIS. It stays «فرانسوی رو آسون یاد بگیر!»: it is the
 * brand's voice and the first thing a visitor reads, and stuffing a search
 * phrase into it would cost more than the ranking is worth. The keyword lives
 * here, in the title and the description, where it belongs.
 *
 * Every claim below is on a page a human can read: Paris (hero), three video
 * courses A1–B1 (inc/courses.php), 24-hour support and the end-of-course
 * interview (zandi_faqs()). Nothing here is invented to fill the character
 * count.
 *
 * @return array{title:string,description:string}
 */
function zandi_home_meta() {
	return apply_filters(
		'zandi_home_meta',
		array(
			'title'       => 'آموزش زبان فرانسه آنلاین از صفر تا مکالمه | آکادمی زندی',
			'description' => 'آموزش زبان فرانسه آنلاین با شیما زندی، مدرس ساکن پاریس. سه دوره ویدیویی از سطح پایه A1 تا B1، با پشتیبانی ۲۴ ساعته، تصحیح تمرین و مصاحبه پایان دوره.',
		)
	);
}

/**
 * Copy for the 404 page.
 *
 * @return array<string,string>
 */
function zandi_not_found() {
	return apply_filters(
		'zandi_not_found',
		array(
			'title'       => 'این صفحه پیدا نشد',
			'description' => 'شاید آدرس عوض شده، شاید یه حرف توی لینک جا افتاده. از اینجا می‌تونی برگردی سر جای درست.',
			'primary'     => array( 'label' => 'دوره‌ها رو ببین', 'url' => zandi_section_url( 'courses' ) ),
			'secondary'   => array( 'label' => 'بپرس', 'url' => zandi_support_url() ),
		)
	);
}

/**
 * The نماد اعتماد الکترونیکی seal, exactly as eNamad issued it.
 *
 * Verbatim, and that is a hard requirement rather than a preference. eNamad's
 * own guidance (enamad.ir/logohelp) is explicit on three points:
 *
 *   «لوگو اینماد حتما باید روی صفحه اصلی قرار گیرد»
 *      — it has to be on the homepage. The footer is on every page, so it is.
 *
 *   «لوگو اینماد باید بدون هیچگونه ویرایشی و عینا مطابق آنچه در پنل کاربری
 *    شما … آمده است روی سایت قرار گیرد»
 *      — no edits of any kind, character for character.
 *
 *   «برای درج لوگو اینماد … از یک ادیتور html ساده مانند notepad استفاده
 *    نمایید چون برخی cms ها مانند وردپرس به‌صورت خودکار لوگوی اینماد را
 *    ویرایش کرده و پارامترها را تغییر می‌دهند و لوگو نمایش داده نمی‌شود»
 *      — WordPress is named, by name, as a CMS that rewrites this markup and
 *        breaks the seal.
 *
 * That last point is why this lives in a PHP template and not in a page, a
 * post, a widget or a block. Content that goes through the editor is filtered
 * on the way in and on the way out — wp_targeted_link_rel() adds rel attributes
 * to target="_blank" links, and TinyMCE and the block parser both normalise
 * attributes they do not recognise, which is fatal to the non-standard `code`
 * attribute below. Nothing here passes through any of that: the string is held
 * in a nowdoc, so PHP does not interpolate or escape a single character, and
 * footer.php echoes it untouched.
 *
 * Specifically do NOT "tidy" any of this:
 *
 *   - `referrerpolicy='origin'` on BOTH elements. eNamad's server reads the
 *     referrer to confirm the seal is being served from the domain it was
 *     issued to. Strip it and the image 403s.
 *   - No `rel` attribute. eNamad: «عبارت rel="noopener noreferrer" باعث عدم
 *     نمایش لوگو در سایت شما میشود». Every modern browser already applies
 *     noopener implicitly to target="_blank", so there is no security cost to
 *     leaving it off — the reverse-tabnabbing hole this would otherwise open
 *     was closed by browsers in 2021.
 *   - The `code` attribute on the <img>. Not valid HTML; eNamad wants it there.
 *   - The single quotes, the unescaped `&`, the empty `alt`.
 *   - The `src`. The image must be fetched from trustseal.enamad.ir on every
 *     load. Self-hosting a copy is what «قرار دادن تصویر لوگوی اینماد» forbids,
 *     and eNamad treats tampering with the mark as a criminal matter.
 *
 * The seal is issued to one domain. It will not render on a different one, and
 * a .com seal does not work on a .ir domain even if one redirects to the other.
 *
 * Sizing and placement are done in style.css, on the wrapper — never by editing
 * the markup.
 *
 * @return string
 */
function zandi_enamad_issued_seal() {
	return <<<'HTML'
<a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=7289224&Code=48q0yxLFetDLgVLe46LrH99ClqmV784E'><img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=7289224&Code=48q0yxLFetDLgVLe46LrH99ClqmV784E' alt='' style='cursor:pointer' code='48q0yxLFetDLgVLe46LrH99ClqmV784E'></a>
HTML;
}

/**
 * The seal as it must be printed on this site.
 *
 * The issued markup above, plus exactly one attribute: `data-no-lazy="1"`.
 *
 * This is not decoration, and it is not a change to the mark. LiteSpeed Cache
 * is active on this install, and its Lazy Load rewrites every `<img src=...>`
 * in the page to `data-src` with a placeholder, expecting its own script to
 * swap them back when the image scrolls into view. For the eNamad seal that is
 * fatal twice over:
 *
 *   1. The browser never requests the image at all. Confirmed on the live site
 *      on 19 August 2026 — DevTools' network panel, filtered to `logo.aspx`,
 *      recorded zero requests while the footer showed an empty white tile.
 *   2. eNamad's own verification crawler reads the page looking for the logo
 *      URL in a `src`. After the rewrite there is no `src` to find, so lazy
 *      loading breaks the seal's *verification* as well as its display.
 *
 * So this attribute makes eNamad's requirements more likely to be met, not
 * less. It is the escape hatch LiteSpeed documents for exactly this case, and
 * WP Rocket and Perfmatters honour the same attribute, so swapping the cache
 * plugin does not reopen the hole.
 *
 * Nothing eNamad issued is touched: the id, the Code, the src, both
 * `referrerpolicy` attributes, the non-standard `code` attribute, the empty
 * alt, the inline style and the absence of `rel` are all exactly as delivered.
 * zandi_enamad_issued_seal() above holds that string on its own so it stays
 * auditable, and the test harness compares it byte for byte.
 *
 * If eNamad ever objects, this is one filter away from being switched off:
 *
 *     add_filter( 'zandi_enamad_no_lazy', '__return_false' );
 *
 * — but expect the seal to stop rendering again while LiteSpeed's Lazy Load is
 * on, in which case exclude `trustseal.enamad.ir` under
 * LiteSpeed Cache → Page Optimization → Media Excludes instead.
 *
 * @return string
 */
function zandi_enamad_seal() {
	$seal = zandi_enamad_issued_seal();

	if ( ! apply_filters( 'zandi_enamad_no_lazy', true ) ) {
		return $seal;
	}

	/*
	 * Anchored on `<img ` so only the image is touched and the surrounding
	 * anchor is left exactly as issued. One replacement, never more.
	 */
	return preg_replace( '/<img /', '<img data-no-lazy="1" ', $seal, 1 );
}

/**
 * Trust badges for the footer.
 *
 * نماد اعتماد ships by default — the academy's domain was verified on
 * 12 August 2026 and the seal is unconditional site identity. The ZarinPal
 * badge is added on the filter by inc/woocommerce.php, and only while the
 * gateway is actually enabled: a payment badge on a site that cannot take
 * payment is the one kind of trust mark that costs trust rather than building
 * it.
 *
 * Each entry is a block of markup keyed by a slug, so a badge can be added or
 * dropped without touching footer.php.
 *
 * @return array<string,string> Slug => markup.
 */
function zandi_trust_badges() {
	$badges = array(
		'enamad' => zandi_enamad_seal(),
	);

	/**
	 * Filters the footer trust badges.
	 *
	 * @param array<string,string> $badges Slug => markup.
	 */
	return (array) apply_filters( 'zandi_trust_badges', $badges );
}

/**
 * Primary navigation.
 *
 * @return array<int,array{label:string,href:string}>
 */
function zandi_navigation() {
	/*
	 * Real URLs, not on-page anchors. Every nav item is its own page, so a
	 * visitor can link to «دوره‌ها» or «سوالات متداول» directly and the browser
	 * back button behaves the way they expect.
	 */
	return apply_filters(
		'zandi_navigation',
		array(
			array( 'label' => 'خانه', 'href' => home_url( '/' ) ),
			array( 'label' => 'دوره‌ها', 'href' => zandi_section_url( 'courses' ) ),
			array( 'label' => 'روش تدریس', 'href' => zandi_section_url( 'method' ) ),
			array( 'label' => 'درباره من', 'href' => zandi_section_url( 'about' ) ),
			array( 'label' => 'سوالات متداول', 'href' => zandi_section_url( 'faq' ) ),
			array( 'label' => 'تماس', 'href' => zandi_section_url( 'contact' ) ),
		)
	);
}

/**
 * Fallback navigation, used when no menu is assigned to the `primary` location.
 *
 * @return array<int,array{label:string,url:string}>
 */
function zandi_fallback_nav() {
	$items = array();

	foreach ( zandi_navigation() as $item ) {
		$items[] = array(
			'label' => $item['label'],
			'url'   => $item['href'],
		);
	}

	return apply_filters( 'zandi_fallback_nav', $items );
}

/**
 * Hero.
 *
 * @return array<string,mixed>
 */
function zandi_hero() {
	return apply_filters(
		'zandi_hero',
		array(
			// Empty hides the badge; the hero renders it only when set.
			'badge'       => '',
			'title'       => 'فرانسوی رو آسون یاد بگیر!',
			'description' => 'من شیما زندی‌ام، توی پاریس زندگی می‌کنم و فرانسه درس می‌دم. اینجا از جلسه اول حرف می‌زنی، حتی اگه غلط باشه. چون غلط گفتن قدم اوله، سکوت هیچ قدمی نیست.',
			'primary'     => array( 'label' => 'دوره‌ها رو ببین', 'url' => zandi_section_url( 'courses' ) ),
			/*
			 * An anchor, not the /method/ page. This button sits under the fold
			 * of the hero and its job is to move a visitor down the landing
			 * page, not off it — the same content is a few hundred pixels away
			 * in the «روش تدریس» block. zandi_resolve_anchor() keeps it working
			 * if the hero is ever rendered somewhere other than the front page.
			 *
			 * `#about` is not a typo: template-parts/home/features.php — the
			 * «روش تدریس» section — carries id="about". The /about/ route is a
			 * different thing entirely («درباره من», the teachers partial).
			 */
			'secondary'   => array( 'label' => 'روش تدریسم رو ببین', 'url' => zandi_resolve_anchor( '#about' ) ),
			/*
			 * The three ticked lines under the buttons are gone. Everything they
			 * said is said again a few hundred pixels further down — the stats
			 * band, the method blocks and the FAQ each carry one of them.
			 */
			'highlights'  => array(),
		)
	);
}

/**
 * Trust figures.
 *
 * Every number here is real or derived from the catalogue — the follower count
 * from Instagram, the session total from the three published courses
 * (۷۸ + ۱۰۰ + ۵۹). Do not add a figure that cannot be pointed at.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_stats() {
	return apply_filters(
		'zandi_stats',
		array(
			array(
				/*
				 * The owner's figure for students taught, replacing the Instagram
				 * follower count. In RTL the suffix span sits to the left of the
				 * number, so '+' renders as «+۵۰۰».
				 *
				 * TODO: confirm the caption reads the way she wants — it only
				 * restates the number, and is the one line here she has not
				 * written herself.
				 */
				'icon'    => 'users',
				'value'   => 500,
				'suffix'  => '+',
				'label'   => 'زبان‌آموز',
				'caption' => 'کسانی که تا حالا با من شروع کردن',
			),
			array(
				'icon'    => 'play',
				'value'   => 237,
				'suffix'  => '',
				'label'   => 'جلسه ویدیویی',
				'caption' => 'توی سه سطح پایه، متوسط و پیشرفته',
			),
			array(
				'icon'    => 'chat',
				'value'   => 24,
				'suffix'  => ' ساعته',
				'label'   => 'پشتیبانی',
				'caption' => 'واقعاً ۲۴ ساعته، نه فقط ساعت اداری',
			),
			array(
				'icon'    => 'graduation',
				'value'   => 15,
				'suffix'  => ' دقیقه',
				'label'   => 'مصاحبه پایان دوره',
				'caption' => 'رودررو با خودم، توی گوگل میت',
			),
		)
	);
}

/**
 * Teaching method — the four blocks, shared with the course pages.
 *
 * @return array<int,array<string,string>>
 */
function zandi_features() {
	return apply_filters(
		'zandi_features',
		array(
			array(
				'icon'        => 'chat',
				'title'       => 'مکالمه‌محور، نه گرامرمحور',
				'description' => 'از همون جلسه اول جمله می‌سازی. گرامر پشت جمله میاد، نه جلوش.',
			),
			array(
				'icon'        => 'globe',
				'title'       => 'فرانسه‌ای که واقعاً حرف زده می‌شه',
				'description' => 'چیزی که درس می‌دم همونیه که توی مترو و مغازه و اداره می‌شنوم، نه فرانسه‌ای که فقط توی کتاب‌ها هست.',
			),
			array(
				'icon'        => 'clock',
				'title'       => 'با ریتم خودت',
				'description' => 'دوره آفلاینه. اگر یه هفته عقب بیفتی هیچ اتفاقی نمی‌افته، چون ویدیوها همیشه سر جاشونن.',
			),
			array(
				'icon'        => 'lifebuoy',
				'title'       => 'تنها نیستی',
				'description' => 'بزرگ‌ترین دلیل نصفه رها کردن زبان، تنهاییه. اینجا هر وقت گیر کنی جواب می‌گیری، تکالیف تصحیح‌شده هست و آخرش خود من هستم.',
			),
		)
	);
}

/**
 * The published catalogue.
 *
 * Mirrors inc/courses.php — the three live courses link through to their own
 * landing pages. Upcoming courses are marked and do not pretend to be on sale.
 *
 * The homepage passes false. It shows only the three courses a visitor can
 * actually buy and sends anyone who wants the rest to /courses/ through the
 * «دوره‌های بیشتر» button, so the section ends within a screen instead of
 * trailing off into «به‌زودی» cards nobody can act on. The section page passes
 * true and stays the full catalogue.
 *
 * @param bool $include_upcoming Whether to append the not-yet-published courses.
 * @return array<int,array<string,string>>
 */
function zandi_courses( $include_upcoming = true ) {
	$catalogue = array();

	foreach ( zandi_courses_data() as $course ) {
		$catalogue[] = array(
			'title'       => $course['short_name'],
			'level'       => $course['level'],
			'duration'    => $course['sessions_text'],
			'sessions'    => $course['hours_text'],
			'description' => $course['subtitle'],
			'badge'       => '',
			'tone'        => 'navy',
			'url'         => zandi_course_url( $course['slug'] ),
			'price'       => $course['price_toman'],
			'cover'       => zandi_course_cover( $course['slug'] ),
		);
	}

	if ( isset( $catalogue[0] ) ) {
		$catalogue[0]['badge'] = 'از صفر شروع کن';
	}

	if ( $include_upcoming ) {
		foreach ( zandi_upcoming_courses() as $soon ) {
			$catalogue[] = array(
				'title'       => $soon['title'],
				'level'       => '',
				'duration'    => 'به‌زودی',
				'sessions'    => '',
				'description' => 'هنوز آماده نشده، ولی نزدیکه.',
				'badge'       => 'به‌زودی',
				'tone'        => 'soft',
				'url'         => '',
				'price'       => 0,
				'cover'       => '',
			);
		}
	}

	return apply_filters( 'zandi_courses', $catalogue, $include_upcoming );
}

/**
 * A course's cover artwork.
 *
 * Supplied by the owner, already rendered at 16:10 — the exact ratio `.thumb`
 * reserves — so nothing is cropped or letterboxed. Returns '' when the file is
 * missing, which puts the card back on its engraved abstract composition rather
 * than a broken image.
 *
 * @param string $slug Course slug.
 * @return string URL, or '' when no cover is installed.
 */
function zandi_course_cover( $slug ) {
	$file = 'assets/images/course-' . sanitize_key( $slug ) . '.webp';

	if ( ! file_exists( get_theme_file_path( $file ) ) ) {
		return '';
	}

	return apply_filters( 'zandi_course_cover', get_theme_file_uri( $file ), $slug );
}

/**
 * Shima's photograph, in the crop the caller needs.
 *
 * Two files, both supplied by the owner already framed as she wants them:
 *
 *   portrait  the full frame — the hero and the course pages
 *   avatar    a square she cropped herself — the round avatar on the teacher card
 *
 * NEITHER IS CROPPED BY THE THEME, at her explicit instruction. An earlier
 * version cropped a square out of the portrait for the avatar and trimmed the
 * Eiffel Tower off the left edge of both, on the strength of the "no Eiffel
 * Tower" line in the design notes. That line is about not leading with the
 * landmark every other language school leads with; it was never a licence to
 * recompose her own photograph. The layout adapts to the files, not the reverse.
 *
 * Returns '' when the file is missing, so a stripped deployment draws the empty
 * state it always did instead of a broken image.
 *
 * @param string $variant 'portrait' or 'avatar'.
 * @return string URL, or '' when the file is not installed.
 */
function zandi_shima_photo( $variant = 'portrait' ) {
	$files = array(
		'portrait' => 'assets/images/shima.webp',
		'avatar'   => 'assets/images/shima-avatar.webp',
	);

	$file = isset( $files[ $variant ] ) ? $files[ $variant ] : $files['portrait'];

	if ( ! file_exists( get_theme_file_path( $file ) ) ) {
		return '';
	}

	return apply_filters( 'zandi_shima_photo', get_theme_file_uri( $file ), $variant );
}

/**
 * About Shima — one teacher, and that is the point.
 *
 * @return array<int,array<string,string>>
 */
function zandi_teachers() {
	return apply_filters(
		'zandi_teachers',
		array(
			array(
				'name'       => 'شیما زندی',
				'role'       => 'مدرس و بنیان‌گذار آکادمی · ساکن پاریس',
				'credential' => 'تدریس فرانسه و راهنمای تور فرانسوی‌زبان',
				'bio'        => 'سال‌هاست فرانسه درس می‌دم و توی پاریس زندگی می‌کنم. توی این سال‌ها بارها دیدم آدم‌هایی که گرامرشون عالی بود ولی جلوی یه فرانسوی خشکشون می‌زد. مشکل دانسته‌شون نبود، مشکل این بود که هیچ‌وقت واقعاً حرف نزده بودن. این دوره‌ها رو دقیقاً برای همین ساختم.',
				'focus'      => 'همه دوره‌ها رو خودم درس می‌دم',
				'image'      => zandi_shima_photo( 'avatar' ),
			),
		)
	);
}

/**
 * How the course works, step by step.
 *
 * @return array<int,array<string,string>>
 */
function zandi_journey() {
	return apply_filters(
		'zandi_journey',
		array(
			array(
				'icon'        => 'target',
				'title'       => 'سطحت رو پیدا کن',
				'description' => 'اگر تا حالا فرانسه نخوندی از پایه شروع کن. مطمئن نیستی؟ از صفحه تماس بپرس تا با هم مشخصش کنیم.',
			),
			array(
				'icon'        => 'clipboard',
				'title'       => 'ثبت‌نام کن',
				'description' => 'از ایران با درگاه بانکی، از خارج با کارت به کارت. بعد از پرداخت بلافاصله دسترسیت فعال می‌شه.',
			),
			array(
				'icon'        => 'play',
				'title'       => 'با ریتم خودت ببین',
				'description' => 'ویدیوها کوتاهن و از روز اول کل دوره رو داری. صبح، شب، توی مترو، هر وقت تونستی.',
			),
			array(
				'icon'        => 'repeat',
				'title'       => 'تمرین بفرست',
				'description' => 'تمرین هر درس رو می‌فرستی و تصحیح‌شده با توضیح برمی‌گرده. هر جا گیر کردی از صفحه تماس بپرس.',
			),
			array(
				'icon'        => 'trending',
				'title'       => 'آخرش با من حرف بزن',
				'description' => 'یه جلسه ۱۵ دقیقه‌ای توی گوگل میت. فرانسه حرف می‌زنیم و می‌گم دقیقاً روی چی باید کار کنی.',
			),
		)
	);
}

/**
 * Student testimonials.
 *
 * TODO — real reviews required before launch. Five genuine ones with a name and
 * city beat twenty invented ones, and a fabricated review destroys trust
 * outright if it is ever noticed. Collect from the Telegram channel and
 * Instagram DMs. Returning an empty array renders the section's empty state.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_testimonials() {
	return apply_filters( 'zandi_testimonials', array() );
}

/**
 * Frequently asked questions.
 *
 * @return array<int,array{question:string,answer:string}>
 */
function zandi_faqs() {
	return apply_filters(
		'zandi_faqs',
		array(
			array(
				'question' => 'نمی‌دونم سطحم چیه، از کدوم دوره شروع کنم؟',
				'answer'   => 'اگر تا حالا فرانسه نخوندی، دوره پایه. اگر خوندی و مطمئن نیستی، از صفحه تماس بهم پیام بده تا با هم مشخصش کنیم. ثبت‌نام توی سطح اشتباه فقط وقت خودت رو می‌گیره.',
			),
			array(
				'question' => 'دوره زنده و آنلاینه؟',
				'answer'   => 'نه. ویدیوها ضبط‌شده‌ن و هر وقت خواستی می‌بینی. ولی پشتیبانی، تصحیح تمرین و مصاحبه پایان دوره کاملاً زنده و انسانیه.',
			),
			array(
				'question' => 'چقدر طول می‌کشه تمومش کنم؟',
				'answer'   => 'بستگی به خودت داره. به طور میانگین حدود ۶ ماه. اگر تندتر بری زودتر، اگر کندتر بری هیچ فشاری نیست.',
			),
			array(
				'question' => 'تا کی به ویدیوها دسترسی دارم؟',
				'answer'   => 'همیشه. دسترسی مادام‌العمره و تاریخ انقضا نداره.',
			),
			array(
				'question' => 'روی چه دستگاهی می‌تونم ببینم؟',
				'answer'   => 'ویندوز، مک و اندروید کامل پشتیبانی می‌شن. لایسنس روی ۲ دستگاه فعال می‌شه. روی آیفون و آیپد فقط از طریق نسخه وب اسپات پلیر امکان‌پذیره که تجربه خوبی نیست و پیشنهادش نمی‌کنم.',
			),
			array(
				'question' => 'چطور ثبت‌نام کنم؟',
				'answer'   => 'از ایران با درگاه بانکی مستقیم روی صفحه هر دوره. از خارج از ایران با یورو و کارت به کارت، برای شماره کارت از صفحه تماس پیام بده. بعد از پرداخت بلافاصله دسترسی برات فعال می‌شه.',
			),
			array(
				'question' => 'پرداخت اقساطی دارید؟',
				'answer'   => 'فعلاً نه. کل مبلغ یک‌جا پرداخت می‌شه.',
			),
			array(
				'question' => 'مصاحبه پایان دوره چطوریه؟',
				'answer'   => 'یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم. با هم فرانسه حرف می‌زنیم، بهت بازخورد می‌دم و می‌گم قدم بعدیت چیه. هماهنگی‌ش از صفحه تماس انجام می‌شه.',
			),
		)
	);
}

/**
 * Closing call to action.
 *
 * @return array<string,mixed>
 */
function zandi_final_cta() {
	return apply_filters(
		'zandi_final_cta',
		array(
			'title'        => 'بذار این‌بار تمومش کنیم',
			'description'  => 'دفعه قبل که فرانسه رو شروع کردی چی شد؟ این‌بار یه مسیر مشخص داری، یه معلم داری و یه نفر هست که جواب سوالات رو بده. فقط باید شروع کنی.',
			'primary'      => array( 'label' => 'دوره‌ها رو ببین', 'url' => zandi_section_url( 'courses' ) ),
			'secondary'    => array( 'label' => 'بپرس', 'url' => zandi_support_url() ),
			'reassurance'  => 'مطمئن نیستی کدوم سطح؟ بپرس، با هم پیداش می‌کنیم.',

			// The panel route resolves signed-in students straight to their
			// courses and everyone else to the login form.
			'account_prompt' => 'قبلاً ثبت‌نام کردی؟',
			'account_action' => 'وارد پنل شو',
		)
	);
}

/**
 * Where the site sends anyone who needs a human.
 *
 * Every «بپرس» on the site points here rather than at a messaging app. That is
 * the whole point: the support channel can move — Telegram to WhatsApp,
 * WhatsApp to a form — by editing zandi_contact() and the one page that renders
 * it, instead of forty strings of copy scattered across sixteen files, which is
 * what it used to take.
 *
 * The rule that keeps it that way: **no copy anywhere on the site may name a
 * channel.** Only zandi_contact() and template-parts/home/contact.php know
 * which one is current.
 *
 * zandi_section_url() falls back to `?zandi_section=contact` when permalinks
 * are «ساده», so this cannot 404 the way a hand-built URL would.
 *
 * @return string
 */
function zandi_support_url() {
	return apply_filters( 'zandi_support_url', zandi_section_url( 'contact' ) );
}

/**
 * Contact details.
 *
 * The single place a support channel is named. Change it here and every
 * «از صفحه تماس بپرس» on the site follows, because none of them name a channel
 * themselves — they all point at /contact/, which renders this.
 *
 * TODO — a public email address is still needed. Empty values are skipped by
 * the footer rather than filled with plausible-looking placeholders.
 *
 * @return array<string,string>
 */
function zandi_contact() {
	return apply_filters(
		'zandi_contact',
		array(
			'telegram'      => 'https://t.me/zandiacademy_fr',
			'telegram_name' => '@zandiacademy_fr',
			'instagram'     => 'https://www.instagram.com/shima_zandi.fr',
			'phone'         => '',
			'phone_href'    => '',
			'email'         => '',
			'address'       => '',
			'hours'         => 'پشتیبانی ۲۴ ساعته',
		)
	);
}

/**
 * Social profiles.
 *
 * TODO — a YouTube channel is mentioned in the brand notes but no URL has been
 * supplied, so it is not listed. A social icon linking nowhere is worse than an
 * absent one.
 *
 * @return array<int,array{icon:string,label:string,url:string}>
 */
function zandi_socials() {
	$contact = zandi_contact();

	return apply_filters(
		'zandi_socials',
		array(
			array( 'icon' => 'instagram', 'label' => 'اینستاگرام', 'url' => $contact['instagram'] ),
			array( 'icon' => 'telegram', 'label' => 'تلگرام', 'url' => $contact['telegram'] ),
		)
	);
}

/**
 * Footer link columns.
 *
 * @return array<int,array{title:string,links:array<int,array{label:string,url:string}>}>
 */
function zandi_footer_columns() {
	$courses = array();

	foreach ( zandi_courses_data() as $course ) {
		$courses[] = array(
			'label' => $course['short_name'],
			'url'   => zandi_course_url( $course['slug'] ),
		);
	}

	return apply_filters(
		'zandi_footer_columns',
		array(
			array(
				'title' => 'دوره‌ها',
				'links' => $courses,
			),
			array(
				'title' => 'آکادمی',
				'links' => array(
					array( 'label' => 'روش تدریس', 'url' => zandi_section_url( 'method' ) ),
					array( 'label' => 'درباره من', 'url' => zandi_section_url( 'about' ) ),
					array( 'label' => 'سوالات متداول', 'url' => zandi_section_url( 'faq' ) ),
					array( 'label' => 'تماس', 'url' => zandi_section_url( 'contact' ) ),
				),
			),
		)
	);
}
