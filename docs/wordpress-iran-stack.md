# Iranian WordPress stack — payment, plugins, infrastructure

Reference for building and running آکادمی زندی on WordPress in Iran.

> **Verified 29 July 2026 (۷ مرداد ۱۴۰۵).** Plugin data comes from the
> WordPress.org plugin API on that date. Iranian services change quickly and
> plugins get pulled — **re-check status before installing anything.** Treat the
> numbers below as a snapshot, not a standing fact.

---

## 1. Why none of the usual answers apply

Iran is cut off from Western payment infrastructure by sanctions. **Stripe,
PayPal, Braintree, Square, Google Pay, Apple Pay and Western card networks are
all unavailable** — not "hard to set up", genuinely unusable. Iranian cards
(شتاب / Shetab network) do not work abroad either.

Every payment on this site therefore runs through an Iranian PSP, and every
integration is a local plugin or a custom gateway class.

---

## 2. Payment gateways

### How the market is structured

**درگاه مستقیم (direct / bank PSP)** — a contract with a bank-affiliated
processor: Saman (سامان), Mellat (به‌پرداخت ملت), Parsian (پارسیان),
Zarrin/Pasargad (پاسارگاد), Sepehr (سپهر). Lower per-transaction fees and money
lands in your account directly, but onboarding needs a registered company
(شرکت), نماد اعتماد الکترونیکی (eNamad), and takes weeks.

**درگاه واسط (aggregator / intermediary)** — ZarinPal, Zibal, NextPay, Pay.ir,
IDPay, AqayePardakht. Sign up in days, often as a sole trader, and they handle
the Shaparak relationship. You pay a commission and settlement is on their
cycle (typically daily/weekly).

**For an academy just starting online payments, an aggregator is the right
call.** Move to a direct PSP later if volume justifies it.

### Comparison

| Gateway | WP plugin | Installs | Status (29 Jul 2026) | Notes |
| --- | --- | --- | --- | --- |
| **ZarinPal** (زرین‌پال) | `zarinpal-woocommerce-payment-gateway` | ~50,000 | v5.1.1, updated 15 Jul 2026, tested to WP 7.0.2 | **Default recommendation.** Plugin published by ZarinPal themselves. Fee ~1% capped at ~4,000﷼ per transaction. Adds Rial/Toman currencies to WooCommerce automatically. |
| **Zibal** (زیبال) | vendor-supplied | — | active | Smart transaction routing, refunds, detailed reporting. Good WooCommerce support. Solid ZarinPal alternative. |
| **NextPay** (نکست‌پی) | vendor-supplied | — | active | Established aggregator. |
| **Pay.ir** (پی) | vendor-supplied | — | active | Established aggregator. |
| **AqayePardakht** (آقای پرداخت) | vendor-supplied | — | active | Lower-friction onboarding. |
| **IDPay** (آیدی‌پی) | `woo-idpay-gateway` | — | ⛔ **CLOSED 7 Apr 2026 — security issue** | **Do not install.** Last release v2.2.5, ~3 years stale, only tested to WP 6.4.8. |

⚠️ **The ZarinPal plugin's own 5.1.1 release notes** record removing "a
hardcoded nonce bypass in the checkout payment-method-switch AJAX handler" —
i.e. it had a real vulnerability until very recently. **Keep it updated**, and
audit any Iranian gateway plugin before trusting it; this ecosystem has a weaker
security-review culture than the wider WordPress one. Check reviews too: some
users report **HPOS (High-Performance Order Storage)** incompatibility, so test
checkout after enabling HPOS.

### Toman vs Rial — the factor-of-ten trap

**1 تومان = 10 ریال.**

Prices are quoted to Iranian customers in **Toman**; most gateway APIs expect
**Rial**. Sending a Toman figure to a Rial endpoint undercharges by 10×;
the reverse overcharges by 10×.

- Set WooCommerce currency explicitly (Persian WooCommerce adds تومان, ریال,
  هزار تومان, هزار ریال).
- Read the gateway's API docs for which unit `amount` takes.
- **Test with a real low-value transaction before launch** — this is the single
  most common Iranian WooCommerce bug.

### Buy-now-pay-later (installments)

Course fees are a large single purchase, and Iranian competitors in the language
-school market advertise installment checkout prominently.

- **SnappPay** (اسنپ‌پی) — customer pays ~25% up front, remainder over 3 further
  months (marketed as 4 payments). Vendor supplies a WooCommerce plugin
  (`snapppay-woocommerce-gateway`), installed by upload rather than from
  WordPress.org, then configured under WooCommerce → Settings.
- **Digipay** (دیجی‌پی) — comparable BNPL, also has WooCommerce integrations.

Both require a separate merchant contract on top of your main gateway. Worth
pursuing once the standard gateway is live — it is a conversion lever, not a
prerequisite.

### Adding a gateway — checklist

1. Confirm the plugin is **currently listed and maintained** on WordPress.org,
   or comes directly from the PSP. Check "last updated" and "tested up to".
2. Register the merchant account; obtain merchant ID / API key.
3. Store credentials in `wp-config.php` or the options table — **never in this
   repo**.
4. Set WooCommerce currency and confirm the Rial/Toman unit the API expects.
5. Whitelist the gateway's callback IPs if the host firewalls them.
6. Test a real small transaction end to end, including the **failed** and
   **cancelled** paths, not just success.
7. Verify the order status transition and that the customer receives
   confirmation.

---

## 3. Selling courses — WooCommerce vs LMS

The academy sells **enrolment in taught classes**, not self-paced video
courses. That distinction drives the choice.

- **WooCommerce alone** — treat each course/term as a product. Simplest, fewest
  moving parts, and every Iranian gateway plugin targets WooCommerce first.
  **Recommended starting point.**
- **Tutor LMS** — free + pro. Sells courses *through* WooCommerce, so gateways
  keep working. Has Persian localisations available. Right choice if online
  lessons, quizzes and student progress move onto the site.
- **LearnPress** — free, ~90k+ installs, widely used in Iran, similar model.

Do not add an LMS until there is actual course content to host. A booking form
plus WooCommerce covers enrolment.

---

## 4. Persian localisation

| Plugin | Installs | Status | What it does |
| --- | --- | --- | --- |
| **Persian WooCommerce** (ووکامرس فارسی) | 100,000+ | v10.0.4, updated 12 Jun 2026 | Toman/Rial currencies, Shamsi reports, Iranian province & city lists, Persian WooCommerce translation, gateway integrations. **Install this with WooCommerce.** |
| **WP-Parsidate** (پارسی دیت) | 100,000+ | v6.2.1, updated 24 Jul 2026 | Shamsi (Jalali) calendar throughout WP admin and front end, Persian digit conversion, RTL fixes. Integrates with WooCommerce and ACF. |
| **Persian WooCommerce SMS** | — | active | Order/status SMS via Iranian gateways incl. Kavenegar and MeliPayamak. |

**Note:** the theme already handles its own Persian digits
(`zandi_fa_digits()`) and Jalali year. If WP-Parsidate is installed, check the
two are not double-converting.

---

## 5. SMS and OTP login

Iranian sites commonly log users in by **mobile number + OTP** rather than
email/password — expect users to want this.

Providers: **Kavenegar** (کاوه‌نگار), **MeliPayamak** (ملی‌پیامک), **SMS.ir**,
**IPPanel**. Twilio and Western SMS APIs are unavailable.

Plugin options:
- **OTP Login With Phone Number** (`login-with-phone-number`) — WooCommerce-
  compatible, replaces/extends login, checkout and registration forms; supports
  Kavenegar.
- **JAY Login & Register** — Kavenegar SMS and voice OTP, plus MeliPayamak.
- **miniOrange OTP Verification** — broader, more configuration.

SMS is also the right channel for booking confirmations: an Iranian user is far
likelier to read an SMS than an email.

---

## 6. Infrastructure and sanctions

- **Hosting:** Iranian hosts (ایران‌سرور, پارس‌پک, میزبان‌فا …) are usually
  right — faster domestically, and some PSPs expect an Iranian origin IP.
  Trade-off: WordPress.org update servers can be unreliable from Iranian IPs, so
  plan for manual plugin/core updates and check for security releases yourself.
- **Google services are blocked in Iran** — Fonts, reCAPTCHA, Maps, Analytics
  all fail or hang. A blocked font/script request is not silent: it stalls page
  load. The theme already self-hosts Vazirmatn for exactly this reason.
  - CAPTCHA → honeypot plugins, Akismet, or an Iranian captcha
  - Maps → **Neshan** (نشان) or **Balad** (بلد)
  - Analytics → self-hosted **Matomo**
- **Email deliverability** is poor from Iranian IPs to Gmail. Prefer SMS for
  anything time-sensitive.
- **eNamad (نماد اعتماد الکترونیکی)** — the trust seal is effectively required
  for a commercial Iranian site and is a prerequisite for most direct PSP
  contracts. It is also a genuine conversion signal; budget time for the
  application and plan a footer slot for the badge.

---

## 7. Open questions for the owner

These need business decisions before the corresponding code can be finalised:

1. **Which gateway account** will the academy open — ZarinPal, or is there an
   existing bank PSP contract?
2. **Is there a registered company and eNamad**, or is this a sole trader? This
   determines whether a direct PSP is even available.
3. **Full online payment, or deposit-then-invoice?** The current homepage form
   books a free consultation and takes no money — that may be the right flow to
   keep.
4. **Installments** — worth pursuing SnappPay, given competitors advertise it?
5. **SMS provider** — any existing account with Kavenegar or similar?

---

## Sources

- [ZarinPal for WooCommerce — WordPress.org](https://wordpress.org/plugins/zarinpal-woocommerce-payment-gateway/)
- [ZarinPal official WooCommerce docs](https://www.zarinpal.com/docs/extensions/official-Woocommerce)
- [IDPay WooCommerce plugin — closed listing](https://wordpress.org/plugins/woo-idpay-gateway/)
- [Persian WooCommerce — WordPress.org](https://wordpress.org/plugins/persian-woocommerce/)
- [WP-Parsidate — WordPress.org](https://wordpress.org/plugins/wp-parsidate/)
- [Persian WooCommerce SMS — WordPress.org](https://wordpress.org/plugins/persian-woocommerce-sms/)
- [OTP Login With Phone Number — WordPress.org](https://wordpress.org/plugins/login-with-phone-number/)
- [SnappPay merchant academy — WooCommerce plugin guide](https://academy.snapppay.ir/)
- [SnappPay official site](https://snapppay.ir/)
- [ParsPack — comparison of Iranian payment gateways](https://parspack.com/blog/online-business/payment-gateway)
- [MihanWP — best Iranian payment gateways](https://mihanwp.com/best-payment-gateways/)
- [MihanWP — WordPress LMS plugins](https://mihanwp.com/wordpress-lms-plugins/)
- [Censorship of Google — Wikipedia](https://en.wikipedia.org/wiki/Censorship_of_Google)
