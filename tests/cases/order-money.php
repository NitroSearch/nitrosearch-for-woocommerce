<?php

/**
 * ATTRIBUTED REVENUE MUST BE SCALED BY THE CURRENCY, NOT BY A HUNDRED.
 *
 * THIS IS THE THIRD TIME. The catalogue serializer multiplied every price by 100
 * regardless of currency and was fixed. The Magento module did the same thing on its
 * ORDER path and shipped it — a yen store reported a hundred times its revenue on
 * every order from the day the module launched — and was fixed in `1.0.1`. This
 * plugin then kept the identical line on its own order path, because the audit that
 * found the first one only looked at the catalogue.
 *
 *     $valueCents += (int) round(((float) $item->get_total()) * 100);
 *
 * Nothing about it looks wrong. The payload has always carried `currency` alongside,
 * so a hundred-times-too-large number arrived correctly labelled and the service
 * accepted it. The only symptom is a revenue figure that is plausible and wrong by
 * two orders of magnitude — on the single number this product is sold on.
 *
 * ⚠ WHAT THIS CAN AND CANNOT SEE. It asserts the SHAPE — that the order path consults
 * the exponent table and contains no bare `* 100` — because a unit test with no
 * WooCommerce cannot build a real order. A wrong total assembled out of the right
 * helper still passes here; only an order placed in a zero-decimal currency on a real
 * store can catch that. The guard is deliberately narrow and says so, rather than
 * implying it proves the money is right.
 *
 * ⚠ AND IT DOES NOT ASSERT THE TAX BASIS. `get_total()` is ex-tax on WooCommerce while
 * the other three connectors send tax-inclusive, and that difference is a recorded
 * decision with its own release and its own merchant communication attached — not a
 * defect for this file to catch. Pinning it here would freeze the wrong half.
 */

$src = static function (string $file): string {
    $path = dirname(dirname(__DIR__)).'/src/'.$file;

    // Comments stripped before matching. This repo writes docblocks that NAME the
    // defect a piece of code exists for — the block above says `* 100` twice — so a
    // guard reading raw text finds the forbidden string inside the prose forbidding
    // it, and condemns the corrected tree while passing one that only talks about
    // the rule.
    $code = (string) file_get_contents($path);
    $code = preg_replace('~/\*.*?\*/~s', '', $code) ?? $code;
    $code = preg_replace('~//[^\n]*~', '', $code) ?? $code;

    return $code;
};

return [
    'the order path scales through the currency exponent table' => function () use ($src) {
        $code = $src('Sync/OrderAttribution.php');

        ns_true(
            'OrderAttribution consults Money',
            strpos($code, 'Money::toMinorUnits(') !== false
        );
    },

    'no bare hundred survives on the order path' => function () use ($src) {
        // The exact shape that shipped, in both orders it can be written.
        $code = $src('Sync/OrderAttribution.php');

        ns_is(
            'no `* 100` in live code',
            0,
            preg_match('/\*\s*100(?![0-9])|(?<![0-9])100\s*\*/', $code)
        );
    },

    'the currency travelling with the value is the one it was scaled by' => function () use ($src) {
        // The failure this catches is subtler than the one above and is what made the
        // original invisible: a payload may carry the RIGHT currency label beside a
        // number scaled by something else. Both must come from one variable.
        $code = $src('Sync/OrderAttribution.php');

        ns_true(
            'the order currency is read into a variable',
            preg_match('/\$currency\s*=\s*\(string\)\s*\$order->get_currency\(\)/', $code) === 1
        );
        // `.*` rather than `[^)]*`: the amount argument is itself a call
        // (`$item->get_total()`), so a negated-paren class stops at ITS closing
        // bracket and never reaches the currency. That mistake fails on correct
        // code, which is the direction that gets a guard deleted.
        ns_true(
            'that variable is what scales the value',
            preg_match('/Money::toMinorUnits\(.*\$currency\s*\)/', $code) === 1
        );
        ns_true(
            'and that variable is what the payload sends',
            preg_match("/'currency'\s*=>\s*\\\$currency/", $code) === 1
        );
    },

    'the exponent table itself still distinguishes the currencies that broke' => function () {
        // A pin on the two ends of the range, so a "tidy-up" of the table that
        // collapsed everything to two decimals would fail here rather than on a
        // merchant's dashboard. Money's own case file covers the table in full; this
        // is the order path asserting the dependency it now has.
        require_once dirname(dirname(__DIR__)).'/src/Support/Money.php';

        ns_is('JPY is zero-decimal', 0, \NitroSearch\Support\Money::exponent('JPY'));
        ns_is('KWD is three-decimal', 3, \NitroSearch\Support\Money::exponent('KWD'));
        ns_is('GBP is two-decimal', 2, \NitroSearch\Support\Money::exponent('GBP'));

        // ¥1,000 is 1000 minor units, not 100000. The number from the incident.
        ns_is('¥1000 scales to 1000', 1000, \NitroSearch\Support\Money::toMinorUnits('1000', 'JPY'));
    },
];
