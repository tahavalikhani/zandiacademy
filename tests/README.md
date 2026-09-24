# tests

Eleven command-line scripts that run parts of the theme without a WordPress
install, so a change can be checked before it is deployed.

```
php tests/test-students.php   # the students screen's data layer
php tests/test-render.php     # renders both screens, fails on any PHP notice
php tests/test-export.php     # runs the CSV export and prints the bytes
php tests/test-panel.php      # the student panel's course card
php tests/test-placement.php  # placement routing, and the intent that could hijack it
php tests/test-redirects.php  # where the site sends people, and whether they arrive
php tests/test-sections.php   # which section pages exist, and where retired ones go
php tests/test-podcast.php    # the subscription maths, the token, the transcripts
php tests/test-media.php      # finding an uploaded file by the name the owner typed
php tests/test-reviews.php    # the students' own words, pinned by content hash
php tests/test-conversation.php  # a course page anyone can read and nobody can buy yet
```

`test-sections.php` and `test-conversation.php` are the two that load
`functions.php` rather than only files from `inc/` — the section registry, the
rewrite rules and the no-WooCommerce enrol handler live there. Four helpers are
declared in both the stub and `functions.php`, so those files evaluate a copy
with the four renamed and the `require_once` lines dropped.
`test-conversation.php` does the same to `inc/woocommerce.php`, renaming its
`zandi_woo_active()` and switching the stub's on through
`$GLOBALS['stub_woo_active']` for the one lookup that asks. The technique is
confined to those tests; nothing in the theme was changed to allow it.

`wp-stub.php` is a thin stand-in for the WordPress functions those paths call —
`apply_filters`, the user-meta store, `WP_User`, `WP_User_Query`,
`WP_List_Table` and a few dozen others. It is not a WordPress emulator and does
not try to be. It exists because the alternative is claiming a screen works
because it looks like it should.

**They are command-line only.** The theme directory is served over HTTP, so
each file exits immediately unless `PHP_SAPI` is `cli`.

What they cover, and what they cannot:

| Covered here | Needs a real install |
| --- | --- |
| CSV formula injection, the BOM, ISO dates | Whether Excel opens the file the way Excel does |
| The level filter's whitelist, including «A1+» | Pagination and sorting round trips |
| Placement mirror, tally, capability gate | Digits, WooCommerce, SpotPlayer |
| Every column and both screens rendering clean | The query count under Query Monitor |

The stub deliberately reports WooCommerce as **inactive**, so the render test
also proves the screen degrades rather than fataling when the plugin is off.
