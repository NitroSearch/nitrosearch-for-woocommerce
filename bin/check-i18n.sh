#!/usr/bin/env bash
#
# Translation guards. The plugin ships in every locale listed in LOCALES below,
# and a release that adds a string without re-translating it would silently ship
# screens to every non-English store — nothing else would fail. So the release
# is blocked until every catalog is complete and every compiled artifact
# matches its source.
#
#   bin/check-i18n.sh              # check the working tree (used by preflight §8)
#   bin/check-i18n.sh --update-pot # regenerate languages/nitrosearch.pot (the
#                                  # one canonical command — same flags as the check)
#   bin/check-i18n.sh --selftest   # prove the completeness check still catches
#                                  # an untranslated entry (fixture-based)
#
set -euo pipefail

ROOT="${PREFLIGHT_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}"
LOCALES="es_ES fr_FR de_DE it_IT nl_NL pl_PL pt_PT pt_BR ja tr_TR sv_SE da_DK nb_NO fi cs_CZ ro_RO el id_ID vi ru_RU uk zh_CN en_GB en_AU en_CA en_NZ en_ZA"
POT="languages/nitrosearch.pot"

FAILED=0
pass() { printf '  \033[32mok\033[0m   %s\n' "$1"; }
fail() { printf '  \033[31mFAIL\033[0m %s\n' "$1"; FAILED=1; }
note() { printf '  --   %s\n' "$1"; }

# The one canonical POT generation command; the freshness check regenerates
# with exactly these flags, so the two can never disagree about the flags.
make_pot() { # $1 = source root, $2 = output file
  wp i18n make-pot "$1" "$2" --exclude=dist,bin,node_modules,translations \
    --headers='{"Report-Msgid-Bugs-To":"https://wordpress.org/support/plugin/nitrosearch-for-woocommerce"}' \
    >/dev/null 2>&1
}

# Message ids only (source refs and dates change without meaning drift).
msgid_set() { grep -E '^(msgctxt|msgid|msgid_plural) ' "$1" | sort; }

sha1() { if command -v shasum >/dev/null 2>&1; then shasum; else sha1sum; fi; }

# The readme's translatable region: line 11 (below the header metadata) up to,
# not including, '== Screenshots =='. Screenshots/changelog/upgrade notices are
# version-bound and deliberately untranslated, so a changelog entry must NOT
# invalidate the readme translations — only real listing copy does.
readme_hash() { # $1 = readme.txt
  awk 'NR>=11 && /^== Screenshots ==/{exit} NR>=11{print}' "$1" | sha1 | awk '{print $1}'
}

# The en_* catalogs exist for exactly one reason: the source is en_US, and they
# respell it. A "complete" en_GB catalog that simply echoes every American msgid
# is 100% translated, compiles, matches the POT — and does nothing. Every other
# check in this file passes over it, which was proven by mutation rather than
# assumed. So assert the work itself: each en_* catalog must actually carry the
# British form of the spellings its own locale is supposed to fix.
#
# Keyed on the source spelling, so the check is derived from what the source
# says rather than from a second hand-maintained list that could drift from it.
en_expected() { # $1 = locale -> "msgid_substring|expected_msgstr_substring" per line
  printf '%s\n' 'Colors|Colours' 'Accent color|Accent colour' 'catalog|catalogue' 'we honor|we honour'
  [ "$1" = "en_CA" ] || printf '%s\n' 'Unauthorized.|Unauthorised.' 'Uncheck to stop|Untick to stop'
  [ "$1" = "en_GB" ] && printf '%s\n' 'Add to cart|Add to basket'
  return 0
}

# Does $2 appear as a msgstr for a msgid containing $1, in catalog $3?
en_pair_present() {
  awk -v want_id="$1" -v want_str="$2" '
    /^msgid /   { inid = index($0, want_id) > 0 }
    /^msgstr/   { if (inid && index($0, want_str) > 0) { found = 1 } }
    END         { exit found ? 0 : 1 }
  ' "$3"
}

# msgfmt --statistics wording: "N translated messages[, N fuzzy translations][, N untranslated messages]."
# A catalog msgfmt cannot even compile reports its first error line — under
# `set -e` a bare pipeline here would otherwise abort the whole run mid-check
# with the failure suppressed and a misleading "complete" already printed.
po_incomplete() { # $1 = .po file -> prints the offending line, empty when complete & valid
  local out
  if ! out="$(LC_ALL=C msgfmt --check --statistics -o /dev/null "$1" 2>&1)"; then
    printf '%s\n' "$out" | head -1 || true
    return 0
  fi
  printf '%s\n' "$out" | grep -E 'fuzzy|untranslated' || true
}

if [ "${1:-}" = "--selftest" ]; then
  TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
  printf 'msgid ""\nmsgstr ""\n"Content-Type: text/plain; charset=UTF-8\\n"\n\nmsgid "Alpha"\nmsgstr "A"\n\nmsgid "Beta"\nmsgstr ""\n' > "$TMP/bad.po"
  printf 'msgid ""\nmsgstr ""\n"Content-Type: text/plain; charset=UTF-8\\n"\n\nmsgid "Alpha"\nmsgstr "A"\n' > "$TMP/good.po"
  if [ -z "$(po_incomplete "$TMP/bad.po")" ]; then
    echo "selftest FAILED: an untranslated entry went undetected"; exit 1
  fi
  if [ -n "$(po_incomplete "$TMP/good.po")" ]; then
    echo "selftest FAILED: a complete catalog was reported incomplete"; exit 1
  fi
  echo "selftest ok: the completeness check catches an untranslated entry"
  exit 0
fi

cd "$ROOT"

# The translation layer ships in 1.9.0. Tags cut before it have no languages/
# directory and no 'languages' in their SHIP allowlist; blocking their
# re-publish would block a rollback, which is worse than what this guard
# prevents — so a genuinely pre-1.9 tree passes with a note. The version floor
# keeps the grace from ever excusing a FUTURE tree that reverted the layer.
TREE_VER="$(grep -m1 -oE 'Version:[[:space:]]*[0-9]+\.[0-9]+' nitrosearch.php | grep -oE '[0-9]+\.[0-9]+$' || true)"
TREE_MAJ="${TREE_VER%%.*}"; TREE_MIN="${TREE_VER#*.}"
if [ "${TREE_MAJ:-0}" -eq 1 ] && [ "${TREE_MIN:-0}" -lt 9 ]; then
  if [ ! -d languages ] && ! grep -qE '^SHIP=.*languages' bin/build-plugin.sh 2>/dev/null; then
    echo "pre-translation tree (version ${TREE_VER:-unknown}, no languages/) — translation guards not applicable"
    exit 0
  fi
fi

# A tag republished after a later release added a locale must be judged by the
# locale set it shipped with, not today's — otherwise adding a ninth language
# would forever block rolling back to the eight-language releases. The tree's
# own copy of this script records its list; data extraction only, never run.
TREE_LOCALES="$(grep -m1 '^LOCALES=' bin/check-i18n.sh 2>/dev/null | sed 's/^LOCALES="//; s/"$//' || true)"
if [ -n "$TREE_LOCALES" ]; then
  LOCALES="$TREE_LOCALES"
fi

if [ "${1:-}" = "--update-pot" ]; then
  command -v wp >/dev/null 2>&1 || { echo "wp-cli required (brew install wp-cli)"; exit 1; }
  make_pot . "$POT"
  echo "regenerated $POT ($(grep -c '^msgid ' "$POT") entries)"
  exit 0
fi

echo "Translation checks"

# --- POT exists and is fresh -------------------------------------------------
if [ ! -f "$POT" ]; then
  fail "$POT missing — run bin/check-i18n.sh --update-pot"
else
  pass "POT present"
  if command -v wp >/dev/null 2>&1; then
    FRESH="$(mktemp "${TMPDIR:-/tmp}/nitrosearch-pot.XXXXXX")"
    if make_pot . "$FRESH"; then
      if [ "$(msgid_set "$POT")" = "$(msgid_set "$FRESH")" ]; then
        pass "POT matches the source strings"
      else
        fail "POT is stale — source strings changed; run bin/check-i18n.sh --update-pot, then update every .po"
        diff <(msgid_set "$POT") <(msgid_set "$FRESH") | grep '^[<>]' | head -8 | sed 's/^/       /' || true
      fi
    else
      fail "wp i18n make-pot failed"
    fi
    rm -f "$FRESH"
  else
    note "wp-cli not available — POT freshness not verified here (CI verifies it)"
  fi
fi

# --- Loader wiring (bundled catalogs are dead weight if these regress) --------
grep -q 'Domain Path:\s*/languages' nitrosearch.php \
  && pass "Domain Path header present" \
  || fail "nitrosearch.php lost its 'Domain Path: /languages' header"
grep -q "load_plugin_textdomain('nitrosearch'" nitrosearch.php \
  && pass "load_plugin_textdomain call present" \
  || fail "nitrosearch.php no longer loads the bundled catalogs"
grep -qE '^SHIP=.*languages' bin/build-plugin.sh \
  && pass "languages/ is in the build allowlist" \
  || fail "bin/build-plugin.sh SHIP allowlist lost 'languages' — translations would silently drop from the zip"

# --- Every locale: complete, compiled, current --------------------------------
if ! command -v msgfmt >/dev/null 2>&1; then
  note "gettext (msgfmt) not available — per-locale checks not run here (CI runs them)"
else
  for L in $LOCALES; do
    PO="languages/nitrosearch-$L.po"
    if [ ! -f "$PO" ]; then
      fail "$PO missing"
      continue
    fi
    INCOMPLETE="$(po_incomplete "$PO")"
    if [ -n "$INCOMPLETE" ]; then
      fail "$L catalog incomplete: $INCOMPLETE"
    else
      pass "$L catalog complete"
    fi
    if [ -f "$POT" ] && command -v msgcmp >/dev/null 2>&1; then
      if ! LC_ALL=C msgcmp "$PO" "$POT" >/dev/null 2>&1; then
        fail "$L catalog does not cover the POT (new or changed strings untranslated)"
      fi
    fi
    case "$L" in
      en_*)
        MISSED=""
        while IFS='|' read -r want_id want_str; do
          [ -n "$want_id" ] || continue
          en_pair_present "$want_id" "$want_str" "$PO" || MISSED="$MISSED '$want_str'"
        done <<EOF
$(en_expected "$L")
EOF
        if [ -n "$MISSED" ]; then
          fail "$L echoes the en_US source instead of respelling it — missing:$MISSED"
        else
          pass "$L actually respells the source"
        fi
        ;;
    esac
    MO="languages/nitrosearch-$L.mo"
    TMPMO="$(mktemp "${TMPDIR:-/tmp}/nitrosearch-mo.XXXXXX")"
    if ! msgfmt -o "$TMPMO" "$PO" 2>/dev/null; then
      fail "$L .po does not compile — msgfmt error (run: msgfmt --check $PO)"
      rm -f "$TMPMO"
      continue
    fi
    if [ ! -f "$MO" ]; then
      fail "$MO missing — compile with msgfmt (bin/build-plugin.sh does this too)"
    elif ! cmp -s "$TMPMO" "$MO"; then
      fail "$MO is stale — recompile from the .po"
    else
      pass "$L .mo current"
    fi
    rm -f "$TMPMO"
    PHP_CAT="languages/nitrosearch-$L.l10n.php"
    # The tie between .po and .l10n.php is the revision date VALUE — make-php
    # rewrites the header key as lowercase 'po-revision-date', so match the
    # value alone, not the .po header line.
    REV="$(grep -m1 -oE 'PO-Revision-Date: [^\\"]*' "$PO" | head -1 | sed 's/^PO-Revision-Date: //' || true)"
    if [ ! -f "$PHP_CAT" ]; then
      fail "$PHP_CAT missing — run: wp i18n make-php languages"
    elif [ -n "$REV" ] && ! grep -qF "$REV" "$PHP_CAT"; then
      fail "$PHP_CAT was not generated from the current .po — run: wp i18n make-php languages"
    else
      pass "$L .l10n.php current"
    fi
  done
fi

# --- Readme translations track the English readme -----------------------------
CURRENT_HASH="$(readme_hash readme.txt)"
for L in $LOCALES; do
  RPO="translations/readme/$L.po"
  if [ ! -f "$RPO" ]; then
    fail "$RPO missing — the $L directory listing would go stale"
    continue
  fi
  STORED="$(grep -m1 -oE '^# Source-Readme-Hash: [0-9a-f]+' "$RPO" | awk '{print $3}' || true)"
  if [ "$STORED" = "$CURRENT_HASH" ]; then
    pass "$L readme translation matches the current readme"
  else
    fail "$L readme translation is stale — readme.txt changed; re-translate translations/readme/$L.po (stored ${STORED:-none}, current $CURRENT_HASH)"
  fi
done

echo
if [ "$FAILED" -ne 0 ]; then
  echo "Translation checks FAILED."
  exit 1
fi
echo "All translation checks passed."
