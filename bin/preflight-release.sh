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

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
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
UNBUILT='nightly|reconcil|health.?check|analytic|merchandis|personalis|personaliz|a/b test'
ROADMAP_MARKER='roadmap|coming soon|planned|not yet|in design|we.re building'

OFFENDERS="$(grep -inE "$UNBUILT" readme.txt | grep -viE "$ROADMAP_MARKER" || true)"
if [ -z "$OFFENDERS" ]; then
  pass "readme.txt claims nothing unbuilt in present tense"
else
  fail "readme.txt markets unbuilt features without a roadmap label:"
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
SHOTS="$(find .wordpress-org -maxdepth 1 -name 'screenshot-*.png' 2>/dev/null | wc -l | tr -d ' ')"
if [ "$CAPTIONS" = "$SHOTS" ]; then
  pass "$CAPTIONS screenshot captions, $SHOTS images"
else
  fail "$CAPTIONS screenshot captions but $SHOTS images in .wordpress-org/"
fi

# --- 6. Package hygiene -----------------------------------------------------
# The listing images must never ride along in the download.
if [ -d .wordpress-org ]; then
  pass ".wordpress-org/ present (listing images, never packaged)"
else
  fail ".wordpress-org/ missing — the directory listing would lose its images"
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

echo
if [ "$FAILED" -ne 0 ]; then
  echo "Pre-release checks FAILED — nothing should be published."
  exit 1
fi
echo "All pre-release checks passed."
