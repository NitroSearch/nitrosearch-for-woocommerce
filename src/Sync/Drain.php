<?php

namespace NitroSearch\Sync;

use NitroSearch\Api\Client;
use NitroSearch\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Drains the outbox in batches on a recurring Action Scheduler action (never raw
 * WP-Cron alone). Builds one signed request per batch and, on success, removes
 * exactly the rows it sent (compare-and-delete on version, so an edit that
 * landed mid-flight is preserved and re-sent).
 */
final class Drain
{
    public const HOOK = 'nitrosearch_drain';
    public const BATCH = 100;

    /** Rest for ~this fraction of the last batch's own duration before chaining. */
    private const DUTY_CYCLE = 0.5;

    /** Never hold the Action Scheduler worker sleeping longer than this. */
    private const MAX_PAUSE_MS = 2000;

    /** Stop chaining back-to-back batches once we're using this share of memory_limit. */
    private const MEMORY_HEADROOM = 0.75;

    /** Wall-clock ms the most recent batch's ingest round-trip took (self-throttle input). */
    private static int $lastElapsedMs = 0;

    public static function register(): void
    {
        add_action(self::HOOK, [self::class, 'run']);
    }

    public static function schedule(): void
    {
        if (function_exists('as_has_scheduled_action')
            && function_exists('as_schedule_recurring_action')
            && ! as_has_scheduled_action(self::HOOK)) {
            as_schedule_recurring_action(time() + 10, 60, self::HOOK, [], 'nitrosearch');
        }
    }

    public static function run(): void
    {
        if (! Settings::isConnected()) {
            return;
        }

        // Watchdog: if a background full sync stalled (a lost chunk action — Action
        // Scheduler does not auto-retry), re-arm it. Cheap no-op unless one is active
        // and unscheduled, so it just makes "resumable" actually resume unattended.
        FullSync::resumeIfStalled();

        // Daily config refresh (search key + widget URLs) — a cheap timestamp
        // check on every heartbeat, one small request a day when due.
        ConfigRefresh::maybeRun();

        // Periodic status check (every few minutes when due) — keeps plan/limit/count
        // current and is how the service asks this store to send its catalogue again.
        ResyncCheck::maybeRun();

        // Self-pacing: sync throughput is governed by how fast the outbox drains, not
        // by the fixed 60s heartbeat. When a full batch went through and a backlog
        // remains, chain an IMMEDIATE async follow-up so a large catalogue (a first
        // full sync) drains fast — instead of one batch per minute. The chain is linear
        // (one follow-up per successful full batch) and self-terminates the moment the
        // queue empties, the batch comes back partial, or a send fails (e.g. a 429 rate
        // limit) — after which the 60s heartbeat resumes and only bounds idle latency.
        if (self::drainOnce() !== 'full' || Outbox::pendingCount() <= 0) {
            return;
        }

        // Memory-headroom guard: a batch of wide variable products can hydrate a lot of
        // WC_Product objects. If this process is already near its PHP memory_limit, do
        // NOT chain another back-to-back batch — let the 60s heartbeat resume the work
        // in a FRESH process instead of risking an OOM that kills the drain mid-flight.
        if (! self::hasMemoryHeadroom()) {
            return;
        }

        // Self-throttle (be a polite guest): rest for a slice of the time this batch
        // itself took before chaining the next one, so a first full sync uses at most
        // ~half the wall-clock on the MERCHANT's own host rather than pegging it
        // back-to-back. Adaptive + config-free — a fast host barely pauses, a slow or
        // loaded one rests more — and capped so we never hold the worker too long. This
        // governs load locally instead of relying on the backend's 429 (which never
        // fires on a host slower than the server-side rate ceiling).
        self::throttle();

        self::enqueueImmediate();
    }

    /**
     * Drain a single batch.
     *
     * @return string one of: 'empty' (nothing pending), 'partial' (backlog drained),
     *                'full' (a full batch sent — more likely waiting), 'error' (send failed).
     */
    private static function drainOnce(): string
    {
        $rows = Outbox::claim(self::BATCH);
        if (! $rows) {
            return 'empty';
        }

        $items = [];
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row->id;
            $op = $row->op;

            if ($op === 'upsert') {
                // Dispatch on the queued type. A null return means "must not be
                // indexed" — gone, unpublished, password-protected, excluded by an
                // SEO plugin or by a membership plugin's filter — and it becomes a
                // DELETE so anything that stopped being public leaves the index
                // rather than lingering in it.
                $data = $row->object_type === 'product'
                    ? ProductSerializer::serialize((int) $row->object_id)
                    : ContentSerializer::serialize((int) $row->object_id);

                if ($data === null) {
                    $op = 'delete';
                    $data = ['id' => (int) $row->object_id];
                } else {
                    // Always state the type, from the queue row that already knows it.
                    // The content serializer set it; the product one never did, so a
                    // product went over the wire with no type at all and the backend
                    // fell back to "whatever this id was last time". That default is
                    // the compatibility rule for plugins older than this feature, not
                    // something a current plugin should be leaning on: it made a
                    // mistyped row permanent, because no product upsert could ever
                    // correct it, and the document would sit in the wrong section of
                    // the dropdown forever.
                    $data['object_type'] = $row->object_type;
                }
            } else {
                $data = ['id' => (int) $row->object_id];
            }

            $items[] = ['op' => $op, 'version' => (int) $row->version, 'data' => $data];
        }

        $startedAt = microtime(true);
        $result = Client::ingestBatch($items);
        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        self::$lastElapsedMs = $elapsedMs;

        if (! $result['ok']) {
            Outbox::release($ids);   // leave them pending for the next tick
            // Stored, then shown later under the translated "Last error" label —
            // kept locale-neutral (status + detail) so it can't go stale in the
            // locale that happened to be active when the sync ran.
            Settings::update(['last_error' => 'HTTP '.($result['code'] ?? 0).': '.($result['error'] ?? '')]);

            return 'error';
        }

        foreach ($rows as $row) {
            Outbox::complete((int) $row->id, (int) $row->version);
        }
        self::recordBatchPerformance($elapsedMs, count($items));
        Settings::update(['last_sync' => current_time('mysql', true), 'last_error' => '']);

        return count($rows) >= self::BATCH ? 'full' : 'partial';
    }

    /**
     * Enqueue an immediate one-off drain via Action Scheduler so a backlog keeps
     * draining without waiting for the next 60s heartbeat. A no-op if Action Scheduler
     * isn't available (the recurring heartbeat still covers it). Public so the chunked
     * full sync can kick a drain as it queues products.
     */
    public static function enqueueImmediate(): void
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::HOOK, [], 'nitrosearch');
        }
    }

    /**
     * Rest for a slice of the last batch's own duration (a ~50% duty cycle), capped, so
     * back-to-back draining leaves the merchant's host headroom for real shopper traffic.
     */
    private static function throttle(): void
    {
        $pauseMs = (int) min(self::MAX_PAUSE_MS, round(self::$lastElapsedMs * self::DUTY_CYCLE));
        if ($pauseMs > 0) {
            usleep($pauseMs * 1000);
        }
    }

    /** True while this process has comfortable headroom below its PHP memory_limit. */
    private static function hasMemoryHeadroom(): bool
    {
        $limit = self::memoryLimitBytes();
        if ($limit <= 0) {
            return true; // unlimited (-1) or unparseable → don't block on memory
        }

        return memory_get_usage(true) < ($limit * self::MEMORY_HEADROOM);
    }

    /** The process memory_limit in bytes; -1 for unlimited/unknown. */
    private static function memoryLimitBytes(): int
    {
        $raw = trim((string) ini_get('memory_limit'));
        if ($raw === '' || $raw === '-1') {
            return -1;
        }
        $value = (int) $raw;

        return match (strtolower(substr($raw, -1))) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Roll the just-drained batch into the local sync-performance stats shown on the
     * admin screen: the round-trip of this batch, a smoothed average, and running
     * totals. `avg_batch_ms` is an exponential moving average (weighting the latest
     * batch ~30%) so it tracks recent performance without storing a history.
     */
    private static function recordBatchPerformance(int $elapsedMs, int $items): void
    {
        $prevAvg = (int) Settings::get('avg_batch_ms', 0);
        $avg = $prevAvg > 0 ? (int) round(($prevAvg * 0.7) + ($elapsedMs * 0.3)) : $elapsedMs;

        Settings::update([
            'last_batch_ms'      => $elapsedMs,
            'avg_batch_ms'       => $avg,
            'sync_batches_total' => (int) Settings::get('sync_batches_total', 0) + 1,
            'sync_items_total'   => (int) Settings::get('sync_items_total', 0) + $items,
        ]);
    }
}
