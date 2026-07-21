=== NitroSearch for WooCommerce ===
Contributors: webdeviant
Tags: woocommerce, search, product search, ajax search, instant search
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Amazon-quality search for WooCommerce. Instant, typo-tolerant results served from the cloud, without slowing your store down.

== Description ==

NitroSearch is a hosted search service built exclusively for WooCommerce. This connector plugin syncs your product catalogue to NitroSearch and lets it serve instant, typo-tolerant search and filtering to your shoppers — every query goes straight from the browser to our engine, so your WordPress server is never in the search path.

Features:

* **Instant, typo-tolerant search** — results in around a tenth of a second, and "runing shoes" still finds your running shoes.
* **A feather-light widget** — enhances your theme's existing search box, sealed in its own shadow DOM so it never clashes with your theme or slows your speed score. No theme rebuild required.
* **Filters, facets and a full results page** — category, brand, price, on-sale and in-stock facets, a complete results grid with pagination, and add-to-cart right from the results.
* **Sync you can trust** — reliable background sync with active health checks and nightly reconciliation, plus a live sync-status screen so you can see exactly what is indexed.
* **No search load on your own server** — the work your WordPress host physically can't do, done for you.
* **Honest, simple pricing** — a genuinely free tier, every plan gets every feature, and you only pay for catalogue size.

The free tier works the moment you install the plugin. A NitroSearch account is optional and lets you manage your plan, see search analytics, and upgrade from your dashboard. See https://nitrosearch.io.

== External services ==

This plugin connects to the NitroSearch hosted search service (https://nitrosearch.io) to provide search.

* **What is sent, and when:** When you click "Connect store", the plugin registers your site with NitroSearch (your site URL and a randomly generated install identifier). After connecting, your product data (names, descriptions, SKUs, prices, stock status, categories, attributes, images and permalinks) is sent to NitroSearch so it can be indexed for search. Product changes are sent as they happen.
* **Where:** NitroSearch API at https://api.nitrosearch.io.
* No data leaves your site until you click Connect.

Service terms: https://nitrosearch.io/terms
Privacy policy: https://nitrosearch.io/privacy

== Installation ==

1. Install and activate WooCommerce.
2. Install and activate NitroSearch for WooCommerce.
3. Open the NitroSearch menu in wp-admin and click "Connect store".

== Changelog ==

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
