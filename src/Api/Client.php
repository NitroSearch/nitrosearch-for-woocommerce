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

        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        $token = (string) Settings::get('connect_token');
        if ($token !== '') {
            // Present the provisioning token when the service requires one to onboard.
            $headers['X-NS-Connect-Token'] = $token;
        }

        $response = wp_remote_post(Settings::apiUrl().'/v1/connect', [
            'timeout' => 20,
            'headers' => $headers,
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

    /**
     * Trigger loopback proof-of-control. On success the backend provisions the store
     * and returns the search block — persist it. When the site can't be reached from
     * the backend (firewalled), verification stays pending and search keys are
     * fetched later via fetchSearchKey() once control is confirmed.
     *
     * @return array{ok:bool,verified:bool,reason:string}
     */
    public static function verify(): array
    {
        $res = self::send('POST', '/v1/verify');
        if (! $res['ok']) {
            return ['ok' => false, 'verified' => false, 'reason' => (string) ($res['error'] ?? 'unreachable')];
        }
        $body = is_array($res['body']) ? $res['body'] : [];
        $verified = (bool) ($body['verification']['verified'] ?? false);
        if ($verified && isset($body['search']) && is_array($body['search'])) {
            self::storeSearch($body['search']);
        }

        return ['ok' => true, 'verified' => $verified, 'reason' => (string) ($body['verification']['reason'] ?? '')];
    }

    /**
     * Poll plan / limit / verified / product count. Used during the connect→verify
     * window and to show sync health; persists the snapshot for the admin screen.
     *
     * @return array{ok:bool,verified:bool,claimed:bool,plan:string,product_limit:int,product_count:int}
     */
    public static function status(): array
    {
        $res = self::send('GET', '/v1/status');
        $body = is_array($res['body']) ? $res['body'] : [];
        $status = [
            'ok'            => $res['ok'],
            'verified'      => (bool) ($body['verified'] ?? false),
            'claimed'       => (bool) ($body['claimed'] ?? false),
            'plan'          => (string) ($body['plan'] ?? ''),
            'product_limit' => (int) ($body['product_limit'] ?? 0),
            'product_count' => (int) ($body['product_count'] ?? 0),
        ];
        if ($res['ok']) {
            Settings::update([
                'verified'      => $status['verified'],
                'claimed'       => $status['claimed'],
                'product_limit' => $status['product_limit'],
                'product_count' => $status['product_count'],
            ]);
        }

        return $status;
    }

    /**
     * Fetch + persist the scoped search key (available once the store is verified).
     * This is how the widget gets its key when verification happened out-of-band
     * (via the backend's loopback, not this request).
     *
     * @return array{ok:bool,error?:string}
     */
    public static function fetchSearchKey(): array
    {
        $res = self::send('GET', '/v1/search-key');
        if (! $res['ok']) {
            return ['ok' => false, 'error' => 'HTTP '.$res['code']];
        }
        self::storeSearch(is_array($res['body']) ? $res['body'] : []);

        return ['ok' => true];
    }

    /**
     * Mint a single-use "Manage / Upgrade" link so the store owner can attach this
     * free store to a NitroSearch account (or upgrade its plan) without re-indexing.
     * Minted server-side for a verified, still-unclaimed store and returned once as a
     * URL whose token lives in the fragment (kept off logs / Referer). Rate-limited,
     * so mint it on an explicit click — never on every page load.
     *
     * @return array{ok:bool,claim_url?:string,expires_at?:string,error?:string}
     */
    public static function claimLink(): array
    {
        $res  = self::send('POST', '/v1/claim-link');
        $body = is_array($res['body']) ? $res['body'] : [];

        if (! $res['ok']) {
            return ['ok' => false, 'error' => (string) ($body['reason'] ?? $body['message'] ?? ('HTTP '.$res['code']))];
        }

        return [
            'ok'         => true,
            'claim_url'  => (string) ($body['claim_url'] ?? ''),
            'expires_at' => (string) ($body['expires_at'] ?? ''),
        ];
    }

    /**
     * Persist a search block: {scoped_search_key, collection, engine:{host}, public_key_id?}.
     *
     * @param  array<string,mixed>  $search
     */
    private static function storeSearch(array $search): void
    {
        Settings::update([
            'scoped_search_key' => (string) ($search['scoped_search_key'] ?? ''),
            'collection'        => (string) ($search['collection'] ?? Settings::get('collection')),
            'engine_host'       => (string) ($search['engine']['host'] ?? ''),
            'search_public_id'  => (string) ($search['public_key_id'] ?? Settings::get('search_public_id')),
        ]);
    }

    /**
     * Send an HMAC-signed request with an empty body (GET, or a bodyless POST).
     *
     * @return array{ok:bool,code:int,body:mixed,error?:string}
     */
    private static function send(string $method, string $path): array
    {
        $headers = Hmac::headers(
            (string) Settings::get('sync_key_id'),
            (string) Settings::get('sync_secret'),
            $method,
            $path,
            '',
            (string) Settings::get('site_url', get_site_url()),
            Settings::installId(),
        );

        $response = wp_remote_request(Settings::apiUrl().$path, [
            'method'  => $method,
            'timeout' => 25,
            'headers' => $headers,
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
