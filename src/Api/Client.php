<?php

namespace NitroSearch\Api;

use NitroSearch\Settings;
use NitroSearch\Support\Hmac;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * HTTP client for the NitroSearch backend. `connect` is unauthenticated (returns
 * the store's credentials); `ingestBatch` is HMAC-signed with the sync secret.
 */
final class Client
{
    /**
     * Register this store and persist the returned credentials.
     *
     * @return array{ok:bool,error?:string}
     */
    public static function connect(): array
    {
        $siteUrl = get_site_url();
        $installId = Settings::installId();

        $response = wp_remote_post(Settings::apiUrl().'/v1/connect', [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'body'    => wp_json_encode(['site_url' => $siteUrl, 'install_id' => $installId]),
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 201 || ! is_array($body)) {
            return ['ok' => false, 'error' => "Connect failed (HTTP {$code}): ".wp_remote_retrieve_body($response)];
        }

        Settings::update([
            'connected'         => true,
            'site_url'          => $siteUrl,
            'store_id'          => $body['store_id'] ?? '',
            'collection'        => $body['collection'] ?? '',
            'sync_key_id'       => $body['sync']['key_id'] ?? '',
            'sync_secret'       => $body['sync']['secret'] ?? '',
            'search_public_id'  => $body['search']['public_key_id'] ?? '',
            'scoped_search_key' => $body['search']['scoped_search_key'] ?? '',
            'engine_host'       => $body['search']['engine']['host'] ?? '',
            'widget_loader_url' => $body['widget']['loader_url'] ?? '',
            'widget_bundle_url' => $body['widget']['bundle_url'] ?? '',
        ]);

        return ['ok' => true];
    }

    /**
     * Send a signed delta batch.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return array{ok:bool,code:int,body:mixed,error?:string}
     */
    public static function ingestBatch(array $items): array
    {
        $path = '/v1/ingest/batch';
        $body = wp_json_encode(['items' => array_values($items)]);

        $headers = Hmac::headers(
            (string) Settings::get('sync_key_id'),
            (string) Settings::get('sync_secret'),
            'POST',
            $path,
            $body,
            (string) Settings::get('site_url', get_site_url()),
            Settings::installId(),
        );
        $headers['Content-Type'] = 'application/json';

        $response = wp_remote_post(Settings::apiUrl().$path, [
            'timeout' => 20,
            'headers' => $headers,
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'code' => 0, 'body' => null, 'error' => $response->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return [
            'ok'   => $code >= 200 && $code < 300,
            'code' => $code,
            'body' => json_decode(wp_remote_retrieve_body($response), true),
        ];
    }
}
