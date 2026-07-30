<?php
/**
 * Course data — the single source for every course landing page.
 *
 * `/courses/{slug}` is one template driven by this file. Adding a fourth course
 * means adding one entry to zandi_courses_data() and nothing else.
 *
 * Copy is reproduced verbatim from the approved Persian copy document. Do not
 * rewrite or translate it. Editorial notes from that document (the pre-launch
 * warnings, the pricing commentary) are guidance for the team and deliberately
 * do NOT appear anywhere on the site.
 *
 * NEVER claim on any page: a money-back guarantee, installment payments, or a
 * completion certificate. The academy offers none of the three.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * All courses, keyed by slug.
 *
 * @return array<string,array<string,mixed>>
 */
function zandi_courses_data() {
	return apply_filters(
		'zandi_courses_data',
		array(

			'a1' => array(
				'slug'        => 'a1',
				'level'       => 'A1',
				'eyebrow'     => 'سطح پایه · A1',
				'short_name'  => 'دوره پایه A1',
				'title'       => 'از صفر فرانسه یاد بگیر، بدون اینکه اول از همه بترسی 🇫🇷',
				'subtitle'    => 'اگر تا حالا حتی یک کلمه فرانسه بلد نیستی، اینجا دقیقاً جای درستیه. با من از حرف اول شروع می‌کنیم و تا جایی می‌ریم که بتونی خودت رو معرفی کنی، خرید کنی، آدرس بپرسی و یه گفتگوی ساده رو تا آخر ببری.',
				'cta_primary' => 'ثبت‌نام در دوره پایه',

				// Session count leads: it is the strongest number on every card.
				'sessions'      => 78,
				'sessions_text' => '۷۸ جلسه ویدیویی',
				'hours_text'    => '۱۶ ساعت آموزش',

				'price_toman' => 8970000,
				'price_euro'  => 219,

				'about_title' => 'بیا یک بار برای همیشه، درست شروع کنیم',
				'about_body'  => array(
					'خیلی‌هامون فرانسه رو یه بار شروع کردیم و ول کردیم. یه اپلیکیشن، چند تا ویدیو یوتیوب، یه کلاس که سرش شلوغ بود. مشکل تو نبودی، مشکل این بود که هیچ‌کدوم از اینا یه مسیر نداشتن.',
					'دوره پایه یه مسیر داره. از الفبا و تلفظ شروع می‌کنه و قدم‌به‌قدم می‌رسه به جایی که بتونی درباره خودت، خانواده‌ت، کارت و روز‌مرگی‌هات حرف بزنی. هر چیزی که یاد می‌گیری بلافاصله توی یه جمله واقعی استفاده می‌شه، چون هدف این نیست که گرامر بلد باشی، هدف اینه که حرف بزنی.',
				),
				'outcomes'    => array(
					'خودت و آدم‌های اطرافت رو معرفی کنی',
					'توی مغازه، کافه و رستوران خرید کنی و سفارش بدی',
					'آدرس بپرسی و آدرس بدی',
					'درباره علاقه‌ها، کار و برنامه روزانه‌ت حرف بزنی',
					'جمله‌های ساده رو بخونی و بنویسی',
					'یه گفتگوی کوتاه رو بدون فرار کردن تا آخر ببری',
				),

				'who_for'     => array(
					'کسی که از صفر شروع می‌کنه و هیچ پیش‌زمینه‌ای نداره',
					'کسی که قبلاً چند بار شروع کرده و نصفه ول کرده',
					'کسی که برای مهاجرت یا تحصیل تازه دارد اولین قدم را برمی‌دارد',
					'کسی که وقت کلاس حضوری نداره ولی می‌خواد جدی پیش بره',
				),
				'who_not_for' => array(
					'کسی که قبلاً فرانسه خونده و می‌تونه جمله بسازه (برو سراغ A2)',
					'کسی که فقط دنبال چند تا جمله برای سفر یک‌هفته‌ایه',
					'کسی که دنبال دوره فوری برای آزمون تا یک ماه دیگه‌ست',
				),

				'closing_title' => 'بذار این‌بار تمومش کنیم',
				'closing_body'  => 'دفعه قبل که فرانسه رو شروع کردی چی شد؟ این‌بار یه مسیر مشخص داری، یه معلم داری و یه نفر هست که جواب سوالات رو بده. فقط باید شروع کنی.',

				/*
				 * Only A1 carries this question — a beginner asking it is the
				 * whole point of the page.
				 */
				'extra_faq' => array(
					array(
						'q' => 'هیچی بلد نیستم، می‌تونم شروع کنم؟',
						'a' => 'بله. دوره پایه دقیقاً از حرف اول شروع می‌شه و هیچ پیش‌نیازی نداره.',
					),
				),

				// TODO: real curriculum needed for all three levels before launch.
				'curriculum' => array(),

				'meta_description' => 'دوره پایه زبان فرانسه A1 با شیما زندی؛ ۷۸ جلسه ویدیویی، ۱۶ ساعت آموزش، پشتیبانی ۲۴ ساعته و مصاحبه پایان دوره. از صفر تا گفتگوی ساده.',
			),

			'a2' => array(
				'slug'        => 'a2',
				'level'       => 'A2',
				'eyebrow'     => 'سطح متوسط · A2',
				'short_name'  => 'دوره متوسط A2',
				'title'       => 'دیگه وقتشه از جمله‌های کوتاه بیای بیرون 🇫🇷',
				'subtitle'    => 'پایه رو بلدی ولی وسط حرف زدن گیر می‌کنی؟ توی این دوره یاد می‌گیری از گذشته و آینده حرف بزنی، نظرت رو بگی، ماجرا تعریف کنی و جمله‌هات رو به هم وصل کنی. اون چیزی که باعث می‌شه حرف زدنت شبیه یه آدم واقعی بشه، نه شبیه کتاب.',
				'cta_primary' => 'ثبت‌نام در دوره متوسط',

				'sessions'      => 100,
				'sessions_text' => '۱۰۰ جلسه ویدیویی',
				'hours_text'    => 'نزدیک به ۹ ساعت آموزش',

				'price_toman' => 9970000,
				'price_euro'  => 249,

				'about_title' => 'جایی که فرانسه‌ت از «بلدم» می‌رسه به «می‌تونم»',
				'about_body'  => array(
					'سطح A1 بهت ابزار داد. A2 بهت آزادی می‌ده.',
					'توی این دوره می‌ری سراغ چیزهایی که تا حالا نداشتی و همیشه جات خالی بود: حرف زدن از گذشته، برنامه‌ریزی برای آینده، گفتن نظر و دلیل، تعریف کردن یه اتفاق از اول تا آخر. اینا همون چیزهاییه که باعث می‌شه یه مکالمه واقعی بیشتر از سه جمله دوام بیاره.',
				),
				'outcomes'    => array(
					'از اتفاق‌های گذشته و خاطراتت تعریف کنی',
					'درباره برنامه‌ها و آرزوهات حرف بزنی',
					'نظرت رو بگی و برایش دلیل بیاری',
					'توی موقعیت‌های اداری و روزمره از پس خودت بربیای',
					'ایمیل و پیام‌های غیررسمی بنویسی',
					'فیلم و پادکست ساده رو تا حد خوبی بفهمی',
				),

				'who_for'     => array(
					'کسی که A1 رو تموم کرده',
					'کسی که پایه رو بلده ولی وسط حرف زدن گیر می‌کنه',
					'کسی که برای مهاجرت باید سطحش رو بالاتر ببره',
					'کسی که سال‌ها پیش فرانسه خونده و می‌خواد برگرده',
				),
				'who_not_for' => array(
					'کسی که هنوز الفبا و تلفظ رو کامل نمی‌شناسه (اول A1)',
					'کسی که راحت درباره گذشته و آینده حرف می‌زنه (برو B1)',
				),

				'closing_title' => 'وقتشه از جمله‌های کوتاه بیای بیرون',
				'closing_body'  => 'تو پایه رو بلدی. چیزی که کم داری آزادیه، و دقیقاً همون چیزیه که این دوره بهت می‌ده.',

				'extra_faq'  => array(),
				'curriculum' => array(),

				'meta_description' => 'دوره متوسط زبان فرانسه A2 با شیما زندی؛ ۱۰۰ جلسه ویدیویی، پشتیبانی ۲۴ ساعته و مصاحبه پایان دوره. از جمله‌های کوتاه به مکالمه واقعی.',
			),

			'b1' => array(
				'slug'        => 'b1',
				'level'       => 'B1',
				'eyebrow'     => 'سطح پیشرفته · B1',
				'short_name'  => 'دوره پیشرفته B1',
				'title'       => 'فرانسه‌ت خوبه، حالا بیا روان و بی‌مکث حرف بزن 🇫🇷',
				'subtitle'    => 'این سطحیه که ازش به بعد فرانسه از «درس» تبدیل می‌شه به «زبان». بحث می‌کنی، مخالفت می‌کنی، از تجربه‌هات می‌گی و منظورت رو دقیق می‌رسونی. برای کسی که می‌خواد توی فرانسه کار کنه، درس بخونه یا زندگی کنه، این همون نقطه‌ست.',
				'cta_primary' => 'ثبت‌نام در دوره پیشرفته',

				'sessions'      => 59,
				'sessions_text' => '۵۹ جلسه ویدیویی',
				'hours_text'    => 'نزدیک به ۹ ساعت و نیم آموزش',

				'price_toman' => 11970000,
				'price_euro'  => 319,

				'about_title' => 'از این‌جا به بعد، فرانسه زبان توئه',
				'about_body'  => array(
					'B1 نقطه‌ایه که خیلی چیزها عوض می‌شه. سفارت‌ها اینجا رو جدی می‌گیرن، دانشگاه‌ها اینجا رو می‌خوان و مهم‌تر از همه، خودت اینجا احساس می‌کنی بالاخره داری زندگی می‌کنی نه ترجمه.',
					'توی این دوره روی چیزی کار می‌کنیم که سخت‌ترین قسمت زبانه: دقت. اینکه دقیقاً همون چیزی رو بگی که منظورته، نه نزدیک‌ترین چیزی که بلدی. بحث می‌کنیم، مخالفت می‌کنیم، توضیح می‌دیم و حرف‌ها رو به هم وصل می‌کنیم.',
				),
				'outcomes'    => array(
					'توی یه بحث شرکت کنی و از نظرت دفاع کنی',
					'تجربه‌ها و احساساتت رو با جزئیات تعریف کنی',
					'متن‌های واقعی مثل خبر و مقاله رو بخونی',
					'نامه و متن نسبتاً رسمی بنویسی',
					'توی محیط کار و دانشگاه از پس مکالمه بربیای',
					'فیلم و پادکست فرانسوی رو دنبال کنی',
				),

				'who_for'     => array(
					'کسی که A2 رو تموم کرده',
					'کسی که برای کار یا تحصیل توی فرانسه آماده می‌شه',
					'کسی که می‌خواد از حالت «می‌فهمم ولی نمی‌تونم بگم» بیاد بیرون',
					'کسی که فرانسه‌ش خوبه ولی روان نیست',
				),
				'who_not_for' => array(
					'کسی که هنوز جمله‌های گذشته براش سخته (اول A2)',
					'کسی که دنبال دوره تخصصی آزمون است',
				),

				'closing_title' => 'بیا فرانسه رو تموم کنیم',
				'closing_body'  => 'این آخرین قدم بین «فرانسه بلدم» و «فرانسه زندگی می‌کنم».',

				'extra_faq'  => array(),
				'curriculum' => array(),

				'meta_description' => 'دوره پیشرفته زبان فرانسه B1 با شیما زندی؛ ۵۹ جلسه ویدیویی، پشتیبانی ۲۴ ساعته و مصاحبه پایان دوره. از فهمیدن تا حرف زدن روان.',
			),
		)
	);
}

/**
 * One course by slug.
 *
 * @param string $slug Course slug.
 * @return array<string,mixed>|null
 */
function zandi_get_course( $slug ) {
	$courses = zandi_courses_data();

	return isset( $courses[ $slug ] ) ? $courses[ $slug ] : null;
}

/**
 * Courses that are announced but not yet on sale.
 *
 * Rendered as "به‌زودی" cards with an email capture in the other-courses row.
 *
 * @return array<int,array<string,string>>
 */
function zandi_upcoming_courses() {
	return apply_filters(
		'zandi_upcoming_courses',
		array(
			array( 'title' => 'دوره فرانسه برای مهاجران' ),
			array( 'title' => 'دوره مکالمه A1 · A2 · B1' ),
		)
	);
}

/* -------------------------------------------------------------------------
 * Shared copy — identical on all three pages.
 * ---------------------------------------------------------------------- */

/**
 * Trust bar items.
 *
 * @return array<int,string>
 */
function zandi_course_trust_items() {
	return apply_filters(
		'zandi_course_trust_items',
		array(
			'۱۴۲ هزار دنبال‌کننده در اینستاگرام',
			'تدریس از پاریس',
			'متد مکالمه‌محور',
			'پشتیبانی ۲۴/۷',
		)
	);
}

/**
 * The "what you get" cards.
 *
 * @return array<int,array{icon:string,title:string,body:string}>
 */
function zandi_course_deliverables() {
	return apply_filters(
		'zandi_course_deliverables',
		array(
			array(
				'icon'  => 'play',
				'title' => 'جلسات ویدیویی',
				'body'  => 'ویدیوها کوتاهن، بین چند دقیقه تا یه ربع. عمداً این‌طوری ضبطشون کردم چون تمرکز آدم بعد از یه ربع می‌پره و ویدیوی چهل دقیقه‌ای فقط باعث می‌شه عقب بیفتی و بی‌خیال بشی. اینجا هر جلسه یه موضوع مشخص داره و تمومش می‌کنی. همه ویدیوها روی اسپات پلیر آپلود شدن و از همون روز اول کل دوره رو داری.',
			),
			array(
				'icon'  => 'clipboard',
				'title' => 'جزوه و تمرین',
				'body'  => 'هر درس جزوه خودش رو داره، به علاوه تمرین‌هایی که مجبورت می‌کنن چیزی که یاد گرفتی رو واقعاً استفاده کنی. تمرین‌ها رو می‌فرستی و ادمین‌ها تصحیح‌شده برات پس می‌فرستن.',
			),
			array(
				'icon'  => 'chat',
				'title' => 'پشتیبانی ۲۴ ساعته',
				'body'  => 'یه کانال تلگرام اختصاصی برای همین دوره. هر ساعتی از شبانه‌روز سوال داشتی بپرس. اینجا کسی بابت سوال ساده پرسیدن قضاوت نمی‌شه.',
			),
			array(
				'icon'  => 'users',
				'title' => 'مصاحبه پایان دوره با من',
				'body'  => 'آخر دوره یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم داری. فرانسه حرف می‌زنیم، اشکالاتت رو می‌گیرم و بهت می‌گم دقیقاً روی چی باید کار کنی. این تنها راهیه که بفهمی واقعاً کجا ایستادی.',
			),
		)
	);
}

/**
 * Teaching-method blocks.
 *
 * @return array<int,array{title:string,body:string}>
 */
function zandi_course_method() {
	return apply_filters(
		'zandi_course_method',
		array(
			array(
				'title' => 'مکالمه‌محور، نه گرامرمحور',
				'body'  => 'گرامر یاد می‌گیریم، ولی نه اینکه اول شش جلسه قاعده حفظ کنی و بعد بخوای حرف بزنی. از همون جلسه اول جمله می‌سازی. گرامر پشت جمله میاد، نه جلوش.',
			),
			array(
				'title' => 'فرانسه‌ای که واقعاً حرف زده می‌شه',
				'body'  => 'من توی پاریس زندگی می‌کنم. چیزی که درس می‌دم همونیه که توی مترو و مغازه و اداره می‌شنوم، نه فرانسه‌ای که فقط توی کتاب‌ها وجود داره.',
			),
			array(
				'title' => 'با ریتم خودت',
				'body'  => 'دوره آفلاینه. صبح، شب، سر کار، توی مترو، هر وقت که تونستی. اگر یه هفته عقب بیفتی هیچ اتفاقی نمی‌افته، چون ویدیوها همیشه سر جاشونن.',
			),
			array(
				'title' => 'تنها نیستی',
				'body'  => 'بزرگ‌ترین دلیل نصفه رها کردن زبان، تنهاییه. اینجا کانال تلگرام هست، تکالیف تصحیح‌شده هست و آخرش خود من هستم.',
			),
		)
	);
}

/**
 * The course-info box rows, in order.
 *
 * The first two are per-course; the rest are identical everywhere.
 *
 * @param array $course Course data.
 * @return array<int,array{icon:string,text:string}>
 */
function zandi_course_info_rows( $course ) {
	return apply_filters(
		'zandi_course_info_rows',
		array(
			array( 'icon' => '🎬', 'text' => $course['sessions_text'] ),
			array( 'icon' => '⏱️', 'text' => $course['hours_text'] ),
			array( 'icon' => '📘', 'text' => 'جزوه، تمرین و فایل تکمیلی' ),
			array( 'icon' => '👤', 'text' => 'تدریس شخص شیما زندی' ),
			array( 'icon' => '♾️', 'text' => 'دسترسی مادام‌العمر' ),
			array( 'icon' => '💬', 'text' => 'پشتیبانی ۲۴ ساعته در تلگرام' ),
			array( 'icon' => '🎤', 'text' => 'مصاحبه ۱۵ دقیقه‌ای پایان دوره' ),
		),
		$course
	);
}

/**
 * Shared FAQ.
 *
 * Two questions from the copy document are deliberately absent:
 *
 *   TODO — «این دوره برای آزمون TCF یا DELF خوبه؟» needs a definitive answer
 *   from Shima before it can be published.
 *
 *   TODO — «اگر بعد از خرید پشیمون بشم چی؟» needs a refund decision. The copy
 *   document is explicit that a question whose answer is "no" is better left
 *   unasked, so it stays off the page until there is a policy.
 *
 * Both are recorded here rather than rendered as empty placeholders: an
 * unanswered refund question on a sales page costs more trust than it earns.
 *
 * @return array<int,array{q:string,a:string}>
 */
function zandi_course_faq() {
	return apply_filters(
		'zandi_course_faq',
		array(
			array(
				'q' => 'نمی‌دونم سطحم چیه، از کدوم دوره شروع کنم؟',
				'a' => 'اگر تا حالا فرانسه نخوندی، A1. اگر خوندی و مطمئن نیستی، توی تلگرام بهم پیام بده تا با هم مشخصش کنیم. ثبت‌نام توی سطح اشتباه فقط وقت خودت رو می‌گیره.',
			),
			array(
				'q' => 'دوره زنده و آنلاینه؟',
				'a' => 'نه. ویدیوها ضبط‌شده‌ن و هر وقت خواستی می‌بینی. ولی پشتیبانی، تصحیح تمرین و مصاحبه پایان دوره کاملاً زنده و انسانیه.',
			),
			array(
				'q' => 'چقدر طول می‌کشه تمومش کنم؟',
				'a' => 'بستگی به خودت داره. به طور میانگین حدود ۶ ماه. اگر تندتر بری زودتر، اگر کندتر بری هیچ فشاری نیست.',
			),
			array(
				'q' => 'تا کی به ویدیوها دسترسی دارم؟',
				'a' => 'همیشه. دسترسی مادام‌العمره و تاریخ انقضا نداره.',
			),
			array(
				'q' => 'روی چه دستگاهی می‌تونم ببینم؟',
				'a' => 'ویندوز، مک و اندروید کامل پشتیبانی می‌شن. لایسنس روی ۲ دستگاه فعال می‌شه. ⚠️ روی آیفون و آیپد فقط از طریق نسخه وب اسپات پلیر امکان‌پذیره که تجربه خوبی نیست و پیشنهادش نمی‌کنیم. اگر لپ‌تاپ یا گوشی اندروید داری، از اون استفاده کن.',
			),
			array(
				'q' => 'چطور ثبت‌نام کنم؟',
				'a' => 'از ایران با درگاه بانکی مستقیم روی همین صفحه. از خارج از ایران با یورو و کارت به کارت، برای شماره کارت توی تلگرام پیام بده. بعد از پرداخت بلافاصله دسترسی برات فعال می‌شه.',
			),
			array(
				'q' => 'پرداخت اقساطی دارید؟',
				'a' => 'فعلاً نه. کل مبلغ یک‌جا پرداخت می‌شه.',
			),
			array(
				'q' => 'تکالیف رو کی تصحیح می‌کنه؟',
				'a' => 'ادمین‌های آکادمی. تکلیف رو توی تلگرام می‌فرستی و تصحیح‌شده با توضیح برمی‌گرده.',
			),
			array(
				'q' => 'مصاحبه پایان دوره چطوریه؟',
				'a' => 'یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم. با هم فرانسه حرف می‌زنیم، بهت بازخورد می‌دم و می‌گم قدم بعدیت چیه. هماهنگی‌ش توی تلگرام انجام می‌شه.',
			),
		)
	);
}

/**
 * Student testimonials.
 *
 * TODO — real testimonials required before launch. The copy document is
 * emphatic: five genuine reviews with a name and city beat twenty invented
 * ones, and a fabricated review destroys trust outright if noticed. Collect
 * from the Telegram channel and Instagram DMs.
 *
 * Returning an empty array renders the section's empty state.
 *
 * @return array<int,array{quote:string,name:string,role:string,city:string,rating:int}>
 */
function zandi_course_testimonials() {
	return apply_filters( 'zandi_course_testimonials', array() );
}

/**
 * About-Shima copy.
 *
 * @return array<string,mixed>
 */
function zandi_about_shima() {
	return apply_filters(
		'zandi_about_shima',
		array(
			'title' => 'من کی‌ام و چرا این دوره رو ساختم',
			'body'  => array(
				'سلام، من شیما زندی‌ام 👋',
				'سال‌هاست فرانسه درس می‌دم و توی پاریس زندگی می‌کنم. کنارش راهنمای تور فرانسوی‌زبان بودم، یعنی سال‌ها کارم این بوده که آدم‌ها رو به هم وصل کنم با زبانی که بلد نیستن.',
				'توی این سال‌ها یه چیز رو بارها دیدم: آدم‌هایی که گرامرشون عالی بود ولی جلوی یه فرانسوی خشکشون می‌زد. مشکل دانسته‌شون نبود، مشکل این بود که هیچ‌وقت واقعاً حرف نزده بودن.',
				'این دوره‌ها رو دقیقاً برای همین ساختم. اینجا از جلسه اول حرف می‌زنی، حتی اگه غلط باشه. غلط گفتن قدم اوله، سکوت هیچ قدمی نیست.',
			),
			'photo' => zandi_shima_photo( 'portrait' ),
		)
	);
}

/*
 * There was a zandi_course_nav() here, feeding a course-only header whose items
 * were on-page anchors — and whose پادکست and وبلاگ entries pointed at '#'.
 * Course pages now use the site header, so the navigation is zandi_navigation()
 * in inc/content.php and every item is a real page. The two labels with no
 * destination were dropped rather than pointed somewhere arbitrary.
 *
 * TODO: reinstate پادکست and وبلاگ as sections in zandi_sections() once there is
 * something to link to.
 */
