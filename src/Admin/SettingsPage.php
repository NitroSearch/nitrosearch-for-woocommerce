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
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_nitrosearch_connect', [$this, 'handleConnect']);
        add_action('admin_post_nitrosearch_sync', [$this, 'handleSync']);
        add_action('admin_post_nitrosearch_disconnect', [$this, 'handleDisconnect']);
    }

    public function menu(): void
    {
        add_menu_page(
            'NitroSearch',
            'NitroSearch',
            'manage_woocommerce',
            'nitrosearch',
            [$this, 'render'],
            'dashicons-search',
            58
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $connected = Settings::isConnected();
        $notice = isset($_GET['ns_notice']) ? sanitize_text_field(wp_unslash($_GET['ns_notice'])) : '';
        $action = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1>NitroSearch</h1>
            <?php if ($notice !== '') : ?>
                <div class="notice notice-info is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <?php if (! $connected) : ?>
                <p>Connect this store to NitroSearch to start syncing your catalogue.</p>
                <p><code><?php echo esc_html(Settings::apiUrl()); ?></code></p>
                <form method="post" action="<?php echo esc_url($action); ?>">
                    <?php wp_nonce_field('nitrosearch_connect'); ?>
                    <input type="hidden" name="action" value="nitrosearch_connect">
                    <?php submit_button('Connect store', 'primary'); ?>
                </form>
            <?php else : ?>
                <table class="widefat striped" style="max-width:720px">
                    <tbody>
                        <tr><th>Status</th><td><strong style="color:green">Connected</strong></td></tr>
                        <tr><th>Store</th><td><code><?php echo esc_html((string) Settings::get('store_id')); ?></code></td></tr>
                        <tr><th>Collection</th><td><code><?php echo esc_html((string) Settings::get('collection')); ?></code></td></tr>
                        <tr><th>Pending changes</th><td><?php echo (int) Outbox::pendingCount(); ?></td></tr>
                        <tr><th>Last sync</th><td><?php echo esc_html((string) (Settings::get('last_sync') ?: 'never')); ?></td></tr>
                        <?php if (Settings::get('last_error')) : ?>
                            <tr><th>Last error</th><td style="color:#b32d2e"><?php echo esc_html((string) Settings::get('last_error')); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p style="margin-top:1em">
                    <form method="post" action="<?php echo esc_url($action); ?>" style="display:inline">
                        <?php wp_nonce_field('nitrosearch_sync'); ?>
                        <input type="hidden" name="action" value="nitrosearch_sync">
                        <?php submit_button('Sync all products', 'secondary', 'submit', false); ?>
                    </form>
                    <form method="post" action="<?php echo esc_url($action); ?>" style="display:inline;margin-left:8px">
                        <?php wp_nonce_field('nitrosearch_disconnect'); ?>
                        <input type="hidden" name="action" value="nitrosearch_disconnect">
                        <?php submit_button('Disconnect', 'delete', 'submit', false); ?>
                    </form>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handleConnect(): void
    {
        $this->authorize('nitrosearch_connect');

        $result = Client::connect();
        if ($result['ok']) {
            $count = Hooks::fullSync();
            Drain::schedule();
            $this->redirect("Connected. Queued {$count} products for sync.");
        }
        $this->redirect('Connect failed: '.($result['error'] ?? 'unknown error'));
    }

    public function handleSync(): void
    {
        $this->authorize('nitrosearch_sync');
        $count = Hooks::fullSync();
        Drain::schedule();
        $this->redirect("Queued {$count} products for sync.");
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
