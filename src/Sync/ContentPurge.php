<?php

namespace NitroSearch\Sync;

use NitroSearch\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Removes a content type from the index after the merchant switches it off.
 *
 * Without this, unticking Pages did nothing at all to what was already indexed.
 * The documents stayed, they kept appearing in the storefront dropdown, and they
 * kept consuming the store's allowance — while the settings screen told the
 * merchant that switching them off "frees it up for your catalogue". Worse, the
 * hooks stop tracking a disabled type, so those documents could no longer be
 * updated or deleted by anything: an edit or a trash enqueued nothing, and the
 * stale copy served forever.
 *
 * Built like FullSync and for the same reason: a site can have thousands of
 * posts, so the removal is enumerated a keyset page at a time through Action
 * Scheduler rather than in the merchant's settings-save request.
 */
final class ContentPurge
{
    public const HOOK = 'nitrosearch_content_purge_chunk';

    /** Ids queued per background chunk (one multi-row insert). */
    public const CHUNK = 500;

    public static function register(): void
    {
        add_action(self::HOOK, [self::class, 'runChunk'], 10, 2);
    }

    /**
     * Queue removal of every published item of these types.
     *
     * Published only: anything that had left `publish` was already removed from the
     * index at the moment it did, by the status-transition hook.
     *
     * @param  array<int, string>  $postTypes
     */
    public static function start(array $postTypes): void
    {
        if (! Settings::hasSearchKey()) {
            return;   // nothing was ever indexed
        }

        foreach ($postTypes as $postType) {
            if (! in_array((string) $postType, Settings::SUPPORTED_CONTENT_TYPES, true)) {
                continue;
            }
            self::scheduleChunk(0, (string) $postType);
        }

        Drain::schedule();
        Drain::enqueueImmediate();
    }

    /**
     * Enqueue one keyset page of deletes, then schedule the next chunk.
     *
     * A delete row carries only the id — the drain does not build a document for it —
     * so this works fine even though the serializer now refuses this type. The
     * backend tombstones the mirror row, which is what actually returns the slot to
     * the merchant's allowance.
     */
    public static function runChunk($afterId = 0, $postType = ''): void
    {
        $postType = (string) $postType;

        if (! in_array($postType, Settings::SUPPORTED_CONTENT_TYPES, true)) {
            return;
        }

        // Switched back on while this chunk waited in the queue. Stop: deleting now
        // would fight the full sync that re-enabling started, and the two could
        // interleave into a half-indexed type.
        if (in_array($postType, Settings::indexedContentTypes(), true)) {
            return;
        }

        $ids = PostPager::publishedIds((int) $afterId, self::CHUNK, $postType);
        if (! $ids) {
            return;
        }

        Outbox::enqueueMany($postType, $ids, 'delete');

        if (count($ids) === self::CHUNK) {
            self::scheduleChunk((int) max($ids), $postType);
        }
    }

    /** Stop any queued removal (disconnect/deactivate). */
    public static function cancel(): void
    {
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions(self::HOOK, [], 'nitrosearch');
        }
    }

    private static function scheduleChunk(int $afterId, string $postType): void
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::HOOK, [$afterId, $postType], 'nitrosearch');

            return;
        }

        self::runInlineBounded($afterId, $postType);
    }

    /**
     * Fallback for a host without Action Scheduler. Same bounded-inline contract as
     * FullSync: a strict time budget, no re-entrant scheduling, and no attempt to
     * walk a whole site in one admin request.
     */
    private static function runInlineBounded(int $afterId, string $postType): void
    {
        $deadline = microtime(true) + 10.0;
        $cursor = $afterId;

        do {
            $ids = PostPager::publishedIds($cursor, self::CHUNK, $postType);
            if (! $ids) {
                return;
            }

            Outbox::enqueueMany($postType, $ids, 'delete');
            $cursor = (int) max($ids);

            if (count($ids) < self::CHUNK) {
                return;
            }
        } while (microtime(true) < $deadline);
    }
}
