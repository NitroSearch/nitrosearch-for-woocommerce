<?php

namespace NitroSearch\Sync;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Keyset paging over published post ids, shared by everything that has to walk a
 * whole site a page at a time.
 *
 * Keyset (WHERE ID > last) rather than OFFSET so it stays O(page) on a huge
 * catalogue and is stable under concurrent inserts — an offset cursor silently
 * skips rows when something is published behind it. Ids only, with every cache
 * warm-up switched off: hydrating posts, terms and meta here would defeat the
 * point, which is to enumerate cheaply and let the drain do the heavy work.
 */
final class PostPager
{
    /**
     * One page of published ids of $postType after $afterId, ascending.
     *
     * @return array<int, int>
     */
    public static function publishedIds(int $afterId, int $limit, string $postType): array
    {
        global $wpdb;

        $where = static function (string $sql) use ($afterId, $wpdb): string {
            return $sql.$wpdb->prepare(" AND {$wpdb->posts}.ID > %d", $afterId);
        };

        add_filter('posts_where', $where);
        try {
            $query = new \WP_Query([
                'post_type' => $postType,
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
            // ALWAYS detach the cursor filter, even if WP_Query (or a callback it
            // fires) throws — otherwise the " AND ID > N" clause would leak into
            // every later post query in the same worker process (silent corruption).
            remove_filter('posts_where', $where);
        }

        return array_map('intval', $query->posts);
    }

    /** Published items of these types, for the "syncing N items" counts. */
    public static function countPublished(array $postTypes): int
    {
        $total = 0;
        foreach ($postTypes as $postType) {
            $counts = wp_count_posts((string) $postType);
            $total += (int) ($counts->publish ?? 0);
        }

        return $total;
    }
}
