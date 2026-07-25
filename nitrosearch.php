<?php
/**
 * Plugin Name:       NitroSearch for WooCommerce
 * Plugin URI:        https://nitrosearch.io
 * Description:        Blazing-fast hosted search for WooCommerce. Syncs your catalog to NitroSearch and replaces the default WordPress search with instant, typo-tolerant results.
 * Version:           1.2.2
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            WebDeviant
 * Author URI:        https://nitrosearch.io
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

define('NITROSEARCH_VERSION', '1.2.2');
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
});

register_deactivation_hook(__FILE__, function (): void {
    if (function_exists('as_unschedule_all_actions')) {
        as_unschedule_all_actions(\NitroSearch\Sync\Drain::HOOK);
    }
});

// Boot once WooCommerce is loaded.
add_action('plugins_loaded', function (): void {
    if (! class_exists('WooCommerce')) {
        return;
    }
    (new \NitroSearch\Plugin())->boot();
}, 20);
