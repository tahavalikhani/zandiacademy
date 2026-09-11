<?php
/**
 * Bot configuration — COPY THIS FILE TO config.php AND EDIT THE COPY.
 *
 * config.php holds three passwords and must never be committed, emailed, or
 * pasted into a chat. It lives on the server and nowhere else. This sample is
 * the only version that belongs in the repository.
 *
 * @package Zandi
 */

return array(

	/*
	 * From @BotFather. Anyone holding this can read the group and remove every
	 * member, so treat it exactly like the wp-admin password. If it leaks,
	 * @BotFather's /revoke issues a new one and kills the old one instantly.
	 */
	'token'          => 'PASTE-THE-BOTFATHER-TOKEN-HERE',

	/*
	 * Telegram's shared secret. setWebhook hands it over once and Telegram
	 * repeats it in a header on every delivery, which is what proves a request
	 * really came from Telegram. It never appears in a URL.
	 */
	'secret'         => 'INVENT-A-LONG-RANDOM-STRING-HERE',

	/*
	 * The password for the setup page, and a DIFFERENT string from the one
	 * above. It is typed as ?key= in the address bar, so it lands in browser
	 * history, the access log and any screenshot. Disposable by design: change
	 * it whenever you have shown the URL to anyone.
	 *
	 * The nightly sweep uses it too — see the cron line on the setup page.
	 */
	'setup_key'      => 'INVENT-A-SECOND-DIFFERENT-RANDOM-STRING-HERE',

	/*
	 * THE ONE THAT MUST MATCH THE WEBSITE. Define the identical string in
	 * wp-config.php as ZANDI_BOT_SECRET:
	 *
	 *     define( 'ZANDI_BOT_SECRET', '…' );
	 *     define( 'ZANDI_BOT_URL', 'https://bot.zandiacademy.com' );
	 *
	 * It does two jobs, and both of them are the reason this bot can work at
	 * all without ever calling the website back. It signs the entitlement the
	 * site pushes here, and it signs the connect link a student taps — so this
	 * bot can verify who they are locally, rather than asking a server that
	 * refuses requests from datacentre addresses.
	 *
	 * A third different random string. 40+ characters.
	 */
	'bridge_secret'  => 'INVENT-A-THIRD-STRING-AND-PUT-IT-IN-WP-CONFIG-TOO',

	/*
	 * The public HTTPS address of this folder, no trailing slash.
	 */
	'webhook_url'    => 'https://bot.zandiacademy.com',

	/* ------------------------------------------------------------------
	 * Already correct for this project — measured from the bot's own log on
	 * 11 September 2026. Neither is a secret: a chat id is an address.
	 * --------------------------------------------------------------- */

	/*
	 * Who gets told when something happens. Shima's own Telegram account, and
	 * the only account whose button presses the bot obeys.
	 */
	'admin_chat_id'  => 743302471,

	/*
	 * The podcast group: ◆ Podcast Bonjour Monjour 🇫🇷
	 *
	 * Negative and starting -100 because it is a supergroup, which is what
	 * makes remove-then-let-back-in possible: unbanChatMember does not work in
	 * a basic group.
	 */
	'group_chat_id'  => -1002167405019,

	/*
	 * Where to send somebody whose subscription has run out. The page, not a
	 * checkout — whether a plan can be bought today is the page's question.
	 */
	'buy_url'        => 'https://zandiacademy.com/podcast/',
);
