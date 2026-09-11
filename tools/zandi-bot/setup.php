<?php
/**
 * The bot's one screen.
 *
 * Behind the setup key, and temporary in spirit: it exists so somebody can see
 * whether the machine is alive without reading a log file over FTP.
 *
 * @package Zandi
 */

defined( 'ZANDI_BOT' ) || exit;

$config = zandi_bot_config();
$key    = (string) ( $_GET['key'] ?? '' );
$action = (string) ( $_GET['do'] ?? '' );
$notice = '';

if ( 'set' === $action ) {
	$result = zandi_bot_call(
		'setWebhook',
		array(
			'url'             => rtrim( (string) $config['webhook_url'], '/' ) . '/',
			'secret_token'    => $config['secret'],
			'allowed_updates' => json_encode( ZANDI_BOT_UPDATES ),
		)
	);

	$notice = ! empty( $result['ok'] ) ? 'وبهوک ثبت شد.' : 'ثبت وبهوک شکست خورد: ' . ( $result['description'] ?? $result['error'] ?? '?' );
}

if ( 'test' === $action ) {
	zandi_bot_notify( "🔔 پیام تست. اگر این رو می‌بینی، خبررسانی کار می‌کنه." );
	$notice = 'پیام تست فرستاده شد.';
}

if ( 'sweep' === $action ) {
	$stats  = zandi_bot_sweep();
	$notice = sprintf( 'جارو انجام شد: %d نفر بررسی، %d یادآوری، %d حذف.', $stats['checked'], $stats['reminded'], $stats['removed'] );
}

if ( 'link' === $action ) {
	$made   = zandi_bot_group_link();
	$notice = $made ? 'لینک گروه ساخته شد.' : 'ساختن لینک نشد — ربات باید ادمین باشه با اجازه‌ی «دعوت کاربران با لینک».';
}

$me    = zandi_bot_call( 'getMe' );
$hook  = zandi_bot_call( 'getWebhookInfo' );
$store = zandi_bot_live_store();
$log   = is_readable( ZANDI_BOT_LOG ) ? (string) file_get_contents( ZANDI_BOT_LOG ) : '';
$lines = array_slice( array_filter( explode( "\n", str_replace( '<?php exit; ?>', '', $log ) ) ), -25 );

$total = count( $store['users'] );
$live  = 0;
$bound = 0;

foreach ( $store['users'] as $row ) {
	if ( ! empty( $row['tg'] ) ) {
		++$bound;
	}

	if ( zandi_bot_row_live( (array) $row ) ) {
		++$live;
	}
}

$cron = sprintf( 'curl -s "%s/?cron=%s" > /dev/null', rtrim( (string) $config['webhook_url'], '/' ), $config['setup_key'] );

header( 'Content-Type: text/html; charset=utf-8' );
?>
<!doctype html>
<html lang="fa" dir="rtl">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>ربات پادکست</title>
<style>
body{font-family:Tahoma,system-ui,sans-serif;max-width:48rem;margin:0 auto;padding:24px 18px 60px;background:#f6f7f9;color:#16202f;line-height:1.7}
h1{font-size:1.4rem;margin:0 0 4px}
h2{font-size:1.05rem;margin:28px 0 8px}
.card{background:#fff;border:1px solid #e1e6ed;border-radius:10px;padding:14px 16px;margin-top:12px}
.ok{color:#1b6e52;font-weight:700}.bad{color:#b3261e;font-weight:700}
code,pre{font-family:ui-monospace,Menlo,monospace;font-size:.82rem;direction:ltr;unicode-bidi:isolate}
pre{background:#eff2f6;border:1px solid #e1e6ed;border-radius:8px;padding:12px;overflow-x:auto;text-align:left}
a.btn{display:inline-block;background:#1b365d;color:#fff;text-decoration:none;padding:8px 16px;border-radius:8px;margin:6px 8px 0 0;font-size:.9rem}
a.btn.grey{background:#5b6980}
.note{background:#fff6e5;border:1px solid #f0dcb4;border-radius:8px;padding:12px 14px;margin-top:12px;font-size:.92rem}
.nums{display:grid;grid-template-columns:repeat(auto-fit,minmax(7rem,1fr));gap:1px;background:#e1e6ed;border:1px solid #e1e6ed;border-radius:8px;overflow:hidden}
.nums div{background:#fff;padding:12px 14px}
.nums b{display:block;font-size:1.5rem;line-height:1.2}
.nums span{font-size:.78rem;color:#5b6980}
dt{font-weight:700;font-size:.85rem;color:#5b6980;margin-top:8px}
dd{margin:0}
</style>

<h1>ربات پادکست Bonjour Monjour</h1>
<p style="color:#5b6980;margin:0">صفحه‌ی مدیریت. فقط با کلید باز می‌شه.</p>

<?php if ( $notice ) : ?>
	<div class="note"><?php echo htmlspecialchars( $notice, ENT_QUOTES, 'UTF-8' ); ?></div>
<?php endif; ?>

<h2>وضعیت</h2>
<div class="card">
	<dl>
		<dt>ربات</dt>
		<dd><?php echo ! empty( $me['ok'] ) ? '<span class="ok">✅ @' . htmlspecialchars( (string) ( $me['result']['username'] ?? '?' ), ENT_QUOTES, 'UTF-8' ) . '</span>' : '<span class="bad">❌ ' . htmlspecialchars( (string) ( $me['description'] ?? $me['error'] ?? '?' ), ENT_QUOTES, 'UTF-8' ) . '</span>'; ?></dd>
		<dt>وبهوک</dt>
		<dd><code><?php echo htmlspecialchars( (string) ( $hook['result']['url'] ?? '—' ), ENT_QUOTES, 'UTF-8' ); ?></code></dd>
		<dt>در صف / آخرین خطا</dt>
		<dd><code><?php echo (int) ( $hook['result']['pending_update_count'] ?? 0 ); ?></code> / <code><?php echo htmlspecialchars( (string) ( $hook['result']['last_error_message'] ?? '—' ), ENT_QUOTES, 'UTF-8' ); ?></code></dd>
		<dt>لینک گروه</dt>
		<dd><code><?php echo htmlspecialchars( $store['link'] ?: '— هنوز ساخته نشده', ENT_QUOTES, 'UTF-8' ); ?></code></dd>
	</dl>
	<p>
		<a class="btn" href="?key=<?php echo rawurlencode( $key ); ?>&amp;do=test">پیام تست</a>
		<a class="btn grey" href="?key=<?php echo rawurlencode( $key ); ?>&amp;do=set">ثبت وبهوک</a>
		<a class="btn grey" href="?key=<?php echo rawurlencode( $key ); ?>&amp;do=link">ساختن لینک گروه</a>
		<a class="btn grey" href="?key=<?php echo rawurlencode( $key ); ?>&amp;do=sweep">جارو کن</a>
	</p>
</div>

<h2>مشترک‌ها</h2>
<div class="card">
	<div class="nums">
		<div><b><?php echo (int) $total; ?></b><span>کل رکوردها</span></div>
		<div><b><?php echo (int) $live; ?></b><span>اشتراک فعال</span></div>
		<div><b><?php echo (int) $bound; ?></b><span>تلگرام وصل‌شده</span></div>
	</div>
	<p style="font-size:.9rem;color:#5b6980;margin:12px 0 0">
		این‌ها فقط کسایی‌ان که سایت ازشون خبر داده. اعضای قدیمی گروه که هنوز حساب وصل نکردن اینجا نیستن — و
		<strong>جارو هیچ‌وقت به کسی که نمی‌شناسه دست نمی‌زنه</strong>، پس جاشون امنه.
	</p>
</div>

<h2>جارو، هر شب</h2>
<div class="card">
	<p style="margin:0 0 10px;font-size:.92rem">این خط رو توی <strong>DirectAdmin ← Cron Jobs</strong> بذار. یک بار در شبانه‌روز کافیه.</p>
	<pre><?php echo htmlspecialchars( $cron, ENT_QUOTES, 'UTF-8' ); ?></pre>
	<p style="font-size:.88rem;color:#5b6980;margin:10px 0 0">اگر <code>setup_key</code> رو عوض کردی، این خط رو هم عوض کن.</p>
</div>

<h2>چه اتفاقی افتاده</h2>
<div class="card">
	<?php if ( $lines ) : ?>
		<pre><?php echo htmlspecialchars( implode( "\n", $lines ), ENT_QUOTES, 'UTF-8' ); ?></pre>
	<?php else : ?>
		<p style="margin:0;color:#5b6980">هنوز چیزی ثبت نشده.</p>
	<?php endif; ?>
</div>
