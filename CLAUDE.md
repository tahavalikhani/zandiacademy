# Zandi Academy — project context

**Read this before writing any code.** It exists so every agent starts with the
same context and the owner does not have to re-explain the project each time.

---

## What this is

A website for **آکادمی زندی** — a French-language academy **based in Iran**,
teaching Persian speakers. The site's job is to build trust and get visitors to
book a class.

| | |
| --- | --- |
| Platform | **WordPress**, self-hosted |
| This repo | The theme (`wp-content/themes/zandiacademy/`) |
| Site language | **Persian (fa_IR)**, RTL |
| Audience | Iranian residents, paying in **Toman** |
| Teaches | French only — not a multi-language school |

---

## Hard rules

1. **WordPress-native code only.** Everything must be something WordPress can
   read and run directly: PHP templates, hooks, filters, the plugin API. Use
   core functions (`wp_enqueue_script`, `wp_nonce_field`, `esc_html`,
   `wp_safe_redirect`, `add_action`) rather than hand-rolled equivalents.
2. **No build step.** No npm, no bundler, no Tailwind, no page builder. Plain
   CSS and plain JavaScript, committed as-is. The repo previously held a
   React/Vite app; it was deliberately removed. Do not reintroduce one.
3. **Iranian payment methods only.** Stripe, PayPal, Google Pay and every other
   Western processor are unavailable in Iran — never propose them. See
   [`docs/wordpress-iran-stack.md`](docs/wordpress-iran-stack.md).
4. **RTL and Persian throughout.** Logical CSS properties, Persian digits via
   `zandi_fa_digits()`, Jalali dates.
5. **Research before implementing.** Iranian services change fast and plugins
   get pulled from WordPress.org. Check current status on the web before
   recommending or integrating anything — do not rely on memory. A concrete
   example of why: the popular **IDPay** WooCommerce plugin was **closed by
   WordPress.org on 7 April 2026 for a security issue**. Recommending it from
   memory would have shipped a vulnerability.

---

## What the owner wants help with

- Writing code for the site
- Changing the theme
- Configuring WordPress itself — installing and setting up plugins, wiring
  payment gateway APIs, and adding features

Treat WordPress admin configuration as in scope, not just theme code. When a
task needs settings changed in wp-admin, give the exact click path in Persian
UI terms alongside any code.

---

## Repo layout

```
style.css  rtl.css            Theme header + full stylesheet, RTL refinements
functions.php                 Setup, enqueues, nav walker, booking handler
header.php  footer.php
front-page.php                Homepage — section ordering only
index.php  page.php  single.php  comments.php  searchform.php
inc/
  content.php                 Every Persian string, behind apply_filters()
  icons.php                   Inline SVG icon registry
  template-tags.php           Button, badge, avatar, rating, heading helpers
template-parts/home/          One file per homepage section
assets/fonts/                 Vazirmatn variable woff2, self-hosted
assets/js/theme.js            The only JavaScript (~9 KB, no dependencies)
docs/wordpress-iran-stack.md  Iranian payment + plugin research
```

Full detail in [`README.md`](README.md).

---

## Conventions already established

- **Content lives in `inc/content.php`**, every getter wrapped in
  `apply_filters()`. That is the seam for ACF/Customizer later. Do not hard-code
  copy into templates.
- **Section partials compose the helpers in `inc/template-tags.php`** and never
  repeat markup. New visual pattern → helper first.
- **Digits:** Vazirmatn's `ss01` font feature is deliberately **off** — it
  rewrites Latin digits as Persian and corrupts CEFR codes like `A2`/`B2`.
  Localise explicitly with `zandi_fa_digits()`.
- **RTL:** `style.css` uses logical properties so layout mirrors itself;
  `rtl.css` carries only gradient angles and physical `translateX`. Note that
  `inset-inline-start` resolves against the element's **own** `dir` — putting
  `dir="ltr"` on a positioned box flips which edge it anchors to.
- **Progressive enhancement:** `<html>` ships `no-js`, swapped before first
  paint. Nothing may depend on JavaScript to be readable.
- **The booking form** posts to `admin-post.php` with a nonce and fires
  `zandi_booking_submitted( $name, $phone )`. That is currently the only network
  seam in the theme.

---

## Design language — do not drift

Deep French Navy `#1B365D` is the brand and carries ~95% of the visual weight.
French Red `#C8102E` is an accent at small scale only — never a surface or a
button fill. Minimal, lots of white space, 16/20px radii, soft navy-tinted
shadows, subtle motion. The reference register is Apple / Stripe / Notion /
Linear — deliberately **not** a typical Iranian آموزشگاه site.

Typography is **Vazirmatn** (self-hosted variable woff2), fallback IRANSansX.

No Eiffel Tower, no flag graphics, no stock "smiling students with laptops".
French cues come from geometry (guilloché engraving) and language.

---

## Payment — the short version

Full comparison, current plugin status and integration checklist:
**[`docs/wordpress-iran-stack.md`](docs/wordpress-iran-stack.md)**

- Selling courses → **WooCommerce** + an Iranian gateway plugin.
- Default recommendation: **ZarinPal** (اینترنتی واسط, easiest onboarding,
  official plugin maintained by ZarinPal itself, ~50k installs).
- **Do not use IDPay** — plugin closed for a security issue, April 2026.
- Add **`Persian WooCommerce`** for Toman currency, Shamsi dates and Iranian
  province/city lists (100k+ installs, actively maintained).
- Installment payment (**SnappPay**, 4 payments) is worth considering — course
  fees are a large single purchase and competitors in this market offer it.
- Currency: prices are quoted in **تومان**, but most gateways settle in
  **ریال** (1 Toman = 10 Rial). Getting this wrong is a factor-of-ten billing
  bug — always confirm which unit a gateway's API expects.

---

## Things that simply do not work in Iran

Do not propose these; find the local equivalent instead.

| Blocked / unavailable | Use instead |
| --- | --- |
| Stripe, PayPal, Western card processors | ZarinPal, Zibal, NextPay, Pay.ir, direct bank PSPs |
| Google Fonts CDN | Self-hosted fonts (already done — Vazirmatn in `assets/fonts/`) |
| Google reCAPTCHA | Honeypot, Persian captcha plugins, or Cloudflare Turnstile if reachable |
| Google Maps embeds | Neshan (نشان) or Balad (بلد) maps |
| Twilio, Western SMS | Kavenegar, MeliPayamak (ملی‌پیامک), SMS.ir, IPPanel |
| Gmail SMTP for transactional mail | Iranian SMTP or the host's mail server |
| Many CDNs / some plugin update servers | Iranian hosting + mirrors |

Iranian hosting is usually the right call for speed and payment-gateway
whitelisting, but note that WordPress.org update servers are sometimes
unreliable from Iranian IPs — plan for manual plugin updates.

---

## Working agreements

- **Search the web first** for anything involving Iranian services, plugin
  choices, or gateway APIs. Confirm the plugin still exists, is maintained, and
  is not closed for security.
- **Verify before claiming done.** This repo has a habit of it: the theme was
  checked by rendering `front-page.php` through a WordPress API stub and
  screenshot-comparing at 390/1440px. Keep that standard.
- **Say what is unverified.** If something could not be checked (needs a live
  WordPress install, a merchant account, real API credentials), say so plainly
  rather than implying it works.
- **Never commit secrets.** Gateway merchant IDs, API keys and SMS tokens go in
  `wp-config.php` or the options table on the live site — never in this repo.
- Work on the branch you were given; commit and push when the task is complete.
