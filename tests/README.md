# tests

Four command-line scripts that run parts of the theme without a WordPress
install, so a change can be checked before it is deployed.

```
php tests/test-students.php   # the students screen's data layer
php tests/test-render.php     # renders both screens, fails on any PHP notice
php tests/test-export.php     # runs the CSV export and prints the bytes
```

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
