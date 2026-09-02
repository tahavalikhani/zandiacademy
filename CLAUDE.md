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
functions.php                 Setup, enqueues, nav walker, routing
header.php  footer.php
front-page.php                Homepage — section ordering only
index.php  page.php  single.php  comments.php  searchform.php
inc/
  content.php                 Every Persian string, behind apply_filters()
  icons.php                   Inline SVG icon registry
  template-tags.php           Button, badge, avatar, rating, heading helpers
template-parts/home/          One file per homepage section
template-course.php           /courses/{slug} — one template for every course
template-section.php          /{section}/ — courses, method, about, faq, contact
template-parts/course/        One file per course-page section
inc/courses.php               All course data and copy
assets/css/courses.css        Course-page layout + components, scoped to
                              .course-page. Colour comes from style.css.
template-placement.php        /placement/ — the free تعیین سطح test
template-parts/placement/     Intro, one question, the form, the result
inc/placement.php             Route, scoring, copy, storage — the whole feature
inc/data/questions.json       The question bank. VERBATIM. Never edit by hand.
assets/css/placement.css      Placement components, on the site palette
assets/js/placement.js        The stepper. Additive; the test works without it.
template-account.php          /login/ and /register/
template-dashboard.php        /panel/ — the student dashboard
template-parts/account/       Sign-in and sign-up forms
template-parts/panel/         One file per panel section
inc/auth.php                  Student accounts — signup, login, route guards
inc/panel.php                 Copy and data for the account pages and the panel
assets/css/panel.css          Account + panel components, on the site palette
assets/images/                Shima's portrait + avatar, and one cover per
                              course — zandi_shima_photo(), zandi_course_cover()
assets/fonts/                 Vazirmatn variable woff2, self-hosted
assets/js/theme.js            The only JavaScript (~9 KB, no dependencies)
docs/wordpress-iran-stack.md  Iranian payment + plugin research
```

Full detail in [`README.md`](README.md).

---

## Conventions already established

- **Assets are versioned by their own mtime**, through
  `zandi_asset_version( $path )`. ZANDI_VERSION alone was the cache-buster for a
  long run of commits and never moved, so every stylesheet stayed pinned at
  `?ver=1.1.0` and browsers, the host cache and the CDN all kept serving the copy
  they first saw. Templates are PHP and update instantly, so deploys looked
  half-applied — new copy on the page, old layout around it, and nothing to point
  at. **Never enqueue an asset with the bare constant.**

- **Content lives in `inc/content.php`**, every getter wrapped in
  `apply_filters()`. That is the seam for ACF/Customizer later. Do not hard-code
  copy into templates.
- **No copy anywhere may name a support channel.** Every «بپرس» on the site
  reads «از صفحه تماس بپرس» and links to `zandi_support_url()` → `/contact/`.
  **`zandi_contact()` is the only place a URL or an `@name` is written down.**
  Three templates render what it holds — `template-parts/home/contact.php`,
  `footer.php` and `template-parts/panel/support.php` — and not one of them
  knows a channel by name; they read the label and the note out of the array
  too. `zandi_socials()` is the same. Moving support — Telegram to WhatsApp,
  WhatsApp to a form — is then one edit, not forty strings across sixteen files,
  which is what it was until 3 August 2026. Two links used to hard-code
  `https://t.me/…` and bypassed the getter entirely — if you add a support link,
  use the array.
  The check is a grep, and it is exact:
  `grep -rn "t\.me/" --include="*.php" .` **must only ever hit `inc/content.php`.**
  Run it after touching any of the four files above. (CLAUDE.md claimed until
  2 September 2026 that `test-support.php` enforced this. There is no such file
  in the repo and there is no evidence there ever was — it was a scratchpad
  script that did not survive its session.)
- **Three Telegram destinations, and they are not interchangeable.** `support`
  (`@tav_1089`) is where a human answers and is what «پشتیبانی» means everywhere.
  `teacher` (`@shima_zandi`) is Shima's own account and appears **in the student
  panel only** — it is what having an account buys you, not a public inbox; the
  owner decided this on 2 September 2026. `telegram` (`@zandiacademy_fr`) is the
  public broadcast **channel**: content, not support. Until that date only
  `telegram` existed and the whole site pointed at it, so the panel's «تماس با من»
  and the contact page's «پشتیبانی ۲۴ ساعته» both landed somewhere nobody replies.
  Do not re-merge them.
- **Section partials compose the helpers in `inc/template-tags.php`** and never
  repeat markup. New visual pattern → helper first.
- **Facts only.** Never invent a statistic, testimonial, instructor, address or
  phone number to fill a layout. An earlier draft did exactly that and shipped a
  wrong first name for a real person. Missing data gets an empty state and a
  TODO — `zandi_testimonials()` and `zandi_contact()` are the pattern.
- **Digits:** Vazirmatn's `ss01` font feature is deliberately **off** — it
  rewrites Latin digits as Persian and corrupts CEFR codes like `A2`/`B2`.
  Localise explicitly with `zandi_fa_digits()`.
- **RTL:** `style.css` uses logical properties so layout mirrors itself;
  `rtl.css` carries only gradient angles and physical `translateX`. Note that
  `inset-inline-start` resolves against the element's **own** `dir` — putting
  `dir="ltr"` on a positioned box flips which edge it anchors to.
- **Progressive enhancement:** `<html>` ships `no-js`, swapped before first
  paint. Nothing may depend on JavaScript to be readable.
- **Student accounts are WordPress users.** `inc/auth.php` adds `/register/`,
  `/login/`, `/logout/` and `/panel/`. Identity is the **mobile number**
  (`user_login` + `billing_phone` meta); email is optional. The auth forms post
  to themselves and are processed on `template_redirect`, the way
  `wp-login.php` does, so they re-render with errors and keep phone numbers out
  of query strings and server logs.
- **A route is only real when it is declared in all three places** —
  `zandi_register_routes()`, `zandi_query_vars()` and `zandi_parse_request()`,
  all in `functions.php` — **and its URLs are built by a helper** that falls back
  to a query string when permalinks are «ساده». Miss the parse_request entry and
  the route dies the moment a plugin flushes the rewrite rules; miss the URL
  helper and every link 404s at the web server before PHP even runs.
- **The free-consultation form is gone** (30 July 2026). `zandi_handle_booking()`,
  `zandi_booking_confirmed()` and the `zandi_booking_submitted` action were
  removed with it. Do not reintroduce them.
- **Digits owns both auth pages; the theme gets out of its way.** `/login/` and
  `/register/` are two real routes and each renders Digits' own form —
  `df_digits_form_login()` and `df_digits_form_signup()`, both resolved through
  `zandi_auth_form_markup( $route )`. A combined one-field form was attempted and
  abandoned: Digits ships two forms that cross-link, and forcing them into one
  broke signup. **Phone only** — enabling email makes Digits render a tabbed
  form.
- **Both auth pages must always come from the same system.** The regression worth
  remembering: `/login/` rendered Digits while `/register/` fell back to the
  theme's own password form, so a student could sign up with a password and then
  be unable to sign in, because the login form wanted a code. Anything that
  changes one auth page changes the other. **Do not add a third auth page.**
  (An earlier note here said `/register/` 301s to `/login/` when an OTP provider
  is active. It does not, and has not since the two-form decision — that
  redirect belonged to the abandoned single-form plan and was removed with it.)
- **The provider's form is rendered on `template_redirect`, not in the
  template.** `zandi_prepare_auth_form()` builds it early and
  `zandi_auth_form_markup()` hands the template the stash. This is not caching.
  A plugin enqueues the script its form needs at the moment it is asked for the
  markup; asking from inside a partial happens after `wp_enqueue_scripts` has
  fired and `wp_head()` has printed, so every `wp_enqueue_script()` Digits made
  went into a queue nobody would flush again and **its JavaScript never reached
  the page**. The symptom was not a missing stylesheet — it was that Digits
  hides all but the current step panel with JS, so «ورود», «عضویت» and «تایید
  شماره موبایل» all rendered at once, stacked, on `/register/`. Any provider
  swapped in here needs the same treatment.
- **Digits' markup is cleaned in PHP, not fought with selectors.** The theme
  holds the provider's form as a string before printing it, so
  `zandi_clean_provider_form()` (`inc/auth.php`) removes the two things that
  duplicate the card — Digits' own «عضویت» heading and its «اکنون وارد شوید»
  cross-link — by matching their **text**, never a class name. Three rounds of
  CSS failed at this because class names do not partition; text does.
  **The function fails open, and that property is the point.** Missing `ext-dom`,
  a parse error, an empty result, a result with no `<form>` left in it — every
  one returns the original string. If it ever returned `''`, the theme would
  conclude no OTP provider is active and draw the password fallback, which is
  the split-auth regression two bullets down. Never add a path that can return a
  shorter-but-broken string. `zandi_norm_fa()` handles the invisible differences
  (ZWNJ, Arabic ي/ك) that make «ثبت‌نام» and «ثبت نام» compare unequal.
- **Never style a plugin's markup by class-name substring.** The override sheet
  in `assets/css/panel.css` once matched `[class*='dig']`, `[class*='tab']` and
  `[class*='digit'][class*='submit']`, and forced `max-width`, `width` and
  `display` through them. Those substrings do not partition the way it assumed:
  `digits_tab_content_mobile active` is a step *panel*, `digits_submit_wrapper`
  is a *container*, and the step track is deliberately wider than its box. The
  result was a stray navy pill behind the phone field, a button bleeding past
  the card and overlapping panels. The sheet now follows two rules — **style
  only real controls** (`input`, `select`, `button`, `a`), and **set no layout
  property on anything the plugin owns**. Colour is safe to sweep broadly;
  geometry never is.
- **The built-in phone + password form is a deliberate fallback**, reachable only
  while no OTP plugin is active. Digits is paid software in the auth path whose
  8.4.6.x line carried CVE-2025-4094. If it is deactivated or its licence lapses,
  `/login/` must degrade to a working form, not a blank card.
- **The mobile number is the join key** between the WordPress account, the
  WooCommerce order and the SpotPlayer licence. Each writes it under a different
  name, so `zandi_user_phone()` walks `zandi_phone_meta_keys()` and
  `zandi_sync_student_phone()` mirrors the result into `billing_phone`. Do not
  read a phone number straight out of one meta key.
- **`zandi_identity_verified()` is only consulted on the fallback path.** With
  Digits active the code is verified before the user row exists, so it is never
  reached. It is not a security check.
- **Account and panel pages carry no `.reveal`.** Scroll-reveal starts at
  opacity 0 and is undone by JavaScript; a login form that needs a script to
  become visible can fail shut. **The placement test is the same kind of page
  and follows the same rule** — its intro is the instructions someone has to
  read before they can start, not decoration. This was caught by screenshot:
  with `.reveal` on those cards the page rendered as a heading, a button and
  nothing in between.
- **`hidden` does not hide a `.btn`.** `[hidden] { display: none }` is a
  user-agent rule and any author `display` beats it — including
  `.btn { display: inline-flex }`. Every control that ships `hidden` and is
  revealed by script needs an author rule to back it up; `placement.css` carries
  `.placement-page [hidden] { display: none }` for exactly this. Without it the
  no-JS test page showed thirty «بعدی» buttons that advanced nothing.
- **The placement test is built but deliberately not linked.** `/placement/` is
  a real route with no menu item, no footer column, no homepage section, and
  `noindex` while it is reviewed. Three steps launch it, all named on
  `zandi_placement_noindex()` in `inc/placement.php`. It is open to everyone for
  now; `zandi_placement_requires_login()` is the single line that closes it to
  students, and nothing else has to change when it flips.
- **The placement test is scored in PHP, never in the browser.** The answer key
  stays on the server, the result has to be storable against an account, and
  with scripts off the page is a working thirty-question form. Options are
  shuffled per render, so a radio carries its **position on screen** and the
  form carries a `wp_hash()`-signed position→option map. **That signature is
  load-bearing**: unsigned, a visitor could rewrite the map so every position
  pointed at option 0 — always the correct answer in the bank — and score thirty
  out of thirty. There is a test for exactly that attack.
- **`zandi_bidi()` does not isolate a trailing `+`.** Its chain has to end on a
  letter or digit so that a full stop closing a Persian sentence stays outside
  the isolated run — which means «A1+» comes out as «+A1» in a right-to-left
  page. `zandi_placement_level_label()` isolates a bare CEFR code whole and
  sends only the sentence («هنوز به A1 نرسیده») through `zandi_bidi()`. Anywhere
  else a level code with a `+` is printed, it needs the same treatment.
- **A whole French sentence needs `dir="ltr"` on its ELEMENT, not on a span
  inside it.** An isolated span fixes character order but leaves the block
  right-aligned, so the sentence hangs off the wrong edge. `zandi_placement_dir_attrs()`
  returns the attributes for the element and `zandi_placement_content()` escapes
  the text; a *mixed* run still goes through `zandi_bidi()`. This is the single
  most important technical point on that page.

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

**The logo is the ز / Z ligature** from the owner's logo pack (v1, August 2026):
a Latin Z whose tail sweeps into the Persian «ز», with a red dot above. It
replaced an abstract Z on a rounded navy tile that predated a real logo.

- **Red is only ever the dot.** The pack's one hard rule, and the site's. The
  dot is the smallest thing the brand owns — the dot of ز, a French accent, a
  full stop — and it stops meaning anything the moment red spreads to a button
  or a banner.
- **The mark is inline SVG**, drawn by `zandi_logo()` in `inc/template-tags.php`
  and styled in `style.css`. Not an `<img>`: CSS has to reach `stroke` and
  `fill` so one mark serves the white header and the navy footer, and so the
  draw-on animation needs no second file. `.logo__mark` needs
  `overflow: visible` — the pack's viewBox is tight and clips the round caps
  without it.
- **The pack ships `#1D2E5C` / `#DC3327`; the site renders `#1B365D` /
  `#C8102E`.** Deliberate. The pairs are within a couple of percent, and a logo
  navy that is *almost* the header navy beside it reads as a mistake rather than
  as a second brand. One palette.
- Only three files from the pack are installed — `assets/favicon.{ico,svg}` and
  `assets/apple-touch-icon.png`, about 6.7 KB. The rest (app icons for
  Instagram and Telegram, print variants, 2048px PNGs) is 1.5 MB the site would
  never serve. The android-chrome PNGs are only read through a web app
  manifest, and there is none.
- `zandi_asset_uri()` versions the favicon URLs by mtime. Browsers cache
  favicons far harder than stylesheets and a stale one survives a hard refresh,
  so replacing the mark without changing the URL leaves the old icon in the tab
  for weeks.

Shima's own photograph is the one exception to "no photography" — it is hers,
not stock — and it appears **uncropped**, tower and all. The rule above is about
not leading with the landmark as decoration, the way every other language school
does; it is not a reason to recompose her photograph. **Do not crop images the
owner supplies.** She frames them; the layout adapts.

Course covers in `assets/images/course-{slug}.webp` are hers too, already
rendered at 16:10 — the ratio `.thumb` reserves — so they drop in whole.

---

## Order of work — frontend first

**Build the frontend before anything else.** Payment, SMS and plugin
integration come later, section by section, when the owner says so.

Do **not** start on payment or notification code just because this file
documents the decisions below. Those are recorded so nobody has to ask again —
not a backlog to work through. Confirm with the owner before moving off
frontend work.

The homepage **is being redesigned entirely**. Do not treat the current
`front-page.php` sections as final.

**Course landing pages are built** — `/courses/a1`, `/courses/a2`, `/courses/b1`.
**Standalone section pages are built** — `/courses/`, `/method/`, `/about/`,
`/faq/`, `/contact/`, all from `template-section.php`, which composes the same
homepage partials so the copy has one source.
**The placement test is built and unlinked** — `/placement/`, awaiting the
owner's review before it is announced. See the rule above before touching it.

Every page uses **one header and one footer** (`header.php` / `footer.php`) and
**one palette** (`style.css`). The course pages once had their own chrome and
their own cream-and-red palette; the owner's verdict on 30 July 2026 was that
opening a course felt like leaving for a different website, so they were folded
back in. Do not reintroduce a second set of chrome or a second palette — if a
course page needs a new visual pattern, add it to the shared helpers in
`inc/template-tags.php` and style it in `style.css`.

Playfair Display is still declared in `courses.css` and applies to Latin runs
inside course headings only. `zandi_bidi()` emits the `.latin-run` hook on every
page, so promoting it site-wide later is a one-rule change.

Course and section pages carry a breadcrumb via `zandi_breadcrumb()`.

---

## Decisions already made

Answered by the owner on 29 July 2026. Do not re-ask these.

| Question | Decision |
| --- | --- |
| Platform | **WordPress.** Being available on WordPress is the top priority. A Next.js/Vercel build was considered and dropped — Vercel blocks Iranian IPs (AWS enforces the embargo), so it cannot serve this audience. |
| Payment gateway | **ZarinPal** (زرین‌پال) |
| eNamad (نماد اعتماد) | **Not yet** — being obtained soon. Plan a footer slot for the badge. |
| Homepage / booking flow | **Homepage will be rebuilt entirely.** The free-consultation form has been removed and replaced by real accounts. |
| Signup / login | **Built, 30 July 2026.** WordPress users, phone-first, at `/register/` `/login/` `/panel/` on the main domain. A separate `app.zandiacademy.com` was considered and deferred — one install means one login cookie and one order table. |
| Login method | **Digits, two pages. Settled 30 July 2026.** `/login/` signs in, `/register/` creates the account, both rendered by Digits and cross-linked. A single combined form was tried first and reverted — Digits does not work that way. Keep Digits on 9.x: its 8.4.6.x line carried CVE-2025-4094 (CVSS 9.8, OTP brute-force), fixed in 8.4.6.1. |
| Email at sign-in | **No, deferred (30 July 2026).** Digits turns the form tabbed as soon as email is on, and there is no SMTP account. Revisit only when transactional mail has been seen landing in a Gmail inbox. |
| Installments (SnappPay) | **Not for now.** Revisit later. |
| SMS provider | **نجوا (najva.com).** Connected to Digits. Also sells transactional email over SMTP, so it covers the email OTP too — one vendor, one احراز هویت. |
| Telegram | Three accounts, all in `zandi_contact()`. Support is **`https://t.me/tav_1089`** — questions, level checks, exercise corrections and interview scheduling. Shima's own is **`https://t.me/shima_zandi`**, shown in `/panel/` only. `https://t.me/zandiacademy_fr` is the public **channel**, not support (this row called it "the real support channel" until 2 September 2026, which is what sent students to a broadcast channel for help). Named only in `/contact/`, the footer and the panel — see the rule above. |
| Instagram | `https://www.instagram.com/shima_zandi.fr` |
| Persian typeface | **Peyda — licensed and committed.** The owner bought Peyda 4 (SemiPro); the theme uses the `PeydaWeb-*` Font Family web build, and the five web weights are in `assets/fonts/peyda/`. fontiran confirmed that keeping them here is acceptable **on condition the repository stays private** — a public repo would be redistributing a paid font, so if it is ever opened up the files must be removed *and purged from history*. `zandi_peyda_files()` detects them and switches `--font-persian` over; delete them and the site falls back to Vazirmatn with nothing broken. See that folder's README. (This row said "NOT committed, blocked by `.gitignore`" until 10 August 2026. Neither was true.) |

Still open, and worth asking when the work reaches it:

- Full online payment at enrolment, or a deposit followed by an invoice?
- What the rebuilt homepage should contain.

Because there is no eNamad yet, an **aggregator gateway is the only realistic
option** — which is consistent with the ZarinPal choice. A direct bank PSP
would require the trust seal and a registered company.

---

## Payment — the short version

Full comparison, current plugin status and integration checklist:
**[`docs/wordpress-iran-stack.md`](docs/wordpress-iran-stack.md)**

- Selling courses → **WooCommerce** + an Iranian gateway plugin.
- **Chosen gateway: ZarinPal.** Use the official plugin
  (`zarinpal-woocommerce-payment-gateway`), published by ZarinPal themselves.
  Keep it updated — 5.1.1 fixed a real checkout vulnerability.
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

---

## Branches — push to both, every time

Work on the branch you were given, then **leave `main` and
`claude/persian-french-academy-homepage-oa08sj` pointing at the same commit.**
Not "when the feature is finished" — at the end of every task that pushes
anything.

```
git checkout <your-branch> && git push -u origin <your-branch>
git checkout main && git merge <your-branch> --no-edit && git push -u origin main
git checkout <your-branch>
```

**Why this is a rule and not a preference.** The two drifted 15 commits apart
once, across the whole WooCommerce integration — the SpotPlayer bridge, the
cart-loading fix, the checkout rebuild, the payment pages. Everything was
pushed, everything was on the feature branch, and `main` had none of it. From
the owner's side that is indistinguishable from an agent that pushed nothing,
and it cost a round trip to work out which of the two was being looked at.

If you are ever told to push to one branch only, do that — but say plainly in
your reply that the other is now behind, and by how many commits. Silence is
what caused the problem.

**Before you say you have pushed**, verify the ref actually moved rather than
trusting the command's output:

```
git ls-remote --heads origin | grep -E "main|persian"
```

Both hashes should match your local HEAD. If a push is rejected, someone else
pushed while you worked — `git fetch` and merge, never force.
