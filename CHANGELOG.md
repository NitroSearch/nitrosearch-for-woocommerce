# Changelog — NitroSearch for WooCommerce

All notable changes to the plugin are documented here. This mirrors the
`== Changelog ==` section of `readme.txt` (which is what wordpress.org displays);
keep the two identical. The plugin follows [Semantic Versioning](https://semver.org/).
Public releases are published to wordpress.org at `X.Y.0` milestones.

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

[1.1.0]: https://github.com/webdeviant/NitroSearchWP/releases/tag/v1.1.0
[1.0.0]: https://github.com/webdeviant/NitroSearchWP/releases/tag/v1.0.0
