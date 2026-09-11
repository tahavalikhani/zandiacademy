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

	/* ------------------------------------------------------------------
	 * The two below are already correct for this project — measured from
	 * the bot's own log on 11 September 2026. Neither is a secret: a chat id
	 * is an address, not a key, and holding one grants nothing.
	 * --------------------------------------------------------------- */

	/*
	 * Who gets told when something happens. Shima's own Telegram account.
	 *
	 * ONE person, deliberately. Every notification the bot sends goes here and
	 * nowhere else, and it is also the only account allowed to press the
	 * approve and decline buttons — the bot checks the presser's id against
	 * this number before it acts, so a forwarded button does nothing.
	 *
	 * The bot can only message somebody who has opened it at least once. If
	 * notifications never arrive, open @bonjourmonjour_bot and press «Start».
	 */
	'admin_chat_id' => 743302471,

	/*
	 * The podcast group: ◆ Podcast Bonjour Monjour 🇫🇷
	 *
	 * Negative and starting 	-100 because it is a supergroup. That matters:
	 * unbanChatMember only works in supergroups and channels, so this number
	 * being shaped like this is what makes remove-then-let-back-in possible.
	 */
	'group_chat_id' => -1002167405019,
);
