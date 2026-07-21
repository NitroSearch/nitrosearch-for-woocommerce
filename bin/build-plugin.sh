#!/usr/bin/env bash
#
# Build a clean, distributable plugin zip. Uses an explicit allowlist of shipping
# files, so no development files (VCS, editor, CI, docs, local notes) can ever end
# up in the package.
#
#   bin/build-plugin.sh   ->   dist/nitrosearch-X.Y.0.zip
#
set -euo pipefail

SLUG="nitrosearch"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# Files/dirs that ship to users. Anything not listed here is excluded.
SHIP=(nitrosearch.php readme.txt src assets)

VERSION="$(grep -m1 -oE 'Version:[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+' nitrosearch.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
if [ -z "${VERSION}" ]; then
  echo "Could not read version from nitrosearch.php" >&2
  exit 1
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
