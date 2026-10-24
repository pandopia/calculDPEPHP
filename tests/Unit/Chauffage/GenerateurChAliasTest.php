<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage;

use CalculDpePHP\Chauffage\GenerateurChAlias;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

final class GenerateurChAliasTest extends TestCase
{
    public function testLesPacRecentesGardentLeurFamilleSansPerdreLeurNatureHybride(): void
    {
        foreach ([172 => 3, 173 => 7, 174 => 11, 175 => 15, 176 => 19, 178 => 7, 179 => 11, 180 => 15, 181 => 19] as $type => $family) {
            self::assertSame($family, GenerateurChAlias::normalize($type));
            self::assertSame($type >= 178, GenerateurChAlias::isHybride($type));
            self::assertSame($type >= 178, GenerateurChAlias::isHybridePac($type));
        }
        self::assertFalse(GenerateurChAlias::isHybride(171));
        self::assertFalse(GenerateurChAlias::isHybridePac(177));
        foreach ([177 => false, 182 => true, 183 => false, 184 => true] as $type => $hybrid) {
            self::assertSame(74, GenerateurChAlias::normalize($type));
            self::assertSame($hybrid, GenerateurChAlias::isHybride($type));
            self::assertFalse(GenerateurChAlias::isHybridePac($type));
        }
    }

    private function generateur(int $genId, string $caracteristiqueGenerale): DOMElement
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        $caracteristiqueGenerale
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_generateur_ch_id>$genId</enum_type_generateur_ch_id>
                    </donnee_entree>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
</logement>
XML);
        $gen = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $gen);
        return $gen;
    }

    /**
     * §17.2.1.1 p.129 : le système collectif par défaut est « une chaudière
     * atmosphérique mixte standard datant de la construction du bâtiment », au
     * fioul. L'enum 119 renvoie donc à la chaudière fioul de cette période.
     */
    public function testLeSystemeCollectifParDefautSuitLAnneeDeConstruction(): void
    {
        $node = $this->generateur(119, '<annee_construction>1998</annee_construction>');
        self::assertSame(79, GenerateurChAlias::normalizeNode(119, $node));
    }

    /**
     * Sans `annee_construction`, la période déclarée fait foi : 1948-1974
     * commence en 1948, donc une chaudière fioul d'avant 1970.
     */
    public function testLaPeriodeDeConstructionSertDeReplyAnneeInconnue(): void
    {
        $node = $this->generateur(119, '<enum_periode_construction_id>2</enum_periode_construction_id>');
        self::assertSame(75, GenerateurChAlias::normalizeNode(119, $node));
    }

    /**
     * Aucune information sur la construction : la période la plus ancienne,
     * conformément au qualificatif « pénalisante » porté par l'enum.
     */
    public function testSansAucuneDateLaChaudiereLaPlusPenalisanteEstRetenue(): void
    {
        $node = $this->generateur(119, '');
        self::assertSame(75, GenerateurChAlias::normalizeNode(119, $node));
    }

    /**
     * Le barème de construction ne doit pas déborder sur les « autres systèmes
     * à combustion », qui suivent l'année d'installation du générateur.
     */
    public function testLesAutresSystemesACombustionRestentSurLAnneeDInstallation(): void
    {
        $node = $this->generateur(114, '<annee_construction>1998</annee_construction>');
        self::assertSame(75, GenerateurChAlias::normalizeNode(114, $node));
    }

    public function testUnEnumSansAliasEstInchange(): void
    {
        $node = $this->generateur(96, '<annee_construction>1998</annee_construction>');
        self::assertSame(96, GenerateurChAlias::normalizeNode(96, $node));
    }
}
