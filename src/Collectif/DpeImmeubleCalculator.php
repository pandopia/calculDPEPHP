<?php

declare(strict_types=1);

namespace CalculDpePHP\Collectif;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Génération d'un DPE à l'immeuble collectif d'habitation (§17.1).
 *
 * S'applique quand enum_methode_application_dpe_log_id ∈ {26}.
 *
 * Tâches de post-traitement (run après VentilationAggregator et tous calculs ECS/CH) :
 *  — Zeroise conso_auxiliaire_ventilation dans chaque ventilation/donnee_intermediaire.
 *    La valeur correcte (calculée depuis pvent_moy × 8760/1000) a déjà été copiée
 *    dans sortie/ef_conso par VentilationAggregator avant que ce Calculator tourne.
 *
 * Note ECS : le scaling besoin_ecs × Nblgt est géré directement dans BesoinEcsCalculator
 * (nadeqTotal utilisé pour nadeqForInstall quand methode=26), ce qui permet à
 * ConsoEcsCalculator de lire le bon besoin depuis donnee_intermediaire.
 *
 * @spec-section 17.1
 * @spec-pages   106-111
 * @spec-source  resources/specsplitted/17-collectif/01-immeuble-collectif.md
 * @xml-input    logement.caracteristique_generale.{enum_methode_application_dpe_log_id, nombre_appartement}
 * @xml-input    logement.ventilation_collection.ventilation.donnee_intermediaire.conso_auxiliaire_ventilation
 * @xml-output   logement.ventilation_collection.ventilation.donnee_intermediaire.conso_auxiliaire_ventilation (→ 0)
 * @depends-on   \CalculDpePHP\Ventilation\VentilationAggregator, \CalculDpePHP\Sortie\SortieParEnergieAggregator
 * @tables       (aucune)
 */
final class DpeImmeubleCalculator implements CalculatorInterface
{
    /** Codes methode_application_dpe_log_id correspondant à un DPE immeuble collectif. */
    private const METHODE_IMMEUBLE_COLLECTIF = [26];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Ventilation\VentilationAggregator',
            '\CalculDpePHP\Sortie\SortieParEnergieAggregator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor  = new NodeAccessor($context->document);
        $methodeId = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $node);

        if (!in_array($methodeId, self::METHODE_IMMEUBLE_COLLECTIF, true)) {
            return;
        }

        // §17.1.7.2 : la conso_auxiliaire_ventilation est calculée à l'échelle de l'immeuble
        // et reportée dans sortie (déjà fait par VentilationAggregator depuis pvent_moy×8760/1000).
        // Dans donnee_intermediaire de chaque unité ventilation, la valeur doit être 0 car elle
        // n'est pas attribuée par unité de ventilation dans le DPE immeuble.
        $this->zeroVentilationAuxInDi($node, $accessor, $context);

        // Pour les DPE immeuble collectif, ADEME écrit production_pv=0/conso_elec_ac=0
        // dans production_elec_enr/donnee_intermediaire même en absence de PV.
        $this->ensureProductionPvZeros($node, $accessor, $context);
    }

    /**
     * Écrit production_pv=0, conso_elec_ac=0 dans production_elec_enr/donnee_intermediaire
     * quand l'immeuble n'a pas de PV (présence=0). ADEME écrit ces zéros explicitement
     * dans le format DPE immeuble collectif.
     */
    private function ensureProductionPvZeros(DOMElement $logement, NodeAccessor $accessor, CalculationContext $context): void
    {
        foreach ($logement->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->nodeName !== 'production_elec_enr') {
                continue;
            }
            $presence = $accessor->getIntOrNull('./donnee_entree/presence_production_pv', $child) ?? 0;
            if ($presence !== 1) {
                $di = $accessor->ensureDonneeIntermediaire($child);
                $accessor->setChildValue($di, 'production_pv',      0.0);
                $accessor->setChildValue($di, 'conso_elec_ac',      0.0);
                $accessor->setChildValue($di, 'taux_autoproduction', 0.0);
            }
        }
    }

    /**
     * Remet conso_auxiliaire_ventilation = 0 dans chaque ventilation/donnee_intermediaire.
     * VentilationAggregator a déjà propagé la valeur correcte vers sortie/ef_conso.
     */
    private function zeroVentilationAuxInDi(DOMElement $logement, NodeAccessor $accessor, CalculationContext $context): void
    {
        foreach ($logement->getElementsByTagName('ventilation') as $vent) {
            if (!$vent instanceof DOMElement) {
                continue;
            }
            $di = null;
            foreach ($vent->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'donnee_intermediaire') {
                    $di = $child;
                    break;
                }
            }
            if ($di !== null) {
                $accessor->setChildValue($di, 'conso_auxiliaire_ventilation', 0.0);
            }
        }
    }
}
