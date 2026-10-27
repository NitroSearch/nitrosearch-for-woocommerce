#!/usr/bin/env bash
#
# Move the "Tested up to:" line of the PUBLISHED release on wordpress.org,
# without cutting a new version.
#
#   bin/bump-tested-up-to.sh 7.1            # rehearse: show the diff, commit nothing
#   PUBLISH=1 bin/bump-tested-up-to.sh 7.1  # commit it (needs SVN_USER + SVN_PASSWORD)
#
# WHY THIS EXISTS. The directory reads "Tested up to" from the readme of the
# stable tag. A WordPress release the day after ours left every current site
# looking at an "untested with your version" warning, and the only other way to
# move that line is a whole new release. wordpress.org supports exactly this
# change in place: edit readme.txt in trunk and in tags/<stable>, nothing else.
#
# WHAT IT REFUSES, because SVN has no undo and this edits a tag that is already
# being downloaded:
#   - a value that is not plain MAJOR.MINOR;
#   - a value newer than the current WordPress release (that is a claim nobody
#     could have tested);
#   - a value the default branch's readme does not already carry, so git stays
#     the source of truth and the next release cannot silently move it back;
#   - any diff that changes more than that one line in each of the two files.
#
# Env: SVN_URL (default: the plugin's repository), SVN_USER, SVN_PASSWORD,
#      PUBLISH=1 to commit, GIT_README (default: readme.txt beside this script),
#      LATEST_WP (override the wordpress.org version lookup; used by rehearsals).
set -euo pipefail

TESTED="${1:-}"
SVN_URL="${SVN_URL:-https://plugins.svn.wordpress.org/nitrosearch-for-woocommerce}"
SELF_DIR="$(cd "$(dirname "$0")" && pwd)"
GIT_README="${GIT_README:-$SELF_DIR/../readme.txt}"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

die() { printf '\033[31mFAIL\033[0m %s\n' "$1" >&2; exit 1; }
ok() { printf '  \033[32mok\033[0m   %s\n' "$1"; }

# An ANCHORED regex: the value is written into a file on a live tag.
[[ "$TESTED" =~ ^[0-9]+\.[0-9]+$ ]] || die "expected MAJOR.MINOR (e.g. 7.1), got '$TESTED'"
ok "value is MAJOR.MINOR: $TESTED"

# Read whole files into variables, then match. Piping a producer into
# `grep -m1` under pipefail fails BECAUSE it matched (the producer dies of
# SIGPIPE), which is how a good publish once reported red.
header() { local text; text="$(cat "$2")"; grep -m1 "^$1:" <<<"$text" | sed -E "s/^$1:[[:space:]]*//" | tr -d '\r' || true; }

GIT_VALUE="$(header 'Tested up to' "$GIT_README")"
[ "$GIT_VALUE" = "$TESTED" ] || die "the default branch's readme says 'Tested up to: $GIT_VALUE'; change it there first"
ok "the default branch's readme already says $TESTED"

if [ -z "${LATEST_WP:-}" ]; then
  OFFERS="$(curl -fsS https://api.wordpress.org/core/version-check/1.7/)"
  LATEST_WP="$(grep -oE '"current":"[0-9]+\.[0-9]+(\.[0-9]+)?"' <<<"$OFFERS" | head -1 | grep -oE '[0-9]+\.[0-9]+(\.[0-9]+)?' || true)"
fi
[[ "$LATEST_WP" =~ ^[0-9]+\.[0-9]+ ]] || die "could not read the current WordPress version (got '$LATEST_WP')"
LATEST_MM="$(grep -oE '^[0-9]+\.[0-9]+' <<<"$LATEST_WP")"
[ "$(printf '%s\n%s\n' "$TESTED" "$LATEST_MM" | sort -V | tail -1)" = "$LATEST_MM" ] \
  || die "$TESTED is newer than the current WordPress release ($LATEST_WP)"
ok "not newer than the current WordPress release ($LATEST_WP)"

AUTH=(--non-interactive --no-auth-cache)
[ -n "${SVN_USER:-}" ] && AUTH+=(--username "$SVN_USER")
[ -n "${SVN_PASSWORD:-}" ] && AUTH+=(--password "$SVN_PASSWORD")

TRUNK_README="$(svn cat "${AUTH[@]}" "$SVN_URL/trunk/readme.txt")"
STABLE="$(grep -m1 '^Stable tag:' <<<"$TRUNK_README" | sed -E 's/^Stable tag:[[:space:]]*//' | tr -d '\r' || true)"
[[ "$STABLE" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || die "trunk's Stable tag is not X.Y.Z: '$STABLE'"
svn ls "${AUTH[@]}" "$SVN_URL/tags/$STABLE/readme.txt" >/dev/null || die "tags/$STABLE/readme.txt does not exist"
ok "stable tag is $STABLE"

# Sparse checkout: the two readmes and nothing else, so nothing else can be committed.
svn co -q "${AUTH[@]}" --depth empty "$SVN_URL" "$WORK/svn"
cd "$WORK/svn"
svn up -q "${AUTH[@]}" --depth empty trunk tags
svn up -q "${AUTH[@]}" --depth empty "tags/$STABLE"
svn up -q "${AUTH[@]}" trunk/readme.txt "tags/$STABLE/readme.txt"

for f in trunk/readme.txt "tags/$STABLE/readme.txt"; do
  [ "$(grep -c '^Tested up to:' "$f")" = "1" ] || die "$f does not have exactly one 'Tested up to:' line"
  # Keep the file's own line ending (CRLF or LF) on the edited line.
  sed -i -E "s/^Tested up to:[^\r]*/Tested up to: $TESTED/" "$f"
done

if [ -z "$(svn status -q)" ]; then
  ok "both readmes already say $TESTED; nothing to do"
  exit 0
fi

DIFF="$(svn diff)"
echo "--- pending change ---"
echo "$DIFF"
CHANGED="$(grep -E '^[-+]' <<<"$DIFF" | grep -vE '^(\+\+\+|---) ' || true)"
REMOVED="$(grep -c '^-' <<<"$CHANGED" || true)"
ADDED="$(grep -c '^+' <<<"$CHANGED" || true)"
OTHER="$(grep -vE '^[-+]Tested up to:' <<<"$CHANGED" || true)"
[ -z "$OTHER" ] || die "the diff touches something other than 'Tested up to:'"
[ "$REMOVED" -le 2 ] && [ "$ADDED" -le 2 ] && [ "$REMOVED" = "$ADDED" ] \
  || die "expected at most one changed line per file, got -$REMOVED/+$ADDED"
[ "$(svn status -q | wc -l)" -le 2 ] || die "more than the two readmes changed"
ok "the diff is the 'Tested up to:' line and nothing else"

if [ "${PUBLISH:-0}" != "1" ]; then
  echo "Rehearsal: nothing was committed. Re-run with PUBLISH=1 to commit."
  exit 0
fi

svn commit -q "${AUTH[@]}" -m "Tested up to WordPress $TESTED ($STABLE readme only)"
for path in trunk/readme.txt "tags/$STABLE/readme.txt"; do
  LIVE="$(svn cat "${AUTH[@]}" "$SVN_URL/$path")"
  [ "$(grep -m1 '^Tested up to:' <<<"$LIVE" | tr -d '\r')" = "Tested up to: $TESTED" ] || die "$path did not take the change"
done
ok "committed; trunk and tags/$STABLE both say Tested up to: $TESTED"
echo "The directory listing can take a while to re-read the readme."
