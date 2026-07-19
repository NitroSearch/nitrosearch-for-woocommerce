<?php

namespace NitroSearch;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin settings and connection credentials, stored in a single wp_options row.
 * The sync secret is sensitive; it lives server-to-server only and is never
 * printed to the page.
 */
final class Settings
{
    private const OPTION = 'nitrosearch_settings';

    /** @return array<string,mixed> */
    public static function all(): array
    {
        $defaults = [
            'connected'         => false,
            'store_id'          => '',
            'install_id'        => '',
            'sync_key_id'       => '',
            'sync_secret'       => '',
            'search_public_id'  => '',
            'scoped_search_key' => '',
            'collection'        => '',
            'engine'            => [],
        ];

        return array_merge($defaults, get_option(self::OPTION, []));
    }

    public static function get(string $key, mixed $default = ''): mixed
    {
        return self::all()[$key] ?? $default;
    }

    /** @param array<string,mixed> $values */
    public static function update(array $values): void
    {
        update_option(self::OPTION, array_merge(self::all(), $values));
    }

    public static function isConnected(): bool
    {
        return (bool) self::get('connected', false) && self::get('sync_key_id') !== '';
    }

    /** Stable per-install id, generated once and persisted (used for key binding). */
    public static function installId(): string
    {
        $id = self::get('install_id');
        if ($id === '') {
            $id = wp_generate_uuid4();
            self::update(['install_id' => $id]);
        }

        return (string) $id;
    }

    public static function apiUrl(): string
    {
        return rtrim(NITROSEARCH_API_URL, '/');
    }
}
