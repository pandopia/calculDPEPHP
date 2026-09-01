<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\TagFamily;
use PHPUnit\Framework\TestCase;

final class TagFamilyTest extends TestCase
{
    /** @dataProvider familleProvider */
    public function testClassementParChemin(string $path, string $attendu): void
    {
        self::assertSame($attendu, TagFamily::of($path));
    }

    public static function familleProvider(): array
    {
        $ch = 'dpe/logement/installation_chauffage_collection/installation_chauffage/';
        $ecs = 'dpe/logement/installation_ecs_collection/installation_ecs/';
        $sortie = 'dpe/logement/sortie/';

        return [
            // Le même nom de balise doit être imputé à la bonne installation.
            'rendement_generation côté chauffage' => [$ch . 'generateur_chauffage_collection/generateur_chauffage/donnee_intermediaire/rendement_generation', TagFamily::GENERATION_CH],
            'rendement_generation côté ECS'       => [$ecs . 'generateur_ecs_collection/generateur_ecs/donnee_intermediaire/rendement_generation', TagFamily::GENERATION_ECS],
            'cop côté chauffage'                  => [$ch . 'generateur_chauffage_collection/generateur_chauffage/donnee_intermediaire/cop', TagFamily::GENERATION_CH],
            'cop côté ECS'                        => [$ecs . 'generateur_ecs_collection/generateur_ecs/donnee_intermediaire/cop', TagFamily::GENERATION_ECS],

            'enveloppe'   => ['dpe/logement/enveloppe/mur_collection/mur[2]/donnee_intermediaire/umur', TagFamily::ENVELOPPE],
            'ventilation' => ['dpe/logement/ventilation_collection/ventilation/donnee_intermediaire/hperm', TagFamily::VENTILATION],
            'apports'     => [$sortie . 'apport_et_besoin/apport_solaire_ch', TagFamily::APPORTS],
            'besoin ch'   => [$ch . 'donnee_intermediaire/besoin_ch', TagFamily::BESOIN_CH],
            'besoin ecs'  => [$ecs . 'donnee_intermediaire/besoin_ecs_depensier', TagFamily::BESOIN_ECS],

            // Les auxiliaires ne doivent pas retomber dans EF/EP/GES/coûts.
            'aux génération ch'  => [$sortie . 'ef_conso/conso_auxiliaire_generation_ch', TagFamily::AUXILIAIRES],
            'aux EP'             => [$sortie . 'ep_conso/ep_conso_auxiliaire_ventilation', TagFamily::AUXILIAIRES],
            // …sauf ceux dont le suffixe d'usage est le froid.
            'aux distribution fr' => [$sortie . 'ef_conso/conso_auxiliaire_distribution_fr', TagFamily::FROID],

            'froid'   => [$sortie . 'ef_conso/conso_fr_depensier', TagFamily::FROID],
            'pv'      => ['dpe/logement/production_elec_enr/donnee_intermediaire/production_pv', TagFamily::PV],
            'ef'      => [$sortie . 'ef_conso/conso_5_usages', TagFamily::SORTIES_EF],
            'ep'      => [$sortie . 'ep_conso/ep_conso_5_usages_m2', TagFamily::SORTIES_EP],
            'classe dpe' => [$sortie . 'ep_conso/classe_bilan_dpe', TagFamily::SORTIES_EP],
            'ges'     => [$sortie . 'emission_ges/emission_ges_5_usages', TagFamily::GES],
            'classe ges' => [$sortie . 'emission_ges/classe_emission_ges', TagFamily::GES],
            'cout'    => [$sortie . 'cout/cout_5_usages', TagFamily::COUT],
            'confort' => [$sortie . 'confort_ete/brasseur_air', TagFamily::CONFORT_ETE],

            // Consommations remontées au niveau <sortie> : rattachées au poste.
            'conso_ecs en sortie' => [$sortie . 'ef_conso/conso_ecs', TagFamily::GENERATION_ECS],
            'conso_ch en sortie'  => [$sortie . 'ef_conso/conso_ch_depensier', TagFamily::GENERATION_CH],

            'inconnu' => ['dpe/logement/donnee_intermediaire/balise_inventee', TagFamily::AUTRE],
        ];
    }

    public function testLeafRetireLIndexPositionnel(): void
    {
        self::assertSame('umur', TagFamily::leaf('dpe/logement/enveloppe/mur_collection/mur[7]/donnee_intermediaire/umur'));
        self::assertSame('conso_5_usages', TagFamily::leaf('a/b/sortie_par_energie[enum_type_energie_id=1]/conso_5_usages'));
    }

    public function testToutesLesFamillesSontDansLOrdreDAffichage(): void
    {
        $reflection = new \ReflectionClass(TagFamily::class);
        foreach ($reflection->getConstants() as $name => $value) {
            if ($name === 'ORDER' || !is_string($value)) {
                continue;
            }
            self::assertContains($value, TagFamily::ORDER, "Famille $name absente de ORDER");
        }
    }
}
