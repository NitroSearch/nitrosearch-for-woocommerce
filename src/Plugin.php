<?php

namespace NitroSearch;

use NitroSearch\Admin\SettingsPage;
use NitroSearch\Api\VerifyEndpoint;
use NitroSearch\Frontend\WidgetLoader;
use NitroSearch\Sync\ContentPurge;
use NitroSearch\Sync\Drain;
use NitroSearch\Sync\FullSync;
use NitroSearch\Sync\Hooks;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public function boot(): void
    {
        (new SettingsPage())->register();

        // The drain + full-sync chunk handlers are always registered so scheduled
        // work (including a background full sync) runs even before "connected" gates.
        Drain::register();
        FullSync::register();
        ContentPurge::register();

        // The loopback verification endpoint is always available so the backend
        // can prove control of the site (the handler no-ops until connected).
        (new VerifyEndpoint())->register();

        // Change capture, scheduling, and the storefront widget only once the
        // store is connected.
        if (Settings::isConnected()) {
            Hooks::register();
            // Schedule on init — Action Scheduler's data store isn't ready during
            // plugins_loaded, so as_* calls must wait.
            add_action('init', [Drain::class, 'schedule']);
            (new WidgetLoader())->register();
        }

        // A status refresh runnable from WP-cron. Registered unconditionally so a
        // scheduled event always finds its handler; it no-ops when not connected.
        add_action('nitrosearch_refresh_status', static function (): void {
            if (Settings::isConnected()) {
                \NitroSearch\Api\Client::status();
            }
        });

        if (is_admin()) {
            $this->onUpgrade();
        }
    }

    /**
     * Version-change pickup. The activation hook does NOT fire on a plugin
     * update (a lesson already learned by the content-defaults seeding), so new
     * wire fields that arrive via /v1/status — the usage-events token among
     * them — are fetched by scheduling one background status refresh the first
     * time a new version loads in wp-admin. Async on purpose: an admin page
     * load must never block on a remote call.
     */
    private function onUpgrade(): void
    {
        if (get_option('nitrosearch_version') === NITROSEARCH_VERSION) {
            return;
        }
        update_option('nitrosearch_version', NITROSEARCH_VERSION, false);

        if (Settings::isConnected() && ! wp_next_scheduled('nitrosearch_refresh_status')) {
            wp_schedule_single_event(time() + 30, 'nitrosearch_refresh_status');
        }

        // One-time notice for the 1.5.x line: collection of anonymous search
        // usage counts begins with this version (default on, disclosed, with a
        // settings toggle). Shown until dismissed; see SettingsPage::notices().
        if (version_compare(NITROSEARCH_VERSION, '1.5.0', '>=') && ! get_option('nitrosearch_usage_notice_dismissed')) {
            update_option('nitrosearch_usage_notice', '1', false);
        }
    }
}
