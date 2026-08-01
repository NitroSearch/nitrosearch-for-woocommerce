#!/usr/bin/env bash
#
# Pre-release guards. Run this before tagging, and again in CI before anything
# reaches wordpress.org. Every check here exists because it has bitten a release
# somewhere — a publish to the directory reaches every store that installs us,
# and SVN has no undo.
#
#   bin/preflight-release.sh            # check the working tree
#   bin/preflight-release.sh 1.3.0      # also assert the version matches
#
set -euo pipefail

# The tree being checked. CI points this at a checkout of the tag being
# published, while running this script from the current branch — so the newest
# guards apply to whatever we publish, rather than being frozen at tag time.
ROOT="${PREFLIGHT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}"
# Listing images are presentation, not versioned code, so CI takes them from the
# current branch even when publishing an older tag.
ASSETS_DIR="${PREFLIGHT_ASSETS_DIR:-$ROOT/.wordpress-org}"
# Resolve our own directory BEFORE the cd: CI invokes this script by a relative
# path (current/bin/...) while ROOT points at the release checkout, so a
# post-cd "$(dirname "$0")" would resolve into the wrong tree.
SELF_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

EXPECTED="${1:-}"
FAILED=0

pass() { printf '  \033[32mok\033[0m   %s\n' "$1"; }
fail() { printf '  \033[31mFAIL\033[0m %s\n' "$1"; FAILED=1; }

echo "Pre-release checks"
echo

# --- 1. Version consistency -------------------------------------------------
# The classic wordpress.org failure: the directory serves whatever `Stable tag`
# points at, so a mismatch silently ships the wrong code or nothing at all.
HEADER_V="$(grep -m1 -oE 'Version:[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+' nitrosearch.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
CONST_V="$(grep -m1 -oE "NITROSEARCH_VERSION',[[:space:]]*'[0-9]+\.[0-9]+\.[0-9]+" nitrosearch.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
STABLE_V="$(grep -m1 -oE 'Stable tag:[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+' readme.txt | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"

if [ "$HEADER_V" = "$CONST_V" ] && [ "$HEADER_V" = "$STABLE_V" ]; then
  pass "version consistent across all three declarations ($HEADER_V)"
else
  fail "version mismatch — header=$HEADER_V constant=$CONST_V stable-tag=$STABLE_V"
fi

if [ -n "$EXPECTED" ]; then
  if [ "$HEADER_V" = "$EXPECTED" ]; then
    pass "version matches the release being published ($EXPECTED)"
  else
    fail "release is $EXPECTED but the plugin declares $HEADER_V"
  fi
fi

# --- 2. Claims guard --------------------------------------------------------
# We only market what is built. A term from this list may appear ONLY on a line
# that also labels it as future work — otherwise the listing promises something
# the plugin does not do. Retire a term here when the feature actually ships.
# 'analytic' retired 2026-07-29 (shipped backend v1.9-v1.10 + plugin 1.5-1.6);
# 'revenue/attribution' retired same day (shipped backend v1.11 + plugin 1.7).
UNBUILT='nightly|reconcil|health.?check|merchandis|personalis|personaliz|a/b test'
# A line is allowed to name an unbuilt feature if it also labels it as future work
# OR explicitly disclaims it — a changelog entry saying "X isn't built yet" is the
# opposite of marketing X, and honest corrections must not trip their own guard.
ROADMAP_MARKER='roadmap|coming soon|planned|not yet|in design|we.re building|isn.t built|is not built|aren.t built|doesn.t exist|no longer (says|claims|promises|markets)'

# Scan EVERY user-visible surface, not just the listing. The first version of
# this check looked at readme.txt alone, and a "see search analytics" promise
# shipped to production in the admin screen underneath it — the readme was
# corrected while the UI kept saying it. Anything a merchant can read counts.
CLAIM_SURFACES="readme.txt README.md $(find src -name '*.php' 2>/dev/null | tr '\n' ' ')"
# shellcheck disable=SC2086
OFFENDERS="$(grep -inE "$UNBUILT" $CLAIM_SURFACES 2>/dev/null | grep -viE "$ROADMAP_MARKER" || true)"
if [ -z "$OFFENDERS" ]; then
  pass "no unbuilt feature marketed in present tense (readme, README, src/)"
else
  fail "unbuilt features marketed without a roadmap label:"
  printf '       %s\n' "$OFFENDERS"
fi

# --- 3. Links ---------------------------------------------------------------
# A 404 in the readme is what the directory review flagged the first time.
BAD_LINKS="$(grep -oE 'https://nitrosearch\.io/(terms|privacy|dpa)([^a-z-]|$)' readme.txt README.md 2>/dev/null || true)"
if [ -z "$BAD_LINKS" ]; then
  pass "no known-dead legal URLs (the live pages are under /legal/)"
else
  fail "dead legal URLs — use /legal/terms and /legal/privacy:"
  printf '       %s\n' "$BAD_LINKS"
fi

# --- 4. Readme completeness -------------------------------------------------
for section in '== Description ==' '== Installation ==' '== Changelog ==' '== Screenshots ==' '== External services =='; do
  if grep -qF "$section" readme.txt; then
    pass "readme has ${section}"
  else
    fail "readme is missing ${section}"
  fi
done

if grep -qE "^= ${HEADER_V} =$" readme.txt; then
  pass "changelog has an entry for $HEADER_V"
else
  fail "no '= $HEADER_V =' changelog entry in readme.txt"
fi

# --- 5. Screenshots declared vs supplied ------------------------------------
# The directory renders caption N against screenshot-N.png; a mismatch shows
# blank frames or drops captions.
CAPTIONS="$(awk '/^== Screenshots ==$/{f=1;next} /^== /{f=0} f && /^[0-9]+\./' readme.txt | wc -l | tr -d ' ')"
SHOTS="$(find "$ASSETS_DIR" -maxdepth 1 -name 'screenshot-*.png' 2>/dev/null | wc -l | tr -d ' ')"
if [ "$CAPTIONS" = "$SHOTS" ]; then
  pass "$CAPTIONS screenshot captions, $SHOTS images"
else
  fail "$CAPTIONS screenshot captions but $SHOTS images in $ASSETS_DIR"
fi

# --- 6. Listing assets ------------------------------------------------------
# Publishing syncs this directory over the live one with --delete. An empty or
# missing source would silently strip every image from the directory page, so
# refuse rather than publish a listing with no icon, banner or screenshots.
ASSET_COUNT="$(find "$ASSETS_DIR" -maxdepth 1 -type f 2>/dev/null | wc -l | tr -d ' ')"
if [ "$ASSET_COUNT" -gt 0 ]; then
  pass "$ASSET_COUNT listing assets present (never packaged into the download)"
else
  fail "no listing assets in $ASSETS_DIR — publishing would strip the directory page"
fi

# --- 6b. Every class the code uses actually ships -----------------------------
# The autoloader maps NitroSearch\Foo\Bar to src/Foo/Bar.php. If a file the code
# references is missing from the PACKAGE, nothing fails until a merchant's site
# hits that code path and fatals — and by then it is on wordpress.org, where there
# is no undo. Checked against the built package rather than the working tree,
# because the working tree is not what ships.
MISSING=""
for CLASS in $(grep -rhoE '^use NitroSearch\\[A-Za-z0-9_\\]+;' nitrosearch.php src \
               | sed 's/^use NitroSearch\\//; s/;$//' | sort -u); do
  REL="src/$(printf '%s' "$CLASS" | tr '\\' '/').php"
  [ -f "$REL" ] || MISSING="$MISSING $REL"
done
# Fully-qualified references too (the bootstrap uses \NitroSearch\... directly).
for CLASS in $(grep -rhoE '\\NitroSearch\\[A-Za-z0-9_\\]+::' nitrosearch.php src \
               | sed 's/^\\NitroSearch\\//; s/::$//' | sort -u); do
  REL="src/$(printf '%s' "$CLASS" | tr '\\' '/').php"
  [ -f "$REL" ] || MISSING="$MISSING $REL"
done

if [ -n "$MISSING" ]; then
  fail "referenced class files missing from the package:$MISSING"
else
  pass "every referenced NitroSearch class has a file that ships"
fi

# --- 7. Syntax --------------------------------------------------------------
if command -v php >/dev/null 2>&1; then
  if find nitrosearch.php src -name '*.php' -exec php -l {} \; 2>&1 | grep -v '^No syntax errors' | grep -q .; then
    fail "PHP syntax errors"
  else
    pass "PHP syntax clean"
  fi
else
  echo "  --   php not available, skipping lint"
fi

# --- 8. Translations ----------------------------------------------------------
# Nine languages ship with every release. A new string without its nine
# translations, a stale compiled catalog, or a readme edit that left the eight
# translated listings behind would all publish silently — merchants would just
# see mixed-language screens. Delegated to bin/check-i18n.sh (which also has
# the canonical --update-pot command and a --selftest).
if "$SELF_DIR/check-i18n.sh" | sed 's/^/  /'; then
  pass "translation guards (see above)"
else
  fail "translation guards failed — see bin/check-i18n.sh output above"
fi

echo
if [ "$FAILED" -ne 0 ]; then
  echo "Pre-release checks FAILED — nothing should be published."
  exit 1
fi
echo "All pre-release checks passed."
