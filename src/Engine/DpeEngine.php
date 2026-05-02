<?php

declare(strict_types=1);

namespace CalculDpe\Engine;

use CalculDpe\Common\Period;
use CalculDpe\Tables\TableRepository;
use CalculDpe\Xml\NodeAccessor;
use CalculDpe\Xml\XmlReader;
use CalculDpe\Xml\XmlWriter;
use CalculDpe\XmlSanitizer;
use DOMDocument;

/**
 * Moteur de calcul DPE 3CL-2021.
 *
 * Workflow :
 *   1. Charge le XML d'entrée.
 *   2. Purge les balises `<donnee_intermediaire>` et `<sortie>` existantes
 *      (idempotence : on peut relancer sur un fichier déjà calculé).
 *   3. Construit un CalculationContext (zone climatique, altitude, période…).
 *   4. Exécute la pipeline de Calculators dans l'ordre topologique.
 *   5. Sauvegarde le DOM enrichi.
 */
final class DpeEngine
{
    public function __construct(
        private readonly CalculatorPipeline $pipeline,
        private readonly TableRepository $tables,
    ) {}

    public function run(string $inputFile, string $outputFile): void
    {
        $document = (new XmlReader())->load($inputFile);

        // Idempotence : on retire d'abord toute donnée intermédiaire / sortie pré-existante
        $this->purgeOutputs($document);

        $context = $this->buildContext($document);

        $this->pipeline->run($document, $context);

        (new XmlWriter())->save($document, $outputFile);
    }

    private function purgeOutputs(DOMDocument $document): void
    {
        foreach (['donnee_intermediaire', 'sortie'] as $tag) {
            $nodes = [];
            foreach ($document->getElementsByTagName($tag) as $n) {
                $nodes[] = $n;
            }
            foreach ($nodes as $n) {
                if ($n->parentNode !== null) {
                    $n->parentNode->removeChild($n);
                }
            }
        }
    }

    private function buildContext(DOMDocument $document): CalculationContext
    {
        $accessor = new NodeAccessor($document);

        $zone = $accessor->getEnumString('//meteo/enum_zone_climatique_id');
        $alt  = $accessor->getEnumString('//meteo/enum_classe_altitude_id');
        $sh   = $accessor->getFloatOrNull('//caracteristique_generale/surface_habitable_logement');
        $periodeConstrId = $accessor->getIntOrNull('//caracteristique_generale/enum_periode_construction_id');

        $period = $this->detectPeriod($document);

        // Énergie principale du chauffage : prend la première installation, premier générateur.
        // Si type_energie_id == 1 (électricité) → "joule", sinon "autres".
        // Heuristique : suffisante pour les exemples actuels ; à raffiner si plusieurs générateurs
        // de natures différentes apparaissent.
        $energieGenId = $accessor->getIntOrNull('(//generateur_chauffage/donnee_entree/enum_type_energie_id)[1]');
        $energieChauffage = match ($energieGenId) {
            1       => 'joule',                   // électricité (effet Joule, PAC, convecteur)
            null    => null,
            default => 'autres',                  // gaz, fioul, bois, réseau chaleur, etc.
        };

        return new CalculationContext(
            document: $document,
            tables: $this->tables,
            zoneClimatique: $zone,
            classeAltitude: $alt,
            surfaceHabitable: $sh,
            period: $period,
            zoneGroupe: CalculationContext::zoneGroupeFromId($zone),
            energieChauffagePrincipale: $energieChauffage,
            periodeConstructionId: $periodeConstrId,
        );
    }

    private function detectPeriod(DOMDocument $document): ?Period
    {
        $accessor = new NodeAccessor($document);
        // enum_version_id ≥ 2.6 → post-2026 coef élec (fep=1.9), < 2.6 → pré-2026 (fep=2.3)
        $versionId = $accessor->getStringOrNull('//enum_version_id') ?? '';
        if ($versionId !== '') {
            return ((float)$versionId >= 2.6) ? Period::POST_2026 : Period::PRE_2026;
        }
        return null;
    }
}
