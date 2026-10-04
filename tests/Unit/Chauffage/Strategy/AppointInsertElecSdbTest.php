<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Strategy;

use CalculDpePHP\Chauffage\Strategy\AppointInsertElecSdb;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AppointInsertElecSdbTest extends TestCase
{
    /** §9.5 p.63-64 : Cchi = part_i × Bch × INTi / (Rgi × Rei × Rdi × Rri). */
    #[DataProvider('ordresEtDimensionnements')]
    public function testRepartitLesTroisBranchesAvecLeursPropresRendements(bool $inverse, float $rdim): void
    {
        [$doc, $installation, $context] = $this->installation($inverse, $rdim);
        (new AppointInsertElecSdb())->calculate($installation, $context);
        $xpath = new DOMXPath($doc);
        $consommations = [
            1 => 0.675 * 1000 * 0.77 / (0.97 * 0.99 * $rdim),
            2 => 0.225 * 1000 * 0.84 / (0.87 * 0.95 * 0.8 * $rdim),
            3 => 0.1 * 1000 * 0.84 / (0.97 * 0.99 * $rdim),
        ];
        foreach ($consommations as $lien => $attendu) {
            $gen = $xpath->query('//generateur_chauffage[donnee_entree/enum_lien_generateur_emetteur_id=' . $lien . ']')->item(0);
            self::assertInstanceOf(DOMElement::class, $gen);
            self::assertEqualsWithDelta($attendu, (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch)', $gen), 1e-8);
            self::assertEqualsWithDelta(1.2 * $attendu, (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch_depensier)', $gen), 1e-8);
        }
        self::assertEqualsWithDelta(array_sum($consommations), (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch)', $installation), 1e-8);
        self::assertEqualsWithDelta(1.2 * array_sum($consommations), (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch_depensier)', $installation), 1e-8);
        self::assertEqualsWithDelta(1000 / $rdim, (float)$xpath->evaluate('string(donnee_intermediaire/besoin_ch)', $installation), 1e-8);
    }

    public static function ordresEtDimensionnements(): iterable
    {
        yield 'ordre habituel' => [false, 1.0];
        yield 'générateurs et émetteurs inversés' => [true, 1.0];
        yield 'dimensionnement multiple' => [true, 4.0];
    }

    public function testConserveLesPartsParInstallationQuandIlYEnAPlusieurs(): void
    {
        [$doc, $installation, $context] = $this->installation(false, 1.0);
        $installation->parentNode->appendChild($installation->cloneNode(true));
        (new AppointInsertElecSdb())->calculate($installation, $context);
        self::assertSame(675.0, (float)(new DOMXPath($doc))->evaluate('string(donnee_intermediaire/besoin_ch)', $installation));
    }

    public function testConserveLeRepliQuandLesLiensNeSontPasRenseignes(): void
    {
        [$doc, $installation, $context] = $this->installation(false, 1.0);
        $xpath = new DOMXPath($doc);
        foreach (iterator_to_array($xpath->query('//enum_lien_generateur_emetteur_id')) as $lien) {
            $lien->parentNode->removeChild($lien);
        }
        (new AppointInsertElecSdb())->calculate($installation, $context);
        self::assertSame(675.0, (float)$xpath->evaluate('string(donnee_intermediaire/besoin_ch)', $installation));
    }

    /** @return array{DOMDocument, DOMElement, CalculationContext} */
    private function installation(bool $inverse, float $rdim): array
    {
        $emetteurs = $generateurs = [];
        foreach ([[1, 1, 0.97, 0.99, 0.77], [2, 0.87, 0.95, 0.8, 0.84], [3, 1, 0.97, 0.99, 0.84]] as [$lien, $rg, $re, $rr, $i0]) {
            $de = "<donnee_entree><surface_chauffee>100</surface_chauffee><enum_lien_generateur_emetteur_id>$lien</enum_lien_generateur_emetteur_id></donnee_entree>";
            $emetteurs[] = "<emetteur_chauffage>$de<donnee_intermediaire><i0>$i0</i0><rendement_emission>$re</rendement_emission><rendement_distribution>1</rendement_distribution><rendement_regulation>$rr</rendement_regulation></donnee_intermediaire></emetteur_chauffage>";
            $generateurs[] = "<generateur_chauffage>$de<donnee_intermediaire><rendement_generation>$rg</rendement_generation></donnee_intermediaire></generateur_chauffage>";
        }
        $em = implode('', $inverse ? array_reverse($emetteurs) : $emetteurs);
        $gen = implode('', $inverse ? array_reverse($generateurs) : $generateurs);
        $doc = new DOMDocument();
        $doc->loadXML(<<<XML
            <logement><caracteristique_generale><hsp>2.5</hsp><surface_habitable_logement>100</surface_habitable_logement></caracteristique_generale>
            <installation_chauffage_collection><installation_chauffage>
            <donnee_entree><enum_cfg_installation_ch_id>5</enum_cfg_installation_ch_id><surface_chauffee>100</surface_chauffee><rdim>$rdim</rdim></donnee_entree>
            <emetteur_chauffage_collection>$em</emetteur_chauffage_collection><generateur_chauffage_collection>$gen</generateur_chauffage_collection>
            </installation_chauffage></installation_chauffage_collection></logement>
        XML);
        $installation = $doc->getElementsByTagName('installation_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $installation);
        $context = new CalculationContext($doc, new TableRepository(__DIR__ . '/../../../../resources/tables'));
        $context->set('chauffage.besoin_ch', 1000.0);
        $context->set('chauffage.besoin_ch_depensier', 1200.0);
        $context->set('chauffage.gv', 250.0);
        return [$doc, $installation, $context];
    }
}
