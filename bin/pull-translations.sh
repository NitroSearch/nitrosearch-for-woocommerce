#!/usr/bin/env bash
#
# Pull reviewed translations back from translate.wordpress.org.
#
# WHY THIS EXISTS. Uploading a catalog is only half the exchange. A locale's
# translation editor may APPROVE what we sent, REWRITE it, or REJECT it — and
# on 2026-08-15 they had rewritten 100 strings across four locales (Romanian 53,
# Dutch 29, Swedish 16, English UK 2) that our repo knew nothing about. Two
# things follow, and both are bad:
#
#   1. Our bundled catalog is WORSE than the language pack, because a native
#      editor improved the pack and we kept our draft.
#   2. The next re-import OVERWRITES their work with ours. That is not just a
#      regression, it is undoing volunteer effort — the fastest way to lose a
#      locale team's goodwill.
#
# So: before re-importing anything, run this. It takes the editor's wording
# wherever they have one and keeps ours only for strings they have not reached.
#
#   bin/pull-translations.sh            # every locale
#   bin/pull-translations.sh ro_RO nl_NL
#
# Then recompile and commit — the script does the compile for you, and
# bin/check-i18n.sh verifies the result.
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# Derived from the guard's roster so the two can never disagree about which
# locales we ship.
LOCALES="$(grep -m1 '^LOCALES=' bin/check-i18n.sh | sed 's/^LOCALES="//; s/"$//')"

# wordpress.org's slug is NOT the WordPress locale: it is shorter, and lower
# case with a hyphen. Getting one wrong fetches a 404 that parses as an empty
# catalog, which would look exactly like "the editor has done nothing".
slug_for() {
  case "$1" in
    es_ES) echo es ;;      fr_FR) echo fr ;;      de_DE) echo de ;;
    it_IT) echo it ;;      nl_NL) echo nl ;;      pl_PL) echo pl ;;
    pt_PT) echo pt ;;      pt_BR) echo pt-br ;;   ja)    echo ja ;;
    tr_TR) echo tr ;;      sv_SE) echo sv ;;      da_DK) echo da ;;
    nb_NO) echo nb ;;      fi)    echo fi ;;      cs_CZ) echo cs ;;
    ro_RO) echo ro ;;      el)    echo el ;;      id_ID) echo id ;;
    vi)    echo vi ;;      ru_RU) echo ru ;;      uk)    echo uk ;;
    zh_CN) echo zh-cn ;;   en_GB) echo en-gb ;;   en_AU) echo en-au ;;
    en_CA) echo en-ca ;;   en_NZ) echo en-nz ;;   en_ZA) echo en-za ;;
    *) return 1 ;;
  esac
}

for tool in curl php msgfmt; do
  command -v "$tool" >/dev/null 2>&1 || { echo "$tool is required"; exit 1; }
done

TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
BASE="https://translate.wordpress.org/projects/wp-plugins/nitrosearch-for-woocommerce/stable"
CHANGED=""
UNREAD=""
mkdir -p "$TMP/regen"

# ⚠ A RATE LIMIT IS NOT AN ANSWER. translate.wordpress.org returns 429 to a run
# that asks for 27 exports in quick succession, and until 2026-08-18 that landed
# in the same "export unavailable, skipped" line as a locale with nothing to
# say. A run that was refused eleven times therefore looked exactly like a run
# that found eleven locales untouched — and the conclusion drawn from it, "we
# are up to date with every editor", was about strings nobody had fetched.
#
# So: classify the failure, back off, and remember which locales were never
# read. The run's exit status is what carries that; the per-locale lines scroll.
fetch_export() { # $1 = wordpress.org slug, $2 = destination -> 0 read it, 1 did not
  local attempt code
  for attempt in 1 2 3; do
    code="$(curl -sS --max-time 60 -o "$2" -w '%{http_code}' \
      "$BASE/$1/default/export-translations/?format=po" 2>/dev/null || echo 000)"
    [ "$code" = "200" ] && return 0
    case "$code" in
      429|5??) sleep $((attempt * 20)) ;;
      *) printf '%s' "HTTP $code"; return 1 ;;
    esac
  done
  printf '%s' "HTTP $code after 3 attempts"
  return 1
}

for L in ${*:-$LOCALES}; do
  PO="languages/nitrosearch-$L.po"
  [ -f "$PO" ] || { echo "  --   $L: no catalog in this tree, skipped"; continue; }
  SLUG="$(slug_for "$L")" || { echo "  --   $L: no wordpress.org slug mapped, skipped"; continue; }

  if ! WHY="$(fetch_export "$SLUG" "$TMP/live.po")"; then
    echo "  ??   $L: NOT READ — $WHY"
    UNREAD="$UNREAD $L"
    continue
  fi
  sleep 1   # 27 exports back to back is what trips the limit in the first place

  # An export with nothing in it means no editor has been here yet. msgcat
  # would be a no-op, but say so rather than printing a silent "unchanged".
  if ! grep -qE '^msgstr(\[0\])? "[^"]' "$TMP/live.po"; then
    echo "  --   $L: nothing reviewed upstream yet"
    continue
  fi

  # Splice their translations into our file, touching nothing else — see
  # bin/merge-translations.php for why this is not `msgcat --use-first`.
  cp "$PO" "$TMP/before.po"
  ADOPTED="$(php bin/merge-translations.php "$TMP/live.po" "$PO")"

  if [ "$ADOPTED" -eq 0 ]; then
    echo "  ok   $L: already matches the reviewed text"
    continue
  fi

  # Prove the result before keeping it: a merge that produces an invalid or
  # incomplete catalog must not survive the run that made it.
  if ! msgfmt --check --statistics -o "languages/nitrosearch-$L.mo" "$PO" 2>"$TMP/err"; then
    cp "$TMP/before.po" "$PO"
    echo "  FAIL $L: merged catalog does not compile — reverted"
    sed 's/^/         /' "$TMP/err"
    exit 1
  fi
  echo "  ok   $L: adopted $ADOPTED translation(s) from the locale's editor"
  CHANGED="$CHANGED $L"
done

# Only the locales that moved. `wp i18n make-php languages` rewrites all 27,
# and it stamps pot-creation-date with the moment it ran — so regenerating the
# whole directory turns a two-locale adoption into a 27-file diff whose other
# 25 entries differ by a timestamp and nothing else. The instruction attached to
# this script is "read what you are adopting"; 25 files of noise is how that
# stops happening.
if [ -n "$CHANGED" ]; then
  if command -v wp >/dev/null 2>&1; then
    for L in $CHANGED; do cp "languages/nitrosearch-$L.po" "$TMP/regen/"; done
    wp i18n make-php "$TMP/regen" languages >/dev/null
    echo
    echo "Recompiled:$CHANGED. Review the diff before committing — you are adopting"
    echo "someone else's wording, and it should be read, not merged blind."
  else
    echo
    echo "⚠ wp-cli is missing, so the .l10n.php catalogs were NOT regenerated and"
    echo "  every store on$CHANGED would keep reading the previous wording."
    echo "  Install wp-cli and run: wp i18n make-php languages"
    exit 1
  fi
fi

if [ -n "$UNREAD" ]; then
  echo
  echo "⚠ NOT READ:$UNREAD"
  echo "  These locales were never fetched, so nothing here says whether their"
  echo "  editors have changed anything. Re-run for them once the rate limit"
  echo "  clears — this run is NOT a clean sweep:"
  echo "    bin/pull-translations.sh$UNREAD"
  exit 1
fi
