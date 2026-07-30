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
functions.php             Setup, enqueues, nav walker, routes
header.php  footer.php    Site chrome
front-page.php            Homepage — section ordering only
index.php                 Blog index, archives, search
page.php  single.php      Editorial templates
comments.php  searchform.php
inc/
  content.php             Every Persian string on the homepage
  courses.php             All course data and copy
  icons.php               Inline SVG icon registry
  template-tags.php       Button, badge, card, avatar, rating, heading helpers
  auth.php                Student accounts — signup, login, route guards
  panel.php               Copy and data for the account pages and the panel
template-parts/home/      One file per homepage section
template-course.php       /courses/{slug} — one template for every course
header-course.php  footer-course.php
template-parts/course/    One file per course-page section
template-account.php      /login/ and /register/
template-dashboard.php    /panel/ — the student dashboard
header-panel.php  footer-panel.php
template-parts/account/   Sign-in and sign-up forms
template-parts/panel/     One file per panel section
assets/
  fonts/                  Vazirmatn variable woff2 (self-hosted)
  css/courses.css         Course-page palette, scoped to .course-page
  css/panel.css           Account and panel styles, on top of courses.css
  js/theme.js             The only JavaScript (~9 KB, no dependencies)
  favicon.svg
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

### Menus

Assign a menu to **منوی اصلی** and it replaces the built-in navigation. With no
menu assigned, the theme falls back to the on-page anchors (`#courses`,
`#teachers`, …) so a fresh install still looks finished. Anchors are resolved
against the front page by `zandi_resolve_anchor()`, so they work from any URL.

---

## Student accounts

The free-consultation form that used to close the homepage was removed on
29 July 2026 and replaced by real WordPress user accounts. `inc/auth.php` adds
four routes, all served by the theme rather than by editor-created pages:

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

### Why these forms post to themselves

The rest of the theme posts to `admin-post.php` and redirects. An auth form
cannot: it has to re-render with the typed values still in the fields and the
errors beside them. So `/login/` and `/register/` are processed on
`template_redirect` — the same pattern core's own `wp-login.php` uses. Nothing
lands in a query string, so no phone number reaches a server log.

### Dropping in OTP

Iranian students expect a code by SMS, not a password. There is no SMS account
yet, so the built-in forms use a password and two filters are the handover:

```php
add_filter( 'zandi_login_shortcode',    fn() => '[your_otp_login]' );
add_filter( 'zandi_register_shortcode', fn() => '[your_otp_register]' );
```

Set either and the theme keeps its card, heading and page chrome and hands the
form itself to the plugin.

> `zandi_identity_verified()` returns **true** today. It is not a security check
> — it is a declaration that nothing has verified the number yet. Do not grant
> anything on the strength of it until an OTP flow is actually wired.

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
answers open, a scrollable testimonial shelf and the nav expanded.

The account pages and the panel carry no `.reveal` at all. A login form that
needs JavaScript to become visible is a login form that can fail shut, so those
surfaces are painted by CSS alone.

---

## Performance

- One self-hosted variable font, preloaded; no webfont CDN round-trip.
- Zero raster images — the hero panel, course thumbnails and logo are inline SVG
  and CSS gradients, so they cost no requests and stay sharp at any density.
- ~40 KB CSS and ~9 KB JS, both unminified and uncompressed. No framework
  runtime.
- Post thumbnails carry `loading="lazy"` and reserved aspect ratios.

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
