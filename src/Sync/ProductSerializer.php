<?php

namespace NitroSearch\Sync;

use NitroSearch\Support\Money;
use NitroSearch\Support\Text;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Turns a WooCommerce product into the ingest `data` payload the backend expects
 * (one document per product, variable products flattened). Money is always sent
 * as integer minor units — never a float.
 */
final class ProductSerializer
{
    /** @return array<string,mixed>|null null if the product no longer exists */
    public static function serialize(int $productId): ?array
    {
        $product = wc_get_product($productId);
        if (! $product) {
            return null;
        }

        $catalog = $product->get_catalog_visibility();       // visible|catalog|search|hidden
        $searchable = in_array($catalog, ['visible', 'search'], true)
            && $product->get_status() === 'publish';

        $data = [
            'id'               => $product->get_id(),
            // Plain text: markup stripped (a feed/CSV/REST import can bypass WP's own
            // sanitisation, and untrusted markup must never reach a shopper's browser)
            // and entities resolved, because WordPress stores "Salt & Pepper" as
            // "Salt &amp; Pepper" and the widget would render that literally.
            'name'             => Text::plain($product->get_name()),
            'description'      => Text::plain($product->get_short_description() ?: $product->get_description()),
            'sku'              => (string) $product->get_sku(),
            'brand'            => self::brand($product),
            'categories'       => self::terms($productId, 'product_cat'),
            'attributes'       => self::attributes($product),
            'price'            => self::minor($product->get_price()),
            'currency'         => get_woocommerce_currency(),
            // How many decimal places the prices above were scaled by. The service and
            // the shopper-facing search box both divide by THIS, not by whatever the
            // currency normally implies — older versions of this plugin scaled every
            // currency by 100, and reading those as true smallest-units would show a
            // ¥1,000 product as ¥100,000. Stating it removes the guess entirely, and
            // lets the two sides update in either order.
            'price_exponent'   => Money::exponent(get_woocommerce_currency()),
            'in_stock'         => $product->is_in_stock(),
            'on_sale'          => $product->is_on_sale(),
            'visible'          => $searchable,
            'popularity_score' => (int) $product->get_total_sales(),
            'permalink'        => (string) get_permalink($productId),
            'image'            => (string) (wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail') ?: ''),
        ];

        if ($product->is_type('variable')) {
            $data['variants'] = self::variants($product);
        }

        return $data;
    }

    /**
     * Convert a decimal-string price to a whole number of the currency's smallest
     * unit, or null if no price is set.
     *
     * The currency matters: this multiplied by 100 for everything, which sent yen at a
     * hundred times its value and Kuwaiti dinars at a tenth. See Support\Money.
     */
    private static function minor(mixed $price): ?int
    {
        return Money::toMinorUnits($price, get_woocommerce_currency());
    }

    private static function brand(\WC_Product $product): string
    {
        foreach (['product_brand', 'pwb-brand', 'yith_product_brand'] as $tax) {
            if (taxonomy_exists($tax)) {
                $names = self::terms($product->get_id(), $tax);
                if ($names) {
                    return $names[0];
                }
            }
        }

        return '';
    }

    /** @return array<int,string> */
    private static function terms(int $productId, string $taxonomy): array
    {
        $terms = wp_get_post_terms($productId, $taxonomy, ['fields' => 'names']);

        return is_wp_error($terms) ? [] : Text::plainList($terms);
    }

    /**
     * The parent's attributes, resolved ONCE and used by both the product's own
     * `attributes` map and its variants'.
     *
     * ⚠ THIS IS ONE DERIVATION ON PURPOSE, and it is the fix for a real defect. The
     * variants used to build their attribute map independently, from
     * `get_variation_attributes()`, which answers in WooCommerce's INTERNAL vocabulary:
     * the key is `attribute_pa_colour` and the value of a taxonomy attribute is the term
     * SLUG. The parent, meanwhile, sent `Colour` and the term NAME.
     *
     * The service unions a product's attributes with every variant's and slugs the NAMES
     * to merge them — but `colour` and `attribute_pa_colour` are not the same slug, and
     * it deliberately does not touch VALUES. So one real attribute became TWO facets, and
     * the second was shopper-visible junk:
     *
     *     colour                 Blue, Red        ← from the parent
     *     attribute_pa_colour    blue, red        ← from the variants
     *
     * Measured on a real store before the fix. Deriving both sides from one map is what
     * makes them agree by construction rather than by two functions happening to concur.
     *
     * `fields => all` rather than `names`: the same single query yields the display names
     * the parent needs AND the slug→name pairs the variants need. The full walk visits
     * every product in the catalogue, so a second per-attribute query here is not free.
     *
     * @return array<string,array{label:string,values:array<int,string>,key:string,terms:array<string,string>}>
     */
    private static function attributeMap(\WC_Product $product): array
    {
        $map = [];

        foreach ($product->get_attributes() as $attribute) {
            if (! $attribute instanceof \WC_Product_Attribute) {
                continue;
            }

            $name = $attribute->get_name();
            $label = Text::plain(wc_attribute_label($name));
            $terms = [];

            if ($attribute->is_taxonomy()) {
                $all = wc_get_product_terms($product->get_id(), $name, ['fields' => 'all']);
                $values = [];
                foreach ((is_wp_error($all) ? [] : (array) $all) as $term) {
                    $terms[(string) $term->slug] = (string) $term->name;
                    $values[] = $term->name;
                }
            } else {
                $values = $attribute->get_options();
            }

            $values = is_wp_error($values) ? [] : Text::plainList($values);

            // The key `get_variation_attributes()` answers with, for this attribute.
            $map[$label] = [
                'label' => $label,
                'values' => $values,
                'key' => 'attribute_'.sanitize_title($name),
                'terms' => $terms,
            ];
        }

        return $map;
    }

    /** @return array<string,array<int,string>> */
    private static function attributes(\WC_Product $product): array
    {
        $out = [];
        foreach (self::attributeMap($product) as $attribute) {
            if ($attribute['values']) {
                $out[$attribute['label']] = $attribute['values'];
            }
        }

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private static function variants(\WC_Product $product): array
    {
        // Resolved once for the whole product, not once per variation — a shirt with
        // four colours and five sizes has twenty of them.
        $byKey = [];
        foreach (self::attributeMap($product) as $attribute) {
            $byKey[$attribute['key']] = $attribute;
        }

        $variants = [];
        foreach ($product->get_children() as $childId) {
            $variation = wc_get_product($childId);
            if (! $variation) {
                continue;
            }
            $variants[] = [
                'id'         => $variation->get_id(),
                'sku'        => (string) $variation->get_sku(),
                'price'      => self::minor($variation->get_price()),
                'in_stock'   => $variation->is_in_stock(),
                'attributes' => self::variantAttributes($variation->get_variation_attributes(), $byKey),
            ];
        }

        return $variants;
    }

    /**
     * One variation's attributes, in the PARENT's vocabulary.
     *
     * `get_variation_attributes()` answers in WooCommerce's internal terms — the key is
     * `attribute_pa_colour`, and a taxonomy attribute's value is the term SLUG. Sent
     * that way it becomes a second, junk facet beside the parent's own (see
     * {@see attributeMap()}), so both halves are translated back through the parent's map.
     *
     * `array_filter` FIRST, and it is load-bearing: a variation set to "Any Colour" has an
     * empty value for that attribute, which means it does not constrain it. Dropping those
     * is correct — the alternative is a facet value of "".
     *
     * ⚠ AN UNRESOLVABLE KEY IS DROPPED, DELIBERATELY. A variation can carry stale meta for
     * an attribute the parent no longer lists; there is no label to translate it to, and
     * emitting the raw `attribute_*` key is precisely the defect this method exists to
     * fix. Losing a facet value from a detached variation is the smaller harm, and it is
     * the quiet one — so it is written down here rather than discovered later.
     *
     * TAKES THE RAW MAP, NOT THE VARIATION. The translation is the whole of the logic and
     * none of it needs WooCommerce, so it is a pure function of two arrays — which is what
     * lets the suite exercise it at all. A `WC_Product` parameter here would have made the
     * one piece of this fix worth testing reachable only from a running store.
     *
     * @param array<string,mixed>                                                                              $raw   get_variation_attributes()
     * @param array<string,array{label:string,values:array<int,string>,key:string,terms:array<string,string>}> $byKey
     *
     * @return array<string,array<int,string>>
     */
    private static function variantAttributes(array $raw, array $byKey): array
    {
        $out = [];

        foreach (array_filter($raw) as $key => $value) {
            // WooCommerce lower-cases these keys when it builds them; a theme or an
            // import can hand back mixed case, and a missed match is a silent drop.
            $key = strtolower((string) $key);

            if (! isset($byKey[$key])) {
                continue;
            }

            $value = (string) $value;
            $value = $byKey[$key]['terms'][$value] ?? $value;
            $value = Text::plain($value);

            if ($value !== '') {
                $out[$byKey[$key]['label']] = [$value];
            }
        }

        return $out;
    }
}
