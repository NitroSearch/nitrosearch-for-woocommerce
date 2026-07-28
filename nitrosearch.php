<?php
/**
 * Plugin Name:       NitroSearch for WooCommerce
 * Plugin URI:        https://nitrosearch.io
 * Description:        Blazing-fast hosted search for WooCommerce. Syncs your catalog to NitroSearch and replaces the default WordPress search with instant, typo-tolerant results.
 * Version:           1.3.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            WebDeviant
 * Author URI:        https://webdeviant.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nitrosearch
 *
 * WC requires at least: 9.0
 *
 * @package NitroSearch
 */

if (! defined('ABSPATH')) {
    exit;
}

define('NITROSEARCH_VERSION', '1.3.1');
define('NITROSEARCH_FILE', __FILE__);
define('NITROSEARCH_DIR', plugin_dir_path(__FILE__));

// The hosted backend. Overridable via wp-config for local development.
if (! defined('NITROSEARCH_API_URL')) {
    define('NITROSEARCH_API_URL', 'https://api.nitrosearch.io');
}

// Minimal PSR-4 autoloader for the NitroSearch\ namespace -> src/.
spl_autoload_register(function (string $class): void {
    $prefix = 'NitroSearch\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = NITROSEARCH_DIR.'src/'.str_replace('\\', '/', $relative).'.php';
    if (is_readable($path)) {
        require $path;
    }
});

// HPOS compatibility (orders are only ever read via CRUD).
add_action('before_woocommerce_init', function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

register_activation_hook(__FILE__, function (): void {
    \NitroSearch\Sync\Outbox::install();

    // Seed pages-and-posts indexing ON for a genuinely NEW install, and leave an
    // existing one alone. Pages and blog posts consume the same allowance as
    // products, so switching it on during an upgrade could push a store over its
    // limit and have its products refused without the owner doing anything. The
    // marker option distinguishes "never chosen" from "chosen as empty", which the
    // settings array alone cannot.
    if (get_option('nitrosearch_content_defaults_seeded') === false) {
        if (\NitroSearch\Settings::isConnected()) {
            // Already syncing before this version existed: leave content off and let
            // the owner opt in from the settings screen.
            \NitroSearch\Settings::update(['index_content' => []]);
        } else {
            \NitroSearch\Settings::update([
                'index_content' => \NitroSearch\Settings::SUPPORTED_CONTENT_TYPES,
            ]);
        }
        add_option('nitrosearch_content_defaults_seeded', '1');
    }
});

register_deactivation_hook(__FILE__, function (): void {
    if (function_exists('as_unschedule_all_actions')) {
        as_unschedule_all_actions(\NitroSearch\Sync\Drain::HOOK);
    }
    // Also clears the `active` flag so a full sync doesn't silently auto-resume on
    // reactivation with no scheduled chunk.
    \NitroSearch\Sync\FullSync::cancel();
});

// Boot once WooCommerce is loaded.
add_action('plugins_loaded', function (): void {
    if (! class_exists('WooCommerce')) {
        return;
    }
    (new \NitroSearch\Plugin())->boot();
}, 20);
