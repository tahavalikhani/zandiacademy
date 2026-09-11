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

## What replaces this

Once the three answers are in, `index.php` is replaced by the real bot:
entitlement list, `chat_join_request` approval against that list, the daily
expiry sweep, and the renewal reminders. The config file and the webhook
registration stay exactly as they are, so that swap is one upload.
