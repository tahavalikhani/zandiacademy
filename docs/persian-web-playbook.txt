# Building websites for Persian-speaking clients — field notes for AI agents

> **What this is.** Lessons from one real, shipped project: the WordPress site
> of **آکادمی زندی (Zandi Academy)**, a French-language school based in Iran
> that sells pre-recorded video courses, a paid podcast and a free placement
> test to Persian speakers, paid in Toman. Written on 24 September 2026 from the
> project's code, its docs and ~110 commits made between July and September
> 2026.
>
> **What this is not.** A specification, or a list of instructions. Nothing
> here is "the right way" for your project. It is what one project did, **why it
> did it**, and what each choice cost. Your client might sell clothes, run a
> clinic, publish a magazine or live in Toronto. For each item, check whether
> the *reason* applies to them. If the reason doesn't apply, the decision
> probably shouldn't either. When in doubt, ask the client. Don't assume.

---

## 0. How to read this document

Every section is tagged with how far it is likely to transfer:

| Tag | Meaning |
| --- | --- |
| **[Universal]** | Comes from the Persian language or script itself. Likely true for any Persian-language site, wherever it is hosted. |
| **[Iran]** | Comes from serving users **inside Iran** and/or **hosting in Iran**: sanctions, filtering and local payment systems. Find out where your client's users and servers are before you apply any of it. |
| **[WordPress]** | Specific to building on WordPress. Useful only if your project uses it too. |
| **[Choice]** | A decision this project made for its own reasons. It's an option to consider, not a default. |

**Dates matter.** Iranian services, WordPress plugins and internet filtering
change month to month. Anything dated here was true on that date, as far as the
reference project could tell. **Check it again before you rely on it**: search the web,
open the plugin's WordPress.org page, ask the client. The reference project
made this a hard rule after it nearly recommended a payment plugin that had been
pulled from WordPress.org for a security issue.

**Nothing here was re-verified on the day it was written.** It's a snapshot of
what the project recorded, each fact with its date.

---

## TL;DR — the twelve things most worth knowing

1. **First, find out where the users are and where the server will be.** Users
   inside Iran vs. abroad (the diaspora) changes payments, hosting, fonts, maps,
   captchas, messaging and email. It even decides whether *you* can reach the
   live site to test it.
2. **Western payment processors don't work inside Iran**: no Stripe, PayPal,
   Google Pay or Apple Pay. Use Iranian gateways, and watch the
   **Toman ↔ Rial factor of ten**.
3. **A request to a blocked host can hang the page, not just fail.** That
   includes Google Fonts, reCAPTCHA, Maps, Analytics, Gravatar and many CDNs.
   For an Iranian audience, self-host everything the page needs to render.
4. **Right-to-left layout with Latin text mixed in** is where Persian sites
   visibly break. Product codes, brand names, level codes and phone numbers get
   reordered. Isolate every Latin run.
5. **Digits come in three alphabets** (۰۱۲ Persian, ٠١٢ Arabic-Indic, 012 Latin).
   Normalise what users type, and localise what you print on purpose. Never
   switch all digits at once with a font feature or a "FaNum" font variant.
6. **For most Iranian users, identity is a mobile number, not an email.** They
   expect SMS one-time-code (OTP) login. Email sent from Iranian servers to
   Gmail is often dropped.
7. **Audit Iranian WordPress plugins before you trust them.** Section 2.3 lists
   three real security incidents in plugins this project depended on or nearly
   installed.
8. **Speed problems on Iranian hosting are usually on the server** (time to first
   byte). Measure before you optimise CSS. The reference project audited its theme four
   times before admitting that.
9. **Never invent facts to fill a layout**: no made-up statistics, reviews,
   star ratings, addresses or phone numbers. Use an empty state and a TODO.
10. **The owner will configure things in wp-admin themselves.** Give exact click
    paths in the Persian admin UI, e.g. «تنظیمات ← پیوندهای یکتا».
11. **Plugins change your pages without asking.** Plugins have justified every
    paragraph on the site, lazy-loaded a government trust seal until it
    disappeared, and loaded shop scripts on every page. Expect it, and check the
    rendered output.
12. **Write decisions down with dates and reasons** (in a `CLAUDE.md` or
    similar), so the next agent doesn't re-ask the owner or undo a deliberate
    choice.

---

## 1. Questions to settle with the client first

These are the decisions that changed the most code in the reference project.
Asking them on day one is cheaper than finding them out in week six.

| Question | Why it matters | What the reference project answered |
| --- | --- | --- |
| **Where are the users?** Inside Iran, abroad, or both? | Decides payments, hosting, which third-party services work at all, email vs SMS. | Inside Iran. |
| **Where will the site be hosted?** Iranian host or a foreign one? | An Iranian server can't reach many foreign APIs (Telegram, some licence servers, sometimes WordPress.org). Foreign test tools and remote agents may not be able to reach the site at all. | Iranian shared hosting running LiteSpeed. |
| **How does money move?** | Iranian gateways only for Iranian cards. Direct bank gateways need a registered company plus eNamad. | ZarinPal (an aggregator), prices in Toman. |
| **Does the business have eNamad (نماد اعتماد الکترونیکی) and a registered company?** | Needed for direct bank gateways, and a real trust signal for Iranian buyers. | eNamad obtained 12 August 2026, one star; no company, so an aggregator gateway. |
| **What is being sold?** Physical goods, digital goods, services, bookings, subscriptions? | Physical goods need address fields, provinces and cities, and shipping. Digital goods need none of that, and the extra fields only add friction. | Digital: video courses (delivered as SpotPlayer licences) and a subscription to a private Telegram group. |
| **Who edits the site after launch, and how technical are they?** | Decides whether copy lives in code (fast, safe, needs a developer) or in wp-admin (editable, easier to break). | The owner, non-technical. Copy lives in code; she uploads media and sets prices in wp-admin. |
| **How do customers reach a human?** Telegram, WhatsApp, Instagram DM, phone, a form? | The channel changes, so it should be defined in one place (§7). | Telegram, with three different accounts for three different jobs. |
| **Is login needed? How?** | Phone + SMS code is the local expectation. | Yes: phone-only OTP through the Digits plugin and an Iranian SMS provider. |
| **What real content exists?** Photos, reviews, prices, credentials? | You may not invent any of it (§10.2). Missing content gets an empty state. | Only what the owner supplied. Several sections shipped as honest placeholders. |
| **What should it look like: the local norm or something deliberately different?** | Many Iranian sites in a sector look alike. Standing out can be the point, or it can feel foreign. The client decides. | Deliberately *not* a typical Iranian language-institute (آموزشگاه) site: minimal and premium, modelled on Apple, Stripe and Linear. |
| **What tone: formal (رسمی) or conversational (محاوره‌ای)?** | It affects every string on the site, and mixing the two reads badly. | Conversational, first person, in the teacher's own voice («حسابت رو بساز»). |

---

## 2. WordPress — what it is, and why this project chose it

### 2.1 What WordPress is [WordPress]

A short orientation for agents who haven't worked with it.

- **An open-source content management system** written in PHP, storing data in
  MySQL/MariaDB. It runs on almost every shared host, including practically all
  Iranian ones. Its admin area (**wp-admin**, «پیشخوان» in Persian) is fully
  translated into Persian (`fa_IR`).
- **Core** gives you posts, pages, a media library («رسانه»), users and roles,
  menus, settings, a REST API, an XML sitemap and pretty URLs through rewrite
  rules («پیوندهای یکتا»).
- **Themes** control presentation. A *classic* theme is PHP template files
  (`header.php`, `front-page.php`, `page.php` …). A *block* theme is edited in the
  Site Editor and configured with `theme.json`. A theme lives in
  `wp-content/themes/<name>/`.
- **Plugins** add features: **WooCommerce** (a shop), SEO plugins (Yoast,
  Rank Math), cache plugins (LiteSpeed Cache, WP Super Cache), payment-gateway
  plugins, SMS/OTP login plugins, and so on.
- **Hooks** are how everything plugs together. *Actions* run code at a moment
  (`add_action( 'init', … )`). *Filters* let code change a value on its way
  somewhere (`add_filter( 'the_title', … )`, and `apply_filters()` to offer your
  own). Well-behaved theme and plugin code changes WordPress through hooks and
  never by editing core files.
- **`wp-config.php`** holds database credentials and site-wide constants. It is
  the right home for API keys and merchant IDs, **never** the theme's git repo.
- **`wp-content/mu-plugins/`** ("must-use" plugins) load automatically and can't
  be deactivated from the admin. That makes them handy for temporary
  diagnostics you upload, read and delete.
- **The options table** stores settings. Rows marked *autoload* are read on
  **every** request, which is a common hidden cause of slowness (§8).
- **Code vs. database.** Theme code can go in git. Content, media, settings,
  users and orders live in the database, so **a code deploy can't change a
  setting**. Some real bugs exist only in the database, like the «از موتورهای
  جستجو بخواهید این سایت را ایندکس نکنند» checkbox left on after development.
- **Security primitives you are expected to use** rather than reinvent: nonces
  (`wp_nonce_field`, `wp_verify_nonce`), escaping (`esc_html`, `esc_attr`,
  `esc_url`), sanitising (`sanitize_text_field`, `sanitize_key`), capability
  checks (`current_user_can`), `wp_safe_redirect`, and the enqueue API
  (`wp_enqueue_style/script`) instead of hand-written `<link>`/`<script>` tags.

### 2.2 Why this project chose WordPress [Iran] [Choice]

The owner's top priority was **being reachable and payable from inside Iran**.
In order of weight:

1. **The obvious modern alternative wasn't available.** A Next.js site on
   Vercel was considered and dropped: Vercel blocks Iranian IPs, because the
   AWS infrastructure under it enforces the US embargo. Many Western
   platforms either block Iranian visitors or can't be paid for from Iran.
2. **Iranian hosting is mostly PHP/MySQL shared hosting** (cPanel,
   DirectAdmin, often the LiteSpeed web server). WordPress runs there natively.
   No Node server, no container, no build pipeline.
3. **The Iranian commerce ecosystem ships for WordPress first.** Gateway
   plugins (ZarinPal publishes an official WooCommerce plugin), **Persian
   WooCommerce** (Toman currency, Iranian provinces and cities), **WP-Parsidate**
   (the Jalali calendar), OTP login with Iranian SMS panels (Digits), and
   **SpotPlayer**'s WooCommerce plugin for licensing video courses. Each one is
   a settings screen instead of custom integration code.
4. **The owner can run it herself**: a Persian admin, products and prices in
   WooCommerce, videos uploaded through the media library, orders in one list.
5. **One install means one login and one order table.** A separate
   `app.` subdomain for accounts was considered and deferred for that reason.

### 2.3 What WordPress costs, in Iran specifically [WordPress] [Iran]

Weigh these against the benefits above; they are real.

- **Plugin security.** Three incidents in plugins this project used or nearly
  used:
  - The **IDPay** WooCommerce gateway plugin was **closed on WordPress.org on
    7 April 2026 for a security issue**. It had also gone ~3 years without an
    update.
  - **Digits** (the OTP login plugin) had
    **CVE-2025-4094 (CVSS 9.8)** in its 8.4.6.x line. It didn't rate-limit OTP
    checks, so every six-digit code could be brute-forced and any account taken over.
    Fixed in 8.4.6.1.
  - **ZarinPal**'s official plugin, release 5.1.1 (July 2026), removed "a
    hardcoded nonce bypass in the checkout payment-method-switch AJAX handler",
    meaning it had a real checkout vulnerability until then.
  The Iranian plugin ecosystem has a weaker security-review culture than
  WordPress in general. Cracked ("nulled") copies of paid plugins are also widely
  sold. Buy from the vendor, keep plugins updated, and treat an auth or payment
  plugin whose licence has lapsed (so it gets no more updates) as a real risk.
- **Plugins change your output without asking.** This project hit:
  - a Persian plugin that set `text-align: justify` on the whole front end
    (§3.7);
  - WooCommerce loading three stylesheets, cart-fragment AJAX and an
    order-attribution script with its own cookies on **every** page, not just
    the shop;
  - the login plugin loading its assets on every page instead of only on the
    two login pages;
  - a cache plugin's lazy-load rewriting the eNamad trust seal until it
    stopped loading (§5.4).
- **Slow time to first byte** on shared hosting: PHP boot + plugins + database.
  WordPress Site Health reported 1,737 ms on this site (the threshold is
  600 ms). See §8.
- **Updates from WordPress.org are unreliable from Iranian IPs.** Plan to
  download plugin zips elsewhere and upload them via «افزونه‌ها ← افزودن ←
  بارگذاری افزونه».
- **Rewrite rules are fragile.** They live in the database, other plugins wipe
  them when they flush, and with permalinks set to «ساده» (plain) they don't
  exist at all (§11.3).
- **WooCommerce's new block-based cart and checkout ignore the classic PHP
  hooks** themes have always used to change fields (§5.6).

### 2.4 When WordPress may not be the best fit

These are considerations, not rules:

- **Diaspora audience that pays with Western cards** → hosted e-commerce or any
  modern stack becomes available. The Iran constraints in this document mostly
  disappear. The Persian-language ones (§3) do not.
- **A heavily interactive application** (dashboards, real-time features,
  complex state) → WordPress can do it, but it fights you. A PHP framework such
  as Laravel deploys to the same Iranian hosts. A Node stack is an option if the
  client has a VPS.
- **A tiny brochure site that nobody will edit** → static HTML on an Iranian
  host may be the simplest thing that works.
- **A client already on an Iranian hosted store-builder** → weigh the cost of
  migrating against what they'd gain.

If you do choose WordPress, the next choice is **how to build the theme**:

| Approach | Good for | Cost |
| --- | --- | --- |
| **Hand-written classic PHP theme** (this project) | Speed, full control of markup, RTL/bidi precision | Layout changes need a developer |
| **Page builder** (Elementor and similar, very common in Iran) | Owner edits layout visually | Heavier pages, more plugin surface, markup you don't control |
| **Block theme + Site Editor** | Native editing, lighter than builders | Check its RTL quality for your design before committing |
| **Ready-made theme from an Iranian marketplace** (e.g. راست‌چین, ژاکت) | Fast start, Persian out of the box | Quality and update cadence vary; verify licence and support |

### 2.5 How this project used WordPress: patterns you may reuse [WordPress] [Choice]

- **No build step**: no npm, no bundler, no Tailwind, no page builder. Plain
  CSS and one vanilla JS file, committed as-is and deployed by copying files.
  An earlier React/Vite version was deliberately removed. The reason was deploy
  simplicity on shared hosting, and letting the next developer edit without a
  toolchain.
- **All copy in PHP arrays behind `apply_filters()`** (`inc/content.php`,
  `inc/courses.php`). One source for every string, and a seam where ACF, the
  Customizer or a custom post type can plug in later without touching templates.
  *Trade-off:* the owner can't edit copy in wp-admin. For a client who edits
  weekly, prefer pages, custom post types or ACF fields.
- **Section partials compose helper functions** (`zandi_button()`,
  `zandi_badge()`, `zandi_section_heading()` …) and never repeat markup. A new
  visual pattern becomes a helper first.
- **"Virtual routes"**: `/courses/{slug}`, `/about/`, `/contact/`, `/login/`,
  `/panel/` and others are answered by theme code, not by WordPress Pages. It
  kept the pages in code and design-controlled. The cost is the wiring listed in
  §11.3, and SEO plugins can't edit these pages.
- **WooCommerce for money only.** Products carry the price and record who
  paid; everything a visitor reads still comes from the theme. A course is
  *linked* to a product through post meta. It was linked by SKU first, which
  was wrong: a SKU is a free-text field any admin can clear with one click, and
  clearing it silently removed paid courses from students' dashboards.
- **Admin-only code is loaded only in the admin.** The owner's students
  screen is `require`d behind `is_admin()`, so a public page view never parses
  it. The promise was "it cannot slow the site", and that's how it is kept.
- **Command-line tests against a WordPress stub** (`tests/wp-stub.php`) so logic can
  be checked without a WordPress install. Each test exits unless
  `PHP_SAPI === 'cli'`, because the theme folder is web-served.

---

## 3. Language, script and typography [Universal]

### 3.1 Right-to-left layout

- **Set `dir="rtl"` on `<html>` yourself.** WordPress only prints it when the
  site language is an RTL locale. An install left on `en_US` rendered the whole
  site left-to-right. The reference theme forces it with a `language_attributes`
  filter and always loads its RTL stylesheet.
- **Write CSS with logical properties** (`margin-inline-start`,
  `padding-block`, `inset-inline-end`, `border-start-end-radius`,
  `text-align: start`) so the layout mirrors itself. Then an RTL stylesheet only
  needs what CSS can't express logically: **gradient angles** and **physical
  `translateX()`** ("forward" is negative X in RTL).
- **`inset-inline-start` resolves against the element's own `dir`.** Putting
  `dir="ltr"` on a positioned box flips which edge it sticks to. Put `dir` on an
  inner wrapper instead.
- **`scrollLeft` is negative in RTL** (0 → −max in modern browsers). Compare
  `Math.abs(scrollLeft)` in carousel code.
- **Arrows and chevrons that mean "next" or "back" have to flip.** Pick the
  icon from the direction rather than hard-coding it.

### 3.2 Mixed-direction text (bidi): the most common visible bug

Persian sentences constantly contain Latin: brand and model names, product
codes, CEFR levels (A1, B2), prices in euros, email addresses, URLs, French or
English words. The Unicode bidi algorithm reorders **neutral characters**
(spaces, dots, slashes, hyphens, `+`, `·`) based on the paragraph's direction,
so:

- «A1 · A2 · B1» inside Persian renders as «B1 · A2 · A1»: the wrong order;
- two Latin words with a plain space between them swap places («Les nombres» →
  «nombres Les»);
- a level code lands on the wrong side of the word it belongs to;
- «A1+» shows as «+A1»;
- an arrow icon after a Latin link text attaches itself to the Persian word
  before it.

**What worked:** wrap every Latin run in its own isolate, e.g.
`<span dir="ltr">…</span>` (or `<bdi>`), after escaping. The reference helper,
`zandi_bidi()`, escapes the string and then wraps runs with this pattern:

```php
// Runs on the ALREADY-ESCAPED string, hence &#039; / &amp; in the chain.
$pattern = '/(?<!&)(\p{Latin}[\p{Latin}0-9]*(?:(?:&#0?39;|&apos;|&amp;|[ \'’·,.\-–—+\/…])+[\p{Latin}0-9]+)*)/u';
$html    = preg_replace( $pattern, '<span dir="ltr" class="latin-run">$1</span>', esc_html( $text ) );
```

Lessons from getting it wrong four times:

- Use `\p{Latin}`, not `[A-Za-z]`, or accented letters split words in half
  («présenter» → «senterépr»).
- A run must **chain across spaces, apostrophes, slashes and dashes**, or
  multi-word phrases become separate islands that swap places.
- The chain must **end on a letter or digit**, so the full stop closing the
  Persian sentence stays outside the run. The side effect is that a trailing
  `+` is left out, so isolate codes like «A1+» as a whole.
- **Glued forms occur in real user text:** «وB1» (conjunction attached), «b1»
  (lowercase), «A1.2» (dotted sub-level). Test with what real users actually
  write.
- **A whole foreign sentence needs `dir="ltr"` on its block element**, not a
  span inside it. The span fixes character order but leaves the line
  right-aligned, hanging off the wrong edge.
- **Where you put the isolate matters.** Put it on the *name* span, not the
  anchor that also holds an icon. Then the icon stays in the RTL context and
  lands on the side the reader expects.
- Apply `nl2br()` **outside** the bidi helper.

Where this applies beyond a language school: product names («گوشی iPhone 15
Pro»), model numbers, sizes («XL»), prices with currency codes, tracking codes,
emails and phone numbers in contact blocks, and user reviews.

### 3.3 Digits

- **Three digit sets exist**: Persian `۰۱۲۳۴۵۶۷۸۹`, Arabic-Indic `٠١٢٣٤٥٦٧٨٩`
  (sent by some keyboards and phones), Latin `0123456789`.
- **Normalise input.** Anything a user types (phone numbers, codes, amounts)
  should be converted to Latin digits before validating or storing it.
- **Localise output deliberately**, in code, where you choose what gets
  converted (`zandi_fa_digits()` is a plain `str_replace`). Prices, counts,
  dates and phone numbers get Persian digits. Codes that are Latin by nature
  (A2, B2, SKUs) don't.
- **Never localise digits with a font feature.** Vazirmatn's `ss01` and the
  "FaNum" variants of commercial Persian fonts (e.g. `PeydaFaNum`) turn *every*
  Latin digit into a Persian one, which corrupts «A2» into «A۲». The reference theme
  switches `ss01` off and uses the non-FaNum font build.
- **Never run digit replacement over HTML.** WooCommerce's Persian plugin
  prints «تومان» as numeric entities (`&#x62A;…`), and a blind replace turned
  them into invalid entities that printed as raw text. Split on tags and
  entities first, then convert only the text in between.
- **Browser-drawn counters can't be reached from PHP.** Use
  `list-style-type: persian` on ordered lists, or they number in Latin.
- **Thousands separator:** the reference project formats numbers with
  `number_format_i18n()` and then localises the digits.
- **Keep admin screens and exports in Latin digits and ISO dates**: a
  spreadsheet can't sort or sum «۲ شهریور ۱۴۰۵», and scripts parsing admin
  numbers break too.

### 3.4 Letters, ZWNJ and comparing Persian text

- **ZWNJ (U+200C, نیم‌فاصله)** is part of correct Persian spelling
  («می‌کنیم», «ثبت‌نام»). «ثبت‌نام» and «ثبت نام» look almost the same and
  compare as different.
- **Arabic ي and ك vs Persian ی and ک.** Many keyboards, plugin translation
  files and old databases use the Arabic forms, and they look the same on screen.
- Before comparing or searching Persian strings, **normalise**: map ي→ی and ك→ک, fold
  ZWNJ and whitespace, and trim punctuation. The reference project needed this
  (`zandi_norm_fa()`) to recognise a plugin's heading by its text.
- If a `@font-face` uses `unicode-range`, **include U+200C–200F**, or ZWNJ
  falls back to another font and Persian words stop joining correctly.

### 3.5 Dates and the calendar

- Iranians use the **Solar Hijri (Jalali, شمسی) calendar**. Show dates in it.
- **PHP's `intl` extension does it without shipping a conversion table:**

  ```php
  $fmt = new IntlDateFormatter(
      'fa_IR@calendar=persian',
      IntlDateFormatter::LONG, IntlDateFormatter::NONE,
      wp_timezone(), IntlDateFormatter::TRADITIONAL
  );
  echo $fmt->format( new DateTimeImmutable( '@' . $timestamp ) );
  ```

  Keep a fallback for hosts without `intl` (the reference project falls back to
  a localised Gregorian date, not a hand-rolled converter).
- **WP-Parsidate** converts the whole WordPress install to Jalali. If you use it
  alongside your own conversion, check that nothing is converted twice.
- Even a copyright year needs this: the reference footer prints the current
  Jalali year.

### 3.6 Persian fonts

- **Self-host.** Google Fonts is blocked in Iran, and a blocked font request
  doesn't fail quickly: it *stalls* rendering.
- **Free and good:** **Vazirmatn** (SIL Open Font License, one variable woff2
  covering weights 100–900, ~110 KB). Free to redistribute in a repo.
- **Commercial fonts** (Peyda, IRANSansX, Yekan Bakh and others, mostly sold
  through fontiran.com) need a licence. The reference project bought Peyda and
  got written confirmation that keeping the files in a **private** repo is
  acceptable. A public repo would be redistributing a paid font, and deleting it
  later doesn't remove it from git history. Ask the vendor before
  **subsetting** a commercial font: subsetting modifies it, and the licence may
  forbid that.
- **Make the paid font optional.** The theme detects whether the licensed
  files exist and otherwise falls back to Vazirmatn with nothing broken.
- **Declare only the weights the CSS uses.** CSS matches a missing weight to
  the nearest one it has, so unused weights are dead `@font-face` blocks.
- **Preload exactly one face**, the one that paints first. Use
  `font-display: swap`.
- **Emoji and symbols can pull in a second webfont.** With 🇫🇷 or ✦ in the copy,
  the browser walked the font stack looking for a glyph and downloaded the
  whole 108 KB fallback webfont, which didn't have it either. Fix: name the
  system emoji fonts *before* the fallback webfont
  (`'Apple Color Emoji','Segoe UI Emoji','Noto Color Emoji'`), and give the
  fallback webfont a `unicode-range` limited to Persian + Latin text, so it
  can't be chosen for symbols.
- **Font stack used:** `'Peyda', <emoji fonts>, 'Vazirmatn', 'IRANSansX',
  'IRANSans', Tahoma, system-ui, sans-serif`.

### 3.7 Typographic details that differ in Persian

- **More leading.** Persian needs more line height than Latin at the same
  size. The reference project uses `line-height: 2` for paragraphs and 2.1 for
  article text.
- **No uppercase, no letter-spacing tricks.** Tracking that flatters Latin
  small caps only muddies Persian.
- **Watch out for sitewide justification.** Persian WordPress plugins commonly
  add `text-align: justify` to everything, because flush edges look tidy in
  Persian. On narrow phones it opens rivers of white space, and **Safari
  stretches letters apart inside Latin runs** («A1» drawn as «A 1»). The
  reference project had to force `text-align: start !important` on text
  elements, then restore every centred component by name. If the plugin is
  fixed at source, delete the override. Better still, find the plugin's
  «چیدمان دوطرفه» (justified alignment) option and switch it off.
- **Code, URLs and French sentences** inside Persian articles get
  `direction: ltr; text-align: left`.

### 3.8 Copy and tone [Choice]

- Choose **formal or conversational** once and keep it everywhere, including
  error messages, button labels and emails/SMS.
- **Keep every user-facing string in one reviewable place**, including labels
  that change state in JavaScript. The reference project puts both labels of a
  two-state button («کپی کن» / «کپی شد») in the markup and lets JS toggle a
  class, so no Persian string lives in a JS file where no filter can reach it.
- **Write microcopy for the local situation.** Checkout says «پولی از حسابت کم
  نشده. اگر مبلغ کسر شده، بانک ظرف ۷۲ ساعت خودش برمی‌گردونه»: the reassurance
  an Iranian buyer is looking for after a failed bank payment.

---

## 4. The Iranian internet [Iran]

There are three directions of traffic, and each is constrained differently.

### 4.1 From the visitor's browser

These are blocked or unreliable for users inside Iran. A blocked request often
**hangs** until it times out rather than failing fast, and a hanging
render-blocking request freezes the page.

| Don't depend on | What the reference project used or recorded instead |
| --- | --- |
| Google Fonts | Self-hosted woff2 (§3.6) |
| Google reCAPTCHA | A **honeypot** field (hidden from people; bots fill it). Other options: Iranian captcha plugins, Akismet, Cloudflare Turnstile if reachable |
| Google Maps embeds | **Neshan (نشان)** or **Balad (بلد)** |
| Google Analytics | Self-hosted **Matomo** |
| Gravatar | Initials drawn in CSS (twenty avatars that never arrive make a screen look broken) |
| WordPress's emoji script (`s.w.org`) | Removed; browsers draw emoji natively |
| Western CDNs for JS/CSS | Serve from your own domain |
| Video platforms with ads (Aparat) | Self-hosted MP4 in the media library with `preload="none"` and a poster (§10.4) |

### 4.2 From an Iranian server outward

- **Telegram's API is filtered from Iranian datacentres.** The reference project
  measured it on 11 September 2026: `cURL error 7, connection refused`.
  Telegram bots for Iranian businesses are therefore usually hosted abroad
  (Germany in this case).
- **WordPress.org** (updates, plugin installs) is sometimes unreachable.
- **Foreign licence servers, update endpoints, font and avatar CDNs and
  analytics** called by plugins during a page load can **hang for the full
  timeout**. Two of those make a ten-second first byte. The reference theme caps
  outbound HTTP timeouts on front-end page views at 3 seconds, while:
  - exempting payment, licence and SMS hosts **by name** (a gateway
    verification that gives up early means a customer paid and the order never
    completes);
  - exempting cart, checkout and account pages entirely;
  - leaving cron, admin, REST and CLI untouched.
  It's a safety net. The real fix is finding the plugin (Query Monitor's HTTP
  panel names it).
- **Test from inside the server, the way the real code will call.** The
  reference project wrote a throwaway mu-plugin that calls `wp_remote_get()`
  against (1) an Iranian host, (2) a neutral foreign host and (3)
  `api.telegram.org` **with a deliberately invalid token**. A 401 from Telegram
  is a *pass*: it proves the packets arrived, and there is no secret to leak.

### 4.3 From abroad inward

- **Iranian hosts often filter foreign traffic.** The reference site answered
  **503 to requests from datacentre IPs**, and from a foreign datacentre the TLS
  handshake stalled and timed out. From outside you can't tell that apart from
  an overloaded server.
- Consequences:
  - **Remote AI agents, CI, and external testers** (PageSpeed Insights,
    GTmetrix, uptime monitors) may be unable to reach the site, or may report
    nonsense.
  - **Measure from inside Iran.** The reference project ships temporary
    diagnostic mu-plugins that the owner uploads, runs as an admin, copies the
    report from, and deletes.
  - **Webhooks from foreign services** may not arrive.

### 4.4 Designs that follow from this

When two systems can't both reach each other, **decide which direction traffic
flows and let the other side keep its own copy.**

The reference podcast subscription is sold on the Iranian site and enforced by
a Telegram bot in Germany. The bot can't call the site (503), and the site
can't call Telegram, but the site *can* reach the bot. So:

- the site **pushes** `user_id → expiry date` to the bot whenever an order
  changes;
- the student introduces themselves **to the bot** through a signed deep link,
  so the bot learns `user_id → telegram_id` and the site never needs to;
- the group link creates a **join request** rather than a membership, and the
  bot approves or declines it from its copy. A leaked link is worthless;
- either side can be down without breaking the other: the bot keeps removing
  expired members while the site is unreachable, and the site keeps selling
  while the bot is down.

### 4.5 Email

**Mail from Iranian infrastructure to Gmail is routinely dropped.** For
anything time-sensitive (login codes, order confirmations), prefer **SMS**. If
you do send email, use an Iranian SMTP provider (some SMS vendors also sell
transactional email), set the SPF/DKIM records they specify, and **watch a test
message arrive in a real Gmail inbox** before relying on it.

---

## 5. Money [Iran]

### 5.1 Payment gateways

- **Unavailable:** Stripe, PayPal, Braintree, Square, Google Pay, Apple Pay,
  Western card networks. Iranian cards (the Shetab / شتاب network) don't work
  abroad either.
- **Two kinds of Iranian gateway:**
  - **Direct bank gateway (درگاه مستقیم):** Saman, Mellat (به‌پرداخت ملت),
    Parsian, Pasargad, Sepehr… Lower fees, money lands directly. Needs a
    **registered company** and **eNamad**, and onboarding takes weeks.
  - **Aggregator (درگاه واسط):** ZarinPal, Zibal, NextPay, Pay.ir,
    AqayePardakht… Sign-up in days, often as a sole trader. A commission is
    charged, and settlement runs on their schedule.
  - The reference project chose **ZarinPal** (an aggregator) because it had no
    company or eNamad at the time. It uses ZarinPal's **official** WooCommerce
    plugin (`zarinpal-woocommerce-payment-gateway`).
- **IDPay: don't install** (closed on WordPress.org, 7 April 2026, for a security issue).
- **Installments (buy now, pay later)**: **SnappPay (اسنپ‌پی)** (roughly 25%
  up front, then three more payments) and **Digipay (دیجی‌پی)**. Both are
  separate merchant contracts. It's a conversion lever for expensive
  purchases, not a prerequisite. The reference owner postponed it.

### 5.2 Toman vs Rial: the factor-of-ten bug

**1 Toman = 10 Rial.** Customers think and read in **Toman**. Most gateway APIs
expect **Rial**. Send a Toman figure to a Rial API and you undercharge ten
times; do the reverse and you overcharge ten times.

- Set the shop currency explicitly. Persian WooCommerce adds تومان, ریال,
  هزار تومان and هزار ریال.
- Read the gateway docs for which unit `amount` takes. Put a comment at every
  conversion point, and **convert in exactly one place**.
- **Don't publish a price in structured data** (JSON-LD `offers`) until you've
  decided the currency. Toman has no ISO 4217 code, and publishing the wrong
  unit is a factor-of-ten error in public. The reference site publishes no price
  in schema.
- **Test with a real, low-value transaction** before launch, including the
  **failed** and **cancelled** paths, not just success. This is described as
  the most common Iranian WooCommerce bug.

### 5.3 Gateway checklist (from the reference project's research doc)

1. Confirm the plugin is currently listed and maintained on WordPress.org, or
   comes directly from the payment provider. Check "last updated" and "tested
   up to".
2. Register the merchant account and get the merchant ID or API key.
3. Store credentials in `wp-config.php` or the options table, **never in git**.
4. Set the currency and confirm the Rial/Toman unit.
5. Whitelist the gateway's callback IPs if the host firewalls them.
6. Test a real small payment end to end, plus the failed and cancelled paths.
7. Check that the order status changes and that the customer is told.
8. If WooCommerce **HPOS** (High-Performance Order Storage) is on, test
   checkout with it. Some users reported gateway incompatibilities.

### 5.4 eNamad (نماد اعتماد الکترونیکی)

The government-issued e-commerce trust seal. It's effectively expected on a
commercial Iranian site, it's required for direct bank gateways, and it
visibly affects trust.

- **Plan time for the application**, and reserve a footer slot for the badge.
- **Domain verification** can be done by setting the page `<title>` to a code
  for one deploy («تایید عنوان»). If your theme sets the homepage title in
  code, a wp-admin setting won't do it, so plan for a temporary code change.
- **The seal markup must be pasted verbatim.** eNamad treats tampering with the
  mark as a legal matter, and its own help page names **WordPress** as a CMS
  that silently rewrites the code and breaks it. Lessons:
  - Keep it in a **PHP template** (a nowdoc string), **never** in a
    post/page/widget/block. Editor content is filtered, and
    `wp_targeted_link_rel()` adds a `rel` attribute that stops the seal
    rendering.
  - Don't add `rel`, don't remove `referrerpolicy='origin'` (eNamad reads the
    referrer to check the domain), keep the non-standard `code` attribute, and
    **don't self-host the image**.
  - **Cache plugins' lazy-load breaks it.** LiteSpeed rewrote `src` to
    `data-src`, so the browser never requested the image and eNamad's crawler
    found no `src`. Fix: add `data-no-lazy="1"` to the `<img>` (honoured by
    LiteSpeed, WP Rocket and Perfmatters), and/or exclude
    `trustseal.enamad.ir` in the cache plugin's lazy-load settings. Also make
    sure no "localize resources" feature copies it onto your server.
  - A seal is issued for **one domain**. A `.com` seal won't render on a `.ir`
    domain.
- **Show a gateway's trust badge only while the gateway is actually enabled.**
  A payment badge on a site that can't take payment costs trust instead of
  building it.

### 5.5 Checkout: ask only for what the product needs [Choice]

WooCommerce's default checkout asks for company, country, province, city,
street, apartment and postcode. For **digital** products, the reference project
cut it to **name + mobile (required) + email (optional)** and prefilled all of
it for signed-in users.

- **Mobile stays required** because it's the link between the account, the
  order and the delivery system (§6.3).
- **Email is optional** because login is phone-only, and a real customer may
  have none on file. WooCommerce's default would block them at the last step.
- **Lock the order's phone to the account's number**, or the licence can't be
  traced back to the student.
- **Force digital products to be virtual** (no shipping) in code, so a product
  can't be saved by accident as a physical item that asks for an address.
- **Quantity is always one, and buying the same course twice is blocked.**
  Two of the same course in a cart is a support ticket, not a sale.

**For a physical-goods shop this reverses.** You need the address block,
Iranian province/city lists (Persian WooCommerce provides them) and a shipping
integration with Iranian carriers. Research the current options; they weren't
needed here.

### 5.6 WooCommerce specifics that bit this project [WordPress]

- **Block checkout vs classic checkout.** New WooCommerce installs create the
  cart and checkout pages as *blocks* (a React form on the Store API), and those
  **ignore `woocommerce_checkout_fields` and every classic hook**. Field
  changes silently did nothing. The fix: swap the block for the classic
  shortcode **at render time** (a `the_content` filter), not by rewriting the
  page in the database, which is guard logic that failed silently. Then dequeue
  the ~200 KB of block-checkout JS nobody uses.
- **One account system, not two.** WooCommerce ships its own `/my-account/`.
  Two login pages on one site is how a customer ends up with two accounts and
  the order under the wrong one. Redirect WooCommerce's account pages to your
  own, but leave alone the ones the payment flow needs: `order-received` (the
  gateway's return URL), `order-pay` (its retry URL), `view-order` and
  `lost-password`. Redirecting either of the first two strands a customer
  mid-payment.
- **Strip "shop furniture" for non-physical products**: reviews (off in two
  places, the tab *and* `comments_open`), shipping, coupons, weights and
  dimensions, related products, sorting, result counts, the "Sale!" flash, and
  gallery zoom (~40 KB of JS for a thumbnail).
- **Dequeue WooCommerce CSS/JS on pages that aren't shop pages.** It loads on
  every page by default.
- **Order Attribution** (WooCommerce 8.5+) loads `sourcebuster.js` on every
  page and writes `sbjs_*` cookies, which can stop a page cache serving cached
  HTML. The reference project turned it off («ووکامرس ← تنظیمات ← پیشرفته ←
  ویژگی‌ها»). The trade-off is that orders show their source as «نامشخص».
- **Persian digits in prices**: use a `wc_price` filter on the front end only,
  keep Latin digits in admin, and mind the entity trap (§3.3).
- **Only "paid" statuses grant access**, and `processing` counts as paid for
  digital goods. Otherwise a paying customer sees nothing until someone
  manually marks the order complete.
- **Mirror slow answers into flat user meta for list screens**, e.g. "which
  courses does this user own", the way WooCommerce keeps `_money_spent`.
  Rebuild the mirror whole on every order status change (refunds included).
  Treat it as derived, never as the record.

### 5.7 The page the customer lands on after the bank

WooCommerce's default "order received" page treats **paid** and **failed** as the
same page with a different sentence. They are different moments:

- **Paid:** the main action is *where is my purchase now* (go to the
  dashboard). The receipt comes second. If delivery is asynchronous (a licence
  generated by a background job), **say it's coming**, or the buyer assumes
  the purchase failed.
- **Failed:** nobody wants an order number. They want to know **whether they
  were charged** and **how to try again** (one button back to the payment URL).

### 5.8 Digital delivery [Choice]

The courses are video on **SpotPlayer (اسپات پلیر)**, an Iranian DRM video
player. Its WooCommerce plugin issues a licence keyed to the buyer's **mobile
number**. That's why the phone number has to be identical across the account,
the order and the licence.

---

## 6. Accounts and identity [Iran] [Universal]

### 6.1 The phone number is the identity

- Iranian users expect **mobile number + SMS code** rather than email +
  password.
- **Normalise every way people type it** into one canonical form, or the same
  person registers twice:

  ```php
  // Persian/Arabic-Indic digits → Latin, strip non-digits,
  // 0098… / 98… / 9… → 09XXXXXXXXX
  $d = str_replace( [ '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩' ],
                    [ '0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9' ], $raw );
  $d = preg_replace( '/\D+/', '', $d );
  if ( 0 === strpos( $d, '00' ) )                          $d = substr( $d, 2 );
  if ( 12 === strlen( $d ) && 0 === strpos( $d, '98' ) )   $d = '0' . substr( $d, 2 );
  if ( 10 === strlen( $d ) && 0 === strpos( $d, '9' ) )    $d = '0' . $d;
  $valid = (bool) preg_match( '/^09\d{9}$/', $d );
  ```

- **Different systems store the phone under different keys** (the OTP plugin's
  own meta key, WooCommerce's `billing_phone`, your own). Write one getter that
  checks all of them in priority order, and **mirror** the result into the keys
  other systems read (`billing_phone`), on registration and again on login to
  repair older accounts.
- **Email optional.** WordPress's `wp_insert_user()` doesn't require one.
- **Don't reveal which numbers are registered.** Use one error message for "no
  such account" and "wrong password".

### 6.2 OTP login [WordPress]

- **SMS providers:** Kavenegar (کاوه‌نگار), MeliPayamak (ملی‌پیامک), SMS.ir,
  IPPanel, نجوا (the reference project's choice; it also sells transactional
  email). Twilio and Western SMS APIs are unavailable.
- **Login plugins:** **Digits** (paid, the reference choice, 100+ SMS
  gateways), plus alternatives recorded in July 2026: *OTP Login With Phone
  Number* (free, but only ~900 installs, which is thin for an auth path),
  *JAY Login & Register*, *miniOrange OTP Verification*. Check what exists now.
- **OTP endpoints must be rate-limited.** That's what CVE-2025-4094 was
  about: six-digit codes without a rate limit can simply be enumerated.
- **Keep a fallback login.** If the paid OTP plugin is deactivated or its
  licence lapses, the login page should fall back to a working form, not a
  blank card.
- **Both auth pages (login and signup) must always come from the same
  system.** The reference site once served the plugin's OTP form on `/login/`
  and the theme's password form on `/register/`. Students signed up with a
  password and then couldn't sign in, because the login form wanted a code.
- **Enabling email alongside phone in Digits turns its form into tabs.** The
  owner rejected that, so login stayed phone-only.

### 6.3 Embedding a plugin's form in your theme [WordPress]

- **Render the plugin's form before `wp_head()`**, e.g. on
  `template_redirect`, and stash the markup. A plugin enqueues its JS/CSS *at
  the moment it is asked for the markup*. Asking from inside a template part
  happens after the head has printed, so the scripts never reach the page. With
  Digits, the symptom was that every step panel («ورود», «عضویت», «تایید شماره
  موبایل») rendered at once, stacked.
- **Don't style plugin markup by class-name substring**
  (`[class*='dig']`, `[class*='tab']`). Class names don't split up the way the
  selector assumes. It hit step panels and containers, and produced overlapping
  panels and a button bleeding out of its card. Two rules that held up: **style
  only real controls** (`input`, `select`, `button`, `a`), and **set no layout
  property on anything the plugin owns.** Colour is safe to apply broadly;
  geometry isn't.
- **To remove duplicated elements from a plugin's output, match by text, not
  by class**, using a DOM parse of the markup string, and **fail open**: on any
  error, return the original markup unchanged. If your cleaner could ever return
  an empty string, the theme would decide no plugin is active and fall back to
  a different login system.
  Notes: `DOMDocument::loadHTML()` assumes ISO-8859-1, so convert Persian to
  numeric entities first. Pass `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD`.
  Normalise Persian before comparing (§3.4).
- **Notices injected later by the plugin's JavaScript** can't be cleaned in
  PHP. The reference project recognised them in JS by *shape* (appeared after
  load, holds text, painted in a colour the theme doesn't use) and styled a
  class it added itself. One notice had shipped at a contrast ratio of 1.15.

### 6.4 Returning people to where they were going

The owner reported the same bug for weeks: pick a course, get asked to sign in,
sign in, and land on the homepage instead of checkout.

- `?redirect_to=` alone can't work when **a plugin renders and processes the
  login form**. The theme's code that reads `redirect_to` never runs.
- What worked: **record the destination three ways**: the `redirect_to`
  parameter on the theme's own links; the **referer** when someone arrives at
  the login page with no parameter (plugin redirects, header links); and **user
  meta** once an account exists. Use a cookie with a short lifetime (30
  minutes: long enough for an SMS to arrive).
- **Reading the address must never use it up.** A getter that cleared the
  address "because it's being honoured" was called while the login page was
  being drawn, so the page wiped its own return address every time. Only the
  code that actually performs the redirect may clear it, on the line before
  `wp_safe_redirect()`.
- **Don't set a cookie on every anonymous page view** just to remember where
  people were. It stops the whole site from being page-cached.
- `add_query_arg()` **does not URL-encode values.** Encode a destination that
  has its own query string with `rawurlencode()`, or everything after the
  first `&` is lost.
- Validate every destination with `wp_validate_redirect()`, so it stays on your
  own domain.
- Check the login plugin's own redirect settings (Digits: «Dynamic Login and
  Signup Redirection»). They win the first redirect.

### 6.5 Other account details

- **Registration is off by default in WordPress**: «تنظیمات ← همگانی ← عضویت».
- **Keep customers out of wp-admin**, but leave `admin-ajax.php` and
  `admin-post.php` open so front-end forms still work.
- **Account pages: `noindex, follow`**, excluded from the page cache, and each
  with its own `<title>` (§11.3).
- **Honeypot instead of reCAPTCHA** on any public form.
- **Logout links need a nonce**, or any other site can sign your users out.

---

## 7. Messaging apps and support channels [Iran] [Universal]

- Iranian businesses mostly talk to customers over messaging apps. Which apps
  are filtered or unblocked in Iran changes with policy. **Telegram and
  Instagram have long been filtered yet are widely used**, domestic messengers
  exist (Bale, Eitaa, Rubika, Soroush…), and WhatsApp's status has changed over
  the years. **Ask the client which channel their customers actually use.**
- **Define every channel in one place.** In the reference project, no copy
  names a channel. Every «بپرس» reads «از صفحه تماس بپرس» and links to
  `/contact/`, and **one function holds every URL and handle**. Moving support
  from Telegram to WhatsApp is one edit, where it used to be forty strings in
  sixteen files. Enforce it with a check, e.g. `grep -rn "t\.me/"` must only
  match the one file.
- **Keep different jobs on different channels, and label them.** The reference
  site has three Telegram destinations that aren't interchangeable:
  **support** (a human answers), the **teacher's personal account** (shown
  only to paying students inside their dashboard), and the public
  **broadcast channel** (content, not support). For a while the whole site
  pointed at the broadcast channel, so every «پشتیبانی» link landed somewhere
  nobody replies.
- **Tell users which app a button opens**, where it isn't obvious.
- **Bots:** Telegram's API is unreachable from Iranian servers (§4.2). Host
  the bot abroad and design the data flow around one-way reachability (§4.4).
  Two security details from the reference bot: **check who pressed an inline
  button** (forwarded messages keep their buttons), and name `chat_member` in
  `allowed_updates` or Telegram never tells you someone left.

---

## 8. Performance on Iranian hosting [Iran] [WordPress]

### 8.1 Measure first

The reference theme was audited **four times** for front-end causes. Each audit
found little, recommended a page cache, and the site stayed slow. **Time to
first byte is spent on the server** (PHP boot, plugins, database) and no
stylesheet can change it.

- Get a measurement from **inside** (§4.3). The reference project's
  diagnostic mu-plugin records timestamps at WordPress milestones and prints
  the gaps between them:

  | Largest gap | Likely cause |
  | --- | --- |
  | request start → `muplugins_loaded` | PHP compiling code on each request → turn on **OPcache** |
  | `plugins_loaded` → `init` | Plugins doing work on every request → audit plugins |
  | `init` → `wp` | Database, or **WP-Cron** running on the page load |
  | any outbound HTTP | Waiting on a foreign host (§4.2) |
  | autoloaded options > 1 MB | Every request reads them all → clean `wp_options` |

### 8.2 Server-side, roughly in order of impact

1. **A page cache, and proof that it is serving.** On LiteSpeed servers
   (common in Iran) use **LiteSpeed Cache**. On Apache/nginx use **WP Super
   Cache**. Never run two cache plugins. Check in a private window: the second
   load should show `x-litespeed-cache: hit`. A cache plugin that shows a green
   status page and caches nothing is the most likely reason a site stays slow.
2. **Take WP-Cron off page loads:** `define('DISABLE_WP_CRON', true)` **plus** a
   real cron job calling `wp-cron.php`. The constant alone silently stops every
   scheduled task.
3. **OPcache on** (cPanel → Select PHP Version → Extensions).
4. **An object cache (Redis/Memcached)** only if the host offers it. A
   disk-based object cache is usually slower than none.
5. **Clean autoloaded options** (back up first; delete rows only for plugins
   that are no longer installed; expired transients are always safe to delete).
6. **Cache exclusions:** login, register, dashboard, anything personal (a cached
   result page hands one person's data to the next). **Keep the list tight.**
   An exclusion like `/` quietly switches caching off for the homepage.
7. **Cookies that defeat the cache:** `sbjs_*` (WooCommerce order
   attribution), anything a plugin sets on the first page view. A signed-out
   homepage visitor should have almost no cookies.
8. **The QUIC.cloud trap (LiteSpeed Cache).** Critical CSS, Unique CSS,
   low-quality image placeholders and image optimisation are generated **on
   QUIC.cloud's foreign servers**. From Iran the queue may never finish, and
   with "load CSS asynchronously" on, pages then flash unstyled, which looks
   like the cache plugin made things *worse*. Keep those off unless the queue
   is visibly finishing. Keep cache, minify, combine and browser cache (all
   local) on.
9. **Compression + long cache headers** for static assets (`mod_deflate`,
   `mod_expires`), which is safe once asset URLs are versioned (below).

### 8.3 Theme-side (worth doing, but it won't fix TTFB)

- **Version each asset by its own modification time**, not by a theme version
  constant nobody remembers to bump. With a constant that never changed, every
  stylesheet stayed at `?ver=1.1.0` and browsers, the host cache and any CDN
  kept serving old CSS while PHP templates updated instantly. Deploys looked
  half-applied. The same goes for favicons, which browsers cache even harder.
- **Remove what WordPress loads that you don't use**, each behind its own
  filter so it can be turned back on: the emoji script and its DNS prefetch to
  `s.w.org`; block-library CSS on pages without blocks (~90 KB); oEmbed; head
  clutter (RSD, WLW, the generator tag that publishes your WP version); the
  front-end Heartbeat (throttle it).
- **Load a plugin's assets only where the plugin is used.** Match the plugin
  by its directory in the asset URL, never by handle or class substrings.
- **`defer` the one script.** When nothing depends on JS, this is safe by
  construction.
- **Nothing above the fold waits for JavaScript.** A scroll-reveal animation
  (opacity 0 until JS adds a class) on the hero left the headline invisible
  until the script ran, which is exactly the window Largest Contentful Paint is
  measured in.
- **Responsive images for theme files.** `wp_get_attachment_image()` only adds a
  `srcset` for media-library images. Theme images need width variants generated
  once and committed (scaled, never cropped). Keep `sizes` next to `srcset` and
  change them together: a wrong `sizes` is worse than no `srcset`.
- **Stylesheets scoped per page type** (course pages, account pages, shop,
  etc.), with no overlap.

---

## 9. SEO for Persian sites [Universal] [WordPress]

- **`og:locale` = `fa_IR`**, a canonical on every page, a description.
- **Titles people actually search for.** The reference title is «دوره پایه A1
  — آموزش زبان فرانسه | آکادمی زندی». The `<h1>` stays in the brand's voice;
  the `<title>` carries the search words.
- **Every custom route needs a `document_title_parts` filter.** On a virtual
  route, none of WordPress's title branches match, so the title falls back to
  the site name alone. Login, register and dashboard all shipped as
  `<title>site name</title>`.
- **If an SEO plugin gets installed, step aside.** If the theme prints
  canonical/OG/JSON-LD and someone later installs Yoast or Rank Math (both
  popular in Iran), every page gets **two canonicals**, which is worse than
  none. Check for an active SEO plugin at the top of every head function.
  *Exception:* `noindex` on private pages (a result URL holding one person's
  score) prints regardless. Two robots tags are harmless.
- **`noindex` for login, register, dashboard, unfinished pages and personal
  results.**
- **Structured data must say only what the page says.** JSON-LD is exactly
  where an invented rating or price ships unnoticed, because nobody reads it. No
  `aggregateRating` without real rating data; no `offers` without a settled
  currency (§5.2). For courses, **`inLanguage` is the language the material is
  taught in (fa) and `teaches` is the subject**; mixing them up tells Google
  the videos are in French. For self-hosted video, `VideoObject` needs
  `uploadDate` and `thumbnailUrl`; don't claim an `embedUrl` that doesn't exist,
  and don't emit a `VideoObject` for a video that isn't uploaded yet.
- **Virtual routes aren't in WordPress's sitemap.** Register a sitemap provider
  for them.
- **Retired pages: 301, not 404**, and move any structured data that described
  them. A `FAQPage` whose URL is a redirect is a structured-data error that can
  cost the rich result site-wide.
- **Watch the «از موتورهای جستجو بخواهید این سایت را ایندکس نکنند»
  checkbox** («تنظیمات ← خواندن»). It's often left on after development, and no
  code deploy can see it. The reference theme shows an admin notice on every
  screen while it's on.
- **Search Console file verification** when the repo is only the theme: a
  committed file would be served from `/wp-content/themes/…`, where Google
  never looks. The reference project answers the exact root path from PHP on
  `init`, from a whitelist, as `text/plain`. It avoids the rewrite table on
  purpose, so a rules flush can't revoke ownership.
- **Social share images:** Telegram and Instagram previews are a big deal for
  Iranian audiences. Use real artwork at an acceptable size (course covers at
  800×500 worked). A tall portrait gets cropped to landscape badly, so leave it
  out rather than serve it.

---

## 10. Design and content principles

### 10.1 Visual design [Choice]

The reference site deliberately looks unlike a typical Iranian
language-institute site: minimal, lots of white space, one dominant brand
colour (navy `#1B365D`, ~95% of the visual weight), one accent (French red
`#C8102E`) used only at small scale (it is "only ever the dot", taken from the
logo), 16/20px radii, navy-tinted shadows, subtle motion. No stock photos, no
flag or landmark clichés. The French feel comes from geometry and language.

What transferred as general lessons:

- **One palette, one header and footer across every section of the site.**
  The course pages once had their own cream-and-red palette and chrome; the
  owner said opening a course "felt like leaving for a different website", and
  it was merged back. Later, a purple podcast page was rejected for the same
  reason and rebuilt with purple as an **accent only**, taken from the
  podcast's own cover art.
- **Accent colours need a written boundary** (where they may appear and
  where they may not), or they spread.
- **A secondary element must stay visually lighter than the main call to
  action.** A "bonus" card under the buy button went through three designs: too
  loud (read as a coupon), too quiet (read as a detail and skipped), then right.
  The fix was structural: four pieces of information at four sizes, instead
  of one sentence.
- **Don't crop the owner's photographs.** She framed them; the layout adapts.
- **Mobile first.** The reference project checks every change at 390px and
  1440px, and at 320–1920px for overflow.

For your client, the local norm may be exactly right: dense, colourful
e-commerce layouts are familiar to Iranian shoppers. Decide with them.

### 10.2 Facts only [Universal]

- **Never invent** statistics, testimonials, instructors, addresses, phone
  numbers or ratings to fill a layout. An early draft of the reference site did
  exactly that and shipped **the wrong first name for a real person**.
- **Missing data gets an empty state and a TODO**, not a plausible placeholder.
  An icon linking nowhere is worse than no icon.
- **Reviews are verbatim**: the customer's spelling, emoji and line breaks. No
  star rating unless the customer actually gave one ("a 5/5 nobody gave is an
  invented statistic"). The reference project pins every quote with a content
  hash in a test, so nobody can quietly reword a real person.
- **The order of reviews is sometimes a decision the owner made.** Record it.

### 10.3 Progressive enhancement and accessibility [Universal]

- **Everything readable without JavaScript.** `<html class="no-js">`, swapped to
  `js` by an inline script before first paint. CSS reveals everything under
  `.no-js`.
- **Controls that only work with JS ship `hidden`** and are revealed by the
  script that makes them work. Note: **`[hidden]` doesn't hide an element whose
  CSS sets `display`** (e.g. `.btn { display: inline-flex }`), so add an author
  rule to back it up.
- **Don't use scroll-reveal animations on login forms or instructions.** If the
  script fails, they stay invisible.
- One `<h1>`, landmarks, a skip link, real `<label>`s, visible focus; accordions
  built from real buttons with `aria-expanded`.
- **Repeated buttons need distinct accessible names** (a hidden suffix naming
  the item), or a screen reader lists ten identical «ادامه مطلب».
- **Autoplay respects `prefers-reduced-motion`**, never starts without
  overflow, and stops for good once the user interacts.

### 10.4 Media [Iran]

- **Self-hosted video over ad-supported Iranian platforms.** Aparat has no
  publisher-side switch to turn ads off, so a visitor deciding whether to trust
  the business would watch someone else's ad on the sales page. Self-hosting
  also keeps the video rich result on your page.
  - Ad-free Iranian video platforms exist (ArvanCloud VOD, Negavid, Kavimo
    were noted) but are priced for libraries.
  - **`preload="none"` + a poster** loads zero bytes of video until play is
    pressed. **No autoplay:** it's hostile on metered Iranian mobile data.
  - **Big binaries go in the media library, not git** (git keeps every version
    forever, and swapping a clip shouldn't need a deploy).
- **Finding an upload "by the name the owner typed" is harder than it looks.**
  WordPress's `post_name` isn't the file name. It gets uniquified (a copy in the
  trash turns `intro` into `intro-2`) and doesn't change when the title is
  edited. The reference lookup tries slug → exact title → an anchored regex on
  the attached file path, caches misses for a day, and **clears the cache on
  upload**. Without that, the page kept saying «به‌زودی» (coming soon) for a day
  after the upload, which looked exactly like a broken feature.

---

## 11. Engineering practices that paid off

### 11.1 General

- **Research before implementing** anything involving Iranian services or
  plugins. Confirm the plugin exists, is maintained and hasn't been closed.
- **Verify before claiming done.** The reference project renders pages through
  a WordPress stub and compares screenshots at 390px and 1440px.
- **Say what's unverified.** If something needs a live install, a merchant
  account or real API keys to check, say so plainly.
- **A check nobody can run isn't a check.** Three "verification scripts" that
  earlier docs cited turned out never to have been committed: they were
  scratch files that disappeared with the session that wrote them. Put checks in
  `tests/`.
- **Never commit secrets.** Keys go in `wp-config.php` constants (e.g. a
  shared secret for the bot bridge) or the options table. When the secret is
  missing, the feature should **say so in wp-admin** instead of failing quietly.
- **Record decisions with dates and reasons** in the repo's agent-instructions
  file. It's the only reason later agents don't re-ask the owner or undo a
  deliberate choice.

### 11.2 Data and security

- **Sign anything the client sends back that you rely on.** The placement test
  shuffles answer options and sends a position→option map with the form. It is
  signed with `wp_hash()`, because unsigned, a visitor could rewrite the map and
  score 30/30. **Score on the server**: the answer key never reaches the browser.
- **Protect data files in the theme folder.** `.htaccess` deny works on Apache
  and LiteSpeed, **not on nginx**, where you need a `location` block. On shared
  hosting where the only writable place is the web root, begin data files with
  `<?php exit; ?>`.
- **CSV exports for Persian users:** write a **UTF-8 BOM** (`\xEF\xBB\xBF`), or
  Excel shows mojibake. Use Latin digits and ISO dates. **Defuse formula
  injection** by prefixing cells that start with `=`, `+`, `-` or `@` with `'`.
  Names are typed by the people in the file.
- **Sensitive admin screens:** check `manage_options` (not merely "is staff")
  in the menu, in the render callback **and** in the export handler.
- **Don't sort a user list on user meta** in `WP_User_Query`. It becomes an
  INNER JOIN and silently drops every user without that meta: the rows you
  most want to see.

### 11.3 WordPress routing (if you serve pages from code)

A custom route is only reliable when it's declared in **all** of these:

1. `add_rewrite_rule()`: the fast path.
2. A `query_vars` filter.
3. A `parse_request` fallback that reads the path directly, so the route
   survives another plugin flushing the rewrite rules.
4. **A URL helper that falls back to a query string** (`/?my_route=x`) when
   permalinks are «ساده». With plain permalinks, a request for `/login/` gets
   the web server's own 404 and **PHP never runs**. Also show an admin notice
   with the exact fix («تنظیمات ← پیوندهای یکتا ← نام نوشته»).
5. A `document_title_parts` filter (§9).
6. **Flush rewrite rules only when a stored version number changes**, never on
   every load. `after_switch_theme` alone misses updates deployed over git or
   FTP.
7. Keep `redirect_canonical` away from virtual pages, set a 200 status, and
   ship a `404.php`: without it an unknown slug returned a **completely empty
   document**.
8. Let a real WordPress Page at the same slug win, except for routes that are
   applications (login, checkout-like flows), which a stray Page must never
   shadow.

### 11.4 Don't fight plugins blindly

- Before overriding a plugin's CSS, **look for the plugin's own setting**.
- If you must override, scope it narrowly and **write down why**, including
  "delete this if the plugin is fixed".
- **Test the site with each key plugin deactivated.** The theme must still
  render if WooCommerce or the OTP plugin is off (`class_exists()` /
  `function_exists()` guards everywhere).

---

## 12. Working with a non-technical owner

- **Give wp-admin steps as Persian click paths**, the way the admin shows them:
  «افزونه‌ها ← افزودن ← بارگذاری افزونه». A list of common ones is in §15.
- **Make the owner's jobs one step.** Uploading a file whose name follows a
  convention (`course-a1-intro.mp4`) is the whole publishing process for a
  video; the theme finds it.
- **Admin notices for silent failures:** unlinked products, plain permalinks,
  "discourage search engines" on, a missing API secret.
- **Temporary diagnostic tools she can run herself** (mu-plugins with
  instructions in Persian and a "delete me when done" step), because a remote
  agent often can't reach the site (§4.3).
- **Build unannounced features behind `noindex` and no links**, and give one
  documented switch to launch them. The owner reviews at the URL first.
- **When a fix needs two things (code + a setting), say both.**
- **Branches:** the reference owner looks at `main`. Work pushed only to a
  feature branch looked, from her side, identical to nothing having been done.
  Keep what the client looks at up to date, and say plainly when it isn't.

---

## 13. Mapping this to other kinds of projects

A starting point for your own judgement. It isn't a verdict.

| Project type | Probably transfers | Probably doesn't, or needs rethinking | Extra questions to ask |
| --- | --- | --- | --- |
| **Online shop, physical goods** (Iran audience) | §3 in full; §4; gateways, Toman/Rial, eNamad (§5.1–5.4); phone identity; performance; SEO; facts-only | Cutting checkout to name + phone (you need addresses); "quantity always one"; "no shop furniture" (reviews, related products and sorting are useful here) | Shipping carriers and costs? Stock sync with a physical store or accounting software? Returns? Installments (SnappPay/Digipay)? Catalogue size (performance, search in Persian with ی/ک normalisation)? |
| **Service business** (clinic, salon, consultancy) with booking | §3; phone identity + SMS reminders; §4; honeypot forms; maps via Neshan/Balad; facts-only (credentials, licences) | Video delivery; digital licensing | Deposits online or pay on site? Jalali calendar in the booking UI? Reminders by SMS? Legal/medical claims limits? |
| **Online courses / digital products** (closest to the reference) | Almost everything | Specific platform choices (SpotPlayer, Telegram groups) may differ | DRM needed? Device limits? Where do students get support? |
| **Brochure / portfolio site** | §3; self-hosted fonts; performance; SEO; one palette; facts-only | Payments, accounts, WooCommerce | Will anyone edit it? If not, is WordPress needed at all (§2.4)? |
| **Blog / news / magazine** | §3 (bidi and ZWNJ matter a lot in long text), Jalali dates, SEO, performance, caching | Commerce, OTP login | Editorial workflow and roles? Comment spam without reCAPTCHA? Justification plugin temptations (§3.7)? |
| **Diaspora business** (users abroad, company abroad) | §3 in full; tone; facts-only; design | Most of §4 and §5: Western payments and hosting become available | Do they *also* serve users inside Iran? If yes, you're back to §4–5 for that part |
| **Web app / SaaS for Iranian users** | §3; phone identity; §4 (hosting, blocked services, measuring from inside) | "No build step", classic theme patterns | Is WordPress the right base at all (§2.4)? |

---

## 14. Pre-launch questions (a checklist to think with)

- [ ] Do we know where users and servers are, and did we check each third-party
      service against that?
- [ ] Does any page request Google Fonts, reCAPTCHA, Maps, Analytics, Gravatar
      or a foreign CDN? (Check the rendered page's network tab, not the source
      code.)
- [ ] Mixed Latin/Persian text reviewed on screen: codes, brand names, prices,
      phone numbers, user-written text?
- [ ] Digits: input normalised; output localised deliberately; no FaNum or
      `ss01`; ordered lists use `persian`?
- [ ] Jalali dates everywhere a user sees a date?
- [ ] Fonts self-hosted and licensed; the licence allows the repo's visibility?
- [ ] Gateway tested with a real small payment, including failed and cancelled;
      Toman/Rial unit confirmed in writing?
- [ ] eNamad seal renders (check with the cache plugin's lazy-load on)?
- [ ] Phone normalisation + one canonical phone across account, order and
      delivery?
- [ ] OTP rate-limited; fallback login path; both auth pages from one system?
- [ ] After signing in, users return to where they were going?
- [ ] Support channel defined in one place?
- [ ] Page cache proven to serve (a hit on the second load); personal pages
      excluded?
- [ ] «از موتورهای جستجو بخواهید…» unchecked; private pages `noindex`; one
      canonical per page?
- [ ] No invented facts anywhere, including JSON-LD?
- [ ] Works with JavaScript off? Works with each key plugin deactivated?
- [ ] Secrets only in `wp-config.php`?
- [ ] Decisions and their dates written down for the next agent?

---

## 15. Glossary and wp-admin paths

### Terms

| Persian | Meaning |
| --- | --- |
| تومان / ریال | Toman / Rial. 1 Toman = 10 Rial. Prices are shown in Toman; gateways often take Rial. |
| درگاه پرداخت | Payment gateway |
| درگاه مستقیم / درگاه واسط | Direct bank gateway / aggregator gateway |
| شاپرک | Shaparak, the national card-payment switch that Iranian gateways run through |
| شتاب | Shetab, the interbank card network |
| نماد اعتماد الکترونیکی (اینماد) | eNamad, the government e-commerce trust seal |
| نیم‌فاصله | ZWNJ, zero-width non-joiner (U+200C) |
| تقویم شمسی / جلالی | Solar Hijri / Jalali calendar |
| پیشخوان | The wp-admin dashboard |
| پیوندهای یکتا | Permalinks |
| افزونه / پوسته | Plugin / theme |
| رسمی / محاوره‌ای | Formal / conversational register |
| کد یکبار مصرف | One-time code (OTP) |
| ووکامرس فارسی | Persian WooCommerce plugin |
| پارسی دیت | WP-Parsidate plugin |

### wp-admin click paths used in the reference project

| Task | Path |
| --- | --- |
| Activate a theme | نمایش ← پوسته‌ها |
| Set a static front page | تنظیمات ← خواندن ← صفحه ایستا |
| Stop blocking search engines | تنظیمات ← خواندن ← «از موتورهای جستجو بخواهید…» (uncheck) |
| Pretty URLs | تنظیمات ← پیوندهای یکتا ← «نام نوشته» ← ذخیره تغییرات |
| Allow registration | تنظیمات ← همگانی ← عضویت |
| Upload a plugin zip | افزونه‌ها ← افزودن ← بارگذاری افزونه |
| List/remove plugins | افزونه‌ها ← افزونه‌های نصب‌شده |
| Upload media | رسانه ← افزودن |
| Site health / response time | ابزارها ← سلامت سایت |
| Site icon | نمایش ← سفارشی‌سازی ← هویت سایت |
| Inspect a user's meta | کاربران ← ویرایش کاربر |
| Turn off order attribution | ووکامرس ← تنظیمات ← پیشرفته ← ویژگی‌ها |

---

## 16. Snapshot of dated facts (re-verify before use)

| Fact | As recorded | Date |
| --- | --- | --- |
| Vercel blocks Iranian IPs | Reason Next.js/Vercel was dropped | 29 Jul 2026 |
| ZarinPal official WooCommerce plugin | v5.1.1, ~50k installs, fixed a checkout nonce bypass | 29 Jul 2026 |
| IDPay WooCommerce plugin | Closed on WordPress.org for a security issue | 7 Apr 2026 |
| Persian WooCommerce | v10.0.4, 100k+ installs, maintained | 29 Jul 2026 |
| WP-Parsidate | v6.2.1, 100k+ installs, maintained | 29 Jul 2026 |
| Digits | CVE-2025-4094 (CVSS 9.8) in 8.4.6.x, fixed 8.4.6.1; the site ran 9.x | 30 Jul 2026 |
| OTP Login With Phone Number | v1.8.71, ~900 installs | 30 Jul 2026 |
| FluentSMTP | Free, 600k+ installs, generic SMTP | 30 Jul 2026 |
| LiteSpeed Cache | Free, 7M+ installs; CCSS/UCSS run on QUIC.cloud | Aug 2026 |
| Telegram API from an Iranian datacentre | Connection refused (cURL error 7) | 11 Sep 2026 |
| Iranian site from a foreign datacentre | 503 / TLS stall | 2 & 11 Sep 2026 |
| WooCommerce order attribution | Added in 8.5; `sbjs_*` cookies on every page | 2 Sep 2026 |
| Aparat | No publisher-side ad switch; a 2025 pre-roll removal was reported as temporary and reversed | 21 Aug 2026 |

---

*Source: the `zandiacademy` WordPress theme repository, in particular its
`CLAUDE.md`, `README.md`, `docs/wordpress-iran-stack.md`,
`docs/performance.md`, and the reasoning recorded in code comments throughout
`functions.php` and `inc/`. Function names quoted here (`zandi_*`) exist in
that repository and are given so an agent with access to it can read the full
implementation.*
