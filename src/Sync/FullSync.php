<?php

namespace NitroSearch\Sync;

use NitroSearch\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Background, chunked, resumable full-site re-enqueue.
 *
 * A first sync (or a manual "Sync now") must NOT enumerate the whole site in the
 * merchant's admin request. Pulling every id at once and doing tens of thousands
 * of inline inserts on a large store risks a big memory allocation and blowing
 * past max_execution_time — which on a cheap shared host can fatal the wp-admin
 * page mid-run with no way to resume. Instead we page through published ids with
 * a KEYSET cursor (WHERE ID > last) via Action Scheduler, enqueue each page in a
 * single multi-row insert, and persist the cursor so a stalled run resumes from
 * where it stopped rather than restarting. The admin request only reads a cached
 * count and schedules the first chunk, so it returns instantly regardless of size.
 */
final class FullSync
{
    public const HOOK = 'nitrosearch_full_sync_chunk';

    /** Published ids enqueued per background chunk (one multi-row insert). */
    public const CHUNK = 500;

    private const OPT = 'nitrosearch_fullsync';

    public static function register(): void
    {
        add_action(self::HOOK, [self::class, 'runChunk'], 10, 2);
    }

    /**
     * Begin — or resume — a background full sync. Returns the number of published
     * items it will queue (a cached count, cheap). The enumeration + enqueue then
     * runs entirely in Action Scheduler, never in this request.
     *
     * @param  array<int, string>  $onlyPhases  when given, walk ONLY these types.
     *                                          Used when a merchant switches a
     *                                          content type on: the catalogue is
     *                                          already indexed, and re-enumerating
     *                                          it would put the entire store back
     *                                          through the merchant's own host to
     *                                          add a handful of pages.
     */
    public static function start(array $onlyPhases = []): int
    {
        $state = self::state();

        // Already running (the merchant clicked twice, or a prior run stalled): resume
        // from the persisted cursor instead of restarting from zero — but don't pile on
        // a duplicate chain if a chunk is already scheduled (a double-click). A type
        // enabled just now is simply not in `done`, so the run in flight picks it up
        // when the current phase finishes; there is nothing to schedule here.
        if ($state['active']) {
            if (! self::hasScheduledChunk()) {
                self::scheduleChunk($state['cursor'], $state['phase']);
            }
            Drain::schedule();

            return $state['total'];
        }

        // A targeted run starts with every OTHER phase already marked done, so the
        // walk covers exactly the types asked for and nothing else.
        $done = $onlyPhases === []
            ? []
            : array_values(array_diff(self::canonicalPhases(), $onlyPhases));

        $first = self::pendingPhase($done);
        if ($first === null) {
            return 0;   // nothing enabled left to walk
        }

        // For a whole-site run this is the product count, which is what the admin
        // notice quotes ("syncing N products, then your pages and posts"); for a
        // targeted run it is the count of just the types being added.
        $total = PostPager::countPublished($onlyPhases === [] ? ['product'] : $onlyPhases);

        update_option(self::OPT, [
            'active' => true,
            'phase' => $first,
            'cursor' => 0,
            'done' => $done,
            'total' => $total,
            'started' => current_time('mysql', true),
        ], false);

        if (! self::hasScheduledChunk()) {
            self::scheduleChunk(0, $first);
        }
        // Start draining in parallel so items begin syncing immediately; the drain
        // governs its own (heavier) host load via its self-throttle.
        Drain::schedule();
        Drain::enqueueImmediate();

        return $total;
    }

    /**
     * Enqueue one keyset page of published ids, then schedule the next chunk (or
     * move to the next type, or finish). Idempotent: the outbox upsert coalesces, so
     * re-running a chunk (after a crash, or a double-click resume) never duplicates
     * or loses work.
     *
     * @param  int  $afterId  keyset cursor — enqueue published items with ID > this.
     */
    public static function runChunk($afterId = 0, $phase = 'product'): void
    {
        $state = self::state();

        if (! $state['active']) {
            return; // cancelled (disconnected) while the chunk was queued
        }

        // Ignore an action left over from a phase we have already moved past: its
        // cursor belongs to a different id sequence, so paging with it would skip
        // items in the current phase.
        if ((string) $phase !== $state['phase']) {
            return;
        }

        // Switched off while this chunk sat in the queue. Enqueuing it anyway is not
        // unsafe (the serializer refuses a disabled type and the drain turns that into
        // a delete) but it is a whole pointless round trip through the merchant's host
        // and ours, and ContentPurge is already removing this type.
        if (! self::isPhaseEnabled($state['phase'])) {
            self::completePhase($state['phase']);

            return;
        }

        $ids = PostPager::publishedIds((int) $afterId, self::CHUNK, $state['phase']);

        if ($ids && count($ids) === self::CHUNK) {
            Outbox::enqueueMany($state['phase'], $ids, 'upsert');
            $cursor = (int) max($ids);
            self::advance($cursor);
            self::scheduleChunk($cursor, $state['phase']);

            return;
        }

        if ($ids) {
            Outbox::enqueueMany($state['phase'], $ids, 'upsert');
        }

        self::completePhase($state['phase']);
    }

    /**
     * Mark a phase finished and move to the next one still outstanding, or end the
     * run when there is none.
     */
    private static function completePhase(string $phase): void
    {
        $state = self::state();
        $done = $state['done'];
        if (! in_array($phase, $done, true)) {
            $done[] = $phase;
        }

        $next = self::pendingPhase($done);

        if ($next === null) {
            self::finish($done);

            return;
        }

        $state['phase'] = $next;
        $state['cursor'] = 0;
        $state['done'] = $done;
        update_option(self::OPT, $state, false);

        self::scheduleChunk(0, $next);
    }

    /**
     * The canonical order a full sync walks the site: products, THEN content.
     *
     * This ordering is load-bearing, not cosmetic. Pages and blog posts consume the
     * same plan allowance as products, and WordPress ids are chronological — a blog
     * predates the shop, so paging the whole site by id would enqueue years of posts
     * before the first product and spend a small store's entire allowance on them.
     * Syncing products to completion first means the catalogue always claims its
     * capacity, whatever is left over goes to content, and the merchant never has to
     * think about it. (The backend enforces the same priority independently, since a
     * server cannot trust a client to be well behaved.)
     *
     * @return array<int, string>
     */
    private static function canonicalPhases(): array
    {
        return array_merge(['product'], Settings::SUPPORTED_CONTENT_TYPES);
    }

    /**
     * The next type to walk: the first in CANONICAL order that is switched on and
     * that this run has not already finished.
     *
     * Driven by a list of completed phases rather than by the position of the current
     * one, because the enabled list can change mid-run in both directions. Walking
     * merely forward from the current phase meant a type ticked while a later phase
     * was in flight was never enumerated — the run reached its end and finished,
     * silently leaving that content unindexed until the next full sync.
     *
     * @param  array<int, string>  $done
     */
    private static function pendingPhase(array $done): ?string
    {
        foreach (self::canonicalPhases() as $phase) {
            if (! in_array($phase, $done, true) && self::isPhaseEnabled($phase)) {
                return $phase;
            }
        }

        return null;
    }

    /** Products are always indexed; content types only when the merchant says so. */
    private static function isPhaseEnabled(string $phase): bool
    {
        return $phase === 'product'
            || in_array($phase, Settings::indexedContentTypes(), true);
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
        $state = self::state();
        self::scheduleChunk($state['cursor'], $state['phase']);
    }

    /** @return array{active:bool,phase:string,done:array<int,string>,cursor:int,total:int,started:string} */
    public static function state(): array
    {
        $state = get_option(self::OPT, []);
        if (! is_array($state)) {
            $state = [];
        }

        // Which post type this run is currently paging. Products are ALWAYS first
        // (see canonicalPhases()); an older state with no phase is a products-only run.
        $phase = (string) ($state['phase'] ?? 'product');

        return [
            'active' => ! empty($state['active']),
            'phase' => $phase,
            // Phases this run has finished. A run started before this key existed
            // walked strictly forward, so everything ahead of its current phase is
            // done by definition — synthesising that keeps an in-flight upgrade from
            // rewinding to the top of the catalogue.
            'done' => isset($state['done']) && is_array($state['done'])
                ? array_values(array_intersect(array_map('strval', $state['done']), self::canonicalPhases()))
                : self::phasesBefore($phase),
            'cursor' => (int) ($state['cursor'] ?? 0),
            'total' => (int) ($state['total'] ?? 0),
            'started' => (string) ($state['started'] ?? ''),
        ];
    }

    /** @return array<int, string> */
    private static function phasesBefore(string $phase): array
    {
        $canonical = self::canonicalPhases();
        $at = array_search($phase, $canonical, true);

        return $at === false ? [] : array_slice($canonical, 0, (int) $at);
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

    /** @param array<int, string> $done */
    private static function finish(array $done): void
    {
        $state = self::state();
        $state['active'] = false;
        $state['done'] = $done;
        update_option(self::OPT, $state, false);

        // Make sure everything just queued actually ships.
        Drain::schedule();
        Drain::enqueueImmediate();
    }

    private static function scheduleChunk(int $afterId, string $phase = 'product'): void
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::HOOK, [$afterId, $phase], 'nitrosearch');

            return;
        }

        self::runInlineBounded($afterId, $phase);
    }

    /**
     * Fallback for a host without Action Scheduler (WooCommerce bundles it, so this is
     * only defensive). Enumerate inline but under a strict TIME BUDGET and WITHOUT
     * re-entrant scheduling — never recurse/loop through the whole site, which would
     * reintroduce the very OOM / max_execution_time fatal this class exists to
     * prevent. A large site simply continues on the merchant's next admin action (the
     * run stays `active` at the advanced cursor and phase).
     *
     * It walks the SAME phase sequence as the scheduled path. Written for products
     * only, it enqueued the catalogue, called finish(), and left every page and post
     * unindexed with the run reporting itself complete.
     */
    private static function runInlineBounded(int $afterId, string $phase): void
    {
        $deadline = microtime(true) + 10.0; // ~10s of inline work, then yield
        $cursor = $afterId;

        do {
            $state = self::state();
            if (! $state['active']) {
                return;
            }

            if (! self::isPhaseEnabled($phase)) {
                $ids = [];
            } else {
                $ids = PostPager::publishedIds($cursor, self::CHUNK, $phase);
            }

            if ($ids) {
                Outbox::enqueueMany($phase, $ids, 'upsert');
                $cursor = (int) max($ids);
                self::advance($cursor);
            }

            if (count($ids) === self::CHUNK) {
                continue;   // same phase, next page
            }

            // Phase exhausted. completePhase() advances the persisted state and, with
            // no Action Scheduler, calls straight back into here for the next type —
            // so keep going in THIS loop instead, under the same time budget.
            $done = $state['done'];
            if (! in_array($phase, $done, true)) {
                $done[] = $phase;
            }

            $next = self::pendingPhase($done);
            if ($next === null) {
                self::finish($done);

                return;
            }

            $state['phase'] = $next;
            $state['cursor'] = 0;
            $state['done'] = $done;
            update_option(self::OPT, $state, false);

            $phase = $next;
            $cursor = 0;
        } while (microtime(true) < $deadline);

        // Budget exhausted: leave `active` at the advanced cursor for a later continue.
    }

    /** Whether a full-sync chunk action is currently queued in Action Scheduler. */
    private static function hasScheduledChunk(): bool
    {
        return function_exists('as_next_scheduled_action')
            && as_next_scheduled_action(self::HOOK, null, 'nitrosearch') !== false;
    }
}
