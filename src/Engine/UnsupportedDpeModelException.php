<?php

declare(strict_types=1);

namespace CalculDpePHP\Engine;

use RuntimeException;

/**
 * Levée quand le XML d'entrée ne relève pas de la méthode 3CL-2021 logement
 * (enum_modele_dpe_id ≠ 1 : DPE neuf RT2012/RE2020, tertiaire — ou absence
 * de balise <logement>, ex. structure <logement_neuf>).
 *
 * @spec-section 1
 * @spec-pages 4
 */
final class UnsupportedDpeModelException extends RuntimeException
{
}
