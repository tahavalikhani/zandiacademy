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
 * Memoised. A course page reaches this eight to eleven times per request
 * through zandi_current_course(), and without the static each call rebuilt the
 * whole nested literal below and re-dispatched apply_filters() to produce an
 * identical result.
 *
 * @return array<string,array<string,mixed>>
 */
function zandi_courses_data() {
	static $courses = null;

	if ( null !== $courses ) {
		return $courses;
	}

	$courses = apply_filters(
		'zandi_courses_data',
		array(

			'a1' => array(
				'slug'        => 'a1',
				'group_url'   => 'https://t.me/addlist/lz8ax4heGdoyYzE0',
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

				'price_toman'  => 8970000,
				'price_euro'   => 219,
				'podcast_days' => 30,

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

				/*
				 * The syllabus, from the owner. Five sections, thirty-four
				 * topics. The stats line on the page counts these rather than
				 * repeating the numbers, so the two cannot drift apart.
				 *
				 * Shape, per section:
				 *   number  — the section's own numeral, already Persian
				 *   icon    — a name from inc/icons.php
				 *   title   — the section heading
				 *   summary — one line under it, in the accordion header
				 *   items   — array of array( title, one-line description )
				 *
				 * A topic title may carry a French term and a Persian gloss
				 * separated by an em dash. zandi_bidi() isolates the French as
				 * one LTR run — including across spaces, apostrophes, slashes
				 * and that dash — so «Les pronoms relatifs — Dont / Ce dont»
				 * stays in its own order inside an RTL line.
				 *
				 * All three levels are populated. A fourth course needs only its
				 * own entry here; the template is generic.
				 */
				'curriculum' => array(
					array(
						'number'  => '۰۱',
						'icon'    => 'chat',
						'title'   => 'از صفر تا اولین گفت‌وگو',
						'summary' => 'الفبا، تلفظ و جمله‌هایی که از همان جلسه‌ی اول می‌توانی بگویی',
						'items'   => array(
							array( 'L\'alphabet — حروف الفبا', 'تلفظ درست هر حرف و اسپل کردن اسم و فامیل به فرانسوی' ),
							array( 'Les nombres — اعداد', 'شمردن، گفتن قیمت، شماره تلفن و تاریخ' ),
							array( 'L\'heure — ساعت', 'پرسیدن و گفتن ساعت و قرار گذاشتن' ),
							array( 'Se présenter — معرفی خود', 'اسم، سن، ملیت، شهر و شغل را در چند جمله بگو' ),
							array( 'Les couleurs — رنگ‌ها', 'رنگ‌ها و طرز استفاده‌شان کنار اسم‌ها' ),
						),
					),
					array(
						'number'  => '۰۲',
						'icon'    => 'globe',
						'title'   => 'واژگان زندگی روزمره',
						'summary' => 'کلمه‌هایی که هر روز در خیابان، خانه و مغازه لازمت می‌شود',
						'items'   => array(
							array( 'La famille — اعضای خانواده', 'معرفی پدر، مادر، خواهر، برادر و بقیه‌ی فامیل' ),
							array( 'Les métiers — شغل‌ها', 'پرکاربردترین شغل‌ها و پرسیدن شغلِ دیگران' ),
							array( 'Les transports — وسایل نقلیه', 'مترو، اتوبوس، تاکسی و گفتن اینکه با چه چیزی می‌روی' ),
							array( 'La nourriture — خوراکی‌ها', 'غذاها، نوشیدنی‌ها و سفارش دادن در کافه و رستوران' ),
							array( 'Les vêtements — لباس‌ها', 'اسم لباس‌ها و خرید کردن از فروشگاه' ),
							array( 'Le corps — اعضای بدن', 'اسم اعضای بدن و گفتن اینکه کجایت درد می‌کند' ),
							array( 'Les saisons — فصل‌ها', 'چهار فصل، ماه‌ها و روزهای هفته' ),
							array( 'La météo — آب و هوا', 'حرف زدن درباره‌ی هوا؛ رایج‌ترین موضوع گفت‌وگو در فرانسه' ),
						),
					),
					array(
						'number'  => '۰۳',
						'icon'    => 'heart',
						'title'   => 'توصیف کردن و نظر دادن',
						'summary' => 'از جمله‌های خشک به حرف زدنِ طبیعی و شخصی',
						'items'   => array(
							array( 'Les adjectifs — صفات توصیفی', 'توصیف آدم‌ها، اشیا و مکان‌ها' ),
							array( 'صفت + اسم', 'جای درست صفت، مذکر و مؤنث، مفرد و جمع' ),
							array( 'صفات حالت', 'خسته، خوشحال، عصبانی، گرسنه — گفتن حالِ خودت' ),
							array( 'La routine — کارهای روزمره', 'تعریف کردن یک روز کامل از صبح تا شب' ),
							array( 'بیان نظر شخصی', 'دوست دارم / دوست ندارم / به نظر من...' ),
						),
					),
					array(
						'number'  => '۰۴',
						'icon'    => 'layers',
						'title'   => 'پایه‌های گرامر',
						'summary' => 'ستون‌های اصلی جمله‌سازی در فرانسه',
						'items'   => array(
							array( 'Les articles définis', 'حرف تعریف معین: le, la, les' ),
							array( 'Les articles indéfinis', 'حرف تعریف نامعین: un, une, des' ),
							array( 'Les adjectifs démonstratifs', 'اشاره کردن: ce, cet, cette, ces' ),
							array( 'Le pronom tonique', 'ضمایر تأکیدی: moi, toi, lui, elle...' ),
							array( 'La négation', 'منفی کردن جمله با ne … pas' ),
							array( 'Les mots d\'interrogation', 'سؤال ساختن با qui, que, où, quand, comment, pourquoi' ),
						),
					),
					array(
						'number'  => '۰۵',
						'icon'    => 'clock',
						'title'   => 'فعل‌ها و زمان‌ها',
						'summary' => 'گذشته، حال و آینده — کامل‌ترین بخش دوره',
						'items'   => array(
							array( 'Les verbes au premier groupe', 'فعل‌های باقاعده‌ی -er و صرف کردنشان' ),
							array( 'Les verbes au troisième groupe', 'فعل‌های بی‌قاعده‌ی پرکاربرد' ),
							array( 'Les verbes pronominaux', 'فعل‌های انعکاسی مثل se lever و s\'appeler' ),
							array( 'Le présent', 'زمان حال ساده؛ پایه‌ی همه‌ی جمله‌ها' ),
							array( 'Le présent continu', 'کاری که همین الان در حال انجامش هستی: être en train de' ),
							array( 'Le passé composé', 'گذشته؛ تعریف کردن اتفاق‌هایی که افتاده' ),
							array( 'Le passé récent', 'گذشته‌ی نزدیک با venir de' ),
							array( 'Le futur proche', 'آینده‌ی نزدیک با aller + مصدر' ),
							array( 'L\'impératif', 'وجه امری؛ دستور دادن و راهنمایی کردن' ),
							array( 'Les adverbes du temps', 'قیدهای زمان برای حال، گذشته و آینده' ),
						),
					),
				),


				'meta_description' => 'دوره پایه زبان فرانسه A1 با شیما زندی؛ ۷۸ جلسه ویدیویی، ۱۶ ساعت آموزش، پشتیبانی ۲۴ ساعته و مصاحبه پایان دوره. از صفر تا گفتگوی ساده.',
			),

			'a2' => array(
				'slug'        => 'a2',
				'group_url'   => 'https://t.me/addlist/mTzJVJ28BN4xMmM8',
				'level'       => 'A2',
				'eyebrow'     => 'سطح متوسط · A2',
				'short_name'  => 'دوره متوسط A2',
				'title'       => 'دیگه وقتشه از جمله‌های کوتاه بیای بیرون 🇫🇷',
				'subtitle'    => 'پایه رو بلدی ولی وسط حرف زدن گیر می‌کنی؟ توی این دوره یاد می‌گیری از گذشته و آینده حرف بزنی، نظرت رو بگی، ماجرا تعریف کنی و جمله‌هات رو به هم وصل کنی. اون چیزی که باعث می‌شه حرف زدنت شبیه یه آدم واقعی بشه، نه شبیه کتاب.',
				'cta_primary' => 'ثبت‌نام در دوره متوسط',

				'sessions'      => 100,
				'sessions_text' => '۱۰۰ جلسه ویدیویی',
				'hours_text'    => 'نزدیک به ۹ ساعت آموزش',

				'price_toman'  => 9970000,
				'price_euro'   => 249,
				'podcast_days' => 30,

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

				/*
				 * The syllabus, from the owner. Five sections, forty-six topics.
				 * Same shape as A1 — see the note there.
				 */
				'curriculum'         => array(
					array(
						'number'  => '۰۱',
						'icon'    => 'chat',
						'title'   => 'موضوع‌های مکالمه',
						/*
						 * «یازده», not «ده». The source document said ten and
						 * listed eleven, and the count chip rendered beside this
						 * line is computed from the array — so the two would
						 * have contradicted each other in the same header.
						 */
						'summary' => 'یازده سوژه‌ی واقعی که در کلاس درباره‌شان حرف می‌زنی، نه فقط تمرین می‌کنی',
						'items'   => array(
							array( 'Se présenter en A2 — معرفی خود', 'معرفی کامل‌تر و طبیعی‌تر نسبت به سطح قبل' ),
							array( 'Dernier anniversaire — آخرین تولد', 'تعریف کردن یک خاطره‌ی گذشته با جزئیات' ),
							array( 'L\'éducation — تحصیلات', 'رشته، مدرک، دانشگاه و مسیر تحصیلی' ),
							array( 'Une autre langue — زبان دیگر', 'حرف زدن درباره‌ی زبان‌هایی که بلدی و تجربه‌ی یادگیری' ),
							array( 'Le voyage — سفر', 'تعریف سفرهای گذشته و برنامه‌ی سفرهای بعدی' ),
							array( 'La technologie — تکنولوژی', 'گوشی، اپلیکیشن و نقش تکنولوژی در زندگی روزمره' ),
							array( 'La communication — ارتباطات', 'روش‌های ارتباط گرفتن و مقایسه‌شان با هم' ),
							array( 'Les réseaux sociaux — شبکه‌های اجتماعی', 'نظر دادن درباره‌ی مزایا و معایب شبکه‌های اجتماعی' ),
							array( 'Le projet pour l\'avenir — برنامه‌ی آینده', 'گفتن هدف‌ها و نقشه‌های آینده' ),
							array( 'Le week-end dernier — آخر هفته‌ی گذشته', 'روایت کردن اتفاق‌های چند روز اخیر' ),
							array( 'La routine quotidienne — روتین روزانه', 'توصیف کامل یک روز عادی با جزئیات بیشتر' ),
						),
					),
					array(
						'number'  => '۰۲',
						'icon'    => 'clock',
						'title'   => 'زمان‌های جدید فعل',
						'summary' => 'گذشته، آینده و شرط — جایی که فرانسه‌ات از سطح مبتدی جدا می‌شود',
						'items'   => array(
							array( 'Le futur simple', 'آینده‌ی ساده؛ حرف زدن درباره‌ی برنامه‌های دور' ),
							array( 'L\'imparfait', 'گذشته‌ی استمراری؛ توصیف عادت‌ها و صحنه‌های گذشته' ),
							array( 'Passé composé / Imparfait', 'مهم‌ترین تفاوت A2: کِی کدام زمان را به کار ببری' ),
							array( 'Le conditionnel présent', 'ادب، آرزو و درخواست‌های محترمانه' ),
							array( 'Si', 'جمله‌های شرطی و ساختن فرض' ),
						),
					),
					array(
						'number'  => '۰۳',
						'icon'    => 'route',
						'title'   => 'ضمیرها و ساختار جمله',
						'summary' => 'جمله‌های کوتاه را به جمله‌های بلند و روان وصل کن',
						'items'   => array(
							array( 'Qui / Que / Où', 'ضمایر موصولی؛ ساختن جمله‌های ترکیبی' ),
							array( 'Ce que / Ce qui', 'اشاره به یک مفهوم کامل به‌جای یک اسم' ),
							array( 'En / Y', 'دو ضمیر پرکاربردی که تکرار را از جمله حذف می‌کنند' ),
							array( 'COD / COI', 'مفعول مستقیم و غیرمستقیم و جای درست ضمیرشان' ),
							array( 'Les pronoms démonstratifs', 'Celui, Celle, Ceux, Celles' ),
							array( 'Tout / Toute / Tous / Toutes', 'حالت‌های مختلف «همه» و کاربرد هرکدام' ),
							array( 'Il faut + infinitif', 'بیان ضرورت و بایدها' ),
							array( 'Les verbes pronominaux', 'مرور و گسترش فعل‌های انعکاسی در سطح A2' ),
						),
					),
					array(
						'number'  => '۰۴',
						'icon'    => 'target',
						'title'   => 'منفی کردن، مقدار و قید',
						'summary' => 'دقیق‌تر حرف زدن: چقدر، چرا، و دقیقاً چه چیزی نه',
						'items'   => array(
							array( 'La négation — Ne…plus / Ne…jamais', 'شکل‌های پیشرفته‌تر منفی کردن' ),
							array( 'Ne … que', 'گفتن «فقط» به روش فرانسوی' ),
							array( 'Les adverbes A2', 'قیدهای پرکاربرد سطح A2 و جایگاهشان در جمله' ),
							array( 'Peu de / Assez de / Beaucoup de', 'بیان مقدار و اندازه' ),
							array( 'Grâce à / À cause de', 'بیان علت مثبت و علت منفی' ),
							array( 'Presque / Environ', 'گفتن عددها و مقدارهای تقریبی' ),
						),
					),
					array(
						'number'  => '۰۵',
						'icon'    => 'sparkles',
						'title'   => 'عبارت‌های ربطی و بیان نظر',
						'summary' => 'ابزارهایی که حرف زدنت را از «درست» به «طبیعی» می‌رسانند',
						'items'   => array(
							array( 'D\'abord / Puis / Ensuite / Enfin', 'مرتب کردن حرف‌ها؛ اول، بعد، در آخر' ),
							array( 'Au début de / Au milieu de / À la fin de', 'مشخص کردن زمان و مرحله' ),
							array( 'Après / Avant de', 'ترتیب دو کار نسبت به هم' ),
							array( 'Jusqu\'à / Jusqu\'au / Jusqu\'à la', 'بیان «تا کِی» و «تا کجا»' ),
							array( 'Maintenant / Actuellement / Aujourd\'hui', 'سه شکل «الان» و تفاوت ظریفشان' ),
							array( 'Une autre fois / Encore une fois', 'تکرار و دفعه‌ی بعد' ),
							array( 'Aimer / Ne pas aimer', 'بیان علاقه و بی‌علاقگی با ظرافت بیشتر' ),
							array( 'Accepter / Refuser', 'قبول کردن و رد کردن مؤدبانه' ),
							array( 'Accord / Désaccord', 'موافقت و مخالفت در بحث' ),
							array( 'Avantages / Inconvénients', 'مزایا و معایب؛ ستون اصلی مکالمه‌های نظری' ),
							array( 'Au lieu de / À la place de', 'بیان جایگزین و «به‌جای»' ),
							array( 'Tel que / Comme', 'مثال زدن و مقایسه کردن' ),
							array( 'Entre / Parmi', 'انتخاب از بین دو چیز یا از بین یک گروه' ),
							array( 'L\'un des / L\'une des', 'گفتن «یکی از …»' ),
							array( 'À propos de', 'وارد کردن موضوع تازه به گفت‌وگو' ),
							array( 'Chacun / Chacune', 'اشاره به هر نفر یا هر مورد به‌طور جداگانه' ),
						),
					),
				),

				'meta_description' => 'دوره متوسط زبان فرانسه A2 با شیما زندی؛ ۱۰۰ جلسه ویدیویی، پشتیبانی ۲۴ ساعته و مصاحبه پایان دوره. از جمله‌های کوتاه به مکالمه واقعی.',
			),

			'b1' => array(
				'slug'        => 'b1',
				'group_url'   => 'https://t.me/+PHMY-oHIVDwyZjk0',
				'level'       => 'B1',
				'eyebrow'     => 'سطح پیشرفته · B1',
				'short_name'  => 'دوره پیشرفته B1',
				'title'       => 'فرانسه‌ت خوبه، حالا بیا روان و بی‌مکث حرف بزن 🇫🇷',
				'subtitle'    => 'این سطحیه که ازش به بعد فرانسه از «درس» تبدیل می‌شه به «زبان». بحث می‌کنی، مخالفت می‌کنی، از تجربه‌هات می‌گی و منظورت رو دقیق می‌رسونی. برای کسی که می‌خواد توی فرانسه کار کنه، درس بخونه یا زندگی کنه، این همون نقطه‌ست.',
				'cta_primary' => 'ثبت‌نام در دوره پیشرفته',

				'sessions'      => 59,
				'sessions_text' => '۵۹ جلسه ویدیویی',
				'hours_text'    => 'نزدیک به ۹ ساعت و نیم آموزش',

				'price_toman'  => 11970000,
				'price_euro'   => 319,
				'podcast_days' => 30,

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

				/*
				 * The syllabus, from the owner. Five sections, forty-four
				 * topics. Same shape as A1 — see the note there.
				 */
				'curriculum'         => array(
					array(
						'number'  => '۰۱',
						'icon'    => 'chat',
						'title'   => 'موضوع‌های مکالمه',
						'summary' => 'هجده سوژه‌ی واقعی برای حرف زدن طولانی، نه جواب‌های کوتاه',
						'items'   => array(
							array( 'Plus naturel 1 & 2', 'دو بخش کامل برای طبیعی‌تر کردن لحن و جمله‌سازی' ),
							array( 'Pour commencer', 'شروع کردن گفت‌وگو و ورود به بحث' ),
							array( 'Vivre en ville', 'زندگی شهری، مزایا و مشکلاتش' ),
							array( 'La santé et le mode de vie', 'سلامتی، عادت‌ها و سبک زندگی' ),
							array( 'Demander', 'درخواست کردن در موقعیت‌های مختلف با لحن مناسب' ),
							array( 'Rester motivé', 'انگیزه، پشتکار و حرف زدن درباره‌ی هدف‌ها' ),
							array( 'Le covoiturage', 'سفر اشتراکی؛ یکی از رایج‌ترین موضوع‌های روزمره در فرانسه' ),
							array( 'Au milieu', 'وسط ماجرا بودن؛ توصیف موقعیت و شرایط' ),
							array( 'Exprimer', 'بیان کردن احساس، نظر و موضع خودت' ),
							array( 'L\'apprentissage', 'یادگیری، تجربه‌ها و روش‌های آموختن' ),
							array( 'Les réseaux sociaux', 'بحث تحلیلی‌تر درباره‌ی شبکه‌های اجتماعی' ),
							array( 'Le freelancing', 'کار آزاد، مزایا و چالش‌هایش' ),
							array( 'La Freebox', 'موقعیت واقعی زندگی در فرانسه: اینترنت و خدمات خانگی' ),
							array( 'Le travail', 'محیط کار، مصاحبه و گفت‌وگوی حرفه‌ای' ),
							array( 'Le changement', 'تغییر در زندگی و واکنش به آن' ),
							array( 'Le sommeil', 'خواب، بی‌خوابی و عادت‌های شبانه' ),
							array( 'Le voyageur', 'تجربه‌ی مسافر بودن و روایت سفر' ),
							array( 'Les émotions', 'واژگان دقیق احساسات؛ فراتر از خوب و بد' ),
						),
					),
					array(
						'number'  => '۰۲',
						'icon'    => 'layers',
						'title'   => 'گرامر اصلی B1',
						'summary' => 'ساختارهایی که فرانسه‌ات را از «قابل‌فهم» به «درست و حرفه‌ای» می‌رساند',
						'items'   => array(
							array( 'Le subjonctif', 'وجه التزامی؛ مهم‌ترین گرامر سطح B1' ),
							array( 'Le plus-que-parfait', 'گذشته‌ی دورتر؛ روایت کردن دو اتفاق پشت‌سرهم در گذشته' ),
							array( 'Le discours direct et indirect', 'نقل قول مستقیم و غیرمستقیم' ),
							array( 'La voix passive', 'جمله‌ی مجهول و کاربردش در متن‌های رسمی' ),
							array( 'Le gérondif', 'ساختن «en + -ant» برای بیان هم‌زمانی و روش' ),
							array( 'Le participe présent', 'شکل -ant و تفاوتش با ژروندیف' ),
							array( 'Les pronoms relatifs — Dont / Ce dont', 'ضمایر موصولی پیشرفته برای جمله‌های بلند' ),
							array( 'La négation avancée', 'شکل‌های پیچیده‌تر منفی کردن در سطح B1' ),
							array( 'B1 résumé', 'جمع‌بندی و مرور کل گرامر سطح' ),
						),
					),
					array(
						'number'  => '۰۳',
						'icon'    => 'route',
						'title'   => 'ربط دادن ایده‌ها',
						'summary' => 'ابزارهایی برای استدلال کردن و ساختن جمله‌های چندلایه',
						'items'   => array(
							array( 'Alors que / Tandis que', 'بیان تضاد و مقایسه‌ی دو موقعیت' ),
							array( 'Non seulement…, mais aussi', 'اضافه کردن نکته‌ی دوم با تأکید' ),
							array( 'Soit… soit', 'گفتن «یا این یا آن»' ),
							array( 'Ni… ni', 'نفی هم‌زمان دو چیز' ),
							array( 'Sauf que', 'اضافه کردن استثنا و «فقط اینکه…»' ),
							array( 'La raison pour laquelle', 'بیان دلیل به شکل رسمی و ساختارمند' ),
						),
					),
					array(
						'number'  => '۰۴',
						'icon'    => 'clock',
						'title'   => 'زمان، شرط و درجه',
						'summary' => 'دقیق کردن اینکه کِی، تا کِی و تا چه حد',
						'items'   => array(
							array( 'Dès que', 'به‌محض اینکه؛ اتفاق بلافاصله بعد از یک اتفاق دیگر' ),
							array( 'Tant que', 'تا وقتی که؛ بیان شرط ادامه‌دار' ),
							array( 'Jusqu\'à ce que', 'تا زمانی که؛ همراه با سوبژونکتیف' ),
							array( 'À quel point', 'بیان شدت و اندازه‌ی یک چیز' ),
							array( 'Pas forcément', 'گفتن «لزوماً نه»؛ مخالفت نرم و محترمانه' ),
							array( 'Quelque', 'حالت‌های مختلف quelque و کاربرد درست هرکدام' ),
						),
					),
					array(
						'number'  => '۰۵',
						'icon'    => 'sparkles',
						'title'   => 'دقت بیان و فرانسه‌ی محاوره‌ای',
						'summary' => 'همان چیزهایی که فرانسوی‌زبان‌ها می‌گویند ولی در کتاب‌ها نیست',
						'items'   => array(
							array( 'Principalement', 'گفتن «عمدتاً» و مشخص کردن نکته‌ی اصلی' ),
							array( 'Notamment', 'مثال زدن به شکل رسمی و روان' ),
							array( 'Ça me fait…', 'بیان تأثیر یک چیز روی حس و حال تو' ),
							array( 'Ça nécessite de', 'گفتن اینکه یک کار چه چیزی لازم دارد' ),
							array( 'Les verbes familiers', 'فعل‌های محاوره‌ای که در خیابان و بین دوستان می‌شنوی' ),
						),
					),
				),

				'meta_description' => 'دوره پیشرفته زبان فرانسه B1 با شیما زندی؛ ۵۹ جلسه ویدیویی، پشتیبانی ۲۴ ساعته و مصاحبه پایان دوره. از فهمیدن تا حرف زدن روان.',
			),

			/*
			 * THE FIRST CONVERSATION COURSE, AND IT IS NOT ON SALE. The owner
			 * sent the page on 24 September 2026 with one instruction above
			 * the copy: people may read it, nobody may buy it yet. So the page
			 * is real and public, and `coming_soon` makes every way of buying
			 * it say no — see zandi_course_on_sale() below.
			 *
			 * TO LAUNCH IT: delete the `coming_soon` line, set the two prices,
			 * and link a product to it in wp-admin. Nothing else changes; the
			 * «به‌زودی» states all read that one flag.
			 *
			 * It is the same template as A1, A2 and B1 with different parts:
			 * no syllabus (the owner's page has none — the videos are
			 * topic-led, not a sequence), a comparison with the main courses,
			 * and a «how a session goes» section. `sections` names them in the
			 * owner's order. Every string below is the owner's, verbatim.
			 *
			 * مکالمه A2 and B1 are announced as one «به‌زودی» card in
			 * zandi_upcoming_courses() until their pages arrive; each will be
			 * one more entry like this, with `family` => 'conversation'.
			 */
			'conversation-a1' => array(
				'slug'        => 'conversation-a1',
				'family'      => 'conversation',
				'coming_soon' => true,
				'group_url'   => '',
				'level'       => 'A1',
				'eyebrow'     => 'دوره مکالمه · A1',
				'short_name'  => 'دوره مکالمه A1',
				'title'       => 'وقتشه یه قدم جلوتر بری و راحت‌تر حرف بزنی 🗣️',
				'subtitle'    => 'پایه A1 رو داری و می‌خوای توی موقعیت‌های واقعی زندگی در فرانسه راحت‌تر و روون‌تر حرف بزنی؟ اینجا دقیقاً جای درستیه. ویدیوهای این دوره موضوع‌محورن، از دل زندگی واقعی توی فرانسه، به علاوه سوال‌هایی که هم فرانسوی‌ها ممکنه ازت بپرسن و هم ممتحن امتحان.',
				'cta_primary' => 'ثبت‌نام در دوره مکالمه A1',

				// There is no session count: the owner describes the videos by
				// topic and length, not by number.
				'sessions'      => 0,
				'sessions_text' => 'آموزش ویدیویی موضوع‌محور',
				'hours_text'    => 'ویدیوهای ۵ تا ۲۵ دقیقه‌ای',

				// No price yet. Nothing prints one while `coming_soon` is set.
				'price_toman'  => 0,
				'price_euro'   => 0,
				'podcast_days' => 30,

				'sections' => array(
					'about-course',
					'compare',
					'how',
					'deliverables',
					'sample-lesson',
					'fit',
					'shima',
					'testimonials',
				),

				// No handouts row: this course has homework, not a جزوه.
				'info_rows' => array(
					array( 'icon' => '🎬', 'text' => 'آموزش ویدیویی موضوع‌محور' ),
					array( 'icon' => '⏱️', 'text' => 'ویدیوهای ۵ تا ۲۵ دقیقه‌ای' ),
					array( 'icon' => '👤', 'text' => 'تدریس شخص شیما زندی' ),
					array( 'icon' => '♾️', 'text' => 'دسترسی مادام‌العمر' ),
					array( 'icon' => '💬', 'text' => 'پشتیبانی ۲۴ ساعته' ),
					array( 'icon' => '🎤', 'text' => 'مصاحبه ۱۵ دقیقه‌ای پایان دوره' ),
				),

				'trust_items' => array(
					'۱۴۲ هزار دنبال‌کننده در اینستاگرام',
					'تدریس از پاریس',
					'موضوع‌محور',
					'پشتیبانی ۲۴/۷',
				),

				'intro_lead' => 'توی این ویدیو کوتاه توضیح می‌دم دوره مکالمه چطور پیش می‌ره و برای کیه.',

				'jump_links' => array(
					array(
						'label'  => 'دیدن نمونه تدریس',
						'target' => 'sample-lesson',
						'icon'   => 'play',
					),
					array(
						'label'  => 'فرق دوره‌های اصلی و مکالمه',
						'target' => 'compare',
						'icon'   => 'route',
					),
					array(
						'label'  => 'رضایت زبان‌آموزها',
						'target' => 'testimonials',
						'icon'   => 'heart',
					),
				),

				'about_title' => 'حرف زدن، هر چی بیشتر، راحت‌تر',
				'about_body'  => array(
					'حرف زدن مثل هر مهارت دیگه‌ایه. هر چی بیشتر توی موقعیت‌های مختلف تمرینش کنی، راحت‌تر و طبیعی‌تر می‌شه.',
					'دوره مکالمه A1 برای کسیه که پایه‌ش رو داره و حالا می‌خواد بیشتر حرف بزنه: توی موقعیت‌های بیشتر، با موضوع‌های واقعی‌تر، و با آمادگی برای سوال‌هایی که یه روز یه فرانسوی یا یه ممتحن ازش می‌پرسه.',
				),
				'outcomes'    => array(
					'قرار ملاقات بذاری و کارهای اداری ساده‌ت رو پیش ببری',
					'درباره خونه و اجاره حرف بزنی',
					'با همسایه فرانسوی‌ت گپ بزنی',
					'توی موقعیت‌های روزمره زندگی در فرانسه از پس خودت بربیای',
					'به سوال‌هایی درباره موضوعات به‌روز جواب بدی',
					'خودت تمرین کنی و برای حرف زدن ایده‌پردازی کنی',
				),

				/*
				 * The two steps under the comparison — the owner's last line
				 * of that section, «اگه از صفر شروع می‌کنی، اول دوره پایه A1.
				 * اگه پایه A1 رو داری، دوره مکالمه A1 قدم بعدیته.», split at
				 * its full stop. `course` is what each step points at.
				 */
				'compare_path' => array(
					array(
						'when'   => 'اگه از صفر شروع می‌کنی،',
						'then'   => 'اول دوره پایه A1',
						'course' => 'a1',
					),
					array(
						'when'   => 'اگه پایه A1 رو داری،',
						'then'   => 'دوره مکالمه A1 قدم بعدیته',
						'course' => 'conversation-a1',
					),
				),

				'how' => array(
					'title' => 'هر جلسه چطوری پیش می‌ره 🎬',
					'lead'  => 'مهم‌ترین ویژگی دوره‌های مکالمه اینه که یک قالب مشخص نداره، چون قطعاً صحبت کردن با فرانسوی‌ها همه‌ش یک موقعیت یکسان نیست 🥰 پس توی هر شرایطی می‌خوام آماده‌ت کنم که از پس خودت بربیای.',

					/*
					 * The owner's two paragraphs about the two kinds of video,
					 * each under a title taken from its own first sentence.
					 */
					'kinds' => array(
						array(
							'icon'  => 'globe',
							'title' => 'ویدیوهای موضوع‌محور',
							'body'  => 'همه ویدیوها موضوع‌محور هستن، به‌خصوص موضوعاتی که توی محیط واست کاربرد زیادی داره، مثل قرار ملاقات گرفتن، پیش بردن کارهای اداری، اجاره کردن خونه، صحبت با همسایه فرانسوی‌ت و موضوعات طبیعی که از دل زندگی کردن در فرانسه میاد 🇫🇷',
						),
						array(
							'icon'  => 'target',
							'title' => 'سوال و جواب درباره موضوعات به‌روز',
							'body'  => "یه دسته دیگه از ویدیوها شامل سوال و جواب درباره موضوعات به‌روزه، موضوعاتی که هم فرانسوی‌ها ممکنه ازت بپرسن و هم ممتحن امتحان 🥰\nپس اینجا هم می‌تونی با یه تیر دو نشون بزنی 🎯",
						),
					),

					'outro_title' => 'اما صبر کن، همه‌چیز رو اینجا واست توضیح ندادم.',
					'outro'       => 'خودت نمونه تدریس رو ببین تا باورت بشه چقدر برای همه فایل‌ها و انتخاب موضوعات و سوژه‌ها زحمت کشیده شده و با حساسیت زیاد و بر اساس سال‌ها تجربه تدریسم انتخاب شدن 😍',
					'outro_cta'   => 'نمونه تدریس رو ببین ▶',
				),

				/*
				 * Two across rather than four: the homework card is four
				 * sentences, and in a quarter of the row it would stand twice
				 * as tall as the three beside it.
				 */
				'deliverables_columns' => 2,
				'deliverables'         => array(
					array(
						'icon'  => 'play',
						'title' => 'آموزش‌ها',
						'body'  => "آموزش‌ها در قالب ویدیو هستن و بسته به سختی و آسونی هر ویدیو، از ۵ دقیقه تا ۲۵ دقیقه متغیرن ✨\nهمه‌چیز واست جوری تنظیم شده که تو بتونی بالاترین کیفیت رو تجربه کنی و بهترین مسیر مکالمه کردن رو یاد بگیری.",
					),
					array(
						'icon'  => 'clipboard',
						'title' => 'تکلیف',
						'body'  => "اما مگه می‌شه دوره مکالمه تکلیف نداشته باشه؟ 🥰\nتوی ویدیوهایی که ازت تمرین خواستم، بهت گفتم که چجوری تمرین کنی و چه تکلیف‌هایی بنویسی.\nراستی اگه روش درس خوندن و مکالمه کردن رو هم بلد نیستی، نگران نباش. خودم توی ویدیوها بهت گفتم که چیکار کنی و چجوری ایده‌پردازی کنی.\nچون یه بخشی از متد آموزشی این دوره اینه که بهت ماهیگیری یاد بدم که کیف کنی 😎",
					),
					array(
						'icon'  => 'chat',
						'title' => 'پشتیبانی ۲۴ ساعته',
						'body'  => 'هر ساعتی از شبانه‌روز سوال داشتی بپرس. اینجا کسی بابت سوال ساده پرسیدن قضاوت نمی‌شه.',
					),
					array(
						'icon'  => 'users',
						'title' => 'مصاحبه پایان دوره با من',
						'body'  => 'آخر دوره یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم داری. فرانسه حرف می‌زنیم، اشکالاتت رو می‌گیرم و بهت می‌گم دقیقاً روی چی باید کار کنی.',
					),
				),

				'sample_lead' => 'اینم یه تیکه از یکی از جلسه‌های دوره مکالمه. بدون تدوین و بدون تعارف، دقیقاً همونی که می‌بینی.',

				'who_for'     => array(
					'کسی که پایه A1 رو داره و می‌خواد بیشتر و راحت‌تر حرف بزنه',
					'کسی که توی فرانسه زندگی می‌کنه یا قراره بیاد و می‌خواد از پس موقعیت‌های روزمره بربیاد',
					'کسی که می‌خواد برای سوال‌های ممتحن توی امتحان آماده‌تر باشه',
					'کسی که وقت کلاس حضوری نداره ولی می‌خواد جدی تمرین کنه',
				),
				'who_not_for' => array(
					'کسی که از صفر شروع می‌کنه (اول برو سراغ دوره پایه A1)',
					'کسی که دنبال یاد گرفتن گرامر جدیده',
					'کسی که همین حالا یه گفتگوی ساده رو راحت پیش می‌بره (برو سراغ مکالمه A2)',
				),

				'shima_body' => array(
					'سلام، من شیما زندی‌ام 👋',
					'سال‌هاست فرانسه درس می‌دم و توی پاریس زندگی می‌کنم. موضوع‌های این دوره رو از دل همین زندگی انتخاب کردم: چیزایی که هر روز توی مترو، مغازه، اداره و ساختمون خودم می‌بینم و می‌شنوم.',
					'دوره مکالمه رو برای کسایی ساختم که می‌خوان یه قدم جلوتر برن و توی موقعیت‌های بیشتری حرف بزنن. اینجا حرف می‌زنی، حتی اگه غلط باشه. غلط گفتن قدم اوله، سکوت هیچ قدمی نیست.',
				),

				'support_body' => 'هر وقت گیر کنی جواب می‌گیری، توی ویدیوها بهت گفتم چطور تمرین کنی و آخر مسیر هم خود من هستم.',

				'closing_title' => 'بذار این‌بار بیشتر حرف بزنی',
				'closing_body'  => 'پایه‌ش رو داری. حالا وقتشه توی موقعیت‌های واقعی، با موضوع‌های واقعی، یه قدم جلوتر بری.',

				'extra_faq' => array(),

				/*
				 * The WHOLE list, not an addition to zandi_course_faq(). The
				 * shared questions describe the main courses — six months to
				 * finish, handouts corrected by the admins — and are not all
				 * true of this one, so the owner wrote this page its own.
				 */
				'faq' => array(
					array(
						'q' => 'فرق این دوره با دوره پایه A1 چیه؟',
						'a' => 'دوره پایه، پایه زبان رو می‌سازه: گرامر، واژگان، جمله‌سازی و حرف زدن از همون جلسه اول. دوره مکالمه یه قدم جلوتره و تمرکز کاملش روی صحبت کردنه، با ویدیوهای موضوع‌محور از دل زندگی در فرانسه.',
					),
					array(
						'q' => 'دوره پایه رو ندیدم، می‌تونم مکالمه رو بردارم؟',
						'a' => 'اگه جای دیگه A1 خوندی و پایه‌ش رو داری، آره. اگه از صفر شروع می‌کنی، اول دوره پایه A1.',
					),
					array(
						'q' => 'همه جلسه‌ها یه شکل هستن؟',
						'a' => 'نه. دوره مکالمه عمداً قالب ثابت نداره، چون حرف زدن با فرانسوی‌ها همیشه یه موقعیت یکسان نیست. بعضی ویدیوها موقعیت‌های روزمره‌ان و بعضی سوال و جواب درباره موضوعات به‌روز.',
					),
					array(
						'q' => 'برای امتحان هم کمک می‌کنه؟',
						'a' => 'آره. یه دسته از ویدیوها سوال و جواب درباره موضوعاتیه که ممتحن ممکنه ازت بپرسه.',
					),
					array(
						'q' => 'تکلیف داره؟',
						'a' => 'آره. توی ویدیوها بهت گفتم چجوری تمرین کنی و چه تکلیف‌هایی بنویسی.',
					),
					array(
						'q' => 'روش تمرین مکالمه رو بلد نیستم، چیکار کنم؟',
						'a' => 'نگران نباش. توی ویدیوها خودم بهت گفتم چیکار کنی و چجوری ایده‌پردازی کنی.',
					),
					array(
						'q' => 'دوره زنده و آنلاینه؟',
						'a' => 'نه، دوره آفلاینه. هر وقت و هر جا که بخوای می‌بینیش، هر چند بار که بخوای.',
					),
					array(
						'q' => 'ویدیوها چقدرن؟',
						'a' => 'بسته به سختی و آسونی موضوع، از ۵ دقیقه تا ۲۵ دقیقه.',
					),
					array(
						'q' => 'تا کی به ویدیوها دسترسی دارم؟',
						'a' => 'مادام‌العمر.',
					),
					array(
						'q' => 'روی چه دستگاهی می‌تونم ببینم؟',
						'a' => 'ویدیوها روی اسپات پلیرن و لایسنس روی ۲ دستگاه فعال می‌شه. ویندوز، مک و اندروید پشتیبانی می‌شن. روی آیفون فقط نسخه وب کار می‌کنه که پیشنهادش نمی‌کنم.',
					),
					array(
						'q' => 'مصاحبه پایان دوره چطوریه؟',
						'a' => 'یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم. فرانسه حرف می‌زنیم، اشکالاتت رو می‌گیرم و بهت می‌گم دقیقاً روی چی باید کار کنی.',
					),
					array(
						'q' => 'پرداخت اقساطی دارید؟',
						'a' => 'فعلاً نه.',
					),
				),

				'curriculum' => array(),

				'meta_description' => 'دوره مکالمه A1 زبان فرانسه با شیما زندی؛ ویدیوهای موضوع‌محور از دل زندگی در فرانسه، سوال و جواب درباره موضوعات به‌روز و آمادگی برای سوال‌های ممتحن. به‌زودی.',
			),
		)
	);

	return $courses;
}

/**
 * Whether a course can be bought at all, before anyone asks WooCommerce.
 *
 * False for a course marked `coming_soon` in the catalogue: its page is public
 * and nobody may buy it. EVERY PATH TO A PURCHASE ASKS THIS, not just the
 * button, because the owner's instruction was «do not allow them to get it» —
 * a hidden button with a checkout still reachable behind it would not be that:
 *
 *   zandi_course_enrol_state()       the four controls on the page say «به‌زودی»
 *   zandi_woo_handle_enrol()         a hand-built POST is turned away
 *   zandi_handle_enrol()             the same, with WooCommerce off
 *   zandi_woo_block_unreleased()     a product linked to it cannot be bought
 *                                    from /shop/ or its own product page either
 *
 * and the lists that suggest buying — the homepage cards, the footer, the
 * panel's «قدم بعدی» — leave it out.
 *
 * READS THE ORDINARY CATALOGUE, NOT zandi_courses_raw(), and the difference is
 * a real one. zandi_courses_data() memoises whatever it computes FIRST in a
 * request, and zandi_courses_raw() computes it with the live-price filter
 * removed — so if the raw read happens first, every price on the page that
 * follows is the hard-coded one rather than WooCommerce's. This function is
 * asked from `woocommerce_is_purchasable`, which WooCommerce runs on
 * `wp_loaded` for every item in a visitor's cart, before the course page has
 * read anything. The ordinary getter costs one product lookup per course, once
 * per request, and leaves the memo holding the right prices.
 *
 * @param string $slug Course slug.
 * @return bool
 */
function zandi_course_on_sale( $slug ) {
	$courses = zandi_courses_data();
	$on_sale = isset( $courses[ $slug ] ) && empty( $courses[ $slug ]['coming_soon'] );

	/**
	 * Filters whether a course is on sale.
	 *
	 * @param bool   $on_sale Whether it may be bought.
	 * @param string $slug    Course slug.
	 */
	return (bool) apply_filters( 'zandi_course_on_sale', $on_sale, $slug );
}

/**
 * The content sections of a course page, in order.
 *
 * The hero, trust bar and introduction video always open the page, and the
 * support callout, FAQ, closing block and other courses always end it — see
 * template-course.php. This is what goes between, and it is the one part that
 * differs by course: the conversation courses have no syllabus and carry a
 * comparison and a «how a session goes» section instead.
 *
 * Each name is a file in template-parts/course/.
 *
 * @param array<string,mixed> $course A course from zandi_courses_data().
 * @return array<int,string>
 */
function zandi_course_sections( $course ) {
	$sections = ! empty( $course['sections'] )
		? (array) $course['sections']
		: array( 'about-course', 'deliverables', 'method', 'sample-lesson', 'curriculum', 'fit', 'shima', 'testimonials' );

	/**
	 * Filters a course page's content sections.
	 *
	 * @param array<int,string>   $sections Partial names, in order.
	 * @param array<string,mixed> $course   The course.
	 */
	return (array) apply_filters( 'zandi_course_sections', $sections, $course );
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
			/*
			 * «A1 · A2 · B1» until 24 September 2026. مکالمه A1 has its own
			 * page and its own «به‌زودی» card now, so this one announces the
			 * other two.
			 */
			array( 'title' => 'دوره مکالمه A2 · B1' ),
		)
	);
}

/* -------------------------------------------------------------------------
 * The comparison on the conversation pages
 * ---------------------------------------------------------------------- */

/**
 * The two kinds of course the academy teaches, side by side.
 *
 * Shared by every conversation page — the difference between the two families
 * does not change with the level — so مکالمه A2 gets the same comparison the
 * day its entry is added. Each page adds its own two steps underneath, from
 * the course's `compare_path`.
 *
 * The owner's words, verbatim, from her page of 24 September 2026. The one
 * edit is structural: her closing sentence — «دوره‌های اصلی پایه زبان رو
 * می‌سازن و … دوره‌های مکالمه برای وقتیه که …» — is split at its full stop,
 * and each half becomes the summary of the column it describes, under a
 * heading that already names the subject.
 *
 * `levels` are labels, not links: مکالمه A2 has no page yet.
 *
 * @return array<string,array<string,mixed>>
 */
function zandi_course_families() {
	return (array) apply_filters(
		'zandi_course_families',
		array(
			'main'         => array(
				'title'   => 'دوره‌های اصلی',
				'icon'    => 'layers',
				'levels'  => array( 'پایه A1', 'متوسط A2', 'پیشرفته B1' ),
				'summary' => 'پایه زبان رو می‌سازن و تو رو به حرف زدن می‌رسونن.',
				'items'   => array(
					'تدریس گرامر و ساختار جمله',
					'واژگان و موضوع‌های اصلی هر سطح',
					'صرف فعل‌ها و زمان‌ها',
					'مکالمه‌محور، از همون جلسه اول جمله می‌سازی و حرف می‌زنی',
					'جزوه و تمرین برای هر درس',
					'ساختن پایه محکم برای هر سطح',
				),
			),
			'conversation' => array(
				'title'   => 'دوره‌های مکالمه',
				'icon'    => 'chat',
				'levels'  => array( 'مکالمه A1', 'مکالمه A2' ),
				'summary' => 'برای وقتیه که می‌خوای یه قدم جلوتر بری، روون‌تر حرف بزنی و برای امتحان هم آماده‌تر باشی.',
				'items'   => array(
					'تمرکز کامل روی صحبت کردن',
					'ویدیوهای موضوع‌محور از دل زندگی در فرانسه',
					'موقعیت‌های واقعی: قرار ملاقات، کارهای اداری، اجاره خونه، همسایه',
					'سوال و جواب درباره موضوعات به‌روز',
					'آمادگی برای سوال‌های ممتحن در امتحان',
					'یاد گرفتن روش تمرین و ایده‌پردازی برای حرف زدن',
				),
			),
		)
	);
}

/**
 * The comparison section's own wording.
 *
 * `bridge` is the owner's «یه قدم جلوتر», which is exactly the relationship the
 * arrow between the two columns draws. `path_title` introduces her two steps.
 *
 * @return array<string,string>
 */
function zandi_course_compare_copy() {
	return (array) apply_filters(
		'zandi_course_compare_copy',
		array(
			'title'      => 'فرق دوره‌های اصلی و دوره‌های مکالمه چیه؟ 🤔',
			'bridge'     => 'یه قدم جلوتر',
			'path_title' => 'از کدوم شروع کنم؟',
			'here'       => 'همین دوره',
			'view'       => 'مشاهده دوره',
		)
	);
}

/* -------------------------------------------------------------------------
 * Shared copy — identical on all three pages.
 * ---------------------------------------------------------------------- */

/**
 * Trust bar items.
 *
 * A course may carry its own under `trust_items` — the conversation course says
 * «موضوع‌محور» where the main courses say «متد مکالمه‌محور».
 *
 * @param array<string,mixed> $course Optional. The course on the page.
 * @return array<int,string>
 */
function zandi_course_trust_items( $course = array() ) {
	$items = ! empty( $course['trust_items'] )
		? (array) $course['trust_items']
		: array(
			'۱۴۲ هزار دنبال‌کننده در اینستاگرام',
			'تدریس از پاریس',
			'متد مکالمه‌محور',
			'پشتیبانی ۲۴/۷',
		);

	return apply_filters( 'zandi_course_trust_items', $items, $course );
}

/**
 * The "what you get" cards.
 *
 * A course may carry its own four under `deliverables`. A body may hold line
 * breaks; the partial keeps them.
 *
 * @param array<string,mixed> $course Optional. The course on the page.
 * @return array<int,array{icon:string,title:string,body:string}>
 */
function zandi_course_deliverables( $course = array() ) {
	if ( ! empty( $course['deliverables'] ) ) {
		return apply_filters( 'zandi_course_deliverables', (array) $course['deliverables'], $course );
	}

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
				'body'  => 'یه مسیر اختصاصی برای همین دوره. هر ساعتی از شبانه‌روز سوال داشتی بپرس. اینجا کسی بابت سوال ساده پرسیدن قضاوت نمی‌شه.',
			),
			array(
				'icon'  => 'users',
				'title' => 'مصاحبه پایان دوره با من',
				'body'  => 'آخر دوره یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم داری. فرانسه حرف می‌زنیم، اشکالاتت رو می‌گیرم و بهت می‌گم دقیقاً روی چی باید کار کنی. این تنها راهیه که بفهمی واقعاً کجا ایستادی.',
			),
		),
		$course
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
				'body'  => 'بزرگ‌ترین دلیل نصفه رها کردن زبان، تنهاییه. اینجا هر وقت گیر کنی جواب می‌گیری، تکالیف تصحیح‌شده هست و آخرش خود من هستم.',
			),
		)
	);
}

/**
 * The course-info box rows, in order.
 *
 * The first two are per-course; the rest are identical everywhere — unless the
 * course carries its own list under `info_rows`, as the conversation course
 * does, having homework rather than handouts.
 *
 * @param array $course Course data.
 * @return array<int,array{icon:string,text:string}>
 */
function zandi_course_info_rows( $course ) {
	if ( ! empty( $course['info_rows'] ) ) {
		return apply_filters( 'zandi_course_info_rows', (array) $course['info_rows'], $course );
	}

	return apply_filters(
		'zandi_course_info_rows',
		array(
			array( 'icon' => '🎬', 'text' => $course['sessions_text'] ),
			array( 'icon' => '⏱️', 'text' => $course['hours_text'] ),
			array( 'icon' => '📘', 'text' => 'جزوه، تمرین و فایل تکمیلی' ),
			array( 'icon' => '👤', 'text' => 'تدریس شخص شیما زندی' ),
			array( 'icon' => '♾️', 'text' => 'دسترسی مادام‌العمر' ),
			array( 'icon' => '💬', 'text' => 'پشتیبانی ۲۴ ساعته' ),
			array( 'icon' => '🎤', 'text' => 'مصاحبه ۱۵ دقیقه‌ای پایان دوره' ),
		),
		$course
	);
}

/**
 * The line under every syllabus.
 *
 * Shared across all three levels — it describes how the academy sequences its
 * videos, which is the same everywhere, so it lives here rather than being
 * repeated in each course's entry.
 *
 * It exists to answer a question the accordion above it raises and would
 * otherwise leave hanging: a student who reads five tidy numbered sections
 * expects the videos to arrive in that order, and they do not. Saying so here
 * costs one line; letting them discover it in the player costs trust.
 *
 * @return string
 */
function zandi_curriculum_note() {
	return (string) apply_filters(
		'zandi_curriculum_note',
		'ویدیوها به ترتیب همین سرفصل چیده نشدن. موضوع‌ها به‌طور مساوی بین بخش‌های مختلف دوره پخش شدن تا زودتر به حرف زدن برسی و مسیر یکنواخت نشه.'
	);
}

/**
 * The three jumps under the introduction video.
 *
 * A student who has just watched Shima explain the course has exactly three
 * questions next — what does a lesson look like, what is in it, and did it work
 * for anybody else — and all three answers are already on this page, a long way
 * down. These are the shortcuts.
 *
 * NOT enrolment. Every target is something to look at before deciding, so none
 * of them is the navy «ثبت‌نام در دوره»: three filled buttons here would each
 * be as loud as the one action the page is actually for. They take the same
 * pill, the same padding and the same weight as that button and differ only in
 * fill, which is the pair the hero already uses.
 *
 * Plain anchors, no script. `html { scroll-behavior: smooth }` in style.css
 * does the travelling and `.c-section { scroll-margin-top: 6rem }` keeps the
 * fixed header off the heading it lands on — and both stand down under
 * prefers-reduced-motion, which a hand-rolled scroll would not.
 *
 * A course may carry its own under `jump_links`: the conversation course has no
 * syllabus to jump to, and its middle link goes to the comparison instead.
 *
 * @param array<string,mixed> $course Optional. The course on the page.
 * @return array<int,array<string,string>>
 */
function zandi_course_jump_links( $course = array() ) {
	if ( ! empty( $course['jump_links'] ) ) {
		return (array) apply_filters( 'zandi_course_jump_links', (array) $course['jump_links'], $course );
	}

	return (array) apply_filters(
		'zandi_course_jump_links',
		array(
			array(
				'label'  => 'دیدن نمونه تدریس',
				'target' => 'sample-lesson',
				'icon'   => 'play',
			),
			array(
				'label'  => 'دیدن سرفصل‌ها',
				'target' => 'curriculum',
				'icon'   => 'layers',
			),
			array(
				'label'  => 'رضایت زبان‌آموزها',
				'target' => 'testimonials',
				'icon'   => 'heart',
			),
		),
		$course
	);
}

/**
 * The private study group for one course.
 *
 * Each level has its own — files, exercises, and the class talking to each
 * other — so this is course data, per slug, not a support channel.
 *
 * THIS IS THE ONE PLACE OUTSIDE /contact/ WHERE A MESSAGING APP IS NAMED, and
 * the exception is deliberate. The rule in CLAUDE.md exists so the *support*
 * channel can move without editing forty strings of copy; a student who has
 * paid and is being handed a group link has to be told which app it opens, or
 * the button is a mystery. The URL lives in the catalogue and the label lives
 * in zandi_panel_copy() — two places, both of them course delivery.
 *
 * Owners only. The panel renders it; no public page does.
 *
 * @param string $slug Course slug.
 * @return string URL, or '' when the course has no group.
 */
function zandi_course_group_url( $slug ) {
	$courses = zandi_courses_data();
	$url     = isset( $courses[ $slug ]['group_url'] ) ? $courses[ $slug ]['group_url'] : '';

	/**
	 * Filters a course's study-group URL.
	 *
	 * The seam for moving one group, or all of them to another platform,
	 * without editing the catalogue.
	 *
	 * @param string $url  Group URL, or ''.
	 * @param string $slug Course slug.
	 */
	return (string) apply_filters( 'zandi_course_group_url', $url, $slug );
}

/**
 * How many days of podcast access come free with a course.
 *
 * The bundle, and the whole of it: buying a course grants this many days of
 * پادکست Bonjour Monjour, stacked onto whatever the student already had. The
 * owner set it at thirty for all three levels on 12 September 2026 — one month,
 * the same length as the ماهانه plan, so the gift is worth the same as the
 * cheapest thing on the podcast page rather than being a token.
 *
 * IT LIVES IN THE CATALOGUE, NOT ON THE PRODUCT, and that is the opposite of
 * where the podcast plans keep their days. The reason the plans key on product
 * meta is in inc/podcast.php: a plan IS its number of days, and reading it off
 * a title or a SKU would let a marketing edit change what somebody's money
 * buys. A course is not that. A course is a page in this file with a price, a
 * syllabus and a cover, and the gift attached to it is a decision about the
 * offer — so it belongs beside the price, where the next person looking for it
 * will look, and where it is one number rather than three admin screens that
 * can silently disagree.
 *
 * Zero, or a course with no entry at all, means no gift and nothing renders.
 * That is the honest default for a fourth course added later.
 *
 * @param string $slug Course slug.
 * @return int Days, 0 for none.
 */
function zandi_course_podcast_days( $slug ) {
	/*
	 * The raw catalogue when the WooCommerce bridge is loaded, because this is
	 * called once per order item inside zandi_podcast_compute_expiry() and the
	 * filtered getter runs a product lookup per course for a price this never
	 * reads. Same trick, same reason, as zandi_panel_next_course().
	 */
	$courses = function_exists( 'zandi_courses_raw' ) ? zandi_courses_raw() : zandi_courses_data();
	$days    = isset( $courses[ $slug ]['podcast_days'] ) ? (int) $courses[ $slug ]['podcast_days'] : 0;

	/**
	 * Filters the free podcast days a course carries.
	 *
	 * The seam for a promotion — double it for a fortnight, or drop it to zero
	 * to withdraw the offer — without editing the catalogue.
	 *
	 * @param int    $days Days of podcast access, 0 for none.
	 * @param string $slug Course slug.
	 */
	return max( 0, (int) apply_filters( 'zandi_course_podcast_days', $days, $slug ) );
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
				'a' => 'اگر تا حالا فرانسه نخوندی، A1. اگر خوندی و مطمئن نیستی، از صفحه تماس بهم پیام بده تا با هم مشخصش کنیم. ثبت‌نام توی سطح اشتباه فقط وقت خودت رو می‌گیره.',
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
				'a' => 'از ایران با درگاه بانکی مستقیم روی همین صفحه. از خارج از ایران با یورو و کارت به کارت، برای شماره کارت از صفحه تماس پیام بده. بعد از پرداخت بلافاصله دسترسی برات فعال می‌شه.',
			),
			array(
				'q' => 'پرداخت اقساطی دارید؟',
				'a' => 'فعلاً نه. کل مبلغ یک‌جا پرداخت می‌شه.',
			),
			array(
				'q' => 'تکالیف رو کی تصحیح می‌کنه؟',
				'a' => 'ادمین‌های آکادمی. تکلیف رو می‌فرستی و تصحیح‌شده با توضیح برمی‌گرده.',
			),
			array(
				'q' => 'مصاحبه پایان دوره چطوریه؟',
				'a' => 'یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم. با هم فرانسه حرف می‌زنیم، بهت بازخورد می‌دم و می‌گم قدم بعدیت چیه. هماهنگی‌ش از صفحه تماس انجام می‌شه.',
			),
		)
	);
}

/**
 * Student testimonials on a course page — the same list as everywhere else.
 *
 * DELIBERATELY AN ALIAS. Course pages used to hold their own reviews, on the
 * assumption that a review of the A1 course belonged on the A1 page. The owner
 * settled the opposite on 3 September 2026: a review is about the teaching, so
 * the same three appear on every course page and on the homepage, and a student
 * who wrote after finishing A1 is worth reading on the B1 page too. The one
 * list lives in zandi_testimonials().
 *
 * The function stays rather than being deleted because `zandi_course_testimonials`
 * is a published filter, and a caller that hooks it should keep working — it now
 * filters a copy of the shared list instead of an empty array. Do not refill it
 * with a separate set: two lists is the thing this replaced.
 *
 * @return array<int,array{name:string,quote:string,courses:array<int,string>}>
 */
function zandi_course_testimonials() {
	return apply_filters( 'zandi_course_testimonials', zandi_testimonials() );
}

/**
 * About-Shima copy.
 *
 * The title and photograph are the same on every course page. The words may
 * differ: a course that carries `shima_body` has its own — the conversation
 * course says why she built that course, not the main ones.
 *
 * @param array<string,mixed> $course Optional. The course on the page.
 * @return array<string,mixed>
 */
function zandi_about_shima( $course = array() ) {
	$shima = zandi_about_shima_defaults();

	if ( ! empty( $course['shima_body'] ) ) {
		$shima['body'] = (array) $course['shima_body'];
	}

	return apply_filters( 'zandi_about_shima', $shima, $course );
}

/**
 * About-Shima copy for the main courses — the version every page had until a
 * course could carry its own.
 *
 * @return array<string,mixed>
 */
function zandi_about_shima_defaults() {
	return array(
		'title' => 'من کی‌ام و چرا این دوره رو ساختم',
		'body'  => array(
			'سلام، من شیما زندی‌ام 👋',
			'سال‌هاست فرانسه درس می‌دم و توی پاریس زندگی می‌کنم. کنارش راهنمای تور فرانسوی‌زبان بودم، یعنی سال‌ها کارم این بوده که آدم‌ها رو به هم وصل کنم با زبانی که بلد نیستن.',
			'توی این سال‌ها یه چیز رو بارها دیدم: آدم‌هایی که گرامرشون عالی بود ولی جلوی یه فرانسوی خشکشون می‌زد. مشکل دانسته‌شون نبود، مشکل این بود که هیچ‌وقت واقعاً حرف نزده بودن.',
			'این دوره‌ها رو دقیقاً برای همین ساختم. اینجا از جلسه اول حرف می‌زنی، حتی اگه غلط باشه. غلط گفتن قدم اوله، سکوت هیچ قدمی نیست.',
		),
		'photo' => zandi_shima_photo( 'portrait' ),
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
