<?php
/**
 * Single source of truth for the homepage copy.
 *
 * Port of the previous `src/data/content.js`. Templates read from here rather
 * than hard-coding strings, so this stays the one file to swap when the copy
 * moves into ACF fields, the Customizer or the REST API.
 *
 * Every getter is filterable, which is the seam a child theme or plugin uses to
 * override a section without touching a template.
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
			'tagline'     => 'آموزش تخصصی زبان فرانسه',
			'description' => 'آکادمی زندی تنها بر آموزش زبان فرانسه تمرکز دارد؛ با اساتید حرفه‌ای، کلاس‌های کم‌جمعیت و برنامه‌ای که دقیقاً برای هدف شما نوشته می‌شود.',
		)
	);
}

/**
 * Fallback navigation, used when no menu is assigned to the `primary` location.
 *
 * @return array<int,array{label:string,url:string}>
 */
function zandi_fallback_nav() {
	return apply_filters(
		'zandi_fallback_nav',
		array(
			array(
				'label' => 'خانه',
				'url'   => '#home',
			),
			array(
				'label' => 'دوره‌ها',
				'url'   => '#courses',
			),
			array(
				'label' => 'اساتید',
				'url'   => '#teachers',
			),
			array(
				'label' => 'درباره ما',
				'url'   => '#about',
			),
			array(
				'label' => 'سوالات متداول',
				'url'   => '#faq',
			),
			array(
				'label' => 'تماس',
				'url'   => '#contact',
			),
		)
	);
}

/**
 * Hero copy.
 *
 * @return array<string,mixed>
 */
function zandi_hero() {
	return apply_filters(
		'zandi_hero',
		array(
			'badge'       => 'ثبت‌نام ترم پاییز آغاز شد',
			'title'       => 'فرانسوی را مثل یک زبان مادری یاد بگیرید.',
			'description' => 'در آکادمی زندی فقط فرانسه تدریس می‌کنیم. کلاس‌های کوچک و تعاملی، اساتید مسلط و مسیر آموزشی اختصاصی، از اولین «Bonjour» تا گفت‌وگوی روان و مدرک بین‌المللی.',
			'primary'     => array(
				'label' => 'ثبت نام رایگان',
				'url'   => '#register',
			),
			'secondary'   => array(
				'label' => 'مشاهده دوره‌ها',
				'url'   => '#courses',
			),
			'highlights'  => array(
				'کلاس‌های حداکثر ۶ نفره',
				'جلسه مشاوره و تعیین سطح رایگان',
				'آمادگی آزمون‌های TEF و TCF',
			),
		)
	);
}

/**
 * Trust metrics.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_stats() {
	return apply_filters(
		'zandi_stats',
		array(
			array(
				'icon'    => 'users',
				'value'   => 4800,
				'suffix'  => '+',
				'label'   => 'زبان‌آموز فعال',
				'caption' => 'از سراسر ایران و خارج از کشور',
			),
			array(
				'icon'    => 'graduation',
				'value'   => 2600,
				'suffix'  => '',
				'label'   => 'فارغ‌التحصیل',
				'caption' => 'با مدرک سطح B1 و بالاتر',
			),
			array(
				'icon'    => 'calendar',
				'value'   => 14,
				'suffix'  => '',
				'label'   => 'سال تدریس تخصصی',
				'caption' => 'تمرکز کامل روی زبان فرانسه',
			),
			array(
				'icon'    => 'heart',
				'value'   => 98,
				'suffix'  => '٪',
				'label'   => 'رضایت زبان‌آموزان',
				'caption' => 'بر پایه نظرسنجی پایان هر ترم',
			),
		)
	);
}

/**
 * Why-choose-us cards.
 *
 * @return array<int,array<string,string>>
 */
function zandi_features() {
	return apply_filters(
		'zandi_features',
		array(
			array(
				'icon'        => 'sparkles',
				'title'       => 'اساتید حرفه‌ای',
				'description' => 'همه اساتید ما دارای مدرک تدریس فرانسه و تجربه زندگی یا تحصیل در کشورهای فرانسه‌زبان هستند.',
			),
			array(
				'icon'        => 'chat',
				'title'       => 'کلاس‌های تعاملی',
				'description' => 'کلاس‌ها حداکثر شش نفره است؛ بیشتر وقت کلاس صرف صحبت کردن شما می‌شود، نه گوش دادن به استاد.',
			),
			array(
				'icon'        => 'lifebuoy',
				'title'       => 'پشتیبانی کامل',
				'description' => 'بین جلسات هم تنها نیستید. رفع اشکال، بازخورد تمرین‌ها و پیگیری پیشرفت به‌صورت هفتگی انجام می‌شود.',
			),
			array(
				'icon'        => 'route',
				'title'       => 'برنامه آموزشی اختصاصی',
				'description' => 'مسیر شما بر اساس هدفتان نوشته می‌شود؛ مهاجرت، تحصیل، کار یا علاقه شخصی — هر کدام برنامه خودش را دارد.',
			),
		)
	);
}

/**
 * Featured courses.
 *
 * @return array<int,array<string,string>>
 */
function zandi_courses() {
	return apply_filters(
		'zandi_courses',
		array(
			array(
				'title'       => 'فرانسه از صفر',
				'level'       => 'A1',
				'duration'    => '۱۲ هفته',
				'sessions'    => '۲۴ جلسه',
				'description' => 'شروع اصولی با تلفظ درست، الفبا و جمله‌سازی؛ در پایان دوره خودتان را معرفی می‌کنید و گفت‌وگوی ساده دارید.',
				'badge'       => 'محبوب‌ترین',
				'tone'        => 'navy',
				'url'         => '#register',
			),
			array(
				'title'       => 'فرانسه مقدماتی',
				'level'       => 'A2',
				'duration'    => '۱۲ هفته',
				'sessions'    => '۲۴ جلسه',
				'description' => 'گسترش واژگان روزمره و زمان‌های گذشته؛ مکالمه درباره سفر، خرید، کار و برنامه‌های شخصی.',
				'badge'       => '',
				'tone'        => 'soft',
				'url'         => '#register',
			),
			array(
				'title'       => 'فرانسه متوسط',
				'level'       => 'B1',
				'duration'    => '۱۶ هفته',
				'sessions'    => '۳۲ جلسه',
				'description' => 'ورود به گفت‌وگوی روان، بیان نظر و استدلال؛ سطحی که بیشتر دانشگاه‌ها و ادارات مهاجرتی می‌خواهند.',
				'badge'       => '',
				'tone'        => 'deep',
				'url'         => '#register',
			),
			array(
				'title'       => 'فرانسه پیشرفته',
				'level'       => 'B2',
				'duration'    => '۱۶ هفته',
				'sessions'    => '۳۲ جلسه',
				'description' => 'تسلط بر متن‌های تخصصی، بحث آزاد و نگارش رسمی؛ آماده برای تحصیل و کار در محیط فرانسه‌زبان.',
				'badge'       => '',
				'tone'        => 'navy',
				'url'         => '#register',
			),
			array(
				'title'       => 'آمادگی آزمون TEF و TCF',
				'level'       => 'Exam',
				'duration'    => '۸ هفته',
				'sessions'    => '۱۶ جلسه',
				'description' => 'تمرین فشرده چهار مهارت با آزمون‌های شبیه‌سازی‌شده و بازخورد جزئی روی هر بخش.',
				'badge'       => 'فشرده',
				'tone'        => 'deep',
				'url'         => '#register',
			),
			array(
				'title'       => 'مکالمه با استاد فرانسوی‌زبان',
				'level'       => 'Conversation',
				'duration'    => 'ترمی ۸ هفته',
				'sessions'    => '۱۶ جلسه',
				'description' => 'کلاس گفت‌وگومحور برای کسانی که پایه دارند و می‌خواهند لهجه و روانی کلامشان حرفه‌ای شود.',
				'badge'       => '',
				'tone'        => 'soft',
				'url'         => '#register',
			),
		)
	);
}

/**
 * Instructor band.
 *
 * The header links to «اساتید», so the homepage carries a short instructor
 * section. Add an `image` key and Avatar swaps initials for the photograph.
 *
 * @return array<int,array<string,string>>
 */
function zandi_teachers() {
	return apply_filters(
		'zandi_teachers',
		array(
			array(
				'name'       => 'سمیه زندی',
				'role'       => 'مؤسس آکادمی و مدرس ارشد',
				'credential' => 'DAES II — دانشگاه سوربن',
				'bio'        => 'چهارده سال تدریس فرانسه و طراحی برنامه درسی آکادمی؛ متخصص رساندن زبان‌آموز از پایه به سطح B2.',
				'focus'      => 'دوره‌های B1 و B2',
				'image'      => '',
			),
			array(
				'name'       => 'رضا افشار',
				'role'       => 'مدرس آزمون‌های بین‌المللی',
				'credential' => 'ممتحن رسمی TEF و TCF',
				'bio'        => 'هشت سال آماده‌سازی داوطلبان مهاجرت؛ تمرکز روی استراتژی آزمون و بالا بردن نمره در کوتاه‌ترین زمان.',
				'focus'      => 'آمادگی TEF و TCF',
				'image'      => '',
			),
			array(
				'name'       => 'Camille Rousseau',
				'role'       => 'مدرس مکالمه، فرانسوی‌زبان',
				'credential' => 'FLE — دانشگاه لیون ۲',
				'bio'        => 'متولد لیون و ساکن تهران؛ کلاس‌های گفت‌وگومحور برای طبیعی شدن لهجه و روان شدن کلام.',
				'focus'      => 'کارگاه‌های مکالمه',
				'image'      => '',
			),
		)
	);
}

/**
 * Learning-journey steps.
 *
 * @return array<int,array<string,string>>
 */
function zandi_journey() {
	return apply_filters(
		'zandi_journey',
		array(
			array(
				'icon'        => 'clipboard',
				'title'       => 'ثبت نام',
				'description' => 'فرم کوتاه را پر می‌کنید و مشاور آموزشی در کمتر از یک روز کاری با شما تماس می‌گیرد.',
			),
			array(
				'icon'        => 'target',
				'title'       => 'تعیین سطح',
				'description' => 'یک جلسه رایگان گفت‌وگو و آزمون کوتاه، تا دقیقاً از نقطه درست شروع کنید.',
			),
			array(
				'icon'        => 'play',
				'title'       => 'شروع کلاس',
				'description' => 'در گروه هم‌سطح خودتان قرار می‌گیرید و کلاس‌ها با برنامه مشخص آغاز می‌شود.',
			),
			array(
				'icon'        => 'repeat',
				'title'       => 'تمرین',
				'description' => 'تمرین هفتگی، کارگاه مکالمه و بازخورد شخصی استاد روی نقاط ضعف شما.',
			),
			array(
				'icon'        => 'trending',
				'title'       => 'پیشرفت',
				'description' => 'کارنامه پیشرفت هر ماه، و در پایان دوره مدرک سطح و مسیر ترم بعد.',
			),
		)
	);
}

/**
 * Student testimonials.
 *
 * @return array<int,array<string,mixed>>
 */
function zandi_testimonials() {
	return apply_filters(
		'zandi_testimonials',
		array(
			array(
				'name'   => 'سارا مرادی',
				'role'   => 'دانشجوی ارشد در لیون',
				'rating' => 5,
				'quote'  => 'از سطح صفر شروع کردم و بعد از سه ترم مدرک B2 گرفتم. چیزی که فرق داشت، تمرکز کلاس روی حرف زدن بود؛ در جلسه دوم مجبور بودم فرانسه صحبت کنم و همین ترسم را ریخت.',
			),
			array(
				'name'   => 'امیرحسین کاظمی',
				'role'   => 'مهندس نرم‌افزار، مقیم مونترال',
				'rating' => 5,
				'quote'  => 'برای پرونده مهاجرتم به TEF نیاز داشتم. برنامه اختصاصی بستند و در هشت هفته به نمره هدفم رسیدم. گزارش‌های هفتگی دقیقاً می‌گفت کجا ضعیفم.',
			),
			array(
				'name'   => 'نگار حسینی',
				'role'   => 'پژوهشگر تاریخ هنر',
				'rating' => 5,
				'quote'  => 'متن‌های تخصصی هنر را باید به فرانسه می‌خواندم. استاد دوره را با منابع خودم تطبیق داد؛ چنین انعطافی را در هیچ آموزشگاه دیگری ندیدم.',
			),
			array(
				'name'   => 'محمد رضایی',
				'role'   => 'پزشک عمومی',
				'rating' => 5,
				'quote'  => 'با شیفت‌های نامنظم بیمارستان فکر می‌کردم نمی‌شود. کلاس‌های کوچک و جبرانی‌های منظم باعث شد یک جلسه هم عقب نمانم.',
			),
			array(
				'name'   => 'الهه صادقی',
				'role'   => 'طراح گرافیک',
				'rating' => 5,
				'quote'  => 'بعد از دو بار شکست در کلاس‌های آنلاین، اینجا برای اولین بار حس کردم کسی پیشرفتم را دنبال می‌کند. حالا راحت با همکار فرانسوی‌ام جلسه می‌گذارم.',
			),
		)
	);
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
				'question' => 'برای شروع باید پیش‌زمینه‌ای از زبان فرانسه داشته باشم؟',
				'answer'   => 'خیر. دوره «فرانسه از صفر» دقیقاً برای کسانی طراحی شده که هیچ آشنایی قبلی ندارند. اگر هم پیش‌تر فرانسه خوانده‌اید، جلسه تعیین سطح رایگان مشخص می‌کند از کدام دوره شروع کنید.',
			),
			array(
				'question' => 'کلاس‌ها حضوری برگزار می‌شود یا آنلاین؟',
				'answer'   => 'هر دو. کلاس‌های حضوری در ساختمان آکادمی و کلاس‌های آنلاین به‌صورت زنده و تعاملی برگزار می‌شود. کیفیت آموزش، استاد و برنامه درسی در هر دو حالت یکسان است و ویدیوی جلسات تا پایان ترم در دسترس شماست.',
			),
			array(
				'question' => 'هر کلاس چند نفره است؟',
				'answer'   => 'حداکثر شش نفر. این سقف را عمداً پایین نگه داشته‌ایم تا در هر جلسه فرصت کافی برای صحبت کردن هر زبان‌آموز وجود داشته باشد. دوره خصوصی و نیمه‌خصوصی هم ارائه می‌شود.',
			),
			array(
				'question' => 'اگر یک جلسه را از دست بدهم چه اتفاقی می‌افتد؟',
				'answer'   => 'ویدیوی جلسه و جزوه همان روز برایتان ارسال می‌شود و می‌توانید از جلسه رفع اشکال هفتگی استفاده کنید. در صورت غیبت‌های پیاپی، مشاور آموزشی برای جبران با شما هماهنگ می‌کند.',
			),
			array(
				'question' => 'در پایان دوره مدرک می‌گیرم؟',
				'answer'   => 'بله. پس از قبولی در آزمون پایان هر دوره، گواهی سطح آکادمی زندی صادر می‌شود. برای مدارک بین‌المللی مانند TEF و TCF نیز دوره آمادگی اختصاصی داریم و در فرآیند ثبت‌نام آزمون کنارتان هستیم.',
			),
			array(
				'question' => 'شهریه چگونه پرداخت می‌شود؟',
				'answer'   => 'شهریه هر ترم به‌صورت یکجا یا دو قسط قابل پرداخت است. جزئیات دقیق در جلسه مشاوره رایگان و متناسب با دوره انتخابی شما اعلام می‌شود.',
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
			'title'       => 'آماده‌اید یادگیری فرانسه را شروع کنید؟',
			'description' => 'یک جلسه مشاوره و تعیین سطح رایگان رزرو کنید. در همان جلسه مشخص می‌شود از کجا شروع کنید و مسیرتان تا هدف چقدر طول می‌کشد.',
			'primary'     => array(
				'label' => 'ثبت نام',
				'url'   => '#register',
			),
			'secondary'   => array(
				'label' => 'گفت‌وگو با مشاور',
				'url'   => '#contact',
			),
			'reassurance' => 'بدون هزینه و بدون تعهد برای ثبت‌نام در دوره',
		)
	);
}

/**
 * Contact details.
 *
 * @return array<string,string>
 */
function zandi_contact() {
	return apply_filters(
		'zandi_contact',
		array(
			'phone'      => '۰۲۱-۹۱۰۰۲۲۳۴',
			'phone_href' => 'tel:+982191002234',
			'email'      => 'info@zandiacademy.com',
			'address'    => 'تهران، خیابان ولیعصر، بالاتر از میدان ونک، پلاک ۱۴۲، طبقه سوم',
			'hours'      => 'شنبه تا چهارشنبه، ۹ تا ۱۹ — پنجشنبه‌ها ۹ تا ۱۴',
		)
	);
}

/**
 * Social profiles.
 *
 * @return array<int,array{icon:string,label:string,url:string}>
 */
function zandi_socials() {
	return apply_filters(
		'zandi_socials',
		array(
			array(
				'icon'  => 'instagram',
				'label' => 'اینستاگرام',
				'url'   => 'https://instagram.com',
			),
			array(
				'icon'  => 'telegram',
				'label' => 'تلگرام',
				'url'   => 'https://telegram.org',
			),
			array(
				'icon'  => 'whatsapp',
				'label' => 'واتس‌اپ',
				'url'   => 'https://whatsapp.com',
			),
			array(
				'icon'  => 'linkedin',
				'label' => 'لینکدین',
				'url'   => 'https://linkedin.com',
			),
			array(
				'icon'  => 'youtube',
				'label' => 'یوتیوب',
				'url'   => 'https://youtube.com',
			),
		)
	);
}

/**
 * Footer link columns, used when no menu is assigned to the `footer` location.
 *
 * @return array<int,array{title:string,links:array<int,array{label:string,url:string}>}>
 */
function zandi_footer_columns() {
	return apply_filters(
		'zandi_footer_columns',
		array(
			array(
				'title' => 'آکادمی',
				'links' => array(
					array(
						'label' => 'درباره ما',
						'url'   => '#about',
					),
					array(
						'label' => 'اساتید',
						'url'   => '#teachers',
					),
					array(
						'label' => 'مسیر یادگیری',
						'url'   => '#journey',
					),
					array(
						'label' => 'نظرات زبان‌آموزان',
						'url'   => '#testimonials',
					),
				),
			),
			array(
				'title' => 'دوره‌ها',
				'links' => array(
					array(
						'label' => 'فرانسه از صفر (A1)',
						'url'   => '#courses',
					),
					array(
						'label' => 'فرانسه مقدماتی (A2)',
						'url'   => '#courses',
					),
					array(
						'label' => 'فرانسه متوسط (B1)',
						'url'   => '#courses',
					),
					array(
						'label' => 'آمادگی TEF و TCF',
						'url'   => '#courses',
					),
				),
			),
			array(
				'title' => 'راهنما',
				'links' => array(
					array(
						'label' => 'سوالات متداول',
						'url'   => '#faq',
					),
					array(
						'label' => 'تعیین سطح رایگان',
						'url'   => '#register',
					),
					array(
						'label' => 'شرایط ثبت‌نام',
						'url'   => '#faq',
					),
					array(
						'label' => 'تماس با ما',
						'url'   => '#contact',
					),
				),
			),
		)
	);
}
