# Changelog — NitroSearch for WooCommerce

All notable changes to the plugin are documented here. This mirrors the
`== Changelog ==` section of `readme.txt` (which is what wordpress.org displays);
keep the two identical. The plugin follows [Semantic Versioning](https://semver.org/).
Public releases are published to wordpress.org at `X.Y.0` milestones.

## [Unreleased]

## [1.13.0] — 2026-08-15

### Fixed

- **Variable products showed two filters where there should be one, and the second was gibberish.**
  A shirt with a Colour attribute gave shoppers both "Colour" (Blue, Red) and a second filter called
  "attribute_pa_colour" (blue, red) — the same attribute twice, the duplicate written in WordPress's
  internal wording instead of the names you chose. Every variable product was affected. Variations
  now describe themselves the way the product does, so the two become the single filter a shopper
  expects.

- **Revenue from search was reported at the wrong scale on stores whose currency has no decimal
  places, or three.** A yen store's search-attributed revenue was reported at a hundred times its
  real value, and a Kuwaiti dinar store's at a tenth, on every order. Stores in pounds, euros,
  dollars and every other two-decimal currency were never affected. Product prices were already
  correct — this was the revenue figure only.

- **Database changes now reach stores that update the plugin**, not only new installations.
  WordPress does not run a plugin's installation step when it is updated, so a future change to the
  plugin's internal sync table would have quietly reached nobody who already had it.

### Internal

- A test suite and a CI workflow that runs it — 115 assertions on PHP 8.1 through 8.4, covering the
  request signing, the currency exponent table, the order-report retry rules and the variant
  attribute translation. This was the only connector without one.
- The order-report retry classifier now agrees with the other connectors about a transport failure.
  Nothing was being lost: a separate branch answered timeouts before the classifier was reached.

## [1.12.0] — 2026-08-10

### Added

- **The plugin now speaks 22 languages.** Fourteen more locales join the original eight, covering
  both the WordPress admin screens and the search panel your shoppers see: Japanese, Turkish,
  Swedish, Danish, Norwegian, Finnish, Czech, Romanian, Greek, Indonesian, Vietnamese, Russian,
  Ukrainian and Simplified Chinese. Each was drafted to its own community's conventions and reviewed
  by a native speaker.

### Fixed

- **Orders that came from a search are no longer lost when the service is briefly unreachable.**
  An order was reported once, thirty seconds after checkout, by code that never looked at the
  answer — so a single timeout, a 502 from your own host, or a moment of rate limiting destroyed
  that order's revenue figure permanently, with nothing recorded anywhere. The worst case was the
  one that matters most: during a burst of sales, reports past the service's per-minute limit were
  all discarded, so **the busiest hour of the year reported the least revenue**.

  A report is now retried with widening gaps over about nine hours, and if it still cannot be
  delivered your store records why instead of forgetting. The figures cannot be double-counted:
  every attempt sends the same timestamp, which is what the service uses to recognise a repeat.
  Nothing about this runs during a shopper's checkout.

- **Prices are sent with the scale they were measured in, and your store's language always travels
  with them.** Currencies without two decimal places — yen, dinar — could previously be read at the
  wrong scale.

- **Deletions now say what kind of thing was deleted**, so a removed page can no longer be mistaken
  for a removed product with the same id.

## [1.11.0] — 2026-08-03

### Added
- **The service can ask your store to send its catalogue again.** Products are sent
  as they change, and until now nothing could ask for them a second time. If an item
  could not be used when it arrived — one your store's data made unreadable, or one
  that would have taken you past your plan's limit — it simply never appeared in
  search, and nothing knew to try again. Now the service can ask, and your store
  answers on its own: it starts the same sync as the **Sync now** button, in the
  background, and confirms it. Each request is acted on once, so your store is never
  put through its catalogue twice for the same reason.
- **The plugin now checks in every few minutes** instead of only when it is updated
  or when you press **Check status**. That means your plan, your product limit and
  your indexed count stay current on their own — previously a store could run for
  months showing figures that had since changed. It rides the sync schedule that
  already exists, so there is no extra background task and no extra load on your site.

## [1.10.0] — 2026-08-02

### Added
- **Your search connection now renews itself.** The plugin quietly refreshes
  its search credentials about once a day, so search keeps working even on
  stores whose settings screen is never opened. Previously the credentials
  were fetched once at setup and could eventually lapse on a long-untouched
  store.

### Changed
- Sturdier against bad replies: a malformed response from the service (for
  example, a hosting proxy interfering) can no longer clear a working search
  connection. If a reply looks wrong, the plugin keeps what it has and tries
  again the next day.
- The **Check status** button now also refreshes the search credentials every
  time, giving you a one-click fix if search ever stops responding.

## [1.9.0] — 2026-08-01

### Added
- **NitroSearch in your language.** The plugin now ships fully translated into
  Spanish, French, German, Italian, Dutch, Polish, and Portuguese — both
  European and Brazilian. That covers the settings screens, every message the
  plugin shows, and the search box shoppers use, right down to its
  screen-reader announcements. The site's language setting decides
  automatically; English stores are completely unchanged.
- The storefront search box picks up its translation from the site's
  language — plural rules and all — once the store's NitroSearch service
  updates. Nothing to configure.

### Fixed
- The "Last sync" time now shows in the site's own timezone and date format.
  It was shown as a raw UTC timestamp.
- A failed connection no longer says "Connect failed" twice in the same
  message.
- Corrected in the directory listing: the filter list previously mentioned a
  price filter. The filters offered are category, brand, on-sale and
  in-stock.

## [1.8.0] — 2026-07-31

### Added
- **A Design tab.** Choose a layout — Roomy, Compact, Big pictures or Text
  only — a colour scheme (Light, Dark, Automatic to follow each shopper's
  device, or custom colours), corner style, and the font. Set how many
  products appear, where the filters go, and how wide the drop-down opens.
  Every choice resolves to design tokens on the store's own site; none of it
  enlarges what shoppers download.
- The NitroSearch mark now appears in the wp-admin menu, and follows the
  admin colour scheme.

### Fixed
- **Product names were cut off mid-word.** The drop-down was never allowed to
  be wider than the theme's search box, so on a narrow header the filters
  column left almost no room for names. It now opens wide enough to read,
  names wrap to two lines instead of truncating, and the filters move above
  the results when the panel is genuinely tight.
- The search box now uses **the store's font.** It was falling back to the
  browser's default serif on every site, because the widget renders in an
  isolated shadow root that inherits nothing from the page.
- Pale accent colours (yellows, light pastels) rendered white text on top of
  themselves. Label text on the accent colour is now automatically black or
  white, whichever stays readable.
- With pages and posts switched on, their icons and labels had no
  background — a colour the stylesheet referenced but never defined.

## [1.7.0] — 2026-07-29

### Added
- **Search-attributed revenue.** When a shopper adds a product to their basket
  from search results and buys it within 7 days, the plugin reports that
  order's search-attributed value to NitroSearch, powering the revenue figure
  in the analytics dashboards. Attribution lives entirely in the store's own
  WooCommerce session; the order number is hashed with the install id before
  it leaves the site, no shopper details are ever sent, reporting is async so
  checkout is never slowed, and the backend insert is idempotent so a retried
  report can never double-count. Honours the "Search usage data" toggle.

## [1.6.0] — 2026-07-29

### Added
- **A Search analytics card on the NitroSearch screen** — last-30-days searches,
  zero-result rate, click-through rate, top searches, and the searches that
  found nothing, with a link to the full dashboards in the NitroSearch account.
  Paid plans see the numbers; the free tier sees its monthly search count and
  what upgrading unlocks. Cached in a six-hour transient with a 2-second
  timeout, so a slow or unreachable backend can never hang wp-admin — the card
  degrades to "couldn't load" and retries within minutes. Every API-sourced
  string is escaped on output.
- "Refresh status" also refreshes the analytics card.

## [1.5.0] — 2026-07-29

### Added
- **Anonymous, cookieless search usage counts.** The widget now tells NitroSearch
  what was searched, how many results appeared, and what got clicked — with no
  shopper identifiers, no cookies, and nothing stored in the shopper's browser.
  It improves result ranking; per-store reporting on the NitroSearch dashboard is
  on the roadmap. On by default, disclosed in the readme's External-services
  section, and switchable off under **NitroSearch → Appearance → Search usage
  data**. (Requires a backend that issues the store a usage-events token; on an
  older backend the widget simply stays silent.)
- A one-time wp-admin notice explaining the change, shown on the plugin's own
  screen and the plugins screen until dismissed.
- On upgrade, the plugin now schedules one background status refresh so new
  backend-issued settings (the usage-events token among them) arrive without a
  reconnect — the activation hook does not fire on updates, so version-change
  detection in wp-admin does this instead.

### Changed
- Listing honesty pass: the pricing promise now reads "every **search** feature
  on every plan" — search itself is never tiered — and the free-tier copy counts
  **search results** (products, plus any pages and posts you switch on), matching
  how plans have counted since 1.4.0.

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
- The optional "Powered by NitroSearch" credit now links to nitrosearch.io, and
  appears once in your site footer as well as in the search box — so a visitor who
  never opens the search box can still see who powers it. Still **off by default**
  and still entirely your choice; nothing is added to your site unless you tick it.
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

## [1.3.1] — 2026-07-26

### Fixed
- The plugin screen offered "search analytics" on the NitroSearch dashboard.
  That feature wasn't built yet, so the wording is corrected — it's on the
  roadmap, and we'd rather say so than imply otherwise.

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

[1.2.3]: https://github.com/NitroSearch/nitrosearch-for-woocommerce/releases/tag/v1.2.3
[1.2.2]: https://github.com/NitroSearch/nitrosearch-for-woocommerce/releases/tag/v1.2.2
[1.2.1]: https://github.com/NitroSearch/nitrosearch-for-woocommerce/releases/tag/v1.2.1
[1.2.0]: https://github.com/NitroSearch/nitrosearch-for-woocommerce/releases/tag/v1.2.0
[1.1.0]: https://github.com/NitroSearch/nitrosearch-for-woocommerce/releases/tag/v1.1.0
[1.0.0]: https://github.com/NitroSearch/nitrosearch-for-woocommerce/releases/tag/v1.0.0
