<?php
/**
 * Plugin Name:       NitroSearch for WooCommerce
 * Plugin URI:        https://nitrosearch.io
 * Description:        Blazing-fast hosted search for WooCommerce. Syncs your catalog to NitroSearch and replaces the default WordPress search with instant, typo-tolerant results.
 * Version:           1.15.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            WebDeviant
 * Author URI:        https://webdeviant.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nitrosearch
 * Domain Path:       /languages
 *
 * WC requires at least: 9.0
 *
 * @package NitroSearch
 */

if (! defined('ABSPATH')) {
    exit;
}

define('NITROSEARCH_VERSION', '1.15.0');
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

// Bundled translations from languages/. On init, not plugins_loaded:
// WordPress 6.7+ warns on any earlier load.
//
// Two loads, deliberately. `load_plugin_textdomain()` tries the wordpress.org
// language pack in wp-content/languages/plugins/ FIRST and RETURNS THE MOMENT
// IT LOADS ONE — it never reads the catalog in this zip. So on a store whose
// pack is behind our release, a string the pack has not caught up on falls
// back to raw English, not to the complete catalog sitting right here. That is
// not hypothetical: whenever a source string is re-worded, wordpress.org drops
// the old translation and the pack ships without it until an editor approves
// the new one.
//
// Loading the bundled catalog as a SECOND file closes that gap. WordPress 6.5+
// (our floor) keeps several files per text domain and answers from the first
// one that has the string, so the pack still wins wherever it has a
// translation and ours only fills the holes.
add_action('init', function (): void {
    load_plugin_textdomain('nitrosearch', false, dirname(plugin_basename(__FILE__)).'/languages');

    $locale = apply_filters('plugin_locale', determine_locale(), 'nitrosearch');
    $bundled = NITROSEARCH_DIR.'languages/nitrosearch-'.$locale.'.mo';
    if (is_readable($bundled)) {
        load_textdomain('nitrosearch', $bundled, $locale);
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
    // existing one alone. The marker option distinguishes "never activated since
    // this shipped" from "activated before", which the settings array alone cannot.
    if (get_option('nitrosearch_content_defaults_seeded') === false) {
        // Never overwrite a choice the merchant has already made. The activation hook
        // does NOT fire on a plugin update, so a store that upgraded in place carries
        // no marker — and seeding on the strength of the missing marker alone wiped
        // its opt-in the first time the plugin was deactivated and reactivated (a
        // routine troubleshooting step). The stored settings are the real record: the
        // key is present the moment anything has been saved.
        $stored = get_option('nitrosearch_settings');

        if (! is_array($stored) || ! array_key_exists('index_content', $stored)) {
            \NitroSearch\Settings::update([
                // Already syncing before this version existed: leave content off and
                // let the owner opt in from the settings screen. Pages and blog posts
                // consume the same allowance as products, so switching it on during an
                // upgrade could push a store over its limit and have its products
                // refused without the owner doing anything.
                'index_content' => \NitroSearch\Settings::isConnected()
                    ? []
                    : \NitroSearch\Settings::SUPPORTED_CONTENT_TYPES,
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
    \NitroSearch\Sync\ContentPurge::cancel();
});

// Boot once WooCommerce is loaded.
add_action('plugins_loaded', function (): void {
    if (! class_exists('WooCommerce')) {
        return;
    }
    (new \NitroSearch\Plugin())->boot();
}, 20);
