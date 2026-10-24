<?php

declare(strict_types=1);

namespace Tests\Unit\Common;

use CalculDpePHP\Common\ProductionSolaireThermique;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class ProductionSolaireThermiqueTest extends TestCase
{
    public function testLaPublicationRemplaceUneInstallationSansCompterDeuxFois(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><installation_chauffage/><installation_chauffage/><installation_ecs/></logement>');
        $ctx = new CalculationContext($doc, new TableRepository(__DIR__ . '/../../../resources/tables'));
        self::assertSame(0.0, ProductionSolaireThermique::total($ctx));
        $installations = $doc->documentElement->childNodes;
        ProductionSolaireThermique::publier($ctx, $installations->item(0), 100.0);
        ProductionSolaireThermique::publier($ctx, $installations->item(1), 200.0);
        ProductionSolaireThermique::publier($ctx, $installations->item(2), 300.0);
        self::assertSame(600.0, ProductionSolaireThermique::total($ctx));
        ProductionSolaireThermique::publier($ctx, $installations->item(0), 150.0);
        self::assertSame(650.0, ProductionSolaireThermique::total($ctx));
        ProductionSolaireThermique::publier($ctx, $installations->item(2), 0.0);
        self::assertSame(350.0, ProductionSolaireThermique::total($ctx));
    }
}
