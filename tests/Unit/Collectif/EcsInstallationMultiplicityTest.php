<?php

declare(strict_types=1);

namespace Tests\Unit\Collectif;

use CalculDpePHP\Collectif\EcsInstallationMultiplicity;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class EcsInstallationMultiplicityTest extends TestCase
{
    /**
     * §17.1.3.2 : Shmoy = 6550/100 = 65.5 m² ; les groupes de 2620 et
     * 3930 m² représentent donc respectivement 40 et 60 logements moyens.
     */
    public function testSampledGroupsUseSurfaceBasedApartmentCounts(): void
    {
        $xml = <<<'XML'
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>6550</surface_habitable_immeuble>
        <nombre_appartement>100</nombre_appartement>
    </caracteristique_generale>
    <installation_ecs_collection>
        <installation_ecs><donnee_entree>
            <enum_type_installation_id>1</enum_type_installation_id>
            <enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
            <surface_habitable>2620</surface_habitable>
        </donnee_entree></installation_ecs>
        <installation_ecs><donnee_entree>
            <enum_type_installation_id>1</enum_type_installation_id>
            <enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
            <surface_habitable>3930</surface_habitable>
        </donnee_entree></installation_ecs>
    </installation_ecs_collection>
</logement>
XML;
        $document = new DOMDocument();
        $document->loadXML($xml);
        $accessor = new NodeAccessor($document);
        $installations = $document->getElementsByTagName('installation_ecs');

        $this->assertEqualsWithDelta(
            40.0,
            EcsInstallationMultiplicity::sampledOrNull($installations->item(0), $accessor),
            1e-9,
        );
        $this->assertEqualsWithDelta(
            60.0,
            EcsInstallationMultiplicity::sampledOrNull($installations->item(1), $accessor),
            1e-9,
        );
    }

    public function testSimpleInstallationDoesNotOverrideRdim(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<logement><installation_ecs><donnee_entree><enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id></donnee_entree></installation_ecs></logement>');
        $installation = $document->getElementsByTagName('installation_ecs')->item(0);

        $this->assertNull(EcsInstallationMultiplicity::sampledOrNull($installation, new NodeAccessor($document)));
    }
}
