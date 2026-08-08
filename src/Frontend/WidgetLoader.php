<?php

namespace NitroSearch\Frontend;

use NitroSearch\Settings;
use NitroSearch\Support\Design;

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
            '<p class="nitrosearch-credit" style="text-align:center;font-size:12px;line-height:1.6;margin:12px 0;opacity:.75;">%s</p>',
            sprintf(
                /* translators: %s: the linked brand name "NitroSearch". */
                esc_html__('Search powered by %s', 'nitrosearch'),
                '<a href="'.esc_url(self::CREDIT_URL).'" rel="noopener">NitroSearch</a>'
            )
        );
    }

    /**
     * The storefront widget's own UI strings, resolved through the plugin's
     * translation stack. The widget bundle is one shared file for every store,
     * so it carries no locales — the plugin hands it the store's language the
     * same way Design hands it the store's colours. Keys are the widget's label
     * contract; the widget falls back to its built-in English for any it does
     * not receive, so either side can ship first.
     *
     * Plural strings are sent as CLDR category maps (one/few/many/other) built
     * from ordinary _n() calls at representative counts — 1, 2, 5 and 100
     * select the right gettext plural form in every locale we ship — and the
     * widget picks a category with Intl.PluralRules at render time, when it
     * knows the count. 'other' samples at 100, not 5: in Romanian the "few"
     * form covers 2–19, so a count of 5 would freeze the few-form into the
     * category that CLDR only selects from 20 upward.
     *
     * @return array<string,string|array<string,string>>
     */
    private static function widgetLabels(): array
    {
        return [
            'refine_results'    => __('Refine results', 'nitrosearch'),
            'refine'            => __('Refine', 'nitrosearch'),
            'in_stock'          => __('In stock', 'nitrosearch'),
            'on_sale'           => __('On sale', 'nitrosearch'),
            'brand'             => __('Brand', 'nitrosearch'),
            'category'          => __('Category', 'nitrosearch'),
            'view'              => __('View', 'nitrosearch'),
            'add_to_cart'       => __('Add to cart', 'nitrosearch'),
            'adding'            => __('Adding…', 'nitrosearch'),
            'added'             => __('Added ✓', 'nitrosearch'),
            'try_again'         => __('Try again', 'nitrosearch'),
            'searching'         => __('Searching…', 'nitrosearch'),
            'unavailable_brief' => __('Search is unavailable.', 'nitrosearch'),
            'unavailable'       => __('Search is unavailable right now.', 'nitrosearch'),
            'no_products'       => __('No products found.', 'nitrosearch'),
            /* translators: %s: the shopper's search term. */
            'no_products_for'   => __('No products found for “%s”.', 'nitrosearch'),
            /* translators: %s: the shopper's search term. */
            'nothing_found'     => __('Nothing found for “%s”.', 'nitrosearch'),
            'placeholder'       => __('Search products…', 'nitrosearch'),
            'close_search'      => __('Close search', 'nitrosearch'),
            'product_results'   => __('Product results', 'nitrosearch'),
            'recent_searches'   => __('Recent searches', 'nitrosearch'),
            'clear'             => __('Clear', 'nitrosearch'),
            'start_typing'      => __('Start typing to search products…', 'nitrosearch'),
            'sale'              => __('Sale', 'nitrosearch'),
            'out_of_stock'      => __('Out of stock', 'nitrosearch'),
            'pages_posts'       => __('Pages & posts', 'nitrosearch'),
            'page'              => _x('Page', 'a website page, shown on a search result', 'nitrosearch'),
            'article'           => _x('Article', 'a blog post, shown on a search result', 'nitrosearch'),
            /* translators: %s: the search round-trip time in milliseconds. */
            'ms'                => _x('%s ms', 'unit: milliseconds', 'nitrosearch'),
            /* translators: %s: the brand name "NitroSearch". */
            'powered_by'        => sprintf(__('Powered by %s', 'nitrosearch'), 'NitroSearch'),
            /* translators: 1: current page number, 2: total number of pages. */
            'page_of'           => __('Page %1$s of %2$s', 'nitrosearch'),
            'prev'              => __('← Prev', 'nitrosearch'),
            'next'              => __('Next →', 'nitrosearch'),
            'search'            => _x('Search', 'button label', 'nitrosearch'),
            'products_found'    => [
                /* translators: %s: number of products found (screen-reader announcement). */
                'one'   => _n('%s product found.', '%s products found.', 1, 'nitrosearch'),
                'few'   => _n('%s product found.', '%s products found.', 2, 'nitrosearch'),
                'many'  => _n('%s product found.', '%s products found.', 5, 'nitrosearch'),
                'other' => _n('%s product found.', '%s products found.', 100, 'nitrosearch'),
            ],
            'see_all'           => [
                /* translators: %s: total number of results. */
                'one'   => _n('See all %s result →', 'See all %s results →', 1, 'nitrosearch'),
                'few'   => _n('See all %s result →', 'See all %s results →', 2, 'nitrosearch'),
                'many'  => _n('See all %s result →', 'See all %s results →', 5, 'nitrosearch'),
                'other' => _n('See all %s result →', 'See all %s results →', 100, 'nitrosearch'),
            ],
            'results_for'       => [
                /* translators: 1: number of results, 2: the shopper's search term. */
                'one'   => _n('%1$s result for “%2$s”', '%1$s results for “%2$s”', 1, 'nitrosearch'),
                'few'   => _n('%1$s result for “%2$s”', '%1$s results for “%2$s”', 2, 'nitrosearch'),
                'many'  => _n('%1$s result for “%2$s”', '%1$s results for “%2$s”', 5, 'nitrosearch'),
                'other' => _n('%1$s result for “%2$s”', '%1$s results for “%2$s”', 100, 'nitrosearch'),
            ],
            'results_count'     => [
                /* translators: %s: number of results. */
                'one'   => _n('%s result', '%s results', 1, 'nitrosearch'),
                'few'   => _n('%s result', '%s results', 2, 'nitrosearch'),
                'many'  => _n('%s result', '%s results', 5, 'nitrosearch'),
                'other' => _n('%s result', '%s results', 100, 'nitrosearch'),
            ],
        ];
    }

    public function inject(): void
    {
        if (! Settings::isConnected() || ! Settings::get('scoped_search_key')) {
            return;
        }

        $loaderUrl = (string) (Settings::get('widget_loader_url')
            ?: Settings::apiUrl().'/widget/loader.v1.js');

        // Merchant appearance tokens (only what differs from the widget's own
        // defaults; Design resolves the named Look/Colour choices to --ns-* values
        // here so the shared bundle never carries preset names).
        $theme = Design::theme();
        $layout = Design::layout();

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
            // Layout behaviour the widget decides in JS rather than CSS (panel
            // width rule, how many products to list, whether filters get a rail).
            'layout'     => (object) $layout,
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

        // The widget's UI strings in the store's language, plus the locale its
        // plural rules key off (BCP 47, per request so multilingual plugins that
        // switch the locale per page are honoured). English stores send neither:
        // the widget's built-in strings are already English, so their pages stay
        // exactly as before this existed. The probe also keeps a locale we have
        // NO catalog for on the widget's built-in English (and English plural
        // rules) — sending untranslated English maps with, say, Russian plural
        // selection would render "21 product found." The probe string is in
        // every shipped catalog, and the __() call triggers WordPress's
        // just-in-time textdomain load, so future language packs count too.
        $locale = determine_locale();

        // The LOCALE always goes, even for English stores and for languages we have no
        // catalog for. It is not only a translation switch: the search box formats
        // prices with it, so withholding it left a US store's dollars and a British
        // store's pounds formatted identically, and any store outside the shipped
        // catalogs falling back to generic English number conventions. Sending it costs
        // one short string and cannot make anything worse — a widget that does not
        // recognise the locale falls back on its own.
        $config['locale'] = str_replace('_', '-', $locale);

        // The LABELS stay gated on the probe below. Sending untranslated English maps
        // with, say, Russian plural selection would render "21 product found." The probe
        // string is in every shipped catalog, and the __() call triggers WordPress's
        // just-in-time textdomain load, so future language packs count too.
        if (strpos($locale, 'en') !== 0 && __('Search products…', 'nitrosearch') !== 'Search products…') {
            $config['labels'] = self::widgetLabels();
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
