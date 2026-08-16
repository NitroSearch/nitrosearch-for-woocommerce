<?php
/**
 * Splice reviewed translations from a translate.wordpress.org export into one
 * of our catalogs, changing ONLY the msgstr values that differ.
 *
 * Called by bin/pull-translations.sh; see that file for why the pull-back
 * exists at all.
 *
 * Why a surgical splice rather than `msgcat --use-first`: msgcat produces the
 * right CONTENT, but rewrites the whole file into its own canonical form —
 * rewrapped strings, added source references, reordered headers. That is a
 * ~700-line diff per locale for ~30 changed translations, and the one
 * instruction that matters here is "read what you are adopting before you
 * commit it". A diff nobody can read is a review nobody performs.
 *
 *   php bin/merge-translations.php <live-export.po> <our-catalog.po>
 *
 * Exits 0 and prints the number of translations adopted. Only non-empty
 * upstream translations are taken, so a string the editor has not reached
 * keeps ours.
 *
 * @package NitroSearch
 */

if ($argc !== 3) {
    fwrite(STDERR, "usage: merge-translations.php <live.po> <ours.po>\n");
    exit(2);
}
[, $livePath, $ourPath] = $argv;

/**
 * Split a .po into blocks and key each by its msgctxt+msgid, keeping the raw
 * text so the untouched parts of our file survive byte-for-byte.
 *
 * @return array{0: array<int,string>, 1: array<string,int>}
 */
function po_blocks(string $path): array
{
    $blocks = explode("\n\n", (string) file_get_contents($path));
    $index = [];
    foreach ($blocks as $i => $block) {
        if (! preg_match('/^msgid ((?:"(?:[^"\\\\]|\\\\.)*"\n?)+)/m', $block, $m)) {
            continue;
        }
        $msgid = po_unquote($m[1]);
        if ($msgid === '') {
            continue;   // the header entry
        }
        $ctx = preg_match('/^msgctxt ((?:"(?:[^"\\\\]|\\\\.)*"\n?)+)/m', $block, $c)
            ? po_unquote($c[1]) : '';
        $index[$ctx."\x00".$msgid] = $i;
    }

    return [$blocks, $index];
}

function po_unquote(string $raw): string
{
    preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $raw, $m);

    return str_replace(['\\"', '\\n', '\\\\'], ['"', "\n", '\\'], implode('', $m[1]));
}

/** @return array<int,string> the msgstr values of a block, in order */
function po_msgstrs(string $block): array
{
    preg_match_all('/^msgstr(?:\[\d\])? ((?:"(?:[^"\\\\]|\\\\.)*"\n?\s*)+)/m', $block, $m);

    return array_map('po_unquote', $m[1]);
}

/** Replace a block's msgstr values in place, preserving everything around them. */
function po_replace_msgstrs(string $block, array $values): string
{
    $i = 0;

    return (string) preg_replace_callback(
        '/^(msgstr(?:\[(\d)\])? )((?:"(?:[^"\\\\]|\\\\.)*"\n?\s*)+)/m',
        static function (array $m) use (&$i, $values): string {
            $idx = $m[2] !== '' ? (int) $m[2] : $i;
            $i++;
            $v = $values[$idx] ?? '';
            $escaped = str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $v);

            return $m[1].'"'.$escaped.'"'."\n";
        },
        $block
    );
}

[$ourBlocks, $ourIndex] = po_blocks($ourPath);
[$liveBlocks, $liveIndex] = po_blocks($livePath);

$adopted = 0;
foreach ($liveIndex as $key => $li) {
    if (! isset($ourIndex[$key])) {
        continue;   // upstream has a string this tree does not — a stale export
    }
    $theirs = po_msgstrs($liveBlocks[$li]);
    if ($theirs === [] || trim(implode('', $theirs)) === '') {
        continue;   // not reached by an editor yet; keep ours
    }
    $oi = $ourIndex[$key];
    if (po_msgstrs($ourBlocks[$oi]) === $theirs) {
        continue;
    }
    $ourBlocks[$oi] = po_replace_msgstrs($ourBlocks[$oi], $theirs);
    $adopted++;
}

if ($adopted > 0) {
    file_put_contents($ourPath, implode("\n\n", $ourBlocks));
}

echo $adopted, "\n";
