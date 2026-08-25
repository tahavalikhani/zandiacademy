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
inc/students.php              پنل دانشجوها — the owner's own screen. wp-admin
                              ONLY: functions.php requires it under is_admin().
inc/class-zandi-students-table.php
                              Its WP_List_Table, required inside the screen
                              callback — the parent class is a wp-admin class.
assets/css/admin-students.css Its stylesheet, on that one screen and no other.
tests/                        Command-line checks that run the theme against a
                              WordPress stub — `php tests/test-render.php`.
                              CLI-only: the theme directory is web-served.
assets/images/                Shima's portrait + avatar, and one cover per
                              course — zandi_shima_photo(), zandi_course_cover()
assets/fonts/                 Vazirmatn variable woff2, self-hosted
assets/js/theme.js            The only JavaScript (25 KB, no dependencies)
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
  Only two places know the channel: `zandi_contact()` and
  `template-parts/home/contact.php`, which renders it. Moving support — Telegram
  to WhatsApp, WhatsApp to a form — is then an edit to those two, not to forty
  strings across sixteen files, which is what it was until 3 August 2026.
  The footer and `zandi_socials()` still show the channel, but they read
  `zandi_contact()`, so they follow automatically. Two links used to hard-code
  `https://t.me/…` and bypassed the getter entirely — if you add a support link,
  use the helper. `test-support.php` walks every copy getter and fails on a
  channel name, so this cannot quietly come back.
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
- **The search-console verification file is the one path that is deliberately
  NOT a route.** Google fetches `/google2efa9fda7b12fc25.html` from the site
  root and reads one line out of it, byte for byte. This repo is the theme, so a
  committed file would be served from `/wp-content/themes/…` where Google never
  looks — `zandi_serve_verification()` answers the literal path on `init`
  instead, from the whitelist in `zandi_verification_files()`. Do not «fix» it
  by moving it into the rewrite table: the point is to answer without depending
  on rewrite rules any plugin can flush. Removing the file or the handler
  revokes ownership of the property.
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
- **Digits' *runtime* notices are tagged by shape in JS, not matched by class.**
  The notices are injected by the plugin's own JavaScript after an AJAX call, so
  the PHP cleaner never sees them, and four rounds of CSS substring matching
  (`error`, `msg`, `notice`, then `alert`, `warn`, `toast`) all missed the box
  that actually ships — it arrived cyan with pale pink text, at a contrast ratio
  of 1.15. `initProviderNotices()` in `theme.js` watches the document on
  `.panel-page` and tags anything that appears after load, carries text, and is
  painted a colour the theme does not own (`THEME_SURFACES`); `panel.css` styles
  `.zandi-provider-notice`. That class is the **only** place in the file where a
  geometry override on the plugin's own element is allowed — `position: static`,
  because Digits floats the notice over the field it is complaining about — and
  it is allowed precisely because the theme identified that one element itself
  rather than casting a substring net that might also be holding the step track.
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
- **The owner's student panel is wp-admin code, and the `is_admin()` guard on
  its `require_once` is the feature, not a tidiness.** She asked for every
  student in one place *without slowing the website*, and the honest way to
  promise that is to make a public request unable to reach the code at all — it
  is not parsed, no hook is registered, no query is attributable to it. Three
  writes do happen outside wp-admin and they are the whole list: the last
  sign-in on `wp_login`, the placement mirror and tally on
  `zandi_placement_completed`, and the owned-courses mirror on
  `woocommerce_order_status_changed`. Every one is on an **event** — signing in,
  finishing the test, paying — never on a page view. Add nothing to that list
  without the same test.
- **Two mirrors exist so the list can be one query instead of twenty.**
  `zandi_placement_level` / `_score` / `_time` mirror the serialized placement
  result, and `zandi_course_owned` (one meta row per course) mirrors what
  WooCommerce orders say a student owns — the same trick WooCommerce plays with
  `_money_spent`. SQL cannot see inside a serialized array, so without them the
  screen could neither filter by level nor show a course without an order query
  per row. **They are derived and never authoritative:** the result array and
  the orders are the record, both mirrors are rebuilt wholesale rather than
  patched, and one student's own page reads live. A stale mirror can misfile a
  row in a list; it can never misreport the student you are looking at.
- **Nothing on that screen sorts on user meta, and that is deliberate.**
  `meta_key` plus `orderby => meta_value` is an INNER JOIN in `WP_User_Query`,
  so sorting by level or by amount paid silently drops every student who has not
  taken the test or never bought anything — the rows you most want to see. Name
  and signup date sort because they are real columns on `wp_users`; everything
  else is a filter. Do not "fix" this by adding a sortable meta column.
- **No Gravatar anywhere in the panel.** secure.gravatar.com is not reliably
  reachable from Iran, and twenty rows each waiting on an avatar that never
  arrives is a screen that looks broken. The initial is drawn in CSS from
  `zandi_first_char()`.
- **Account and panel pages carry no `.reveal`.** Scroll-reveal starts at
  opacity 0 and is undone by JavaScript; a login form that needs a script to
  become visible can fail shut. **The placement test is the same kind of page
  and follows the same rule** — its intro is the instructions someone has to
  read before they can start, not decoration. This was caught by screenshot:
  with `.reveal` on those cards the page rendered as a heading, a button and
  nothing in between.
- **A control that only works with JavaScript ships `hidden` and is revealed by
  the script that makes it work — never rendered dead.** The licence copy button
  in `template-parts/panel/courses.php` is the pattern: `theme.js` reveals it
  only after `canCopy()` confirms the browser can actually write to the
  clipboard, so a visitor with scripts off, or on a browser with no clipboard
  access, sees no button rather than one that does nothing. The licence stays
  plain selectable text underneath either way, which is what makes leaving the
  button out a complete answer instead of a degraded one. **It needs an author
  `display: none` to back it up** — `.panel-licence__copy[hidden]`, scoped to
  that one control — because `[hidden]` is a user-agent rule and `.btn`'s
  `display: inline-flex` beats it, the same trap `placement.css` already
  documents. Scoped narrowly on purpose: `.panel-page [hidden]` would reach
  Digits' markup, and the theme sets no layout property on a plugin's elements.
- **Both labels of a two-state control live in the markup, not in the script.**
  «کپی کن» and «کپی شد» are two spans stacked in one CSS grid cell, one made
  `visibility: hidden`, and `theme.js` only toggles a class. Writing «کپی شد»
  into `theme.js` would have put a user-facing string where no filter could
  reach it — the one thing `inc/content.php` and `zandi_panel_copy()` exist to
  prevent. Sharing the grid cell is what stops the button resizing at the moment
  of the press. (`theme.js` still holds «نمایش»/«پنهان» on the password toggle,
  from before this rule; it is the last one left.)
- **Nothing above the fold carries `.reveal` either — and that one is about
  speed, not accessibility.** `.reveal` is `opacity: 0` until deferred
  `theme.js` adds `is-visible`, so the homepage headline, its paragraph and its
  buttons were invisible from first paint until the whole document had parsed
  and the script had run. That is precisely the window Largest Contentful Paint
  is measured in: the site reported a slow largest paint for text that had been
  ready the entire time. Proved by screenshot on 16 August 2026 — with scripts
  not yet run, the hero was an empty white column. The `no-js` rule does not
  help; it covers scripts being *off*, not scripts that simply have not run yet,
  which is every first visit. A scroll-reveal above the fold never animates
  anyway — the observer finds the element already in view. `home/hero.php` and
  `course/hero.php` are both clean; keep them that way.
- **`inc/seo.php` owns the head tags, and every one of them stands down for an
  SEO plugin.** The theme writes its own `description`, `canonical`, `og:`,
  `twitter:` and JSON-LD, because the course and section pages are virtual
  routes that Yoast and Rank Math cannot edit anyway. That output is
  unconditional, so installing one of those plugins later would put a **second
  canonical** on every course and section page, and two canonicals that disagree
  are worse than none. `zandi_seo_plugin_active()` is checked at the top of
  every head function — including the three older ones in `functions.php`. The
  single exception is the **robots tag in `zandi_placement_head()`**, which is
  printed either way: a result URL carries one person's score, and standing down
  from `noindex` because a plugin happened to be installed would publish it.
  Two robots tags are harmless — a crawler takes the most restrictive.
  **One consequence worth knowing before it bites:** the homepage `<title>` is
  now set in code by `zandi_home_title()`, not by تنظیمات ← همگانی. eNamad's
  «تایید عنوان» method works by pinning that title to a verification code for
  one deploy (see the eNamad row below) — changing the site title in wp-admin
  will no longer do it. Filter `zandi_home_meta` instead, and remove the filter
  afterwards.
- **A virtual route with no `document_title_parts` filter has no title.**
  `zandi_prepare_virtual_page()` sets `is_home = false` and claims nothing in
  its place, so every branch `wp_get_document_title()` tests is false,
  `$title['title']` is never assigned, and the title collapses to the site name
  alone. `/login/`, `/register/` and `/panel/` all rendered
  `<title>آکادمی زندی</title>` until 16 August 2026. **Any new route needs a
  title filter as surely as it needs the three declarations in `functions.php`.**
  The account routes are also `noindex, follow` — a sign-in form and a private
  dashboard do not belong in an index.
- **`404.php` exists, and deleting it does not degrade gracefully.**
  `zandi_course_template()` answers an unknown course slug with
  `get_query_template( '404' )`, which returns `''` when the theme ships no
  `404.php` — `template_include` then yields nothing and WordPress includes
  nothing at all. Before that file existed, `/courses/a3` returned a 404 status
  with a **completely empty document**: no `<title>`, no heading, no chrome.
- **Theme images are files, not attachments, so nothing gives them a `srcset`
  for free.** `wp_get_attachment_image()` never sees `assets/images/`, and the
  site served `shima.webp` — 1282px wide, 74 KB — at full size to a 360px
  phone, where it is also the largest paint. `zandi_image_srcset()` looks for
  `{name}-{width}.{ext}` beside the original and returns `''` when none exist,
  so the plain `src` keeps working. Variants are generated once with GD and
  committed; there is no build step here. **Scaled, never cropped.**
- **Course videos are self-hosted, and they are NOT in this repository.** The
  two clips on each course page — `intro-video.php` and `sample-lesson.php` —
  are served from the site's own domain rather than an Aparat embed, because
  Aparat has no publisher-side switch to turn ads off: a stranger deciding
  whether to trust the academy would watch someone else's ad on the sales page.
  Self-hosting also keeps the `VideoObject` rich result on *this* page instead
  of handing it to the platform. But the files live in the **Media Library**,
  not in `assets/` — the whole theme is ~2 MB packed and one compressed clip is
  several times that, git keeps every version of a binary forever, and swapping
  a clip should not need a push. `zandi_media()` in `inc/content.php` resolves
  an attachment by slug, so uploading `course-a1-intro.mp4` through
  رسانه ← افزودن is the entire publishing step; `zandi_course_video()` returns
  `''` when nothing is uploaded and the «به‌زودی» placeholder renders as
  before. The lookup is a database query, so it is cached in a transient and
  invalidated on `add_attachment` — **without that the page keeps saying
  «به‌زودی» for a day after the upload, which looks exactly like a broken
  feature.** `preload="none"` plus a poster is what makes this cheaper than an
  embed rather than merely ad-free: zero bytes of video until someone presses
  play. Do not raise it to `metadata` or `auto`. **The poster is the one part
  that does live in the theme** — `assets/images/course-{slug}-{kind}.webp`, a
  frame the owner picks, a few tens of KB, committed so it deploys with the
  layout that positions it. `zandi_course_video_poster()` checks the Media
  Library first so it can still be swapped from wp-admin, then that file, then
  the course cover, so a new video is never a blank frame. It needs no width
  variants: `poster` takes one URL and has no `srcset`.

- **`hidden` does not hide a `.btn`.** `[hidden] { display: none }` is a
  user-agent rule and any author `display` beats it — including
  `.btn { display: inline-flex }`. Every control that ships `hidden` and is
  revealed by script needs an author rule to back it up; `placement.css` carries
  `.placement-page [hidden] { display: none }` for exactly this. Without it the
  no-JS test page showed thirty «بعدی» buttons that advanced nothing.
- **There is ONE return address on this site and it lives in `inc/auth.php`.**
  `zandi_remember_intent()` records where somebody was going, `zandi_capture_intent()`
  records it when a signed-out visitor reaches `/login/` or `/register/`, and
  `zandi_resume_intent()` spends it on the first page view after they are signed
  in. **`?redirect_to=` alone cannot work here and never could:** Digits renders
  AND processes both auth forms, so `zandi_handle_login()` and
  `zandi_handle_register()` — the only two functions that read `redirect_to` —
  never run in production. Nothing consulted the destination at all, and the
  plugin's own setting decided where everyone landed. Pick a course, be told to
  sign in, sign in, land on the homepage. The placement test hit this first and
  invented a cookie for itself; the owner then reported the same thing on the
  checkout, because it was never a placement bug — every gated flow funnels
  through that one step. **Do not add a second mechanism. Do not try a hidden
  field:** the theme does not own that markup.
- **`add_query_arg()` DOES NOT URLENCODE.** `build_query()` calls
  `_http_build_query()` with `$urlencode = false`. A comment in `zandi_login_url()`
  claimed the opposite for a long time, and the cost was that any destination
  carrying its own query string ended at the first `&` — the placement report's
  token was silently dropped, and everything after the `&` became a parameter of
  the *login* page. Encode the value yourself with `rawurlencode()`.
  `tests/wp-stub.php` reproduces core's behaviour exactly so the bug cannot come
  back invisibly.
- **`/panel/` is an account route and also a legitimate destination.**
  `zandi_auth_form_routes()` — login, register, logout — is what may never be
  returned to; `zandi_account_routes()` includes the panel and is the wrong list
  to check. Using the wrong one stranded the very journey the guard protects: ask
  for `/panel/` signed out, get sent to sign in, end up wherever the plugin
  dropped you. Caught by `test-redirects.php`, which is why the two lists exist.
- **Nothing may redirect a student who is already on `/placement/`.**
  `zandi_resume_intent()` runs on `template_redirect` for every page of the site.
  Everywhere else that is the point of it. On the placement route it is always
  wrong, because the student has just chosen a state — and the state they choose
  most often is «دوباره آزمون بده» in the panel, which asks for `?start=1` and
  would land on a months-old report instead of a fresh test. From the student's
  side that is a button that does nothing. The exemption is in
  `zandi_may_resume_intent()`, which spends the address rather than keeping it;
  `test-placement.php` holds it in place.
- **The panel's course card answers three questions, and the copy for all of
  them is in `zandi_panel_copy()`.** What the key is (`licence_label`), what to
  do with it (`licence_steps` — three lines, always visible, deliberately vague
  about the player's own interface because naming a control in someone else's
  app is a fact this repo cannot check), and what to buy next
  (`zandi_panel_next_course()`, which walks the catalogue order and returns the
  first course the student does not own). The next-step card links to the course
  **page**, never to a checkout: whether the thing can be bought today is the
  course page's question, and it already answers it through
  `zandi_enrol_control()`.
- **The step numbers are `list-style-type: persian`.** `zandi_fa_digits()`
  cannot reach a counter the browser draws, so an ordered list is the one place
  Latin digits can leak into a Persian page. It styles the marker only, so the
  CEFR codes inside a step are untouched — which is the whole reason
  Vazirmatn's `ss01` is off.
- **`zandi_placement_history` is now shown, not just stored.** It always kept
  the last ten sittings and nothing ever rendered them. The panel draws them as
  a chain, oldest first, so a right-to-left page reads it from the right edge
  towards the left. It appears only from the second sitting on: a «مسیر» of one
  point is the chip above it repeated. The row owns its own `overflow-x`, so ten
  sittings on a 360px screen scroll inside the card rather than making the page
  scroll sideways.
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
| eNamad (نماد اعتماد) | **Obtained and installed, 12 August 2026.** Type «معتبر — یک ستاره». The seal is in the footer, from `zandi_enamad_seal()` in `inc/content.php`, and reaches the page through `zandi_trust_badges()`. **The markup is verbatim and must stay that way** — eNamad calls the logo a government mark and treats tampering as a criminal matter, and `enamad.ir/logohelp` names **WordPress specifically** as a CMS that silently rewrites the code and breaks it. That is why it lives in a PHP nowdoc in a template and never in a page, post, widget or block: editor content is filtered on the way in and out, and `wp_targeted_link_rel()` would add the `rel` that stops the seal rendering. Do not add `rel`, do not touch `referrerpolicy='origin'` (eNamad reads the referrer to confirm the domain), do not drop the non-standard `code` attribute, and do not self-host the image. Sizing is on the wrapper in `style.css`. Six assertions in the test harness guard all of this. Domain ownership was proved earlier by the «تایید عنوان» method; nothing is left in the theme for that.  **LiteSpeed's Lazy Load broke it on first deploy** — it rewrites `src` to `data-src`, so the browser never requested the image and eNamad's crawler had no `src` to find. The image is printed with `data-no-lazy="1"` for that; the issued string itself is untouched and `zandi_enamad_issued_seal()` keeps it auditable. |
| Homepage / booking flow | **Homepage will be rebuilt entirely.** The free-consultation form has been removed and replaced by real accounts. |
| Signup / login | **Built, 30 July 2026.** WordPress users, phone-first, at `/register/` `/login/` `/panel/` on the main domain. A separate `app.zandiacademy.com` was considered and deferred — one install means one login cookie and one order table. |
| Login method | **Digits, two pages. Settled 30 July 2026.** `/login/` signs in, `/register/` creates the account, both rendered by Digits and cross-linked. A single combined form was tried first and reverted — Digits does not work that way. Keep Digits on 9.x: its 8.4.6.x line carried CVE-2025-4094 (CVSS 9.8, OTP brute-force), fixed in 8.4.6.1. |
| Email at sign-in | **No, deferred (30 July 2026).** Digits turns the form tabbed as soon as email is on, and there is no SMTP account. Revisit only when transactional mail has been seen landing in a Gmail inbox. |
| Installments (SnappPay) | **Not for now.** Revisit later. |
| SMS provider | **نجوا (najva.com).** Connected to Digits. Also sells transactional email over SMTP, so it covers the email OTP too — one vendor, one احراز هویت. |
| Telegram | `https://t.me/zandiacademy_fr` — the real support channel. Questions, level checks, exercise corrections and interview scheduling all happen there. **But no page says so except `/contact/`** — see the rule below. |
| Instagram | `https://www.instagram.com/shima_zandi.fr` |
| Course video hosting | **Self-hosted, decided 21 August 2026.** Aparat was the plan and was dropped: ads are its business model and there is no publisher-side way to disable them, so the owner cannot buy an ad-free embed at any price (the June 2025 pre-roll removal was reported as *موقتاً* and came back). The paid Iranian platforms — ابرآروان, نگاوید, کاویمو — are all real and ad-free, but they are priced for hosting a library and these are six short marketing clips; the course library itself is already on SpotPlayer. Files go in the Media Library, never in this repo — see the rule above. Revisit if a clip ever needs DRM or the traffic outgrows the host. |
| Persian typeface | **Peyda — licensed and committed.** The owner bought Peyda 4 (SemiPro); the theme uses the `PeydaWeb-*` Font Family web build, and the five web weights are in `assets/fonts/peyda/`. fontiran confirmed that keeping them here is acceptable **on condition the repository stays private** — a public repo would be redistributing a paid font, so if it is ever opened up the files must be removed *and purged from history*. `zandi_peyda_files()` detects them and switches `--font-persian` over; delete them and the site falls back to Vazirmatn with nothing broken. See that folder's README. (This row said "NOT committed, blocked by `.gitignore`" until 10 August 2026. Neither was true.) |

Still open, and worth asking when the work reaches it:

- Full online payment at enrolment, or a deposit followed by an invoice?
- What the rebuilt homepage should contain.

The gateway choice was made while there was no eNamad, when an **aggregator was
the only realistic option** — which is what ZarinPal is. Domain ownership has
since been verified, so a direct bank PSP is no longer ruled out by the missing
trust seal, though it would still need a registered company. **Do not treat that
as a reason to revisit ZarinPal** unless the owner asks: it is integrated,
working and paid for.

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
