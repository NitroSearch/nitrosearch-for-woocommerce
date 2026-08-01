# Contributing to NitroSearch for WooCommerce

This repository contains **only** the open-source WordPress/WooCommerce connector plugin that end users install. Everything committed here is public the moment it is pushed — treat every commit, branch, and commit message as world-readable.

## What belongs here

- The plugin's PHP source, admin UI assets, and the storefront loader
- Public documentation for installing, configuring, and contributing to the plugin
- Plugin tests and CI for the plugin only

## What must never enter this repo

- Server-side/backend code, deployment or infrastructure configuration, CI secrets
- API keys, tokens, credentials, `.env` files — including "example" files containing real values, and test fixtures with real data
- Internal documents or excerpts from them (in code, comments, or commit messages)

If the plugin needs to document a server interaction, write the public-facing description here from scratch.

## Translations

The plugin ships fully translated into es_ES, fr_FR, de_DE, it_IT, nl_NL,
pl_PL, pt_PT and pt_BR alongside its English source strings.

- Every user-facing string is wrapped in the WordPress i18n functions with the
  `nitrosearch` text domain, with a `translators:` comment wherever there is a
  placeholder. Complete sentences only — never assemble a message from
  concatenated fragments, and never pluralize by appending letters
  (use `_n()`).
- Catalogs live in `languages/` (`.po` source, compiled `.mo` + `.l10n.php`).
  Regenerate the POT with `bin/check-i18n.sh --update-pot` after string
  changes, and keep every locale complete — `bin/check-i18n.sh` (run by the
  release preflight) fails the release otherwise.
- The wordpress.org listing translations live in `translations/readme/` and
  must track `readme.txt` — the same check verifies that.
- The storefront widget's strings are part of this catalog too: the plugin
  resolves them through gettext and hands them to the widget at runtime, so
  translating a `.po` translates the shopper-facing search UI as well.

## Before every commit

1. `git remote -v` — confirm you are in the repo you think you are.
2. `git diff --staged` — read the full diff; nothing from the list above.
3. Secrets are caught by the secret-scan CI workflow and GitHub push protection, but those are the last line of defense, not the first.
