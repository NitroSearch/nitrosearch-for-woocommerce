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
    /** Where an opted-in credit points. Kept as a constant so both halves agree. */
    public const CREDIT_URL = 'https://nitrosearch.io';

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'inject']);
        add_action('wp_footer', [$this, 'renderCredit']);
    }

    /**
     * The opt-in credit, rendered into the page itself.
     *
     * The widget already draws a credit inside its dropdown, but that panel does not
     * exist until a shopper focuses the search box — verified on a real storefront:
     * with the credit switched on and before any interaction, the widget is not
     * mounted and the page contains no reference to us at all. A visitor who never
     * searches, and every crawler, would therefore never see it.
     *
     * So the credit is also emitted server-side, as ordinary HTML, once per page.
     * Nothing is output unless the merchant has ticked the box: an unrequested
     * external link on someone else's storefront is not ours to add.
     */
    public function renderCredit(): void
    {
        if (! Settings::isConnected() || ! Settings::get('show_badge', false)) {
            return;
        }

        printf(
            '<p class="nitrosearch-credit" style="text-align:center;font-size:12px;line-height:1.6;margin:12px 0;opacity:.75;">%s <a href="%s" rel="noopener">%s</a></p>',
            esc_html__('Search powered by', 'nitrosearch'),
            esc_url(self::CREDIT_URL),
            esc_html__('NitroSearch', 'nitrosearch')
        );
    }

    public function inject(): void
    {
        if (! Settings::isConnected() || ! Settings::get('scoped_search_key')) {
            return;
        }

        $loaderUrl = (string) (Settings::get('widget_loader_url')
            ?: Settings::apiUrl().'/widget/loader.v1.js');

        // Merchant appearance tokens (only send what was set; the widget supplies
        // its own defaults for everything else via --ns-* custom properties).
        $theme = [];
        $accent = (string) Settings::get('accent_color');
        if ($accent !== '') {
            $theme['accent'] = $accent;
        }

        $config = [
            'engine'     => ['host' => (string) Settings::get('engine_host')],
            'collection' => (string) Settings::get('collection'),
            'scopedKey'  => (string) Settings::get('scoped_search_key'),
            'bundleUrl'  => (string) (Settings::get('widget_bundle_url')
                ?: Settings::apiUrl().'/widget/nitrosearch.v1.js'),
            'siteUrl'    => get_site_url(),
            'currency'   => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD',
            // "Powered by NitroSearch" is OPT-IN and defaults OFF — a front-end credit
            // must never show without the site owner's explicit choice (wp.org
            // guideline 10). Merchants enable it in the plugin's Appearance settings.
            'badge'      => (bool) Settings::get('show_badge', false),
            // Where the in-widget credit links, when the merchant has opted in. Absent
            // when they have not, so the widget cannot render a link we were not given
            // permission to place.
            'badgeUrl'   => Settings::get('show_badge', false) ? self::CREDIT_URL : '',
            'theme'      => (object) $theme,
            // Results-page takeover on the product search page (merchant toggle).
            'results'    => (bool) Settings::get('results_takeover', true),
            // Whether to also query pages/posts. False means the widget sends one
            // fewer search per keystroke, so a store with content off pays nothing
            // for the feature existing.
            'content'    => Settings::indexesContent(),
            // Merchant toggle for the anonymous usage beacon. The widget key is
            // 'analytics' (its wire name; the dashboards it will feed are not yet
            // released) — false stops all emission client-side.
            'analytics'  => (bool) Settings::get('share_search_data', true), // wire name; its dashboards are not yet released
        ];

        // The usage-events beacon endpoint + this store's public token. Omitted
        // entirely until the backend has issued one — the widget no-ops without
        // it, so an old backend or an unverified store costs nothing.
        $eventsToken = (string) Settings::get('events_token');
        $eventsUrl = (string) Settings::get('events_url');
        if ($eventsToken !== '' && $eventsUrl !== '') {
            $config['events'] = ['url' => esc_url_raw($eventsUrl), 'token' => $eventsToken];
        }

        // Add-to-cart endpoint for the results grid. The classic wc-ajax endpoint
        // (not the Store API) is used deliberately: it writes the WC session cart
        // the theme's mini-cart and checkout read, and returns mini-cart fragments.
        if (class_exists('WC_AJAX')) {
            $config['cart'] = [
                'add' => esc_url_raw(\WC_AJAX::get_endpoint('add_to_cart')),
            ];
        }

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
