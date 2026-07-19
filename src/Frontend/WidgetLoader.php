<?php

namespace NitroSearch\Frontend;

use NitroSearch\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Injects the storefront widget: a small config blob plus the loader shim. The
 * loader enhances the theme's existing search input in place and lazy-loads the
 * widget bundle on first search intent. Search runs directly against the engine
 * with the store's scoped key — the widget never calls back through PHP.
 */
final class WidgetLoader
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'inject']);
    }

    public function inject(): void
    {
        if (! Settings::isConnected() || ! Settings::get('scoped_search_key')) {
            return;
        }

        $loaderUrl = (string) (Settings::get('widget_loader_url')
            ?: Settings::apiUrl().'/widget/loader.v1.js');

        $config = [
            'engine'     => ['host' => (string) Settings::get('engine_host')],
            'collection' => (string) Settings::get('collection'),
            'scopedKey'  => (string) Settings::get('scoped_search_key'),
            'bundleUrl'  => (string) (Settings::get('widget_bundle_url')
                ?: Settings::apiUrl().'/widget/nitrosearch.v1.js'),
            'siteUrl'    => get_site_url(),
            'currency'   => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD',
            // Free tier shows the badge; paid plans clear it (server decides later).
            'badge'      => true,
            'theme'      => (object) [],
        ];

        $selector = trim((string) Settings::get('selector'));
        if ($selector !== '') {
            $config['selector'] = $selector;
        }

        // Register a no-src handle so we can attach the inline config + loader.
        wp_register_script('nitrosearch-loader', '', [], NITROSEARCH_VERSION, true);
        wp_enqueue_script('nitrosearch-loader');
        wp_add_inline_script(
            'nitrosearch-loader',
            'window.NitroSearchConfig=' . wp_json_encode($config) . ';'
        );
        // Load the external loader shim after the config is defined.
        wp_enqueue_script('nitrosearch-widget', $loaderUrl, ['nitrosearch-loader'], NITROSEARCH_VERSION, true);
    }
}
