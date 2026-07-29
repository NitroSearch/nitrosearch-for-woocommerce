# Changelog — NitroSearch for WooCommerce

All notable changes to the plugin are documented here. This mirrors the
`== Changelog ==` section of `readme.txt` (which is what wordpress.org displays);
keep the two identical. The plugin follows [Semantic Versioning](https://semver.org/).
Public releases are published to wordpress.org at `X.Y.0` milestones.

## [1.4.0] — 2026-07-29

### Added
- **Search your pages and blog posts too.** Shoppers looking for "delivery times"
  or "how to care for leather" now find the page that answers them, shown in their
  own "Pages & posts" section beneath the products — never mixed in with your
  catalogue. Only titles, a short summary, categories, the featured image and the
  publish date are indexed; full page content is never copied.
- **A "What to search" setting** under NitroSearch → Appearance. Products are
  always indexed; pages and blog posts are yours to switch on or off. They share
  the same allowance as your products, so turning them off frees it up for your
  catalogue — and when you do, they are removed from your index.
- Private, password-protected, draft, scheduled and trashed content is never
  indexed, and *noindex* set in Yoast SEO or Rank Math is honoured (per item, per
  content type, or site-wide). Membership and paywall plugins can exclude anything
  else through the new `nitrosearch_content_is_searchable` filter.
- Products always claim capacity first. If your plan is full, pages and posts are
  what gets held back — never your catalogue.
- Existing stores are unaffected until they opt in: pages and posts start switched
  **off** on an existing install, and on for a brand-new one.

### Improved
- Scheduled products and posts now index the moment they go live. Previously a
  post published on a schedule could sit unindexed until something else happened
  to edit it — WordPress publishes on a path that fires no product CRUD hook.
- Switching a content type on no longer re-walks the whole catalogue. The sync
  enumerates only the newly-enabled types, so adding pages to a large store no
  longer puts every product back through the store's own host.

### Fixed
- Starting a new page or post no longer sends a needless removal for something
  that was never indexed. Every "Add New" click creates an auto-draft, and each
  one was queuing a delete.
- Deactivating and reactivating the plugin no longer resets the "What to search"
  choice on a store that upgraded in place.
- On a site without Action Scheduler, a full sync indexed products and then
  stopped, reporting itself complete while pages and posts were never enumerated.

## [1.3.0] — 2026-07-26

### Improved
- Gentler, faster first-time catalogue sync. Large catalogues now sync in the
  background in resumable batches instead of all at once, so connecting a big
  store no longer risks slowing down or timing out the site — and the sync paces
  itself to leave the storefront responsive for shoppers.

### Fixed
- Corrected the Terms of Service and Privacy Policy links in the plugin description.

## [1.2.3] — 2026-07-25

### Fixed
- Plugin header: `Author URI` now points to the author's site
  (`https://webdeviant.io`), distinct from `Plugin URI` (`https://nitrosearch.io`),
  as the WordPress.org directory requires. No functional changes.

## [1.2.2] — 2026-07-25

### Changed
- Refreshed the readme for the WordPress.org directory: fuller description, an
  FAQ and Upgrade Notice, and updated screenshot captions. No functional changes.

## [1.2.1] — 2026-07-25

### Added
- An **optional** "Powered by NitroSearch" credit in the search box — **off by
  default**; enable it under Appearance to show your support.

### Changed
- Tested up to WordPress 7.0.
- Hardened the catalogue-sync database queries (values bound via prepared
  statements).

## [1.2.0] — 2026-07-25

### Added
- Sync performance panel on the admin screen — see how quickly your catalogue
  changes reach your search index (average and most-recent batch speed), how many
  products have synced, how many batches have been sent, and when the next sync
  runs.
- A clear heads-up when your catalogue reaches your plan's product limit, with a
  prompt to upgrade. Your existing search keeps running — only brand-new products
  wait until you upgrade.

## [1.1.0] — 2026-07-21

### Added
- Filters and a full results page — category, brand, price, on-sale and in-stock
  facets, a complete results grid with pagination, and add-to-cart right from the
  results.
- Appearance settings — set an accent colour for prices, highlights and selected
  filters, and optionally point the widget at your theme's search box.
- "Manage / Upgrade" — link your store to a NitroSearch account to manage your
  plan and view analytics, without re-indexing or losing your search.

### Changed
- Refreshed, clearer admin screen showing connection status and live sync health.
- Faster, more accessible instant-search dropdown, with full keyboard navigation
  and recent searches.
- More reliable and more secure catalogue sync, with clearer connection and
  verification status.

## [1.0.0] — 2026-07-19

### Added
- One-click connect: link a store to NitroSearch from the WordPress admin.
- Automatic catalogue sync — products, prices, stock, categories, and attributes
  stay in sync as they change, via a local change-queue drained by reliable
  background processing (keeps working on low-traffic sites and behind caches).
- Instant search widget — enhances the theme's existing search box with fast,
  typo-tolerant product results as customers type. No theme rebuild required.
- Sync status screen showing what is indexed.

[1.2.3]: https://github.com/webdeviant/NitroSearchWP/releases/tag/v1.2.3
[1.2.2]: https://github.com/webdeviant/NitroSearchWP/releases/tag/v1.2.2
[1.2.1]: https://github.com/webdeviant/NitroSearchWP/releases/tag/v1.2.1
[1.2.0]: https://github.com/webdeviant/NitroSearchWP/releases/tag/v1.2.0
[1.1.0]: https://github.com/webdeviant/NitroSearchWP/releases/tag/v1.1.0
[1.0.0]: https://github.com/webdeviant/NitroSearchWP/releases/tag/v1.0.0
