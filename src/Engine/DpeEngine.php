<?php

declare(strict_types=1);

namespace CalculDpePHP\Engine;

use CalculDpePHP\Common\Period;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use CalculDpePHP\Xml\XmlReader;
use CalculDpePHP\Xml\XmlWriter;
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
        $document = $this->calculateDocument($document);

        (new XmlWriter())->save($document, $outputFile);
    }

    public function calculate(string $xml): DOMDocument
    {
        $document = (new XmlReader())->loadString($xml);

        return $this->calculateDocument($document);
    }

    public function calculateDocument(DOMDocument $document): DOMDocument
    {
        // Garde-fou AVANT toute purge : un DPE hors méthode 3CL logement ne doit
        // pas perdre sa <sortie> existante ni produire un faux succès.
        $this->assertCalculable($document);

        // Idempotence : on retire d'abord toute donnée intermédiaire / sortie pré-existante
        $this->purgeOutputs($document);

        $context = $this->buildContext($document);

        $this->pipeline->run($document, $context);

        return $document;
    }

    /**
     * La méthode 3CL-2021 ne couvre que les logements existants :
     * administratif/enum_modele_dpe_id = 1 « dpe 3cl 2021 méthode logement ».
     * Les DPE neufs (2 : RT2012, 3 : RE2020) et tertiaires (4) portent une
     * structure <logement_neuf> sans <donnee_entree> — aucun Calculator ne
     * s'applique, il faut refuser le fichier plutôt que d'écrire un résultat vide.
     *
     * @throws UnsupportedDpeModelException
     */
    private function assertCalculable(DOMDocument $document): void
    {
        $accessor = new NodeAccessor($document);
        $modeleId = $accessor->getIntOrNull('//administratif/enum_modele_dpe_id');

        if ($modeleId !== null && $modeleId !== 1) {
            $libelle = match ($modeleId) {
                2       => 'DPE neuf (RT2012)',
                3       => 'DPE neuf (RE2020)',
                4       => 'DPE 2006 tertiaire et ERP',
                default => "modèle de DPE inconnu (enum_modele_dpe_id=$modeleId)",
            };
            throw new UnsupportedDpeModelException(
                "$libelle : non calculable par la méthode 3CL. " .
                "Seul enum_modele_dpe_id=1 (« dpe 3cl 2021 méthode logement ») est supporté."
            );
        }

        if ($document->getElementsByTagName('logement')->length === 0) {
            $detail = $document->getElementsByTagName('logement_neuf')->length > 0
                ? 'structure <logement_neuf> détectée — DPE neuf (RT2012/RE2020) : non calculable par la méthode 3CL'
                : 'balise <logement> absente : non calculable par la méthode 3CL';
            throw new UnsupportedDpeModelException($detail . '.');
        }
    }

    private function purgeOutputs(DOMDocument $document): void
    {
        // Préserve les caractéristiques saisies (pn, rpn… selon
        // enum_methode_saisie_carac_sys_id) qui sont des entrées stockées
        // en donnee_intermediaire — voir OutputPurger.
        \CalculDpePHP\Xml\OutputPurger::purge($document);
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
