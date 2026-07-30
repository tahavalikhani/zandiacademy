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
 * Copy for the login page.
 *
 * @return array<string,string>
 */
function zandi_login_copy() {
	return apply_filters(
		'zandi_login_copy',
		array(
			'eyebrow'     => 'پنل دانشجو',
			'title'       => 'خوش برگشتی',
			'description' => 'با شماره موبایلی که باهاش ثبت‌نام کردی وارد شو.',
			'submit'      => 'ورود',
			'alt_prompt'  => 'هنوز حساب نداری؟',
			'alt_action'  => 'ثبت‌نام کن',
			'forgot'      => 'رمزت یادت رفته؟ توی تلگرام پیام بده تا درستش کنیم.',
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
			'description' => 'یک بار ثبت‌نام می‌کنی و بعد از هر دستگاهی به دوره‌هات دسترسی داری.',
			'submit'      => 'ساختن حساب',
			'alt_prompt'  => 'قبلاً حساب ساختی؟',
			'alt_action'  => 'وارد شو',
			'closed'      => 'ثبت‌نام روی سایت هنوز باز نشده. فعلاً برای ثبت‌نام توی دوره‌ها از تلگرام اقدام کن.',
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
			'courses_empty_body' => 'سه سطح پایه، متوسط و پیشرفته آماده‌ست. اگر مطمئن نیستی از کدوم شروع کنی، توی تلگرام بپرس تا با هم پیداش کنیم.',
			'courses_cta'     => 'دوره‌ها رو ببین',
			'interview_title' => 'مصاحبه پایان دوره',
			'interview_body'  => 'وقتی دوره‌ات تموم شد، یه جلسه ۱۵ دقیقه‌ای توی گوگل میت با خودم داری. با هم فرانسه حرف می‌زنیم و می‌گم دقیقاً روی چی باید کار کنی.',
			'interview_empty' => 'هماهنگی مصاحبه بعد از تموم شدن دوره، توی تلگرام انجام می‌شه.',
			'support_title'   => 'پشتیبانی',
			'support_body'    => 'هر جا گیر کردی — یه جمله، یه تلفظ، یه تمرین — توی تلگرام بپرس. پشتیبانی ۲۴ ساعته‌ست، نه فقط ساعت اداری.',
			'support_cta'     => 'باز کردن تلگرام',
			'profile_title'   => 'حساب من',
			'profile_name'    => 'نام',
			'profile_phone'   => 'شماره موبایل',
			'profile_email'   => 'ایمیل',
			'profile_empty'   => 'ثبت نشده',
			'profile_note'    => 'برای تغییر مشخصات حساب، توی تلگرام پیام بده.',
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
