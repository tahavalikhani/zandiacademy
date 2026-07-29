# Peyda — drop the licensed files here

Peyda (فونت پیدا) is **commercial software**. It is designed by Seyyed Nasser
Khademtabar and sold exclusively through **[fontiran.com](https://fontiran.com/fonts/peyda)**.
There is no free version — every "دانلود رایگان فونت پیدا" site is distributing
a pirated copy, and using one on a commercial site is a licensing risk, not a
shortcut.

So the font is **not committed to this repository**. The theme is wired to pick
it up automatically as soon as the licensed files are placed in this folder.

## What to buy

A **web / وب‌سایت licence**, not just the desktop one. The desktop licence
covers design files; embedding the font in a website needs the web licence,
priced by project size. The purchase includes `woff2` webfonts.

## What to drop in

Either layout works — the theme checks for the variable file first, then the
static weights.

**Variable (preferred, one file):**

```
assets/fonts/peyda/Peyda-Variable.woff2
```

**Static weights:**

```
assets/fonts/peyda/Peyda-Regular.woff2     (400)
assets/fonts/peyda/Peyda-Medium.woff2      (500)
assets/fonts/peyda/Peyda-SemiBold.woff2    (600)
assets/fonts/peyda/Peyda-Bold.woff2        (700)
```

Rename whatever fontiran ships to match. Nothing else to configure — no code
change, no setting. The next page load serves Peyda, with Vazirmatn still
declared behind it so a missing weight never falls back to a system font.

## Turning it off

Remove the files, or add to a child theme:

```php
add_filter( 'zandi_use_peyda', '__return_false' );
```

## Licence hygiene

`.gitignore` excludes `*.woff2` and `*.ttf` in this folder so the licensed
files are never committed by accident. If the site is deployed from git, copy
them onto the server as part of the deploy rather than committing them.
