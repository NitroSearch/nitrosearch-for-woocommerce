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

        // Self-pacing: sync throughput is governed by how fast the outbox drains, not
        // by the fixed 60s heartbeat. When a full batch went through and a backlog
        // remains, chain an IMMEDIATE async follow-up so a large catalogue (a first
        // full sync) drains back-to-back — turning an ~83h/500k crawl (100 items every
        // 60s) into minutes — instead of one batch per minute. The chain is linear (one
        // follow-up per successful full batch), and it self-terminates the moment the
        // queue empties, the batch comes back partial, or a send fails (e.g. a 429 rate
        // limit) — after which the 60s heartbeat resumes and only bounds idle latency.
        if (self::drainOnce() === 'full' && Outbox::pendingCount() > 0) {
            self::enqueueImmediate();
        }
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
                $data = ProductSerializer::serialize((int) $row->object_id);
                if ($data === null) {          // product vanished — send as a delete
                    $op = 'delete';
                    $data = ['id' => (int) $row->object_id];
                }
            } else {
                $data = ['id' => (int) $row->object_id];
            }

            $items[] = ['op' => $op, 'version' => (int) $row->version, 'data' => $data];
        }

        $startedAt = microtime(true);
        $result = Client::ingestBatch($items);
        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $result['ok']) {
            Outbox::release($ids);   // leave them pending for the next tick
            Settings::update(['last_error' => 'HTTP '.($result['code'] ?? 0).' '.($result['error'] ?? '')]);

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
     * isn't available (the recurring heartbeat still covers it).
     */
    private static function enqueueImmediate(): void
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::HOOK, [], 'nitrosearch');
        }
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
