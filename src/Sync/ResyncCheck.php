<?php

namespace NitroSearch\Sync;

use NitroSearch\Api\Client;
use NitroSearch\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Periodic status check, piggybacked on the drain heartbeat — and the handler for
 * a re-sync the service asks for.
 *
 * WHY THIS EXISTS. Sending the catalogue is fire-and-forget: the service accepts a
 * batch and answers straight away, and this plugin then clears those rows from its
 * outbox. If anything in that batch turns out to be unusable once the service opens
 * it — an item it cannot read, or one that would push the store past its plan — the
 * item is quietly missing from search, and nothing on this side knows to send it
 * again. The service can now say so, and this is what listens.
 *
 * The status endpoint carries a `resync` block only while a request is outstanding.
 * On seeing one we start a full sync and confirm it, and the block disappears.
 *
 * Riding the existing 60-second heartbeat (the same seam ConfigRefresh uses) keeps
 * this to no extra scheduled action and no extra teardown path. Before this, status
 * was fetched only when the plugin version changed or the merchant pressed "Check
 * status", so a store could run for months without ever learning that its plan,
 * its limit, or its indexed count had moved.
 */
class ResyncCheck
{
    /**
     * How often to ask. Frequent enough that a re-sync request is picked up while
     * someone is still watching for it, rare enough to be invisible: twelve small
     * requests an hour against an endpoint that reads a handful of columns.
     */
    private const INTERVAL = 300;

    public static function maybeRun(): void
    {
        if (! Settings::isConnected()) {
            return;
        }

        if (time() - (int) Settings::get('status_checked_at', 0) < self::INTERVAL) {
            return;
        }

        try {
            $status = Client::status();
        } catch (\Throwable $e) {
            // A status fault must never take the drain heartbeat down with it.
            Settings::update(['status_checked_at' => time()]);

            return;
        }

        // Stamped on every completed attempt, success or not, so an unreachable
        // service gets one polite retry per interval rather than one per heartbeat.
        Settings::update(['status_checked_at' => time()]);

        if (! $status['ok']) {
            return;
        }

        $resync = $status['resync'] ?? null;
        if (! is_array($resync) || empty($resync['required'])) {
            return;
        }

        $token = (string) ($resync['token'] ?? '');
        if ($token === '') {
            return;
        }

        self::handle($token);
    }

    /**
     * Start the requested sync, then confirm it.
     *
     * The order matters. We record the token as acted on BEFORE confirming, so that a
     * confirmation which fails to arrive costs one retry rather than a second full
     * sync: the request stays outstanding, the next check sees the same token, skips
     * the sync it has already started, and simply tries the confirmation again.
     *
     * Doing it the other way round — confirm first, record after — would restart the
     * whole catalogue every five minutes for as long as the confirmation kept
     * failing, which on a large store is exactly the runaway load this plugin is
     * careful to avoid everywhere else.
     */
    private static function handle(string $token): void
    {
        if ((string) Settings::get('resync_token_done', '') !== $token) {
            // FullSync is chunked and resumable and schedules its own work, so this
            // returns immediately no matter how large the catalogue is. Calling it
            // while a run is already active resumes that run rather than duplicating it.
            FullSync::start();
            Settings::update(['resync_token_done' => $token]);
        }

        Client::acknowledgeResync($token);
    }
}
