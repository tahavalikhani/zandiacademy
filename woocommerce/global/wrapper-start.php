<?php
/**
 * Opening wrapper — emptied on purpose.
 *
 * WooCommerce prints `<div id="primary" class="content-area"><main
 * id="main" class="site-main">` here. Both already exist: woocommerce.php in
 * the theme root supplies `<main id="main">` and the `.container` that every
 * other page on this site uses, so leaving this in place would duplicate the
 * `id="main"` — invalid, and it breaks the skip link in header.php, which
 * targets exactly that ID.
 *
 * Overridden rather than unhooked because WooCommerce loads it through
 * `woocommerce_output_content_wrapper()`, which a theme is expected to replace
 * via the template, not via remove_action().
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;
