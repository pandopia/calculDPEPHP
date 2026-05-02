<?php

declare(strict_types=1);

namespace CalculDpe\Sortie;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Inertie\InertieCalculator;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><confort_ete> : indicateur qualitatif.
 *
 * Algorithme (open3cl src/2021_04_13_confort_ete.js) :
 *   - isolation_toiture : 0 si un PH extérieur est non-isolé/inconnu, 1 sinon
 *   - aspect_traversant : 1 si les baies couvrent ≥ 2 orientations, 0 sinon
 *   - protection_solaire_exterieure : 0 si une baie non-nord manque de fermeture, 1 sinon
 *   - inertie_lourde : 1 si classe_inertie ∈ {lourde, très lourde}, 0 sinon
 *   - brasseur_air : 0 (pas de brasseur dans les cas conventionnels couverts)
 *   - nv_confort : "insuffisant" si protection=0 ou toiture=0 ;
 *                  "bon" si (inertie+traversant+brasseur) ≥ 2 ; "moyen" sinon
 *
 * @spec-section 10 (annexe qualitative — p.67-69)
 * @spec-source  resources/specsplitted/10-conso-froid/00-overview.md
 * @xml-input    enveloppe.{baie_vitree_collection, plancher_haut_collection}, inertie.enum_classe_inertie_id
 * @xml-output   sortie.confort_ete.{enum_indicateur_confort_ete_id, isolation_toiture, protection_solaire_exterieure, aspect_traversant, brasseur_air, inertie_lourde}
 * @depends-on   \CalculDpe\Inertie\InertieCalculator
 * @tables       (aucune)
 */
final class ConfortEteCalculator implements CalculatorInterface
{
    private const ENUM_INDICATEUR = [
        'insuffisant' => 1,
        'moyen'       => 2,
        'bon'         => 3,
    ];

    // enum_type_adjacence_id values for "extérieur"
    private const ADJACENCE_EXTERIEUR_ID = 1;

    // enum_type_isolation_id values considered as "non isolé" or "inconnu"
    private const ISOLATION_NON_ISOLE_IDS = [1, 2]; // 1=inconnu, 2=non isolé (à vérifier vs XSD)

    // enum_type_fermeture_id for "absence de fermeture"
    private const FERMETURE_ABSENCE_ID = 1;

    // enum_orientation_id for "nord"
    private const ORIENTATION_NORD_ID = 2;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [InertieCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // Classe d'inertie : stockée par InertieCalculator dans logement/donnee_intermediaire
        $inertieId = $accessor->getIntOrNull('donnee_intermediaire/enum_classe_inertie_id', $node);
        $inertieLourde = $this->resolveInertieLourde($inertieId);

        // Baies vitrées
        $baies = [];
        foreach ($node->getElementsByTagName('baie_vitree') as $bv) {
            $baies[] = $bv;
        }

        $aspectTraversant        = $this->resolveAspectTraversant($baies, $accessor);
        $protectionSolaireExt    = $this->resolveProtectionSolaire($baies, $accessor);

        // Planchers hauts (toiture)
        $planchers = [];
        foreach ($node->getElementsByTagName('plancher_haut') as $ph) {
            $planchers[] = $ph;
        }
        $isolationToiture = $this->resolveIsolationToiture($planchers, $accessor);

        $nvBon = $inertieLourde + $aspectTraversant + 0; // brasseur_air=0 conventionnellement
        if ($protectionSolaireExt === 0 || $isolationToiture === 0) {
            $niveau = 'insuffisant';
        } elseif ($nvBon >= 2) {
            $niveau = 'bon';
        } else {
            $niveau = 'moyen';
        }

        $enumId = self::ENUM_INDICATEUR[$niveau] ?? 2;

        // Écriture dans <sortie><confort_ete>
        $sortie     = $accessor->ensureSortie($node);
        $confortEte = $context->document->createElement('confort_ete');
        $sortie->appendChild($confortEte);

        $accessor->setChildValue($confortEte, 'enum_indicateur_confort_ete_id', $enumId);
        $accessor->setChildValue($confortEte, 'isolation_toiture',                $isolationToiture);
        $accessor->setChildValue($confortEte, 'protection_solaire_exterieure',    $protectionSolaireExt);
        $accessor->setChildValue($confortEte, 'aspect_traversant',               $aspectTraversant);
        $accessor->setChildValue($confortEte, 'brasseur_air',                    0);
        $accessor->setChildValue($confortEte, 'inertie_lourde',                  $inertieLourde);
    }

    private function resolveInertieLourde(?int $inertieId): int
    {
        // InertieCalculator: 1=légère, 2=moyenne, 3=lourde, 4=très lourde
        return ($inertieId !== null && $inertieId >= 3) ? 1 : 0;
    }

    /**
     * @param DOMElement[] $baies
     */
    private function resolveAspectTraversant(array $baies, NodeAccessor $accessor): int
    {
        $orientations = [];
        foreach ($baies as $bv) {
            $de = null;
            foreach ($bv->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                    $de = $child;
                    break;
                }
            }
            if ($de === null) {
                continue;
            }
            $orientId = $accessor->getIntOrNull('./enum_orientation_id', $de);
            if ($orientId !== null && !in_array($orientId, $orientations, true)) {
                $orientations[] = $orientId;
            }
        }
        return count($orientations) >= 2 ? 1 : 0;
    }

    /**
     * @param DOMElement[] $baies
     */
    private function resolveProtectionSolaire(array $baies, NodeAccessor $accessor): int
    {
        foreach ($baies as $bv) {
            $de = null;
            foreach ($bv->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                    $de = $child;
                    break;
                }
            }
            if ($de === null) {
                continue;
            }
            $orientId = $accessor->getIntOrNull('./enum_orientation_id', $de);
            if ($orientId === self::ORIENTATION_NORD_ID) {
                continue; // nord → ignoré
            }
            $fermetureId = $accessor->getIntOrNull('./enum_type_fermeture_id', $de);
            if ($fermetureId === self::FERMETURE_ABSENCE_ID) {
                return 0; // absence de fermeture → protection insuffisante
            }
        }
        return 1;
    }

    /**
     * @param DOMElement[] $planchers
     */
    private function resolveIsolationToiture(array $planchers, NodeAccessor $accessor): int
    {
        foreach ($planchers as $ph) {
            $de = null;
            foreach ($ph->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                    $de = $child;
                    break;
                }
            }
            if ($de === null) {
                continue;
            }
            $adjacenceId = $accessor->getIntOrNull('./enum_type_adjacence_id', $de);
            if ($adjacenceId !== self::ADJACENCE_EXTERIEUR_ID) {
                continue; // pas extérieur → non pertinent
            }
            $isolationId = $accessor->getIntOrNull('./enum_type_isolation_id', $de);
            if ($isolationId !== null && in_array($isolationId, self::ISOLATION_NON_ISOLE_IDS, true)) {
                return 0; // non isolé ou inconnu
            }
        }
        return 1;
    }
}
