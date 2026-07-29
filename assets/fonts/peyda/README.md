# Peyda — licensed font, not committed

**The font files are excluded from git on purpose. Read this before "fixing" that.**

Peyda (فونت پیدا) is commercial software, designed by Seyyed Nasser
Khademtabar and sold exclusively through
[fontiran.com](https://fontiran.com/fonts/peyda). The licence grants use, not
redistribution.

`tahavalikhani/zandiacademy` is a **public** GitHub repository. Committing the
`.woff2` files would publish a paid font to anyone who visits the repo — that is
redistribution, and it is exactly what the seller warns against. The
`.gitignore` in this folder blocks the font formats so it cannot happen by
accident.

## Which build this site uses

The **Font Family** web build — `PeydaWeb-*.woff2` from
`02 Web Font/Font Family WebFont/woff2/`.

Two variants in the package are deliberately **not** used:

- **`PeydaFaNum`** substitutes Persian digits for Latin ones *inside the font*.
  That would render CEFR codes as `A۱`, `B۲` — the exact bug this theme
  disables Vazirmatn's `ss01` to avoid. Digits are localised in PHP, by
  `zandi_fa_digits()`, where it can be targeted.
- **`PeydaNoEn`** has no Latin glyphs, so `Bonjour`, `A1` and `€` would drop to
  a fallback face mid-sentence.

## Installing (and deploying)

Copy these five files into this folder:

```
PeydaWeb-ExtraLight.woff2    → 200
PeydaWeb-Regular.woff2       → 400
PeydaWeb-SemiBold.woff2      → 600
PeydaWeb-Bold.woff2          → 700
PeydaWeb-Black.woff2         → 900
```

The desktop naming (`Peyda-Regular.woff2` …) is accepted too, as is a variable
`Peyda-Variable.woff2`, which takes priority if present.

Nothing else to configure. `zandi_peyda_files()` detects them, `wp_head` emits
the `@font-face` rules and overrides `--font-persian`, and the preload follows
whichever face is live. With no files present the site serves Vazirmatn and
emits no Peyda CSS at all.

> **Deploying from git?** These files will not be in the checkout. Copy them
> onto the server as a deploy step (rsync/SFTP), or the site silently falls back
> to Vazirmatn. That fallback is by design — nothing breaks, it just is not
> Peyda.

## Turning it off

```php
add_filter( 'zandi_use_peyda', '__return_false' );
```

## If you want them committed anyway

Only with a licence that permits it, and preferably after making the repository
private. Then delete the `.gitignore` in this folder. Note that once pushed to a
public repo, the files remain in git history even if deleted later.
