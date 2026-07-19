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

        $rows = Outbox::claim(self::BATCH);
        if (! $rows) {
            return;
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

        $result = Client::ingestBatch($items);

        if ($result['ok']) {
            foreach ($rows as $row) {
                Outbox::complete((int) $row->id, (int) $row->version);
            }
            Settings::update(['last_sync' => current_time('mysql', true), 'last_error' => '']);
        } else {
            Outbox::release($ids);   // leave them pending for the next tick
            Settings::update(['last_error' => 'HTTP '.($result['code'] ?? 0).' '.($result['error'] ?? '')]);
        }
    }
}
