<?php

namespace NitroSearch\Sync;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Background, chunked, resumable full-catalogue re-enqueue.
 *
 * A first sync (or a manual "Sync now") must NOT enumerate the whole catalogue in
 * the merchant's admin request. Pulling every product id at once and doing tens of
 * thousands of inline inserts on a large store risks a big memory allocation and
 * blowing past max_execution_time — which on a cheap shared host can fatal the
 * wp-admin page mid-run with no way to resume. Instead we page through published
 * product ids with a KEYSET cursor (WHERE ID > last) via Action Scheduler, enqueue
 * each page in a single multi-row insert, and persist the cursor so a stalled run
 * resumes from where it stopped rather than restarting. The admin request only reads
 * a cached count and schedules the first chunk, so it returns instantly regardless
 * of catalogue size.
 */
final class FullSync
{
    public const HOOK = 'nitrosearch_full_sync_chunk';

    /** Published product ids enqueued per background chunk (one multi-row insert). */
    public const CHUNK = 500;

    private const OPT = 'nitrosearch_fullsync';

    public static function register(): void
    {
        add_action(self::HOOK, [self::class, 'runChunk'], 10, 1);
    }

    /**
     * Begin — or resume — a background full sync. Returns the number of published
     * products it will queue (a cached count, cheap). The enumeration + enqueue then
     * runs entirely in Action Scheduler, never in this request.
     */
    public static function start(): int
    {
        $state = self::state();

        // Already running (the merchant clicked twice, or a prior run stalled): resume
        // from the persisted cursor instead of restarting from zero — but don't pile on
        // a duplicate chain if a chunk is already scheduled (a double-click).
        if ($state['active']) {
            if (! self::hasScheduledChunk()) {
                self::scheduleChunk($state['cursor']);
            }
            Drain::schedule();

            return $state['total'];
        }

        $total = self::countPublished();
        update_option(self::OPT, [
            'active' => true,
            'cursor' => 0,
            'total' => $total,
            'started' => current_time('mysql', true),
        ], false);

        if (! self::hasScheduledChunk()) {
            self::scheduleChunk(0);
        }
        // Start draining in parallel so products begin syncing immediately; the drain
        // governs its own (heavier) host load via its self-throttle.
        Drain::schedule();
        Drain::enqueueImmediate();

        return $total;
    }

    /**
     * Enqueue one keyset page of published product ids, then schedule the next chunk
     * (or finish). Idempotent: the outbox upsert coalesces, so re-running a chunk
     * (after a crash, or a double-click resume) never duplicates or loses work.
     *
     * @param int $afterId keyset cursor — enqueue published products with ID > this.
     */
    public static function runChunk($afterId = 0): void
    {
        if (! self::state()['active']) {
            return; // cancelled (disconnected) while the chunk was queued
        }

        $ids = self::pageIds((int) $afterId, self::CHUNK);

        if (! $ids) {
            self::finish();

            return;
        }

        Outbox::enqueueMany('product', $ids, 'upsert');

        $cursor = (int) max($ids);
        self::advance($cursor);

        if (count($ids) < self::CHUNK) {
            self::finish();

            return;
        }

        self::scheduleChunk($cursor);
    }

    /** Whether a background full sync is currently running. */
    public static function isActive(): bool
    {
        return self::state()['active'];
    }

    /**
     * Re-arm a background full sync whose chunk chain was lost (Action Scheduler does
     * NOT auto-retry, so a fatal inside a chunk leaves the run marked active with a
     * stale cursor and nothing scheduled). Called from the Drain heartbeat so a stalled
     * enumeration resumes within a drain cycle instead of waiting for a manual re-click.
     */
    public static function resumeIfStalled(): void
    {
        if (! self::state()['active'] || self::hasScheduledChunk()) {
            return;
        }
        self::scheduleChunk(self::state()['cursor']);
    }

    /** @return array{active:bool,cursor:int,total:int,started:string} */
    public static function state(): array
    {
        $state = get_option(self::OPT, []);
        if (! is_array($state)) {
            $state = [];
        }

        return [
            'active' => ! empty($state['active']),
            'cursor' => (int) ($state['cursor'] ?? 0),
            'total' => (int) ($state['total'] ?? 0),
            'started' => (string) ($state['started'] ?? ''),
        ];
    }

    /** Stop any in-flight run and clear its scheduled chunks (disconnect/deactivate). */
    public static function cancel(): void
    {
        $state = self::state();
        if ($state['active']) {
            $state['active'] = false;
            update_option(self::OPT, $state, false);
        }
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions(self::HOOK, [], 'nitrosearch');
        }
    }

    private static function advance(int $cursor): void
    {
        $state = self::state();
        $state['cursor'] = $cursor;
        update_option(self::OPT, $state, false);
    }

    private static function finish(): void
    {
        $state = self::state();
        $state['active'] = false;
        update_option(self::OPT, $state, false);

        // Make sure everything just queued actually ships.
        Drain::schedule();
        Drain::enqueueImmediate();
    }

    private static function scheduleChunk(int $afterId): void
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::HOOK, [$afterId], 'nitrosearch');

            return;
        }

        self::runInlineBounded($afterId);
    }

    /**
     * Fallback for a host without Action Scheduler (WooCommerce bundles it, so this is
     * only defensive). Enumerate inline but under a strict TIME BUDGET and WITHOUT
     * re-entrant scheduling — never recurse/loop through the whole catalogue, which
     * would reintroduce the very OOM / max_execution_time fatal this class exists to
     * prevent. A large catalogue simply continues on the merchant's next admin action
     * (the run stays `active` at the advanced cursor).
     */
    private static function runInlineBounded(int $afterId): void
    {
        $deadline = microtime(true) + 10.0; // ~10s of inline work, then yield
        $cursor = $afterId;

        do {
            if (! self::state()['active']) {
                return;
            }
            $ids = self::pageIds($cursor, self::CHUNK);
            if (! $ids) {
                self::finish();

                return;
            }
            Outbox::enqueueMany('product', $ids, 'upsert');
            $cursor = (int) max($ids);
            self::advance($cursor);
            if (count($ids) < self::CHUNK) {
                self::finish();

                return;
            }
        } while (microtime(true) < $deadline);

        // Budget exhausted: leave `active` at the advanced cursor for a later continue.
    }

    /** Whether a full-sync chunk action is currently queued in Action Scheduler. */
    private static function hasScheduledChunk(): bool
    {
        return function_exists('as_next_scheduled_action')
            && as_next_scheduled_action(self::HOOK, null, 'nitrosearch') !== false;
    }

    /**
     * One keyset page of published product ids after $afterId, ascending. Keyset (not
     * offset) so it stays O(page) on a huge catalogue and is stable under concurrent
     * inserts. Ids only — no post/term/meta hydration (that is the drain's job).
     *
     * @return array<int,int>
     */
    private static function pageIds(int $afterId, int $limit): array
    {
        global $wpdb;

        $where = static function (string $sql) use ($afterId, $wpdb): string {
            return $sql.$wpdb->prepare(" AND {$wpdb->posts}.ID > %d", $afterId);
        };

        add_filter('posts_where', $where);
        try {
            $query = new \WP_Query([
                'post_type' => 'product',
                'post_status' => 'publish',
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
                'posts_per_page' => $limit,
                'no_found_rows' => true,
                'cache_results' => false,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'suppress_filters' => false,
            ]);
        } finally {
            // ALWAYS detach the cursor filter, even if WP_Query (or a callback it fires)
            // throws — otherwise the " AND ID > N" clause would leak into every later
            // post query in the same Action Scheduler worker process (silent corruption).
            remove_filter('posts_where', $where);
        }

        return array_map('intval', $query->posts);
    }

    private static function countPublished(): int
    {
        $counts = wp_count_posts('product');

        return (int) ($counts->publish ?? 0);
    }
}
