<?php

namespace NitroSearch\Support;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * HMAC request signing for the ingest API. This MUST stay byte-compatible with
 * the backend's verifier — the canonical string and header names are the shared
 * wire contract. Change one side only together with the other.
 *
 * Canonical string (newline-joined):
 *   v1 \n timestamp \n jti \n key_id \n METHOD \n path \n sha256(body) \n site_url \n install_id
 *
 * `jti` is a fresh 128-bit random nonce per request; the backend accepts it once
 * (single-use), which — with the timestamp window — prevents replay of a captured
 * request. Every signed request must carry a new jti.
 */
final class Hmac
{
    public const VERSION = 'v1';

    public static function canonical(int $timestamp, string $jti, string $keyId, string $method, string $path, string $body, string $siteUrl, string $installId): string
    {
        return implode("\n", [
            self::VERSION,
            (string) $timestamp,
            $jti,
            $keyId,
            strtoupper($method),
            $path,
            hash('sha256', $body),
            $siteUrl,
            $installId,
        ]);
    }

    public static function sign(string $secret, string $canonical): string
    {
        return hash_hmac('sha256', $canonical, $secret);
    }

    /** A fresh 128-bit per-request nonce (hex) for the single-use jti. */
    public static function newJti(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Build the signed headers for a request. Generates a fresh jti each call.
     *
     * @return array<string,string>
     */
    public static function headers(string $keyId, string $secret, string $method, string $path, string $body, string $siteUrl, string $installId): array
    {
        $timestamp = time();
        $jti = self::newJti();
        $canonical = self::canonical($timestamp, $jti, $keyId, $method, $path, $body, $siteUrl, $installId);

        return [
            'X-NS-Key'        => $keyId,
            'X-NS-Timestamp'  => (string) $timestamp,
            'X-NS-Jti'        => $jti,
            'X-NS-Signature'  => self::sign($secret, $canonical),
            'X-NS-Site-Url'   => $siteUrl,
            'X-NS-Install-Id' => $installId,
        ];
    }
}
