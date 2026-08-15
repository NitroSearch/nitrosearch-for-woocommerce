=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.13.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Instant, typo-tolerant WooCommerce product search served from the cloud — Amazon-quality results with facets, without slowing your store down.

== Description ==

**Your shoppers can't buy what they can't find.** WooCommerce's built-in search is slow, misses typos, and buckles on big catalogues — so customers who search and come up empty simply leave. NitroSearch replaces it with fast, forgiving, Amazon-quality search that runs on our servers, not yours.

Install the plugin, connect your store, and from that moment every search your customers type is answered in **around a tenth of a second** — typos and all — straight from our engine. No theme rebuild. No search load on your WordPress host. No slowing down your store.

= Why store owners switch to NitroSearch =

* **Instant & typo-tolerant** — results appear as your customer types, and "runing shoes" still finds your running shoes.
* **It won't slow your store down** — search runs directly between the shopper's browser and our engine, so your WordPress server is never in the search path. The on-page widget is feather-light and sealed in its own shadow DOM, so it never fights your theme or your speed score.
* **Filters, facets & a full results page** — category, brand, on-sale and in-stock facets, a complete results grid with pagination, and add-to-cart right from the results.
* **Your whole site, not just the shop** — optionally index your pages and blog posts too, shown in their own section beneath the products so your catalogue is never buried. Full page content is never copied, and private, password-protected and *noindex* content is always left out.
* **Sync you can trust** — the plugin keeps its own change queue and retries until every change lands, each update signed and versioned so nothing arrives out of order. A live sync-status screen shows exactly what's indexed, so trust is something you can check rather than a promise.
* **Honest, simple pricing** — a genuinely free tier, every search feature on every plan (search itself is never tiered), and pricing that scales only with catalogue size. No per-search fees, no surprise bills.
* **See what shoppers actually search for** — every paid plan includes search analytics: top queries, what got clicked and added to carts, and the searches that found *nothing* (a ready-made list of demand you don't stock yet). A summary lives right in wp-admin; the full dashboards are in your NitroSearch account. Cookieless, with no shopper identifiers — ever.
* **Nothing added to your site unless you ask** — the optional "Powered by NitroSearch" credit is off by default. Turn it on and it adds a small linked credit; leave it off and your storefront gains nothing but the search.
* **Set-up in minutes** — enhances your theme's *existing* search box automatically. No shortcodes, no template edits, no rebuild.

= How it works =

NitroSearch is a hosted search service; this plugin is its official WooCommerce connector. It does two things, and they never get in each other's way:

1. **Keeps our copy of your catalogue fresh.** As products, prices and stock change, the plugin quietly sends the updates to NitroSearch in the background — coalesced into a local change queue, signed, and retried until every change lands.
2. **Answers searches, instantly.** When a shopper searches, the widget talks *directly* to our engine with a search-only key scoped to your store's public products — never through your WordPress server, which is why it stays fast under load.

= Free to start =

The free tier works the moment you install the plugin — up to 100 search results, with every search feature included. A NitroSearch account is optional; create one to manage your plan and upgrade from your dashboard. Learn more at [nitrosearch.io](https://nitrosearch.io).

== External services ==

This plugin connects to the **NitroSearch hosted search service** ([nitrosearch.io](https://nitrosearch.io)) to index your catalogue and serve search results. This is the core purpose of the plugin.

* **What is sent, and when:** When you click **"Connect store"**, the plugin registers your site with NitroSearch (your site URL and a randomly generated install identifier). After connecting, your product data — names, descriptions, SKUs, prices, stock status, categories, attributes, images and permalinks — is sent so it can be indexed for search. Product changes are sent as they happen.
* **Search queries:** Once connected, shoppers' search queries are sent from their browser directly to the NitroSearch engine to return results.
* **Scripts loaded onto your storefront:** once connected, the plugin loads the search widget's JavaScript from `api.nitrosearch.io` (a small loader, plus the widget itself on first search intent) so results can be rendered in the shopper's browser. Nothing is loaded before you connect.
* **Search usage counts:** once connected, the search widget also sends anonymous, cookieless usage events — the query typed, how many results appeared, and clicks on those results — to `api.nitrosearch.io`. They carry no shopper identifiers, no cookies and no IP-based profiles, and raw records are deleted on a rolling schedule. Switch this off any time under **NitroSearch → Appearance → Search usage data**.
* **Where:** the NitroSearch API and search engine, at `api.nitrosearch.io` and your store's dedicated search endpoint.
* **Nothing leaves your site until you click Connect.**

Service Terms of Use: [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Privacy Policy: [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Install and activate **WooCommerce**.
2. Install and activate **NitroSearch for WooCommerce** (from Plugins → Add New, or upload the ZIP).
3. Open the **NitroSearch** menu in wp-admin and click **"Connect store"**.
4. That's it — your catalogue begins syncing, and your theme's search box is upgraded automatically.

== Frequently Asked Questions ==

= Is it really free? =

Yes. The free tier covers up to 100 search results with every search feature included, forever — search itself is never tiered. Paid plans raise the limit (and include extras like the store reporting we're building); you only pay for how much you index, never per search.

= Do I need to create an account? =

No account is required to use the free tier — install, connect, done. An account is optional and lets you manage your plan and upgrade.

= Will it slow my store down? =

No — that's the point. Searches run directly between the shopper's browser and our engine, so your WordPress/WooCommerce server never does the search work. The on-page widget is tiny and loads only when a shopper starts searching.

= Do I have to change my theme or add a shortcode? =

No. NitroSearch enhances your theme's *existing* search box in place. There's nothing to rebuild. (If your theme uses an unusual search field, you can point NitroSearch at it with an optional CSS selector in the Appearance settings.)

= What data leaves my site, and when? =

Nothing until you click **Connect**. After that, your product catalogue is sent to NitroSearch to be indexed, and updates are sent as products change. See the **External services** section above for the full list, plus links to our Terms and Privacy Policy.

= How does it stay in sync with my catalogue? =

The plugin keeps a local change-queue and drains it reliably in the background — so it keeps working even on low-traffic sites and behind aggressive caching — pacing itself to leave your storefront responsive for shoppers. Every update is signed and carries a version, so changes can't land out of order. A live sync-status screen shows exactly what's indexed. Automatic nightly drift-repair is on our roadmap.

= Is WooCommerce required? =

Yes. NitroSearch indexes and searches WooCommerce products, so WooCommerce must be installed and active. It's compatible with WooCommerce High-Performance Order Storage (HPOS).

= Does it show a "Powered by NitroSearch" badge on my store? =

Only if you choose to. The credit is **off by default**, and nothing is added to your storefront unless you turn it on in the plugin's **Appearance** settings. If you do, it appears as a small linked credit in the search box and one line in your site footer, both pointing to nitrosearch.io. Thank you if you switch it on — but the plugin works identically either way.

= What happens if I deactivate the plugin? =

Your store falls back to its normal WooCommerce search. Your data on NitroSearch is managed from your account; you can disconnect at any time from the plugin screen.

== Screenshots ==

1. The NitroSearch admin screen — connection status, live sync health, and sync-performance metrics, all in one place.
2. Instant, typo-tolerant search enhancing your theme's own search box, with brand, category and availability filters.
3. The full search results page — a fast product grid with faceted filtering and pagination.

== Changelog ==

= 1.13.0 =
* Fix: **variable products showed two filters where there should be one, and the second was gibberish.** A shirt with a Colour attribute gave shoppers both "Colour" (Blue, Red) and a second filter called "attribute_pa_colour" (blue, red) — the same attribute twice, the duplicate written in WordPress's internal wording instead of the names you chose. Every variable product was affected. Variations now describe themselves the way the product does, so the two become the single filter a shopper expects.
* Fix: **revenue from search was reported at the wrong scale on stores whose currency has no decimal places, or three.** A yen store's search-attributed revenue was reported at a hundred times its real value, and a Kuwaiti dinar store's at a tenth, on every order. Stores in pounds, euros, dollars and every other two-decimal currency were never affected. Product prices were already correct — this was the revenue figure only.
* Fix: **database changes now reach stores that update the plugin**, not only new installations. WordPress does not run a plugin's installation step when it is updated, so a future change to the plugin's internal sync table would have quietly reached nobody who already had it.

= 1.12.0 =
* New: **the plugin now speaks 22 languages.** Fourteen more locales join the original eight, covering both the WordPress admin screens and the search panel your shoppers see: Japanese, Turkish, Swedish, Danish, Norwegian, Finnish, Czech, Romanian, Greek, Indonesian, Vietnamese, Russian, Ukrainian and Simplified Chinese. Each was drafted to its own community's conventions and reviewed by a native speaker.
* Fix: **orders that came from a search are no longer lost when the service is briefly unreachable.** An order was reported once, thirty seconds after checkout, by code that never looked at the answer — so a single timeout, a hiccup on your host, or a moment of rate limiting destroyed that order's revenue figure permanently, with nothing recorded anywhere. The worst case was the one that matters most: during a rush, reports past the service's per-minute limit were all discarded, so your busiest hour reported the least revenue. A report is now retried with widening gaps over about nine hours, and if it still cannot be delivered your store records why instead of forgetting. The figures cannot be double-counted. Nothing about this runs during a shopper's checkout.
* Fix: **prices are sent with the scale they were measured in, and your store's language always travels with them.** Currencies without two decimal places — yen, dinar — could previously be read at the wrong scale.
* Fix: **deletions now say what kind of thing was deleted**, so a removed page can no longer be mistaken for a removed product with the same id.

= 1.11.0 =
* New: **the service can ask your store to send its catalogue again.** Products are sent as they change, and until now nothing could ask for them a second time. If an item could not be used when it arrived — one your store's data made unreadable, or one that would have taken you past your plan's limit — it simply never appeared in search, and nothing knew to try again. Now the service can ask, and your store answers on its own: it starts the same sync as the **Sync now** button, in the background, and confirms it. Each request is acted on once, so your store is never put through its catalogue twice for the same reason.
* The plugin now checks in every few minutes instead of only when it is updated or when you press **Check status**. That means your plan, your product limit and your indexed count stay current on their own — previously a store could run for months showing figures that had since changed. It rides the sync schedule that already exists, so there is no extra background task and no extra load on your site.

= 1.10.0 =
* New: **your search connection now renews itself.** The plugin quietly refreshes its search credentials about once a day, so search keeps working even on stores whose settings screen is never opened. Previously the credentials were fetched once at setup and could eventually lapse on a long-untouched store.
* Sturdier against bad replies: a malformed response from the service (for example, a hosting proxy interfering) can no longer clear a working search connection. If a reply looks wrong, the plugin keeps what it has and tries again the next day.
* The **Check status** button now also refreshes the search credentials every time, giving you a one-click fix if search ever stops responding.

= 1.9.0 =
* New: **NitroSearch in your language.** The plugin now ships fully translated into Spanish, French, German, Italian, Dutch, Polish, and Portuguese — both European and Brazilian. That covers the settings screens, every message the plugin shows you, and the search box your shoppers use, right down to its screen-reader announcements. Your site's language setting decides automatically; English stores are completely unchanged.
* The storefront search box picks up its translation from your site's language — plural rules and all — once your store's NitroSearch service updates. Nothing to configure.
* Fixed: the "Last sync" time now shows in your site's own timezone and date format. It was shown as a raw UTC timestamp.
* Fixed: a failed connection no longer says "Connect failed" twice in the same message.
* Corrected in this listing: the filter list previously mentioned a price filter. The filters offered are category, brand, on-sale and in-stock.

= 1.8.0 =
* New: a **Design tab**. Choose a layout — Roomy, Compact, Big pictures or Text only — a colour scheme (Light, Dark, Automatic to follow each shopper's device, or your own colours), corner style, and the font. Set how many products appear, where the filters go, and how wide the drop-down opens. Everything is stored on your own site and applied to the search box; none of it enlarges what your shoppers download.
* Fixed: **product names were cut off mid-word.** The drop-down was never allowed to be wider than your theme's search box, so on a narrow header the filters column left almost no room for names. It now opens wide enough to read, names wrap to two lines instead of truncating, and the filters move above the results when the panel is genuinely tight.
* Fixed: the search box now uses **your store's font**. It was falling back to the browser's default serif on every site, because the widget renders in an isolated shadow root that inherits nothing from the page.
* Fixed: pale accent colours (yellows, light pastels) rendered white text on top of themselves. Label text on your accent colour is now automatically black or white, whichever stays readable.
* Fixed: with pages and posts switched on, their icons and labels had no background — a colour the stylesheet referenced but never defined.
* The NitroSearch mark now appears in the wp-admin menu, and follows your admin colour scheme.

= 1.7.0 =
* New: **see the revenue your search drives.** When a shopper adds a product to their basket from search results and goes on to buy it (within 7 days), the plugin reports that order's search-attributed value to NitroSearch — a "Search-attributed revenue" figure appears in your analytics dashboards. Attribution happens entirely inside your store's own session; the order number is hashed before it leaves your site, and no shopper details are ever sent. Respects the same **Search usage data** toggle — switch it off and nothing is reported.

= 1.6.0 =
* New: a **Search analytics** card on the NitroSearch screen — last-30-days searches, zero-result rate, click-through rate, your top searches, and the searches that found nothing. On paid plans; the free tier sees its monthly search count and what upgrading unlocks. The full dashboards live in your NitroSearch account.
* The card is cached for six hours and never slows wp-admin — a slow connection simply shows "couldn't load" and retries.

= 1.5.0 =
* New: anonymous, cookieless **search usage counts**. The widget now tells NitroSearch what was searched, how many results appeared, and what got clicked — with no shopper identifiers, no cookies, and nothing stored in the shopper's browser. It improves result ranking for your store; per-store reporting on your NitroSearch dashboard is on the roadmap. On by default, disclosed in **External services** above, and yours to switch off under **NitroSearch → Appearance → Search usage data**.
* A one-time notice in wp-admin explains the change and links to the toggle.
* Honesty pass on this listing: our pricing promise now reads "every **search** feature on every plan" — search itself is never tiered, and the limit counts search results (products, plus any pages and posts you switch on), matching how plans have counted since 1.4.0.

= 1.4.0 =
* Improved: the optional "Powered by NitroSearch" credit now links to nitrosearch.io and also appears once in your site footer. Still off by default — nothing is added to your site unless you turn it on.
* New: **search your pages and blog posts too.** Shoppers looking for "delivery times" or "how to care for leather" now find the page that answers them, shown in their own "Pages & posts" section beneath the products — never mixed in with your catalogue. Only titles, a short summary, categories, the featured image and the publish date are indexed; full page content is never copied.
* New: choose what gets searched under **NitroSearch → Appearance → What to search**. Products are always indexed; pages and blog posts are yours to switch on or off. They share the same allowance as your products, so turning them off frees it up for your catalogue — and when you do, they're removed from your index.
* Privacy, by default: private, password-protected, draft, scheduled and trashed content is never indexed, and *noindex* set in Yoast SEO or Rank Math is honoured (per item, per content type, or site-wide). Membership and paywall plugins can exclude anything else through the `nitrosearch_content_is_searchable` filter.
* Your products always come first. If your plan is full, pages and posts are what gets held back — never your catalogue.
* Existing stores are unaffected until you opt in: pages and posts start switched **off** on an existing install, and on for a brand-new one.
* Improved: scheduled products and posts now index the moment they go live. Previously a post published on a schedule could sit unindexed until something else happened to edit it.
* Improved: switching a content type on no longer re-walks your entire catalogue — only the new content is enumerated, so a big store stays responsive.
* Fix: starting a new page or post no longer sends a needless removal for something that was never indexed.

= 1.3.1 =
* Fix: the plugin screen offered "search analytics" on your NitroSearch dashboard. That feature isn't built yet, so the wording is corrected — it's on the roadmap, and we'd rather say so than imply otherwise.

= 1.3.0 =
* Improved: gentler, faster first-time catalogue sync. Large catalogues now sync in the background in resumable batches instead of all at once, so connecting a big store no longer risks slowing down or timing out your site — and the sync paces itself to leave your storefront responsive for shoppers.
* Fix: corrected the Terms of Service and Privacy Policy links in the plugin description.

= 1.2.3 =
* Fix: the plugin and author links in the plugin header now point to separate pages, as the plugin directory requires.

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
* New: "Manage / Upgrade" — link your store to a NitroSearch account to manage your plan, without re-indexing or losing your search.
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

= 1.13.0 =
Fixes a duplicate, oddly-named filter that appeared alongside the real one on every variable product. Also corrects search revenue reporting for currencies without two decimal places (yen, dinar) — product prices were already right. No settings to change.

= 1.12.0 =
Fourteen more languages — 22 in total, covering both the admin screens and the shopper-facing search box. Also fixes a bug where an order that came from a search could go uncounted if the service was briefly unreachable, which most affected the busiest hours. No settings to change.

= 1.11.0 =
Your store can now be asked to re-send its catalogue when something did not make it into search, and it answers automatically. It also keeps your plan and product figures up to date on their own. No settings to change.

= 1.9.0 =
Now fully translated into Spanish, French, German, Italian, Dutch, Polish, and Portuguese (European and Brazilian) — the settings screens and the shopper-facing search box. English stores unchanged. Also corrects the listing's filter list.

= 1.8.0 =
Adds a Design tab (layouts, colour schemes, fonts, widths) and fixes the drop-down cutting product names off, the search box ignoring your store's font, and unreadable text on pale accent colours.

= 1.7.0 =
Adds search-attributed revenue to your analytics (hashed order reference only, no shopper details; honours the usage-data toggle). No storefront changes.

= 1.6.0 =
Adds the Search analytics card to the NitroSearch screen (paid plans; free tier sees its search count). No storefront changes.

= 1.5.0 =
Adds anonymous, cookieless search usage counts (no shopper identifiers — improves ranking; reporting is on the roadmap). On by default with a settings toggle under Appearance; details in External services.

= 1.4.0 =
NitroSearch can now search your pages and blog posts alongside your products, in their own section. Off by default on existing stores — switch it on under Appearance → What to search. Nothing changes until you do.

= 1.3.1 =
Corrects wording on the plugin screen that offered a search-analytics feature which isn't built yet. No functional changes.

= 1.3.0 =
Gentler, faster first-time sync for large catalogues, plus corrected Terms/Privacy links.

= 1.2.3 =
Minor plugin-header fix for the WordPress.org directory. No functional changes.

= 1.2.2 =
Refreshed documentation and directory listing. No functional changes.

= 1.2.1 =
The "Powered by NitroSearch" credit is now optional and off by default. Tested up to WordPress 7.0.

= 1.2.0 =
Adds a sync-performance panel and a plan-limit heads-up on the admin screen.
