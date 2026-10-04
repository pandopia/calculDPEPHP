<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Template;

use RuntimeException;

/**
 * Outil de préparation (bin/build-pdf-templates) : retire d'un modèle les
 * textes d'exemple (« Jean Dupont », « 216 kWh »…) situés dans les zones que
 * le générateur recouvre.
 *
 * Le masquage seul suffit à l'œil, mais le texte masqué resterait dans la
 * couche texte du PDF : copiable, indexé par les moteurs de recherche et lu
 * par les lecteurs d'écran. On supprime donc les opérateurs d'affichage de
 * texte (Tj, TJ, ', ") dont le point d'origine tombe dans une zone, en
 * suivant la matrice de texte et la matrice courante (cm, q/Q) du flux.
 *
 * Travaille sur un fichier QDF (qpdf --qdf --normalize-content=y) : flux
 * décompressés et longueurs en objets indirects, recalculées par fix-qdf.
 */
final class TemplateCleaner
{
    /**
     * @param array<int, list<array{0: float, 1: float, 2: float, 3: float}>> $zones page ⇒ rectangles (origine en haut à gauche)
     * @return int nombre d'opérateurs de texte retirés
     */
    public static function cleanQdf(string $qdfPath, array $zones, float $pageHeight = 841.89): int
    {
        $pdf = (string) file_get_contents($qdfPath);
        $removed = 0;

        // Formulaires (XObject /Form) dessinés par chaque page : le bandeau
        // « Exemple de DPE » en est un. Seuls ceux à matrice identité sont
        // traités, leurs coordonnées étant alors celles de la page.
        preg_match_all('/%% Page (\d+)\n.*?endobj/s', $pdf, $pages, PREG_SET_ORDER);
        foreach ($pages as $page) {
            $number = (int) $page[1];
            if (!isset($zones[$number]) || preg_match('#/XObject <<(.*?)>>#s', $page[0], $xobjects) !== 1) {
                continue;
            }
            preg_match_all('#/\S+ (\d+) 0 R#', $xobjects[1], $refs);
            foreach ($refs[1] as $id) {
                $start = strpos($pdf, "\n$id 0 obj\n");
                if ($start === false) {
                    continue;
                }
                $streamAt = strpos($pdf, ">>\nstream\n", $start);
                $endObj = strpos($pdf, "\nendobj", $start);
                if ($streamAt === false || $endObj === false || $streamAt > $endObj) {
                    continue;
                }
                $dict = substr($pdf, $start, $streamAt - $start);
                $identity = !str_contains($dict, '/Matrix')
                    || preg_match('#/Matrix \[\s*1(\.0+)?\s+0(\.0+)?\s+0(\.0+)?\s+1(\.0+)?\s+0(\.0+)?\s+0(\.0+)?\s*\]#', $dict) === 1;
                if (!str_contains($dict, '/Subtype /Form') || !$identity) {
                    continue;
                }
                $bodyStart = $streamAt + strlen(">>\nstream\n");
                $bodyEnd = strpos($pdf, "\nendstream", $bodyStart);
                if ($bodyEnd === false) {
                    continue;
                }
                [$content, $count] = self::cleanStream(substr($pdf, $bodyStart, $bodyEnd - $bodyStart), $zones[$number], $pageHeight);
                $removed += $count;
                $pdf = substr($pdf, 0, $bodyStart) . rtrim($content, "\n") . substr($pdf, $bodyEnd);
            }
        }

        $pdf = (string) preg_replace_callback(
            '/%% Contents for page (\d+)\n(%% Original object ID: [^\n]*\n)?(\d+ 0 obj\n<<.*?>>\nstream\n)(.*?)(\nendstream)/s',
            static function (array $m) use ($zones, $pageHeight, &$removed): string {
                $page = (int) $m[1];
                if (!isset($zones[$page])) {
                    return $m[0];
                }
                [$content, $count] = self::cleanStream($m[4], $zones[$page], $pageHeight);
                $removed += $count;

                return "%% Contents for page {$m[1]}\n" . $m[2] . $m[3] . $content . $m[5];
            },
            $pdf,
        );
        file_put_contents($qdfPath, $pdf);

        return $removed;
    }

    /**
     * @param list<array{0: float, 1: float, 2: float, 3: float}> $zones
     * @return array{0: string, 1: int}
     */
    public static function cleanStream(string $stream, array $zones, float $pageHeight): array
    {
        $tokens = self::tokenize($stream);
        $out = [];
        $operands = [];
        $ctm = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
        $stack = [];
        $tm = $tlm = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
        $leading = 0.0;
        $removed = 0;

        foreach ($tokens as [$type, $value]) {
            if ($type !== 'op') {
                $operands[] = [$type, $value];
                continue;
            }
            $num = static fn (int $i): float => (float) ($operands[$i][1] ?? 0);
            $keep = true;
            switch ($value) {
                case 'q':
                    $stack[] = $ctm;
                    break;
                case 'Q':
                    $ctm = array_pop($stack) ?? [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
                    break;
                case 'cm':
                    $ctm = self::multiply([$num(0), $num(1), $num(2), $num(3), $num(4), $num(5)], $ctm);
                    break;
                case 'BT':
                    $tm = $tlm = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
                    break;
                case 'Tm':
                    $tm = $tlm = [$num(0), $num(1), $num(2), $num(3), $num(4), $num(5)];
                    break;
                case 'TD':
                    $leading = -$num(1);
                    // no break : TD = TL + Td
                case 'Td':
                    $tm = $tlm = self::multiply([1.0, 0.0, 0.0, 1.0, $num(0), $num(1)], $tlm);
                    break;
                case 'TL':
                    $leading = $num(0);
                    break;
                case 'T*':
                    $tm = $tlm = self::multiply([1.0, 0.0, 0.0, 1.0, 0.0, -$leading], $tlm);
                    break;
                case "'":
                case '"':
                    $tm = $tlm = self::multiply([1.0, 0.0, 0.0, 1.0, 0.0, -$leading], $tlm);
                    // no break
                case 'Tj':
                case 'TJ':
                    $origin = self::multiply($tm, $ctm);
                    $x = $origin[4];
                    $y = $pageHeight - $origin[5];
                    foreach ($zones as [$x0, $y0, $x1, $y1]) {
                        // Origine sur la ligne de base : on tolère la descente.
                        if ($x >= $x0 - 0.5 && $x <= $x1 + 0.5 && $y >= $y0 - 0.5 && $y <= $y1 + 4.0) {
                            $keep = false;
                            $removed++;
                            break;
                        }
                    }
                    break;
            }
            if ($keep) {
                $out[] = implode(' ', [...array_column($operands, 1), $value]);
            }
            $operands = [];
        }
        if ($operands !== []) {
            $out[] = implode(' ', array_column($operands, 1));
        }

        return [implode("\n", $out) . "\n", $removed];
    }

    /**
     * @param array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float} $a
     * @param array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float} $b
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}
     */
    private static function multiply(array $a, array $b): array
    {
        return [
            $a[0] * $b[0] + $a[1] * $b[2],
            $a[0] * $b[1] + $a[1] * $b[3],
            $a[2] * $b[0] + $a[3] * $b[2],
            $a[2] * $b[1] + $a[3] * $b[3],
            $a[4] * $b[0] + $a[5] * $b[2] + $b[4],
            $a[4] * $b[1] + $a[5] * $b[3] + $b[5],
        ];
    }

    /**
     * Découpe un flux de contenu en opérandes (texte brut conservé) et
     * opérateurs.
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function tokenize(string $s): array
    {
        $tokens = [];
        $n = strlen($s);
        $i = 0;
        while ($i < $n) {
            $c = $s[$i];
            if (ctype_space($c)) {
                $i++;
                continue;
            }
            if ($c === '%') {
                while ($i < $n && $s[$i] !== "\n" && $s[$i] !== "\r") {
                    $i++;
                }
                continue;
            }
            $start = $i;
            if ($c === '(') {
                $depth = 0;
                for (; $i < $n; $i++) {
                    if ($s[$i] === '\\') {
                        $i++;
                        continue;
                    }
                    if ($s[$i] === '(') {
                        $depth++;
                    } elseif ($s[$i] === ')' && --$depth === 0) {
                        $i++;
                        break;
                    }
                }
                $tokens[] = ['operand', substr($s, $start, $i - $start)];
                continue;
            }
            if ($c === '<' && ($s[$i + 1] ?? '') === '<') {
                $depth = 0;
                for (; $i < $n; $i++) {
                    if (substr($s, $i, 2) === '<<') {
                        $depth++;
                        $i++;
                    } elseif ($s[$i] === '<') {
                        // Chaîne hexadécimale dans un dictionnaire.
                        $end = strpos($s, '>', $i);
                        $i = $end === false ? $n : $end;
                    } elseif (substr($s, $i, 2) === '>>') {
                        $depth--;
                        $i++;
                        if ($depth === 0) {
                            $i++;
                            break;
                        }
                    } elseif ($s[$i] === '(') {
                        // Chaîne dans un dictionnaire : parenthèses équilibrées.
                        $d = 0;
                        for (; $i < $n; $i++) {
                            if ($s[$i] === '\\') {
                                $i++;
                            } elseif ($s[$i] === '(') {
                                $d++;
                            } elseif ($s[$i] === ')' && --$d === 0) {
                                break;
                            }
                        }
                    }
                }
                $tokens[] = ['operand', substr($s, $start, $i - $start)];
                continue;
            }
            if ($c === '<') {
                $end = strpos($s, '>', $i);
                $i = $end === false ? $n : $end + 1;
                $tokens[] = ['operand', substr($s, $start, $i - $start)];
                continue;
            }
            if ($c === '[') {
                // Tableau (opérande de TJ) : chaînes et nombres, sans imbrication utile.
                $i++;
                while ($i < $n && $s[$i] !== ']') {
                    if ($s[$i] === '(') {
                        $d = 0;
                        for (; $i < $n; $i++) {
                            if ($s[$i] === '\\') {
                                $i++;
                            } elseif ($s[$i] === '(') {
                                $d++;
                            } elseif ($s[$i] === ')' && --$d === 0) {
                                break;
                            }
                        }
                    }
                    $i++;
                }
                $i++;
                $tokens[] = ['operand', substr($s, $start, $i - $start)];
                continue;
            }
            if ($c === '/') {
                $i++;
                while ($i < $n && !ctype_space($s[$i]) && !str_contains('/[]()<>{}%', $s[$i])) {
                    $i++;
                }
                $tokens[] = ['operand', substr($s, $start, $i - $start)];
                continue;
            }
            while ($i < $n && !ctype_space($s[$i]) && !str_contains('/[]()<>{}%', $s[$i])) {
                $i++;
            }
            if ($i === $start) {
                throw new RuntimeException("Flux de contenu illisible à l'octet $i.");
            }
            $word = substr($s, $start, $i - $start);
            if ($word === 'BI') {
                // Image en ligne : recopiée telle quelle jusqu'à EI.
                $end = strpos($s, 'EI', $i);
                $i = $end === false ? $n : $end + 2;
                $tokens[] = ['op', substr($s, $start, $i - $start)];
                continue;
            }
            $isNumber = is_numeric($word) || preg_match('/^[+-]?\.?\d/', $word) === 1;
            $tokens[] = [$isNumber || in_array($word, ['true', 'false', 'null'], true) ? 'operand' : 'op', $word];
        }

        return $tokens;
    }
}
