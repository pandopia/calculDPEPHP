<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\XsdVocabulary;
use PHPUnit\Framework\TestCase;

final class XsdVocabularyTest extends TestCase
{
    private const XSD = __DIR__ . '/../../../resources/ademe_DPE.xsd';

    public function testLeVocabulaireEstCharge(): void
    {
        $paths = XsdVocabulary::load(self::XSD);

        self::assertArrayHasKey('dpe/logement/sortie/ef_conso/conso_5_usages', $paths);
        self::assertArrayHasKey('dpe/logement/enveloppe/inertie/enum_classe_inertie_id', $paths);
    }

    /**
     * Le contrôle porte sur le chemin, pas sur le seul nom : une balise réelle
     * écrite au mauvais endroit doit être signalée (cf. TASK-K14).
     */
    public function testUneBaliseReelleAuMauvaisEndroitEstSignalee(): void
    {
        $unknown = XsdVocabulary::unknownElements(
            ['dpe/logement/donnee_intermediaire/enum_classe_inertie_id' => '3'],
            self::XSD,
        );

        self::assertSame(['dpe/logement/donnee_intermediaire/enum_classe_inertie_id'], $unknown);
    }

    public function testLeMemeNomAuBonEndroitEstAccepte(): void
    {
        $unknown = XsdVocabulary::unknownElements(
            ['dpe/logement/enveloppe/inertie/enum_classe_inertie_id' => '3'],
            self::XSD,
        );

        self::assertSame([], $unknown);
    }

    /**
     * Le XSD versionné est antérieur aux XML publiés : il ignore `numero_dpe`,
     * pourtant présent dans tous les fichiers de l'observatoire. Une balise vue
     * dans la référence doit donc être acceptée malgré le schéma.
     */
    public function testUneBaliseAbsenteDuXsdMaisPresenteDansLaReferenceEstAcceptee(): void
    {
        self::assertArrayNotHasKey('dpe/numero_dpe', XsdVocabulary::load(self::XSD));

        $unknown = XsdVocabulary::unknownElements(
            ['dpe/numero_dpe' => '2650E0036638H'],
            self::XSD,
            ['dpe/numero_dpe' => '2650E0036638H'],
        );

        self::assertSame([], $unknown);
    }

    public function testUneBaliseInventeeEstSignalee(): void
    {
        $unknown = XsdVocabulary::unknownElements([
            'dpe/logement/sortie/ef_conso/conso_5_usages' => '1',
            'dpe/logement/installation_ecs_collection/installation_ecs/generateur_ecs_collection/generateur_ecs/donnee_intermediaire/Qgw' => '0',
        ], self::XSD);

        self::assertSame(
            ['dpe/logement/installation_ecs_collection/installation_ecs/generateur_ecs_collection/generateur_ecs/donnee_intermediaire/Qgw'],
            $unknown,
        );
    }

    public function testLesIndexPositionnelsEtSemantiquesSontIgnores(): void
    {
        $unknown = XsdVocabulary::unknownElements([
            'dpe/logement/enveloppe/mur_collection/mur[3]/donnee_intermediaire/umur' => '1',
            'dpe/logement/sortie/sortie_par_energie_collection/sortie_par_energie[enum_type_energie_id=1]/conso_5_usages' => '1',
        ], self::XSD);

        self::assertSame([], $unknown);
    }

    public function testXsdAbsentNeSignaleRien(): void
    {
        // Sans vocabulaire de référence, le contrôle se désactive plutôt que
        // de déclarer inconnues toutes les balises du fichier.
        self::assertSame([], XsdVocabulary::unknownElements(['a/inconnue' => '1'], '/introuvable.xsd'));
    }
}
