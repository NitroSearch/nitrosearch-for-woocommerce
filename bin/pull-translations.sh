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
CHANGED_ANY=0

for L in ${*:-$LOCALES}; do
  PO="languages/nitrosearch-$L.po"
  [ -f "$PO" ] || { echo "  --   $L: no catalog in this tree, skipped"; continue; }
  SLUG="$(slug_for "$L")" || { echo "  --   $L: no wordpress.org slug mapped, skipped"; continue; }

  curl -sS --fail --max-time 60 -o "$TMP/live.po" \
    "$BASE/$SLUG/default/export-translations/?format=po" \
    || { echo "  --   $L: export unavailable, skipped"; continue; }

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
  CHANGED_ANY=1
done

if [ "$CHANGED_ANY" -eq 1 ] && command -v wp >/dev/null 2>&1; then
  wp i18n make-php languages >/dev/null
  echo
  echo "Recompiled. Review the diff before committing — you are adopting someone"
  echo "else's wording, and it should be read, not merged blind."
fi
