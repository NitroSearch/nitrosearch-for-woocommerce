=== NitroSearch for WooCommerce ===
Contributors: webdeviant
Tags: woocommerce, search, product search, ajax search, instant search
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blazing-fast hosted search for WooCommerce. Replace slow WordPress search with instant, typo-tolerant results served from the cloud.

== Description ==

NitroSearch is a hosted search service for WooCommerce stores. This connector plugin syncs your product catalogue to NitroSearch and lets it serve instant, typo-tolerant search and filtering to your shoppers — without adding load to your own database.

Features:

* Instant, typo-tolerant product search
* Reliable background catalogue sync (survives cache layers and low-traffic sites)
* Per-product sync status so you can see exactly what is indexed
* No search load on your own server

A NitroSearch account is required. See https://nitrosearch.io.

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

= 0.1.0 =
* Initial connector: store connect, catalogue sync (outbox + background drain), sync status.
