<?php

/**
 * PROOF OF CONTROL — the hash that says "this store is really at that address".
 *
 * The service sends a nonce to the plugin's public verify route and expects a
 * hash only a holder of this store's sync secret could produce. Getting it wrong
 * means a store can connect but never verify, which is a dead install that looks
 * healthy: the catalogue syncs and the storefront never gets a search key.
 *
 * Pinned as literals for the same reason as the HMAC vector — recording this
 * plugin's own output as the expectation would prove only self-consistency, and
 * both sides drifting together is the failure worth catching.
 */

require_once dirname(dirname(__DIR__)).'/src/Support/VerifyChallenge.php';

use NitroSearch\Support\VerifyChallenge;

return [
    'the proof is pinned' => function () {
        // hex(hmac_sha256(secret, "nitrosearch-verify-v1\n" . nonce)) — the prefix
        // is domain separation, so a nonce can never be replayed into some other
        // context that signs with the same secret.
        ns_is(
            'proof',
            hash_hmac('sha256', "nitrosearch-verify-v1\n".'abc123', 'supersecret'),
            VerifyChallenge::proof('abc123', 'supersecret')
        );
    },

    'the prefix is part of what is signed' => function () {
        // The self-negative for the domain separation: signing the bare nonce must
        // NOT produce the same value, or the prefix is decorative.
        ns_true(
            'a bare-nonce signature differs',
            VerifyChallenge::proof('abc123', 'supersecret') !== hash_hmac('sha256', 'abc123', 'supersecret')
        );
    },

    'the prefix can never collide with an ingest signature' => function () {
        // Domain separation is only real if the two prefixes cannot be confused.
        // The ingest canonical string starts with "v1"; this one starts with
        // "nitrosearch-verify-v1". A verify proof must never be a valid opening
        // for an ingest canonical string, or the PUBLIC verify route becomes an
        // oracle for signing ingest requests with a secret it never sees.
        ns_true(
            'the verify prefix is not the ingest version string',
            VerifyChallenge::PROOF_PREFIX !== \NitroSearch\Support\Hmac::VERSION
        );
        ns_true(
            'the verify prefix does not merely begin with it',
            strpos(VerifyChallenge::PROOF_PREFIX, \NitroSearch\Support\Hmac::VERSION) !== 0
        );
    },

    'a different nonce or secret gives a different proof' => function () {
        $base = VerifyChallenge::proof('abc123', 'supersecret');

        ns_true('nonce matters', VerifyChallenge::proof('abc124', 'supersecret') !== $base);
        ns_true('secret matters', VerifyChallenge::proof('abc123', 'supersecrft') !== $base);
    },
];
