<p align="center">
  <img src="assets/mark.svg" width="72" height="72" alt="NitroSearch">
</p>

<h1 align="center">NitroSearch for WooCommerce</h1>

<p align="center">
  <strong>Amazon-quality search for WooCommerce.</strong><br>
  Instant, typo-tolerant product search served from the cloud — without adding load to your store.
</p>

<p align="center">
  <a href="https://nitrosearch.io">nitrosearch.io</a> &nbsp;·&nbsp;
  <a href="https://nitrosearch.io/pricing">Pricing</a> &nbsp;·&nbsp;
  <a href="https://nitrosearch.io/legal/privacy">Privacy</a> &nbsp;·&nbsp;
  <a href="CONTRIBUTING.md">Contributing</a>
</p>

---

NitroSearch is a hosted search service built exclusively for WooCommerce. This connector plugin syncs
your product catalogue to NitroSearch and lets it serve instant, typo-tolerant search and filtering to
your shoppers — every query goes straight from the browser to our engine, so your WordPress server is
never in the search path.

## Why NitroSearch

- **Instant, typo-tolerant search** — results in around a tenth of a second, and *"runing shoes"* still
  finds your running shoes.
- **A feather-light widget** — enhances your theme's existing search box, sealed in its own shadow DOM so
  it never clashes with your theme or drags down your speed score. No theme rebuild required.
- **Filters, facets & a full results page** — category, brand, price, on-sale and in-stock facets, a
  complete results grid with pagination, and add-to-cart right from the results.
- **Sync you can trust** — an outbox that coalesces every change, signed and versioned updates so nothing
  lands out of order, and retries until each one sticks — with a live "in sync" screen to prove it.
- **No search load on your own server** — the work your WordPress host physically can't do, done for you.
- **Honest, simple pricing** — a genuinely free tier, every search feature on every plan (search itself is
  never tiered), and you only pay for how much you index. Cancel yourself in one click.

## How it works

1. Install and activate WooCommerce, then NitroSearch for WooCommerce.
2. Open the **NitroSearch** menu in wp-admin and click **Connect store**.
3. Your catalogue syncs in the background, and your theme's search box lights up with instant results.

**Nothing leaves your site until you click Connect** — that click is the consent gate. After connecting,
your product data (names, descriptions, SKUs, prices, stock, categories, attributes, images and
permalinks) is sent to NitroSearch to be indexed, and product changes are sent as they happen.

A NitroSearch account is optional to get started (the free tier works on install) and lets you manage your
plan and upgrade from your dashboard.

## Requirements

| | |
|---|---|
| WordPress | 6.5+ |
| WooCommerce | 9.0+ |
| PHP | 8.1+ |

## Building a release

The distributable zip is built from an explicit allowlist, so no development files are ever packaged:

```bash
bin/build-plugin.sh   # -> dist/nitrosearch-X.Y.0.zip
```

## Contributing

Issues and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). This repository contains the
WordPress connector plugin only.

## License

GPL-2.0-or-later. See the plugin header and [readme.txt](readme.txt) for details.
