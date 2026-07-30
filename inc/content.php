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
			'secondary'   => array( 'label' => 'روش تدریسم رو ببین', 'url' => zandi_section_url( 'method' ) ),
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
				'label'   => 'پشتیبانی در تلگرام',
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
				'description' => 'بزرگ‌ترین دلیل نصفه رها کردن زبان، تنهاییه. اینجا کانال تلگرام هست، تکالیف تصحیح‌شده هست و آخرش خود من هستم.',
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
 * @return array<int,array<string,string>>
 */
function zandi_courses() {
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

	return apply_filters( 'zandi_courses', $catalogue );
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
				'description' => 'اگر تا حالا فرانسه نخوندی از پایه شروع کن. مطمئن نیستی؟ توی تلگرام بپرس تا با هم مشخصش کنیم.',
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
				'description' => 'تمرین هر درس رو می‌فرستی و تصحیح‌شده با توضیح برمی‌گرده. هر جا گیر کردی توی تلگرام بپرس.',
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
				'answer'   => 'اگر تا حالا فرانسه نخوندی، دوره پایه. اگر خوندی و مطمئن نیستی، توی تلگرام بهم پیام بده تا با هم مشخصش کنیم. ثبت‌نام توی سطح اشتباه فقط وقت خودت رو می‌گیره.',
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
				'answer'   => 'از ایران با درگاه بانکی مستقیم روی صفحه هر دوره. از خارج از ایران با یورو و کارت به کارت، برای شماره کارت توی تلگرام پیام بده. بعد از پرداخت بلافاصله دسترسی برات فعال می‌شه.',
			),
			array(
				'question' => 'پرداخت اقساطی دارید؟',
				'answer'   => 'فعلاً نه. کل مبلغ یک‌جا پرداخت می‌شه.',
			),
			array(
				'question' => 'مصاحبه پایان دوره چطوریه؟',
				'answer'   => 'یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم. با هم فرانسه حرف می‌زنیم، بهت بازخورد می‌دم و می‌گم قدم بعدیت چیه. هماهنگی‌ش توی تلگرام انجام می‌شه.',
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
			'secondary'    => array( 'label' => 'توی تلگرام بپرس', 'url' => 'https://t.me/zandiacademy_fr' ),
			'reassurance'  => 'مطمئن نیستی کدوم سطح؟ بپرس، با هم پیداش می‌کنیم.',

			// The panel route resolves signed-in students straight to their
			// courses and everyone else to the login form.
			'account_prompt' => 'قبلاً ثبت‌نام کردی؟',
			'account_action' => 'وارد پنل شو',
		)
	);
}

/**
 * Contact details.
 *
 * Telegram is the academy's real support channel — questions, level checks,
 * exercise corrections and interview scheduling all happen there — so it is the
 * primary contact everywhere on the site.
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
			'hours'         => 'پشتیبانی تلگرام، ۲۴ ساعته',
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
