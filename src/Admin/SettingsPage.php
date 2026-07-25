<?php

namespace NitroSearch\Admin;

use NitroSearch\Api\Client;
use NitroSearch\Settings;
use NitroSearch\Sync\Drain;
use NitroSearch\Sync\Hooks;
use NitroSearch\Sync\Outbox;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The wp-admin screen: connect the store, see sync health, trigger a full sync.
 * Nothing leaves the site until the merchant clicks Connect (the consent gate).
 */
final class SettingsPage
{
    /** The page hook returned by add_menu_page, used to scope asset loading. */
    private string $hook = '';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_post_nitrosearch_connect', [$this, 'handleConnect']);
        add_action('admin_post_nitrosearch_refresh', [$this, 'handleRefresh']);
        add_action('admin_post_nitrosearch_sync', [$this, 'handleSync']);
        add_action('admin_post_nitrosearch_disconnect', [$this, 'handleDisconnect']);
        add_action('admin_post_nitrosearch_appearance', [$this, 'handleAppearance']);
        add_action('admin_post_nitrosearch_claim', [$this, 'handleClaim']);
    }

    public function menu(): void
    {
        $this->hook = (string) add_menu_page(
            'NitroSearch',
            'NitroSearch',
            'manage_woocommerce',
            'nitrosearch',
            [$this, 'render'],
            'dashicons-search',
            58
        );
    }

    /** Load the branded stylesheet on our screen only — never elsewhere in wp-admin. */
    public function enqueueAssets(string $hook): void
    {
        if ($hook !== $this->hook) {
            return;
        }
        wp_enqueue_style(
            'nitrosearch-admin',
            plugins_url('assets/admin.css', NITROSEARCH_FILE),
            [],
            NITROSEARCH_VERSION
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $connected = Settings::isConnected();
        $ready = Settings::hasSearchKey();
        $notice = isset($_GET['ns_notice']) ? sanitize_text_field(wp_unslash($_GET['ns_notice'])) : '';
        $action = admin_url('admin-post.php');

        if (! $connected) {
            $pillClass = '';
            $pillText = 'Not connected';
        } elseif (! $ready) {
            $pillClass = 'ns-pill--pending';
            $pillText = 'Confirming control…';
        } else {
            $pillClass = 'ns-pill--ok';
            $pillText = 'Connected & verified';
        }
        ?>
        <div class="wrap nitrosearch-admin">
            <h1>NitroSearch</h1>

            <div class="ns-hero">
                <div class="ns-hero__brand">
                    <?php echo self::markSvg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG ?>
                    <div class="ns-hero__text">
                        <p class="ns-hero__wordmark">Nitro<span>Search</span></p>
                        <p class="ns-hero__tagline">Amazon-quality search for WooCommerce</p>
                    </div>
                </div>
                <span class="ns-pill <?php echo esc_attr($pillClass); ?>">
                    <span class="ns-pill__dot"></span><?php echo esc_html($pillText); ?>
                </span>
            </div>

            <?php if ($notice !== '') : ?>
                <div class="notice notice-info is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <?php if (! $connected) : ?>
                <div class="ns-card">
                    <h2 class="ns-card__title">Connect your store</h2>
                    <p class="ns-card__intro">
                        Connect this store to NitroSearch to start syncing your catalogue and serving instant,
                        typo-tolerant search. Nothing leaves your site until you click Connect.
                    </p>
                    <form method="post" action="<?php echo esc_url($action); ?>">
                        <?php wp_nonce_field('nitrosearch_connect'); ?>
                        <input type="hidden" name="action" value="nitrosearch_connect">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="ns_connect_token">Provisioning token</label></th>
                                <td>
                                    <input name="connect_token" id="ns_connect_token" type="text"
                                        class="regular-text" autocomplete="off"
                                        value="<?php echo esc_attr((string) Settings::get('connect_token')); ?>">
                                    <p class="description">Optional. Only needed if you were given a token to connect this store.</p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Connect store', 'primary'); ?>
                    </form>
                </div>
            <?php elseif (! $ready) : ?>
                <div class="ns-card">
                    <div class="notice notice-warning inline"><p>
                        <strong>Confirming control of your site…</strong>
                        We’re verifying that you control this store before building your search
                        index. In production this is automatic; if your site isn’t reachable
                        from our servers it can take a moment. Your catalogue syncs as soon as
                        it’s confirmed.
                    </p></div>
                    <div class="ns-actions">
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_refresh'); ?>
                            <input type="hidden" name="action" value="nitrosearch_refresh">
                            <?php submit_button('Check status', 'primary', 'submit', false); ?>
                        </form>
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_disconnect'); ?>
                            <input type="hidden" name="action" value="nitrosearch_disconnect">
                            <?php submit_button('Disconnect', 'delete', 'submit', false); ?>
                        </form>
                    </div>
                </div>
            <?php else :
                $count = (int) Settings::get('product_count');
                $limit = (int) Settings::get('product_limit');
                $pct = $limit > 0 ? min(100, (int) round($count / $limit * 100)) : 0;
                $atLimit = (bool) Settings::get('at_limit');
                ?>
                <?php if ($atLimit) : ?>
                    <div class="notice notice-warning inline ns-notice"><p>
                        <strong>You’ve reached your plan’s product limit.</strong>
                        Your search keeps running for the products already indexed, but new products won’t be added until you upgrade. Open <em>Manage / Upgrade</em> below to move to a bigger plan.
                    </p></div>
                <?php endif; ?>
                <div class="ns-card">
                    <h2 class="ns-card__title">Sync health</h2>
                    <div class="ns-stats">
                        <div class="ns-stat">
                            <div class="ns-stat__label">Products indexed</div>
                            <div class="ns-stat__value"><?php echo esc_html(number_format_i18n($count)); ?> <span style="color:#94a3b8;font-weight:500;">/ <?php echo esc_html(number_format_i18n($limit)); ?></span></div>
                            <div class="ns-progress"><div class="ns-progress__fill" style="width:<?php echo esc_attr((string) max($pct, 2)); ?>%"></div></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label">Pending changes</div>
                            <div class="ns-stat__value"><?php echo esc_html(number_format_i18n((int) Outbox::pendingCount())); ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label">Last sync</div>
                            <div class="ns-stat__value" style="font-size:14px;font-weight:600;"><?php echo esc_html((string) (Settings::get('last_sync') ?: 'Never')); ?></div>
                        </div>
                        <?php if (Settings::get('last_error')) : ?>
                            <div class="ns-stat">
                                <div class="ns-stat__label">Last error</div>
                                <div class="ns-stat__value ns-stat__value--error"><?php echo esc_html((string) Settings::get('last_error')); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ns-actions">
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_sync'); ?>
                            <input type="hidden" name="action" value="nitrosearch_sync">
                            <?php submit_button('Sync all products', 'primary', 'submit', false); ?>
                        </form>
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_refresh'); ?>
                            <input type="hidden" name="action" value="nitrosearch_refresh">
                            <?php submit_button('Refresh status', 'secondary', 'submit', false); ?>
                        </form>
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_disconnect'); ?>
                            <input type="hidden" name="action" value="nitrosearch_disconnect">
                            <?php submit_button('Disconnect', 'delete', 'submit', false); ?>
                        </form>
                    </div>
                </div>

                <?php
                $avgMs = (int) Settings::get('avg_batch_ms');
                $lastMs = (int) Settings::get('last_batch_ms');
                $itemsTotal = (int) Settings::get('sync_items_total');
                $batchesTotal = (int) Settings::get('sync_batches_total');
                $nextDrain = function_exists('as_next_scheduled_action') ? as_next_scheduled_action(Drain::HOOK) : false;
                $nextLabel = $nextDrain ? human_time_diff(time(), (int) $nextDrain).' from now' : 'On demand';
                ?>
                <div class="ns-card">
                    <h2 class="ns-card__title">Sync performance</h2>
                    <div class="ns-stats">
                        <div class="ns-stat">
                            <div class="ns-stat__label">Avg sync speed</div>
                            <div class="ns-stat__value"><?php echo $avgMs > 0 ? esc_html(number_format_i18n($avgMs)).' <span style="color:#94a3b8;font-weight:500;">ms</span>' : '—'; ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label">Last batch</div>
                            <div class="ns-stat__value"><?php echo $lastMs > 0 ? esc_html(number_format_i18n($lastMs)).' <span style="color:#94a3b8;font-weight:500;">ms</span>' : '—'; ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label">Products synced</div>
                            <div class="ns-stat__value"><?php echo esc_html(number_format_i18n($itemsTotal)); ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label">Batches sent</div>
                            <div class="ns-stat__value"><?php echo esc_html(number_format_i18n($batchesTotal)); ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label">Next sync</div>
                            <div class="ns-stat__value" style="font-size:14px;font-weight:600;"><?php echo esc_html($nextLabel); ?></div>
                        </div>
                    </div>
                    <p class="ns-card__intro" style="margin-top:12px;margin-bottom:0;">
                        Changes to your catalogue are batched and pushed in the background — these figures show how quickly they reach your search index.
                    </p>
                </div>

                <div class="ns-card">
                    <h2 class="ns-card__title">Your plan &amp; account</h2>
                    <?php if (Settings::get('claimed')) : ?>
                        <div class="ns-confirm">
                            <span class="ns-confirm__check" aria-hidden="true">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span>This store is claimed to a NitroSearch account. Manage your plan, settings and analytics from your NitroSearch dashboard.</span>
                        </div>
                    <?php else : ?>
                        <p class="ns-card__intro">
                            You're on the <strong>Free</strong> plan. Claim this store to a NitroSearch account to manage it,
                            see search analytics, or upgrade — your index and search stay exactly as they are.
                        </p>
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_claim'); ?>
                            <input type="hidden" name="action" value="nitrosearch_claim">
                            <?php submit_button('Manage / Upgrade', 'primary', 'submit', false); ?>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="ns-card">
                    <h2 class="ns-card__title">Appearance</h2>
                    <form method="post" action="<?php echo esc_url($action); ?>">
                        <?php wp_nonce_field('nitrosearch_appearance'); ?>
                        <input type="hidden" name="action" value="nitrosearch_appearance">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="ns_accent">Accent colour</label></th>
                                <td>
                                    <input name="accent_color" id="ns_accent" type="text" class="regular-text"
                                        placeholder="#111827" value="<?php echo esc_attr((string) Settings::get('accent_color')); ?>">
                                    <p class="description">Hex colour for prices, highlights and selected filters. Leave blank for the default.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ns_selector">Search box selector</label></th>
                                <td>
                                    <input name="selector" id="ns_selector" type="text" class="regular-text"
                                        placeholder="e.g. input.my-theme-search" value="<?php echo esc_attr((string) Settings::get('selector')); ?>">
                                    <p class="description">Optional CSS selector for your theme's search input. Leave blank to auto-detect.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Search results page</th>
                                <td>
                                    <label>
                                        <input name="results_takeover" id="ns_results" type="checkbox" value="1"
                                            <?php checked((bool) Settings::get('results_takeover', true)); ?>>
                                        Enhance the product search results page with NitroSearch results and filters
                                    </label>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Save appearance', 'secondary'); ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * The NitroSearch flame mark — an elongated-hex frame around a forward "N".
     * Inline SVG so it renders without an external request; a fixed gradient id
     * keeps it self-contained on the single-instance settings screen.
     */
    private static function markSvg(): string
    {
        return '<svg class="ns-hero__mark" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            .'<defs><linearGradient id="ns-hero-flame" x1="4" y1="6" x2="44" y2="42" gradientUnits="userSpaceOnUse">'
            .'<stop stop-color="#35e3e0"/><stop offset="0.5" stop-color="#38a5f6"/><stop offset="1" stop-color="#6366f1"/>'
            .'</linearGradient></defs>'
            .'<path d="M14 5 H34 L45 24 L34 43 H14 L3 24 Z" stroke="url(#ns-hero-flame)" stroke-width="2.4" stroke-linejoin="round"/>'
            .'<path d="M17 33 V15 L31 33 V15" stroke="url(#ns-hero-flame)" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>'
            .'</svg>';
    }

    public function handleConnect(): void
    {
        $this->authorize('nitrosearch_connect');

        $token = isset($_POST['connect_token']) ? sanitize_text_field(wp_unslash($_POST['connect_token'])) : '';
        Settings::update(['connect_token' => $token]);

        $result = Client::connect();
        if (! $result['ok']) {
            $this->redirect('Connect failed: '.($result['error'] ?? 'unknown error'));
        }

        // Connect provisions only a shell. Prove control before syncing: in
        // production the backend loopbacks to this site and hands back the search
        // key immediately; if it can't reach us (firewalled), verification stays
        // pending and the merchant confirms via "Check status".
        Client::verify();
        if (Settings::hasSearchKey()) {
            $count = Hooks::fullSync();
            Drain::schedule();
            $this->redirect("Connected and verified. Queued {$count} products for sync.");
        }
        $this->redirect('Connected. Confirming control of your site — this can take a moment. Use “Check status” below if it doesn’t update.');
    }

    /**
     * Poll the backend for verification + sync health. If control has just been
     * confirmed (loopback out-of-band), fetch the search key and kick off the first
     * full sync; otherwise retry the loopback.
     */
    public function handleRefresh(): void
    {
        $this->authorize('nitrosearch_refresh');

        $wasReady = Settings::hasSearchKey();
        $status = Client::status();

        if ($status['verified'] && ! Settings::hasSearchKey()) {
            Client::fetchSearchKey();
        } elseif (! $status['verified']) {
            Client::verify(); // retry the loopback in case the site is now reachable
        }

        if (! $wasReady && Settings::hasSearchKey()) {
            $count = Hooks::fullSync();
            Drain::schedule();
            $this->redirect("Verified! Queued {$count} products for sync.");
        }
        if (Settings::hasSearchKey()) {
            $this->redirect((int) Settings::get('product_count').' products indexed · '.(int) Outbox::pendingCount().' pending.');
        }
        $this->redirect('Still confirming control of your site — please try again in a moment.');
    }

    public function handleSync(): void
    {
        $this->authorize('nitrosearch_sync');
        $count = Hooks::fullSync();
        Drain::schedule();
        $this->redirect("Queued {$count} products for sync.");
    }

    public function handleAppearance(): void
    {
        $this->authorize('nitrosearch_appearance');
        $accent = isset($_POST['accent_color']) ? sanitize_hex_color(wp_unslash($_POST['accent_color'])) : '';
        $selector = isset($_POST['selector']) ? sanitize_text_field(wp_unslash($_POST['selector'])) : '';
        Settings::update([
            'accent_color' => (string) $accent,
            'selector' => $selector,
            'results_takeover' => isset($_POST['results_takeover']),
        ]);
        $this->redirect('Appearance saved.');
    }

    public function handleDisconnect(): void
    {
        $this->authorize('nitrosearch_disconnect');
        Settings::update([
            'connected' => false, 'sync_key_id' => '', 'sync_secret' => '',
            'scoped_search_key' => '', 'search_public_id' => '',
        ]);
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions(Drain::HOOK);
        }
        $this->redirect('Disconnected.');
    }

    /**
     * Mint a single-use claim/manage link and send the owner to the NitroSearch
     * portal to finish claiming (or upgrading) this store. The token rides the URL
     * FRAGMENT (#token=…), which survives the redirect but is never sent to a server
     * or logged. A cross-host redirect, so wp_redirect (not wp_safe_redirect).
     */
    public function handleClaim(): void
    {
        $this->authorize('nitrosearch_claim');

        $result = Client::claimLink();
        if (! empty($result['ok']) && ! empty($result['claim_url'])) {
            wp_redirect($result['claim_url']); // phpcs:ignore WordPress.Security.SafeRedirect
            exit;
        }

        $messages = [
            'already_claimed'   => 'This store is already claimed to a NitroSearch account.',
            'not_verified'      => 'We couldn’t confirm control of your site yet — click "Refresh status" first, then try again.',
            'reverify_required' => 'Your site needs re-checking — click "Refresh status", then try again.',
            'rate_limited'      => 'Too many attempts — please wait a minute and try again.',
        ];
        $reason = (string) ($result['error'] ?? '');
        $this->redirect($messages[$reason] ?? ('Could not create a manage link. Please try again.'));
    }

    private function authorize(string $nonce): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions.');
        }
        check_admin_referer($nonce);
    }

    private function redirect(string $notice): void
    {
        wp_safe_redirect(add_query_arg(
            ['page' => 'nitrosearch', 'ns_notice' => rawurlencode($notice)],
            admin_url('admin.php')
        ));
        exit;
    }
}
