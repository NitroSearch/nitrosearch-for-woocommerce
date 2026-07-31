<?php

namespace NitroSearch\Support;

use NitroSearch\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Turns the merchant's Design choices into the widget's appearance contract.
 *
 * The widget is one shared bundle served to every store, so it knows nothing
 * about "Roomy" or "Dark" — it reads `--ns-*` design tokens and a small layout
 * object. Resolving names to values HERE means adding a preset is a plugin
 * change with no storefront cost, and a merchant's stored choices can never
 * grow the bundle everyone downloads.
 *
 * Only values that differ from the widget's own defaults are emitted, so a store
 * on the defaults ships an empty theme object.
 */
final class Design
{
    /** Density tokens per look. Keys map to --ns-* custom properties. */
    private const LOOKS = [
        // The default: two-line names and a thumbnail big enough to recognise.
        'roomy'   => ['thumb' => '48px', 'rowPad' => '10px', 'nameLines' => '2', 'size' => '14px'],
        // ~40% more rows before scrolling, for long catalogues of short names.
        'compact' => ['thumb' => '36px', 'rowPad' => '6px',  'nameLines' => '1', 'size' => '13px'],
        // Image-led stores: a bigger picture inside the same row, no second layout.
        'images'  => ['thumb' => '72px', 'rowPad' => '12px', 'nameLines' => '2', 'size' => '14px'],
        // No thumbnails at all — B2B, spares, or stores without good photography.
        'text'    => ['thumb' => '0px',  'rowPad' => '8px',  'nameLines' => '2', 'size' => '14px'],
    ];

    private const CORNERS = ['rounded' => '12px', 'soft' => '6px', 'square' => '0'];

    /** Panel/text/chrome colours for the non-custom schemes. */
    private const SCHEMES = [
        'dark' => [
            'bg' => '#111827', 'text' => '#f9fafb', 'muted' => '#9ca3af',
            'border' => '#374151', 'chipBg' => '#1f2937', 'surface2' => '#1f2937',
        ],
    ];

    private const SYSTEM_FONT = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

    /**
     * The `theme` half of the widget config: --ns-* token values.
     *
     * @return array<string,string|bool>
     */
    public static function theme(): array
    {
        $theme = [];

        $look = (string) Settings::get('design_look', 'roomy');
        foreach (self::LOOKS[$look] ?? self::LOOKS['roomy'] as $token => $value) {
            $theme[$token] = $value;
        }

        $scheme = (string) Settings::get('design_scheme', 'light');
        if ($scheme === 'dark') {
            $theme += self::SCHEMES['dark'];
        } elseif ($scheme === 'auto') {
            // The widget carries the dark palette for this one case and applies it
            // behind prefers-color-scheme; sending ten more tokens would not work,
            // because the switch has to happen in CSS on the shopper's device.
            $theme['autoDark'] = true;
        } elseif ($scheme === 'custom') {
            $bg = (string) Settings::get('design_bg', '');
            $text = (string) Settings::get('design_text', '');
            if ($bg !== '') {
                $theme['bg'] = $bg;
                // Chrome derived from the panel background so a dark custom panel
                // does not keep light-grey chips and borders.
                $dark = ! self::isLight($bg);
                $theme['chipBg'] = $dark ? '#1f2937' : '#f3f4f6';
                $theme['surface2'] = $theme['chipBg'];
                $theme['border'] = $dark ? '#374151' : '#e5e7eb';
            }
            if ($text !== '') {
                $theme['text'] = $text;
                $theme['muted'] = self::fade($text);
            }
        }

        $accent = (string) Settings::get('accent_color', '');
        if ($accent !== '') {
            $theme['accent'] = $accent;
            // Never asked of the merchant: a pale accent with the widget's default
            // white label text is unreadable on buttons, chips and checkboxes.
            $theme['accentContrast'] = self::isLight($accent) ? '#111827' : '#ffffff';
        }

        $corners = (string) Settings::get('design_corners', 'rounded');
        if (isset(self::CORNERS[$corners]) && $corners !== 'rounded') {
            $theme['radius'] = self::CORNERS[$corners];
        }

        $font = (string) Settings::get('design_font', 'store');
        if ($font === 'store') {
            // A sentinel, not a value: the widget renders inside a Shadow DOM reset
            // with `all: initial`, so nothing is inherited across the boundary. The
            // loader swaps this for the store's own computed stack at mount.
            $theme['font'] = 'store';
        } elseif ($font === 'system') {
            $theme['font'] = self::SYSTEM_FONT;
        } elseif ($font === 'custom') {
            $stack = trim((string) Settings::get('design_font_stack', ''));
            if ($stack !== '') {
                $theme['font'] = $stack;
            }
        }

        return $theme;
    }

    /**
     * The `layout` half: behaviour the widget decides in JS, not CSS.
     *
     * @return array<string,string|int>
     */
    public static function layout(): array
    {
        $layout = [];

        $width = (string) Settings::get('design_width', 'auto');
        if (in_array($width, ['wide', 'match'], true)) {
            $layout['width'] = $width;
        }

        $perPage = (int) Settings::get('design_per_page', 8);
        if ($perPage > 0 && $perPage !== 8) {
            $layout['perPage'] = max(2, min(20, $perPage));
        }

        $filters = (string) Settings::get('design_filters', 'auto');
        if (in_array($filters, ['top', 'off'], true)) {
            $layout['facets'] = $filters;
        }

        return $layout;
    }

    /**
     * WCAG relative luminance, used only to decide black-or-white label text.
     * Anything unparseable is treated as light, which keeps dark text on it.
     */
    private static function isLight(string $hex): bool
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return true;
        }

        $channel = static function (int $v): float {
            $s = $v / 255;

            return $s <= 0.03928 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
        };

        $luminance = 0.2126 * $channel((int) hexdec(substr($hex, 0, 2)))
            + 0.7152 * $channel((int) hexdec(substr($hex, 2, 2)))
            + 0.0722 * $channel((int) hexdec(substr($hex, 4, 2)));

        // The midpoint of the WCAG contrast curve: above it, black text wins.
        return $luminance > 0.179;
    }

    /** A muted companion to the chosen text colour (60% toward the panel). */
    private static function fade(string $hex): string
    {
        $clean = ltrim(trim($hex), '#');
        if (strlen($clean) === 3) {
            $clean = $clean[0].$clean[0].$clean[1].$clean[1].$clean[2].$clean[2];
        }
        if (strlen($clean) !== 6 || ! ctype_xdigit($clean)) {
            return '#6b7280';
        }

        $mid = self::isLight($hex) ? 0 : 255;   // fade toward the opposite end
        $out = '';
        for ($i = 0; $i < 3; $i++) {
            $c = (int) hexdec(substr($clean, $i * 2, 2));
            $out .= str_pad(dechex((int) round($c * 0.6 + $mid * 0.4)), 2, '0', STR_PAD_LEFT);
        }

        return '#'.$out;
    }
}
