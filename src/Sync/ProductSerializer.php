<?php

namespace NitroSearch\Sync;

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
            'name'             => $product->get_name(),
            'description'      => wp_strip_all_tags((string) ($product->get_short_description() ?: $product->get_description())),
            'sku'              => (string) $product->get_sku(),
            'brand'            => self::brand($product),
            'categories'       => self::terms($productId, 'product_cat'),
            'attributes'       => self::attributes($product),
            'price'            => self::minor($product->get_price()),
            'currency'         => get_woocommerce_currency(),
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

    /** Convert a decimal-string price to integer minor units, or null if empty. */
    private static function minor(mixed $price): ?int
    {
        if ($price === '' || $price === null) {
            return null;
        }

        return (int) round(((float) $price) * 100);
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

        return is_wp_error($terms) ? [] : array_values($terms);
    }

    /** @return array<string,array<int,string>> */
    private static function attributes(\WC_Product $product): array
    {
        $out = [];
        foreach ($product->get_attributes() as $attribute) {
            if (! $attribute instanceof \WC_Product_Attribute) {
                continue;
            }
            $label = wc_attribute_label($attribute->get_name());
            $values = $attribute->is_taxonomy()
                ? wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names'])
                : $attribute->get_options();
            $values = is_wp_error($values) ? [] : array_values(array_map('strval', $values));
            if ($values) {
                $out[$label] = $values;
            }
        }

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private static function variants(\WC_Product $product): array
    {
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
                'attributes' => array_map(
                    static fn ($v) => [(string) $v],
                    array_filter($variation->get_variation_attributes())
                ),
            ];
        }

        return $variants;
    }
}
