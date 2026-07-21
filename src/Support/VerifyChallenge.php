<?php

namespace NitroSearch\Support;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Proof for the loopback site-verification challenge. This MUST stay
 * byte-compatible with the NitroSearch backend verifier — it is the shared wire
 * contract that lets this install prove it controls its own hostname without ever
 * exposing the sync secret.
 *
 *   proof = hex(hmac_sha256(sync_secret, "nitrosearch-verify-v1\n" + nonce))
 *
 * The "nitrosearch-verify-v1" prefix is deliberate domain separation: it can never
 * collide with the ingest signature's canonical string (see Hmac, which starts
 * with "v1"), so the public verify endpoint cannot be abused to forge ingest
 * requests. Change one side only together with the other.
 */
final class VerifyChallenge
{
    public const PROOF_PREFIX = 'nitrosearch-verify-v1';

    public static function proof(string $nonce, string $secret): string
    {
        return hash_hmac('sha256', self::PROOF_PREFIX."\n".$nonce, $secret);
    }
}
