<?php

declare(strict_types=1);

namespace CalculDpePHP\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Puissance moyenne des auxiliaires de ventilation Pventmoy — §5 p.41-42.
 *
 * Algorithme (selon type de bâtiment) :
 *
 * Immeuble collectif / Appartement (enum_methode_application_dpe_log_id ≠ 1) :
 *   Pventmoy = Pvent × Qvarepconv × Sh
 *   Pvent (W/(m³/h)) issu de `tv_debits_ventilation.pvent_immeuble`.
 *
 * Maison individuelle (enum_methode_application_dpe_log_id = 1) :
 *   Pventmoy = valeur forfaitaire fixe (W-ThC) issue de `tv_debits_ventilation.pvent_maison`.
 *
 * Ventilation hybride : Pvent déjà intègre le ratio du temps de fonctionnement mécanique
 *   (0,167 pour collectif, 0,083 pour individuel) — voir tv_debits_ventilation.
 *
 * @spec-section 5
 * @spec-pages 41-42
 * @spec-source resources/specsplitted/05-auxiliaires-ventilation/00-calcul.md
 * @xml-input  ventilation.donnee_entree.{enum_type_ventilation_id, surface_ventile}
 * @xml-input  ancestor::logement.caracteristique_generale.enum_methode_application_dpe_log_id
 * @xml-output ventilation.donnee_intermediaire.pvent_moy
 * @depends-on aucun
 * @tables tv_debits_ventilation
 */
final class PventMoyCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [];
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

        $typeId = $accessor->getIntOrNull('./enum_type_ventilation_id', $entree);
        $sh = $accessor->getFloatOrNull('./surface_ventile', $entree);
        if ($typeId === null || $sh === null) {
            return;
        }

        $debits = $context->tables->load('ventilation/tv_debits_ventilation');
        $row = $debits[$typeId] ?? null;
        if ($row === null) {
            return;
        }

        $pventMoy = $this->isMaison($node, $accessor)
            ? (float)$row['pvent_maison']
            : (float)$row['pvent_immeuble'] * (float)$row['qvarep'] * $sh;

        if ($pventMoy === 0.0) {
            return; // Ventilation naturelle — pas de moteur
        }

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'pvent_moy', $pventMoy);
    }

    /**
     * Détermine si le logement est une maison individuelle.
     * `enum_methode_application_dpe_log_id = 1` → "dpe maison individuelle".
     */
    private function isMaison(DOMElement $ventilation, NodeAccessor $accessor): bool
    {
        $logement = $ventilation->parentNode?->parentNode;
        if (!$logement instanceof DOMElement) {
            return false;
        }
        $methodeId = $accessor->getIntOrNull(
            './caracteristique_generale/enum_methode_application_dpe_log_id',
            $logement
        );
        return $methodeId === 1;
    }
}
