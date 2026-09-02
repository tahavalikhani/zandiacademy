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

## 0. Start here — measure before changing anything

**Added 2 September 2026, after a fourth round of blind optimisation.**

Every round of performance work on this site has been done without a single
measurement of the live server. The theme has now been audited four times and
is not the bottleneck; each audit ends by recommending a page cache, and the
site is still slow. That is what happens when you keep tuning the half you can
see.

`tools/zandi-perf-probe.php` measures the half nobody has looked at. It is a
temporary mu-plugin that watches one real page load and prints where the time
went.

1. Upload it to `wp-content/mu-plugins/` (create the folder if it is not there).
   Files there load automatically — there is nothing to activate.
2. Sign in as an administrator.
3. Open `https://zandiacademy.com/?zandi_probe=1` and scroll to the bottom.
4. Do the same on `https://zandiacademy.com/courses/a1?zandi_probe=1`.
5. Copy both reports out.
6. **Delete the file when finished.**

Only a signed-in administrator who asks for the report by hand ever sees it. A
visitor cannot trigger it and is not slowed down by it.

### How to read it

The **TIME** block is the diagnosis. The gaps between milestones say which part
of WordPress is expensive:

| Where the big gap is | What it means | Go to |
| --- | --- | --- |
| `request start` → `muplugins_loaded` | PHP is parsing plugin and theme code before anything runs | [2.3 OPcache](#23-opcache-check-this-first) |
| `plugins_loaded` → `init` | Plugins are doing work on every request | [2.7 audit the plugin list](#27-audit-the-plugin-list) |
| `init` → `wp` | Database, or WP-Cron running on the page load | [2.2](#22-take-wp-cron-off-the-request-path), [2.4](#24-object-cache-if-offered) |
| Anything in **OUTBOUND HTTP** | The visitor is waiting on another server | [2.8](#28-the-quiccloud-trap) |
| **AUTOLOADED OPTIONS** over 1 MB | Every request reads all of it, cached or not | [2.9](#29-autoloaded-options) |

If the whole report shows a fast page, then the server is fine and the problem
is the network path to the visitor — which is a hosting question, not a code
one.

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

### Added 2 September 2026

- **`srcset` on the course covers.** The portrait got this treatment in August
  and the covers were missed, which left the *homepage* — three covers — as the
  page with the most avoidable bytes on it. Each cover is 800px wide because a
  3-up desktop card needs that at 2×; a phone draws the same card about 320px
  wide and was being sent the whole file.

  | Cover | 400px | 600px | original (800px) |
  | --- | --- | --- | --- |
  | `course-a1` | 8,986 | 14,062 | 23,452 |
  | `course-a2` | 13,494 | 22,710 | 42,894 |
  | `course-b1` | 10,602 | 17,410 | 30,688 |
  | **total** | **33,082** | **54,182** | **97,034** |

  A 1× phone loading the homepage fetches **33 KB instead of 97 KB**. Two
  variants rather than the helper's default three: the original is only 800px,
  so 960 does not exist and 480/640 sit too close together to be worth two
  files. `zandi_course_cover_srcset()` and `zandi_course_cover_sizes()` are in
  `inc/content.php`, beside `zandi_course_cover()` — the `sizes` lives next to
  the `srcset` deliberately, because a `sizes` that disagrees with the layout
  makes the browser pick the wrong file, which is worse than sending no
  `srcset` at all. Used by the homepage cards and the placement result cards.

- **WooCommerce Order Attribution is dequeued.** Added in WooCommerce 8.5, it
  loads `sourcebuster.js` plus an initialiser on **every page of the site** —
  not just the shop — and writes a family of `sbjs_*` cookies from JavaScript to
  remember where a visitor came from.

  It is removed for two reasons. It is bytes and a cookie write on every page
  view for a last-click attribution report this academy does not use. And the
  `sbjs_*` cookies are a known page-cache irritant: a cache configured to vary
  on, or bypass for, unrecognised cookies stops serving cached HTML once they
  exist — which is exactly the failure where a cache plugin is installed, looks
  enabled, and changes nothing.

  **This turns a feature off.** With it active, orders record their origin as
  «نامشخص» rather than naming the referrer. One line brings it back:

  ```php
  add_filter( 'zandi_trim_order_attribution', '__return_false' );
  ```

  The tidier fix, which also stops the PHP side loading, is WooCommerce's own
  switch: **ووکامرس ← تنظیمات ← پیشرفته ← ویژگی‌ها**, untick «Order
  Attribution». `zandi_woo_trim_order_attribution()` is the belt to that pair of
  braces and is harmless when the setting is already off.

### Audited and found clean, 2 September 2026

Recorded so the next audit does not spend its time here again. All of this was
checked and none of it is a problem:

- **No outbound HTTP** anywhere in the theme. Nothing on a page load waits on
  another server.
- **No uncached database work on the front end.** `zandi_courses_data()`,
  `zandi_course_product_map()` and `zandi_media()` are all memoised, and the
  last two are also cached in an option and a transient. `inc/students.php` —
  the only file with real queries in it — is behind `is_admin()`.
- **Cookies are correctly scoped.** The intent cookie is written only on
  `/login/` and `/register/`, the placement cookie only on submission. Nothing
  puts a `Set-Cookie` on an anonymous homepage request, which is what would stop
  the site being page-cached at all. (The cookies that *do* threaten the cache
  come from WooCommerce, not from here — see Order Attribution above.)
- **`flush_rewrite_rules()` is properly guarded** by a stored version compare,
  so it runs on a route change and never on an ordinary page load.
- **`theme.js` is clean** — passive scroll listeners, `IntersectionObserver`
  rather than scroll maths, a `prefers-reduced-motion` guard, and layout reads
  confined to the carousel and the notice tagger.
- **Stylesheets do not overlap.** `courses.css`, `panel.css`, `placement.css`
  and `shop.css` are each scoped to their own page type and enqueued only there.

**The conclusion is worth stating plainly: the theme is not what is slow.** If
the site still feels slow after part 2, the answer is in the probe's output, not
in another pass over the CSS.

### Still on the table

- **Suppressing the discarded main query on virtual routes.** `/courses/a1`,
  `/faq/`, `/panel/` and the rest are built in `parse_request`, so WordPress
  still runs its main blog-index query for them and the result is thrown away.
  It can be short-circuited on `pre_get_posts` — the four route detectors all
  read query vars, which are set by then, and `zandi_prepare_virtual_page()`
  already resets `is_404` afterwards, so emptying the posts is safe.
  **Not done yet because it cannot be tested from a checkout of this repo** —
  it changes routing on a live shop, and the test harness stubs WordPress rather
  than running it. Do it on a staging copy, not straight to production. The win
  is a query or two per virtual page, so it is a tidy-up, not a rescue.
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

In rough order of how much each one buys. **Run the probe in part 0 first** —
it tells you which of these is actually your problem, so you are not doing all
seven and hoping.

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

#### Then prove it is actually serving

**Do not skip this.** A cache plugin that is installed, shows a green status
page and caches nothing is the single most likely reason this site has stayed
slow through several rounds of work.

In a **private/incognito window** (so you are signed out), open DevTools →
Network, load the homepage, click the first request, and read the response
headers. Then reload and read them again.

- LiteSpeed serving from cache: `x-litespeed-cache: hit`. The first load will
  say `miss` — that is correct, it is the second that matters.
- WP Super Cache: an HTML comment at the very bottom of the page source saying
  the page is cached, with a timestamp.

If the second load still says `miss`, the cache is not working and nothing else
in this list will help. Check, in order: a second caching plugin, the exclusion
list in 2.5 being too broad, and cookies on the request (2.10).

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

### 2.3 OPcache — check this first

**Never checked on this site, and it is the cheapest large win available.**

Without OPcache, PHP re-reads and re-compiles every `.php` file on **every
request**. This theme alone is about 516 KB of PHP; WooCommerce and Digits are
several megabytes more. Compiling all of that per request is hundreds of
milliseconds of pure waste, and it shows up in the probe as a large gap between
`request start` and `muplugins_loaded`.

The probe reports it directly. If it says `OPcache: OFF`:

**cPanel → Select PHP Version → Extensions**, tick `opcache`. On some Iranian
hosts it is under **MultiPHP INI Editor** as `opcache.enable = 1` instead. If
neither exists, ask the host to enable it — it is a standard PHP extension and
there is no good reason for it to be off.

Then reload the probe and confirm the hit rate is above 95%.

### 2.4 Object cache, if offered

If **Redis** or **Memcached** appears in cPanel, enable it and install
*Redis Object Cache*. It caches database results between requests and is the
second-biggest TTFB lever after page caching. If the host does not offer it,
skip — a disk-based "object cache" is usually slower than none.

### 2.5a LiteSpeed Lazy Load and the eNamad seal

LiteSpeed's **Lazy Load Images** rewrites every `<img src=…>` in the page to
`data-src` with a placeholder, and swaps it back with its own script when the
image scrolls into view. On 19 August 2026 that was stopping the نماد اعتماد
seal from loading at all: the footer drew an empty white tile and DevTools,
filtered to `logo.aspx`, recorded **zero requests** — the browser was never
asked to fetch it.

It breaks the seal twice over. The visitor never sees it, and eNamad's own
verification crawler reads the page looking for the logo URL in a `src` — after
the rewrite there is no `src` to find.

The theme now prints the image with `data-no-lazy="1"`, which is the escape
hatch LiteSpeed documents for this, and which WP Rocket and Perfmatters honour
too. Nothing eNamad issued is altered; see `zandi_enamad_seal()` in
`inc/content.php`.

**If the seal is still blank after deploying and purging**, exclude it in the
plugin as well: **LiteSpeed Cache → Page Optimization → Media Excludes → Lazy
Load Image Excludes**, add `trustseal.enamad.ir`, then **Purge All**.

While in that screen, also check **Tuning → Localize Resources** does not list
enamad. That option copies third-party files onto your own server, which for
this seal both breaks it and is the self-hosting eNamad forbids.

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

**Keep this list tight.** An exclusion that is broader than it needs to be —
`/courses`, or a bare `/` — switches the cache off for the pages that matter
most, while the plugin's status page still says caching is enabled. If the
homepage will not return a cache hit, look here before anywhere else.

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

The probe prints the active list with the timings, so a large
`plugins_loaded → init` gap can be read against it. Deleting is better than
deactivating: a deactivated plugin still leaves its autoloaded options behind
(2.9).

### 2.8 The QUIC.cloud trap

**Specific to this site, and easy to miss.**

LiteSpeed Cache's page *caching* runs on your own server. But several of its
headline optimisation features do not — **Critical CSS (CCSS), Unique CSS
(UCSS), Low-Quality Image Placeholder and Image Optimization are all generated
on QUIC.cloud's servers**, not locally. The plugin sends your pages out, waits
for a result, and applies it when it arrives.

From an Iranian IP that round trip is not reliable. The failure is quiet and it
looks exactly like the plugin doing nothing: CCSS is switched on, the queue
never drains, and pages are served with their CSS loaded asynchronously *without*
the critical CSS that was supposed to make that safe — so the page renders
unstyled for a moment and then jumps. That reads as "slower after I installed
the cache plugin", which is the worst possible outcome because it makes the one
thing that would help look like the thing that hurt.

**LiteSpeed Cache → Page Optimization → CSS Settings**, and check:

- *Generate Critical CSS* — **off**, unless the queue is demonstrably draining.
- *Generate UCSS* — **off**, same reason.
- *Load CSS Asynchronously* — **off**. Without CCSS this one is actively
  harmful; with it off, the theme's stylesheets load normally.

**Page Optimization → Media Settings**: leave *Image Optimization* alone unless
QUIC.cloud is confirmed reachable.

Keep **Cache**, **Minify CSS/JS**, **Combine** and **Browser Cache** on — all of
those run locally on your own server and are the parts that actually help.

Check the queue at **LiteSpeed Cache → Toolbox → Queue**. A queue with entries
that never clear is this problem.

### 2.9 Autoloaded options

**The most commonly missed cause of a slow WordPress, and never checked here.**

Every row in `wp_options` marked `autoload = yes` is fetched and unserialized on
**every single request** — including cached ones, and including admin-ajax
calls. WooCommerce, Digits, and every plugin ever installed and deleted leave
rows behind. Sites that have been running for a year routinely carry several
megabytes of this.

The probe reports the total and names the largest rows. Read it as:

| Total | Verdict |
| --- | --- |
| under 200 KB | healthy |
| 200 KB – 1 MB | fine, not worth chasing |
| 1 MB – 3 MB | worth cleaning |
| over 3 MB | **on its own enough to explain a slow site** |

To clean it, in **phpMyAdmin → SQL** (**back up the database first**), look at
what the probe named. Rows belonging to a plugin that is no longer installed are
safe to delete:

```sql
DELETE FROM wp_options WHERE option_name = 'the_name_the_probe_showed';
```

Expired transients are always safe to clear:

```sql
DELETE FROM wp_options
WHERE option_name LIKE '\_transient\_%'
   OR option_name LIKE '\_site\_transient\_%';
```

WordPress regenerates any it still needs. If a row belongs to a plugin that *is*
still active, do not delete it — turn off the feature that fills it instead.

### 2.10 Cookies that defeat the cache

If the probe shows a fast page but visitors still wait, the cache is being
bypassed rather than being slow. The usual culprits, in order:

- **`sbjs_*`** — WooCommerce Order Attribution. Dealt with in part 1; confirm
  the scripts are gone by searching the page source for `sourcebuster`.
- **`woocommerce_items_in_cart` / `woocommerce_cart_hash`** — set once a visitor
  puts something in the cart, and they *should* bypass the cache for that
  person. Correct behaviour; not a bug.
- **Anything a plugin sets on a first page view.** In an incognito window, load
  the homepage and check DevTools → Application → Cookies. A signed-out visitor
  on the homepage should have almost nothing there.

---

## 3. Checking whether it worked

Re-run **ابزارها → سلامت سایت** and read the reported response time. That single
number is the measure. Anything under 600 ms clears the threshold; under 300 ms
means the page cache is doing its job.

Better, re-run the probe from part 0 and compare the TIME block against the
first run. That says *which* change bought the improvement, which matters the
next time this comes up.

If it has barely moved after 2.1, the cache is not actually serving — check for a
second caching plugin, confirm the plugin's own status page says caching is on,
and verify a logged-out browser gets a cache header.

---

## 4. A note on measuring from outside Iran

The audit on 2 September 2026 could not reach `zandiacademy.com` at all. From a
datacentre with good general connectivity — Google answered in 460 ms on the
same connection — every attempt to `zandiacademy.com` (185.255.90.50) stalled
mid-TLS-handshake and timed out after about 12 seconds, on both port 443 and
port 80. Google's PageSpeed Insights API could not be used either, for an
unrelated quota reason.

**This cannot be read as a verdict on the server.** Iranian hosts commonly
filter foreign IPs, and that is indistinguishable from an overloaded origin from
the outside. It does mean two things worth writing down:

1. Every remote audit of this site is working from the source code alone. That
   is why `tools/zandi-perf-probe.php` exists — the owner can measure from
   inside Iran, where the site is actually reachable.
2. If the host *is* filtering foreign traffic, PageSpeed Insights, GTmetrix,
   Google Search Console's Core Web Vitals and every other external testing
   tool will fail or report nonsense. That is worth confirming with the host
   before trusting any score from one of them.
