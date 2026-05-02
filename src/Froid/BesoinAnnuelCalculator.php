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
    /** Cin (J/K) = coeff × Sh, par classe d'inertie (§10.2 p.69) */
    private const CIN_COEFF = [
        1 => 110_000.0, // légère
        2 => 165_000.0, // moyenne
        3 => 260_000.0, // lourde
        4 => 260_000.0, // très lourde
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

        $sh  = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node) ?? 0.0;
        $cin = $cinCoeff * $sh;  // J/K

        $t = ($gv > 0.0) ? $cin / (3600.0 * $gv) : 0.0;
        $a = 1.0 + $t / 15.0;

        // ── 4. Apports froid (de FCalculator via contexte) ─────────────────────
        $asFrMensuel = (array)$context->get('apport.as_fr_mensuel', []);
        $aiFrMensuel = (array)$context->get('apport.ai_fr_mensuel', []);

        // ── 5. Données climatiques DH28/DH26 de tv_sollicitations ──────────────
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : null;
        $tvS    = ($zoneId !== null && $altId !== null)
            ? ($context->tables->load('reference/tv_sollicitations')[$zoneId][$altId] ?? null)
            : null;

        // ── 6. Boucle mensuelle ─────────────────────────────────────────────────
        $bfrTotal     = 0.0;
        $bfrDepTotal  = 0.0;

        for ($j = 1; $j <= 12; $j++) {
            $row    = $tvS[$j] ?? null;
            $dh28j  = ($row !== null && isset($row['DH28']) && $row['DH28'] !== null) ? (float)$row['DH28'] : 0.0;
            $dh26j  = ($row !== null && isset($row['DH26']) && $row['DH26'] !== null) ? (float)$row['DH26'] : 0.0;

            $asJ = ($asFrMensuel[$j] ?? 0.0) * 1000.0; // kWh → Wh
            $aiJ = ($aiFrMensuel[$j] ?? 0.0) * 1000.0; // kWh → Wh
            $gains = $asJ + $aiJ;

            $bfrTotal    += $this->besoinMensuel($gains, $gv, $dh28j, $a);
            $bfrDepTotal += $this->besoinMensuel($gains, $gv, $dh26j, $a);
        }

        $this->writeOutputs($accessor, $node, $bfrTotal, $bfrDepTotal);
        $context->set('froid.besoin_fr',          $bfrTotal);
        $context->set('froid.besoin_fr_depensier', $bfrDepTotal);
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

    private function writeOutputs(NodeAccessor $accessor, DOMElement $node, float $bfr, float $bfrDep): void
    {
        $sortie         = $accessor->ensureSortie($node);
        $apportEtBesoin = $this->ensureApportEtBesoin($sortie);
        $accessor->setChildValue($apportEtBesoin, 'besoin_fr',          $bfr);
        $accessor->setChildValue($apportEtBesoin, 'besoin_fr_depensier', $bfrDep);
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
