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
#   bin/check-i18n.sh --selftest   # prove the checks still catch what they are
#                                  # for — an untranslated entry, and a PHP
#                                  # catalog left behind by its .po (fixtures)
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
  # 'Untick'/'tick' is NOT asserted: we shipped it as the British form and the
  # en_GB translation editor reverted it to 'Uncheck'/'check' — while keeping
  # 'Add to basket', so they accepted the substantive change and overruled this
  # one. A guard should encode what a native editor has agreed, not our reading.
  [ "$1" = "en_CA" ] || printf '%s\n' 'Unauthorized.|Unauthorised.'
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

# Prints nothing when the shipped catalog carries exactly the .po's messages,
# and a short description of the first divergence when it does not.
php_catalog_drift() { # $1 = shipped .l10n.php, $2 = freshly generated one
  php -r '
    $a = @include $argv[1]; $b = @include $argv[2];
    if (! is_array($a) || ! is_array($b)) { echo "not a readable catalog"; exit; }
    $am = $a["messages"] ?? null; $bm = $b["messages"] ?? null;
    if (! is_array($am) || ! is_array($bm)) { echo "no messages array"; exit; }
    if ($am === $bm) { exit; }
    $n = 0; $first = "";
    foreach ($bm as $k => $v) {
      if (! array_key_exists($k, $am) || $am[$k] !== $v) { $n++; if ($first === "") { $first = $k; } }
    }
    foreach ($am as $k => $v) { if (! array_key_exists($k, $bm)) { $n++; if ($first === "") { $first = $k; } } }
    printf("%d message(s) differ, first: %.60s", $n, str_replace("\n", " ", $first));
  ' "$1" "$2"
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

  # The .l10n.php drift check, proven the same way. Its predecessor compared
  # PO-Revision-Date, which bin/merge-translations.php does not touch — so a
  # catalog rebuilt around an editor's new wording, with the PHP file left as it
  # was, passed. That is the mutation below: same header, one changed message.
  printf '<?php\nreturn ["language"=>"de_DE","pot-creation-date"=>"A","messages"=>["Add to cart"=>"In den Warenkorb"]];\n' > "$TMP/shipped.l10n.php"
  printf '<?php\nreturn ["language"=>"de_DE","pot-creation-date"=>"B","messages"=>["Add to cart"=>"In den Warenkorb"]];\n' > "$TMP/same.l10n.php"
  printf '<?php\nreturn ["language"=>"de_DE","pot-creation-date"=>"A","messages"=>["Add to cart"=>"In den Einkaufswagen"]];\n' > "$TMP/drifted.l10n.php"
  if [ -z "$(php_catalog_drift "$TMP/shipped.l10n.php" "$TMP/drifted.l10n.php")" ]; then
    echo "selftest FAILED: a stale PHP catalog went undetected"; exit 1
  fi
  if [ -n "$(php_catalog_drift "$TMP/shipped.l10n.php" "$TMP/same.l10n.php")" ]; then
    echo "selftest FAILED: a current PHP catalog was reported stale (the generation stamp is not drift)"; exit 1
  fi

  echo "selftest ok: an untranslated entry and a stale PHP catalog are both caught"
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

# --- The PHP catalogs, rebuilt once so each can be compared to its .po --------
#
# ⚠ THIS REPLACED A CHECK THAT COULD NOT SEE THE THING IT WAS FOR. The tie
# between a .po and its .l10n.php used to be the PO-Revision-Date value: if the
# date appeared in the PHP file, the PHP file was "current". But
# bin/merge-translations.php adopts an editor's wording WITHOUT touching that
# header — it changes msgstr values only — so a pull that forgot to recompile
# left every store on that locale reading the previous wording while the guard
# said the catalog was current. It was live on this branch for exactly one run.
#
# So compare the translations themselves. One make-php over a copy of the whole
# languages/ directory, then a per-locale comparison of the message maps —
# headers excluded, because make-php stamps pot-creation-date with the time it
# ran and that byte would otherwise report every catalog as drifted.
REGEN=""
if command -v wp >/dev/null 2>&1 && command -v php >/dev/null 2>&1; then
  REGEN_SRC="$(mktemp -d "${TMPDIR:-/tmp}/nitrosearch-po.XXXXXX")"
  REGEN="$(mktemp -d "${TMPDIR:-/tmp}/nitrosearch-l10n.XXXXXX")"
  trap 'rm -rf "$REGEN_SRC" "$REGEN"' EXIT
  cp languages/*.po "$REGEN_SRC"/ 2>/dev/null || true
  wp i18n make-php "$REGEN_SRC" "$REGEN" >/dev/null 2>&1 || REGEN=""
fi

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
    if [ ! -f "$PHP_CAT" ]; then
      fail "$PHP_CAT missing — run: wp i18n make-php languages"
    elif [ -z "$REGEN" ] || [ ! -f "$REGEN/nitrosearch-$L.l10n.php" ]; then
      note "$L .l10n.php not verified here (wp-cli unavailable) — CI verifies it"
    else
      DRIFT="$(php_catalog_drift "$PHP_CAT" "$REGEN/nitrosearch-$L.l10n.php")"
      if [ -n "$DRIFT" ]; then
        fail "$L .l10n.php does not carry the .po's translations ($DRIFT) — run: wp i18n make-php languages"
      else
        pass "$L .l10n.php current"
      fi
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
