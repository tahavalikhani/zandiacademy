# Peyda — licensed font, committed

Peyda (فونت پیدا) is commercial software, designed by Seyyed Nasser
Khademtabar and sold through [fontiran.com](https://fontiran.com/fonts/peyda).

The academy holds a **Peyda 4 (SemiPro)** licence and confirmed with fontiran
that keeping the files in this repository is acceptable, so the five web weights
are committed here and the site works straight from a checkout.

> **This depends on the repository staying private.** A public repository would
> be redistributing a paid font to anyone who visits it. If the repo is ever made
> public, remove these files (and purge them from git history — deleting a file
> does not remove it from earlier commits).

## Which build is used

The **Font Family** web build, `PeydaWeb-*.woff2`:

| File | Weight |
| --- | --- |
| `PeydaWeb-ExtraLight.woff2` | 200 |
| `PeydaWeb-Regular.woff2` | 400 |
| `PeydaWeb-SemiBold.woff2` | 600 |
| `PeydaWeb-Bold.woff2` | 700 |
| `PeydaWeb-Black.woff2` | 900 |

Two variants in the purchased package are deliberately **not** used:

- **`PeydaFaNum`** substitutes Persian digits for Latin ones *inside the font*.
  That would render CEFR codes as `A۱` and `B۲` — the exact bug this theme
  disables Vazirmatn's `ss01` to avoid. Digits are localised in PHP by
  `zandi_fa_digits()`, where it can be targeted.
- **`PeydaNoEn`** has no Latin glyphs, so `Bonjour`, `A1` and `€` would drop to
  a fallback face mid-sentence.

## How it is wired

`zandi_peyda_files()` detects whatever is present, `wp_head` emits the
`@font-face` rules and overrides the `--font-persian` custom property, and the
preload follows whichever face is live. The desktop naming (`Peyda-Regular.woff2`
…) and a variable `Peyda-Variable.woff2` are also accepted, the variable file
taking priority.

Remove the files and the site falls back to Vazirmatn with no Peyda CSS emitted —
nothing breaks. To disable it while keeping the files:

```php
add_filter( 'zandi_use_peyda', '__return_false' );
```
