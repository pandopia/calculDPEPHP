<?php

declare(strict_types=1);

namespace Tests\Unit\Tables;

use CalculDpePHP\Tables\TableRepository;
use PHPUnit\Framework\TestCase;

/**
 * Les deux tables de §9.8 — DH14 et Text, par `ilpa`, altitude, mois et zone.
 *
 * `ilpa = 0` vient des tableaux de §18.2.1 à §18.2.3, `ilpa = 1` de ceux de
 * §18.3.1 à §18.3.3 (bâtiments à inertie lourde en parois anciennes, dont la
 * saison de chauffe est raccourcie).
 */
final class Dh14BaseAppointTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    /** @return array{array<mixed>, array<mixed>} */
    private function tables(): array
    {
        $repo = new TableRepository(self::PROJECT_ROOT . '/resources/tables');

        return [$repo->load('chauffage/dh14_base_appoint'), $repo->load('chauffage/text_mensuel_base_appoint')];
    }

    /**
     * Les deux tables doivent s'accorder sur la saison de chauffe : un mois
     * qui porte des degrés-heures porte une température, et réciproquement.
     *
     * `ilpa = 1`, H3, ≥ 800 m, octobre portait 1 822,0 — la valeur du tableau
     * `ilpa = 0` de §18.2.3 — alors que §18.3.3 y publie un tiret, comme pour
     * sa température. §9.8 traitait donc ce mois avec une température nulle,
     * et le garde de `CollectifBaseAppoint` ne peut plus l'écarter depuis
     * qu'il teste DH14 et non le signe de Text.
     */
    public function testLesDeuxTablesSAccordentSurLaSaisonDeChauffe(): void
    {
        [$dh14, $text] = $this->tables();

        $desaccords = [];
        foreach ([0, 1] as $ilpa) {
            for ($altitude = 1; $altitude <= 3; $altitude++) {
                for ($mois = 1; $mois <= 12; $mois++) {
                    for ($zone = 1; $zone <= 8; $zone++) {
                        $d = (float) ($dh14[$ilpa][$altitude][$mois][$zone] ?? 0.0);
                        $t = (float) ($text[$ilpa][$altitude][$mois][$zone] ?? 0.0);
                        if (($d > 0.0) !== ($t !== 0.0)) {
                            $desaccords[] = "ilpa=$ilpa alt=$altitude zone=$zone mois=$mois (DH14=$d, Text=$t)";
                        }
                    }
                }
            }
        }

        self::assertSame([], $desaccords, "Saison de chauffe incohérente :\n" . implode("\n", $desaccords));
    }

    /**
     * §18.2.3 et §18.3.3 ne publient aucune donnée pour H1a, H2a et H2b
     * au-dessus de 800 m : ces croisements sont vides dans l'annexe, et leur
     * vide n'est pas un trou de transcription. §9.8 n'y produit donc pas de
     * base — ce que TASK-K69 avait signalé comme suspect, à tort.
     */
    public function testLesCroisementsVidesSontCeuxDeLAnnexe(): void
    {
        [$dh14] = $this->tables();

        $vides = [];
        foreach ([0, 1] as $ilpa) {
            for ($altitude = 1; $altitude <= 3; $altitude++) {
                for ($zone = 1; $zone <= 8; $zone++) {
                    $somme = 0.0;
                    for ($mois = 1; $mois <= 12; $mois++) {
                        $somme += (float) ($dh14[$ilpa][$altitude][$mois][$zone] ?? 0.0);
                    }
                    if ($somme <= 0.0) {
                        $vides[] = "$ilpa/$altitude/$zone";
                    }
                }
            }
        }

        self::assertSame(
            ['0/3/1', '0/3/4', '0/3/5', '1/3/1', '1/3/4', '1/3/5'],
            $vides,
        );
    }

    /**
     * La température extérieure est une grandeur signée : §18.2.2 et §18.2.3
     * la tabulent négative en plein hiver dès 400 m. Une table qui n'en
     * porterait aucune aurait perdu les signes à l'extraction.
     */
    public function testLesTemperaturesNegativesDHiverSontConservees(): void
    {
        [, $text] = $this->tables();

        $negatives = 0;
        foreach ([0, 1] as $ilpa) {
            for ($altitude = 1; $altitude <= 3; $altitude++) {
                for ($mois = 1; $mois <= 12; $mois++) {
                    for ($zone = 1; $zone <= 8; $zone++) {
                        if ((float) ($text[$ilpa][$altitude][$mois][$zone] ?? 0.0) < 0.0) {
                            $negatives++;
                        }
                    }
                }
            }
        }

        self::assertGreaterThan(0, $negatives);
        self::assertEqualsWithDelta(-0.8, (float) $text[0][3][12][3], 1e-9, 'H1c ≥ 800 m en décembre');
    }
}
