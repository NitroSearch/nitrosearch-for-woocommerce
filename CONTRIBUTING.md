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

## Before every commit

1. `git remote -v` — confirm you are in the repo you think you are.
2. `git diff --staged` — read the full diff; nothing from the list above.
3. Secrets are caught by the secret-scan CI workflow and GitHub push protection, but those are the last line of defense, not the first.
