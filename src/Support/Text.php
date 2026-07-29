<?php

namespace NitroSearch\Support;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Turning WordPress content into the plain text a search index should hold.
 *
 * WordPress stores titles HTML-encoded: type "Salt & Pepper" into the title field
 * and `post_title` holds `Salt &amp; Pepper`. A theme decodes that on the way out,
 * so nothing looks wrong on the site itself — but sending it verbatim to the index
 * means the widget renders the entity literally, and the shopper reads
 * "Salt &amp;amp; Pepper" in the dropdown. Ampersands are common enough in retail
 * names ("Black & Decker", "Shoes & Boots") that this is not an edge case.
 *
 * Used by every serializer, so the two cannot drift on it.
 */
final class Text
{
    /**
     * Plain text: markup removed, then entities resolved to real characters.
     *
     * The order matters and is the whole safety argument. Stripping FIRST means
     * `&lt;script&gt;` survives the strip (it is not markup yet), and decoding
     * afterwards turns it into the literal characters `<script>` — text, which the
     * widget escapes on render like any other text. Decoding first would produce
     * real markup for the stripper to remove, quietly destroying content a merchant
     * legitimately wrote about HTML, and would reward anyone feeding double-encoded
     * payloads through a CSV import.
     */
    public static function plain(mixed $value): string
    {
        $text = wp_strip_all_tags((string) $value);

        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * The same treatment for a list of term names — categories and brands come from
     * the same encoded storage as titles.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public static function plainList(array $values): array
    {
        return array_values(array_map([self::class, 'plain'], $values));
    }
}
