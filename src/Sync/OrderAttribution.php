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
 * reporting.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * REPORTS ARE NOW DELIVERED, NOT MERELY ATTEMPTED (2026-08-10).
 *
 * Until this change an order got exactly ONE chance to be reported. Delivery
 * was a single wp_schedule_single_event() thirty seconds after checkout, whose
 * callback was Api\Client::reportOrder() — a method that posted the payload and
 * read nothing back, not the status code, not a transport error. It returned
 * cleanly whatever happened, WP-Cron marked the event done, and the order was
 * gone. There was no queue, no attempt count and no record: a merchant looking
 * at a revenue figure that was quietly missing orders had nothing anywhere to
 * tell them so.
 *
 * ANY single failure did it — a proxy 502, a DNS blip, a request that ran past
 * the ten-second timeout — but the one that costs real money is the throttle.
 * The orders endpoint accepts 60 reports a minute per store, so a flash sale
 * that bursts past one order a second loses every order over the line. The
 * busiest hour of the year is the hour that reports the least revenue, and it
 * is the hour a merchant uses to judge whether search is earning its keep.
 *
 * So delivery is now: send → read the answer → on a retryable answer, schedule
 * the SAME payload again with a widening delay, up to MAX_ATTEMPTS, then give
 * up and record why. Three properties make that safe:
 *
 *  1. THE PAYLOAD IS FROZEN AT CHECKOUT. `occurred_at` is stamped once, here,
 *     and re-sent byte-identical on every attempt. The service dedupes on
 *     (store, order_ref, occurred_at), so at-least-once delivery of a frozen
 *     tuple lands exactly once however many attempts it takes. A timestamp
 *     re-derived at send time would instead insert a fresh conversion row per
 *     attempt and OVERSTATE the merchant's revenue — the failure mode opposite
 *     to the one being fixed, and the worse of the two.
 *  2. IT IS BOUNDED IN BOTH DIRECTIONS. MAX_ATTEMPTS bounds one report (six
 *     tries over roughly nine hours, then abandoned); MAX_QUEUED bounds how
 *     many reports may be waiting at once, so a shop whose backend is
 *     permanently unreachable cannot pile scheduled work up without limit.
 *  3. NOTHING HERE TOUCHES THE NETWORK ON THE CHECKOUT REQUEST. The only
 *     outbound call in this feature is Client::reportOrder(), reachable only
 *     from dispatchReport(), which only ever runs on a background worker.
 *     Everything on the shopper's own request is a session read, a session
 *     write and one scheduler insert — and each of the two checkout-path entry
 *     points is sealed in `catch (\Throwable)`, so no fault of any kind here
 *     can surface in a checkout.
 *
 * WHY ACTION SCHEDULER RATHER THAN RAW WP-CRON. The outbox drain already runs
 * on it (see Sync\Drain), WooCommerce is a hard requirement of this plugin and
 * bundles it, and it is the better queue for work that must not be lost: its
 * actions are table rows rather than entries in a single serialised `cron`
 * option, so they survive the lost-update race that option is prone to when
 * several requests schedule work at once; it will not silently swallow a second
 * event because a similar one is already due within ten minutes; and every
 * pending, failed and abandoned report is visible to the merchant in
 * WooCommerce → Status → Scheduled Actions, which is the only place a stuck
 * report can be SEEN. wp_schedule_single_event() stays as a fallback for the
 * case where the functions are somehow absent, and the hook name is unchanged
 * so any event queued by an earlier version still lands on the new handler.
 */
final class OrderAttribution
{
    /**
     * The scheduled hook. Deliberately the same string an earlier version used,
     * so events already sitting in a store's queue at upgrade time route to the
     * new handler instead of being orphaned. Their payload has no `attempt` key
     * and is read as attempt 1.
     */
    public const REPORT_HOOK = 'nitrosearch_report_order';

    private const SESSION_KEY = 'nitrosearch_attr';

    private const WINDOW_SECONDS = 7 * DAY_IN_SECONDS;

    private const MAX_TRACKED = 50;

    /**
     * How long to wait before attempt N, indexed from 1 (so BACKOFF[0] is the
     * delay before the first send, which stays at the 30 seconds this feature
     * has always used).
     *
     * Widening, because the failures worth retrying have different shapes: a
     * throttle clears in under a minute, a deploy in a few, an expired card or
     * an unverified store in hours. Roughly nine hours end to end — long enough
     * to ride out an outage, short enough that every attempt is still far inside
     * the eight-day window the service will accept `occurred_at` within, so no
     * retry of ours can be rewritten server-side into a duplicate.
     *
     * @var array<int,int>
     */
    private const BACKOFF = [30, 120, 600, 1800, 7200, 21600];

    /** Attempts before a report is abandoned. One per BACKOFF entry. */
    private const MAX_ATTEMPTS = 6;

    /**
     * Ceiling on reports waiting to be sent at any one moment.
     *
     * A shop whose backend is permanently unreachable — a firewalled host, a
     * revoked key, a store suspended and never resumed — would otherwise add
     * scheduled work per order forever and never remove any. Past this many
     * waiting reports the newest is dropped rather than queued: the alternative
     * is an unbounded scheduler table on the merchant's own database, which is
     * a bigger problem for them than the attribution they are already not
     * getting. Attempts of reports ALREADY accepted are not subject to it —
     * a retry replaces a queue entry rather than adding one, so letting the cap
     * block retries would only convert a full queue into a permanently full one.
     */
    private const MAX_QUEUED = 250;

    public static function register(): void
    {
        add_action('woocommerce_add_to_cart', [self::class, 'captureAdd'], 10, 6);
        // Classic checkout and the blocks (Store API) checkout complete on
        // different hooks; the handler is idempotent per order either way.
        add_action('woocommerce_checkout_order_processed', [self::class, 'orderCompleted'], 20, 1);
        add_action('woocommerce_store_api_checkout_order_processed', [self::class, 'orderCompleted'], 20, 1);
        // The scheduled sender. Was wired straight to Client::reportOrder, which
        // is what made delivery one-shot: there was nothing between the send and
        // the scheduler to notice a failure or ask for another attempt.
        add_action(self::REPORT_HOOK, [self::class, 'dispatchReport'], 10, 1);
    }

    /**
     * Runs inside the widget's wc-ajax add-to-cart request. Marks the product
     * as search-added in the WC session when the widget said so.
     *
     * Sealed: this runs on a shopper's own add-to-cart request, and an add to
     * cart that fails because an analytics marker could not be stored would be
     * a straight loss of a sale.
     */
    public static function captureAdd(string $cartItemKey, int $productId, int $quantity, int $variationId, array $variation, array $cartItemData): void
    {
        try {
            self::mark($productId);
        } catch (\Throwable $e) {
            // Attribution is optional; the shopper's cart is not.
        }
    }

    private static function mark(int $productId): void
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

    /**
     * On order completion: compute the attributed slice, freeze it, queue it.
     *
     * Sealed for the reason the whole feature is asynchronous — a checkout must
     * never be slowed, and must certainly never fail, because of anything this
     * plugin wanted to record about it. The seal covers the scheduler insert as
     * well as the arithmetic: a store with a broken or locked Action Scheduler
     * table must lose an attribution, not an order.
     */
    public static function orderCompleted($order): void
    {
        try {
            self::collect($order);
        } catch (\Throwable $e) {
            // Nothing this class does is worth an order for.
        }
    }

    private static function collect($order): void
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

        $orderId = (int) $order->get_id();
        if ($orderId <= 0) {
            return;
        }

        // The cap is tested only for a NEW report, never for a retry of one
        // already accepted — see MAX_QUEUED. A dropped report is recorded rather
        // than lost silently, because "the figure is low" is otherwise
        // indistinguishable from "nobody searched".
        if (self::queuedCount() >= self::MAX_QUEUED) {
            self::note('order report dropped: '.self::MAX_QUEUED.' reports already waiting to send');

            return;
        }

        // STAMPED EXACTLY ONCE, HERE. Every attempt re-sends this same string;
        // it is half of the key the service dedupes on, so deriving it at send
        // time would make each retry a new conversion row for one order. The
        // order's own creation time is the honest answer for revenue-by-day and
        // does not move between attempts; the gmdate() fallback covers an order
        // with no recorded date and is evaluated once, right here, not on the
        // wire.
        $occurredAt = $order->get_date_created() ? $order->get_date_created()->format('c') : gmdate('c');

        // Async on purpose: reporting must never slow or fail a checkout.
        self::queueReport([
            'order_id' => $orderId,
            'value_cents' => $valueCents,
            'currency' => (string) $order->get_currency(),
            'occurred_at' => $occurredAt,
            'item_ids' => $itemIds,
            'q' => $query,
            'attempt' => 1,
        ], self::BACKOFF[0]);
    }

    /**
     * Send one queued report and decide what happens next. Runs on a background
     * worker (an Action Scheduler queue runner, or WP-Cron on the fallback
     * path) — never on a shopper's request.
     *
     * `attempt` travels in the scheduler args rather than in the payload, and
     * Client::reportOrder() builds its request body from named keys only, so a
     * fourth attempt is byte-identical on the wire to the first. That is what
     * makes retrying safe against a service that dedupes on content.
     *
     * @param  mixed  $report
     */
    public static function dispatchReport($report): void
    {
        try {
            if (! is_array($report)) {
                return;
            }

            $result = Client::reportOrder($report);
            if (empty($result['retry'])) {
                return;   // accepted, refused for good, or nothing to send
            }

            $attempt = max(1, (int) ($report['attempt'] ?? 1));
            if ($attempt >= self::MAX_ATTEMPTS) {
                // Give up, and say so. Roughly nine hours of a service that
                // could not take this order is not a blip, and continuing would
                // trade a lost attribution for an unbounded queue.
                self::note(sprintf(
                    'order report abandoned after %d attempts (HTTP %d: %s)',
                    $attempt,
                    (int) ($result['status'] ?? 0),
                    (string) ($result['error'] ?? '')
                ));

                return;
            }

            $report['attempt'] = $attempt + 1;
            self::queueReport($report, self::BACKOFF[$attempt] ?? self::BACKOFF[count(self::BACKOFF) - 1]);
        } catch (\Throwable $e) {
            // A worker is shared with the outbox drain; a fault reporting one
            // order must not take the store's whole sync queue down with it.
        }
    }

    /**
     * Queue one attempt.
     *
     * Action Scheduler first (the queue the drain already runs on, durable in a
     * table of its own and inspectable by the merchant); wp_schedule_single_event
     * only if it is genuinely unavailable, which on a store meeting this
     * plugin's WooCommerce requirement it is not.
     *
     * @param  array<string,mixed>  $report
     */
    private static function queueReport(array $report, int $delay): void
    {
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time() + $delay, self::REPORT_HOOK, [$report], 'nitrosearch');

            return;
        }

        wp_schedule_single_event(time() + $delay, self::REPORT_HOOK, [$report]);
    }

    /**
     * How many reports are waiting to be sent right now.
     *
     * Counted from the queue itself rather than from a stored tally, because a
     * tally drifts: it is incremented on a checkout request and decremented on a
     * worker, and any missed decrement — a fatal in the worker, a row cleaned up
     * by Action Scheduler's own retention sweep — would leave the counter high
     * forever and silence attribution on a perfectly healthy store. The query is
     * bounded to the cap plus one, because the only question being asked is
     * whether the queue is at it.
     */
    private static function queuedCount(): int
    {
        if (function_exists('as_get_scheduled_actions')) {
            $pending = as_get_scheduled_actions([
                'hook'     => self::REPORT_HOOK,
                'status'   => 'pending',   // ActionScheduler_Store::STATUS_PENDING
                'per_page' => self::MAX_QUEUED + 1,
            ], 'ids');

            return is_array($pending) ? count($pending) : 0;
        }

        if (! function_exists('_get_cron_array')) {
            return 0;
        }

        $count = 0;
        foreach ((array) _get_cron_array() as $due) {
            if (is_array($due) && isset($due[self::REPORT_HOOK])) {
                $count += count((array) $due[self::REPORT_HOOK]);
            }
        }

        return $count;
    }

    /**
     * Record why a report was given up on.
     *
     * Kept OUT of the shared `last_error` the sync status card reads: an
     * attribution fault is not a sync fault, and writing it there would make a
     * perfectly healthy catalogue sync look broken (and would let either one
     * overwrite the other's message). Locale-neutral for the same reason
     * Sync\Drain's is — a stored string outlives the locale that was active when
     * it was written. There is no screen for this yet; it exists so the question
     * "why is this store's attributed revenue low" has an answer on the store
     * rather than only a guess.
     */
    private static function note(string $message): void
    {
        Settings::update(['attribution_last_error' => $message]);
    }
}
