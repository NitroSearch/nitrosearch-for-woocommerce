<?php

/**
 * MONEY — how many small units make one whole one.
 *
 * Prices go on the wire as a whole number of the currency's smallest unit. The
 * conversion used to multiply by 100 for every currency, which is right for
 * pounds, dollars and euros and wrong for about fifty others. The failure is
 * silent by construction: the payload is well formed, the service accepts it, and
 * the only symptom is a number that is plausible and wrong by two orders of
 * magnitude.
 *
 * ⚠ THE SAME MISTAKE SHIPPED ON A SIBLING CONNECTOR AND WAS LIVE FOR TWO DAYS —
 * a yen store reporting a hundred times its revenue on every order — and it
 * survived a guard that self-tested for exactly that shape, because the guard only
 * ever read its own fixtures. So these cases assert against the REAL table in the
 * shipped class, and the two ends of the range are named individually rather than
 * covered by a loop over a list this file also owns.
 *
 * The table is ISO 4217 and the SERVICE keeps the same one. If you change either,
 * change both.
 */

require_once dirname(dirname(__DIR__)).'/src/Support/Money.php';

use NitroSearch\Support\Money;

return [
    'the ordinary case is two decimal places' => function () {
        foreach (['GBP', 'USD', 'EUR', 'CHF', 'AUD', 'CAD', 'SEK'] as $code) {
            ns_is($code.' exponent', 2, Money::exponent($code));
        }

        // Anything unlisted must fall back to 2, not to 0 — a fallback of 0 would
        // silently multiply every unknown currency by one.
        ns_is('an unknown code falls back to 2', 2, Money::exponent('ZZZ'));
    },

    'zero-decimal currencies have no fractional part' => function () {
        // ¥1,000 is 1000, not 100000. This is the exact defect that shipped.
        ns_is('JPY exponent', 0, Money::exponent('JPY'));
        ns_is('KRW exponent', 0, Money::exponent('KRW'));
        ns_is('¥1000 in minor units', 1000, Money::toMinorUnits('1000', 'JPY'));
        ns_is('¥1000.00 in minor units', 1000, Money::toMinorUnits('1000.00', 'JPY'));
    },

    'three-decimal currencies divide into a thousand' => function () {
        // The other direction: a tenth of the real value rather than a hundred
        // times it. Quieter, and just as wrong.
        foreach (['BHD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND'] as $code) {
            ns_is($code.' exponent', 3, Money::exponent($code));
        }

        ns_is('KWD 19.994 in minor units', 19994, Money::toMinorUnits('19.994', 'KWD'));
    },

    'four-decimal currencies are in the table too' => function () {
        ns_is('CLF exponent', 4, Money::exponent('CLF'));
        ns_is('UYW exponent', 4, Money::exponent('UYW'));
    },

    'the Iraqi dinar is deliberately zero, not three' => function () {
        // IQD is three decimals in ISO 4217 and ZERO here, on purpose: the
        // shopper-facing search box uses the browser's own currency data, which
        // treats it as zero, and every layer has to agree or prices come out
        // orders of magnitude apart. Pinned so nobody "corrects" it against the
        // standard without finding this comment.
        ns_is('IQD exponent', 0, Money::exponent('IQD'));
    },

    'the code is normalised before it is looked up' => function () {
        // A store handing over "jpy" or " JPY " must not silently fall through to
        // the two-decimal default — that failure looks exactly like a correct
        // conversion of a different currency.
        ns_is('lower case', 0, Money::exponent('jpy'));
        ns_is('padded', 0, Money::exponent('  JPY  '));
        ns_is('mixed case', 3, Money::exponent('kWd'));
    },

    'an ordinary price converts the ordinary way' => function () {
        ns_is('19.99 GBP', 1999, Money::toMinorUnits('19.99', 'GBP'));
        ns_is('0 GBP', 0, Money::toMinorUnits('0', 'GBP'));
        ns_is('a float input', 1999, Money::toMinorUnits(19.99, 'GBP'));
    },

    'no price is distinct from a price of zero' => function () {
        // A product with no price set is a real state, and it must not arrive as
        // free. Both are falsy in PHP, which is exactly why this is pinned.
        ns_is('empty string', null, Money::toMinorUnits('', 'GBP'));
        ns_is('null', null, Money::toMinorUnits(null, 'GBP'));
        ns_is('zero is still zero', 0, Money::toMinorUnits('0.00', 'GBP'));
    },

    'rounding does not lose a penny to float representation' => function () {
        // 0.1 + 0.2 arithmetic: `(int) (1.15 * 100)` is 114 on most builds because
        // the float is really 1.14999…. The conversion rounds rather than
        // truncating, and this is the assertion that says so.
        ns_is('1.15 GBP', 115, Money::toMinorUnits('1.15', 'GBP'));
        ns_is('8.35 GBP', 835, Money::toMinorUnits('8.35', 'GBP'));
        ns_is('2.675 GBP', 268, Money::toMinorUnits('2.675', 'GBP'));
        ns_is('8.335 GBP', 834, Money::toMinorUnits('8.335', 'GBP'));
    },

    'the one input whose answer depends on the host PHP is NOT pinned' => function () {
        // ⚠ A REAL, MEASURED DIVERGENCE, recorded here rather than asserted.
        // `Money::toMinorUnits('1.005', 'GBP')` is **101 on PHP 8.1, 8.2 and 8.3**
        // and **100 on PHP 8.4**, because 8.4 changed how `round()` handles a value
        // that is exactly half-way in decimal and not representable in binary
        // (1.005 is really 1.00499999…). This plugin requires PHP 8.1 and
        // WordPress hosts run the whole range, so two merchants can send different
        // minor-unit values for the same price.
        //
        // It is not pinned because pinning either answer makes CI red on the other
        // half of the matrix, and "make the test match this runner" would bury the
        // finding. What IS asserted is that the answer stays within one minor unit
        // of the true value — which is the property that actually matters, and
        // which holds on every version.
        //
        // Only exact half-way inputs are affected; every ordinary price above is
        // identical across the matrix. A three-decimal price on a two-decimal
        // currency is the way a store reaches this at all.
        $actual = Money::toMinorUnits('1.005', 'GBP');

        ns_true('1.005 GBP is 100 or 101, depending on the host PHP', $actual === 100 || $actual === 101);
    },
];
