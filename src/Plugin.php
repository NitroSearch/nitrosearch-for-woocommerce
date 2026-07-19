<?php

namespace NitroSearch;

use NitroSearch\Admin\SettingsPage;
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

        // Change capture + scheduling only once the store is connected.
        if (Settings::isConnected()) {
            Hooks::register();
            Drain::schedule();
        }
    }
}
