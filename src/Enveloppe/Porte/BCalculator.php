<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\Porte;

use CalculDpePHP\Enveloppe\AbstractBCalculator;

/**
 * Coefficient de réduction des déperditions b pour une porte.
 *
 * @spec-section 3.1
 * @spec-pages 8-12
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/01-coef-reduction-b/00-detail.md
 * @xml-input  porte.donnee_entree.{tv_coef_reduction_deperdition_id, enum_type_adjacence_id}
 * @xml-output porte.donnee_intermediaire.b
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
        return ['porte'];
    }
}
