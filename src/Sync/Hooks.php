<?php

namespace NitroSearch\Sync;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce/WordPress hook listeners. Each does exactly one thing: write a
 * coalesced dirty row to the outbox. No HTTP, no payload building here — that
 * keeps admin saves and checkout fast and never blocks on the network.
 */
final class Hooks
{
    public static function register(): void
    {
        add_action('woocommerce_new_product', [self::class, 'upsert']);
        add_action('woocommerce_update_product', [self::class, 'upsert']);
        add_action('woocommerce_product_set_stock', [self::class, 'upsertFromProduct']);
        add_action('woocommerce_variation_set_stock', [self::class, 'upsertFromVariation']);
        add_action('woocommerce_new_product_variation', [self::class, 'upsertFromVariationId']);
        add_action('woocommerce_update_product_variation', [self::class, 'upsertFromVariationId']);
        add_action('wp_trash_post', [self::class, 'maybeDelete']);
        add_action('before_delete_post', [self::class, 'maybeDelete']);
        add_action('untrashed_post', [self::class, 'maybeUpsertPost']);
    }

    public static function upsert($productId): void
    {
        Outbox::enqueue('product', (int) $productId, 'upsert');
    }

    public static function upsertFromProduct($product): void
    {
        if ($product instanceof \WC_Product) {
            Outbox::enqueue('product', $product->get_id(), 'upsert');
        }
    }

    public static function upsertFromVariation($variation): void
    {
        if ($variation instanceof \WC_Product) {
            Outbox::enqueue('product', $variation->get_parent_id(), 'upsert');
        }
    }

    public static function upsertFromVariationId($variationId): void
    {
        $parent = wp_get_post_parent_id((int) $variationId);
        if ($parent) {
            Outbox::enqueue('product', (int) $parent, 'upsert');
        }
    }

    public static function maybeDelete($postId): void
    {
        if (get_post_type((int) $postId) === 'product') {
            Outbox::enqueue('product', (int) $postId, 'delete');
        }
    }

    public static function maybeUpsertPost($postId): void
    {
        if (get_post_type((int) $postId) === 'product') {
            Outbox::enqueue('product', (int) $postId, 'upsert');
        }
    }

    /** Enqueue every published product (used on connect / manual full sync). */
    public static function fullSync(): int
    {
        $ids = get_posts([
            'post_type'   => 'product',
            'post_status' => 'publish',
            'fields'      => 'ids',
            'numberposts' => -1,
        ]);

        foreach ($ids as $id) {
            Outbox::enqueue('product', (int) $id, 'upsert');
        }

        return count($ids);
    }
}
