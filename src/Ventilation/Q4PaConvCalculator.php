<?php

declare(strict_types=1);

namespace CalculDpePHP\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Valeur conventionnelle de la perméabilité sous 4 Pa — §4 p.39.
 *
 * Algorithme :
 *   1. Si `enum_methode_saisie_q4pa_conv_id` ∈ {2, 3} (mesure < 2 ans ou RSET/RSEE) :
 *      lecture directe de `q4pa_conv_saisi`.
 *   2. Sinon : lookup `tv_q4pa_conv` par `tv_q4pa_conv_id`.
 *   3. Sans ligne exploitable : même forfait depuis la période, le type de
 *      bâtiment, l'isolation des parois et les joints des menuiseries (§4 p.39).
 *
 * @spec-section 4
 * @spec-pages 39
 * @spec-source resources/specsplitted/04-renouvellement-air/00-calcul.md
 * @xml-input  ventilation.donnee_entree.{enum_methode_saisie_q4pa_conv_id, q4pa_conv_saisi, tv_q4pa_conv_id}
 * @xml-input  logement.caracteristique_generale.{annee_construction, enum_periode_construction_id, enum_methode_application_dpe_log_id}
 * @xml-input  logement.enveloppe.{mur_collection, plancher_haut_collection, baie_vitree_collection, porte_collection}
 * @xml-output ventilation.donnee_intermediaire.q4pa_conv
 * @depends-on \\CalculDpePHP\\Enveloppe\\Mur\\BCalculator, \\CalculDpePHP\\Enveloppe\\PlancherHaut\\BCalculator
 * @depends-on \\CalculDpePHP\\Enveloppe\\BaieVitree\\BCalculator, \\CalculDpePHP\\Enveloppe\\Porte\\BCalculator
 * @tables tv_q4pa_conv
 */
final class Q4PaConvCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        // Les parois donnant sur un local chauffé ne participent pas aux
        // surfaces déperditives utilisées par les exceptions de la p.39.
        return [
            '\\CalculDpePHP\\Enveloppe\\Mur\\BCalculator',
            '\\CalculDpePHP\\Enveloppe\\PlancherHaut\\BCalculator',
            '\\CalculDpePHP\\Enveloppe\\BaieVitree\\BCalculator',
            '\\CalculDpePHP\\Enveloppe\\Porte\\BCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'ventilation';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('ventilation sans <donnee_entree>.');
        }

        $methode = $accessor->getIntOrNull('./enum_methode_saisie_q4pa_conv_id', $entree);

        if ($methode === 2 || $methode === 3) {
            $saisi = $accessor->getFloatOrNull('./q4pa_conv_saisi', $entree);
            if ($saisi !== null) {
                $intermediaire = $accessor->ensureDonneeIntermediaire($node);
                $accessor->setChildValue($intermediaire, 'q4pa_conv', $saisi);
                return;
            }
        }

        $tvId = $accessor->getIntOrNull('./tv_q4pa_conv_id', $entree);
        $table = $context->tables->load('ventilation/tv_q4pa_conv');
        $value = $tvId !== null ? ($table[$tvId] ?? null) : null;
        $value ??= $this->forfaitDepuisBatiment($node, $accessor, $table);
        if ($value === null) {
            return;
        }

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'q4pa_conv', (float)$value);
    }

    /**
     * §4 p.39 : les neuf forfaits ordinaires et les trois exceptions sont
     * définis par les caractéristiques physiques, pas par la présence d'un ID.
     * Une saisie de méthode 1 n'est pas une mesure et ne supplée pas cet ID.
     *
     * @param array<int, float> $table
     * @spec-formula F-4-q4pa-forfait
     */
    private function forfaitDepuisBatiment(DOMElement $node, NodeAccessor $accessor, array $table): ?float
    {
        $logement = $node->parentNode?->parentNode;
        if (!$logement instanceof DOMElement || $logement->nodeName !== 'logement') {
            return null;
        }
        $mode = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logement);
        if ($mode === null || $mode < 1 || $mode > 40) {
            return null;
        }
        $maison = in_array($mode, [1, 14, 18], true);
        $annee = $accessor->getIntOrNull('./caracteristique_generale/annee_construction', $logement);
        $periode = $accessor->getIntOrNull('./caracteristique_generale/enum_periode_construction_id', $logement);
        if ($annee !== null && $annee >= 1200 && $annee <= 2100) {
            $periode = match (true) {
                $annee < 1948 => 1,
                $annee <= 1974 => 2,
                $annee <= 2005 => 7,
                $annee <= 2012 => 8,
                default => 9,
            };
        }
        if ($periode === null || $periode < 1 || $periode > 10) {
            return null;
        }

        if ($periode <= 2 && $this->majoriteIsolee($logement, $accessor)) {
            return $table[$periode === 1 ? 10 : 11];
        }
        if ($periode === 1 && $this->majoriteAvecJoints($logement, $accessor)) {
            return $table[12];
        }

        $ligne = $maison
            ? match (true) {
                $periode === 1 => 5,
                $periode === 2 => 6,
                $periode <= 7 => 7,
                $periode === 8 => 8,
                default => 9,
            }
            : match (true) {
                $periode === 1 => 1,
                $periode === 2 => 2,
                $periode <= 8 => 3,
                default => 4,
            };
        return $table[$ligne];
    }

    /** §4 p.39 : isolation de PLUS de 50 % des surfaces murs/plafonds. */
    private function majoriteIsolee(DOMElement $logement, NodeAccessor $accessor): bool
    {
        $total = $isole = 0.0;
        $xpath = new \DOMXPath($logement->ownerDocument);
        foreach ($xpath->query('./enveloppe/mur_collection/mur | ./enveloppe/plancher_haut_collection/plancher_haut', $logement) as $paroi) {
            if (!$paroi instanceof DOMElement || !$this->estDeperditif($paroi, $accessor)) {
                continue;
            }
            $surface = max(0.0, $accessor->getFloatOrNull('./donnee_entree/surface_paroi_opaque', $paroi) ?? 0.0);
            $total += $surface;
            $type = $accessor->getIntOrNull('./donnee_entree/enum_type_isolation_id', $paroi);
            if ($type !== null && $type >= 3 && $type <= 9) {
                $isole += $surface;
            }
        }
        return $isole > 0.5 * $total;
    }

    /** §4 p.39 : joints sur PLUS de 50 % de la surface des menuiseries. */
    private function majoriteAvecJoints(DOMElement $logement, NodeAccessor $accessor): bool
    {
        $total = $avecJoints = 0.0;
        $xpath = new \DOMXPath($logement->ownerDocument);
        foreach ($xpath->query('./enveloppe/baie_vitree_collection/baie_vitree | ./enveloppe/porte_collection/porte', $logement) as $menuiserie) {
            if (!$menuiserie instanceof DOMElement || !$this->estDeperditif($menuiserie, $accessor)) {
                continue;
            }
            $champ = $menuiserie->nodeName === 'porte' ? 'surface_porte' : 'surface_totale_baie';
            $surface = max(0.0, $accessor->getFloatOrNull('./donnee_entree/' . $champ, $menuiserie) ?? 0.0);
            $total += $surface;
            if ($accessor->getIntOrNull('./donnee_entree/presence_joint', $menuiserie) === 1) {
                $avecJoints += $surface;
            }
        }
        return $avecJoints > 0.5 * $total;
    }

    private function estDeperditif(DOMElement $node, NodeAccessor $accessor): bool
    {
        return $accessor->getIntOrNull('./donnee_entree/enum_type_adjacence_id', $node) !== 22
            && ($accessor->getFloatOrNull('./donnee_intermediaire/b', $node) ?? 1.0) > 0.0;
    }
}
