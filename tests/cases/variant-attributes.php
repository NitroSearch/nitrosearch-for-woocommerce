<?php

/**
 * A VARIATION'S ATTRIBUTES MUST SPEAK THE PARENT'S VOCABULARY, NOT WOOCOMMERCE'S.
 *
 * `WC_Product_Variation::get_variation_attributes()` answers internally: the key is
 * `attribute_pa_colour`, and a taxonomy attribute's value is the term SLUG. The parent's
 * own attribute map, built a few lines above it, says `Colour` and the term NAME.
 *
 * The service unions a product's attributes with every variant's, slugging the NAMES so
 * that "Colour Way", "colour-way" and "colour_way" merge — but `colour` and
 * `attribute_pa_colour` do not merge, and it deliberately does not normalise VALUES at
 * all. So one real attribute became two facets, and the second was shopper-visible junk.
 * Measured on a real store before the fix:
 *
 *     parent    {"Colour": ["Blue","Red"],        "Size": ["Large","Small"]}
 *     variants  {"attribute_pa_colour": ["blue"], "attribute_size": ["Large"]}
 *
 * ⚠ WHAT THIS FILE CAN AND CANNOT SEE. There is no WooCommerce here, so it cannot build
 * a `WC_Product` — it exercises the pure translation step (`variantAttributes()`) against
 * a hand-built parent map, and asserts the SHAPE of the code around it. The real proof is
 * an actual variable product on a real store, which is what `dev/woo-sandbox` is for and
 * what this fix was verified against in both directions. Do not read a green here as
 * proof that a merchant's facets are right.
 */

require_once dirname(dirname(__DIR__)).'/src/Support/Text.php';
require_once dirname(dirname(__DIR__)).'/src/Sync/ProductSerializer.php';

/** The parent map, in the shape attributeMap() produces. */
function ns_parent_map(): array
{
    return [
        'attribute_pa_colour' => [
            'label' => 'Colour',
            'values' => ['Blue', 'Red'],
            'key' => 'attribute_pa_colour',
            'terms' => ['blue' => 'Blue', 'red' => 'Red'],
        ],
        'attribute_size' => [
            'label' => 'Size',
            'values' => ['Large', 'Small'],
            'key' => 'attribute_size',
            'terms' => [],
        ],
    ];
}

/**
 * The method takes the raw `get_variation_attributes()` map rather than a `WC_Product`,
 * which is what makes it reachable from here at all — no WooCommerce, no stub class
 * pretending to be one.
 */
function ns_variant_attributes(array $raw): array
{
    $m = new ReflectionMethod('NitroSearch\Sync\ProductSerializer', 'variantAttributes');

    if (PHP_VERSION_ID < 80100) {
        $m->setAccessible(true);
    }

    return $m->invokeArgs(null, [$raw, ns_parent_map()]);
}

return [
    'a taxonomy attribute arrives as the parent label and the term NAME' => function () {
        // The exact pair measured on the sandbox: key `attribute_pa_colour`, value `blue`.
        ns_is(
            'colour',
            ['Colour' => ['Blue']],
            ns_variant_attributes(['attribute_pa_colour' => 'blue'])
        );
    },

    'a custom attribute keeps its value and gains the parent label' => function () {
        // Non-taxonomy attributes already carry the display value; only the KEY was wrong.
        ns_is(
            'size',
            ['Size' => ['Large']],
            ns_variant_attributes(['attribute_size' => 'Large'])
        );
    },

    'both together match what the parent sends' => function () {
        // The property that actually matters: after the service unions parent and
        // variants, every name and every value it sees came from the same vocabulary.
        $variant = ns_variant_attributes([
            'attribute_pa_colour' => 'red',
            'attribute_size' => 'Small',
        ]);

        foreach ($variant as $label => $values) {
            $parent = ns_parent_map()['attribute_'.strtolower($label === 'Colour' ? 'pa_colour' : 'size')];

            ns_is("{$label} uses the parent's label", $parent['label'], $label);
            foreach ($values as $value) {
                ns_true(
                    "{$label}={$value} is one of the parent's values",
                    in_array($value, $parent['values'], true)
                );
            }
        }
    },

    'no internal key survives to the wire' => function () {
        // The self-negative, stated as the defect rather than as its fix: whatever the
        // translation does, nothing may come out still called `attribute_*`.
        $out = ns_variant_attributes([
            'attribute_pa_colour' => 'blue',
            'attribute_size' => 'Large',
        ]);

        foreach (array_keys($out) as $key) {
            ns_true(
                "'{$key}' is not an internal WooCommerce key",
                strpos((string) $key, 'attribute_') !== 0
            );
        }
    },

    'an "any" variation contributes nothing rather than an empty facet value' => function () {
        // A variation set to "Any Colour" has an empty value: it does not constrain that
        // attribute. Emitting it would put "" in a merchant's refine rail.
        ns_is('any', ['Size' => ['Large']], ns_variant_attributes([
            'attribute_pa_colour' => '',
            'attribute_size' => 'Large',
        ]));
    },

    'a key the parent does not list is dropped, not passed through raw' => function () {
        // Stale variation meta for a removed attribute. There is no label to translate it
        // to, and passing it through is exactly the defect. Dropping loses a facet value
        // from a detached variation, which is the smaller and the deliberate harm.
        ns_is('stale', ['Size' => ['Large']], ns_variant_attributes([
            'attribute_pa_ghost' => 'nothing',
            'attribute_size' => 'Large',
        ]));
    },

    'a mixed-case key still matches' => function () {
        // WooCommerce lower-cases these when it builds them, but an import or a theme can
        // hand back mixed case, and a missed match is a SILENT drop rather than an error.
        ns_is('mixed case', ['Colour' => ['Blue']], ns_variant_attributes([
            'ATTRIBUTE_PA_COLOUR' => 'blue',
        ]));
    },

    'the parent and the variants are built from ONE derivation' => function () {
        // The structural property behind all of the above. Two independent derivations
        // agreeing today is what produced the defect: each was self-consistent and they
        // spoke different languages. Asserted on the source, since there is no
        // WooCommerce here to run attributeMap() against.
        $src = (string) file_get_contents(dirname(dirname(__DIR__)).'/src/Sync/ProductSerializer.php');
        $src = preg_replace('~/\*.*?\*/~s', '', $src) ?? $src;
        $src = preg_replace('~//[^\n]*~', '', $src) ?? $src;

        ns_true('attributes() reads the shared map', preg_match('/function attributes\([^)]*\)[^{]*\{[^}]*attributeMap\(/s', $src) === 1);
        ns_true('variants() reads the shared map', preg_match('/function variants\([^)]*\)[^{]*\{.*?attributeMap\(/s', $src) === 1);
        ns_is(
            'get_variation_attributes() is consulted in exactly one place',
            1,
            preg_match_all('/get_variation_attributes\(/', $src)
        );

        // ⚠ AND THE RESULT MUST GO THROUGH THE TRANSLATION. Every assertion above this
        // one calls `variantAttributes()` directly, so they all keep passing if the CALL
        // SITE stops using it — which is the original defect exactly:
        //
        //     'attributes' => array_map(fn ($v) => [(string) $v],
        //                               array_filter($variation->get_variation_attributes())),
        //
        // Restoring that line failed ZERO assertions until this one existed. Testing a
        // pure function proves the function; it does not prove anyone calls it.
        ns_true(
            'variants() routes the raw map through variantAttributes()',
            preg_match('/function variants\([^)]*\)[^{]*\{.*?self::variantAttributes\(/s', $src) === 1
        );
    },
];
