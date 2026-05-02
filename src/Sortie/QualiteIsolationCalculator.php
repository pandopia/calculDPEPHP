<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use DOMXPath;

/**
 * Bloc <sortie><qualite_isolation> : Ubat et indices qualitatifs par paroi.
 *
 * Algorithme identique à open3cl (2021_04_13_qualite_isolation.js) :
 *   ubat = (Σ déperditions + PT) / Σ(surface_déperditive)
 *
 * Pour chaque type de paroi, Umoy = Σ(S×U) / Σ(S), filtré b>0.
 *
 * Seuils (strict < comme open3cl) :
 *   Enveloppe         : < 0.45 → 1, < 0.65 → 2, < 0.85 → 3, sinon 4
 *   Murs              : < 0.30 → 1, < 0.45 → 2, < 0.65 → 3, sinon 4
 *   Plancher bas      : < 0.25 → 1, < 0.45 → 2, < 0.65 → 3, sinon 4
 *   Comble aménagé    : < 0.18 → 1, < 0.25 → 2, < 0.30 → 3, sinon 4
 *   Toit terrasse     : < 0.25 → 1, < 0.30 → 2, < 0.35 → 3, sinon 4
 *   Comble perdu      : < 0.15 → 1, < 0.20 → 2, < 0.30 → 3, sinon 4
 *   Menuiseries (bv+portes) : < 1.60 → 1, < 2.20 → 2, < 3.00 → 3, sinon 4
 *
 * Tags plancher_haut écrits uniquement si des planchers de ce sous-type existent.
 *
 * @spec-section 3
 * @spec-pages   7
 * @spec-source  resources/specsplitted/03-enveloppe-deperditions/00-overview-GV.md
 * @xml-input    enveloppe.*
 * @xml-output   sortie.qualite_isolation.{ubat, qualite_isol_enveloppe, qualite_isol_mur,
 *               qualite_isol_plancher_haut_comble_amenage, qualite_isol_plancher_haut_toit_terrasse,
 *               qualite_isol_plancher_haut_comble_perdu, qualite_isol_plancher_bas,
 *               qualite_isol_menuiserie}
 * @depends-on   \CalculDpePHP\Enveloppe\EnveloppeAggregator
 * @tables       (aucune)
 */
final class QualiteIsolationCalculator implements CalculatorInterface
{
    // Seuils open3cl (strict <)
    private const ENV_THRESHOLDS  = [0.45, 0.65, 0.85];
    private const MUR_THRESHOLDS  = [0.30, 0.45, 0.65];
    private const PB_THRESHOLDS   = [0.25, 0.45, 0.65];
    private const CA_THRESHOLDS   = [0.18, 0.25, 0.30]; // comble aménagé
    private const TT_THRESHOLDS   = [0.25, 0.30, 0.35]; // toit terrasse
    private const CP_THRESHOLDS   = [0.15, 0.20, 0.30]; // comble perdu
    private const MENU_THRESHOLDS = [1.60, 2.20, 3.00];

    // enum_type_plancher_haut_id=12 → comble aménagé
    private const COMBLE_AMENAGE_ID = 12;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpePHP\Enveloppe\EnveloppeAggregator'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $xpath    = new DOMXPath($context->document);

        // ubat NUMERATOR : somme des déperditions stockées (open3cl: dp.deperdition_*)
        // Ces valeurs incluent déjà le coefficient b et les effets d'adjacence.
        $sortie   = $accessor->ensureSortie($node);
        $depBlock = null;
        foreach ($sortie->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === 'deperdition') {
                $depBlock = $c;
                break;
            }
        }
        $depNumerator = 0.0;
        foreach (['deperdition_mur', 'deperdition_plancher_bas', 'deperdition_plancher_haut',
                   'deperdition_baie_vitree', 'deperdition_porte', 'deperdition_pont_thermique'] as $f) {
            if ($depBlock !== null) {
                $depNumerator += $accessor->getFloatOrNull('./' . $f, $depBlock) ?? 0.0;
            }
        }

        // Qualité par paroi : Umoy = Σ(S×U) / ΣS, filtrés adj≠22 (+ b>0 pour murs/bv/portes)
        [$umurSU,   $sMur]   = $this->sumQualite($xpath, $node, 'mur',          'umur',         'surface_paroi_opaque', filterBGt0: true,  excludeAdj22: true);
        [$upbSU,    $sPB]    = $this->sumQualite($xpath, $node, 'plancher_bas',  'upb_final',    'surface_paroi_opaque', filterBGt0: false, excludeAdj22: true);
        [$ubvSU,    $sBV]    = $this->sumQualite($xpath, $node, 'baie_vitree',   'u_menuiserie', 'surface_totale_baie',  filterBGt0: true,  excludeAdj22: false);
        [$uPorteSU, $sPorte] = $this->sumQualite($xpath, $node, 'porte',         'uporte',       'surface_porte',        filterBGt0: true,  excludeAdj22: false);

        // Planchers hauts
        [$uCaMoy, $sCA, $uTtMoy, $sTT, $uCpMoy, $sCP] = $this->sumPlancherHaut($xpath, $node, $accessor);
        $sPH = $sCA + $sTT + $sCP;

        // ubat = déperditions_stockées / surface_déperditive (hors adj=22 dans dénominateur)
        $surfaceDeperditives = $sMur + $sPB + $sPH + $sBV + $sPorte;
        $ubat = $surfaceDeperditives > 0.0 ? round($depNumerator / $surfaceDeperditives, 3) : 0.0;

        // Moyennes pour qualité
        $umurMoy  = $sMur   > 0.0 ? $umurSU   / $sMur   : null;
        // plancher_bas : open3cl écrit toujours qualite_isol_plancher_bas (0/0 → u=0 → classe 1)
        $upbMoy   = $sPB    > 0.0 ? $upbSU    / $sPB    : 0.0;
        $sumMenuSU = $ubvSU + $uPorteSU;
        $surfMenu  = $sBV + $sPorte;
        $uMenuMoy  = $surfMenu > 0.0 ? $sumMenuSU / $surfMenu : null;

        $qi = $this->ensureChild($context, $sortie, 'qualite_isolation');

        $accessor->setChildValue($qi, 'ubat', $ubat);
        $accessor->setChildValue($qi, 'qualite_isol_enveloppe', $this->classe($ubat, self::ENV_THRESHOLDS));

        if ($umurMoy  !== null) {
            $accessor->setChildValue($qi, 'qualite_isol_mur', $this->classe($umurMoy, self::MUR_THRESHOLDS));
        }
        // plancher_bas always written (open3cl behaviour: qualite_isol(0,0,...) = 1)
        $accessor->setChildValue($qi, 'qualite_isol_plancher_bas', $this->classe($upbMoy, self::PB_THRESHOLDS));

        if ($uMenuMoy !== null) {
            $accessor->setChildValue($qi, 'qualite_isol_menuiserie', $this->classe($uMenuMoy, self::MENU_THRESHOLDS));
        }

        // Planchers hauts : tag écrit uniquement si ce sous-type existe (UphX > 0 dans open3cl)
        if ($uCaMoy !== null) {
            $accessor->setChildValue($qi, 'qualite_isol_plancher_haut_comble_amenage', $this->classe($uCaMoy, self::CA_THRESHOLDS));
        }
        if ($uTtMoy !== null) {
            $accessor->setChildValue($qi, 'qualite_isol_plancher_haut_toit_terrasse', $this->classe($uTtMoy, self::TT_THRESHOLDS));
        }
        if ($uCpMoy !== null) {
            $accessor->setChildValue($qi, 'qualite_isol_plancher_haut_comble_perdu', $this->classe($uCpMoy, self::CP_THRESHOLDS));
        }
    }

    /**
     * Somme Σ(S×U) et Σ(S) pour un type de paroi (qualité — sans coefficient b).
     * Filtres optionnels : b>0 et/ou exclusion adj=22 (local non déperditif chauffé).
     *
     * @return array{0: float, 1: float}  [sumSU, sumS]
     */
    private function sumQualite(
        DOMXPath $xpath,
        DOMElement $logement,
        string $tag,
        string $uTag,
        string $surfaceTag,
        bool $filterBGt0,
        bool $excludeAdj22,
    ): array {
        $sumSU = 0.0;
        $sumS  = 0.0;

        $nodes = $xpath->query(".//$tag", $logement);
        if ($nodes === false) {
            return [0.0, 0.0];
        }

        foreach ($nodes as $n) {
            if (!$n instanceof DOMElement) {
                continue;
            }
            if ($filterBGt0) {
                $bVal = $this->childFloat($n, 'donnee_intermediaire', 'b');
                if ($bVal === null || $bVal === 0.0) {
                    continue;
                }
            }
            if ($excludeAdj22) {
                $adjRaw = null;
                foreach ($n->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                        foreach ($child->childNodes as $gc) {
                            if ($gc instanceof DOMElement && $gc->nodeName === 'enum_type_adjacence_id') {
                                $adjRaw = trim($gc->textContent ?? '');
                                break 2;
                            }
                        }
                    }
                }
                if ($adjRaw === '22') {
                    continue;
                }
            }
            $u = $this->childFloat($n, 'donnee_intermediaire', $uTag);
            $s = $this->childFloat($n, 'donnee_entree', $surfaceTag);
            if ($u === null || $s === null) {
                continue;
            }
            $sumSU += $s * $u;
            $sumS  += $s;
        }

        return [$sumSU, $sumS];
    }

    /**
     * Catégorise les planchers hauts en 3 sous-types (comme open3cl) :
     * - comble aménagé  : adjacence=1 ET (type_ph=12 OU description contient "combles aménagés")
     * - toit terrasse   : adjacence=1 ET NON comble aménagé
     * - comble perdu    : adjacence≠1
     *
     * @return array{0:float|null, 1:float, 2:float|null, 3:float, 4:float|null, 5:float}
     *         [uCaMoy, sCA, uTtMoy, sTT, uCpMoy, sCP]
     *         Surfaces (sCA/sTT/sCP) excluent adj=22 (pour dénominateur ubat, comme open3cl).
     *         U moyens utilisent tous les planchers (comme open3cl pour le numérateur qualité).
     */
    private function sumPlancherHaut(DOMXPath $xpath, DOMElement $logement, NodeAccessor $accessor): array
    {
        $caSum = ['su' => 0.0, 's' => 0.0]; // s exclut adj=22
        $ttSum = ['su' => 0.0, 's' => 0.0];
        $cpSum = ['su' => 0.0, 's' => 0.0];

        $nodes = $xpath->query('.//plancher_haut', $logement);
        if ($nodes === false) {
            return [null, 0.0, null, 0.0, null, 0.0];
        }

        foreach ($nodes as $ph) {
            if (!$ph instanceof DOMElement) {
                continue;
            }
            $u = $this->childFloat($ph, 'donnee_intermediaire', 'uph');
            $s = $this->childFloat($ph, 'donnee_entree', 'surface_paroi_opaque');
            if ($u === null || $s === null) {
                continue;
            }

            $adjId    = $accessor->getIntOrNull('./donnee_entree/enum_type_adjacence_id', $ph);
            $typePhId = $accessor->getIntOrNull('./donnee_entree/enum_type_plancher_haut_id', $ph);
            $desc     = strtolower($accessor->getStringOrNull('./donnee_entree/description', $ph) ?? '');

            if ($adjId === 1) {
                $isCA   = ($typePhId === self::COMBLE_AMENAGE_ID)
                       || str_contains($desc, 'combles aménagés')
                       || str_contains($desc, 'comble aménagé');
                $bucket = $isCA ? 'ca' : 'tt';
            } else {
                $bucket = 'cp';
            }

            if ($bucket === 'ca') {
                $caSum['su'] += $s * $u;
                if ($adjId !== 22) { $caSum['s'] += $s; }
            } elseif ($bucket === 'tt') {
                $ttSum['su'] += $s * $u;
                if ($adjId !== 22) { $ttSum['s'] += $s; }
            } else {
                $cpSum['su'] += $s * $u;
                if ($adjId !== 22) { $cpSum['s'] += $s; }
            }
        }

        $uMoy = fn(array $a): ?float => $a['su'] > 0.0 ? $a['su'] / max($a['s'], 1e-9) : null;
        return [
            $uMoy($caSum), $caSum['s'],
            $uMoy($ttSum), $ttSum['s'],
            $uMoy($cpSum), $cpSum['s'],
        ];
    }

    /** Returns quality class 1-4: class $i if U < thresholds[$i-1] (strict, like open3cl), else 4. */
    private function classe(float $u, array $thresholds): int
    {
        foreach ($thresholds as $i => $t) {
            if ($u < $t) {
                return $i + 1;
            }
        }
        return 4;
    }

    private function childFloat(DOMElement $node, string $container, string $tag): ?float
    {
        foreach ($node->childNodes as $c) {
            if (!$c instanceof DOMElement || $c->nodeName !== $container) {
                continue;
            }
            foreach ($c->childNodes as $g) {
                if (!$g instanceof DOMElement || $g->nodeName !== $tag) {
                    continue;
                }
                $v = trim($g->textContent ?? '');
                if ($v === '') {
                    return null;
                }
                $v = str_replace(',', '.', $v);
                return is_numeric($v) ? (float)$v : null;
            }
        }
        return null;
    }

    private function ensureChild(CalculationContext $context, DOMElement $parent, string $tag): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) {
                return $c;
            }
        }
        $el = $context->document->createElement($tag);
        $parent->appendChild($el);
        return $el;
    }
}
