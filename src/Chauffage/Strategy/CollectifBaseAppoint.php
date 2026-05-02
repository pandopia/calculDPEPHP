<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Chauffage collectif base + appoint — §9.8 p.65-66.
 *
 * cfg_id = 10 : "installation de chauffage collectif avec base + appoint"
 *
 * La base (générateur collectif) fonctionne seule au-dessus d'une température T calculée.
 * L'appoint (générateur individuel) prend en charge le reste du besoin.
 *
 *   Pe = Pn × Rd × Rr × Re           (puissance émise utile, kW)
 *   T  = 14 − Pe × DH14 / Bch        (température de basculement, °C)
 *
 *   Bch_basej = Bchj × (1 − DHTj/DH14j)
 *   DHTj = Nrefj × (Textj − Tbase) × Xj^5 × (14 − 28Xj + 20Xj² − 5Xj³)
 *   Xj   = 0.5 × (T − Tbase) / (Textj − Tbase)
 *
 *   Cch1 = Bch_base × INT1 × Ich1   (base)
 *   Cch2 = (Bch − Bch_base) × INT2 × Ich2   (appoint)
 *
 * Conventionnellement l'appoint couvre 50% du besoin (Pn_appoint = 50% du dimensionnement).
 *
 * @spec-section  9.8
 * @spec-pages    65-66
 * @spec-source   resources/specsplitted/09-conso-chauffage/08-collectif-base-appoint.md
 * @xml-input     installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id=10, surface_chauffee, rdim,
 *                    enum_methode_calcul_conso_id, batiment_materiaux_anciens}
 *                generateur_chauffage[0].donnee_intermediaire.{pn, rendement_generation}
 *                emetteur_chauffage[0,1].donnee_intermediaire.{i0, rendement_emission, rendement_distribution, rendement_regulation}
 * @xml-output    installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 *                generateur_chauffage[0].donnee_intermediaire.conso_ch
 *                generateur_chauffage[1].donnee_intermediaire.conso_ch
 * @depends-on    \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 *                \CalculDpePHP\Chauffage\Rendement\EmissionCalculator
 *                \CalculDpePHP\Chauffage\Rendement\DistributionCalculator
 *                \CalculDpePHP\Chauffage\Rendement\RegulationCalculator
 *                \CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator
 *                \CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator
 * @tables        chauffage/dh14_base_appoint, chauffage/text_mensuel_base_appoint, reference/tv_sollicitations
 */
final class CollectifBaseAppoint implements CalculatorInterface
{
    /** Tbase (°C) par zone_id (1-8) et altitude_id (1-3) — §18.1 p.121 */
    private const TBASE = [
        1 => [1 => -9.5, 2 => -11.5, 3 => -13.5],   // H1a
        2 => [1 => -9.5, 2 => -11.5, 3 => -13.5],   // H1b
        3 => [1 => -9.5, 2 => -11.5, 3 => -13.5],   // H1c
        4 => [1 => -6.5, 2 => -8.5,  3 => -10.5],   // H2a
        5 => [1 => -6.5, 2 => -8.5,  3 => -10.5],   // H2b
        6 => [1 => -6.5, 2 => -8.5,  3 => -10.5],   // H2c
        7 => [1 => -6.5, 2 => -8.5,  3 => -10.5],   // H2d
        8 => [1 => -3.5, 2 => -5.5,  3 => -7.5],    // H3
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Chauffage\BesoinChauffageCalculator',
            '\CalculDpePHP\Chauffage\Rendement\EmissionCalculator',
            '\CalculDpePHP\Chauffage\Rendement\DistributionCalculator',
            '\CalculDpePHP\Chauffage\Rendement\RegulationCalculator',
            '\CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator',
            '\CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        if ($node->nodeName !== 'installation_chauffage') {
            return false;
        }
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                foreach ($child->childNodes as $c) {
                    if ($c instanceof DOMElement && $c->nodeName === 'enum_cfg_installation_ch_id') {
                        return (int)trim($c->textContent) === 10;
                    }
                }
            }
        }
        return false;
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Contexte bâtiment ──────────────────────────────────────────────
        $bch   = (float)$context->get('chauffage.besoin_ch', 0.0);
        $gv    = (float)$context->get('chauffage.gv', 1.0);
        if ($bch <= 0.0) {
            return;
        }

        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : 1;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : 1;
        $tbase  = (float)(self::TBASE[$zoneId][$altId] ?? -9.5);

        // ── 2. Paramètres de l'installation ──────────────────────────────────
        $logementNode = $node->parentNode?->parentNode;
        $hsp        = $accessor->getFloatOrNull('./caracteristique_generale/hsp', $logementNode) ?? 2.5;
        $shImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logementNode) ?? 0.0;
        $surface    = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $node) ?? $shImmeuble;
        $rdim       = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        if ($rdim <= 0.0) {
            $rdim = 1.0;
        }
        if ($shImmeuble <= 0.0) {
            $shImmeuble = $surface ?: 1.0;
        }

        // ── 3. ilpa : parois anciennes + inertie lourde ───────────────────────
        $matAnciensRaw = $accessor->getIntOrNull('./donnee_entree/batiment_materiaux_anciens', $logementNode) ?? 0;
        $inertieId     = $accessor->getIntOrNull('./donnee_intermediaire/enum_classe_inertie_id', $logementNode) ?? 1;
        // PHP enum: 1=légère, 2=moyenne, 3=lourde, 4=très lourde
        $ilpa = ($matAnciensRaw === 1 && $inertieId >= 3) ? 1 : 0;

        // ── 4. Tables climatiques ─────────────────────────────────────────────
        $tvS    = $context->tables->load('reference/tv_sollicitations')[$zoneId][$altId] ?? [];
        $tvDH14 = $context->tables->load('chauffage/dh14_base_appoint')[$ilpa][$altId] ?? [];
        $tvText = $context->tables->load('chauffage/text_mensuel_base_appoint')[$ilpa][$altId] ?? [];

        // ── 5. Générateurs et émetteurs (base=0, appoint=1) ──────────────────
        [$genBase, $genAppoint] = $this->listGenerateurs($node);
        [$emBase,  $emAppoint]  = $this->listEmetteurs($node);

        // ── 6. Puissance émise base (kW) ─────────────────────────────────────
        $pnW  = $accessor->getFloatOrNull('./donnee_intermediaire/pn', $genBase) ?? 0.0;
        $pnKw = $pnW / 1000.0;
        $rdBase = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_distribution', $emBase) ?? 1.0;
        $rrBase = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_regulation',   $emBase) ?? 1.0;
        $reBase = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_emission',     $emBase) ?? 1.0;
        $pe = ($pnKw > 0.0) ? $pnKw * $rdBase * $rrBase * $reBase : 0.0;

        // ── 7. Besoin de l'installation (kWh) ─────────────────────────────────
        $bch1 = $bch * ($surface / $shImmeuble);  // part de l'installation

        // ── 8. DH14 saison complète (Σ mois) ─────────────────────────────────
        $dh14Saison = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $dh14Saison += (float)(($tvDH14[$j] ?? [])[$zoneId] ?? 0.0);
        }

        // ── 9. Température de basculement T ──────────────────────────────────
        if ($pe > 0.0 && $dh14Saison > 0.0 && $bch1 > 0.0) {
            $t = 14.0 - ($pe * $dh14Saison) / $bch1;
        } else {
            // Pas de base fonctionnelle : 50% par convention (spec p.66)
            $t = -9999.0;  // sentinel : bch_base_j = 0.5 × bch_j
        }

        // ── 10. Besoin mensuel proportionnel à DH19 ────────────────────────────
        $sumDH19 = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $row = $tvS[$j] ?? null;
            if ($row !== null && isset($row['DH19']) && $row['DH19'] !== null) {
                $sumDH19 += (float)$row['DH19'];
            }
        }

        // ── 11. Calcul mensuel de Bch_base ────────────────────────────────────
        $bchBase = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $rowS    = $tvS[$j] ?? null;
            $dh14j   = (float)(($tvDH14[$j] ?? [])[$zoneId] ?? 0.0);
            $textj   = (float)(($tvText[$j] ?? [])[$zoneId] ?? 0.0);
            $nref19j = (float)(($rowS !== null ? ($rowS['Nref19'] ?? 0) : 0));
            $dh19j   = (float)(($rowS !== null ? ($rowS['DH19']   ?? 0.0) : 0.0));

            // Besoin mensuel proportionnel au DH19 mensuel
            $bchJ = ($sumDH19 > 0.0) ? $bch1 * $dh19j / $sumDH19 : 0.0;

            if ($t < -999.0) {
                // Convention 50%
                $bchBaseJ = 0.5 * $bchJ;
            } elseif ($pe <= 0.0 || $dh14j <= 0.0 || $textj <= 0.0) {
                $bchBaseJ = 0.0;
            } else {
                $denomXj = $textj - $tbase;
                if (abs($denomXj) < 1e-9) {
                    $bchBaseJ = 0.0;
                } else {
                    $xj = 0.5 * ($t - $tbase) / $denomXj;
                    // §9.8 p.66 : DHTj = Nrefj × (Textj-Tbase) × Xj^5 × (14-28Xj+20Xj²-5Xj³)
                    $x2 = $xj * $xj;
                    $x3 = $x2 * $xj;
                    $x5 = $x2 * $x3;
                    $poly = 14.0 - 28.0 * $xj + 20.0 * $x2 - 5.0 * $x3;
                    $dhtj = $nref19j * ($textj - $tbase) * $x5 * $poly;
                    $dhtj = max(0.0, $dhtj);

                    $ratioDh = ($dh14j > 0.0) ? 1.0 - $dhtj / $dh14j : 0.0;
                    $bchBaseJ = ($ratioDh > 0.0) ? $bchJ * $ratioDh : 0.0;
                }
            }

            $bchBase += $bchBaseJ;
        }
        $bchBase = max(0.0, min($bchBase, $bch1));

        // ── 12. Intermittences ─────────────────────────────────────────────────
        $g = ($hsp * $shImmeuble) > 0.0 ? $gv / ($hsp * $shImmeuble) : 1.0;
        $i0Base    = $accessor->getFloatOrNull('./donnee_intermediaire/i0', $emBase)    ?? 1.0;
        $i0Appoint = $accessor->getFloatOrNull('./donnee_intermediaire/i0', $emAppoint) ?? $i0Base;
        $intBase    = $i0Base    / (1.0 + 0.1 * ($g - 1.0));
        $intAppoint = $i0Appoint / (1.0 + 0.1 * ($g - 1.0));

        // ── 13. Rendements base ────────────────────────────────────────────────
        $rgBase = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_generation', $genBase) ?? 1.0;
        $denomBase = max(1e-9, $rgBase * $reBase * $rdBase * $rrBase);
        $consoBase = $bchBase * $intBase / $denomBase;

        // ── 14. Rendements appoint ─────────────────────────────────────────────
        $rdApp = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_distribution', $emAppoint) ?? 1.0;
        $rrApp = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_regulation',   $emAppoint) ?? 1.0;
        $reApp = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_emission',     $emAppoint) ?? 1.0;
        $rgApp = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_generation', $genAppoint) ?? 1.0;
        $denomApp = max(1e-9, $rgApp * $reApp * $rdApp * $rrApp);
        $consoApp = max(0.0, $bch1 - $bchBase) * $intAppoint / $denomApp;

        $consoTotal = $consoBase + $consoApp;

        // ── 15. Écriture dans installation.donnee_intermediaire ────────────────
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch',          $bch1);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $bch1);
        $accessor->setChildValue($di, 'conso_ch',           $consoTotal);
        $accessor->setChildValue($di, 'conso_ch_depensier', $consoTotal);

        // ── 16. Écriture dans chaque générateur ───────────────────────────────
        if ($genBase !== null) {
            $genDi = $accessor->ensureDonneeIntermediaire($genBase);
            $accessor->setChildValue($genDi, 'conso_ch',           $consoBase);
            $accessor->setChildValue($genDi, 'conso_ch_depensier', $consoBase);
        }
        if ($genAppoint !== null) {
            $genDi = $accessor->ensureDonneeIntermediaire($genAppoint);
            $accessor->setChildValue($genDi, 'conso_ch',           $consoApp);
            $accessor->setChildValue($genDi, 'conso_ch_depensier', $consoApp);
        }
    }

    /**
     * @return array{0: DOMElement|null, 1: DOMElement|null}  [base, appoint]
     */
    private function listGenerateurs(DOMElement $node): array
    {
        $genCollection = $this->getChild($node, 'generateur_chauffage_collection');
        if ($genCollection === null) {
            return [null, null];
        }
        $gens = [];
        foreach ($genCollection->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'generateur_chauffage') {
                $gens[] = $child;
            }
        }
        return [$gens[0] ?? null, $gens[1] ?? null];
    }

    /**
     * @return array{0: DOMElement|null, 1: DOMElement|null}  [base, appoint]
     */
    private function listEmetteurs(DOMElement $node): array
    {
        $emCollection = $this->getChild($node, 'emetteur_chauffage_collection');
        if ($emCollection === null) {
            return [null, null];
        }
        $ems = [];
        foreach ($emCollection->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'emetteur_chauffage') {
                $ems[] = $child;
            }
        }
        return [$ems[0] ?? null, $ems[1] ?? null];
    }

    private function getChild(DOMElement $parent, string $tag): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $tag) {
                return $child;
            }
        }
        return null;
    }
}
