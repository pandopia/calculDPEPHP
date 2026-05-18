<?php

declare(strict_types=1);

namespace CalculDpePHP\Froid;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Besoin annuel de froid Bfr et Bfr_depensier (§10.1-10.2 p.68-69).
 *
 * Bfr = Σ_j Bfrj
 *
 * Formule mensuelle (§10.2) :
 *   Rbthj = (Ai_frj + As_frj) / (GV × DH28j)     [ou DH26j pour dépensier]
 *   Si Rbthj < 0.5 : Bfrj = 0
 *   Sinon :
 *     t   = Cin / (3600 × GV)        Cin en J/K selon inertie
 *     a   = 1 + t / 15
 *     futj = (1 − Rbthj^−a) / (1 − Rbthj^−(a+1))   si Rbthj ≠ 1
 *          = a / (a+1)                               si Rbthj = 1
 *     Bfrj = (Ai_frj + As_frj) / 1000 + futj × GV × DH28j / 1000
 *
 * Les apports froid (Ai_fr, As_fr) sont issus de FCalculator (contexte).
 * Les données climatiques DH28/DH26 proviennent de tv_sollicitations.
 *
 * NOTE : si climatisation_collection est vide OU si les données DH28/DH26
 * ne sont pas disponibles, besoin_fr = 0 (colonne non encore digitalisée).
 *
 * @spec-section 10.1-10.2
 * @spec-pages   68-69
 * @spec-source  resources/specsplitted/10-conso-froid/01-besoin-annuel.md
 * @xml-input    logement.climatisation_collection
 * @xml-output   logement.sortie.apport_et_besoin.{besoin_fr, besoin_fr_depensier}
 * @depends-on   \CalculDpePHP\Enveloppe\EnveloppeAggregator, \CalculDpePHP\Apport\FCalculator, \CalculDpePHP\Ventilation\VentilationAggregator
 * @tables       reference/tv_sollicitations (colonnes DH28, DH26, Nref28, Nref26 — à digitaliser TASK-A05)
 */
final class BesoinAnnuelCalculator implements CalculatorInterface
{
    /**
     * Cin (J/K) = coeff × Sh, par classe d'inertie (§10.2 p.69).
     * Mapping XSD : 1=très lourde, 2=lourde, 3=moyenne, 4=légère.
     */
    private const CIN_COEFF = [
        1 => 260_000.0, // très lourde
        2 => 260_000.0, // lourde
        3 => 165_000.0, // moyenne
        4 => 110_000.0, // légère
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Enveloppe\EnveloppeAggregator',
            '\CalculDpePHP\Apport\FCalculator',
            '\CalculDpePHP\Ventilation\VentilationAggregator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Y a-t-il un système de climatisation ? ──────────────────────────
        $hasClimatisation = false;
        foreach ($node->getElementsByTagName('climatisation') as $c) {
            if ($c instanceof DOMElement) {
                $hasClimatisation = true;
                break;
            }
        }

        if (!$hasClimatisation) {
            $this->writeOutputs($accessor, $node, 0.0, 0.0);
            $context->set('froid.besoin_fr',          0.0);
            $context->set('froid.besoin_fr_depensier', 0.0);
            return;
        }

        // ── 2. GV ──────────────────────────────────────────────────────────────
        $dpParois = (float)$context->get('enveloppe.dp_parois', 0.0);
        $dpPT     = (float)$context->get('enveloppe.dp_pont_thermique', 0.0);
        $hvent    = (float)$context->get('ventilation.hvent', 0.0);
        $hperm    = (float)$context->get('ventilation.hperm', 0.0);
        $gv       = $dpParois + $dpPT + $hvent + $hperm;

        if ($gv <= 0.0) {
            $this->writeOutputs($accessor, $node, 0.0, 0.0);
            $context->set('froid.besoin_fr',          0.0);
            $context->set('froid.besoin_fr_depensier', 0.0);
            return;
        }

        // ── 3. Inertie → Cin ────────────────────────────────────────────────────
        $inertieId = $accessor->getIntOrNull('./enveloppe/inertie/enum_classe_inertie_id', $node);
        $cinCoeff  = self::CIN_COEFF[$inertieId ?? 2] ?? 165_000.0;

        // Sh : surface_habitable_logement pour maison/appartement, sinon surface_habitable_immeuble
        $sh = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node)
            ?? $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node)
            ?? 0.0;
        $cin = $cinCoeff * $sh;  // J/K

        $t = ($gv > 0.0) ? $cin / (3600.0 * $gv) : 0.0;
        $a = 1.0 + $t / 15.0;

        // ── 4. Sse mensuelle (depuis SurfaceSudEquivalenteCalculator) ─────────
        $sseMensuel    = (array)$context->get('apport.sse_mensuel',     array_fill(1, 12, 0.0));
        $sseEtsMensuel = (array)$context->get('apport.sse_ets_mensuel', array_fill(1, 12, 0.0));

        // ── 4b. Nadeq (= valeur stockée par FCalculator) ──────────────────────
        $nadeq = (float)$context->get('apport.nadeq', 0.0);

        // ── 5. Sollicitations refroidissement ──────────────────────────────────
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : null;
        $tvFr   = ($zoneId !== null && $altId !== null)
            ? ($context->tables->load('reference/tv_sollicitations_froid')[$zoneId][$altId] ?? null)
            : null;

        // Apport interne forfaitaire (§6.1) — identique chauffage/refroidissement
        $aiBase = 3.52 * $sh + 90.0 * (132.0 / 168.0) * $nadeq; // W

        // ── 6. Boucle mensuelle ─────────────────────────────────────────────────
        $bfrTotal       = 0.0;
        $bfrDepTotal    = 0.0;
        $apportSolaireFr = 0.0;
        $apportInterneFr = 0.0;

        for ($j = 1; $j <= 12; $j++) {
            $row    = $tvFr[$j] ?? null;
            if ($row === null) {
                continue;
            }
            $dh28j   = (float)($row['DH28']   ?? 0.0);
            $dh26j   = (float)($row['DH26']   ?? 0.0);
            $nref28j = (float)($row['Nref28'] ?? 0.0);
            $nref26j = (float)($row['Nref26'] ?? 0.0);
            $eFr28j  = (float)($row['E_fr_28'] ?? 0.0);
            $eFr26j  = (float)($row['E_fr_26'] ?? 0.0);

            $ssej = ($sseMensuel[$j] ?? 0.0) + ($sseEtsMensuel[$j] ?? 0.0);

            // Asj_fr = 1000 × Ssej × E_fr_j   (Wh)
            $asFr28 = 1000.0 * $ssej * $eFr28j;
            $asFr26 = 1000.0 * $ssej * $eFr26j;

            // Aij_fr = aiBase × Nref_fr_j   (Wh)
            $aiFr28 = $aiBase * $nref28j;
            $aiFr26 = $aiBase * $nref26j;

            $bfrTotal    += $this->besoinMensuel($asFr28 + $aiFr28, $gv, $dh28j, $a);
            $bfrDepTotal += $this->besoinMensuel($asFr26 + $aiFr26, $gv, $dh26j, $a);

            // Apports annuels (kWh) — consigne conventionnelle (28°C)
            $apportSolaireFr += $ssej * $eFr28j;          // Asj_kWh = Ssej × E_fr_28_j
            $apportInterneFr += $aiFr28 / 1000.0;          // Aij_kWh
        }

        $this->writeOutputs($accessor, $node, $bfrTotal, $bfrDepTotal, $apportSolaireFr, $apportInterneFr);
        $context->set('froid.besoin_fr',          $bfrTotal);
        $context->set('froid.besoin_fr_depensier', $bfrDepTotal);
        $context->set('apport.apport_solaire_fr', $apportSolaireFr);
        $context->set('apport.apport_interne_fr', $apportInterneFr);
    }

    /**
     * §10.2 p.68-69 : besoin mensuel de froid (kWh).
     * DH = Σ(Text_h - Tint) sur les heures de refroidissement (°Ch).
     * gains en Wh, gv en W/K, DH en °Ch → résultat en kWh.
     */
    private function besoinMensuel(float $gains, float $gv, float $dh, float $a): float
    {
        if ($dh <= 0.0) {
            return 0.0;
        }

        $gvDh = $gv * $dh;
        $rbth = ($gvDh > 0.0) ? $gains / $gvDh : 0.0;

        if ($rbth < 0.5) {
            return 0.0;
        }

        $fut = $this->computeFut($rbth, $a);
        // Bfrj = gains/1000 + fut × GV × DH / 1000
        return ($gains + $fut * $gvDh) / 1000.0;
    }

    /**
     * §10.2 : facteur d'utilisation des apports pour le froid.
     * fut = (1 − Rbth^−a) / (1 − Rbth^−(a+1))   si Rbth ≠ 1
     *      = a / (a+1)                             si Rbth = 1
     */
    private function computeFut(float $rbth, float $a): float
    {
        if (abs($rbth - 1.0) < 1e-9) {
            return $a / ($a + 1.0);
        }
        $r_neg_a  = $rbth ** (-$a);
        $r_neg_a1 = $rbth ** (-$a - 1.0);
        $denom = 1.0 - $r_neg_a1;
        if (abs($denom) < 1e-12) {
            return 1.0;
        }
        return (1.0 - $r_neg_a) / $denom;
    }

    private function writeOutputs(
        NodeAccessor $accessor,
        DOMElement $node,
        float $bfr,
        float $bfrDep,
        float $apportSolaireFr = 0.0,
        float $apportInterneFr = 0.0,
    ): void {
        $sortie         = $accessor->ensureSortie($node);
        $apportEtBesoin = $this->ensureApportEtBesoin($sortie);
        $accessor->setChildValue($apportEtBesoin, 'apport_solaire_fr',  $apportSolaireFr);
        $accessor->setChildValue($apportEtBesoin, 'apport_interne_fr',  $apportInterneFr);
        $accessor->setChildValue($apportEtBesoin, 'besoin_fr',          $bfr);
        $accessor->setChildValue($apportEtBesoin, 'besoin_fr_depensier', $bfrDep);

        // Per-climatisation : besoin_fr au prorata de surface_clim parmi toutes les climatisations,
        // puis ramené à la part climatisée du bâtiment.
        $shTotal = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node)
            ?? $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node)
            ?? 0.0;
        if ($shTotal <= 0.0) {
            return;
        }
        foreach ($node->getElementsByTagName('climatisation') as $clim) {
            if (!$clim instanceof DOMElement) {
                continue;
            }
            $surfClim = $accessor->getFloatOrNull('./donnee_entree/surface_clim', $clim)
                ?? $accessor->getFloatOrNull('./donnee_entree/surface_refroidie', $clim)
                ?? 0.0;
            // bfr ici = besoin pour le bâtiment entier → ramener à la surface effectivement refroidie
            $ratio = ($surfClim > 0.0) ? min(1.0, $surfClim / $shTotal) : 1.0;
            $di = $accessor->ensureDonneeIntermediaire($clim);
            $accessor->setChildValue($di, 'besoin_fr', $bfr * $ratio);
        }
    }

    private function ensureApportEtBesoin(DOMElement $sortie): DOMElement
    {
        foreach ($sortie->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'apport_et_besoin') {
                return $child;
            }
        }
        $el = $sortie->ownerDocument->createElement('apport_et_besoin');
        $sortie->appendChild($el);
        return $el;
    }
}
