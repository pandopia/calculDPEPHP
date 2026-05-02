<?php

declare(strict_types=1);

namespace CalculDpePHP\Engine;

use CalculDpePHP\Tables\TableRepository;

final class DefaultDpeEngineFactory
{
    public static function create(?string $projectRoot = null): DpeEngine
    {
        $projectRoot ??= dirname(__DIR__, 2);

        return new DpeEngine(
            self::buildPipeline(),
            new TableRepository($projectRoot . '/resources/tables'),
        );
    }

    private static function buildPipeline(): CalculatorPipeline
    {
        $pipeline = new CalculatorPipeline();

        $pipeline->add(new \CalculDpePHP\Enveloppe\Mur\BCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\PlancherBas\BCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\PlancherHaut\BCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\BaieVitree\BCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\Porte\BCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\Mur\Umur0Calculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\Mur\UmurCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\PlancherBas\Upb0Calculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\PlancherBas\UpbCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\PlancherBas\UpbFinalCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\PlancherHaut\Uph0Calculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\PlancherHaut\UphCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\BaieVitree\UgCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\BaieVitree\UwCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\BaieVitree\UjnCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\BaieVitree\UMenuiserieCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\BaieVitree\SwCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\BaieVitree\Fe1Calculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\BaieVitree\Fe2Calculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\Porte\UporteCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\PontThermique\KCalculator());
        $pipeline->add(new \CalculDpePHP\Enveloppe\EnveloppeAggregator());
        $pipeline->add(new \CalculDpePHP\Ventilation\Q4PaConvCalculator());
        $pipeline->add(new \CalculDpePHP\Ventilation\HventCalculator());
        $pipeline->add(new \CalculDpePHP\Ventilation\HpermCalculator());
        $pipeline->add(new \CalculDpePHP\Ventilation\PventMoyCalculator());
        $pipeline->add(new \CalculDpePHP\Ventilation\ConsoAuxiliaireVentilationCalculator());
        $pipeline->add(new \CalculDpePHP\Ventilation\VentilationAggregator());
        $pipeline->add(new \CalculDpePHP\Sortie\DeperditionCalculator());
        $pipeline->add(new \CalculDpePHP\Inertie\InertieCalculator());
        $pipeline->add(new \CalculDpePHP\Intermittence\IntermittenceCalculator());
        $pipeline->add(new \CalculDpePHP\Apport\SurfaceSudEquivalenteCalculator());
        $pipeline->add(new \CalculDpePHP\Apport\EspaceTamponSolariseCalculator());
        $pipeline->add(new \CalculDpePHP\Apport\FCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\BesoinChauffageCalculator());
        $pipeline->add(new \CalculDpePHP\Froid\BesoinAnnuelCalculator());
        $pipeline->add(new \CalculDpePHP\Froid\ConsoFroidCalculator());
        $pipeline->add(new \CalculDpePHP\Ecs\BesoinEcsCalculator());
        $pipeline->add(new \CalculDpePHP\Ecs\Rendement\DistributionCalculator());
        $pipeline->add(new \CalculDpePHP\Ecs\Rendement\StockageCalculator());
        $pipeline->add(new \CalculDpePHP\Ecs\Rendement\CombustionCalculator());
        $pipeline->add(new \CalculDpePHP\Ecs\Rendement\CetAccumulationCalculator());
        $pipeline->add(new \CalculDpePHP\Ecs\Rendement\ReseauChaleurCalculator());
        $pipeline->add(new \CalculDpePHP\Ecs\ConsoEcsCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Rendement\EmissionCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Rendement\DistributionCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Rendement\RegulationCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Rendement\Combustion\InsertsPoelesCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereProfilChargeCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\InstallationClassique());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\MultiGenerateurs());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\InsertPoeleAppoint());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\InsertElecSdb());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\AppointInsertElecSdb());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\ChaudiereReleve());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\ConvecteurBijonction());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\ChauffageSolaire());
        $pipeline->add(new \CalculDpePHP\Chauffage\Strategy\SolaireInsertPoele());
        $pipeline->add(new \CalculDpePHP\Auxiliaire\AuxGenerationCalculator());
        $pipeline->add(new \CalculDpePHP\Auxiliaire\AuxDistributionCalculator());
        $pipeline->add(new \CalculDpePHP\Eclairage\ConsoEclairageCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\EfConsoCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\EpConsoCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\EmissionGesCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\QualiteIsolationCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\ConfortEteCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\CoutCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\SortieParEnergieAggregator());
        $pipeline->add(new \CalculDpePHP\Collectif\DpeImmeubleCalculator());
        $pipeline->add(new \CalculDpePHP\Collectif\DpeAppartementCalculator());
        $pipeline->add(new \CalculDpePHP\Collectif\ChauffageMultiImmeubleCalculator());
        $pipeline->add(new \CalculDpePHP\Collectif\ImmeubleMixteCalculator());
        $pipeline->add(new \CalculDpePHP\ProductionElec\ProductionPvCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\ProductionElectriciteCalculator());
        $pipeline->add(new \CalculDpePHP\Sortie\ApportEtBesoinCalculator());

        return $pipeline;
    }
}
