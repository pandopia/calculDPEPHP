<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Le XSD définit `sortie_par_energie_collection` comme la décomposition de
 * `<sortie>` par type d'énergie : leur somme est l'agrégat par construction.
 * Quand les deux divergent, le fichier se contredit.
 */
final class AgregatEtVentilationTest extends TestCase
{
    /** @param list<string> $parEnergie */
    private function document(string $agregat, array $parEnergie): DOMDocument
    {
        $lignes = '';
        foreach ($parEnergie as $i => $valeur) {
            $lignes .= sprintf(
                '<sortie_par_energie><enum_type_energie_id>%d</enum_type_energie_id>'
                . '<emission_ges_ch>%s</emission_ges_ch></sortie_par_energie>',
                $i + 1,
                $valeur,
            );
        }

        $doc = new DOMDocument();
        $doc->loadXML(sprintf(
            '<dpe><logement><sortie><emission_ges><emission_ges_ch>%s</emission_ges_ch></emission_ges>'
            . '<sortie_par_energie_collection>%s</sortie_par_energie_collection></sortie></logement></dpe>',
            $agregat,
            $lignes,
        ));

        return $doc;
    }

    /** 2133E0741159K duplique ses lignes : la ventilation vaut le double. */
    public function testUneVentilationDoubleEstImputeeALaReference(): void
    {
        $suspects = ReferenceDefects::detect([], $this->document('1772', ['1772', '1772']));

        self::assertArrayHasKey('emission_ges_ch', $suspects);
        self::assertStringContainsString('3544', $suspects['emission_ges_ch']);
    }

    /** 2659E2596108U publie un agrégat positif sur une ventilation nulle. */
    public function testUneVentilationNulleSousUnAgregatPositifEstSuspecte(): void
    {
        self::assertArrayHasKey(
            'emission_ges_ch',
            ReferenceDefects::detect([], $this->document('245', ['0'])),
        );
    }

    /** Une ventilation qui somme bien son agrégat ne déclenche rien. */
    public function testUneVentilationCoherenteNEstPasSuspecte(): void
    {
        self::assertArrayNotHasKey(
            'emission_ges_ch',
            ReferenceDefects::detect([], $this->document('1772', ['1000', '772'])),
        );
    }

    /** L'arrondi du millième ne suffit pas à déclencher la règle. */
    public function testUnArrondiNEstPasUneContradiction(): void
    {
        self::assertArrayNotHasKey(
            'emission_ges_ch',
            ReferenceDefects::detect([], $this->document('1000', ['999.9'])),
        );
    }

    /** Sans ventilation publiée, la règle ne s'applique pas. */
    public function testSansVentilationRienNEstImpute(): void
    {
        self::assertArrayNotHasKey(
            'emission_ges_ch',
            ReferenceDefects::detect([], $this->document('1772', [])),
        );
    }
}
