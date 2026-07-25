# Changelog — NitroSearch for WooCommerce

All notable changes to the plugin are documented here. This mirrors the
`== Changelog ==` section of `readme.txt` (which is what wordpress.org displays);
keep the two identical. The plugin follows [Semantic Versioning](https://semver.org/).
Public releases are published to wordpress.org at `X.Y.0` milestones.

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
