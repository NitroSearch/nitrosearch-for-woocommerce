<?php

namespace NitroSearch\Sync;

use NitroSearch\Settings;
use NitroSearch\Support\Text;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Builds the document for non-product content — pages and blog posts.
 *
 * Deliberately narrower than the product serializer. Only the title, a capped
 * summary, taxonomy terms, the featured image and the publish date are sent; the
 * full post body is NOT indexed. Two reasons: a long body systematically
 * outranks a short product name under the engine's default relevance, and body
 * text is the cost driver for index memory.
 *
 * The exclusion rules below are the security boundary of this feature. The
 * documents reach a search key that ships in public storefront HTML, so anything
 * we index is effectively public. `visible` therefore defaults to FALSE and is
 * only ever raised for content that passes every check.
 */
final class ContentSerializer
{
    /** Characters of summary text sent per item. */
    private const EXCERPT_LIMIT = 500;

    /**
     * @return array<string, mixed>|null  null when the item must not be indexed
     *                                    (the caller turns that into a delete, so
     *                                    anything that becomes non-public is
     *                                    actively removed from the index)
     */
    public static function serialize(int $postId): ?array
    {
        $post = get_post($postId);

        if (! $post instanceof \WP_Post) {
            return null;
        }

        if (! self::isPubliclySearchable($post)) {
            return null;
        }

        $published = get_post_time('U', true, $post);

        return [
            'id' => $post->ID,
            'object_type' => $post->post_type === 'page' ? 'page' : 'post',
            'name' => Text::plain(get_the_title($post)),
            'excerpt' => self::excerpt($post),
            'categories' => self::terms($post),
            'visible' => true,
            'permalink' => (string) get_permalink($post),
            'image' => (string) (get_the_post_thumbnail_url($post, 'medium_large') ?: ''),
            'published_at' => is_numeric($published) ? (int) $published : 0,
        ];
    }

    /**
     * Every reason a piece of content must stay out of the index.
     *
     * Fail closed: an unrecognised state is not indexed. Note that a
     * password-protected post has `post_status = 'publish'`, so a naive
     * publish-means-public rule would index the title and body of gated content —
     * which is exactly the trust bug this method exists to prevent.
     */
    private static function isPubliclySearchable(\WP_Post $post): bool
    {
        // Only genuinely published content. Excludes draft, pending, private,
        // future (scheduled), trash, auto-draft and inherit.
        if ($post->post_status !== 'publish') {
            return false;
        }

        // Password-protected: publish status, but not public.
        if ($post->post_password !== '') {
            return false;
        }

        // A post type the site itself excludes from search.
        $typeObject = get_post_type_object($post->post_type);
        if (! $typeObject || ! empty($typeObject->exclude_from_search)) {
            return false;
        }

        // Only the types the merchant has actually enabled.
        if (! in_array($post->post_type, Settings::indexedContentTypes(), true)) {
            return false;
        }

        // Respect an SEO plugin's noindex — if the site tells search engines to
        // ignore a page, it should not surface in the site's own search either.
        if (self::isNoIndex($post->ID, $post->post_type)) {
            return false;
        }

        /**
         * Last word for anything we cannot see — membership and paywall plugins
         * gate content by rules that are invisible from here, so they need a way
         * to keep it out.
         *
         * Return false to exclude.
         *
         * @param  bool     $searchable  whether NitroSearch will index this item
         * @param  int      $post_id     the item's id
         * @param  \WP_Post $post        the item
         */
        return (bool) apply_filters('nitrosearch_content_is_searchable', true, $post->ID, $post);
    }

    /**
     * Whether the site has asked for this item to be kept out of search.
     *
     * Checking per-post meta alone is not enough, and the gap is a common
     * configuration rather than an edge case: Yoast's *Search Appearance → Content
     * Types → Show Posts in search results? → No* stores a POST-TYPE-level flag and
     * writes no per-post meta whatsoever, so a per-post-only check sees nothing and
     * indexes the lot. Rank Math has the same shape. So this looks at three levels —
     * per post, per post type, and the whole site.
     *
     * Yoast's per-post value is tri-state: '1' noindex, '2' explicitly index, '0' or
     * absent means "follow the type default". A '2' therefore has to override a
     * type-level noindex, or enabling one page out of a hidden type would not work.
     *
     * Only Yoast and Rank Math are understood. The merchant-facing copy is scoped to
     * match rather than promising every SEO plugin.
     */
    private static function isNoIndex(int $postId, string $postType): bool
    {
        $yoastPost = get_post_meta($postId, '_yoast_wpseo_meta-robots-noindex', true);

        if ($yoastPost === '1') {
            return true;   // explicitly noindexed
        }

        if ($yoastPost === '2') {
            return false;  // explicitly indexed — overrides any broader default
        }

        // "Discourage search engines from indexing this site" (Settings → Reading).
        // Cast rather than compare strictly: WordPress normalises this option to an
        // INTEGER 0, so `=== '0'` silently never matches. Verified against a real
        // install — it is the kind of thing that reads correct and does nothing.
        if ((string) get_option('blog_public', '1') === '0') {
            return true;
        }

        $yoastTitles = (array) get_option('wpseo_titles', []);
        if (! empty($yoastTitles['noindex-'.$postType])) {
            return true;
        }

        $rankMathPost = get_post_meta($postId, 'rank_math_robots', true);
        if (is_array($rankMathPost) && in_array('noindex', $rankMathPost, true)) {
            return true;
        }

        $rankMathTitles = (array) get_option('rank_math_titles', []);
        if (($rankMathTitles['pt_'.$postType.'_custom_robots'] ?? '') === 'on'
            && in_array('noindex', (array) ($rankMathTitles['pt_'.$postType.'_robots'] ?? []), true)) {
            return true;
        }

        return false;
    }

    /**
     * A short, plain-text summary: the hand-written excerpt when there is one,
     * otherwise the opening of the content with shortcodes and blocks stripped.
     */
    private static function excerpt(\WP_Post $post): string
    {
        $raw = $post->post_excerpt !== ''
            ? $post->post_excerpt
            : $post->post_content;

        // excerpt_remove_blocks strips block markup without rendering it, so a
        // dynamic block cannot execute during a background sync.
        if (function_exists('excerpt_remove_blocks')) {
            $raw = excerpt_remove_blocks($raw);
        }

        $text = Text::plain(strip_shortcodes((string) $raw));
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        // wp_trim_words would cut on a word boundary but has no hard character
        // ceiling; the backend column and the index budget care about characters.
        return trim(mb_substr($text, 0, self::EXCERPT_LIMIT));
    }

    /**
     * Taxonomy terms, reusing the `categories` field products already facet on so
     * one facet covers the whole index rather than needing a second.
     *
     * @return array<int, string>
     */
    private static function terms(\WP_Post $post): array
    {
        $names = [];

        foreach (get_object_taxonomies($post->post_type, 'objects') as $taxonomy) {
            if (! $taxonomy->public || ! $taxonomy->show_in_nav_menus && ! $taxonomy->hierarchical) {
                // Skip internal taxonomies (e.g. post_format) — they are noise in a
                // shopper-facing facet.
                continue;
            }

            $terms = wp_get_post_terms($post->ID, $taxonomy->name, ['fields' => 'names']);
            if (! is_wp_error($terms)) {
                $names = array_merge($names, $terms);
            }
        }

        return array_values(array_unique(Text::plainList($names)));
    }
}
