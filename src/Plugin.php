<?php

namespace NitroSearch;

use NitroSearch\Admin\SettingsPage;
use NitroSearch\Api\VerifyEndpoint;
use NitroSearch\Frontend\WidgetLoader;
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
    }
}
