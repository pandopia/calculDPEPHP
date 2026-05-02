<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\BaieVitree;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Coefficient Ug (vitrage seul) — §3.3.1.
 *
 * Calcul à partir des paramètres directs (`enum_type_vitrage_id`, `enum_type_gaz_lame_id`,
 * `vitrage_vir`, `enum_inclinaison_vitrage_id`, `epaisseur_lame`). N'utilise pas
 * `tv_ug_id` (mapping interne LICIEL).
 *
 * Cas spéciaux :
 *   - `enum_methode_saisie_perf_vitrage_id ∈ {3,5,7,9,11,13,15}` (Ug saisi justifié) →
 *     lecture de `ug_1` ou `ug_saisi` selon balise présente.
 *   - Survitrage : Ug = Ug_double_vitrage_air + 0.1 (lame plafonnée à 20 mm).
 *   - Brique de verre pleine/creuse, polycarbonate : Uw direct, l'écriture de Ug
 *     est conservatrice à partir du Uw spécifique.
 *
 * @spec-section 3.3.1
 * @spec-pages 23-25
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/03-parois-vitrees-portes/01-ug/00-calcul.md
 * @xml-input  baie_vitree.donnee_entree.{enum_type_vitrage_id, enum_type_gaz_lame_id, vitrage_vir, enum_inclinaison_vitrage_id, epaisseur_lame, ug_saisi, ug_1}
 * @xml-output baie_vitree.donnee_intermediaire.ug
 * @tables tv_ug
 */
final class UgCalculator implements CalculatorInterface
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
        return $node->nodeName === 'baie_vitree';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('baie_vitree sans <donnee_entree>.');
        }

        // Si le XML porte une valeur Ug saisie/pré-calculée (méthodes 3/5/7/9/11/13/15), l'utiliser.
        $direct = $accessor->getFloatOrNull('./ug_saisi', $entree)
               ?? $accessor->getFloatOrNull('./ug_1', $entree);
        if ($direct !== null) {
            $this->writeUg($node, $accessor, $direct);
            return;
        }

        $ug = $this->compute($entree, $accessor, $context);
        if ($ug !== null) {
            $this->writeUg($node, $accessor, $ug);
        }
    }

    private function compute(DOMElement $entree, NodeAccessor $accessor, CalculationContext $context): ?float
    {
        $typeVitrage   = $accessor->getIntOrNull('./enum_type_vitrage_id', $entree);
        $gaz           = $accessor->getIntOrNull('./enum_type_gaz_lame_id', $entree);
        $vir           = $accessor->getIntOrNull('./vitrage_vir', $entree);
        $inclinaison   = $accessor->getIntOrNull('./enum_inclinaison_vitrage_id', $entree);
        $epaisseur     = $accessor->getFloatOrNull('./epaisseur_lame', $entree);

        $table = $context->tables->load('enveloppe/tv_ug');

        // Cas spéciaux : brique de verre, polycarbonate (Uw direct, pas de Ug nécessaire)
        if (in_array($typeVitrage, [5, 6], true)) {
            return null; // Le UwCalculator gérera ces cas via le mapping spécial
        }

        $orient = $this->orientationFromInclinaison($inclinaison);

        if ($typeVitrage === 1) {
            // Simple vitrage
            return (float)$table['simple'][$orient];
        }

        if ($typeVitrage === 4) {
            // Survitrage : Ug = Ug_double_air + 0.1, lame ≤ 20mm
            $eClamped = min($epaisseur ?? 6.0, 20.0);
            $ugDouble = $this->lookupDoubleTriple($table, 'double', $orient, 'air', $vir === 1, $eClamped);
            return $ugDouble + 0.10;
        }

        if ($typeVitrage === 2 || $typeVitrage === 3) {
            $key = $typeVitrage === 2 ? 'double' : 'triple';
            $gazKey = $this->gazKey($gaz);
            return $this->lookupDoubleTriple($table, $key, $orient, $gazKey, $vir === 1, $epaisseur ?? 6.0);
        }

        return null;
    }

    private function orientationFromInclinaison(?int $inclinaison): string
    {
        // §3.3 : ≥75° = vertical, <75° = horizontal
        return match ($inclinaison) {
            1, 2    => 'horizontal', // <25° et 25°-75°
            3       => 'vertical',   // >75°
            4       => 'horizontal',
            default => 'vertical',
        };
    }

    private function gazKey(?int $gaz): string
    {
        // §3.3.1 : « par défaut, doubles et triples installés à partir de 2006 sont remplis Argon/Krypton »
        // Ici on suit la balise XML directement. 1=air, 2=argon/krypton, 3=inconnu (assimilé air).
        return $gaz === 2 ? 'argon' : 'air';
    }

    /**
     * @param array<string, mixed> $table
     */
    private function lookupDoubleTriple(array $table, string $kind, string $orient, string $gaz, bool $vir, float $epaisseur): float
    {
        $traitement = $vir ? 'vir' : 'standard';
        /** @var array<int, float> $epaisseursTable */
        $epaisseursTable = $table[$kind][$orient][$gaz][$traitement];

        // §3.3.1 attention : « si l'épaisseur n'est pas tabulée, prendre la valeur directement inférieure »
        $candidates = array_keys($epaisseursTable);
        sort($candidates);
        $best = $candidates[0];
        foreach ($candidates as $e) {
            if ($e <= $epaisseur) $best = $e;
        }
        return (float)$epaisseursTable[$best];
    }

    private function writeUg(DOMElement $node, NodeAccessor $accessor, float $ug): void
    {
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'ug', $ug);
    }
}
