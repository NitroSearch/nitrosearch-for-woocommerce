<?php

namespace NitroSearch\Sync;

use NitroSearch\Api\Client;
use NitroSearch\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Daily configuration refresh, piggybacked on the drain heartbeat.
 *
 * The scoped search key the widget uses embeds an expiry, and the widget asset
 * URLs can move between service releases — so a connected store re-fetches its
 * search block roughly once a day instead of holding the values it was handed
 * at onboarding forever. Riding the existing 60-second heartbeat (the same seam
 * FullSync::resumeIfStalled() uses) means no extra scheduled action to
 * register, no extra teardown paths, and per-store timing that spreads itself
 * out naturally.
 *
 * Gated on `verified` alone, deliberately: a verified store whose stored key
 * was somehow lost gets it BACKFILLED here, not just rotated. Failures are
 * harmless by design — Client::fetchSearchKey() never overwrites stored values
 * with a bad response, and the key has months of validity margin, so the next
 * day's attempt is always soon enough.
 */
class ConfigRefresh
{
    private const INTERVAL = DAY_IN_SECONDS;

    public static function maybeRun(): void
    {
        // Belt and braces: the stored `verified` flag can lag reality on older
        // installs (it was historically written only by the status poll), so a
        // store that HOLDS a key is treated as refresh-eligible regardless —
        // the backend simply 409s if it truly is not verified, harmlessly.
        if (! Settings::get('verified') && ! Settings::hasSearchKey()) {
            return;
        }

        if (time() - (int) Settings::get('config_refreshed_at', 0) < self::INTERVAL) {
            return;
        }

        try {
            Client::fetchSearchKey();
        } catch (\Throwable $e) {
            // A refresh fault must never take the drain heartbeat down with it.
        }

        // Stamped on every completed attempt, success or not: a failing backend
        // gets one polite retry per day, not one per heartbeat.
        Settings::update(['config_refreshed_at' => time()]);
    }
}
