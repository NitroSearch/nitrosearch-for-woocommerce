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
            'verified'          => false, // proof-of-control passed (from /v1/status)
            'index_content'     => [],    // e.g. ['page','post'] — see the note above
            'product_limit'     => 0,     // plan cap (from /v1/status)
            'product_count'     => 0,     // products in the engine so far (from /v1/status)
            'at_limit'          => false, // catalogue has hit the plan cap (from /v1/status)
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
