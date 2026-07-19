<?php

namespace NitroSearch;

use NitroSearch\Admin\SettingsPage;
use NitroSearch\Frontend\WidgetLoader;
use NitroSearch\Sync\Drain;
use NitroSearch\Sync\Hooks;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public function boot(): void
    {
        (new SettingsPage())->register();

        // The drain action handler is always registered so scheduled work runs.
        Drain::register();

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
