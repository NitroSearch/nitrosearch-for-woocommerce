<?php

/**
 * A SHOP THAT UPGRADES MUST GET THE SCHEMA, NOT JUST A SHOP THAT INSTALLS.
 *
 * WordPress does not fire `register_activation_hook` on a plugin UPDATE. This plugin
 * has known that for a long time — `Plugin::onUpgrade()`'s own docblock says so, and
 * cites the content-defaults seeding as the lesson — and `Outbox::install()` was
 * nonetheless reachable from the activation hook and nowhere else.
 *
 * WHY THAT WENT UNNOTICED FOR SO LONG, and why it is worth a test rather than a fix:
 * the hole is invisible until the first time a column is added, and then it is
 * invisible again. A queue INSERT naming a missing column fails inside the write path,
 * the merchant's catalogue quietly stops being indexed, and nothing anywhere says why.
 * The failure arrives on the shops that have been running the plugin LONGEST — the
 * ones least likely to be watching a fresh install for problems.
 *
 * The other three connectors have all met the same thing. OpenCart shipped a report
 * table that an upgrading shop never received and had to add a runtime self-heal.
 * PrestaShop has no upgrade script at all. Magento avoids it entirely with a
 * declarative schema. It is a family of bug, not an incident.
 *
 * ⚠ WHAT THIS CANNOT SEE. It asserts the WIRING — that the version-change path calls
 * the installer — not that the resulting table is correct. `dbDelta` deciding it
 * cannot ALTER something, a permissions failure, a table left half-migrated: all pass
 * here. Only a real upgrade on a real store proves those, which is what RELEASING.md
 * asks a release to describe.
 */

$source = static function (string $file): string {
    $code = (string) file_get_contents(dirname(dirname(__DIR__)).'/src/'.$file);
    // Comments out first — this file's own subject is named in the prose above the
    // code it guards, and a text match would find it there.
    $code = preg_replace('~/\*.*?\*/~s', '', $code) ?? $code;

    return preg_replace('~//[^\n]*~', '', $code) ?? $code;
};

return [
    'the version-change path installs the schema' => function () use ($source) {
        $code = $source('Plugin.php');

        // Locate onUpgrade() and require the call INSIDE it. Asserting against the
        // whole file would pass on a call sitting in any other method, which is the
        // shape the bug already had — the installer existed, just not on this path.
        $start = strpos($code, 'function onUpgrade');
        ns_true('onUpgrade() exists', $start !== false);

        $body = substr($code, (int) $start);

        ns_true(
            'onUpgrade() calls the outbox installer',
            preg_match('/Outbox::install\s*\(/', $body) === 1
        );
    },

    'the installer is still idempotent, or running it every upgrade is unsafe' => function () use ($source) {
        // The whole reason this can live on a hot-ish path. `dbDelta` creates what is
        // missing and alters what has drifted; a plain `CREATE TABLE` without
        // `IF NOT EXISTS` would error on every upgraded shop instead.
        $code = $source('Sync/Outbox.php');

        ns_true('install() uses dbDelta', strpos($code, 'dbDelta(') !== false);
    },

    'the activation hook is not the only route to the schema' => function () use ($source) {
        // The self-negative, expressed as the property rather than the fix: whatever
        // the mechanism, the installer must be reachable from more than activation.
        // If someone later moves the call out of onUpgrade() into a different
        // version-change path, this stays true and the test above is the one to update.
        $plugin = $source('Plugin.php');
        $bootstrap = (string) file_get_contents(dirname(dirname(__DIR__)).'/nitrosearch.php');

        $inActivation = preg_match('/register_activation_hook/', $bootstrap) === 1;
        $elsewhere = preg_match('/Outbox::install\s*\(/', $plugin) === 1;

        ns_true('the activation hook still exists (a fresh install needs it)', $inActivation);
        ns_true('and it is not the only caller', $elsewhere);
    },
];
