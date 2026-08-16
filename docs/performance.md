# Performance — what the theme does, and what the server has to do

Written after WordPress Site Health reported, on 4 August 2026:

> کش برگه شناسایی نشد و زمان پاسخ سرور کند است
> میانگین زمان پاسخ سرور ۱٬۷۳۷ میلی‌ثانیه بود. این مورد باید کمتر از آستانه
> پیشنهاد شده ۶۰۰ میلی‌ثانیه باشد.

**Read this first.** 1,737 ms is *time to first byte* — how long the server takes
before sending a single character of HTML. It is spent booting PHP, loading
plugins and running queries. Nothing in a stylesheet, a font or a template can
change it. The theme work below is worth doing and makes the page lighter once it
arrives, but **about 1.4 of those 1.7 seconds are won on the server**, in
[part 2](#2-what-has-to-happen-on-the-server).

---

## 1. What the theme does

| Measured on disk | Bytes |
| --- | --- |
| `style.css` + `rtl.css`, render-blocking on every page | 78,210 |
| `assets/css/courses.css`, course pages only | 35,039 |
| `assets/css/panel.css`, account and panel pages only | 55,148 |
| `assets/js/theme.js`, deferred | 20,919 |
| Peyda actually downloaded — 3 faces (400/600/700) | 145,960 |

> These are re-measured on 16 August 2026. The three figures this table carried
> before that date were written once and never updated — CSS was quoted at
> 63,209 and `theme.js` at "~5 KB", against actual figures a third and four
> times larger. If you edit a stylesheet, re-run `stat -c '%s %n'` over this
> list rather than trusting the numbers here.

Measured over the wire, before and after:

| Page | Requests | Uncompressed |
| --- | --- | --- |
| Homepage — before / after | 13 / 13 | 813 KB / 814 KB |
| Course page — before / after | 12 / **11** | 518 KB / **410 KB** |

The homepage carries the course cover photographs, which is most of its weight
and is real content. The course page lost 108 KB and a request. These are
*uncompressed* figures — with the compression in
[2.6](#26-browser-caching-headers-for-static-assets) the CSS shrinks by roughly
80% on top of this.

### Removed from every front-end page

`inc/performance.php` holds this, one function per concern, each behind its own
filter. Nothing touches wp-admin or the block editor.

- **Emoji script and styles.** ~12 KB of JavaScript whose job is replacing emoji
  with images on browsers that cannot draw them. It also emitted a `dns-prefetch`
  to `s.w.org`, a host that is not reliably reachable from Iran.
- **Block editor stylesheets** — `wp-block-library`, `wp-block-library-theme`,
  `classic-theme-styles`, `global-styles`. Around 90 KB that styled nothing,
  because every page here is built from PHP templates. Guarded by
  `is_singular() && has_blocks()`, so a post written in the block editor keeps
  its styling automatically — there is no setting to remember.
- **oEmbed discovery** links and `wp-embed.js`.
- **Head leftovers** — RSD, Windows Live Writer manifest, the generator tag
  (which published the exact WordPress version to anyone scanning for known
  vulnerabilities), shortlink, adjacent-post links, and the REST API `<head>`
  tag. The REST `Link:` *header* is kept; it is free and tools use it.
- **Heartbeat** throttled to 60s on the front end. The admin keeps its default,
  because post locking and autosave depend on it.

Deliberately kept: `feed_links`, in case the academy blogs later.

### Other changes

- `assets/js/theme.js` is now `defer`red, not merely footer-placed. Nothing on
  the page depends on JavaScript, so this is safe by construction.
- The **Playfair preload was removed** from course pages. It was 38 KB competing
  with the Peyda preload on the critical path for a face that only sets a few
  Latin runs inside headings. The `@font-face` stays and loads under
  `font-display: swap`.
- **Peyda declares only the weights the CSS asks for** — 400, 600 and 700.
  ExtraLight and Black were declared but never fetched by any browser, because no
  rule requests them and CSS resolves weight 500 down to 400. They now cost
  nothing at all rather than two dead `@font-face` blocks per page.
- `zandi_courses_data()` is memoised. A course page reached it 8–11 times per
  request, rebuilding the whole dataset each time.
- **Vazirmatn is no longer fetched for symbols.** This was the largest saving
  found, and it was invisible until the requests were actually measured. Course
  copy uses 🇫🇷 📦 🎬 as well as ✦ ▶ ⚠ ⏱ ♾. Peyda contains none of them, so for
  each one the browser walked the font stack looking for a match and downloaded
  the next webfont it found — **all 108 KB of Vazirmatn** — on a page whose
  Persian was already being set in Peyda.

  Two changes fix it. The stack now names `Apple Color Emoji, Segoe UI Emoji,
  Noto Color Emoji` between Peyda and Vazirmatn, which catches the true emoji.
  That is not enough on its own: ✦ ▶ ⚠ ⏱ ♾ are **dingbats, not emoji**, so no
  emoji font claims them either. So the Vazirmatn `@font-face` now carries a
  `unicode-range` saying what it is for — Persian and Latin text — which means
  the browser cannot select it for a symbol at all.

  Everything still renders: the symbols fall through to the system font that
  draws them, and the no-Peyda fallback deploy still loads Vazirmatn normally
  for its Persian. A course page is **108 KB and one request lighter**.

  Note the `unicode-range` includes `U+200C`. That is ZWNJ, and Persian word
  shaping depends on it — leaving it out would break «می‌کنیم».

### Added 16 August 2026

Two more theme-side wins, from the audit against the «مشکل‌ها» checklist.

- **The hero text no longer waits for JavaScript.** `.hero__title`,
  `.hero__description` and `.hero__actions` carried `.reveal`, which is
  `opacity: 0` until deferred `theme.js` adds `is-visible`. The headline was
  therefore invisible from first paint until the document had parsed and the
  script had run — which is precisely the window Largest Contentful Paint is
  measured in. The site was reporting a slow largest paint for text that had
  been ready the whole time. The `no-js` rule does not help here: it covers
  scripts being *off*, not scripts that simply have not run yet.

  A scroll-reveal above the fold never animates anyway — the element is already
  in view when the observer starts — so nothing is lost visually.

- **`srcset` on the portrait.** `shima.webp` is 1282px wide and 74,568 bytes,
  and the hero draws it about 350px wide on a phone, where it is also the
  largest paint. Three scaled variants are now committed beside it and offered
  through `zandi_image_srcset()`:

  | File | Bytes |
  | --- | --- |
  | `shima-480.webp` | 23,302 |
  | `shima-640.webp` | 34,232 |
  | `shima-960.webp` | 58,498 |
  | `shima.webp` (original, full-size candidate) | 74,568 |

  A 1× phone now fetches 23 KB instead of 74 KB. They are **scaled, never
  cropped** — the owner frames her own photographs. Generated once with GD and
  checked in, because there is no build step here by design; delete them and
  the helper returns `''` and the plain `src` keeps working.

### Still on the table

- **Minification.** This repo has no build step by design, so the theme cannot
  minify itself. Let the cache plugin do it at runtime — both plugins below can.
- **Font subsetting.** Peyda ships ~146 KB across three unsubsetted faces.
  Subsetting to Persian + basic Latin would cut that by roughly 60%, but
  subsetting *modifies the font*, which the licence may forbid. **Ask fontiran
  before doing it, and get the answer in writing.**
- `Vazirmatn-Variable.woff2` (111 KB) ships but — since the emoji fix above — is
  never downloaded while Peyda is installed. It is the free fallback for a deploy
  that is missing the Peyda files, so it is worth keeping in the repo.

---

## 2. What has to happen on the server

In rough order of how much each one buys.

### 2.1 Install a page cache — this is the fix

First find out which web server is running. **cPanel → PHP Info**, or open the
site with the browser's Network tab and read the `Server:` response header.

**If it says LiteSpeed** — common on Iranian cPanel hosting — install
**LiteSpeed Cache**. Free, 7M+ installs, actively maintained. It is the only
plugin that can drive the server's own cache module. Turn on *Cache → Enable
Cache*, and its CSS/JS minify and combine options, which covers the minification
the theme cannot do itself.

**If it says Apache or nginx** — install **WP Super Cache** (Automattic,
1M+ installs). Set *Caching On*, and prefer *Simple* delivery mode over *Expert*
unless the host confirms it is safe to write mod_rewrite rules.

Never run two caching plugins at once. That is worse than running none.

> WordPress.org is sometimes unreachable from Iranian IPs. If the plugin
> installer fails, download the `.zip` on a working connection and upload it via
> **افزونه‌ها → افزودن → بارگذاری افزونه**.

### 2.2 Take WP-Cron off the request path

By default WordPress runs scheduled tasks *during a visitor's page load*, which
is a classic TTFB spike. Two halves, both required.

In `wp-config.php`, above the `/* That's all, stop editing! */` line:

```php
define( 'DISABLE_WP_CRON', true );
```

Then **cPanel → Cron Jobs**, every 15 minutes:

```
wget -q -O - https://zandiacademy.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

Setting the constant without adding the cron job silently stops every scheduled
task on the site.

### 2.3 PHP 8.2 or newer

**cPanel → Select PHP Version.** Materially faster than PHP 7.x. Load the site
afterwards to check. If a plugin breaks, that plugin is the problem.

### 2.4 Object cache, if offered

If **Redis** or **Memcached** appears in cPanel, enable it and install
*Redis Object Cache*. It caches database results between requests and is the
second-biggest TTFB lever after page caching. If the host does not offer it,
skip — a disk-based "object cache" is usually slower than none.

### 2.5 Two cache exclusions this site specifically needs

Add both in the cache plugin's exclusion settings:

- **`/login/`, `/register/`, `/panel/` and `/placement/`** — every one of them
  renders differently per visitor. A cached `/panel/` served to the next person
  shows them someone else's dashboard, and a cached placement result hands out
  one student's score. `/panel/` is behind a login so most plugins skip it by
  default; the other three are not, so name them explicitly.
- **`/wp-admin/` and `wp-login.php`** — normally excluded by default; confirm.

> An earlier version of this list told you to exclude `booking=ok`, for the
> free-consultation form's thank-you redirect. That form, `zandi_handle_booking()`
> and the flag itself were all removed on 30 July 2026. There is nothing to
> exclude and the rule can be deleted if it was ever added.

**Then check the virtual pages.** `/courses/a1`, `/faq/` and the rest are built
in `parse_request` rather than being real WordPress pages. Load `/courses/a1`
twice and confirm it still returns **200** and that the second load carries a
cache header. This is worth testing rather than assuming.

### 2.6 Browser caching headers for static assets

Site Health's second complaint. If the cache plugin adds its own block, skip
this. Otherwise add above `# BEGIN WordPress` in `.htaccess` — **back the file up
first**:

```apache
<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresByType text/css               "access plus 1 year"
	ExpiresByType application/javascript "access plus 1 year"
	ExpiresByType font/woff2             "access plus 1 year"
	ExpiresByType image/svg+xml          "access plus 1 month"
</IfModule>

<IfModule mod_deflate.c>
	AddOutputFilterByType DEFLATE text/html text/css application/javascript image/svg+xml
</IfModule>
```

The compression half matters most: it takes the theme's 78 KB of CSS down to
roughly 15 KB over the wire.

Long cache lifetimes are safe because every theme asset is versioned by **its
own modification time**, through `zandi_asset_version( $path )`. Edit a
stylesheet and its URL changes on the next request; nothing has to be bumped by
hand. (This paragraph used to say `ZANDI_VERSION`, which is exactly the mistake
that constant caused: it never moved, so every stylesheet stayed pinned at
`?ver=1.1.0` while templates updated instantly, and deploys looked half-applied.
Never enqueue an asset with the bare constant.)

### 2.7 Audit the plugin list

**افزونه‌ها → افزونه‌های نصب‌شده.** Every active plugin runs on every request.
Deactivate and delete anything not genuinely in use — page builders especially,
and any second caching or "optimisation" plugin.

---

## 3. Checking whether it worked

Re-run **ابزارها → سلامت سایت** and read the reported response time. That single
number is the measure. Anything under 600 ms clears the threshold; under 300 ms
means the page cache is doing its job.

If it has barely moved after 2.1, the cache is not actually serving — check for a
second caching plugin, confirm the plugin's own status page says caching is on,
and verify a logged-out browser gets a cache header.
