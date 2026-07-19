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
3. **Commit** — `Release vX.Y.0`.
4. **Build the distributable** — `bin/build-plugin.sh` → `dist/nitrosearch-X.Y.0.zip`
   (a clean tree containing only shipping files). Inspect the printed file list.
5. **Tag & GitHub release:**
   ```bash
   git tag -a vX.Y.0 -m "vX.Y.0"
   git push origin main --follow-tags
   gh release create vX.Y.0 --title "vX.Y.0" --notes "…" dist/nitrosearch-X.Y.0.zip
   ```
6. **wordpress.org** (once the plugin is listed) — copy the shipping files to the
   SVN `trunk`, tag `tags/X.Y.0`, and set the `Stable tag`.

## Pre-push checklist

- `git remote -v` — confirm this is the plugin repo.
- `git diff` — only plugin code + `readme.txt` / `CHANGELOG.md`.
- The three version numbers match.
- `bin/build-plugin.sh` output lists **only** shipping files (no dev files).
