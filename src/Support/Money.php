<?php

namespace NitroSearch\Support;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prices are sent as a whole number of the currency's smallest unit — never as a
 * decimal, because decimals and money are a classic source of silent rounding errors.
 *
 * How many of those small units make one whole unit depends on the currency, and that
 * is the part this fixes. The conversion used to multiply by 100 for every currency,
 * which is right for pounds, dollars and euros and wrong for several others:
 *
 *   - Yen and won have NO fractional part at all. A ¥1,000 product was being sent as
 *     100,000 — a hundred times its real price. That is not just a display problem:
 *     the number is what price sorting and price filters compare, so a whole Japanese
 *     catalogue sorted and filtered against inflated values.
 *   - Kuwaiti and Bahraini dinars, among others, divide into a THOUSAND. Those went
 *     the other way, at a tenth of their real value.
 *
 * The list below is ISO 4217. It is deliberately a plain table rather than something
 * derived at runtime: this has to work on any WordPress host, including those without
 * the intl extension, so there is nothing to derive it from. The service keeps the
 * same table, and the two must agree — if you change one, change both.
 *
 * Anything not listed has two decimal places, which covers the vast majority.
 */
final class Money
{
    /**
     * No fractional part — the smallest unit IS the whole unit.
     *
     * The Iraqi dinar is here even though the ISO standard gives it three decimals: the
     * shopper-facing search box uses the browser's own currency data, which treats it as
     * zero, and every layer has to agree or prices come out orders of magnitude wrong.
     */
    private const ZERO_DECIMAL = [
        'ADP', 'AFN', 'ALL', 'BIF', 'BYR', 'CLP', 'DJF', 'ESP', 'GNF',
        'IQD', 'IRR', 'ISK', 'ITL', 'JPY', 'KMF', 'KPW', 'KRW', 'LAK',
        'LBP', 'LUF', 'MGA', 'MGF', 'MMK', 'MRO', 'PYG', 'RSD', 'RWF',
        'SLL', 'SOS', 'STD', 'SYP', 'TMM', 'TRL', 'UGX', 'UYI', 'VND',
        'VUV', 'XAF', 'XOF', 'XPF', 'YER', 'ZMK', 'ZWD',
    ];

    /** Three decimal places — one whole unit is a thousand small ones. */
    private const THREE_DECIMAL = ['BHD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND'];

    /** Four decimal places. Rare; listed so the table is complete rather than nearly so. */
    private const FOUR_DECIMAL = ['CLF', 'UYW'];

    /** How many small units make one whole unit, as a power of ten. */
    public static function exponent(string $currency): int
    {
        $code = strtoupper(trim($currency));

        if (in_array($code, self::ZERO_DECIMAL, true)) {
            return 0;
        }
        if (in_array($code, self::THREE_DECIMAL, true)) {
            return 3;
        }
        if (in_array($code, self::FOUR_DECIMAL, true)) {
            return 4;
        }

        return 2;
    }

    /**
     * Convert a price as the store holds it (a decimal string like "19.99") into a
     * whole number of the currency's smallest unit.
     *
     * Returns null for an empty price, which is a real state — a product with no price
     * set — and must stay distinct from a price of zero.
     */
    public static function toMinorUnits(mixed $price, string $currency): ?int
    {
        if ($price === '' || $price === null) {
            return null;
        }

        return (int) round(((float) $price) * (10 ** self::exponent($currency)));
    }
}
