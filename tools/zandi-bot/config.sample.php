<?php
/**
 * Bot configuration — COPY THIS FILE TO config.php AND EDIT THE COPY.
 *
 * config.php holds two passwords and must never be committed, emailed, or
 * pasted into a chat. It lives on the server and nowhere else. This sample is
 * the only version that belongs in the repository.
 *
 * @package Zandi
 */

return array(

	/*
	 * From @BotFather. Looks like 8123456789:AAH-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
	 *
	 * Anyone holding this can read the group and remove every member, so treat
	 * it exactly like the wp-admin password. If it ever leaks, @BotFather's
	 * /revoke issues a new one and kills the old one instantly.
	 */
	'token'         => 'PASTE-THE-BOTFATHER-TOKEN-HERE',

	/*
	 * Telegram's shared secret. You invent it; setWebhook hands it to Telegram,
	 * and Telegram repeats it in a header on every delivery. That header is what
	 * proves an incoming request really came from Telegram rather than from
	 * somebody who found the address.
	 *
	 * It never appears in a URL, so it does not end up in browser history,
	 * screenshots or the server's access log. Long and random: 30+ characters,
	 * letters and digits, no spaces.
	 */
	'secret'        => 'INVENT-A-LONG-RANDOM-STRING-HERE',

	/*
	 * The password for the setup page, and a DIFFERENT string from the one
	 * above. It is typed as ?key= in the address bar, which means it is visible
	 * over your shoulder, saved in browser history, written into the server's
	 * access log and captured by any screenshot of the window.
	 *
	 * That is why it is separate: a setup key that leaks costs you one edit to
	 * this file, while the Telegram secret above stays untouched. Treat this one
	 * as disposable and change it whenever you have shown the URL to anyone.
	 */
	'setup_key'     => 'INVENT-A-SECOND-DIFFERENT-RANDOM-STRING-HERE',

	/*
	 * The public HTTPS address of this folder, no trailing slash.
	 * Telegram will only deliver to a real certificate, so it must be https.
	 */
	'webhook_url'   => 'https://bot.zandiacademy.com',

	/*
	 * Filled in later, once the bot has been added to the group and the setup
	 * page has shown you the number. Leave it at 0 for now.
	 */
	'group_chat_id' => 0,
);
