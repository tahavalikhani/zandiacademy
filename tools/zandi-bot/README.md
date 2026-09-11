# The podcast bot

Holds the only entrance to ◆ Podcast Bonjour Monjour 🇫🇷 and opens it for people
who have paid, by itself, within a second of them asking. Removes them a day
after their subscription lapses, reminds them at 7, 3 and 1 days before, and
tells Shima what it did.

## Why it is shaped like this

Two network facts, both measured on 11 September 2026, decide the whole design.

**Telegram is filtered outbound from Iranian datacentres**, so this cannot run
on the website's server. It runs in Germany.

**The website answers 503 to requests from datacentre addresses**, so this bot
cannot call the website either. Traffic flows one way only: the site pushes
entitlement here and the bot decides from its own copy.

That constraint turns out to be a feature. The site being unreachable does not
stop expired members being removed on time, and this bot being down does not
stop the shop selling — join requests simply queue in Telegram until it returns.

## The identity is split across the two sides

A bot cannot look anybody up by phone number. There is no such API, at any
price. So:

| | knows |
| --- | --- |
| the site | `user_id → expires` |
| the bot | `user_id → telegram_id` |

The site never learns a Telegram id, because the student introduces themselves
to the **bot** by tapping a signed deep link. Each side holds one half of the
join and neither has to ask the other, which is the only arrangement the network
allows. The push is keyed on `user_id` for exactly this reason.

## Why a leaked invite link is worthless

The group's link creates a join **request**, not a membership. Tapping it does
not let anybody in — it asks, and the bot answers by looking the person up. Post
the link publicly and nobody unpaid gets through.

A one-time link would be weaker: it is still a key, and it works for whoever
opens it first rather than for the person it was issued to.

## Files

```
index.php        routing, Telegram updates, the sweep
store.php        who has paid — a JSON file, keyed on WordPress user id
token.php        the connect token, verified. Side-effect free on purpose:
                 the theme's test harness requires it directly and runs it
                 against tokens minted by zandi_podcast_bind_token(), so the
                 two codebases cannot drift apart unnoticed
setup.php        the one screen, behind the setup key
config.php       three secrets. Never committed, never emailed
store.json.php   written by the bot. Gitignored
updates.log.php  written by the bot. Gitignored
```

Both data files open with `<?php exit; ?>` so fetching their URL returns
nothing: shared hosting only guarantees one writable place, and it is inside the
document root.

## Installing

1. Upload `index.php`, `store.php`, `token.php`, `setup.php`, `config.sample.php`.
2. Rename the sample to `config.php` and fill in the four secrets. `bridge_secret`
   must be the identical string defined in the site's `wp-config.php` as
   `ZANDI_BOT_SECRET`.
3. Open `https://bot.zandiacademy.com/?key=<setup_key>`.
4. Press **ثبت وبهوک**, then **ساختن لینک گروه**.
5. Put the cron line the page prints into DirectAdmin → Cron Jobs, once a day.

## The sweep only touches rows it knows

The group still holds people who joined before the site sold anything. They are
not in the store, so the sweep cannot see them and they stay where they are. A
sweep that removed everybody it could not account for would empty the group the
first time it ran — so it never does that, by construction.

They enter the system the moment they connect an account, and not before.

## Three things that are easy to get wrong

**The presser of a button is checked, not assumed.** A forwarded notification
keeps its buttons, so `callback_query` compares `from.id` against
`admin_chat_id`. Without that, anybody the message reached could open the group.

**`chat_member` must be named in `allowed_updates`.** Telegram withholds it
otherwise, even from an admin bot, and nobody is ever told a member left.

**`creates_join_request` cannot be combined with `member_limit`.** Telegram
refuses the call. That is fine — the limit is the weaker idea.
