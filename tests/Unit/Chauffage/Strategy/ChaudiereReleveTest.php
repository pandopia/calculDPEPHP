<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Strategy;

use CalculDpePHP\Chauffage\Strategy\ChaudiereReleve;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ChaudiereReleveTest extends TestCase
{
    /** §9.7 p.65 : 60 % PAC, 15 % relève et 25 % insert, chacun via ses émissions. */
    #[DataProvider('ordresEtDimensionnements')]
    public function testChaqueGenerateurSuitSesEmissions(bool $inverse, float $rdim, int $typePac = 7): void
    {
        $emetteurs = [];
        foreach ([[1, 40, 0.90, 1, 0.92, 0.95], [1, 60, 0.85, 0.95, 0.92, 0.95], [2, 100, 0.84, 0.95, 1, 0.8]] as [$lien, $surface, $i0, $re, $rd, $rr]) {
            $emetteurs[] = "<emetteur_chauffage><donnee_entree><surface_chauffee>$surface</surface_chauffee><enum_lien_generateur_emetteur_id>$lien</enum_lien_generateur_emetteur_id></donnee_entree>"
                . "<donnee_intermediaire><i0>$i0</i0><rendement_emission>$re</rendement_emission><rendement_distribution>$rd</rendement_distribution><rendement_regulation>$rr</rendement_regulation></donnee_intermediaire></emetteur_chauffage>";
        }
        $generateurs = [];
        foreach ([[$typePac, 1, 3.2], [106, 1, 0.97], [43, 2, 0.75]] as [$type, $lien, $rg]) {
            $generateurs[] = "<generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>$type</enum_type_generateur_ch_id><enum_lien_generateur_emetteur_id>$lien</enum_lien_generateur_emetteur_id></donnee_entree>"
                . "<donnee_intermediaire><rendement_generation>$rg</rendement_generation></donnee_intermediaire></generateur_chauffage>";
        }
        $document = new DOMDocument();
        $document->loadXML('<logement><caracteristique_generale><hsp>2.5</hsp><surface_habitable_immeuble>100</surface_habitable_immeuble></caracteristique_generale>'
            . '<installation_chauffage_collection><installation_chauffage><donnee_entree><enum_cfg_installation_ch_id>9</enum_cfg_installation_ch_id><surface_chauffee>100</surface_chauffee><rdim>' . $rdim . '</rdim></donnee_entree>'
            . '<emetteur_chauffage_collection>' . implode('', $inverse ? array_reverse($emetteurs) : $emetteurs) . '</emetteur_chauffage_collection>'
            . '<generateur_chauffage_collection>' . implode('', $inverse ? array_reverse($generateurs) : $generateurs) . '</generateur_chauffage_collection>'
            . '</installation_chauffage></installation_chauffage_collection></logement>');
        $context = new CalculationContext($document, new TableRepository(__DIR__ . '/../../../../resources/tables'));
        $context->set('chauffage.besoin_ch', 1000.0);
        $context->set('chauffage.besoin_ch_depensier', 1200.0);
        $context->set('chauffage.gv', 250.0); // G=1, donc INT=I0.
        $installation = $document->getElementsByTagName('installation_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $installation);

        (new ChaudiereReleve())->calculate($installation, $context);

        $emissionPrincipale = 0.4 * 0.90 / (0.92 * 0.95) + 0.6 * 0.85 / (0.95 * 0.92 * 0.95);
        $attendus = [
            $typePac => 0.60 * 1000 * $emissionPrincipale / (3.2 * $rdim),
            106 => 0.15 * 1000 * $emissionPrincipale / (0.97 * $rdim),
            43 => 0.25 * 1000 * 0.84 / (0.75 * 0.95 * 0.8 * $rdim),
        ];
        $xpath = new DOMXPath($document);
        foreach ($attendus as $type => $attendu) {
            $generateur = $xpath->query('//generateur_chauffage[donnee_entree/enum_type_generateur_ch_id=' . $type . ']')->item(0);
            self::assertInstanceOf(DOMElement::class, $generateur);
            self::assertEqualsWithDelta($attendu, (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch)', $generateur), 1e-8);
            self::assertEqualsWithDelta(1.2 * $attendu, (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch_depensier)', $generateur), 1e-8);
        }
        self::assertEqualsWithDelta(array_sum($attendus), (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch)', $installation), 1e-8);
        self::assertEqualsWithDelta(1000 / $rdim, (float)$xpath->evaluate('string(donnee_intermediaire/besoin_ch)', $installation), 1e-8);
    }

    public static function ordresEtDimensionnements(): iterable
    {
        yield 'ordre habituel' => [false, 1.0];
        yield 'ordre inverse' => [true, 1.0];
        yield 'dimensionnement multiple' => [true, 4.0];
        yield 'autre thermodynamique électrique' => [false, 1.0, 117];
        yield 'autre thermodynamique gaz inversé' => [true, 4.0, 118];
    }

    /** Sans association exploitable, le repli sur les émissions de l'installation reste défini. */
    #[DataProvider('liensSansEmissionExploitable')]
    public function testLienSansEmetteurUtiliseLeRepli(string $lien, string $emetteurs): void
    {
        $document = new DOMDocument();
        $document->loadXML('<logement><installation_chauffage_collection><installation_chauffage>'
            . $emetteurs
            . '<generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>43</enum_type_generateur_ch_id>' . $lien . '</donnee_entree>'
            . '<donnee_intermediaire><rendement_generation>0.75</rendement_generation></donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection>'
            . '</installation_chauffage></installation_chauffage_collection></logement>');
        $context = new CalculationContext($document, new TableRepository(__DIR__ . '/../../../../resources/tables'));
        $context->set('chauffage.besoin_ch', 1000.0);
        $context->set('chauffage.gv', 2.5); // Sh=1, Hsp=2,5 : G=1.

        (new ChaudiereReleve())->calculate($document->getElementsByTagName('installation_chauffage')->item(0), $context);

        self::assertEqualsWithDelta(1000 * 0.25 / 0.75, (float)(new DOMXPath($document))->evaluate('string(//installation_chauffage/donnee_intermediaire/conso_ch)'), 1e-8);
    }

    public static function liensSansEmissionExploitable(): iterable
    {
        yield 'lien absent' => ['', ''];
        yield 'lien sans emetteur' => ['<enum_lien_generateur_emetteur_id>2</enum_lien_generateur_emetteur_id>', ''];
        yield 'surface nulle' => [
            '<enum_lien_generateur_emetteur_id>2</enum_lien_generateur_emetteur_id>',
            '<emetteur_chauffage_collection><emetteur_chauffage><donnee_entree><surface_chauffee>0</surface_chauffee><enum_lien_generateur_emetteur_id>2</enum_lien_generateur_emetteur_id></donnee_entree></emetteur_chauffage></emetteur_chauffage_collection>',
        ];
    }

    public function testInstallationSansGenerateurPublieUneConsommationNulle(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<logement><installation_chauffage><donnee_entree><enum_cfg_installation_ch_id>9</enum_cfg_installation_ch_id></donnee_entree></installation_chauffage></logement>');
        $context = new CalculationContext($document, new TableRepository(__DIR__ . '/../../../../resources/tables'));
        $context->set('chauffage.besoin_ch', 1000.0);
        $installation = $document->getElementsByTagName('installation_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $installation);
        $calculator = new ChaudiereReleve();
        self::assertTrue($calculator->appliesTo($installation));
        self::assertFalse($calculator->appliesTo($document->documentElement));
        self::assertContains(\CalculDpePHP\Chauffage\BesoinChauffageCalculator::class, $calculator->dependencies());

        $calculator->calculate($installation, $context);

        $xpath = new DOMXPath($document);
        self::assertSame('0', $xpath->evaluate('string(//installation_chauffage/donnee_intermediaire/conso_ch)'));
        self::assertSame('1000', $xpath->evaluate('string(//installation_chauffage/donnee_intermediaire/besoin_ch)'));
    }
}
