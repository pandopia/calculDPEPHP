<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\PlancherHaut;

use CalculDpe\Enveloppe\AbstractBCalculator;

/**
 * Coefficient de réduction des déperditions b pour un plancher haut.
 *
 * @spec-section 3.1
 * @spec-pages 8-12
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/01-coef-reduction-b/00-detail.md
 * @xml-input  plancher_haut.donnee_entree.{tv_coef_reduction_deperdition_id, enum_type_adjacence_id}
 * @xml-output plancher_haut.donnee_intermediaire.b
 * @tables tv_coef_reduction_deperdition_id
 */
final class BCalculator extends AbstractBCalculator
{
    public function id(): string
    {
        return self::class;
    }

    protected function appliesToTags(): array
    {
        return ['plancher_haut'];
    }
}
