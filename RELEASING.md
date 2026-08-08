# Releasing — NitroSearch for WooCommerce

The plugin uses Semantic Versioning `MAJOR.MINOR.PATCH` and is published to
wordpress.org at **`X.Y.0` milestones only** (v1.0.0, v1.1.0, v1.2.0…). Small
changes accumulate between milestones and ship batched in the next `X.Y.0`, so
every public release carries a clear, meaningful changelog.

- **MINOR** (`x.Y.0`) — a feature milestone; the unit of public release.
- **MAJOR** (`X.0.0`) — a breaking change.
- **PATCH** (`x.y.Z`) — reserved for urgent public hotfixes only; ordinary small
  changes wait for the next milestone.

## Cutting a release

1. **Changelog** — add the new version section to **both** `readme.txt`
   (the `== Changelog ==` block — this is what wordpress.org shows) and
   `CHANGELOG.md`, keeping them identical and user-facing.
2. **Bump the version in all three places, and make sure they match:**
   - `Version:` header in `nitrosearch.php`
   - `NITROSEARCH_VERSION` constant in `nitrosearch.php`
   - `Stable tag:` in `readme.txt`
3. **Translations** — the plugin ships in 23 languages (English + the 22
   locales listed in `bin/check-i18n.sh`'s `LOCALES`), and every release must
   ship them complete:
   - New or changed **code strings**: `bin/check-i18n.sh --update-pot`, then add
     the translations to every `languages/nitrosearch-*.po` and recompile
     (`bin/build-plugin.sh` recompiles; or `msgfmt` + `wp i18n make-php languages`).
   - Changed **readme copy** (anything above `== Screenshots ==`): update every
     `translations/readme/*.po` (and its `*-readme.txt` review copy) — the
     stored `Source-Readme-Hash` ties them to the English readme, and preflight
     fails while they lag. After release, import the updated readme `.po` files
     on translate.wordpress.org so the localized directory listings follow.
   - `bin/check-i18n.sh` verifies all of it (preflight §8 runs it too):
     POT freshness, zero untranslated/fuzzy entries per locale, compiled
     `.mo`/`.l10n.php` matching their `.po`, and readme-translation parity.
4. **Run the guards** — `bin/preflight-release.sh X.Y.0`. This is the same script
   CI runs before anything reaches the directory, so a failure here is a failure
   there. It checks version consistency across all three declarations, that the
   readme markets nothing unbuilt in present tense, that no legal link 404s, that
   the changelog and screenshot captions are complete, that PHP parses, and that
   the translations are release-ready (§8, via `bin/check-i18n.sh`).
5. **Commit** — `Release vX.Y.0`.
6. **Build the distributable** — `bin/build-plugin.sh` → `dist/nitrosearch-X.Y.0.zip`
   (a clean tree containing only shipping files). Inspect the printed file list.
7. **Tag & GitHub release:**
   ```bash
   git tag -a vX.Y.0 -m "vX.Y.0"
   git push origin main --follow-tags
   gh release create vX.Y.0 --title "vX.Y.0" --notes "…" dist/nitrosearch-X.Y.0.zip
   ```
8. **wordpress.org — automatic.** Publishing the GitHub release triggers
   `.github/workflows/deploy-to-wordpress-org.yml`, which re-runs the guards,
   rebuilds from the allowlist, syncs `trunk` and the listing assets, and creates
   `tags/X.Y.0` as a server-side copy. It refuses to overwrite a tag that already
   exists. Watch the run; a red build means nothing was published.

> **Gotcha — a release event runs the workflow file *from the tag*, not from
> `main`.** So a fix to the workflow only takes effect for releases tagged
> *after* it lands; tagging first and fixing the workflow afterwards means the
> release still runs the old file. If you have just changed the workflow, either
> re-tag or publish that one release with the manual trigger (which does use
> `main`'s copy). The guards and listing assets are unaffected — those are
> checked out from the default branch on purpose.
>
> The failure mode is safe: every guard and the build run before Subversion is
> even installed, so a workflow that breaks on an old tag stops without touching
> the directory.

## The directory listing assets

The images on the wordpress.org page — screenshots, banners, icons — live in
`.wordpress-org/`. They are **not** part of the plugin: `bin/build-plugin.sh`
packages an explicit allowlist (`nitrosearch.php readme.txt src assets languages`),
so a dotfile directory can never end up in the download. The plugin's own runtime
`assets/` (admin CSS and brand mark) is a different thing and *does* ship, as do
the compiled translation catalogs in `languages/`. The readme translations in
`translations/` feed translate.wordpress.org and are deliberately **not** packaged.

Screenshot captions in `readme.txt` are matched to `screenshot-N.png` by number,
so the two must stay in step — the guards check the counts agree.

## Publishing by hand

Only needed if the workflow is unavailable. Use the manual trigger first
(**Actions → Deploy to WordPress.org → Run workflow**, leaving *dry run* ticked)
to see exactly what would change without committing anything.

## Pre-push checklist

- `git remote -v` — confirm this is the plugin repo.
- `git diff` — only plugin code + `readme.txt` / `CHANGELOG.md`.
- `bin/preflight-release.sh X.Y.0` passes.
- `bin/build-plugin.sh` output lists **only** shipping files (no dev files).
