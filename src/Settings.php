<?php

namespace NitroSearch;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin settings and connection credentials, stored in a single wp_options row.
 * The sync secret is sensitive; it lives server-to-server only and is never
 * printed to the page.
 */
final class Settings
{
    private const OPTION = 'nitrosearch_settings';

    /** The only non-product types this version can index. */
    public const SUPPORTED_CONTENT_TYPES = ['page', 'post'];

    /**
     * The wire sentinel for "no limit" on `product_limit` from GET /v1/status.
     *
     * An unlimited entitlement has no numeric cap, but the field is typed as an
     * integer on the wire, so the backend sends this rather than null — a null
     * would arrive here as `(int) null` = 0, which reads as a store that may index
     * nothing at all. Treated as a threshold rather than an equality test so a
     * future larger sentinel still reads as unlimited instead of rendering as a
     * ten-digit number.
     */
    public const PLAN_UNLIMITED = 1000000000;

    /** @return array<string,mixed> */
    public static function all(): array
    {
        $defaults = [
            'connected'         => false,
            'store_id'          => '',
            'install_id'        => '',
            'sync_key_id'       => '',
            'sync_secret'       => '',
            'search_public_id'  => '',
            'scoped_search_key' => '',
            'collection'        => '',
            'engine_host'       => '',
            'widget_loader_url' => '',
            'widget_bundle_url' => '',
            'selector'          => '',   // optional custom search-input selector
            'accent_color'      => '',   // optional widget accent colour (hex)
            // ---- Design (see Admin\Appearance) -------------------------------
            // Named looks and colour schemes rather than loose CSS: the plugin
            // resolves each to the widget's --ns-* tokens, so the shared widget
            // bundle never learns preset names and a new preset costs the
            // storefront nothing. Empty string means "the widget's own default".
            'design_look'       => 'roomy',  // roomy | compact | images | text
            'design_scheme'     => 'light',  // light | dark | auto | custom
            'design_bg'         => '',       // panel background (custom scheme only)
            'design_text'       => '',       // body text colour (custom scheme only)
            'design_corners'    => 'rounded',// rounded | soft | square
            'design_font'       => 'store',  // store | system | custom
            'design_font_stack' => '',       // used when design_font = custom
            'design_width'      => 'auto',   // auto | wide | match
            'design_per_page'   => 8,        // products listed in the dropdown
            'design_filters'    => 'auto',   // auto | top | off
            'connect_token'     => '',   // optional provisioning token, sent on connect
            'results_takeover'  => true, // hydrate the product search-results page
            // Which non-product content to index, alongside products. Pages and blog
            // posts consume the SAME quota as products, so this is a real cost lever
            // for the merchant, not a cosmetic toggle — hence off unless chosen.
            //
            // Defaults to [] here so an EXISTING install never starts consuming its
            // allowance with blog posts on upgrade, and never has products refused
            // through no action of its own. A fresh install gets pages and posts on,
            // seeded at activation (see Plugin::activate) where "no value yet" is
            // distinguishable from "the merchant turned it off".
            'show_badge'        => false, // opt-in "Powered by NitroSearch" in the widget (default OFF)
            // Anonymous, cookieless search usage counts (searches, result counts,
            // clicks) sent to NitroSearch. Default ON, disclosed in the readme's
            // External-services section and in a one-time notice on upgrade; the
            // merchant can switch it off below. Carries no shopper identifiers.
            'share_search_data' => true,
            'events_url'        => '',    // beacon endpoint (from the backend)
            'events_token'      => '',    // this store's public events token
            'analytics_included' => false, // plan includes the reporting surfaces (not yet released)
            'verified'          => false, // proof-of-control passed (from /v1/status)
            'index_content'     => [],    // e.g. ['page','post'] — see the note above
            'product_limit'     => 0,     // plan cap (from /v1/status)
            'product_count'     => 0,     // products in the engine so far (from /v1/status)
            'at_limit'          => false, // catalogue has hit the plan cap (from /v1/status)
            // When the daily config refresh last ran (unix ts) — the scoped search
            // key embeds an expiry, so a connected store re-fetches it (plus the
            // widget asset URLs) roughly once a day off the drain heartbeat
            // (see Sync\ConfigRefresh). 0 = never.
            'config_refreshed_at' => 0,
            // When the periodic status check last ran (unix ts). Status used to be
            // fetched only on a version change or a manual "Check status", so a store
            // could run for months without learning that its plan, limit or indexed
            // count had moved — and could not be told to re-send its catalogue at all
            // (see Sync\ResyncCheck). 0 = never.
            'status_checked_at' => 0,
            // The last re-sync request this store has acted on. Compared against the
            // token the service sends so a single request starts exactly one full
            // sync, however many times it is seen before the confirmation lands.
            'resync_token_done' => '',
            // Sync performance, measured locally as batches drain (see Sync\Drain).
            'last_batch_ms'     => 0,     // round-trip of the most recent ingest batch
            'avg_batch_ms'      => 0,     // smoothed average batch round-trip
            'sync_batches_total' => 0,    // batches sent since install
            'sync_items_total'  => 0,     // product changes pushed since install
        ];

        return array_merge($defaults, get_option(self::OPTION, []));
    }

    public static function get(string $key, mixed $default = ''): mixed
    {
        return self::all()[$key] ?? $default;
    }

    /** @param array<string,mixed> $values */
    public static function update(array $values): void
    {
        update_option(self::OPTION, array_merge(self::all(), $values));
    }

    /**
     * Post types indexed alongside products, filtered to the ones we support.
     *
     * Allowlisted rather than passed through: this value ends up deciding what gets
     * sent to a public search index, so an unexpected entry must not widen it.
     *
     * @return array<int, string>
     */
    public static function indexedContentTypes(): array
    {
        $chosen = self::get('index_content', []);

        return array_values(array_intersect(
            is_array($chosen) ? array_map('strval', $chosen) : [],
            self::SUPPORTED_CONTENT_TYPES,
        ));
    }

    public static function indexesContent(): bool
    {
        return self::indexedContentTypes() !== [];
    }

    /** Whether this store's plan has no numeric cap (see PLAN_UNLIMITED). */
    public static function hasUnlimitedPlan(): bool
    {
        return (int) self::get('product_limit', 0) >= self::PLAN_UNLIMITED;
    }

    public static function isConnected(): bool
    {
        return (bool) self::get('connected', false) && self::get('sync_key_id') !== '';
    }

    /**
     * Search-ready: connected AND we hold the scoped search key. The store is only
     * search-ready once it has passed proof-of-control (verification) and we've
     * fetched its key — connect alone provisions only a shell.
     */
    public static function hasSearchKey(): bool
    {
        return self::isConnected() && self::get('scoped_search_key') !== '';
    }

    /** Stable per-install id, generated once and persisted (used for key binding). */
    public static function installId(): string
    {
        $id = self::get('install_id');
        if ($id === '') {
            $id = wp_generate_uuid4();
            self::update(['install_id' => $id]);
        }

        return (string) $id;
    }

    public static function apiUrl(): string
    {
        return rtrim(NITROSEARCH_API_URL, '/');
    }
}
