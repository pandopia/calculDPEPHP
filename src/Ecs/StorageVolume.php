<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs;

use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/** Volume réellement stocké, selon le type de production déclaré dans le XSD. */
final class StorageVolume
{
    public static function fromEntry(DOMElement $generator, NodeAccessor $accessor): float
    {
        // Type 1 = absence de stockage (production instantanée). Certains
        // anciens exports conservent un volume résiduel malgré cette saisie.
        // Il ne doit créer ni pertes de ballon ni dimensionnement à accumulation.
        if ($accessor->getIntOrNull('./donnee_entree/enum_type_stockage_ecs_id', $generator) === 1) {
            return 0.0;
        }
        return max(0.0, $accessor->getFloatOrNull('./donnee_entree/volume_stockage', $generator) ?? 0.0);
    }
}
