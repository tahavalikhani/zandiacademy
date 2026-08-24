<?php
/**
 * Copy and data for the account pages and the student panel.
 *
 * Same rules as inc/content.php: every string lives here behind a filter, and
 * nothing is invented. The panel currently has no purchases to show because
 * payment is not connected, so `zandi_student_courses()` returns an empty array
 * and the template draws an honest empty state rather than a fake course card.
 *
 * Voice: second person singular («تو»), the same as the course pages.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Browser titles for the account routes, keyed by zandi_account_routes().
 *
 * One per route, and they have to stay distinct — the whole point of the
 * filter that reads them (zandi_account_title() in inc/auth.php) is that these
 * three pages used to share a single title.
 *
 * @return array<string,string>
 */
function zandi_account_titles() {
	return apply_filters(
		'zandi_account_titles',
		array(
			'login'    => 'ورود به حساب کاربری',
			'register' => 'ساختن حساب کاربری',
			'logout'   => 'خروج از حساب',
			'panel'    => 'پنل دانشجو',
		)
	);
}

/**
 * Copy for the login page.
 *
 * @return array<string,string>
 */
function zandi_login_copy() {
	$otp = zandi_otp_provider_active();

	return apply_filters(
		'zandi_login_copy',
		array(
			'eyebrow' => 'پنل دانشجو',

			/*
			 * Signing in and signing up are two pages, so this heading names one
			 * job only. It said «ورود یا ثبت‌نام» while the plan was a single
			 * combined form; promising both on a page that only signs people in
			 * is how a new student ends up typing a number that gets rejected.
			 */
			'title'       => 'خوش برگشتی',
			'description' => $otp
				? 'شماره موبایلت رو بنویس تا یه کد برات بفرستم.'
				: 'با شماره موبایلی که باهاش ثبت‌نام کردی وارد شو.',
			'submit'      => 'ورود',

			'alt_prompt'  => 'هنوز حساب نداری؟',
			'alt_action'  => 'ثبت‌نام کن',

			'forgot'      => $otp
				? 'کد نرسید؟ چند ثانیه صبر کن و دوباره بزن. اگر باز هم نیومد از صفحه تماس پیام بده.'
				: 'رمزت یادت رفته؟ از صفحه تماس پیام بده تا درستش کنیم.',
		)
	);
}

/**
 * Copy for the registration page.
 *
 * @return array<string,string>
 */
function zandi_register_copy() {
	return apply_filters(
		'zandi_register_copy',
		array(
			'eyebrow'     => 'پنل دانشجو',
			'title'       => 'حسابت رو بساز',
			'description' => zandi_otp_provider_active()
				? 'شماره موبایلت رو بنویس تا یه کد برات بفرستم و حسابت ساخته بشه.'
				: 'یک بار ثبت‌نام می‌کنی و بعد از هر دستگاهی به دوره‌هات دسترسی داری.',
			'submit'      => 'ساختن حساب',
			'alt_prompt'  => 'قبلاً حساب ساختی؟',
			'alt_action'  => 'وارد شو',
			'closed'      => 'ثبت‌نام روی سایت هنوز باز نشده. فعلاً برای ثبت‌نام توی دوره‌ها از صفحه تماس اقدام کن.',
		)
	);
}

/**
 * Shared field labels and hints.
 *
 * @return array<string,string>
 */
function zandi_auth_fields() {
	return apply_filters(
		'zandi_auth_fields',
		array(
			'name'           => 'نام و نام خانوادگی',
			'name_hint'      => 'همون اسمی که دوست داری صدات کنم.',
			'phone'          => 'شماره موبایل',
			'phone_hint'     => 'با همین شماره وارد می‌شی، پس یادت بمونه.',
			'identifier'     => 'شماره موبایل',
			'email'          => 'ایمیل (اختیاری)',
			'email_hint'     => 'اگر بنویسی، بازیابی رمز عبور راحت‌تره.',
			'password'       => 'رمز عبور',
			'password_hint'  => 'حداقل ۸ کاراکتر.',
			'remember'       => 'من رو به خاطر بسپار',
		)
	);
}

/**
 * The reassurance panel beside the auth form.
 *
 * Answers the one question a phone-number field always raises — «چرا باید
 * شماره‌م رو بدم؟». Nothing here is aspirational: each line names a section of
 * the panel that already exists, so the promise is kept the moment a student
 * signs in.
 *
 *   - «دوره‌های من»  → template-parts/panel/courses.php
 *   - «مصاحبه پایان دوره» → template-parts/panel/interview.php
 *   - «پشتیبانی» → template-parts/panel/support.php
 *
 * This item said «مصاحبه و تعیین سطح» and promised the panel tracked a level
 * assessment. It never did — that partial is the end-of-course interview, and
 * the academy runs no assessment session at all. Named for what it shows.
 *
 * @return array<string,mixed>
 */
function zandi_auth_benefits() {
	return apply_filters(
		'zandi_auth_benefits',
		array(
			'title' => 'حساب دانشجویی برای چیه؟',
			'items' => array(
				array(
					'icon'  => 'layers',
					'title' => 'دوره‌هات، هر جا که باشی',
					'body'  => 'یک بار ثبت‌نام می‌کنی و بعد از موبایل و لپ‌تاپ به همون دوره‌ها دسترسی داری.',
				),
				array(
					'icon'  => 'target',
					'title' => 'مصاحبه پایان دوره',
					'body'  => 'وقت مصاحبه‌ی پایان دوره‌ت رو از توی پنل دنبال می‌کنی.',
				),
				array(
					'icon'  => 'chat',
					'title' => 'پشتیبانی مستقیم',
					'body'  => 'سؤال درسی و تصحیح تمرین، بدون واسطه و مستقیم از خودم.',
				),
			),

			/*
			 * Said on the page rather than left to be inferred. The number is
			 * the username here, so a student is right to ask what happens to
			 * it, and «پیام تبلیغاتی نمی‌فرستم» is a promise the site keeps —
			 * there is no marketing SMS integration and none is planned.
			 */
			'note'  => 'شماره‌ت فقط برای ورود و خبر دادن درباره‌ی کلاس‌هاته. پیام تبلیغاتی نمی‌فرستم.',
		)
	);
}

/**
 * The error message attached to one field, if there is one.
 *
 * Presentation only: `zandi_auth_errors()` already carries a WP_Error whose
 * codes name the field that failed, and this reads them. The summary above the
 * form still renders every message — a screen reader needs one announcement,
 * not five — but sighted students should not have to map «شماره موبایل باید…»
 * back to a field by eye.
 *
 * Several codes can point at the same input: `phone` is a malformed number and
 * `phone_taken` is a real one that is already registered.
 *
 * @param string $field Field key: name, phone, email, password or identifier.
 * @return string The first matching message, or '' when the field is fine.
 */
function zandi_auth_field_error( $field ) {
	$map = apply_filters(
		'zandi_auth_error_fields',
		array(
			'name'       => array( 'name' ),
			'phone'      => array( 'phone', 'phone_taken' ),
			'email'      => array( 'email', 'email_taken' ),
			'password'   => array( 'password' ),
			'identifier' => array( 'identifier', 'unknown' ),
		)
	);

	if ( empty( $map[ $field ] ) ) {
		return '';
	}

	$errors = zandi_auth_errors();

	foreach ( $map[ $field ] as $code ) {
		$message = $errors->get_error_message( $code );

		if ( '' !== $message ) {
			return $message;
		}
	}

	return '';
}

/**
 * Copy for the panel itself.
 *
 * @return array<string,mixed>
 */
function zandi_panel_copy() {
	return apply_filters(
		'zandi_panel_copy',
		array(
			'eyebrow'         => 'پنل دانشجو',
			'greeting'        => 'سلام %s',
			'intro'           => 'همه‌چیزِ دوره‌هات از همین‌جا شروع می‌شه.',
			'courses_title'   => 'دوره‌های من',
			'courses_empty'   => 'هنوز توی هیچ دوره‌ای ثبت‌نام نکردی.',
			'courses_empty_body' => 'سه سطح پایه، متوسط و پیشرفته آماده‌ست. اگر مطمئن نیستی از کدوم شروع کنی، از صفحه تماس بپرس تا با هم پیداش کنیم.',
			'courses_cta'     => 'دوره‌ها رو ببین',

			/*
			 * The licence label was hard-coded in the partial. Copy lives behind
			 * a getter, so it moved here with the pending strings.
			 *
			 * The pending state is not a nicety. The licence is created by a
			 * background job now, so between paying and that job finishing there
			 * is a window — usually seconds — where the student owns the course
			 * and has no key. The panel used to render nothing at all in that
			 * window: no licence block, no download button, just a bare card.
			 * To someone who has only just paid, that reads as a failed purchase.
			 */
			'licence_label'   => 'کلید لایسنس اسپات پلیر',
			'licence_pending' => 'لایسنس در حال آماده‌سازیه',
			'licence_pending_body' => 'خریدت ثبت شده. کلید لایسنس و لینک دانلود پلیر تا یکی دو دقیقه دیگه همین‌جا ظاهر می‌شه — صفحه رو تازه کن.',
			'interview_title' => 'مصاحبه پایان دوره',
			'interview_body'  => 'وقتی دوره‌ات تموم شد، یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم داری. با هم فرانسه حرف می‌زنیم و می‌گم دقیقاً روی چی باید کار کنی.',
			'interview_empty' => 'هماهنگی مصاحبه بعد از تموم شدن دوره، از صفحه تماس انجام می‌شه.',
			'support_title'   => 'پشتیبانی',
			'support_body'    => 'هر جا گیر کردی — یه جمله، یه تلفظ، یه تمرین — بپرس. پشتیبانی ۲۴ ساعته‌ست، نه فقط ساعت اداری.',
			'support_cta'     => 'تماس با من',
			'profile_title'   => 'حساب من',
			'profile_name'    => 'نام',
			'profile_phone'   => 'شماره موبایل',
			'profile_email'   => 'ایمیل',
			'profile_empty'   => 'ثبت نشده',
			'profile_note'    => 'برای تغییر مشخصات حساب، از صفحه تماس پیام بده.',
			'logout'          => 'خروج از حساب',
		)
	);
}

/**
 * The courses this student has access to.
 *
 * Empty until enrolment is wired. When WooCommerce and the SpotPlayer plugin
 * are installed, hook this filter and return one entry per paid order:
 *
 *     array(
 *       'title'   => 'دوره پایه A1',
 *       'level'   => 'A1',
 *       'url'     => home_url( '/courses/a1/' ),
 *       'licence' => '…',   // SpotPlayer licence key
 *       'player'  => '…',   // player download URL
 *     )
 *
 * TODO: connect to WooCommerce orders once ZarinPal is live.
 *
 * @param int $user_id Optional user ID. Defaults to the current user.
 * @return array<int,array<string,string>>
 */
function zandi_student_courses( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();

	return apply_filters( 'zandi_student_courses', array(), $user_id );
}

/**
 * The panel's own navigation.
 *
 * @return array<int,array{label:string,url:string}>
 */
function zandi_panel_nav() {
	return apply_filters(
		'zandi_panel_nav',
		array(
			array( 'label' => 'دوره‌های من', 'url' => '#my-courses' ),
			array( 'label' => 'حساب من', 'url' => '#my-account' ),
			array( 'label' => 'پشتیبانی', 'url' => '#support' ),
		)
	);
}
