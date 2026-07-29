# آکادمی زندی — Homepage

Homepage for a premium Persian-language academy that teaches **French only**.
React + Tailwind CSS, RTL throughout, built as a component library rather than a
single page so the remaining pages can reuse it as-is.

---

## Getting started

```bash
npm install
npm run dev      # dev server
npm run build    # production bundle → dist/
npm run preview  # serve the built bundle
npm run lint     # ESLint (react, hooks, jsx-a11y)
```

## Stack

| Concern    | Choice                                                       |
| ---------- | ------------------------------------------------------------ |
| Framework  | React 18                                                     |
| Styling    | Tailwind CSS 3 (tokens in `tailwind.config.js`)              |
| Motion     | Framer Motion                                                |
| Build      | Vite (`base: './'` — relative asset URLs for WordPress)      |
| Typography | Vazirmatn variable, self-hosted; IRANSansX as fallback       |
| Icons      | In-repo inline SVG set (`components/ui/Icon.jsx`)            |

No page builder, no UI kit, no icon package, no image dependencies.

---

## Folder structure

```
src/
├── assets/fonts/          Vazirmatn variable woff2 (self-hosted)
├── components/
│   ├── layout/            Navbar, Footer, Logo
│   ├── sections/          One file per homepage section
│   └── ui/                The design system — every primitive lives here
├── data/content.js        Every Persian string on the page
├── hooks/                 useScrolled, useScrollSpy
├── lib/                   cn (class join), motion variants, digit formatting
├── pages/Home.jsx         Section ordering, nothing else
└── styles/index.css       Tailwind layers, @font-face, base styles
```

**Rule of thumb:** sections compose primitives and never re-implement them. If a
section needs a new visual pattern, it goes in `components/ui/` first.

---

## Design system

### Colour

Navy is the brand. Red is an accent used at small scale only — eyebrow badges,
the tricolore mark, one focal element per view. It is never a surface colour.

| Token          | Value     | Use                                  |
| -------------- | --------- | ------------------------------------ |
| `navy-700`     | `#1B365D` | Primary brand, headings, primary CTA |
| `navy-800/900` | darker    | Dark sections, footer                |
| `navy-50/100`  | tints     | Icon tiles, hairline borders         |
| `rouge-500`    | `#C8102E` | Accent only                          |
| `mist`         | `#F4F6F8` | Alternating section background       |
| `ink`          | `#111111` | Body text                            |
| `success`      | `#16A34A` | Confirmations, progress              |

### Type

Vazirmatn variable (100–900) in a single 111 KB woff2, `font-display: swap`.

> **Note:** Vazirmatn's `ss01` feature rewrites Latin digits as Persian ones,
> which corrupts CEFR codes such as `A2`/`B2`. It is deliberately left off in
> `styles/index.css`. Localise digits explicitly with `toPersianDigits()` from
> `lib/format.js` instead.

Display sizes (`display-sm/md/lg`) are fluid `clamp()` values, so headings scale
continuously instead of stepping at breakpoints.

### Shape and depth

Radii: `soft` 16px, `card` 20px, `pill` fully round. Shadows run
`soft → card → lift`, all tinted navy rather than neutral grey.

### Motion

Two gestures only, both defined in `lib/motion.js`: a 20px fade-up and a 2%
fade-scale, on one shared easing curve. `Reveal` / `RevealGroup` / `RevealItem`
wrap them for scroll entrances. Every animated component checks
`useReducedMotion()` and renders a plain element when motion is reduced; the
stylesheet also neutralises transitions under `prefers-reduced-motion`.

---

## RTL

`<html dir="rtl" lang="fa">` and direction-aware utilities (`ps/pe`, `ms/me`,
`start/end`) throughout — no physical `left`/`right` in layout code.

Two things to know when editing:

- **`start-*` resolves against the element's own `dir`.** Putting `dir="ltr"` on
  a positioned box flips which edge it anchors to. Scope `dir` to an inner
  wrapper, as the tricolore marks do.
- **`scrollLeft` is negative in RTL** (0 → −max). `Carousel` compares
  `Math.abs(scrollLeft)` so the same bound checks work in both directions.

Latin runs (email, phone, `Bonjour !`, the CEFR ladder) are wrapped in
`dir="ltr"` so they read correctly inside Persian copy.

---

## Accessibility

- Landmarks: `header` / `main` / `section[aria-labelledby]` / `footer`, one `h1`.
- Skip link to `#main` as the first focusable element.
- One focus-visible treatment (`:focus-visible` ring) for every control.
- Accordion: real `<button>` headers with `aria-expanded` / `aria-controls`,
  panels as labelled regions.
- Carousel: native scroll-snap, so touch, trackpad and keyboard all work; the
  region is focusable and the arrow buttons carry `aria-label`s.
- Star ratings announce once as text; the stars themselves are `aria-hidden`.
- Every form field has a real `<label>` — placeholders never stand in for one.

Verified at 320 / 390 / 768 / 1024 / 1440 / 1920 px: no horizontal overflow,
no console errors, no unnamed controls.

---

## Performance

- One self-hosted variable font; no webfont CDN round-trip.
- Zero raster images — the hero panel, course thumbnails and logo are inline SVG
  and CSS gradients, so they cost no requests and stay sharp at any density.
- `react`/`react-dom` and `framer-motion` are split into separate chunks for
  long-term caching.
- Course and avatar images, once real ones land, already carry `loading="lazy"`
  and `decoding="async"`, and their aspect ratios are reserved to avoid layout
  shift.

Production bundle: ~103 KB gzipped JS + 7 KB CSS + 111 KB font.

---

## WordPress integration

The build is deliberately framework-agnostic on the output side.

**1. Build with relative asset paths.** `vite.config.js` sets `base: './'`, so
`dist/assets/*` can live anywhere under the theme directory.

**2. Enqueue the bundle from the theme:**

```php
function zandi_enqueue_homepage() {
  if ( ! is_front_page() ) {
    return;
  }

  $dist = get_template_directory_uri() . '/dist/assets';

  wp_enqueue_style( 'zandi-app', "$dist/style.css", [], '1.0.0' );
  wp_enqueue_script( 'zandi-app', "$dist/index.js", [], '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'zandi_enqueue_homepage' );
```

Vite hashes filenames; read `dist/.vite/manifest.json` (enable
`build.manifest`) to resolve them, or turn hashing off in `rollupOptions.output`
if you would rather version by query string.

**3. Provide the mount point** in `front-page.php`:

```php
<div id="root"></div>
```

**4. Keep `dir` and `lang` on the document** — in the theme's header template:

```php
<html <?php language_attributes(); ?> dir="rtl">
```

**5. Wire the content.** All copy lives in `src/data/content.js` and nothing else
reads from a CMS, so there is exactly one file to replace. Either:

- **Build time:** generate `content.js` from the WP REST API before `npm run build`, or
- **Run time:** have `front-page.php` print
  `window.__ZANDI__ = <?php echo wp_json_encode( $fields ); ?>;` and have
  `content.js` fall back to its current values when that global is absent.

**6. Connect the form.** `FinalCta.jsx` has a single `handleSubmit` that
currently just confirms locally — point it at `admin-ajax.php`, a REST route or
Contact Form 7 there. It is the only network seam in the codebase.

---

## Not included yet

Homepage only, per scope. The nav links to `#courses`, `#teachers`, `#about`,
`#faq` and `#contact`, which are on-page anchors today and become routes when
those pages exist. The teachers band was added because the specified navigation
includes «اساتید» and that link would otherwise dead-end.

Course thumbnails and instructor photos render considered placeholders
(engraved gradient panels, initials avatars). Pass `src` to `Thumbnail` or
`Avatar` when real photography arrives — no layout changes needed.
