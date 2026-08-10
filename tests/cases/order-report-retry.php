<?php

/**
 * ORDER REPORTING — WHICH ANSWERS DESTROY AN ORDER AND WHICH ONE COMES BACK.
 *
 * This is revenue attribution: the number a merchant reads to decide whether
 * search is worth paying for. Until 2026-08-10 this plugin was the worst of the
 * four connectors at it — `reportOrder()` called `wp_remote_post()` and dropped
 * the result on the floor, on a single scheduled attempt, with a docblock claiming
 * WP-cron would retry. It never did. Any failure lost that order's revenue figure
 * permanently, and nothing anywhere logged a word.
 *
 * Three of the statuses that were being treated as final are temporary states
 * rather than verdicts:
 *
 *   429 the store is sending faster than the per-store rate limit allows, which
 *       happens exactly during a flash sale — so the busiest hour of the year
 *       reported the least revenue;
 *   409 the store is not verified YET, an ordinary few minutes of onboarding;
 *   423 the account is suspended, a state stores come back from.
 *
 * THE OPPOSITE MISTAKE IS WORSE. The service deduplicates a report on
 * (store, order reference, occurred_at), so a retry is free ONLY while it re-sends
 * the same bytes. A timestamp derived at send time turns a retry into a SECOND
 * sale for one order and OVERSTATES a merchant's revenue — a number nobody
 * complains about and nobody can trust.
 *
 * Exercised against the shipping class; needs no WordPress, no store and no
 * network.
 */

require_once dirname(dirname(__DIR__)).'/src/Api/Client.php';

use NitroSearch\Api\Client;

return [
    'a temporary refusal is retried, not discarded' => function () {
        // The three that used to be dropped, plus the rest of the retryable set.
        //
        // 401 is retryable HERE and would not be on a caller that cached headers:
        // the signature and its nonce are rebuilt inside every attempt, so the next
        // request is genuinely different rather than a replay of the one that was
        // just refused.
        foreach ([401, 408, 409, 423, 425, 429] as $status) {
            ns_true(
                'HTTP '.$status.' is retryable',
                ns_call_private('NitroSearch\Api\Client', 'isOrderRetryable', [$status])
            );
        }
    },

    'a verdict is accepted as final' => function () {
        // The other direction, and it matters as much: retrying these spends the
        // store's own scheduler to be told the same thing again. 400 is a payload
        // this plugin built wrong and will build wrong again; 403 and 404 are the
        // wrong credentials or the wrong endpoint; 410 is gone.
        foreach ([400, 403, 404, 410, 422] as $status) {
            ns_false(
                'HTTP '.$status.' is final',
                ns_call_private('NitroSearch\Api\Client', 'isOrderRetryable', [$status])
            );
        }
    },

    'every 5xx is retryable, not just the ones anybody listed' => function () {
        // A server error is the service's problem, never the store's, and the set
        // is open-ended on purpose: a 599 from an intermediary must not be read as
        // "this order was rejected".
        foreach ([500, 502, 503, 504, 507, 599] as $status) {
            ns_true(
                'HTTP '.$status.' is retryable',
                ns_call_private('NitroSearch\Api\Client', 'isOrderRetryable', [$status])
            );
        }
    },

    'a transport failure is retryable' => function () {
        // No status at all — timeout, DNS, TLS, refused connection. Nothing is
        // known about whether the order was recorded, and re-sending is safe
        // because the payload carries the dedupe tuple unchanged. Reported as
        // status 0 so the caller has one shape to read.
        ns_true(
            'status 0 is retryable',
            ns_call_private('NitroSearch\Api\Client', 'isOrderRetryable', [0])
        );
    },

    'the retryable set is exactly the four connectors agree on' => function () {
        // THE SET IS A CROSS-CONNECTOR CONTRACT, not a local preference. All four
        // modules classify the same statuses the same way, because a merchant
        // running two platforms must not see one of them silently lose the orders
        // the other kept. Pinned as an exact list so a quiet addition or removal
        // here has to be a deliberate, coordinated change.
        $reflected = new ReflectionClass('NitroSearch\Api\Client');
        $constants = $reflected->getConstants();

        ns_true('the plugin declares a retry set', array_key_exists('ORDER_RETRY_CODES', $constants));
        ns_is('the 4xx retry set', [401, 408, 409, 423, 425, 429], $constants['ORDER_RETRY_CODES']);
    },

    'success is neither retried nor an error' => function () {
        // 2xx must not appear in the retry set at all — a retryable success would
        // re-send an order the service already recorded, and the dedupe tuple is
        // the only thing standing between that and a double count.
        $reflected = new ReflectionClass('NitroSearch\Api\Client');
        $codes = $reflected->getConstants()['ORDER_RETRY_CODES'];

        foreach ([200, 201, 202, 204] as $status) {
            ns_false('HTTP '.$status.' is not in the retry set', in_array($status, $codes, true));
        }
    },

    'the outcome is a tri-state whose halves cannot disagree' => function () {
        // `done` and `retry` are always exact opposites; both are named on the wire
        // so the caller reads what it means rather than negating a flag. A build
        // where they could both be true would be a queue row that is deleted AND
        // rescheduled.
        foreach ([[true, 200], [false, 429]] as [$done, $status]) {
            $outcome = ns_call_private('NitroSearch\Api\Client', 'orderOutcome', [$done, $status, '']);

            ns_is('done', $done, $outcome['done']);
            ns_is('retry is its opposite', ! $done, $outcome['retry']);
            ns_is('status is carried through', $status, $outcome['status']);
        }
    },
];
