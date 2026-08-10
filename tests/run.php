<?php
/**
 * The plugin's test runner. No Composer, no PHPUnit, no network, no WordPress.
 *
 * WHY THIS EXISTS, AND WHY IT IS LAST. This is the oldest and largest connector —
 * the one on wordpress.org, the one with the most installs — and it was the only
 * one of the four with no test suite at all. PrestaShop and OpenCart both gained
 * one; this repo had a secret scan and a wordpress.org deploy job, neither of
 * which reads a line of the code they publish.
 *
 * That gap is not theoretical. On 2026-08-10 every connector was found to be
 * throwing away search-attributed orders on any 4xx — and WooCommerce was the
 * worst of the four: `reportOrder()` dropped the HTTP result entirely, so one
 * fire-and-forget attempt was the whole retry story. It was found by reading four
 * implementations against each other, not by anything mechanical, because on this
 * repo there was nothing mechanical to find it.
 *
 * WHY IT IS HAND-ROLLED. The plugin ships as a ZIP to wordpress.org with no build
 * step and no Composer dependencies, on purpose — a plugin that resolves
 * dependencies at install time fails on most shared hosts. A dev-only dependency
 * would mean a lockfile, a vendor directory that must not ship, and a packaging
 * rule to keep it out. A hundred lines of runner costs less and cannot leak into
 * the archive. The two sibling connectors made the same call, and this file is
 * deliberately their shape so that reading one teaches you all three.
 *
 * WHAT IT COVERS, DELIBERATELY: the pure, framework-free parts where being wrong
 * is silent and expensive — the HMAC canonicalisation (a drift here is a 401 in
 * production, not a negotiation), the proof-of-control hash, the currency exponent
 * table that decides whether a price is 19.99 or 1999.00, and the order-report
 * retry classification that decides whether a merchant's revenue figure survives a
 * rate limit.
 *
 * WHAT IT DOES NOT COVER, and no green here should be read as covering: the hooks,
 * the outbox, the drain, the serializers' WordPress-facing halves, or the admin
 * screen. Those need a real WordPress, and the honest verification for them is a
 * real store — which is what RELEASING.md asks a release to describe.
 *
 *   php tests/run.php
 */

// Every shipped class guards on ABSPATH so its file cannot be fetched directly
// over the web. Defining it here is what lets them be loaded at all — without it
// a naive `php -r "require …"` against this repo prints nothing and exits 0,
// which looks exactly like a passing test.
if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__.'/');
}

$root = dirname(__DIR__);

$passed = 0;
$failures = [];
$currentCase = '';

/**
 * @param string $label
 * @param mixed  $expected
 * @param mixed  $actual
 */
function ns_is($label, $expected, $actual)
{
    global $passed, $failures, $currentCase;

    if ($expected === $actual) {
        $passed++;

        return;
    }

    $failures[] = sprintf(
        "%s › %s\n      expected: %s\n      actual:   %s",
        $currentCase,
        $label,
        var_export($expected, true),
        var_export($actual, true)
    );
}

/**
 * @param string $label
 * @param bool   $condition
 */
function ns_true($label, $condition)
{
    ns_is($label, true, (bool) $condition);
}

/**
 * @param string $label
 * @param bool   $condition
 */
function ns_false($label, $condition)
{
    ns_is($label, false, (bool) $condition);
}

/**
 * Reach a private static. The classifications below are not part of the plugin's
 * public surface and should not become part of it just to be testable.
 *
 * @param string            $class
 * @param string            $method
 * @param array<int, mixed> $args
 *
 * @return mixed
 */
function ns_call_private($class, $method, array $args)
{
    $reflected = new ReflectionMethod($class, $method);

    if (PHP_VERSION_ID < 80100) {
        // Needed up to 8.0, a no-op from 8.1, and DEPRECATED as of 8.5 — calling
        // it unconditionally prints a notice per assertion.
        $reflected->setAccessible(true);
    }

    return $reflected->invokeArgs(null, $args);
}

$cases = glob(__DIR__.'/cases/*.php');
sort($cases);

// A runner that finds no cases prints nothing and exits 0, which is
// indistinguishable from a clean run. It has to be an error — this project has
// lost more time to guards that passed over nothing than to guards that were
// wrong.
if (! $cases) {
    fwrite(STDERR, "no test cases found under tests/cases/ — the runner is not looking at what it thinks it is\n");
    exit(1);
}

foreach ($cases as $file) {
    $currentCase = basename($file, '.php');
    $tests = require $file;

    if (! is_array($tests) || ! $tests) {
        $failures[] = $currentCase.' › the case file returned no tests';
        continue;
    }

    foreach ($tests as $name => $test) {
        $currentCase = basename($file, '.php').' :: '.$name;
        $test($root);
    }
}

if ($failures) {
    fwrite(STDERR, "\n".count($failures)." FAILED\n\n");
    foreach ($failures as $f) {
        fwrite(STDERR, '  ✗ '.$f."\n\n");
    }
    exit(1);
}

fwrite(STDOUT, "ok    {$passed} assertions across ".count($cases)." case file(s)\n");
exit(0);
