<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Common\ClimaticSolicitations;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Chauffage\Rendement\RendementGenerateur;
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
 * @spec-section  8, 9.1.1, 9.8, 17.2.1.1, 18.3
 * @spec-pages    55-59, 65-66, 111-112, 136-137
 * @spec-source   resources/specsplitted/08-intermittence/00-calcul.md
 * @spec-source   resources/specsplitted/09-conso-chauffage/01-installation-seule/01-conso.md
 * @spec-source   resources/specsplitted/09-conso-chauffage/08-collectif-base-appoint.md
 * @spec-source   resources/specsplitted/18-annexes/03-inertie-lourde/00-overview.md
 * @xml-input     installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id=10, surface_chauffee, rdim,
 *                    enum_methode_calcul_conso_id}
 *                logement.meteo.batiment_materiaux_anciens, logement.enveloppe.inertie.enum_classe_inertie_id
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
    use StrategieComputeTrait;

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
        $this->calculerScenario($node, $context, false);
        $this->calculerScenario($node, $context, true);
    }

    /**
     * §9.1.1 p.57 et §9.8 p.65-66 : appliquer le bilan base/appoint à
     * chaque consigne (19 °C ou 21 °C) avec ses besoins mensuels propres.
     *
     * @spec-formula F-9.8-bch-base-mensuel
     */
    private function calculerScenario(DOMElement $node, CalculationContext $context, bool $depensier): void
    {
        $accessor = new NodeAccessor($context->document);
        $suffixe = $depensier ? '_depensier' : '';

        // ── 1. Contexte bâtiment ──────────────────────────────────────────────
        $bch   = (float)$context->get('chauffage.besoin_ch' . $suffixe, 0.0);
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
        $matAnciensRaw = $accessor->getIntOrNull('./meteo/batiment_materiaux_anciens', $logementNode) ?? 0;
        $inertieId = (int) $context->get(
            'inertie.classe_id',
            $accessor->getIntOrNull('./enveloppe/inertie/enum_classe_inertie_id', $logementNode) ?? 1,
        );
        // §18.3 : parois anciennes ET inertie lourde. Le XSD numérote
        // 1=très lourde, 2=lourde, 3=moyenne, 4=légère.
        $ilpa = ($matAnciensRaw === 1 && in_array($inertieId, [1, 2], true)) ? 1 : 0;

        // ── 4. Tables climatiques ─────────────────────────────────────────────
        $tvS    = ClimaticSolicitations::heating($context, $zoneId, $altId);
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
        // Part de l'installation dans le besoin. §17.1 ne rapporte la surface
        // chauffée à celle de l'immeuble que lorsque `chauffage.besoin_ch` est
        // lui-même à l'échelle de l'immeuble ; sur les modes qui le portent au
        // logement seul, le diviseur est la surface du logement et la part
        // vaut 1. `partSurfacique` porte cette distinction pour les dix autres
        // stratégies : la dupliquer ici reviendrait à la laisser diverger.
        $partSurface = $this->partSurfacique($accessor, $node);
        $bch1 = $bch * $partSurface;
        $besoinsMensuels = (array)$context->get('chauffage.besoin_ch_mensuel' . $suffixe, []);

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

        // Repli pour un contexte construit sans BesoinChauffageCalculator
        // (appel isolé). Le pipeline fournit toujours les douze Bchj réels.
        $colonneDh = $depensier ? 'DH21' : 'DH19';
        $colonneNref = $depensier ? 'Nref21' : 'Nref19';
        $sommeDh = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $row = $tvS[$j] ?? null;
            if ($row !== null && isset($row[$colonneDh]) && $row[$colonneDh] !== null) {
                $sommeDh += (float)$row[$colonneDh];
            }
        }

        // ── 11. Calcul mensuel de Bch_base ────────────────────────────────────
        $bchBase = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $rowS    = $tvS[$j] ?? null;
            $dh14j   = (float)(($tvDH14[$j] ?? [])[$zoneId] ?? 0.0);
            $textj   = (float)(($tvText[$j] ?? [])[$zoneId] ?? 0.0);
            $nrefj = (float)(($rowS !== null ? ($rowS[$colonneNref] ?? 0) : 0));
            $dhj   = (float)(($rowS !== null ? ($rowS[$colonneDh] ?? 0.0) : 0.0));

            $bchJ = $besoinsMensuels !== []
                ? (float)($besoinsMensuels[$j] ?? 0.0) * $partSurface
                : (($sommeDh > 0.0) ? $bch1 * $dhj / $sommeDh : 0.0);

            if ($t < -999.0) {
                // Convention 50%
                $bchBaseJ = 0.5 * $bchJ;
            } elseif ($pe <= 0.0 || $dh14j <= 0.0) {
                // Hors saison de chauffe, DH14j est nul et le mois ne porte
                // aucun besoin : rien à répartir entre base et appoint.
                //
                // Le test ne peut pas porter sur `Textj` : c'est une
                // température extérieure moyenne en °C, et §18.3 la tabule
                // **négative** en janvier, février et décembre dès la classe
                // d'altitude 3 — −0,8 °C en décembre pour H1c, par exemple.
                // Les traiter comme des données manquantes vidait la base de
                // trois mois d'hiver sur les huit tabulations concernées,
                // c'est-à-dire là où elle travaille le plus.
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
                    $dhtj = $nrefj * ($textj - $tbase) * $x5 * $poly;
                    $dhtj = max(0.0, $dhtj);

                    $ratioDh = ($dh14j > 0.0) ? 1.0 - $dhtj / $dh14j : 0.0;
                    $bchBaseJ = ($ratioDh > 0.0) ? $bchJ * $ratioDh : 0.0;
                }
            }

            $bchBase += $bchBaseJ;
        }
        $bchBase = max(0.0, min($bchBase, $bch1));

        // ── 12. Intermittences ─────────────────────────────────────────────────
        // §8 : GV et Hsp × Sh décrivent la même enveloppe. §17.2.1.1
        // calcule le besoin d'un DPE à l'appartement sur ce logement seul.
        $modeDpe = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logementNode);
        $shG = in_array($modeDpe, self::MODES_LOGEMENT_SEUL, true)
            ? ($accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logementNode) ?? $shImmeuble)
            : $shImmeuble;
        $g = ($hsp * $shG) > 0.0 ? $gv / ($hsp * $shG) : 1.0;
        $i0Base    = $accessor->getFloatOrNull('./donnee_intermediaire/i0', $emBase)    ?? 1.0;
        $i0Appoint = $accessor->getFloatOrNull('./donnee_intermediaire/i0', $emAppoint) ?? $i0Base;
        $intBase    = $i0Base    / (1.0 + 0.1 * ($g - 1.0));
        $intAppoint = $i0Appoint / (1.0 + 0.1 * ($g - 1.0));

        // ── 13. Rendements base ────────────────────────────────────────────────
        $rgBase = $this->rendementScenario($accessor, $genBase, $context, $depensier);
        $denomBase = max(1e-9, $rgBase * $reBase * $rdBase * $rrBase);
        $consoBase = $bchBase * $intBase / $denomBase;

        // ── 14. Rendements appoint ─────────────────────────────────────────────
        $rdApp = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_distribution', $emAppoint) ?? 1.0;
        $rrApp = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_regulation',   $emAppoint) ?? 1.0;
        $reApp = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_emission',     $emAppoint) ?? 1.0;
        $rgApp = $this->rendementScenario($accessor, $genAppoint, $context, $depensier);
        $denomApp = max(1e-9, $rgApp * $reApp * $rdApp * $rrApp);
        $consoApp = max(0.0, $bch1 - $bchBase) * $intAppoint / $denomApp;

        $consoTotal = $consoBase + $consoApp;

        // ── 15. Écriture dans installation.donnee_intermediaire ────────────────
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch' . $suffixe, $bch1);
        $accessor->setChildValue($di, 'conso_ch' . $suffixe, $consoTotal);

        // ── 16. Écriture dans chaque générateur ───────────────────────────────
        // §15.1.1 p.97 : chaque générateur n'appelle ses auxiliaires que sur
        // la part du besoin qu'il couvre — ici celle de la base et celle de
        // l'appoint, déjà séparées par §9.10.
        $partBase = $bch1 > 0.0 ? $bchBase / $bch1 : 1.0;
        if ($genBase !== null) {
            $genDi = $accessor->ensureDonneeIntermediaire($genBase);
            $accessor->setChildValue($genDi, 'conso_ch' . $suffixe, $consoBase);
            if (!$depensier) {
                \CalculDpePHP\Chauffage\PartBesoinGenerateur::publier($context, $genBase, $partBase);
            }
        }
        if ($genAppoint !== null) {
            $genDi = $accessor->ensureDonneeIntermediaire($genAppoint);
            $accessor->setChildValue($genDi, 'conso_ch' . $suffixe, $consoApp);
            if (!$depensier) {
                \CalculDpePHP\Chauffage\PartBesoinGenerateur::publier($context, $genAppoint, max(0.0, 1.0 - $partBase));
            }
        }
    }

    private function rendementScenario(NodeAccessor $accessor, ?DOMElement $generateur, CalculationContext $context, bool $depensier): float
    {
        if ($generateur === null) {
            return 1.0;
        }
        if ($depensier) {
            $rendements = (array)$context->get('chauffage.rendement_generation_depensier', []);
            $reference = $accessor->getStringOrNull('./donnee_entree/reference', $generateur);
            if ($reference !== null && isset($rendements[$reference])) {
                return (float)$rendements[$reference];
            }
        }
        return RendementGenerateur::lire($accessor, $generateur) ?? 1.0;
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
