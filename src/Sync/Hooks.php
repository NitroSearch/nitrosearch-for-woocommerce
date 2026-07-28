<?php

namespace NitroSearch\Sync;

use NitroSearch\Settings;

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

        // Status transitions, for EVERY type we index including products.
        //
        // The Woo CRUD hooks above miss a class of change entirely: a scheduled post
        // published by WP-Cron goes through wp_publish_post(), which writes
        // post_status directly and fires no CRUD hook — so a scheduled product could
        // sit unindexed until an unrelated edit touched it. This also covers the
        // reverse, where content leaving `publish` must leave a public index promptly
        // (unpublished, made private, password-protected).
        add_action('transition_post_status', [self::class, 'onStatusTransition'], 10, 3);

        // Content edits. Products keep their own CRUD hooks; this covers pages and
        // blog posts, including saves made by the REST API and by importers.
        add_action('wp_after_insert_post', [self::class, 'onContentSaved'], 10, 4);

        // Term changes alter the facet values we send and fire no save hook.
        add_action('set_object_terms', [self::class, 'onTermsChanged'], 10, 4);
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
        $type = self::trackedType((int) $postId);
        if ($type !== null) {
            Outbox::enqueue($type, (int) $postId, 'delete');
        }
    }

    public static function maybeUpsertPost($postId): void
    {
        $type = self::trackedType((int) $postId);
        if ($type !== null) {
            Outbox::enqueue($type, (int) $postId, 'upsert');
        }
    }

    /**
     * Any move into or out of `publish` for something we track.
     *
     * Leaving `publish` enqueues a DELETE, not an upsert: the serializer would
     * refuse to build a document for it anyway, and a delete takes it out of the
     * public index straight away rather than leaving it there until something else
     * happens to touch the item.
     */
    public static function onStatusTransition($newStatus, $oldStatus, $post): void
    {
        if (! $post instanceof \WP_Post || $newStatus === $oldStatus) {
            return;
        }

        $type = self::trackedTypeForPostType($post->post_type);
        if ($type === null) {
            return;
        }

        if ($newStatus === 'publish') {
            Outbox::enqueue($type, $post->ID, 'upsert');
        } elseif ($oldStatus === 'publish') {
            Outbox::enqueue($type, $post->ID, 'delete');
        }
    }

    /**
     * A page or blog post was created or updated.
     *
     * `wp_after_insert_post` rather than `save_post`, because it fires after terms
     * and meta are written — so the document we build is not missing taxonomy values
     * saved in the same request.
     */
    public static function onContentSaved($postId, $post, $update, $postBefore): void
    {
        if (! $post instanceof \WP_Post || $post->post_type === 'product') {
            // Products are covered by the Woo CRUD hooks, which also know about price
            // and stock; enqueuing here as well would just double the outbox writes.
            return;
        }

        $type = self::trackedTypeForPostType($post->post_type);
        if ($type === null) {
            return;
        }

        // Enqueued whatever the status: the serializer decides indexability, and its
        // refusal becomes a delete — so content that has just stopped being public is
        // actively removed rather than lingering.
        Outbox::enqueue($type, (int) $postId, $post->post_status === 'publish' ? 'upsert' : 'delete');
    }

    /**
     * Terms changed on an object, so the facet values we last sent are stale. Fires
     * on bulk category assignment and on importer writes, neither of which triggers
     * a save hook.
     */
    public static function onTermsChanged($objectId, $terms, $ttIds, $taxonomy): void
    {
        $type = self::trackedType((int) $objectId);
        if ($type !== null) {
            Outbox::enqueue($type, (int) $objectId, 'upsert');
        }
    }

    /** The outbox type for a post id, or null when we do not track it. */
    private static function trackedType(int $postId): ?string
    {
        return self::trackedTypeForPostType((string) get_post_type($postId));
    }

    private static function trackedTypeForPostType(string $postType): ?string
    {
        if ($postType === 'product') {
            return 'product';
        }

        // Only what the merchant enabled. Checked here as well as in the serializer,
        // so a disabled type never even reaches the queue.
        if (in_array($postType, Settings::indexedContentTypes(), true)) {
            return $postType === 'page' ? 'page' : 'post';
        }

        return null;
    }
}
