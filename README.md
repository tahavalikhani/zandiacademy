# Zandi Academy — قالب وردپرس

A standalone WordPress theme for a premium Persian-language academy that teaches
**French only**. Classic PHP templates, plain CSS, RTL throughout.

**No build step.** No npm, no bundler, no page builder. Copy the folder into
`wp-content/themes/` and activate it.

---

## Install

```
wp-content/themes/zandiacademy/
```

Then **نمایش ← پوسته‌ها** and activate *Zandi Academy*.

Set **تنظیمات ← خواندن ← صفحه ایستا** to make the homepage the front page —
`front-page.php` takes over automatically.

Requires WordPress 6.0+ and PHP 7.4+. Nothing else.

---

## Files

```
style.css                 Theme header + the entire stylesheet
rtl.css                   Right-to-left refinements (loaded when is_rtl())
functions.php             Setup, enqueues, nav walker, routing
header.php  footer.php    Site chrome
front-page.php            Homepage — section ordering only
index.php                 Blog index, archives, search
page.php  single.php      Editorial templates
comments.php  searchform.php
inc/
  content.php             Every Persian string on the homepage
  icons.php               Inline SVG icon registry
  template-tags.php       Button, badge, card, avatar, rating, heading helpers
  placement.php           Placement test — route, scoring, copy, storage
  data/questions.json     The question bank. Verbatim; never edited by hand.
  students.php            پنل دانشجوها — the owner's screen. wp-admin only.
  class-zandi-students-table.php  Its WP_List_Table.
template-parts/home/      One file per homepage section
template-placement.php    /placement/ — the free placement test
template-parts/placement/ Intro, one question, the form, the result
assets/
  images/shima.webp       Portrait — hero and course pages
  images/shima-avatar.webp  Square — the teacher-card avatar
  images/course-{slug}.webp  Course cover art, 16:10
  fonts/                  Vazirmatn variable woff2 (self-hosted)
  js/theme.js             The only JavaScript (~5 KB, no dependencies)
  css/admin-students.css  The students screen, enqueued on that screen alone
  favicon.svg
tests/                    Command-line checks against a WordPress stub
```

**Rule of thumb:** section partials compose the helpers in `inc/template-tags.php`
and never repeat markup. A new visual pattern goes in the helpers first.

---

## Editing content

All homepage copy lives in `inc/content.php` as plain PHP arrays. Every getter
is wrapped in `apply_filters()`, so a child theme or plugin can override a
section without touching a template:

```php
add_filter( 'zandi_courses', function ( $courses ) {
	$courses[0]['title'] = 'فرانسه از پایه';
	return $courses;
} );
```

The same seam is where ACF, the Customizer or a custom post type plugs in later
— replace the array, leave the templates alone.

### Changing the support channel

No copy on the site names a messaging app. Every «از صفحه تماس بپرس» links to
`zandi_support_url()`, which resolves to `/contact/`, and only that page names
the channel. So switching support is two edits, not forty:

```php
// 1. The channel itself — this is what /contact/ and the footer render.
add_filter( 'zandi_contact', function ( $contact ) {
	$contact['telegram']      = 'https://wa.me/98…';
	$contact['telegram_name'] = '+98…';
	return $contact;
} );

// 2. Optional: send «بپرس» somewhere other than /contact/ entirely.
add_filter( 'zandi_support_url', fn() => home_url( '/help/' ) );
```

`zandi_courses` is the one filter that fires with two different payloads. The
homepage asks for the courses that are on sale and closes the section with a
«دوره‌های بیشتر» button; `/courses/` asks for the whole catalogue, «به‌زودی»
cards included. A callback that needs to tell them apart takes the second
argument:

```php
add_filter( 'zandi_courses', function ( $courses, $include_upcoming ) {
	return $courses;
}, 10, 2 );
```

### Menus

Assign a menu to **منوی اصلی** and it replaces the built-in navigation. With no
menu assigned, the theme falls back to the on-page anchors (`#courses`,
`#teachers`, …) so a fresh install still looks finished. Anchors are resolved
against the front page by `zandi_resolve_anchor()`, so they work from any URL.

---

## Student accounts

The free-consultation form that used to close the homepage was removed on
30 July 2026 and replaced by real WordPress user accounts. `inc/auth.php` adds
four routes, served by the theme rather than by editor-created pages:

| Route | What it does |
| --- | --- |
| `/register/` | Creates a subscriber. Honours **تنظیمات ← همگانی ← عضویت** |
| `/login/` | Signs in and honours a host-confined `redirect_to` |
| `/logout/` | Nonce-checked, then back to the homepage |
| `/panel/` | The dashboard. Signed-out visitors bounce to `/login/` |

**Identity is the mobile number.** It is stored as `user_login` and mirrored into
`billing_phone` user meta, which is where WooCommerce and the Iranian OTP plugins
look for it. Email is optional — delivery from Iranian IPs to Gmail is
unreliable, and `wp_insert_user()` does not require one.

`zandi_normalize_phone()` folds every spelling a student might type — Persian or
Arabic-Indic digits, `+98`, `0098`, spaces, dashes, a bare `9…` — into one
canonical `09XXXXXXXXX`, so the same person cannot register twice.

Students are kept out of `wp-admin` (`zandi_block_admin_for_students()`), with
`admin-ajax.php` and `admin-post.php` exempted so front-end forms keep working.

### Routing

These routes go through the same three-part mechanism as the course and section
pages, and all three parts matter:

1. `zandi_register_routes()` — the rewrite rule, the fast path.
2. `zandi_parse_request()` — reads the path directly, so the routes survive
   another plugin flushing the rewrite rules.
3. `zandi_account_url()` — prints `?zandi_account=login` instead of `/login/`
   when **Settings → پیوندهای یکتا** is on «ساده». On such an install WordPress
   stores no rewrite rules and writes no `.htaccess` block, so `/login/` is
   answered by Apache or nginx looking for a directory of that name — its own
   404, with PHP never running. No theme hook can rescue that; only the URL can.

### Why these forms post to themselves

The rest of the theme posts to `admin-post.php` and redirects. An auth form
cannot: it has to re-render with the typed values still in the fields and the
errors beside them. So `/login/` and `/register/` are processed on
`template_redirect` — the same pattern core's own `wp-login.php` uses. Nothing
lands in a query string, so no phone number reaches a server log.

### OTP — one form, no passwords

**Digits** owns sign-in, with **نجوا** delivering the codes. There is one form
and one route:

> `/login/` takes a mobile number and a code and signs the student in.
> `/register/` takes a mobile number and a code, asks for a full name, and
> creates the account. Digits renders both, and they cross-link.

A combined single-field form was built first and reverted: Digits ships a login
form and a signup form that link to each other, and collapsing them broke the
only page that could create an account.

The theme detects Digits by `function_exists( 'df_digits_form' )` and hands both
forms over, keeping its own card, heading and page chrome around them.
`zandi_auth_form_markup( $route )` resolves per route:

1. `zandi_login_shortcode()` / `zandi_register_shortcode()`, if a filter has set
   one — the escape hatch, so a specific shortcode or a different provider can
   always be forced.
2. `df_digits_form_login()` or `df_digits_form_signup()`.
3. `df_digits_form()`, for builds exposing only the one entry point.
4. An empty string, meaning the built-in fallback is drawn.

**Both pages resolve through that one function on purpose.** When they did not,
`/login/` showed Digits while `/register/` quietly drew the theme's own password
form — so a student signed up with a password and then could not sign in,
because the login form wanted a code. Change one auth page, change the other.

```php
// Only needed to force a shortcode or swap providers; Digits is automatic.
add_filter( 'zandi_login_shortcode', fn() => '[digits_login_form]' );
```

Which fields the form shows and what a new student is asked for after
verification are **Digits settings** (`دیجیتس → فرم‌ها → ورود` and `→ عضویت`),
not theme code.

**Phone only, deliberately.** Digits will also accept an email in the same form,
but switching that on makes it render a *tabbed* form — two inputs behind two
tabs — instead of the single field this was built for. It also needs an SMTP
account that does not exist, and because the flow is passwordless, a student who
signs up with an email that never delivers has no other way in. When it is
enabled, `zandi_login_copy()` needs its wording widened; it names a phone number
today.

**The built-in phone + password form is kept as a fallback**, reachable only
while no OTP plugin is active. That is deliberate: Digits is paid software
sitting directly in the auth path, and its 8.4.6.x line carried
[CVE-2025-4094](https://wpscan.com/vulnerability/b5f0a263-644b-4954-a1f0-d08e2149edbb/)
(CVSS 9.8 — no rate limit on OTP checks, so every code was brute-forceable,
fixed in 8.4.6.1). If it is ever deactivated or its licence lapses, `/login/`
must degrade to a working form rather than a blank page.

### Keeping the number where the rest of the stack looks for it

The mobile number is the join key between the WordPress account, the WooCommerce
order and the SpotPlayer licence — and each writes it under a different name. So
`zandi_user_phone()` walks the candidate keys in `zandi_phone_meta_keys()`,
normalising each and returning the first that is a real Iranian mobile, and
`zandi_sync_student_phone()` (on `user_register` and `wp_login`) mirrors the
result into `zandi_phone` **and** `billing_phone`.

Without that mirror a student who signed up by SMS would reach checkout with an
empty phone field and be issued a SpotPlayer licence under a number they never
gave.

> Digits' exact meta key is the one thing its documentation would not confirm —
> the names in the candidate list come from the CVE proof-of-concept, where they
> appear as POST fields. The list plus the sync is safe either way; confirm the
> real key against one signup under **کاربران ← ویرایش کاربر** and trim the list.

> `zandi_identity_verified()` is only consulted on the fallback path. With Digits
> active the code is verified before the user row exists, so it is never reached.

---

## The students panel — همهٔ دانشجوها در یک صفحه

**پیشخوان ← دانشجوها**, in wp-admin. Every account the site has made, with what
is known about each: name, mobile, placement level and score, courses owned,
lifetime payment, signup date and last sign-in. Four summary tiles above it, a
search that accepts a name or a mobile number in Persian or Latin digits, level
and course filters, an Excel export, and a page per student carrying the
question-by-question review of their test, their orders and their SpotPlayer
licences.

**Only the administrator can open it.** The capability is `manage_options`, not
`zandi_is_staff()` / `edit_posts` — an editor writing blog posts has no business
reading students' phone numbers. It is checked again inside the render callback
and again in the export handler.

### It cannot slow the site

`inc/students.php` is required from `functions.php` **only when `is_admin()` is
true**, so a public request never parses it, never registers one of its hooks
and never runs a query for it. Its stylesheet loads on that one screen. The
list-table class is required inside the render callback, because `WP_List_Table`
is a wp-admin class that does not exist on the front end.

The screen's queries are flat rather than per-row — one `WP_User_Query`, one
`cache_users()` that primes every returned student's meta in a single query, and
then nothing. Every column reads meta that is already in memory. Twenty rows and
five rows cost the same number of queries. The four tiles are cached in a
fifteen-minute transient.

Three writes happen outside wp-admin, all on events and none on a page view:
the last sign-in (`wp_login`), the placement mirror and sitting tally
(`zandi_placement_completed`), and the owned-courses mirror
(`woocommerce_order_status_changed`).

### The export

A nonced `admin-post.php` action streaming CSV, in chunks of 200 users so memory
stays flat. It writes a UTF-8 BOM — without it Excel reads the file in the
system code page and every Persian name becomes mojibake — and Latin digits with
ISO dates, because a spreadsheet cannot sort or add up «۲ شهریور ۱۴۰۵». Any cell
starting with `=`, `+`, `-` or `@` is prefixed with `'`: names are typed by the
people in the file, and a cell beginning `=cmd|` is a remote-code-execution
attempt against whoever opens it.

---

## Design system

### Colour

Navy is the brand. Red is an accent used at small scale only — eyebrow badges,
the tricolore marks, one focal element per view. It is never a surface colour.
Tokens are CSS custom properties on `:root` in `style.css`.

| Token          | Value     | Use                                  |
| -------------- | --------- | ------------------------------------ |
| `--navy-700`   | `#1B365D` | Primary brand, headings, primary CTA |
| `--navy-800/900` | darker  | Dark sections, footer                |
| `--navy-50/100`  | tints   | Icon tiles, hairline borders         |
| `--rouge-500`  | `#C8102E` | Accent only                          |
| `--mist`       | `#F4F6F8` | Alternating section background       |
| `--ink`        | `#111111` | Body text                            |
| `--success`    | `#16A34A` | Confirmations, progress              |

### Type

Vazirmatn variable (100–900) in a single 111 KB woff2, self-hosted, preloaded,
`font-display: swap`. IRANSansX is the declared fallback.

> **Note:** Vazirmatn's `ss01` feature rewrites Latin digits as Persian ones,
> which corrupts CEFR codes such as `A2`/`B2`. It is deliberately switched off.
> Localise digits explicitly with `zandi_fa_digits()`.

Display sizes are fluid `clamp()` values, so headings scale continuously
instead of stepping at breakpoints.

### Shape, depth, motion

Radii 16 / 20 / pill. Shadows run `soft → card → lift`, all tinted navy rather
than neutral grey. Motion is two gestures — a 20px fade-up and a 2% fade-scale —
on one easing curve (`--ease-premium`). Everything animated is also correct when
static.

---

## RTL

`style.css` is written with CSS logical properties (`margin-inline`,
`inset-inline-start`, `border-end-start-radius`, `padding-block`), so the layout
mirrors itself with no direction-specific rules. `rtl.css` carries only what CSS
cannot express logically — gradient angles and physical `translateX` — plus a
couple of Persian typography adjustments.

Two things to know when editing:

- **`inset-inline-start` resolves against the element's own `dir`.** Putting
  `dir="ltr"` on a positioned box flips which edge it anchors to. Scope `dir` to
  an inner wrapper, as `zandi_tricolore()` does.
- **`scrollLeft` is negative in RTL** (0 → −max). The carousel compares
  `Math.abs( scrollLeft )` so the same bound checks work in both directions.

Latin runs (email, phone, `Bonjour !`, the CEFR ladder) are wrapped in
`dir="ltr"` so they read correctly inside Persian copy.

---

## Accessibility

- Landmarks: `header` / `main` / `section[aria-labelledby]` / `footer`, one `h1`.
- Skip link to `#main` as the first focusable element.
- One `:focus-visible` treatment for every control.
- Accordion: real `<button>` headers with `aria-expanded` / `aria-controls`,
  panels as labelled regions.
- Carousel: native scroll-snap, so touch, trackpad and keyboard all work; the
  region is focusable and the arrows carry `aria-label`s.
- Star ratings announce once as text; the stars are `aria-hidden`.
- Every field has a real `<label>` — placeholders never stand in for one.
- Repeated actions ("مشاهده دوره") carry a specific screen-reader name.

Verified at 320–1920 px: no horizontal overflow, no console errors, no unnamed
controls, and nothing left invisible under `prefers-reduced-motion`.

---

## Progressive enhancement

`theme.js` is the only script and nothing depends on it. `<html>` ships with
`no-js`, swapped for `js` before the first paint; the `.no-js` rules in
`style.css` reveal everything the script would otherwise animate in. With
JavaScript disabled you still get: the full page (no blank reveals), all FAQ
answers open, a scrollable testimonial shelf, the nav expanded, and a booking
form that posts and confirms via redirect.

---

## Performance

- One self-hosted variable font, preloaded; no webfont CDN round-trip.
- Zero raster images — the hero panel, course thumbnails and logo are inline SVG
  and CSS gradients, so they cost no requests and stay sharp at any density.
- ~40 KB CSS and ~9 KB JS, both unminified and uncompressed. No framework
  runtime.
- Post thumbnails carry `loading="lazy"` and reserved aspect ratios.

---

## The placement test — `/placement/`

Thirty questions, A1 to B2, about ten minutes. Free, no account, and the level
is shown the moment the last question is answered — never behind a form.

**It is not linked from anywhere yet.** No menu item, no footer column, no
homepage section, and `noindex` while it is being reviewed. To launch it:

1. `zandi_placement_noindex()` → `false` (inc/placement.php)
2. add «تعیین سطح رایگان» to `zandi_navigation()` (inc/content.php)
3. decide whether `zandi_placement_requires_login()` should now be `true` — it
   is `false` today so the page can be tested without an account. Flipping it
   sends signed-out visitors to `/login/` with a return address; nothing else
   needs to change, because results already save to whoever is signed in.

**The question bank is `inc/data/questions.json`, reproduced verbatim from the
owner's specification and never edited from PHP.** The band thresholds are read
out of its `bands` array rather than hard-coded, so retuning the test after the
pilot is one edit to that file.

### Scored on the server, not in the browser

The specification sketches a browser-side scorer. This does it in PHP, for three
reasons: the answer key never has to reach the browser; the result has to be
storable against an account, so the server computes it anyway; and the theme's
rule is that nothing may depend on JavaScript to be readable. With scripts off
the test is thirty questions on one page and one submit button, and it scores
identically. JavaScript only upgrades that to one question per screen with a
progress bar.

### How an answer survives the round trip

Each question's three real options are shuffled per render and «نمی‌دانم» is
pinned last. A radio therefore carries its **position on screen**, not the
option's own index — otherwise every correct answer would be `value="0"` and the
key would be in the markup. The form carries the render's position→option map
and a `wp_hash()` signature of it. The signature is load-bearing: without it a
visitor could rewrite the map so every position pointed at option 0 and score
thirty out of thirty.

`inc/data/.htaccess` denies HTTP access to the bank on Apache and LiteSpeed. **On
nginx it is inert** — add the equivalent to the server block, or the answer key
is a URL away:

```
location ~* /wp-content/themes/.+/inc/data/ { deny all; return 404; }
```

### The result

Held in a one-day transient behind a random token, so the page is reached by
redirect: refreshing does not re-submit thirty answers and the back button
behaves. Signed-in students also get it written to user meta —
`zandi_placement_result` for the panel and a capped `zandi_placement_history` —
and every individual answer is stored, which is what will later show which
questions are useless. `zandi_placement_completed` is the hook for aggregate
item analysis.

The panel section (`template-parts/panel/placement.php`) renders **nothing**
until a student has actually sat the test, so the panel does not advertise a
page that has not launched.

### What it recommends

`zandi_placement_recommendations()` maps each of the eight outcomes to a course
that is on sale today, or to `/contact/` for the levels the catalogue does not
reach yet. The specification's own CTA column offers a waiting list, a free
revision booklet and a free consultation; the academy has none of the three, and
the consultation was deliberately removed from the site in July 2026, so that
column is not rendered.

---

## Not included yet

Homepage plus the standard editorial templates. The navigation links to
`#courses`, `#teachers`, `#about`, `#faq` and `#contact`, which are on-page
anchors today and become real pages when they exist — point the menu at them and
`zandi_resolve_anchor()` steps out of the way.

Course thumbnails and instructor photos render considered placeholders
(engraved gradient panels, initials avatars). Add an `image` key in
`inc/content.php` and `zandi_avatar()` swaps in the photograph; the aspect ratio
is already reserved, so nothing shifts.
