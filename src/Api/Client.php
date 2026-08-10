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
            // Declare the ecommerce platform so the account dashboard shows the right
            // platform. This is the WooCommerce plugin, so the platform is always
            // 'woocommerce'.
            'body'    => wp_json_encode([
                'site_url'   => $siteUrl,
                'install_id' => $installId,
                'platform'   => 'woocommerce',
            ]),
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 201 || ! is_array($body)) {
            // Locale-neutral diagnostic detail: the admin screen wraps it in a
            // translated "Connect failed: %s" (which also made the old
            // "Connect failed (HTTP …)" prefix here read twice).
            return ['ok' => false, 'error' => "HTTP {$code}: ".wp_remote_retrieve_body($response)];
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
            // Usage-events beacon endpoint + this store's public token (absent on
            // older backends and unverified shells — harmless empties).
            'events_url'        => $body['events']['url'] ?? '',
            'events_token'      => $body['events']['token'] ?? '',
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
        if ($verified) {
            // Persist the flag the rest of the plugin trusts (the daily config
            // refresh gates on it) — previously only the status poll wrote it,
            // so a fresh install that verified here kept a stale false until
            // the next manual status check.
            Settings::update(['verified' => true]);
        }
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
            // The backend now enforces the plan cap at ingest; `at_limit` tells the
            // merchant they've hit it so we can prompt an upgrade (older backends
            // omit it, so it defaults to false).
            'at_limit'      => (bool) ($body['at_limit'] ?? false),
            // Present ONLY while the service is asking this store to send its whole
            // catalogue again (see Sync\ResyncCheck). Absent on every ordinary
            // response and on any backend that predates it — absence is the signal,
            // so there is nothing to compare against when it is missing.
            'resync'        => is_array($body['resync'] ?? null) ? $body['resync'] : null,
        ];
        // Persist only when the decoded body actually looks like a status
        // response: a 200 with an undecodable/foreign body (proxy or WAF
        // interference) must not flatten real stored state to defaults.
        if ($res['ok'] && $body !== [] && array_key_exists('verified', $body)) {
            $update = [
                'verified'      => $status['verified'],
                'claimed'       => $status['claimed'],
                'product_limit' => $status['product_limit'],
                'product_count' => $status['product_count'],
                'at_limit'      => $status['at_limit'],
                // Whether the plan includes the reporting surfaces (not yet released;
                // additive field — older backends omit it).
                'analytics_included' => (bool) ($body['analytics_included'] ?? false), // reporting is not yet released
            ];
            // The usage-events endpoint + token ride the poll every install already
            // makes — this is how stores connected before the feature existed pick
            // theirs up, with no new endpoint and no reconnect.
            if (! empty($body['events']['token'])) {
                $update['events_url'] = (string) ($body['events']['url'] ?? '');
                $update['events_token'] = (string) $body['events']['token'];
            }
            Settings::update($update);
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

        // A 200 whose body did not decode to the expected shape (proxy/WAF
        // interference, injected notice output) must never touch stored state:
        // blanking a working key here kills storefront search until the next
        // refresh. A stale-but-valid key always beats no key.
        $body = $res['body'];
        if (! is_array($body) || ! is_string($body['scoped_search_key'] ?? null) || $body['scoped_search_key'] === '') {
            return ['ok' => false, 'error' => 'malformed response body'];
        }
        self::storeSearch($body);

        return ['ok' => true];
    }

    /**
     * HTTP statuses that mean "come back and ask again", as opposed to "this is
     * the answer". Everything from 500 up is retryable too and is tested
     * separately, and so is a transport failure (which has no status at all).
     *
     * @var array<int,int>
     */
    private const ORDER_RETRY_CODES = [401, 408, 409, 423, 425, 429];

    /**
     * Report a search-attributed order on the signed server channel. The order id
     * is hashed with the install id before it leaves the site — the backend only
     * ever sees an opaque, store-scoped reference, and no shopper detail is part
     * of the payload.
     *
     * THIS RETURNS AN ANSWER BECAUSE, UNTIL 2026-08-10, IT RETURNED NOTHING.
     * The method called wp_remote_post() and dropped the result on the floor: no
     * status code was read, no transport error was noticed, the signature was
     * `: void`. Its own docblock claimed "WP-cron will simply try again", and
     * that was never true — the only caller is a wp_schedule_single_event() that
     * fires exactly once, and a clean return marks that event done. So an order
     * was reported once, thirty seconds after checkout, and ANY failure destroyed
     * it permanently with nothing recorded anywhere: one 502 from a proxy, one
     * DNS blip, one request that ran past the 10-second timeout, one 429.
     *
     * THE 429 IS THE ONE THAT MATTERS. This endpoint allows 60 reports a minute
     * per store, so the failure lands hardest exactly where a merchant is looking:
     * a flash sale that bursts past a report a second loses every order over the
     * line, and the busiest hour of the year reports the least revenue. That is
     * also the hour whose numbers are used to judge whether search is worth
     * paying for.
     *
     * The caller (Sync\OrderAttribution::dispatchReport) acts on the answer:
     *
     *   done  → any 2xx, including a 202 that says {accepted:false, reason:
     *           'disabled'}; and every 4xx not named below — 400/422 (a payload
     *           that is wrong now will be just as wrong in an hour), 403 (this
     *           store may not report), 404 (a backend older than the endpoint).
     *           Retrying these spends the store's own scheduler to be told the
     *           same thing again.
     *   retry → 401, 408, 409 (the store is not verified YET), 423 (the account
     *           is suspended — a state merchants come back from), 425, 429, any
     *           5xx, and a transport failure (reported as status 0).
     *
     * 401 IS RETRYABLE HERE AND WOULD NOT BE ON A CALLER THAT REUSED HEADERS:
     * Hmac::headers() is built fresh inside this method on every attempt, nonce
     * included, so the next attempt is a genuinely different signed request
     * rather than a replay of the one that was just refused.
     *
     * IT NEVER RE-DERIVES occurred_at. The timestamp is stamped once, by the
     * caller, when the order completes, and re-sent byte-identical on every
     * attempt — the service dedupes on (store, order_ref, occurred_at), so a
     * timestamp regenerated at send time turns each retry into a SECOND
     * conversion row for the same order and inflates the merchant's reported
     * revenue. The old `?? gmdate('c')` fallback on this line was that hazard
     * sitting one missing array key away, so a report that reaches here without
     * a timestamp is now refused outright rather than stamped on the way out.
     *
     * @param  array{order_id:int,value_cents:int,currency:string,occurred_at:string,item_ids:array<int,string>,q:string}  $report
     * @return array{done:bool,retry:bool,status:int,error:string}
     */
    public static function reportOrder(array $report): array
    {
        // Neither of these is a fault, and neither is worth coming back for: an
        // unconnected store has no channel to send on, and a merchant who turned
        // sharing off has already answered. The reply would be identical on every
        // future attempt, so this is done, not retry.
        if (! Settings::isConnected() || ! Settings::get('share_search_data', true)) {
            return self::orderOutcome(true, 0, 'not reporting');
        }

        $occurredAt = (string) ($report['occurred_at'] ?? '');
        if ($occurredAt === '') {
            return self::orderOutcome(true, 0, 'missing occurred_at');
        }

        $path = '/v1/orders';
        $body = wp_json_encode([
            'order_ref' => hash('sha256', Settings::installId().'|order|'.(int) ($report['order_id'] ?? 0)),
            'value_cents' => (int) ($report['value_cents'] ?? 0),
            'currency' => (string) ($report['currency'] ?? 'USD'),
            'occurred_at' => $occurredAt,
            'item_ids' => array_values(array_map('strval', (array) ($report['item_ids'] ?? []))),
            'q' => (string) ($report['q'] ?? ''),
        ]);

        if (! is_string($body)) {
            // Unencodable payload (malformed UTF-8 in the search term, say). It
            // will not encode on the next attempt either.
            return self::orderOutcome(true, 0, 'unencodable payload');
        }

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
            'timeout' => 10,
            'headers' => $headers,
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            // Timeout, DNS, TLS, refused connection — the request never got an
            // answer, so nothing is known about whether the order was recorded.
            // Retrying is safe: the service dedupes on the tuple this payload
            // carries, and the payload is re-sent unchanged.
            return self::orderOutcome(false, 0, $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code >= 200 && $code < 300) {
            return self::orderOutcome(true, $code, '');
        }

        return self::orderOutcome(! self::isOrderRetryable($code), $code, 'HTTP '.$code);
    }

    /**
     * Is this status "come back and ask again", rather than an answer?
     *
     * A NAMED METHOD RATHER THAN AN INLINE EXPRESSION, because the rule is the
     * thing worth testing and an expression buried inside `reportOrder()` can only
     * be reached by standing up WordPress and the network. It was inline until
     * 2026-08-10, which is part of why the rule it replaced — "every 4xx is final"
     * — survived long enough to throw away a merchant's busiest hour: nothing
     * could ask it a question without a live shop.
     *
     * The sibling connectors expose the same method for the same reason, and the
     * suites on all of them pin the same set.
     *
     * ⚠ STATUS 0 — A TRANSPORT FAILURE — IS RETRYABLE HERE TOO, even though
     * `reportOrder()` above returns before it can reach this method: the
     * `is_wp_error()` branch answers a timeout, DNS blip, TLS error or refused
     * connection directly. It is included anyway because the OTHER TWO CONNECTORS
     * CLASSIFY IT HERE, and a classifier that disagrees with its siblings on a
     * value it merely happens never to be handed is a trap with a fuse in it: the
     * first caller to route a transport failure through this method would have the
     * order treated as FINAL and deleted, which is the exact defect of 2026-08-10
     * arriving by a different road. The set is a cross-connector contract, so the
     * answer must be the same everywhere the question can be asked.
     *
     * Found by this plugin's own test suite on its first run.
     */
    private static function isOrderRetryable(int $status): bool
    {
        return $status === 0
            || $status >= 500
            || in_array($status, self::ORDER_RETRY_CODES, true);
    }

    /**
     * Build the tri-state reportOrder() answer. `done` and `retry` are always
     * exact opposites; both are named on the wire so the caller reads what it
     * means rather than negating a flag.
     *
     * @return array{done:bool,retry:bool,status:int,error:string}
     */
    private static function orderOutcome(bool $done, int $status, string $error): array
    {
        return ['done' => $done, 'retry' => ! $done, 'status' => $status, 'error' => $error];
    }

    /**
     * The 30-day analytics summary for the wp-admin card (docs on the service
     * side). A 2-second timeout on purpose: this can run during an admin page
     * render, and a slow backend must never hang wp-admin — the card degrades
     * to its "couldn't load" state instead.
     *
     * @return array{ok:bool,body:array<string,mixed>}
     */
    public static function analyticsSummary(): array
    {
        $res = self::send('GET', '/v1/analytics/summary', 2);

        return ['ok' => $res['ok'], 'body' => is_array($res['body']) ? $res['body'] : []];
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
     * Persist a search block: {scoped_search_key, collection, engine:{host}, public_key_id?, widget?}.
     *
     * Defence in depth for the unattended refresh path: a partial or mangled
     * block must never blank a working value. The key gates the whole persist;
     * every other field falls back to its stored value when absent — the widget
     * has NO fallback for an empty engine_host, so blanking it would break
     * storefront search even with a valid key.
     *
     * @param  array<string,mixed>  $search
     */
    private static function storeSearch(array $search): void
    {
        $key = $search['scoped_search_key'] ?? null;
        if (! is_string($key) || $key === '') {
            return;
        }

        $update = [
            'scoped_search_key' => $key,
            'collection'        => (string) (($search['collection'] ?? '') !== '' ? $search['collection'] : Settings::get('collection')),
            'engine_host'       => (string) (($search['engine']['host'] ?? '') !== '' ? $search['engine']['host'] : Settings::get('engine_host')),
            'search_public_id'  => (string) (($search['public_key_id'] ?? '') !== '' ? $search['public_key_id'] : Settings::get('search_public_id')),
        ];
        if (! empty($search['events']['token'])) {
            $update['events_url'] = (string) ($search['events']['url'] ?? '');
            $update['events_token'] = (string) $search['events']['token'];
        }
        // Widget asset URLs ride the search-key response on newer backends so a
        // relocated bundle reaches long-running installs; absence (an older
        // backend) leaves the stored URLs untouched.
        if (! empty($search['widget']['loader_url'])) {
            $update['widget_loader_url'] = (string) $search['widget']['loader_url'];
        }
        if (! empty($search['widget']['bundle_url'])) {
            $update['widget_bundle_url'] = (string) $search['widget']['bundle_url'];
        }
        Settings::update($update);
    }

    /**
     * Send an HMAC-signed request with an empty body (GET, or a bodyless POST).
     *
     * @return array{ok:bool,code:int,body:mixed,error?:string}
     */
    /**
     * Confirm that a requested full re-sync has STARTED.
     *
     * The token comes from the `resync` block on GET /v1/status and is echoed back
     * verbatim; it tells the service which request is being answered, so a
     * confirmation that arrives late cannot close a newer one.
     *
     * It rides the request BODY rather than a query string because the signature
     * covers the body and not the query — putting it in the URL would leave it
     * outside the signature and would mean signing a different string from the one
     * being requested.
     *
     * Best-effort by design: the service answers 204 whatever the token turns out to
     * be, and an unsent confirmation simply leaves the request outstanding for the
     * next check to retry. Never throws, never blocks the heartbeat.
     */
    public static function acknowledgeResync(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $path = '/v1/resync/ack';
        $body = wp_json_encode(['token' => $token]);

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
            'timeout' => 15,
            'headers' => $headers,
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 300;
    }

    private static function send(string $method, string $path, int $timeout = 25): array
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
            'timeout' => $timeout,
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
