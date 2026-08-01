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

# Recompile translation catalogs so the zip always ships .mo (and WP 6.5+
# fast-loading .l10n.php) that match the .po sources. The compiled files are
# also committed and preflight verifies their freshness, so a machine without
# the tools still ships correct catalogs — this is the belt to that braces.
if ls languages/nitrosearch-*.po >/dev/null 2>&1; then
  if command -v msgfmt >/dev/null 2>&1; then
    for po in languages/nitrosearch-*.po; do
      msgfmt --check -o "${po%.po}.mo" "$po"
    done
  fi
  if command -v wp >/dev/null 2>&1; then
    wp i18n make-php languages >/dev/null
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
