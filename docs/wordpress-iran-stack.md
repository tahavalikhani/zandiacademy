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

**Correction (30 July 2026).** An earlier draft of this document said the
academy sells *"enrolment in taught classes, not self-paced video courses"*.
That is wrong, and it pointed at the wrong architecture. The three published
courses are **pre-recorded, self-paced video** — A1 is 78 sessions, A2 is 100,
B1 is 59 — delivered on **SpotPlayer**, with lifetime access, corrected
exercises over Telegram and a live 15-minute interview at the end. The live,
human parts sit *around* the videos; they are not the product being sold.

- **WooCommerce alone** — treat each course/term as a product. Simplest, fewest
  moving parts, and every Iranian gateway plugin targets WooCommerce first.
  **Recommended starting point.**
- **Tutor LMS** — free + pro. Sells courses *through* WooCommerce, so gateways
  keep working. Has Persian localisations available. Right choice if online
  lessons, quizzes and student progress move onto the site.
- **LearnPress** — free, ~90k+ installs, widely used in Iran, similar model.

Do not add an LMS. WooCommerce plus SpotPlayer covers the whole chain.

### SpotPlayer (اسپات پلیر)

The courses already live there, which makes it part of the stack whether or not
it was chosen deliberately.

SpotPlayer publishes an **official WooCommerce plugin**: after a paid order it
generates a licence keyed to the buyer's **mobile number** and shows the licence
key plus the player and video download links on the order-received page. Course
creation, video upload and licence management are also reachable over their HTTP
API with an account API key.

Two consequences for this theme:

1. **The mobile number is the join key.** A student's phone links their
   WordPress account, their WooCommerce order and their SpotPlayer licence. This
   is why `inc/auth.php` stores the phone as `user_login` *and* mirrors it into
   `billing_phone` — WooCommerce writes that field at checkout and the SpotPlayer
   plugin reads it.
2. **The panel does not need a video player.** `zandi_student_courses()` returns
   licence keys and download links, not streams.

> **Unverified.** `spotplayer.ir` is not reachable from the machine this was
> written on — every request is reset at the proxy. The above comes from search
> results quoting SpotPlayer's own documentation, not from reading it directly.
> Confirm the plugin's current name, version and settings before installing, and
> re-check the licence-per-device limit (the course pages promise 2 devices).

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

### Chosen: Digits + نجوا (30 July 2026)

**Digits** (`unitedover.com`, CodeCanyon) is installed and running the sign-in
flow, with **نجوا** as the SMS gateway. Digits supports 100+ SMS panels and a
custom-API gateway, so an Iranian provider is a settings screen rather than
code. It gives exactly the flow the academy wanted: one field for a mobile
number *or* an email, an OTP, an existing account signed in and a new one
registered after asking for a full name.

> ⚠️ **Digits carried a critical vulnerability.**
> [CVE-2025-4094](https://wpscan.com/vulnerability/b5f0a263-644b-4954-a1f0-d08e2149edbb/)
> — **CVSS 9.8** — the plugin applied *no rate limit* to OTP validation, so all
> 999,999 codes could simply be enumerated and any account taken over, admin
> included. Public exploit code exists. **Fixed in 8.4.6.1**; the site runs 9.x.
> This plugin sits directly in the authentication path: keep it updated, and if
> the licence ever lapses so it stops receiving updates, treat that as a reason
> to reconsider, not a minor inconvenience.

The theme does not hard-depend on it. It detects `df_digits_form()`, and without
it falls back to its own phone + password form — see `inc/auth.php`.

**Alternative, if Digits is ever dropped:**
- **OTP Login With Phone Number** (`login-with-phone-number`) — free,
  WooCommerce-compatible, supports Kavenegar and DrPayamak. As of 30 July 2026:
  v1.8.71, updated within the week, no closure notice — but only **900+ active
  installs**, which is thin for the auth path.
- **JAY Login & Register** — Kavenegar SMS and voice OTP, plus MeliPayamak.
- **miniOrange OTP Verification** — broader, more configuration.

SMS is also the right channel for order confirmations: an Iranian user is far
likelier to read an SMS than an email.

### Email OTP — the trap worth naming

Digits can accept an email as well as a number in the same field. Before turning
that on, understand the failure mode: the code has to be delivered by email, and
**mail from Iranian infrastructure to Gmail is routinely dropped**. Because the
flow is passwordless, a student who signs up by email and never receives the
code has *no other way into their account*.

If email is enabled anyway:

- **نجوا sells transactional email over SMTP** alongside the SMS panel — same
  vendor, same احراز هویت, one bill. That is the natural choice here.
- Bridge it with **FluentSMTP** (free, 600k+ installs, generic SMTP, no premium
  upsell). WordPress's `wp_mail()` then carries Digits' email OTP.
- Add the SPF/DKIM records نجوا specifies to the domain's DNS. Without them the
  mail is spam-foldered regardless of provider.
- **Verify by sending a test to a real Gmail address and watching it arrive**
  before letting students choose that path.

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

## 7. Decisions and sequencing

### Sequencing

**Frontend first.** Payment, SMS and plugin work happen later, section by
section, on the owner's signal. Nothing in this document is a backlog to start
working through — it is reference for when each piece comes up.

The homepage is being **rebuilt entirely**, so do not build payment UI against
the current sections.

### Settled (29 July 2026)

| Question | Decision | What it implies |
| --- | --- | --- |
| Gateway | **ZarinPal** | Use the official `zarinpal-woocommerce-payment-gateway` plugin. Keep it current. |
| eNamad | **Not yet, coming soon** | Confirms the aggregator route — a direct bank PSP needs the seal and a registered company. Reserve a footer slot for the badge. |
| Booking flow | **Homepage being rebuilt** | The current free-consultation form is not final; don't harden it. |
| Installments (SnappPay) | **Not now** | Revisit once standard payment is live. |
| SMS | **Wanted, no account yet** | Provider still to be chosen — see §5. |

### Still open

- Full payment at enrolment, or deposit + invoice?
- Which SMS provider — Kavenegar, ملی‌پیامک, SMS.ir, IPPanel?
- What the rebuilt homepage should contain.

### Notes for when payment work starts

Because eNamad is pending, sequence it as: get the ZarinPal merchant account →
install WooCommerce + Persian WooCommerce → install the ZarinPal plugin →
confirm the Toman/Rial unit (§2) → test a real low-value transaction including
the failed and cancelled paths → add the eNamad badge when the seal arrives.

---

## Sources

- [ZarinPal for WooCommerce — WordPress.org](https://wordpress.org/plugins/zarinpal-woocommerce-payment-gateway/)
- [ZarinPal official WooCommerce docs](https://www.zarinpal.com/docs/extensions/official-Woocommerce)
- [IDPay WooCommerce plugin — closed listing](https://wordpress.org/plugins/woo-idpay-gateway/)
- [Persian WooCommerce — WordPress.org](https://wordpress.org/plugins/persian-woocommerce/)
- [WP-Parsidate — WordPress.org](https://wordpress.org/plugins/wp-parsidate/)
- [Persian WooCommerce SMS — WordPress.org](https://wordpress.org/plugins/persian-woocommerce-sms/)
- [OTP Login With Phone Number — WordPress.org](https://wordpress.org/plugins/login-with-phone-number/)
- [Digits — vendor site](https://digits.unitedover.com/) · [placing the form in a template](https://help.unitedover.com/digits/kb/how-to-place-digits-form-on-any-page/)
- [CVE-2025-4094 — Digits OTP auth bypass (WPScan)](https://wpscan.com/vulnerability/b5f0a263-644b-4954-a1f0-d08e2149edbb/)
- [نجوا — SMS web service](https://www.najva.com/%D9%88%D8%A8-%D8%B3%D8%B1%D9%88%DB%8C%D8%B3-%D9%BE%DB%8C%D8%A7%D9%85%DA%A9/) · [email marketing / transactional](https://www.najva.com/%D8%B3%D8%B1%D9%88%DB%8C%D8%B3-%D8%A7%DB%8C%D9%85%DB%8C%D9%84-%D9%85%D8%A7%D8%B1%DA%A9%D8%AA%DB%8C%D9%86%DA%AF/)
- [FluentSMTP — WordPress.org](https://wordpress.org/plugins/fluent-smtp/)
- [SpotPlayer — WooCommerce plugin docs](https://spotplayer.ir/help/api/woocommerce) (unreachable from the authoring machine; see §3)
- [SpotPlayer — API overview](https://spotplayer.ir/help/api)
- [wp_insert_user() — WordPress developer reference](https://developer.wordpress.org/reference/functions/wp_insert_user/) (confirms `user_email` is optional)
- [SnappPay merchant academy — WooCommerce plugin guide](https://academy.snapppay.ir/)
- [SnappPay official site](https://snapppay.ir/)
- [ParsPack — comparison of Iranian payment gateways](https://parspack.com/blog/online-business/payment-gateway)
- [MihanWP — best Iranian payment gateways](https://mihanwp.com/best-payment-gateways/)
- [MihanWP — WordPress LMS plugins](https://mihanwp.com/wordpress-lms-plugins/)
- [Censorship of Google — Wikipedia](https://en.wikipedia.org/wiki/Censorship_of_Google)
