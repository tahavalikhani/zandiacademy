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
functions.php             Setup, enqueues, nav walker, booking handler
header.php  footer.php    Site chrome
front-page.php            Homepage — section ordering only
index.php                 Blog index, archives, search
page.php  single.php      Editorial templates
comments.php  searchform.php
inc/
  content.php             Every Persian string on the homepage
  icons.php               Inline SVG icon registry
  template-tags.php       Button, badge, card, avatar, rating, heading helpers
template-parts/home/      One file per homepage section
assets/
  fonts/                  Vazirmatn variable woff2 (self-hosted)
  js/theme.js             The only JavaScript (~5 KB, no dependencies)
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

### The booking form

`admin-post.php` receives the closing form. Hook the result:

```php
add_action( 'zandi_booking_submitted', function ( $name, $phone ) {
	wp_mail( get_option( 'admin_email' ), 'درخواست مشاوره', "$name — $phone" );
}, 10, 2 );
```

It is nonce-checked and works without JavaScript (POST + redirect); `theme.js`
upgrades it to a fetch so the page does not reload. This is the theme's only
network seam.

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

## Not included yet

Homepage plus the standard editorial templates. The navigation links to
`#courses`, `#teachers`, `#about`, `#faq` and `#contact`, which are on-page
anchors today and become real pages when they exist — point the menu at them and
`zandi_resolve_anchor()` steps out of the way.

Course thumbnails and instructor photos render considered placeholders
(engraved gradient panels, initials avatars). Add an `image` key in
`inc/content.php` and `zandi_avatar()` swaps in the photograph; the aspect ratio
is already reserved, so nothing shifts.
