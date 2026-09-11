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
	 * A password you invent, used two ways:
	 *
	 *   - it is the ?key= that opens the setup page below, so nobody who
	 *     guesses the URL can reach it;
	 *   - Telegram sends it back on every webhook request in a header, which is
	 *     what proves an incoming request really came from Telegram and not
	 *     from someone who found the address.
	 *
	 * Any long random string. 30+ characters, letters and digits, no spaces.
	 */
	'secret'        => 'INVENT-A-LONG-RANDOM-STRING-HERE',

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
