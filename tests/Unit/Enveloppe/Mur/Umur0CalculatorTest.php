<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\Mur;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\Mur\Umur0Calculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Umur0CalculatorTest extends TestCase
{
    private function umur0(string $entree): ?float
    {
        $document = new DOMDocument();
        $document->loadXML('<logement><mur><donnee_entree>' . $entree . '</donnee_entree></mur></logement>');
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(__DIR__ . '/../../../../resources/tables'),
            zoneGroupe: 'H1',
            energieChauffagePrincipale: 'autres',
            periodeConstructionId: 1,
        );
        $mur = $document->getElementsByTagName('mur')->item(0);
        self::assertInstanceOf(DOMElement::class, $mur);

        (new Umur0Calculator())->calculate($mur, $context);

        $node = $document->getElementsByTagName('umur0')->item(0);

        return $node === null ? null : (float) $node->textContent;
    }

    private static function table(int $materiau, ?float $epaisseur, int $doublage = 2): string
    {
        $xml = '<enum_methode_saisie_u0_id>2</enum_methode_saisie_u0_id>'
            . '<enum_materiaux_structure_mur_id>' . $materiau . '</enum_materiaux_structure_mur_id>'
            . '<enum_type_doublage_id>' . $doublage . '</enum_type_doublage_id>';
        if ($epaisseur !== null) {
            $xml .= '<epaisseur_structure>' . $epaisseur . '</epaisseur_structure>';
        }

        return $xml;
    }

    /**
     * Les en-têtes de colonnes §3.2.1.2 sont des épaisseurs tabulées discrètes :
     * une épaisseur intermédiaire retient le point tabulé inférieur.
     *
     * Les attendus intègrent le plafond Umur_nu = Min(Umur0 ; 2,5) du schéma
     * §3.2.1.1, appliqué à la valeur écrite dans `<umur0>`.
     *
     * @return iterable<string, array{int, float, float}>
     */
    public static function epaisseurs(): iterable
    {
        // Briques pleines simples p.14 : … | 19 | 23 | 28 | 34 | 45 | … | 70
        yield 'épaisseur tabulée lue telle quelle' => [8, 23.0, 2.50];
        yield 'épaisseur intermédiaire ramenée au point inférieur' => [8, 24.0, 2.50];
        yield 'épaisseur juste sous un point ne remonte pas' => [8, 27.9, 2.50];
        yield 'point tabulé suivant atteint exactement' => [8, 28.0, 2.25];
        yield 'épaisseur entre deux points' => [8, 30.0, 2.25];
        yield 'épaisseur sous le premier point retient la colonne « ≤ N »' => [8, 5.0, 2.50];
        yield 'dernier point tabulé atteint' => [8, 70.0, 1.20];
        yield 'épaisseur au-delà du dernier point retient la colonne « ≥ N »' => [8, 75.0, 1.20];
        // Béton banché p.15 : ≤20 | 22,5 | 25 | 28 | 30 | 35 | 40 | ≥45
        yield 'point tabulé décimal' => [13, 30.0, 2.40];
        yield 'épaisseur entre deux points, dont un décimal' => [13, 34.9, 2.40];
        yield 'colonne « ≥ 45 » du béton banché' => [13, 45.0, 1.90];
        // Béton de mâchefer p.15 : la colonne « ≥ 45 » est vide, 40 est le dernier point.
        yield 'dernier point tabulé sans colonne « ≥ N »' => [14, 60.0, 1.80];
    }

    #[DataProvider('epaisseurs')]
    public function testEpaisseurRetientLePointTabuleInferieur(int $materiau, float $epaisseur, float $expected): void
    {
        self::assertSame($expected, $this->umur0(self::table($materiau, $epaisseur)));
    }

    /** Une épaisseur > 80 cm est en réalité sérialisée en mm par certains logiciels. */
    public function testEpaisseurEnMillimetresEstRamenee(): void
    {
        self::assertSame(2.50, $this->umur0(self::table(8, 240.0)));
    }

    /** Matériaux sans colonne d'épaisseur : valeur unique quelle que soit l'épaisseur. */
    public function testMateriauSansEpaisseurTabulee(): void
    {
        self::assertSame(1.70, $this->umur0(self::table(6, 15.0)));
        self::assertSame(1.70, $this->umur0(self::table(27, null)));
    }

    /** Cloison de plâtre §3.2.1.2 : 3,33 W/(m².K), plafonné à Umur_nu = 2,5. */
    public function testCloisonDePlatre(): void
    {
        self::assertSame(2.50, $this->umur0(self::table(20, null)));
    }

    /** Matériau inconnu ou « autres » : forfait 2,5. */
    public function testMateriauxForfaitaires(): void
    {
        foreach ([1, 21, 22, 23] as $materiau) {
            self::assertSame(2.50, $this->umur0(self::table($materiau, 30.0)));
        }
    }

    /** Méthode 1 : type de paroi inconnu → 2,5 sans lecture de table. */
    public function testTypeDeParoiInconnu(): void
    {
        self::assertSame(2.50, $this->umur0(
            '<enum_methode_saisie_u0_id>1</enum_methode_saisie_u0_id>'
            . '<enum_materiaux_structure_mur_id>8</enum_materiaux_structure_mur_id>'
            . '<enum_type_doublage_id>2</enum_type_doublage_id>'
        ));
    }

    /** Méthodes 3 et 4 : la valeur saisie prime ; sans balise, forfait 2,5. */
    public function testSaisieDirecte(): void
    {
        self::assertSame(1.42, $this->umur0(
            '<enum_methode_saisie_u0_id>3</enum_methode_saisie_u0_id>'
            . '<enum_materiaux_structure_mur_id>8</enum_materiaux_structure_mur_id>'
            . '<enum_type_doublage_id>2</enum_type_doublage_id>'
            . '<umur0_saisi>1.42</umur0_saisi>'
        ));
        self::assertSame(2.50, $this->umur0(
            '<enum_methode_saisie_u0_id>4</enum_methode_saisie_u0_id>'
            . '<enum_materiaux_structure_mur_id>8</enum_materiaux_structure_mur_id>'
            . '<enum_type_doublage_id>2</enum_type_doublage_id>'
        ));
    }

    /** Méthode 5 : U saisi directement, Umur0 n'est pas calculé. */
    public function testMethode5NeProduitPasUmur0(): void
    {
        self::assertNull($this->umur0(
            '<enum_methode_saisie_u0_id>5</enum_methode_saisie_u0_id>'
            . '<enum_materiaux_structure_mur_id>8</enum_materiaux_structure_mur_id>'
        ));
    }

    /**
     * Doublage §3.2.1.2 : R = 0,10 (lame < 15 mm) ou 0,21 (lame ≥ 15 mm, doublage connu).
     *
     * @return iterable<string, array{int, float}>
     */
    public static function doublages(): iterable
    {
        yield 'absence de doublage' => [2, 2.00];
        yield 'type inconnu, pas de résistance' => [1, 2.00];
        yield 'lame d\'air < 15 mm' => [3, 1.0 / (1.0 / 2.00 + 0.10)];
        yield 'lame d\'air ≥ 15 mm' => [4, 1.0 / (1.0 / 2.00 + 0.21)];
        yield 'doublage connu' => [5, 1.0 / (1.0 / 2.00 + 0.21)];
    }

    #[DataProvider('doublages')]
    public function testDoublage(int $doublage, float $expected): void
    {
        // Briques creuses 20 cm → Umur0 = 2,00 avant doublage.
        self::assertEqualsWithDelta($expected, $this->umur0(self::table(10, 20.0, $doublage)), 1e-9);
    }

    /** Enduit isolant de paroi ancienne : Umur0 = 1 / (1/Umur0 + 0,7). */
    public function testEnduitIsolantParoiAncienne(): void
    {
        $expected = 1.0 / (1.0 / 3.20 + 0.7);
        self::assertEqualsWithDelta($expected, $this->umur0(
            self::table(2, 20.0) . '<enduit_isolant_paroi_ancienne>1</enduit_isolant_paroi_ancienne>'
        ), 1e-9);
    }
}
