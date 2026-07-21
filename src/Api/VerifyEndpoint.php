<?php

namespace NitroSearch\Api;

use NitroSearch\Settings;
use NitroSearch\Support\VerifyChallenge;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Public REST endpoint the NitroSearch backend calls to confirm this site
 * controls its own hostname. The backend issues a random nonce and fetches this
 * endpoint over a server-to-server loopback; we answer with an HMAC proof over the
 * sync secret. A caller that does not hold the secret cannot produce the proof, so
 * merely reflecting the nonce never passes verification.
 *
 * It is intentionally public (the backend is unauthenticated when it loopback-
 * fetches) yet safe: the proof is domain-separated from the ingest signature (see
 * VerifyChallenge), so it can never be used as a signing oracle for ingest.
 *
 *   GET /wp-json/nitrosearch/v1/verify?nonce=…  ->  { "proof": "<hex>" }
 */
final class VerifyEndpoint
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        register_rest_route('nitrosearch/v1', '/verify', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'args'                => [
                'nonce' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
            'callback'            => [$this, 'handle'],
        ]);
    }

    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $secret = (string) Settings::get('sync_secret');
        if ($secret === '') {
            // Not connected yet — there is no secret to prove control with.
            return new WP_REST_Response(['error' => 'not_connected'], 409);
        }

        $nonce = (string) $request->get_param('nonce');
        if ($nonce === '') {
            return new WP_REST_Response(['error' => 'missing_nonce'], 400);
        }

        return new WP_REST_Response(
            ['proof' => VerifyChallenge::proof($nonce, $secret)],
            200
        );
    }
}
