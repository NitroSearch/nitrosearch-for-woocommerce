<?php

/**
 * TRANSLATION MERGE — adopting an editor's wording without rewriting the file.
 *
 * `bin/pull-translations.sh` brings reviewed text back from
 * translate.wordpress.org, and `bin/merge-translations.php` is the part that
 * decides what lands in the catalog. Its whole reason for existing instead of
 * `msgcat --use-first` is that a merge must be READABLE: only the msgstr values
 * that actually changed may move, so a human can review what they are adopting
 * before committing someone else's words.
 *
 * ⚠ IT WAS NOT DOING THAT. Every adopted block also gained a trailing newline,
 * so the file grew one blank line per adopted string on every run — 29 of them
 * are sitting in the committed nl_NL catalog from the first pull, and 194
 * elsewhere are indistinguishable from them by eye. Nothing failed: msgfmt does
 * not care about blank lines, the compiled .mo is identical, and the guard in
 * bin/check-i18n.sh compares compiled artifacts rather than source layout. The
 * only symptom is the diff getting harder to read every time the tool is used,
 * which is the one property it was built to protect.
 *
 * So these cases assert the LAYOUT as well as the content: after a merge, the
 * file must differ from its input only in the msgstr lines whose text changed.
 * That is the invariant the review depends on, and it is the one no other check
 * in this repo can see.
 *
 * WHAT THIS DOES NOT PROVE: that the wording adopted is any good, or that the
 * export it came from is the locale's current state. Both are human calls —
 * see docs the ops runbook keeps on the wordpress.org side of the exchange.
 */

/**
 * Run the real script the way pull-translations.sh runs it, against temp files.
 *
 * @return array{0: string, 1: string} the merged catalog and the adopted count
 */
function ns_merge_po($root, $live, $ours)
{
    $dir = sys_get_temp_dir().'/ns-merge-'.bin2hex(random_bytes(6));
    mkdir($dir);
    file_put_contents($dir.'/live.po', $live);
    file_put_contents($dir.'/ours.po', $ours);

    $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/bin/merge-translations.php')
        .' '.escapeshellarg($dir.'/live.po').' '.escapeshellarg($dir.'/ours.po').' 2>&1';
    $adopted = trim((string) shell_exec($cmd));
    $merged = (string) file_get_contents($dir.'/ours.po');

    unlink($dir.'/live.po');
    unlink($dir.'/ours.po');
    rmdir($dir);

    return [$merged, $adopted];
}

/** A minimal but real catalog: header, a plain entry, a contexted one, a plural. */
function ns_po_fixture($translations)
{
    $po = "msgid \"\"\nmsgstr \"\"\n\"Content-Type: text/plain; charset=UTF-8\\n\"\n"
        ."\"Plural-Forms: nplurals=2; plural=(n != 1);\\n\"\n\n";

    $po .= "msgid \"Connected & verified\"\nmsgstr \"".$translations['connected']."\"\n\n";

    $po .= "msgctxt \"a website page, shown on a search result\"\n"
        ."msgid \"Page\"\nmsgstr \"".$translations['page']."\"\n\n";

    $po .= "#. translators: %s: the number of results.\n"
        ."msgid \"%s result\"\nmsgid_plural \"%s results\"\n"
        ."msgstr[0] \"".$translations['one']."\"\n"
        ."msgstr[1] \"".$translations['many']."\"\n";

    return $po;
}

return [
    'it adopts the editor’s wording and reports how much it took' => function ($root) {
        $ours = ns_po_fixture([
            'connected' => 'Verbunden & verifiziert',
            'page' => 'Seite',
            'one' => '%s Ergebnis',
            'many' => '%s Ergebnisse',
        ]);
        $live = ns_po_fixture([
            'connected' => 'Verbunden und verifiziert',
            'page' => 'Seite',
            'one' => '%s Ergebnis',
            'many' => '%s Ergebnisse',
        ]);

        [$merged, $adopted] = ns_merge_po($root, $live, $ours);

        ns_is('one translation adopted', '1', $adopted);
        ns_true('the editor’s wording is in the file', strpos($merged, 'Verbunden und verifiziert') !== false);
        ns_false('ours is gone', strpos($merged, 'Verbunden & verifiziert') !== false);
    },

    'a merge changes nothing but the msgstr lines it adopts' => function ($root) {
        // THE REGRESSION, and note WHICH entry it uses. A catalog is split on
        // blank lines, so every block except the last one ends at its final
        // character with no newline of its own — that is the shape the old
        // implementation invented a newline for, and the file grew a blank line
        // per adopted string on every run. Adopting the LAST entry instead
        // passes either way, which is how this went unnoticed.
        $ours = ns_po_fixture([
            'connected' => 'Verbunden & verifiziert',
            'page' => 'Seite',
            'one' => '%s Ergebnis',
            'many' => '%s Ergebnisse',
        ]);
        $live = ns_po_fixture([
            'connected' => 'Verbunden und verifiziert',
            'page' => 'Seite',
            'one' => '%s Ergebnis',
            'many' => '%s Ergebnisse',
        ]);

        [$merged] = ns_merge_po($root, $live, $ours);

        ns_is('no blank line was invented anywhere', 0, substr_count($merged, "\n\n\n"));
        ns_is('the file did not grow a trailing newline', substr($ours, -1), substr($merged, -1));

        // And the only line that moved is the one msgstr that was adopted.
        $before = explode("\n", $ours);
        $after = explode("\n", $merged);
        ns_is('same number of lines', count($before), count($after));
        $moved = 0;
        foreach ($after as $i => $line) {
            if (! isset($before[$i]) || $before[$i] !== $line) {
                $moved++;
            }
        }
        ns_is('exactly one msgstr line changed', 1, $moved);
    },

    'a catalog already carrying blank lines keeps exactly the ones it had' => function ($root) {
        // Most tier-2 catalogs are double-spaced from birth and nl_NL carries 29
        // strays from the first pull. Neither shape may be "tidied" by a merge —
        // the tool's contract is that it touches translations and nothing else.
        $ours = str_replace("\n\n", "\n\n\n", ns_po_fixture([
            'connected' => 'Verbunden und verifiziert',
            'page' => 'Seite',
            'one' => '%s Ergebnis',
            'many' => '%s Ergebnisse',
        ]));
        $live = ns_po_fixture([
            'connected' => 'Verbunden und verifiziert',
            'page' => 'Pagina',
            'one' => '%s Ergebnis',
            'many' => '%s Ergebnisse',
        ]);

        [$merged, $adopted] = ns_merge_po($root, $live, $ours);

        ns_is('the contexted entry was adopted', '1', $adopted);
        ns_true('by its context, not by msgid alone', strpos($merged, 'Pagina') !== false);
        ns_is(
            'the double spacing it arrived with is still there',
            substr_count($ours, "\n\n\n"),
            substr_count($merged, "\n\n\n")
        );
        ns_is('and nothing deeper was created', 0, substr_count($merged, "\n\n\n\n"));
    },

    'a string the editor has not reached keeps ours' => function ($root) {
        $ours = ns_po_fixture([
            'connected' => 'Verbunden und verifiziert',
            'page' => 'Seite',
            'one' => '%s Ergebnis',
            'many' => '%s Ergebnisse',
        ]);
        $live = ns_po_fixture([
            'connected' => '',
            'page' => 'Seite',
            'one' => '',
            'many' => '',
        ]);

        [$merged, $adopted] = ns_merge_po($root, $live, $ours);

        ns_is('nothing adopted', '0', $adopted);
        ns_is('the file is byte-identical', $ours, $merged);
    },
];
