# The podcast bot — bootstrap

Not the finished bot. This is the smallest thing that proves the chain works and
answers the three questions that cannot be answered from Iran, or from a code
review, or by me:

1. **Can the German host reach Telegram?** (`getMe` returning a username.)
2. **Can Telegram reach the German host?** Outbound working proves nothing about
   inbound — a webhook delivery is the only evidence.
3. **Is the podcast group a supergroup, and what is its chat id?** There is no
   API that looks up a private group. The group has to introduce itself, which
   it does the moment the bot is added to it.

Question 3 decides whether the plan works at all: `banChatMember` works in every
kind of chat, but `unbanChatMember` only works in supergroups and channels. In a
basic group the bot could remove an expired subscriber and then be unable to let
them back in when they renewed.

## Where it goes

On the SarvData host (Germany), in the folder the `bot` subdomain serves:

```
domains/zandiacademy.com/public_html/bot/
  index.php          this bootstrap
  config.php         made from config.sample.php — holds the secrets
  updates.log.php    written by the bot, do not create it yourself
```

`config.php` is in `.gitignore` and must never come back into the repository,
be emailed, or be pasted into a chat. It holds the bot token, which is the
password to the group: anyone with it can read every episode and remove every
member. If it is ever exposed, `@BotFather` → `/revoke` issues a new token and
kills the old one immediately.

## Running it

1. Upload `index.php` and `config.sample.php`.
2. Rename `config.sample.php` to `config.php` and fill in `token` and `secret`.
   The secret is one you invent — 30+ random characters.
3. Open `https://bot.zandiacademy.com/?key=<the secret>` and work down the page.

The setup page is the only way in: without `?key=` matching `setup_key`, every
request gets a bare 404. A bot endpoint is a public URL that strangers will
find, so it gives nothing away when poked.

## Why there are two secrets and not one

`setup_key` opens the setup page and is typed into the address bar, so it ends
up in browser history, in the server's access log, and in any screenshot of the
window. `secret` is Telegram's — `setWebhook` hands it over once and Telegram
repeats it in a header on every delivery, so it never appears in a URL at all.

They started as one string and that was wrong: showing somebody the setup page
was enough to expose the value that authenticates Telegram. Split, a leaked
setup key costs one edit to `config.php` and nothing else. The bot refuses to
start if the two are equal.

## Apache serves index.html before index.php

DirectAdmin drops a placeholder `index.html` into a new subdomain's folder, and
`DirectoryIndex` prefers it. Upload `index.php` beside it and the subdomain
still answers with the placeholder — the bot is there and simply never runs.
Delete the placeholder.

## Why the log file has a `.php` extension

Shared hosting only guarantees one writable place — the folder itself, inside
the web root — so the log would be fetchable at its own URL. It is written as
`updates.log.php` opening with `<?php exit; ?>`, so requesting it executes that
line and returns nothing. Reading it happens through the setup page, behind the
key.

## The three answers, 11 September 2026

All green.

| Question | Answer |
| --- | --- |
| German host → Telegram | yes, `@bonjourmonjour_bot` |
| Telegram → German host | yes, webhook delivering, no errors |
| Group type | **supergroup**, `-1002167405019` |
| Iranian site → Telegram | no — expected, and why this runs in Germany |
| Iranian site → this host | yes, 404 in 1.5s |

That last row is the one the architecture depends on. The site answers 503 to
requests from datacentres, so the bot cannot ask it who has paid; the site has
to push. Now we know it can.

## What the bot does today

Holds the door and tells Shima. Every join request is intercepted and sent to
her with an approve and a decline button; joins and departures are reported;
being demoted out of admin is reported loudly, because that is the one failure
that leaves the bot running while every approval silently fails.

`zandi_bot_may_join()` is the seam. It returns `null` — "ask" — because there is
no paid list yet. When WooCommerce starts pushing one, it returns true or false
and the same code path settles the request in under a second without waking
anybody. The notification stops being a question and becomes a receipt. Nothing
else in the file changes.

Two details worth not losing:

- **The presser is checked, not assumed.** A forwarded notification keeps its
  buttons, so `callback_query` compares `from.id` against `admin_chat_id` before
  acting. Without that, anyone the message reached could open the group.
- **`chat_member` must be named in `allowed_updates`.** Telegram withholds it
  otherwise, even from an admin bot, and nobody is told when a member leaves.

## Still to come

The paid list and the push endpoint that fills it, the daily expiry sweep
(kick = `banChatMember` then `unbanChatMember`, so renewing lets them back),
and the renewal reminders at 7, 3 and 1 days.
