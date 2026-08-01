#!/usr/bin/env bash
#
# Build a clean, distributable plugin zip. Uses an explicit allowlist of shipping
# files, so no development files (VCS, editor, CI, docs, local notes) can ever end
# up in the package.
#
#   bin/build-plugin.sh   ->   dist/nitrosearch-X.Y.0.zip
#
set -euo pipefail

SLUG="nitrosearch-for-woocommerce"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# Files/dirs that ship to users. Anything not listed here is excluded.
# (translations/ is deliberately absent: the readme translations there feed
# translate.wordpress.org, not the zip.)
SHIP=(nitrosearch.php readme.txt src assets languages)

VERSION="$(grep -m1 -oE 'Version:[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+' nitrosearch.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
if [ -z "${VERSION}" ]; then
  echo "Could not read version from nitrosearch.php" >&2
  exit 1
fi

# Recompile the .mo catalogs so the zip always ships binaries matching the
# .po sources (msgfmt output is deterministic, so this never perturbs the
# package). The .l10n.php catalogs are deliberately NOT regenerated here:
# `wp i18n make-php` stamps its generation time into a header, so a build-time
# regeneration makes every build byte-unique — which broke the file-by-file
# wp.org serve verification on the 1.9.0 release (eight files differing only
# in pot-creation-date). The committed .l10n.php files are what ship, and
# preflight §8 (bin/check-i18n.sh) fails the release if they lag their .po.
if ls languages/nitrosearch-*.po >/dev/null 2>&1; then
  if command -v msgfmt >/dev/null 2>&1; then
    for po in languages/nitrosearch-*.po; do
      msgfmt --check -o "${po%.po}.mo" "$po"
    done
  fi
fi

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$STAGE/$SLUG"
for item in "${SHIP[@]}"; do
  cp -R "$item" "$STAGE/$SLUG/"
done

mkdir -p dist
ZIP="dist/${SLUG}-${VERSION}.zip"
rm -f "$ZIP"
( cd "$STAGE" && zip -rq "$ROOT/$ZIP" "$SLUG" -x '*.DS_Store' )

echo "Built $ZIP"
echo "Contents:"
unzip -l "$ZIP"
