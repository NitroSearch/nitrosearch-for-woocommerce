<?php

namespace NitroSearch\Admin;

use NitroSearch\Api\Client;
use NitroSearch\Settings;
use NitroSearch\Sync\ContentPurge;
use NitroSearch\Sync\Drain;
use NitroSearch\Sync\FullSync;
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
        add_action('admin_post_nitrosearch_dismiss_usage', [$this, 'handleDismissUsage']);
        add_action('admin_notices', [$this, 'usageNotice']);
    }

    /**
     * One-time notice when the 1.5.x line starts collecting anonymous search
     * usage counts: default on, plainly described, with the toggle one click
     * away. Shown until dismissed, on our screen and the plugins screen only —
     * a merchant who never visits either is not nagged elsewhere.
     */
    public function usageNotice(): void
    {
        if (! get_option('nitrosearch_usage_notice') || ! current_user_can('manage_woocommerce')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || ! in_array($screen->id, ['plugins', 'toplevel_page_nitrosearch'], true)) {
            return;
        }
        $settingsUrl = admin_url('admin.php?page=nitrosearch');
        $dismissUrl = wp_nonce_url(admin_url('admin-post.php?action=nitrosearch_dismiss_usage'), 'nitrosearch_dismiss_usage');
        ?>
        <div class="notice notice-info">
            <p>
                <?php echo wp_kses(__("<strong>NitroSearch now measures how your store's search performs</strong> — anonymous, cookieless counts of searches and result clicks, with no shopper identifiers. It helps result ranking improve, and per-store reporting is on the roadmap.", 'nitrosearch'), ['strong' => []]); ?>
                <?php
                printf(
                    /* translators: %s: a link to the NitroSearch settings screen, labelled "NitroSearch → Appearance". */
                    esc_html__('Manage it under %s.', 'nitrosearch'),
                    '<a href="'.esc_url($settingsUrl).'">'.esc_html__('NitroSearch → Appearance', 'nitrosearch').'</a>'
                );
                ?>
                &nbsp;<a href="<?php echo esc_url($dismissUrl); ?>"><?php esc_html_e('Dismiss', 'nitrosearch'); ?></a>
            </p>
        </div>
        <?php
    }

    public function handleDismissUsage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Unauthorized.', 'nitrosearch'));
        }
        check_admin_referer('nitrosearch_dismiss_usage');
        delete_option('nitrosearch_usage_notice');
        update_option('nitrosearch_usage_notice_dismissed', '1', false);
        wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=nitrosearch'));
        exit;
    }

    public function menu(): void
    {
        $this->hook = (string) add_menu_page(
            'NitroSearch',
            'NitroSearch',
            'manage_woocommerce',
            'nitrosearch',
            [$this, 'render'],
            self::menuIcon(),
            58
        );
    }

    /**
     * The brand mark for the admin menu, as a base64 SVG data URI.
     *
     * The encoding matters: WordPress tests for the literal
     * `data:image/svg+xml;base64,` prefix, and only then marks the icon as an SVG
     * and lets `svg-painter.js` recolour it for the user's admin colour scheme
     * (base, hover and current states). A URL-encoded data URI, or a file URL,
     * silently falls back to a plain <img> that never matches the scheme.
     *
     * The painter rewrites `fill` attributes ONLY — it ignores `stroke` and CSS
     * `style` — so this is a purpose-built filled version of the hero mark rather
     * than the stroked, gradient-filled artwork used on the settings screen.
     * `fill="black"` is the correct value to ship: it is what the painter expects
     * to replace, and it is a sane fallback if the painter never runs.
     */
    private static function menuIcon(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">'
            // Elongated-hex frame, drawn as a ring (outer path, inner path, even-odd).
            .'<path fill="black" fill-rule="evenodd" d="M5.6 1.6H14.4L19.3 10L14.4 18.4H5.6L0.7 10Z'
            .'M6.3 2.8H13.7L17.9 10L13.7 17.2H6.3L2.1 10Z"/>'
            // The forward-leaning N: two uprights and the diagonal between them.
            .'<path fill="black" d="M6.5 5.7H7.9V14.3H6.5Z"/>'
            .'<path fill="black" d="M12.1 5.7H13.5V14.3H12.1Z"/>'
            .'<path fill="black" d="M6.5 5.7H7.9L13.5 14.3H12.1Z"/>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
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
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin-notice string, for display only; sanitized, no state change.
        $notice = isset($_GET['ns_notice']) ? sanitize_text_field(wp_unslash($_GET['ns_notice'])) : '';
        $action = admin_url('admin-post.php');
        // Which tab to show. Anything unrecognised falls back to the dashboard, and
        // the Design tab is unreachable until the store is search-ready — there is
        // nothing to style before then.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view selector; no state change.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'dashboard';
        $tab = ($tab === 'design' && $ready) ? 'design' : 'dashboard';

        if (! $connected) {
            $pillClass = '';
            $pillText = __('Not connected', 'nitrosearch');
        } elseif (! $ready) {
            $pillClass = 'ns-pill--pending';
            $pillText = __('Confirming control…', 'nitrosearch');
        } else {
            $pillClass = 'ns-pill--ok';
            $pillText = __('Connected & verified', 'nitrosearch');
        }
        ?>
        <div class="wrap nitrosearch-admin">
            <h1>NitroSearch</h1>

            <div class="ns-hero">
                <div class="ns-hero__brand">
                    <?php echo self::markSvg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG ?>
                    <div class="ns-hero__text">
                        <p class="ns-hero__wordmark">Nitro<span>Search</span></p>
                        <p class="ns-hero__tagline"><?php esc_html_e('Amazon-quality search for WooCommerce', 'nitrosearch'); ?></p>
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
                    <h2 class="ns-card__title"><?php esc_html_e('Connect your store', 'nitrosearch'); ?></h2>
                    <p class="ns-card__intro">
                        <?php esc_html_e('Connect this store to NitroSearch to start syncing your catalogue and serving instant, typo-tolerant search. Nothing leaves your site until you click Connect.', 'nitrosearch'); ?>
                    </p>
                    <form method="post" action="<?php echo esc_url($action); ?>">
                        <?php wp_nonce_field('nitrosearch_connect'); ?>
                        <input type="hidden" name="action" value="nitrosearch_connect">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="ns_connect_token"><?php esc_html_e('Provisioning token', 'nitrosearch'); ?></label></th>
                                <td>
                                    <input name="connect_token" id="ns_connect_token" type="text"
                                        class="regular-text" autocomplete="off"
                                        value="<?php echo esc_attr((string) Settings::get('connect_token')); ?>">
                                    <p class="description"><?php esc_html_e('Optional. Only needed if you were given a token to connect this store.', 'nitrosearch'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Connect store', 'nitrosearch'), 'primary'); ?>
                    </form>
                </div>
            <?php elseif (! $ready) : ?>
                <div class="ns-card">
                    <div class="notice notice-warning inline"><p>
                        <strong><?php esc_html_e('Confirming control of your site…', 'nitrosearch'); ?></strong>
                        <?php esc_html_e('We’re verifying that you control this store before building your search index. In production this is automatic; if your site isn’t reachable from our servers it can take a moment. Your catalogue syncs as soon as it’s confirmed.', 'nitrosearch'); ?>
                    </p></div>
                    <div class="ns-actions">
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_refresh'); ?>
                            <input type="hidden" name="action" value="nitrosearch_refresh">
                            <?php submit_button(__('Check status', 'nitrosearch'), 'primary', 'submit', false); ?>
                        </form>
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_disconnect'); ?>
                            <input type="hidden" name="action" value="nitrosearch_disconnect">
                            <?php submit_button(__('Disconnect', 'nitrosearch'), 'delete', 'submit', false); ?>
                        </form>
                    </div>
                </div>
            <?php else :
                $count = (int) Settings::get('product_count');
                $limit = (int) Settings::get('product_limit');
                // An unlimited plan arrives as a sentinel, because the wire field is an
                // integer and has no way to say "none". Rendered literally it read
                // "81 / 1,000,000,000" with a progress bar pinned at its 2% floor —
                // which is not what an Enterprise merchant is paying for.
                $unlimited = Settings::hasUnlimitedPlan();
                $pct = $unlimited ? 100 : ($limit > 0 ? min(100, (int) round($count / $limit * 100)) : 0);
                $atLimit = ! $unlimited && (bool) Settings::get('at_limit');
                $tabBase = admin_url('admin.php?page=nitrosearch');
                ?>
                <nav class="nav-tab-wrapper ns-tabs">
                    <a href="<?php echo esc_url($tabBase); ?>"
                        class="nav-tab <?php echo $tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e('Dashboard', 'nitrosearch'); ?>
                    </a>
                    <a href="<?php echo esc_url($tabBase.'&tab=design'); ?>"
                        class="nav-tab <?php echo $tab === 'design' ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e('Design', 'nitrosearch'); ?>
                    </a>
                </nav>
                <?php if ($tab === 'design') :
                    $this->renderDesign($action);
                else : ?>
                <?php if ($atLimit) : ?>
                    <div class="notice notice-warning inline ns-notice"><p>
                        <strong><?php esc_html_e('You’ve reached your plan’s limit.', 'nitrosearch'); ?></strong>
                        <?php
                        printf(
                            /* translators: %s: the "Manage / Upgrade" button label, wrapped in <em>. Translate it the same way as the button. */
                            esc_html__('Your search keeps running for everything already indexed, but new items won’t be added until you upgrade. Your products always take priority, so anything held back is a page or a post. Open %s below to move to a bigger plan.', 'nitrosearch'),
                            '<em>'.esc_html__('Manage / Upgrade', 'nitrosearch').'</em>'
                        );
                        ?>
                    </p></div>
                <?php endif; ?>
                <div class="ns-card">
                    <h2 class="ns-card__title"><?php esc_html_e('Sync health', 'nitrosearch'); ?></h2>
                    <div class="ns-stats">
                        <div class="ns-stat">
                            <div class="ns-stat__label"><?php esc_html_e('Search results indexed', 'nitrosearch'); ?></div>
                            <div class="ns-stat__value"><?php echo esc_html(number_format_i18n($count)); ?> <span style="color:#94a3b8;font-weight:500;">/ <?php echo $unlimited ? esc_html__('Unlimited', 'nitrosearch') : esc_html(number_format_i18n($limit)); ?></span></div>
                            <div class="ns-progress"><div class="ns-progress__fill" style="width:<?php echo esc_attr((string) max($pct, 2)); ?>%"></div></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label"><?php esc_html_e('Pending changes', 'nitrosearch'); ?></div>
                            <div class="ns-stat__value"><?php echo esc_html(number_format_i18n((int) Outbox::pendingCount())); ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label"><?php esc_html_e('Last sync', 'nitrosearch'); ?></div>
                            <?php
                            // Stored as UTC (Drain writes current_time('mysql', true));
                            // shown in the site's own timezone, date format and language.
                            $lastSyncUtc = (string) Settings::get('last_sync');
                            $lastSyncLabel = $lastSyncUtc !== ''
                                ? (string) wp_date(get_option('date_format').' '.get_option('time_format'), strtotime($lastSyncUtc.' +00:00'))
                                : __('Never', 'nitrosearch');
                            ?>
                            <div class="ns-stat__value" style="font-size:14px;font-weight:600;"><?php echo esc_html($lastSyncLabel); ?></div>
                        </div>
                        <?php if (Settings::get('last_error')) : ?>
                            <div class="ns-stat">
                                <div class="ns-stat__label"><?php esc_html_e('Last error', 'nitrosearch'); ?></div>
                                <div class="ns-stat__value ns-stat__value--error"><?php echo esc_html((string) Settings::get('last_error')); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ns-actions">
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_sync'); ?>
                            <input type="hidden" name="action" value="nitrosearch_sync">
                            <?php submit_button(__('Sync all products', 'nitrosearch'), 'primary', 'submit', false); ?>
                        </form>
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_refresh'); ?>
                            <input type="hidden" name="action" value="nitrosearch_refresh">
                            <?php submit_button(__('Refresh status', 'nitrosearch'), 'secondary', 'submit', false); ?>
                        </form>
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_disconnect'); ?>
                            <input type="hidden" name="action" value="nitrosearch_disconnect">
                            <?php submit_button(__('Disconnect', 'nitrosearch'), 'delete', 'submit', false); ?>
                        </form>
                    </div>
                </div>

                <?php
                $avgMs = (int) Settings::get('avg_batch_ms');
                $lastMs = (int) Settings::get('last_batch_ms');
                $itemsTotal = (int) Settings::get('sync_items_total');
                $batchesTotal = (int) Settings::get('sync_batches_total');
                $nextDrain = function_exists('as_next_scheduled_action') ? as_next_scheduled_action(Drain::HOOK) : false;
                $nextLabel = $nextDrain
                    /* translators: %s: a human-readable time interval, e.g. "4 mins". */
                    ? sprintf(__('%s from now', 'nitrosearch'), human_time_diff(time(), (int) $nextDrain))
                    : __('On demand', 'nitrosearch');
                $msUnit = ' <span style="color:#94a3b8;font-weight:500;">'.esc_html(_x('ms', 'unit: milliseconds', 'nitrosearch')).'</span>';
                ?>
                <div class="ns-card">
                    <h2 class="ns-card__title"><?php esc_html_e('Sync performance', 'nitrosearch'); ?></h2>
                    <div class="ns-stats">
                        <div class="ns-stat">
                            <div class="ns-stat__label"><?php esc_html_e('Avg sync speed', 'nitrosearch'); ?></div>
                            <div class="ns-stat__value"><?php echo $avgMs > 0 ? esc_html(number_format_i18n($avgMs)).$msUnit : '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- both halves escaped above ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label"><?php esc_html_e('Last batch', 'nitrosearch'); ?></div>
                            <div class="ns-stat__value"><?php echo $lastMs > 0 ? esc_html(number_format_i18n($lastMs)).$msUnit : '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- both halves escaped above ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label"><?php esc_html_e('Items synced', 'nitrosearch'); ?></div>
                            <div class="ns-stat__value"><?php echo esc_html(number_format_i18n($itemsTotal)); ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label"><?php esc_html_e('Batches sent', 'nitrosearch'); ?></div>
                            <div class="ns-stat__value"><?php echo esc_html(number_format_i18n($batchesTotal)); ?></div>
                        </div>
                        <div class="ns-stat">
                            <div class="ns-stat__label"><?php esc_html_e('Next sync', 'nitrosearch'); ?></div>
                            <div class="ns-stat__value" style="font-size:14px;font-weight:600;"><?php echo esc_html($nextLabel); ?></div>
                        </div>
                    </div>
                    <p class="ns-card__intro" style="margin-top:12px;margin-bottom:0;">
                        <?php esc_html_e('Changes to your catalogue are batched and pushed in the background — these figures show how quickly they reach your search index.', 'nitrosearch'); ?>
                    </p>
                </div>

                <div class="ns-card">
                    <h2 class="ns-card__title"><?php esc_html_e('Search analytics', 'nitrosearch'); ?> <span class="ns-muted"><?php esc_html_e('last 30 days', 'nitrosearch'); ?></span></h2>
                    <?php $ana = $this->analyticsSummary(); ?>
                    <?php if ($ana === null) : ?>
                        <p class="ns-muted"><?php esc_html_e('Couldn’t load analytics just now — it will retry automatically.', 'nitrosearch'); ?></p>
                    <?php elseif (empty($ana['entitled'])) : ?>
                        <p>
                            <?php
                            $teaserCount = (int) ($ana['teaser']['searches_30d'] ?? 0);
                            printf(
                                /* translators: 1: "N searches" (already localized), shown in bold. 2: the entry price of the cheapest paid plan (always billed in US dollars). */
                                wp_kses(__('<strong>%1$s</strong> on your store in the last 30 days. See what shoppers searched for, what they clicked, and what they looked for and didn’t find — included on every paid plan, from %2$s.', 'nitrosearch'), ['strong' => []]),
                                esc_html(sprintf(
                                    /* translators: %s: number of searches. */
                                    _n('%s search', '%s searches', $teaserCount, 'nitrosearch'),
                                    number_format_i18n($teaserCount)
                                )),
                                esc_html(sprintf(
                                    /* translators: %s: a price in US dollars, e.g. "$5.99". "mo" is short for month. */
                                    __('%s/mo', 'nitrosearch'),
                                    '$5.99'
                                ))
                            );
                            ?>
                        </p>
                        <p><a class="button button-primary" href="<?php echo esc_url((string) (($ana['portal_url'] ?? '') ?: 'https://app.nitrosearch.io')); ?>" target="_blank" rel="noopener"><?php esc_html_e('Upgrade to unlock', 'nitrosearch'); ?></a></p>
                    <?php elseif (empty($ana['collecting'])) : ?>
                        <p class="ns-muted"><?php esc_html_e('Analytics starts collecting with plugin 1.5.0+ active — data appears within a few hours of shoppers searching.', 'nitrosearch'); ?></p>
                    <?php else : ?>
                        <div class="ns-stats">
                            <div class="ns-stat"><span class="ns-stat__label"><?php esc_html_e('Searches', 'nitrosearch'); ?></span>
                                <span class="ns-stat__value"><?php echo esc_html(number_format_i18n((int) ($ana['searches'] ?? 0))); ?></span></div>
                            <div class="ns-stat"><span class="ns-stat__label"><?php esc_html_e('Zero-result rate', 'nitrosearch'); ?></span>
                                <span class="ns-stat__value"><?php echo isset($ana['zero_rate']) && $ana['zero_rate'] !== null ? esc_html(number_format_i18n(((float) $ana['zero_rate']) * 100, 1).'%') : '&#8212;'; ?></span></div>
                            <div class="ns-stat"><span class="ns-stat__label"><?php esc_html_e('Click-through', 'nitrosearch'); ?></span>
                                <span class="ns-stat__value"><?php echo isset($ana['ctr']) && $ana['ctr'] !== null ? esc_html(number_format_i18n(((float) $ana['ctr']) * 100, 1).'%') : '&#8212;'; ?></span></div>
                        </div>
                        <?php if (! empty($ana['top_queries']) || ! empty($ana['top_zero'])) : ?>
                            <div class="ns-columns">
                                <?php if (! empty($ana['top_queries'])) : ?>
                                    <div>
                                        <h3 class="ns-subhead"><?php esc_html_e('Top searches', 'nitrosearch'); ?></h3>
                                        <ol class="ns-list">
                                            <?php foreach (array_slice((array) $ana['top_queries'], 0, 5) as $row) : ?>
                                                <li><?php echo esc_html((string) ($row['q'] ?? '')); ?> <span class="ns-muted">&times;<?php echo esc_html(number_format_i18n((int) ($row['n'] ?? 0))); ?></span></li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </div>
                                <?php endif; ?>
                                <?php if (! empty($ana['top_zero'])) : ?>
                                    <div>
                                        <h3 class="ns-subhead"><?php esc_html_e('Searched, found nothing', 'nitrosearch'); ?></h3>
                                        <ol class="ns-list">
                                            <?php foreach (array_slice((array) $ana['top_zero'], 0, 5) as $row) : ?>
                                                <li><?php echo esc_html((string) ($row['q'] ?? '')); ?> <span class="ns-muted">&times;<?php echo esc_html(number_format_i18n((int) ($row['n'] ?? 0))); ?></span></li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <p><a href="<?php echo esc_url((string) (($ana['portal_url'] ?? '') ?: 'https://app.nitrosearch.io')); ?>" target="_blank" rel="noopener"><?php esc_html_e('View full analytics →', 'nitrosearch'); ?></a></p>
                    <?php endif; ?>
                </div>

                <div class="ns-card">
                    <h2 class="ns-card__title"><?php esc_html_e('Your plan & account', 'nitrosearch'); ?></h2>
                    <?php if (Settings::get('claimed')) : ?>
                        <div class="ns-confirm">
                            <span class="ns-confirm__check" aria-hidden="true">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span><?php esc_html_e('This store is claimed to a NitroSearch account. Manage your plan and billing from your NitroSearch dashboard.', 'nitrosearch'); ?></span>
                        </div>
                    <?php else : ?>
                        <p class="ns-card__intro">
                            <?php echo wp_kses(__("You're on the <strong>Free</strong> plan. Claim this store to a NitroSearch account to manage it or upgrade — your index and search stay exactly as they are.", 'nitrosearch'), ['strong' => []]); ?>
                        </p>
                        <form method="post" action="<?php echo esc_url($action); ?>">
                            <?php wp_nonce_field('nitrosearch_claim'); ?>
                            <input type="hidden" name="action" value="nitrosearch_claim">
                            <?php submit_button(__('Manage / Upgrade', 'nitrosearch'), 'primary', 'submit', false); ?>
                        </form>
                    <?php endif; ?>
                </div>

                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * The Design tab: how the search box looks on the storefront.
     *
     * Every control here resolves to a widget design token in Support\Design, so
     * nothing on this screen enlarges the JavaScript your shoppers download. The
     * defaults are what a store gets without ever opening this tab, and they are
     * chosen to look right on a standard WooCommerce theme.
     */
    private function renderDesign(string $action): void
    {
        $look = (string) Settings::get('design_look', 'roomy');
        $scheme = (string) Settings::get('design_scheme', 'light');
        $font = (string) Settings::get('design_font', 'store');
        $looks = [
            'roomy'   => [__('Roomy', 'nitrosearch'), __('Two-line names and a clear picture. The default.', 'nitrosearch')],
            'compact' => [__('Compact', 'nitrosearch'), __('Smaller rows, so more products fit before scrolling.', 'nitrosearch')],
            'images'  => [__('Big pictures', 'nitrosearch'), __('Larger thumbnails for image-led catalogues.', 'nitrosearch')],
            'text'    => [__('Text only', 'nitrosearch'), __('No pictures — good for spares, parts and B2B.', 'nitrosearch')],
        ];
        $schemes = [
            'light'  => [__('Light', 'nitrosearch'), __('A white panel. Suits most themes.', 'nitrosearch')],
            'dark'   => [__('Dark', 'nitrosearch'), __('A dark panel, for dark headers and themes.', 'nitrosearch')],
            'auto'   => [__('Automatic', 'nitrosearch'), __("Follows each shopper's own device setting.", 'nitrosearch')],
            'custom' => [__('Custom', 'nitrosearch'), __('Choose your own panel and text colours below.', 'nitrosearch')],
        ];
        ?>
        <div class="ns-card">
            <h2 class="ns-card__title"><?php esc_html_e('Look', 'nitrosearch'); ?></h2>
            <form method="post" action="<?php echo esc_url($action); ?>">
                <?php wp_nonce_field('nitrosearch_appearance'); ?>
                <input type="hidden" name="action" value="nitrosearch_appearance">

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Layout', 'nitrosearch'); ?></th>
                        <td>
                            <?php foreach ($looks as $key => $meta) : ?>
                                <label style="display:block;margin-bottom:6px;">
                                    <input type="radio" name="design_look" value="<?php echo esc_attr($key); ?>"
                                        <?php checked($look, $key); ?>>
                                    <strong><?php echo esc_html($meta[0]); ?></strong>
                                    <span class="description">&mdash; <?php echo esc_html($meta[1]); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ns_per_page"><?php esc_html_e('Products shown', 'nitrosearch'); ?></label></th>
                        <td>
                            <select name="design_per_page" id="ns_per_page">
                                <?php foreach ([4, 6, 8, 10, 12] as $n) : ?>
                                    <option value="<?php echo esc_attr((string) $n); ?>"
                                        <?php selected((int) Settings::get('design_per_page', 8), $n); ?>>
                                        <?php echo esc_html((string) $n); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('How many products appear in the drop-down as your shopper types.', 'nitrosearch'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ns_filters"><?php esc_html_e('Filters', 'nitrosearch'); ?></label></th>
                        <td>
                            <select name="design_filters" id="ns_filters">
                                <option value="auto" <?php selected((string) Settings::get('design_filters', 'auto'), 'auto'); ?>>
                                    <?php esc_html_e('Automatic (recommended)', 'nitrosearch'); ?>
                                </option>
                                <option value="top" <?php selected((string) Settings::get('design_filters', 'auto'), 'top'); ?>>
                                    <?php esc_html_e('Always across the top', 'nitrosearch'); ?>
                                </option>
                                <option value="off" <?php selected((string) Settings::get('design_filters', 'auto'), 'off'); ?>>
                                    <?php esc_html_e('Hide in the drop-down', 'nitrosearch'); ?>
                                </option>
                            </select>
                            <p class="description"><?php esc_html_e('In stock, on sale, brand and category filters. Automatic puts them in a side column when there is room, and across the top when there is not.', 'nitrosearch'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ns_width"><?php esc_html_e('Drop-down width', 'nitrosearch'); ?></label></th>
                        <td>
                            <select name="design_width" id="ns_width">
                                <option value="auto" <?php selected((string) Settings::get('design_width', 'auto'), 'auto'); ?>>
                                    <?php esc_html_e('Automatic (recommended)', 'nitrosearch'); ?>
                                </option>
                                <option value="wide" <?php selected((string) Settings::get('design_width', 'auto'), 'wide'); ?>>
                                    <?php esc_html_e('Wide', 'nitrosearch'); ?>
                                </option>
                                <option value="match" <?php selected((string) Settings::get('design_width', 'auto'), 'match'); ?>>
                                    <?php esc_html_e('Match my search box', 'nitrosearch'); ?>
                                </option>
                            </select>
                            <p class="description"><?php esc_html_e('Automatic gives product names enough room to read, even when your theme\'s search box is narrow. Match my search box keeps it exactly as wide as the box.', 'nitrosearch'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="ns-card__title"><?php esc_html_e('Colours', 'nitrosearch'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Colour scheme', 'nitrosearch'); ?></th>
                        <td>
                            <?php foreach ($schemes as $key => $meta) : ?>
                                <label style="display:block;margin-bottom:6px;">
                                    <input type="radio" name="design_scheme" value="<?php echo esc_attr($key); ?>"
                                        <?php checked($scheme, $key); ?>>
                                    <strong><?php echo esc_html($meta[0]); ?></strong>
                                    <span class="description">&mdash; <?php echo esc_html($meta[1]); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ns_accent"><?php esc_html_e('Accent colour', 'nitrosearch'); ?></label></th>
                        <td>
                            <input name="accent_color" id="ns_accent" type="text" class="regular-text"
                                placeholder="#111827" value="<?php echo esc_attr((string) Settings::get('accent_color')); ?>">
                            <p class="description"><?php esc_html_e('Used for prices, highlights and selected filters. Leave blank for the default. Text on top of it is set to black or white automatically, whichever stays readable.', 'nitrosearch'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ns_bg"><?php esc_html_e('Panel background', 'nitrosearch'); ?></label></th>
                        <td>
                            <input name="design_bg" id="ns_bg" type="text" class="regular-text"
                                placeholder="#ffffff" value="<?php echo esc_attr((string) Settings::get('design_bg')); ?>">
                            <p class="description"><?php esc_html_e('Only used when the colour scheme is set to Custom.', 'nitrosearch'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ns_text"><?php esc_html_e('Text colour', 'nitrosearch'); ?></label></th>
                        <td>
                            <input name="design_text" id="ns_text" type="text" class="regular-text"
                                placeholder="#111827" value="<?php echo esc_attr((string) Settings::get('design_text')); ?>">
                            <p class="description"><?php esc_html_e('Only used when the colour scheme is set to Custom.', 'nitrosearch'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ns_corners"><?php esc_html_e('Corners', 'nitrosearch'); ?></label></th>
                        <td>
                            <select name="design_corners" id="ns_corners">
                                <option value="rounded" <?php selected((string) Settings::get('design_corners', 'rounded'), 'rounded'); ?>>
                                    <?php esc_html_e('Rounded', 'nitrosearch'); ?>
                                </option>
                                <option value="soft" <?php selected((string) Settings::get('design_corners', 'rounded'), 'soft'); ?>>
                                    <?php esc_html_e('Slightly rounded', 'nitrosearch'); ?>
                                </option>
                                <option value="square" <?php selected((string) Settings::get('design_corners', 'rounded'), 'square'); ?>>
                                    <?php esc_html_e('Square', 'nitrosearch'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ns_font"><?php esc_html_e('Text style', 'nitrosearch'); ?></label></th>
                        <td>
                            <select name="design_font" id="ns_font">
                                <option value="store" <?php selected($font, 'store'); ?>>
                                    <?php esc_html_e('Match my store', 'nitrosearch'); ?>
                                </option>
                                <option value="system" <?php selected($font, 'system'); ?>>
                                    <?php esc_html_e('System default', 'nitrosearch'); ?>
                                </option>
                                <option value="custom" <?php selected($font, 'custom'); ?>>
                                    <?php esc_html_e('Custom font', 'nitrosearch'); ?>
                                </option>
                            </select>
                            <p class="description"><?php esc_html_e('Match my store borrows the font your theme already uses around the search box.', 'nitrosearch'); ?></p>
                            <input name="design_font_stack" id="ns_font_stack" type="text" class="regular-text"
                                style="margin-top:6px;"
                                placeholder="<?php echo esc_attr_x('e.g. Georgia, serif', 'example font stack', 'nitrosearch'); ?>"
                                value="<?php echo esc_attr((string) Settings::get('design_font_stack')); ?>">
                            <p class="description"><?php esc_html_e('Only used when Custom font is selected.', 'nitrosearch'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="ns-card__title"><?php esc_html_e('What to search', 'nitrosearch'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Include', 'nitrosearch'); ?></th>
                        <td>
                            <?php $indexed = Settings::indexedContentTypes(); ?>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="checkbox" checked disabled> <?php esc_html_e('Products', 'nitrosearch'); ?>
                                <span class="description">(<?php esc_html_e('always indexed', 'nitrosearch'); ?>)</span>
                            </label>
                            <label style="display:block;margin-bottom:4px;">
                                <input name="index_content[]" type="checkbox" value="page"
                                    <?php checked(in_array('page', $indexed, true)); ?>> <?php esc_html_e('Pages', 'nitrosearch'); ?>
                            </label>
                            <label style="display:block;">
                                <input name="index_content[]" type="checkbox" value="post"
                                    <?php checked(in_array('post', $indexed, true)); ?>> <?php esc_html_e('Blog posts', 'nitrosearch'); ?>
                            </label>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: the technical term "noindex", wrapped in <em>. Do not translate it. */
                                    esc_html__('Pages and posts count towards the same allowance as your products, so switching them off frees it up for your catalogue. Your products always come first and are never displaced by them. Private, password-protected and unpublished content is never indexed, and we honour %s set by Yoast SEO or Rank Math (per item, per content type, or site-wide).', 'nitrosearch'),
                                    '<em>noindex</em>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Search results page', 'nitrosearch'); ?></th>
                        <td>
                            <label>
                                <input name="results_takeover" id="ns_results" type="checkbox" value="1"
                                    <?php checked((bool) Settings::get('results_takeover', true)); ?>>
                                <?php esc_html_e('Enhance the product search results page with NitroSearch results and filters', 'nitrosearch'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h2 class="ns-card__title"><?php esc_html_e('Privacy &amp; credit', 'nitrosearch'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Search usage data', 'nitrosearch'); ?></th>
                        <td>
                            <label>
                                <input name="share_search_data" id="ns_share_data" type="checkbox" value="1"
                                    <?php checked((bool) Settings::get('share_search_data', true)); ?>>
                                <?php esc_html_e('Share anonymous search usage counts with NitroSearch', 'nitrosearch'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e("Counts searches, result totals and result clicks on your store's search — cookieless and anonymous, with no shopper identifiers, no IP addresses and nothing stored in the shopper's browser. Used to improve result ranking and to power your search analytics. Untick to stop sending immediately.", 'nitrosearch'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Powered-by credit', 'nitrosearch'); ?></th>
                        <td>
                            <label>
                                <input name="show_badge" id="ns_badge" type="checkbox" value="1"
                                    <?php checked((bool) Settings::get('show_badge', false)); ?>>
                                <?php esc_html_e('Show a small “Powered by NitroSearch” credit, linking to nitrosearch.io', 'nitrosearch'); ?>
                            </label>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: the URL https://nitrosearch.io, wrapped in <code>. */
                                    esc_html__('Off by default, and entirely your choice. Turning it on adds a small credit in the search box and one line in your site footer, both linking to %s — a normal, followed link. Nothing is added to your site unless you tick this. Thank you if you do.', 'nitrosearch'),
                                    '<code>https://nitrosearch.io</code>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <details style="margin:12px 0;">
                    <summary style="cursor:pointer;"><?php esc_html_e('Advanced', 'nitrosearch'); ?></summary>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="ns_selector"><?php esc_html_e('Search box selector', 'nitrosearch'); ?></label></th>
                            <td>
                                <input name="selector" id="ns_selector" type="text" class="regular-text"
                                    placeholder="<?php echo esc_attr_x('e.g. input.my-theme-search', 'example CSS selector', 'nitrosearch'); ?>" value="<?php echo esc_attr((string) Settings::get('selector')); ?>">
                                <p class="description"><?php esc_html_e("Optional CSS selector for your theme's search input. Leave blank to auto-detect.", 'nitrosearch'); ?></p>
                            </td>
                        </tr>
                    </table>
                </details>

                <?php submit_button(__('Save design', 'nitrosearch'), 'primary'); ?>
            </form>
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

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce + capability verified in authorize() (check_admin_referer) above.
        $token = isset($_POST['connect_token']) ? sanitize_text_field(wp_unslash($_POST['connect_token'])) : '';
        Settings::update(['connect_token' => $token]);

        $result = Client::connect();
        if (! $result['ok']) {
            $this->redirect(sprintf(
                /* translators: %s: the error detail returned by the server. */
                __('Connect failed: %s', 'nitrosearch'),
                (string) (($result['error'] ?? '') ?: __('unknown error', 'nitrosearch'))
            ));
        }

        // Connect provisions only a shell. Prove control before syncing: in
        // production the backend loopbacks to this site and hands back the search
        // key immediately; if it can't reach us (firewalled), verification stays
        // pending and the merchant confirms via "Check status".
        Client::verify();
        if (Settings::hasSearchKey()) {
            $count = FullSync::start();
            $this->redirect(__('Connected and verified.', 'nitrosearch').' '.$this->syncStartedNotice($count));
        }
        $this->redirect(__('Connected. Confirming control of your site — this can take a moment. Use “Check status” below if it doesn’t update.', 'nitrosearch'));
    }

    /**
     * "Syncing N products…" as one complete, translatable sentence per variant —
     * never assembled from fragments, so every language can order it naturally.
     */
    private function syncStartedNotice(int $count): string
    {
        if (Settings::indexesContent()) {
            return sprintf(
                /* translators: %s: number of products. */
                _n('Syncing %s product in the background, then your pages and posts.', 'Syncing %s products in the background, then your pages and posts.', $count, 'nitrosearch'),
                number_format_i18n($count)
            );
        }

        return sprintf(
            /* translators: %s: number of products. */
            _n('Syncing %s product in the background.', 'Syncing %s products in the background.', $count, 'nitrosearch'),
            number_format_i18n($count)
        );
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
        // A manual refresh should also refresh the analytics card.
        delete_transient('nitrosearch_analytics_summary');

        if ($status['verified'] && ! Settings::hasSearchKey()) {
            Client::fetchSearchKey();
        } elseif (! $status['verified']) {
            Client::verify(); // retry the loopback in case the site is now reachable
        }

        if (! $wasReady && Settings::hasSearchKey()) {
            $count = FullSync::start();
            $this->redirect(__('Verified!', 'nitrosearch').' '.$this->syncStartedNotice($count));
        }
        if (Settings::hasSearchKey()) {
            $this->redirect(sprintf(
                /* translators: 1: number of items in the search index, 2: number of changes waiting to sync. */
                __('%1$s search results indexed · %2$s pending.', 'nitrosearch'),
                number_format_i18n((int) Settings::get('product_count')),
                number_format_i18n((int) Outbox::pendingCount())
            ));
        }
        $this->redirect(__('Still confirming control of your site — please try again in a moment.', 'nitrosearch'));
    }

    public function handleSync(): void
    {
        $this->authorize('nitrosearch_sync');
        $count = FullSync::start();
        $this->redirect($this->syncStartedNotice($count));
    }

    public function handleAppearance(): void
    {
        $this->authorize('nitrosearch_appearance');
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce + capability verified in authorize() (check_admin_referer) above.
        $accent = isset($_POST['accent_color']) ? sanitize_hex_color(wp_unslash($_POST['accent_color'])) : '';
        $selector = isset($_POST['selector']) ? sanitize_text_field(wp_unslash($_POST['selector'])) : '';
        // Allowlisted against what this version supports — this value decides what
        // gets sent to a public search index, so an unexpected entry must not widen
        // it. Absent (all boxes cleared) correctly means "content off".
        $submitted = isset($_POST['index_content']) && is_array($_POST['index_content'])
            ? array_map('sanitize_key', wp_unslash($_POST['index_content']))
            : [];
        $content = array_values(array_intersect($submitted, Settings::SUPPORTED_CONTENT_TYPES));

        $wasIndexing = Settings::indexedContentTypes();

        // Design choices are allowlisted to the values this version understands:
        // they end up interpolated into the storefront's CSS custom properties, so
        // a stray value must fall back to the default rather than travel.
        $choice = static function (string $field, array $allowed, string $default): string {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce + capability verified in authorize() above.
            $value = isset($_POST[$field]) ? sanitize_key(wp_unslash($_POST[$field])) : '';

            return in_array($value, $allowed, true) ? $value : $default;
        };

        // A font stack is free text by nature; strip anything that could close the
        // declaration or smuggle a fetch. The widget re-checks on the way in.
        $fontStack = isset($_POST['design_font_stack'])
            ? sanitize_text_field(wp_unslash($_POST['design_font_stack']))
            : '';
        if (preg_match('/[{};<>\\\\@]|url\s*\(/i', $fontStack)) {
            $fontStack = '';
        }

        $perPage = isset($_POST['design_per_page']) ? (int) $_POST['design_per_page'] : 8;
        $perPage = max(2, min(20, $perPage));

        Settings::update([
            'accent_color' => (string) $accent,
            'selector' => $selector,
            'index_content' => $content,
            'results_takeover' => isset($_POST['results_takeover']),
            'show_badge' => isset($_POST['show_badge']),
            'share_search_data' => isset($_POST['share_search_data']),
            'design_look' => $choice('design_look', ['roomy', 'compact', 'images', 'text'], 'roomy'),
            'design_scheme' => $choice('design_scheme', ['light', 'dark', 'auto', 'custom'], 'light'),
            'design_corners' => $choice('design_corners', ['rounded', 'soft', 'square'], 'rounded'),
            'design_font' => $choice('design_font', ['store', 'system', 'custom'], 'store'),
            'design_width' => $choice('design_width', ['auto', 'wide', 'match'], 'auto'),
            'design_filters' => $choice('design_filters', ['auto', 'top', 'off'], 'auto'),
            'design_font_stack' => $fontStack,
            'design_per_page' => $perPage,
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce + capability verified in authorize() above.
            'design_bg' => isset($_POST['design_bg']) ? (string) sanitize_hex_color(wp_unslash($_POST['design_bg'])) : '',
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce + capability verified in authorize() above.
            'design_text' => isset($_POST['design_text']) ? (string) sanitize_hex_color(wp_unslash($_POST['design_text'])) : '',
        ]);
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $newlyEnabled = array_values(array_diff($content, $wasIndexing));
        $newlyDisabled = array_values(array_diff($wasIndexing, $content));

        // Switched OFF means "take these out of my index and give me the allowance
        // back" — which is what the description above this field promises. Nothing
        // else will ever do it: the hooks stop tracking a disabled type, so those
        // documents would otherwise sit in the index forever, still being returned
        // by the storefront and still consuming the plan.
        if ($newlyDisabled !== []) {
            ContentPurge::start($newlyDisabled);
        }

        // Newly-enabled types are not in the index yet and no hook will fire for
        // existing content, so a full sync is the only way they appear. Scoped to
        // just those types: the catalogue is already indexed, and re-enumerating it
        // would push the whole store back through the merchant's own host to add a
        // handful of pages.
        if ($newlyEnabled !== [] && Settings::hasSearchKey()) {
            FullSync::start($newlyEnabled);
        }

        if ($newlyEnabled !== [] && Settings::hasSearchKey()) {
            $this->redirect($this->contentChangeNotice($newlyEnabled, false), 'design');
        }
        if ($newlyDisabled !== [] && Settings::hasSearchKey()) {
            $this->redirect($this->contentChangeNotice($newlyDisabled, true), 'design');
        }

        $this->redirect(__('Design saved.', 'nitrosearch'), 'design');
    }

    /**
     * The "Saved. Indexing/Removing…" notice as complete sentences per content
     * type — the wire slugs ('page', 'post') are never shown to a person, and
     * no language is asked to pluralize by appending letters.
     *
     * @param array<int,string> $types
     */
    private function contentChangeNotice(array $types, bool $removing): string
    {
        $pages = in_array('page', $types, true);
        $posts = in_array('post', $types, true);

        if ($removing) {
            if ($pages && $posts) {
                return __('Saved. Removing your pages and blog posts from your index in the background.', 'nitrosearch');
            }

            return $pages
                ? __('Saved. Removing your pages from your index in the background.', 'nitrosearch')
                : __('Saved. Removing your blog posts from your index in the background.', 'nitrosearch');
        }

        if ($pages && $posts) {
            return __('Saved. Indexing your pages and blog posts in the background.', 'nitrosearch');
        }

        return $pages
            ? __('Saved. Indexing your pages in the background.', 'nitrosearch')
            : __('Saved. Indexing your blog posts in the background.', 'nitrosearch');
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
        FullSync::cancel();
        ContentPurge::cancel();
        $this->redirect(__('Disconnected.', 'nitrosearch'));
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
            'already_claimed'   => __('This store is already claimed to a NitroSearch account.', 'nitrosearch'),
            /* translators: "Refresh status" is the button of that name on this screen — translate it the same way. */
            'not_verified'      => __('We couldn’t confirm control of your site yet — click "Refresh status" first, then try again.', 'nitrosearch'),
            /* translators: "Refresh status" is the button of that name on this screen — translate it the same way. */
            'reverify_required' => __('Your site needs re-checking — click "Refresh status", then try again.', 'nitrosearch'),
            'rate_limited'      => __('Too many attempts — please wait a minute and try again.', 'nitrosearch'),
        ];
        $reason = (string) ($result['error'] ?? '');
        $this->redirect($messages[$reason] ?? __('Could not create a manage link. Please try again.', 'nitrosearch'));
    }

    /**
     * The card's data: a signed 30-day summary, cached in a 6-hour transient —
     * the plugin's FIRST transient, because this is its first render-time
     * backend read (everything else is poll-and-persist). The remote call is
     * capped at 2s (Client::analyticsSummary) so wp-admin never hangs; a
     * failure caches a short negative marker so a down backend is retried in
     * minutes, not on every page load. Returns null when unavailable.
     *
     * @return array<string,mixed>|null
     */
    private function analyticsSummary(): ?array
    {
        if (! Settings::isConnected()) {
            return null;
        }

        $cached = get_transient('nitrosearch_analytics_summary');
        if ($cached === 'unavailable') {
            return null;
        }
        if (is_array($cached)) {
            return $cached;
        }

        $res = Client::analyticsSummary();
        if (! $res['ok']) {
            set_transient('nitrosearch_analytics_summary', 'unavailable', 5 * MINUTE_IN_SECONDS);

            return null;
        }

        $summary = $res['body'];
        set_transient('nitrosearch_analytics_summary', $summary, 6 * HOUR_IN_SECONDS);

        return $summary;
    }

    private function authorize(string $nonce): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Insufficient permissions.', 'nitrosearch'));
        }
        check_admin_referer($nonce);
    }

    private function redirect(string $notice, string $tab = ''): void
    {
        $args = ['page' => 'nitrosearch', 'ns_notice' => rawurlencode($notice)];
        if ($tab !== '') {
            $args['tab'] = $tab;
        }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
