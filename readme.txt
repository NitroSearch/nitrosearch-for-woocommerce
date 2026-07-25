=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce, product search, ajax search, instant search, live search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Amazon-quality search for WooCommerce — instant, typo-tolerant results served from the cloud, without slowing your store down.

== Description ==

**Your shoppers can't buy what they can't find.** WooCommerce's built-in search is slow, misses typos, and buckles on big catalogues — so customers who search and come up empty simply leave. NitroSearch replaces it with fast, forgiving, Amazon-quality search that runs on our servers, not yours.

Install the plugin, connect your store, and from that moment every search your customers type is answered in **around a tenth of a second** — typos and all — straight from our engine. No theme rebuild. No search load on your WordPress host. No slowing down your store.

= Why store owners switch to NitroSearch =

* **Instant & typo-tolerant** — results appear as your customer types, and "runing shoes" still finds your running shoes.
* **It won't slow your store down** — search runs directly between the shopper's browser and our engine, so your WordPress server is never in the search path. The on-page widget is feather-light and sealed in its own shadow DOM, so it never fights your theme or your speed score.
* **Filters, facets & a full results page** — category, brand, price, on-sale and in-stock facets, a complete results grid with pagination, and add-to-cart right from the results.
* **Sync you can trust** — reliable background sync with active health checks and nightly reconciliation keep our copy of your catalogue accurate, with a live sync-status screen so you can see exactly what's indexed. No more "force re-index" guessing games.
* **Honest, simple pricing** — a genuinely free tier, every plan gets every feature, and you only ever pay for catalogue size. No per-search fees, no surprise bills.
* **Set-up in minutes** — enhances your theme's *existing* search box automatically. No shortcodes, no template edits, no rebuild.

= How it works =

NitroSearch is a hosted search service; this plugin is its official WooCommerce connector. It does two things, and they never get in each other's way:

1. **Keeps our copy of your catalogue fresh.** As products, prices and stock change, the plugin quietly sends the updates to NitroSearch in the background — coalesced, retried, and reconciled nightly so nothing drifts out of sync.
2. **Answers searches, instantly.** When a shopper searches, the widget talks *directly* to our engine with a search-only key scoped to your store's public products — never through your WordPress server, which is why it stays fast under load.

= Free to start =

The free tier works the moment you install the plugin — up to 100 products, with every feature included. A NitroSearch account is optional; create one to manage your plan, see search analytics, and upgrade from your dashboard. Learn more at [nitrosearch.io](https://nitrosearch.io).

== External services ==

This plugin connects to the **NitroSearch hosted search service** ([nitrosearch.io](https://nitrosearch.io)) to index your catalogue and serve search results. This is the core purpose of the plugin.

* **What is sent, and when:** When you click **"Connect store"**, the plugin registers your site with NitroSearch (your site URL and a randomly generated install identifier). After connecting, your product data — names, descriptions, SKUs, prices, stock status, categories, attributes, images and permalinks — is sent so it can be indexed for search. Product changes are sent as they happen.
* **Search queries:** Once connected, shoppers' search queries are sent from their browser directly to the NitroSearch engine to return results.
* **Where:** the NitroSearch API and search engine, at `api.nitrosearch.io` and your store's dedicated search endpoint.
* **Nothing leaves your site until you click Connect.**

Service Terms of Use: [https://nitrosearch.io/terms](https://nitrosearch.io/terms)
Privacy Policy: [https://nitrosearch.io/privacy](https://nitrosearch.io/privacy)

== Installation ==

1. Install and activate **WooCommerce**.
2. Install and activate **NitroSearch for WooCommerce** (from Plugins → Add New, or upload the ZIP).
3. Open the **NitroSearch** menu in wp-admin and click **"Connect store"**.
4. That's it — your catalogue begins syncing, and your theme's search box is upgraded automatically.

== Frequently Asked Questions ==

= Is it really free? =

Yes. The free tier covers up to 100 products with every feature included, forever. Paid plans simply raise the catalogue-size limit — you only pay for how many products you index, never per search.

= Do I need to create an account? =

No account is required to use the free tier — install, connect, done. An account is optional and lets you manage your plan, view search analytics, and upgrade.

= Will it slow my store down? =

No — that's the point. Searches run directly between the shopper's browser and our engine, so your WordPress/WooCommerce server never does the search work. The on-page widget is tiny and loads only when a shopper starts searching.

= Do I have to change my theme or add a shortcode? =

No. NitroSearch enhances your theme's *existing* search box in place. There's nothing to rebuild. (If your theme uses an unusual search field, you can point NitroSearch at it with an optional CSS selector in the Appearance settings.)

= What data leaves my site, and when? =

Nothing until you click **Connect**. After that, your product catalogue is sent to NitroSearch to be indexed, and updates are sent as products change. See the **External services** section above for the full list, plus links to our Terms and Privacy Policy.

= How does it stay in sync with my catalogue? =

The plugin keeps a local change-queue, drains it reliably in the background (so it keeps working even on low-traffic sites and behind aggressive caching), and NitroSearch reconciles the whole catalogue nightly to auto-repair any drift. A live sync-status screen shows exactly what's indexed.

= Is WooCommerce required? =

Yes. NitroSearch indexes and searches WooCommerce products, so WooCommerce must be installed and active. It's compatible with WooCommerce High-Performance Order Storage (HPOS).

= Does it show a "Powered by NitroSearch" badge on my store? =

Only if you choose to. The credit is **off by default** — you can turn it on in the plugin's **Appearance** settings if you'd like to show your support.

= What happens if I deactivate the plugin? =

Your store falls back to its normal WooCommerce search. Your data on NitroSearch is managed from your account; you can disconnect at any time from the plugin screen.

== Screenshots ==

1. The NitroSearch admin screen — connection status, live sync health, and sync-performance metrics, all in one place.
2. Instant, typo-tolerant search enhancing your theme's own search box, with brand, price and availability filters.
3. The full search results page — a fast product grid with faceted filtering and pagination.

== Changelog ==

= 1.2.2 =
* Documentation and directory assets refreshed.

= 1.2.1 =
* New: an optional "Powered by NitroSearch" credit in the search box — off by default; turn it on in Appearance if you'd like to show your support.
* Compatibility: tested up to WordPress 7.0.
* Housekeeping: hardened the catalogue-sync database queries.

= 1.2.0 =
* New: a Sync performance panel on the admin screen — average and most-recent batch speed, how many products have synced, batches sent, and when the next sync runs.
* New: a clear heads-up when your catalogue reaches your plan's product limit, with a prompt to upgrade. Your existing search keeps running — only brand-new products wait until you upgrade.

= 1.1.0 =
* New: filters and a full results page — category, brand, price, on-sale and in-stock facets, a complete results grid with pagination, and add-to-cart right from the results.
* New: appearance settings — set an accent colour for prices, highlights and selected filters, and optionally point the widget at your theme's search box.
* New: "Manage / Upgrade" — link your store to a NitroSearch account to manage your plan and view analytics, without re-indexing or losing your search.
* Improved: a refreshed, clearer admin screen showing connection status and live sync health.
* Improved: faster, more accessible instant-search dropdown, with full keyboard navigation and recent searches.
* Improved: more reliable and more secure catalogue sync, with clearer connection and verification status.

= 1.0.0 =
* Initial release.
* One-click connect: link your store to NitroSearch from the WordPress admin.
* Automatic catalogue sync — products, prices, stock, categories, and attributes stay in sync as they change, with reliable background processing that keeps working even on low-traffic sites.
* Instant search widget — enhances your theme's existing search box with fast, typo-tolerant product results as customers type. No theme rebuild required.
* Sync status screen so you can see exactly what is indexed.

== Upgrade Notice ==

= 1.2.2 =
Refreshed documentation and directory listing. No functional changes.

= 1.2.1 =
The "Powered by NitroSearch" credit is now optional and off by default. Tested up to WordPress 7.0.

= 1.2.0 =
Adds a sync-performance panel and a plan-limit heads-up on the admin screen.
