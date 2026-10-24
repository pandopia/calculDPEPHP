<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement de distribution Rd de l'installation de chauffage (§12.2 p.76).
 *
 * Le type de réseau est encodé dans enum_type_emission_distribution_id.
 * L'état d'isolation est fourni par reseau_distribution_isole (0=non, 1=oui).
 * Pour les fluides frigorigènes et les émetteurs sans réseau : Rd=1.
 * Le type générique 41 ne décrit pas le réseau : seul dans ce cas, la ligne
 * publique ADEME fournit sa nature, sans requalifier le type d'émission.
 *
 * @spec-section 12.2
 * @spec-pages   76
 * @spec-source  resources/specsplitted/12-rendements-installations/02-distribution.md
 * @spec-source  https://gitlab.com/observatoire-dpe/observatoire-dpe/-/blob/52792fb9e79e52390ded7d4527a7f7a962fd5ccc/modele_donnee/valeur_tables.xlsx (rendement_distribution_ch A2:E13)
 * @xml-input    emetteur_chauffage.donnee_entree.{enum_type_emission_distribution_id, reseau_distribution_isole, tv_rendement_distribution_ch_id}
 * @xml-output   emetteur_chauffage.donnee_intermediaire.rendement_distribution
 * @depends-on   (aucun)
 * @tables       chauffage/tv_rendement_distribution_ch, chauffage/tv_type_reseau_distribution_ch
 */
final class DistributionCalculator implements CalculatorInterface
{
    private const DEFAULT_RD = 1.00;

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
        return $node->nodeName === 'emetteur_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $emId  = $accessor->getIntOrNull('./donnee_entree/enum_type_emission_distribution_id', $node);
        $isolation = $accessor->getIntOrNull('./donnee_entree/reseau_distribution_isole', $node);
        if ($emId === 41) {
            $tvId = $accessor->getIntOrNull('./donnee_entree/tv_rendement_distribution_ch_id', $node);
            $reseaux = $context->tables->load('chauffage/tv_type_reseau_distribution_ch');
            $reseau = $tvId !== null ? ($reseaux[$tvId] ?? null) : null;
            $emId = $reseau['emetteur_reseau'] ?? $emId;
            // Une isolation directement déclarée reste prioritaire. La ligne
            // ADEME ne la supplée que lorsqu'elle est absente ou nil.
            $isolation ??= isset($reseau['isole']) ? (int)$reseau['isole'] : null;
        }
        $isole = $isolation === 1;

        $table = $context->tables->load('chauffage/tv_rendement_distribution_ch');
        if ($emId === null || !isset($table[$emId])) {
            $rd = self::DEFAULT_RD;
        } else {
            $row = $table[$emId];
            $rd  = $isole ? (float)$row['isole'] : (float)$row['non_isole'];
        }

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_distribution', $rd);
    }
}
