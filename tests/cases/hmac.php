<?php

/**
 * THE SHARED WIRE-CONTRACT VECTOR.
 *
 * The signature below is not this plugin's output recorded back as an expectation
 * — that would only prove the plugin agrees with itself, and a canonicalisation
 * that drifts on both sides at once is exactly the shape this has to catch. It is
 * the vector the SERVICE's verifier and every sibling connector reproduce, byte
 * for byte, so a change to any one signer shows up here as a mismatch rather than
 * in production as an unexplained 401.
 *
 * ⚠ THE CONSTANTS ARE IDENTICAL TO THE PRESTASHOP MODULE'S ON PURPOSE. Two
 * independent implementations agreeing on one pinned vector is the whole
 * mechanism; if you find yourself changing them on one side to make a test pass,
 * that IS the failure, and the other connectors and the service have to move with
 * it. There is no such thing as a local fix to this file.
 *
 * The inputs are synthetic throughout — `ns_sec_test`, `supersecret`, `shop.test`
 * — and exist only to pin a hash. Nothing here is a credential.
 */

require_once dirname(dirname(__DIR__)).'/src/Support/Hmac.php';

use NitroSearch\Support\Hmac;

const NS_TS = 1700000000;
const NS_JTI = '00112233445566778899aabbccddeeff';
const NS_KEY_ID = 'ns_sec_test';
const NS_BODY = '{"items":[]}';
const NS_SECRET = 'supersecret';

const NS_CANONICAL = "v1\n"
    ."1700000000\n"
    ."00112233445566778899aabbccddeeff\n"
    ."ns_sec_test\n"
    ."POST\n"
    ."/v1/ingest/batch\n"
    ."eef46741adfc3a9f76294d3b78f37a45f113092ac9d44ee77c7a038a88ff09a1\n"
    ."https://shop.test\n"
    .'install-xyz';

const NS_SIGNATURE = '7bd1738acad15bb990dfaa26f2be887d6b7731ce34fec914b0b67fb56343909d';

function ns_canonical($body = NS_BODY)
{
    return Hmac::canonical(
        NS_TS,
        NS_JTI,
        NS_KEY_ID,
        'POST',
        '/v1/ingest/batch',
        $body,
        'https://shop.test',
        'install-xyz'
    );
}

return [
    'the canonical string is byte-identical to the shared vector' => function () {
        ns_is('canonical', NS_CANONICAL, ns_canonical());
    },

    'the body is hashed, never included raw' => function () {
        // The canonical string carries sha256(body). A signer that inlined the
        // body would still verify against itself and fail against the service,
        // and the failure would only appear on a request with a non-empty body —
        // which is every ingest batch and no health check.
        ns_true('body absent from canonical', strpos(ns_canonical(), NS_BODY) === false);
        ns_true('body hash present', strpos(ns_canonical(), hash('sha256', NS_BODY)) !== false);
    },

    'the method is upper-cased before it is signed' => function () {
        // The service canonicalises the method it received, which arrives upper.
        // A signer that passed `post` through would sign a string the service can
        // never reconstruct.
        ns_is(
            'lower-case method signs identically',
            ns_canonical(),
            Hmac::canonical(NS_TS, NS_JTI, NS_KEY_ID, 'post', '/v1/ingest/batch', NS_BODY, 'https://shop.test', 'install-xyz')
        );
    },

    'the signature is byte-identical to the shared vector' => function () {
        ns_is('signature', NS_SIGNATURE, Hmac::sign(NS_SECRET, NS_CANONICAL));
    },

    'the plugin signs its own canonical string to the same value' => function () {
        // Both halves together: canonicalisation AND signing. Either alone can be
        // right while the pair is wrong.
        ns_is('end to end', NS_SIGNATURE, Hmac::sign(NS_SECRET, ns_canonical()));
    },

    'a single changed byte changes the signature' => function () {
        // The self-negative. A pin that cannot fail is a pin on nothing — if this
        // ever passes, the assertions above are proving that two constants are
        // equal to themselves.
        ns_true(
            'different body → different signature',
            Hmac::sign(NS_SECRET, ns_canonical('{"items":[1]}')) !== NS_SIGNATURE
        );
        ns_true(
            'different secret → different signature',
            Hmac::sign('supersecrft', NS_CANONICAL) !== NS_SIGNATURE
        );
    },

    'headers() sends exactly the names the service reads' => function () {
        // THE HEADER NAMES ARE THE CONTRACT, as much as the canonical string is.
        // The service reads these six and reconstructs the canonical string from
        // them; a field committed to in the signature but sent under a different
        // name cannot be recovered, so the request fails 401 with nothing in it to
        // say why. Pinned as an exact set, in order, so a rename on either side
        // lands here.
        $headers = Hmac::headers(NS_KEY_ID, NS_SECRET, 'POST', '/v1/ingest/batch', NS_BODY, 'https://shop.test', 'install-xyz');

        ns_is('header names', [
            'X-NS-Key',
            'X-NS-Timestamp',
            'X-NS-Jti',
            'X-NS-Signature',
            'X-NS-Site-Url',
            'X-NS-Install-Id',
        ], array_keys($headers));

        ns_is('key id is sent verbatim', NS_KEY_ID, $headers['X-NS-Key']);
        ns_is('site url is sent verbatim', 'https://shop.test', $headers['X-NS-Site-Url']);

        // THE SECRET IS NEVER A HEADER. It is the signing key; a plugin that sent
        // it would be handing every proxy on the path the ability to forge this
        // store's ingest.
        ns_true('no header carries the secret', ! in_array(NS_SECRET, array_values($headers), true));
    },

    'each call uses a fresh nonce' => function () {
        // The jti is the replay defence. A signer that reused one would produce
        // requests that verify perfectly and get rejected as replays — or worse,
        // would not.
        $a = Hmac::headers(NS_KEY_ID, NS_SECRET, 'POST', '/v1/ingest/batch', NS_BODY, 'https://shop.test', 'install-xyz');
        $b = Hmac::headers(NS_KEY_ID, NS_SECRET, 'POST', '/v1/ingest/batch', NS_BODY, 'https://shop.test', 'install-xyz');

        ns_true('jti differs between calls', $a['X-NS-Jti'] !== $b['X-NS-Jti']);
        ns_is('jti is 128-bit hex', 1, preg_match('/^[0-9a-f]{32}$/', $a['X-NS-Jti']));
    },
];
