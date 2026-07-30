<?php

namespace NitroSearch\Sync;

use NitroSearch\Api\Client;
use NitroSearch\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Search→order attribution, entirely inside the store's OWN session.
 *
 * When the search widget adds to cart it marks the wc-ajax request
 * (`ns_search=1`, `ns_q=<query>`); WooCommerce fires `woocommerce_add_to_cart`
 * during that same request, so we stash {product → query, time} in the WC
 * session — the store's first-party session, never ours, and nothing beyond
 * the marker leaves the browser. When an order completes, items added via
 * search within the last 7 days make up the ATTRIBUTED slice: its value (in
 * minor units) and a hashed order reference are reported to NitroSearch on
 * the signed server channel — the real order id never leaves the site, and
 * shopper details are never part of the payload.
 *
 * Honours the "Share search usage data" toggle: off = no session marking, no
 * reporting. Reporting is async (a scheduled single event) so checkout is
 * never slowed or failed by it.
 */
final class OrderAttribution
{
    private const SESSION_KEY = 'nitrosearch_attr';

    private const WINDOW_SECONDS = 7 * DAY_IN_SECONDS;

    private const MAX_TRACKED = 50;

    public static function register(): void
    {
        add_action('woocommerce_add_to_cart', [self::class, 'captureAdd'], 10, 6);
        // Classic checkout and the blocks (Store API) checkout complete on
        // different hooks; the handler is idempotent per order either way.
        add_action('woocommerce_checkout_order_processed', [self::class, 'orderCompleted'], 20, 1);
        add_action('woocommerce_store_api_checkout_order_processed', [self::class, 'orderCompleted'], 20, 1);
        add_action('nitrosearch_report_order', [Client::class, 'reportOrder'], 10, 1);
    }

    /**
     * Runs inside the widget's wc-ajax add-to-cart request. Marks the product
     * as search-added in the WC session when the widget said so.
     */
    public static function captureAdd(string $cartItemKey, int $productId, int $quantity, int $variationId, array $variation, array $cartItemData): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only marker on WC's own add-to-cart request; no state change beyond a session note, and WC has already authorized the add itself.
        if (! isset($_POST['ns_search']) || ! Settings::get('share_search_data', true) || ! function_exists('WC') || WC()->session === null) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above; value is sanitized and truncated.
        $q = isset($_POST['ns_q']) ? mb_substr(sanitize_text_field(wp_unslash($_POST['ns_q'])), 0, 128) : '';

        $attr = (array) WC()->session->get(self::SESSION_KEY, []);
        $attr[(string) $productId] = ['q' => $q, 't' => time()];
        if (count($attr) > self::MAX_TRACKED) {
            $attr = array_slice($attr, -self::MAX_TRACKED, null, true);
        }
        WC()->session->set(self::SESSION_KEY, $attr);
    }

    /** On order completion: compute the attributed slice, schedule the report. */
    public static function orderCompleted($order): void
    {
        if (! Settings::get('share_search_data', true) || ! Settings::isConnected()) {
            return;
        }
        if (is_numeric($order)) {
            $order = wc_get_order((int) $order);
        }
        if (! $order instanceof \WC_Order || ! function_exists('WC') || WC()->session === null) {
            return;
        }

        $attr = (array) WC()->session->get(self::SESSION_KEY, []);
        if ($attr === []) {
            return;
        }

        $cutoff = time() - self::WINDOW_SECONDS;
        $valueCents = 0;
        $itemIds = [];
        $query = '';
        foreach ($order->get_items() as $item) {
            if (! $item instanceof \WC_Order_Item_Product) {
                continue;
            }
            $pid = (string) $item->get_product_id();
            if (! isset($attr[$pid]) || (int) $attr[$pid]['t'] < $cutoff) {
                continue;
            }
            $valueCents += (int) round(((float) $item->get_total()) * 100);
            $itemIds[] = $pid;
            if ($query === '' && $attr[$pid]['q'] !== '') {
                $query = (string) $attr[$pid]['q'];
            }
            unset($attr[$pid]);   // consumed — a second order never re-attributes it
        }

        WC()->session->set(self::SESSION_KEY, $attr);

        if ($itemIds === []) {
            return;
        }

        // Async on purpose: reporting must never slow or fail a checkout.
        wp_schedule_single_event(time() + 30, 'nitrosearch_report_order', [[
            'order_id' => $order->get_id(),
            'value_cents' => $valueCents,
            'currency' => (string) $order->get_currency(),
            'occurred_at' => $order->get_date_created() ? $order->get_date_created()->format('c') : gmdate('c'),
            'item_ids' => $itemIds,
            'q' => $query,
        ]]);
    }
}
