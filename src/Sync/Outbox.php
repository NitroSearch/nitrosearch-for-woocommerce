<?php

namespace NitroSearch\Sync;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The local dirty-queue. Hooks write coalesced rows here (one row per object,
 * last-write-wins) doing zero HTTP and zero payload building; the drain reads
 * them in batches. This is what keeps product saves and checkout fast and makes
 * the sync survive the backend being briefly unreachable.
 */
final class Outbox
{
    // This class is the plugin's private data layer for its OWN custom queue table
    // (wp_nitrosearch_queue). Every method runs direct, prepared queries against it:
    // there is no core WordPress API for a custom table, the table name is
    // $wpdb->prefix + a fixed literal (never user input), all values are bound via
    // $wpdb->prepare(), and object caching is intentionally skipped — this IS the
    // write path. The DB sniffs are therefore disabled class-wide, with that rationale.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

    public static function table(): string
    {
        global $wpdb;

        return $wpdb->prefix.'nitrosearch_queue';
    }

    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH.'wp-admin/includes/upgrade.php';

        $table = self::table();
        $charset = $wpdb->get_charset_collate();

        // One row per (object_type, object_id); coalescing upsert on write.
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            object_type VARCHAR(20) NOT NULL,
            object_id BIGINT UNSIGNED NOT NULL,
            op VARCHAR(10) NOT NULL,
            version BIGINT UNSIGNED NOT NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'pending',
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY object (object_type, object_id),
            KEY status (status)
        ) {$charset};");
    }

    /** A monotonic per-write version (ms since epoch) — the last-write-wins key. */
    private static function version(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    public static function enqueue(string $objectType, int $objectId, string $op): void
    {
        global $wpdb;
        $table = self::table();
        $now = current_time('mysql', true);
        $version = self::version();

        // Coalescing upsert: newest write wins, row returns to 'pending' so an
        // edit landing during an in-flight drain is not lost. Direct query on the
        // plugin's own sync-queue table — no core API, intentionally uncached.
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (object_type, object_id, op, version, status, updated_at)
             VALUES (%s, %d, %s, %d, 'pending', %s)
             ON DUPLICATE KEY UPDATE op = VALUES(op), version = VALUES(version), status = 'pending', updated_at = VALUES(updated_at)",
            $objectType,
            $objectId,
            $op,
            $version,
            $now
        ));
    }

    /**
     * Coalescing multi-row upsert — the batch form of enqueue(), used by the chunked
     * full sync so a whole page of product ids becomes ONE insert instead of N inline
     * queries in the admin request. Same last-write-wins semantics per row.
     *
     * @param array<int,int> $objectIds
     */
    public static function enqueueMany(string $objectType, array $objectIds, string $op): void
    {
        global $wpdb;

        $ids = array_values(array_unique(array_filter(array_map('intval', $objectIds))));
        if (! $ids) {
            return;
        }

        $table = self::table();
        $now = current_time('mysql', true);
        $version = self::version();

        // One "(%s,%d,%s,%d,'pending',%s)" tuple per id; every value is bound through
        // prepare — only the fixed placeholder list + the plugin-owned table name are
        // interpolated. Direct query on the plugin's own sync-queue table.
        $tuple = "(%s, %d, %s, %d, 'pending', %s)";
        $values = implode(', ', array_fill(0, count($ids), $tuple));
        $args = [];
        foreach ($ids as $id) {
            array_push($args, $objectType, $id, $op, $version, $now);
        }

        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (object_type, object_id, op, version, status, updated_at)
             VALUES {$values}
             ON DUPLICATE KEY UPDATE op = VALUES(op), version = VALUES(version), status = 'pending', updated_at = VALUES(updated_at)",
            ...$args
        ));
    }

    /**
     * Claim up to $limit pending rows for draining.
     *
     * @return array<int,object>
     */
    public static function claim(int $limit): array
    {
        global $wpdb;
        $table = self::table();

        self::reclaimStale();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, object_type, object_id, op, version FROM {$table} WHERE status = 'pending' ORDER BY id ASC LIMIT %d",
            $limit
        ));

        if ($rows) {
            // Bind every id via prepare (a %d placeholder each) — never interpolate
            // the value list. The table name is a fixed, plugin-owned identifier.
            $ids = array_map(static fn ($r) => (int) $r->id, $rows);
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("UPDATE {$table} SET status = 'claimed' WHERE id IN ({$placeholders})", ...$ids));
        }

        return $rows ?: [];
    }

    /**
     * Compare-and-delete: remove the row only if it is still the exact version we
     * claimed. If a newer write arrived during the drain, the version differs and
     * the row is left pending to be re-sent — no lost updates.
     */
    public static function complete(int $id, int $version): void
    {
        global $wpdb;
        $table = self::table();
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE id = %d AND status = 'claimed' AND version = %d",
            $id,
            $version
        ));
    }

    /** Return claimed rows to pending (e.g. after a failed send). */
    public static function release(array $ids): void
    {
        global $wpdb;
        $table = self::table();
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (! $ids) {
            return;
        }
        // Every id is bound via a %d placeholder through prepare; only the fixed
        // plugin-owned table name and the constant placeholder list are interpolated.
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET status = 'pending' WHERE id IN ({$placeholders})", ...$ids));
    }

    /** Rescue rows stuck 'claimed' (a crashed drain) after 5 minutes. */
    private static function reclaimStale(): void
    {
        global $wpdb;
        $table = self::table();
        // Fixed maintenance query on the plugin's own queue table; no user input.
        $wpdb->query(
            "UPDATE {$table} SET status = 'pending'
             WHERE status = 'claimed' AND updated_at < (UTC_TIMESTAMP() - INTERVAL 5 MINUTE)"
        );
    }

    public static function pendingCount(): int
    {
        global $wpdb;
        $table = self::table();
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'");
    }
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
}
