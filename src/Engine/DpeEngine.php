<?php

declare(strict_types=1);

namespace CalculDpePHP\Engine;

use CalculDpePHP\Common\Period;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
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

    public function calculateDocument(DOMDocument $document, bool $simplifiedApartmentNeeds = false): DOMDocument
    {
        // Garde-fou AVANT toute purge : un DPE hors méthode 3CL logement ne doit
        // pas perdre sa <sortie> existante ni produire un faux succès.
        $this->assertCalculable($document);

        // Idempotence : on retire d'abord toute donnée intermédiaire / sortie pré-existante
        $this->purgeOutputs($document);

        $context = $this->buildContext($document);
        $context->set('collectif.besoin_simplifie', $simplifiedApartmentNeeds);

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
    /**
     * Domaine des types de générateur que les tables couvrent, par balise :
     * [champ, identifiant maximal, libellé d'usage].
     *
     * Les bornes sont celles du modèle 2.6 ; les extensions 2.7 couvertes
     * sont admises explicitement dans assertTechnologiesConnues().
     */
    private const GENERATEURS_CONNUS = [
        'generateur_chauffage' => ['enum_type_generateur_ch_id', 171, 'de chauffage'],
        'generateur_ecs'       => ['enum_type_generateur_ecs_id', 134, 'd\'ECS'],
    ];

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

        $this->assertTechnologiesConnues($accessor, $document);
        $this->assertOptionsConnues($accessor, $document);
        $this->assertFroidElectrique($accessor, $document);
    }

    /**
     * EP, GES, coûts et ventilation par énergie du froid ne couvrent pour
     * l'instant que l'électricité du réseau. XSD 2.6/2.7 : le type 21 est un
     * système thermodynamique gaz ; le modèle 2.7 ajoute le réseau de froid 23.
     * Une performance EER valide ne rend pas leurs conversions électriques.
     * L'énergie absente/nil conserve le fonctionnement électrique historique.
     *
     * @spec-source editeur/public/schemas/DPEv2.7.xsd
     * @throws UnsupportedDpeModelException
     */
    private function assertFroidElectrique(NodeAccessor $accessor, DOMDocument $document): void
    {
        foreach ($document->getElementsByTagName('climatisation') as $climatisation) {
            $type = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_fr_id', $climatisation);
            $energie = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $climatisation);
            if ($type === 21 || ($type !== null && $type > 22) || ($energie !== null && $energie !== 1)) {
                throw new UnsupportedDpeModelException(sprintf(
                    'refroidissement non couvert (enum_type_generateur_fr_id=%s, enum_type_energie_id=%s) : '
                    . 'seul le froid électrique du réseau est implémenté pour les conversions EP/GES/coûts. '
                    . 'Le fichier est refusé avant purge — voir TASK-K63.',
                    $type ?? 'absent', $energie ?? 'absent',
                ));
            }
        }
    }

    /**
     * Domaine des options dont les calculateurs implémentent la méthode.
     *
     * @spec-source editeur/public/schemas/DPEv2.7.xsd
     * Les appinfo des champs ci-dessous introduisent solaire ECS seule (5),
     * MTA (4), régulation prédictive (8), période thermo 2027 (4), RCEG et
     * deux coefficients saisis. Leur simple présence ne suffit pas à rendre
     * leur calcul disponible. Refuser avant purge, comme les générateurs.
     * Un maximum null signifie qu'aucune valeur saisie n'est encore traitée.
     *
     * @throws UnsupportedDpeModelException
     */
    private function assertOptionsConnues(NodeAccessor $accessor, DOMDocument $document): void
    {
        // ADEME 05aabba7, feuille debits_ventilation : en 2.7 le type 1
        // utilise qvaerat_conv=1.2 et qvarep_conv=0, contre qvarep=1.2
        // historiquement. Tant que la nouvelle séparation aération/ventilation
        // n'est pas implémentée, ne pas produire les anciens intermédiaires.
        $version = $accessor->getStringOrNull('//administratif/enum_version_id');
        if ($version !== null && version_compare($version, '2.7', '>=')) {
            foreach ($document->getElementsByTagName('ventilation') as $ventilation) {
                if ($accessor->getIntOrNull('./donnee_entree/enum_type_ventilation_id', $ventilation) === 1) {
                    throw new UnsupportedDpeModelException(
                        'aération par ouverture des fenêtres : la séparation ventilation/aération du modèle DPE 2.7 '
                        . 'n’est pas encore implémentée. Le fichier est refusé avant purge — voir TASK-K63.',
                    );
                }
            }
        }
        $options = [
            ['installation_ecs', 'enum_type_installation_solaire_id', 4],
            ['installation_ecs', 'enum_bouclage_reseau_ecs_id', 3],
            ['emetteur_chauffage', 'enum_equipement_intermittence_id', 7],
            ['generateur_ecs', 'enum_periode_installation_ecs_thermo_id', 3],
            ['installation_ecs', 'presence_rceg', 0],
            ['generateur_ecs', 'scf_saisi', null],
            ['installation_chauffage', 'couverture_pac_hybride_saisi', null],
        ];
        foreach ($options as [$balise, $champ, $maximum]) {
            foreach ($document->getElementsByTagName($balise) as $equipement) {
                $valeur = $accessor->getFloatOrNull('./donnee_entree/' . $champ, $equipement);
                // La période 4 décrit les CET depuis 2027. Les nouveaux
                // enums 135-138 et l'« autre thermodynamique » 82 sont couverts
                // par la feuille officielle scop (lignes 108-113). Les anciens
                // enums datés ne sont pas requalifiés depuis leur seule période.
                if ($balise === 'generateur_ecs' && $champ === 'enum_periode_installation_ecs_thermo_id'
                    && $valeur === 4.0 && $version !== null && version_compare($version, '2.7', '>=')
                    && in_array($accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $equipement), [82, 135, 136, 137, 138], true)) {
                    continue;
                }
                if ($valeur !== null && ($maximum === null || $valeur > $maximum)) {
                    throw new UnsupportedDpeModelException(sprintf(
                        '%s/%s=%s : option du modèle DPE 2.7 non encore implémentée — voir TASK-K63. '
                        . 'Le fichier est refusé avant purge plutôt que de calculer en ignorant cette option.',
                        $balise, $champ, $valeur,
                    ));
                }
            }
        }
    }

    /**
     * Refuse un générateur dont le type dépasse le domaine que les tables
     * couvrent.
     *
     * Le modèle 2.7 du 16 octobre 2026 ajoute treize types de générateur de
     * chauffage (172 à 184) et douze d'ECS (135 à 146) : pompes à chaleur et
     * chauffe-eau thermodynamiques à partir de 2027, chaudières bois granulés
     * après 2024, ballons à commande intelligente. Les quatre CET 135-138
     * et les neuf PAC 172-176/178-181, ainsi que les chaudières à condensation
     * CH 177/182 et ECS 139/144, sont couverts par le classeur ADEME
     * 05aabba7 ; les autres restent
     * refusés tant que toutes leurs caractéristiques ne sont pas digitalisées.
     *
     * **Sans ce garde le moteur ne crashe pas : il fait pire.** Un générateur
     * inconnu traverse les calculateurs de rendement sans que l'un d'eux
     * s'applique, ressort sans `pn`, sans `rpn` et sans
     * `rendement_generation`, et sa consommation est calculée avec un
     * rendement implicite de 1. Le fichier produit est silencieusement faux,
     * et rien ne le signale.
     *
     * @throws UnsupportedDpeModelException
     */
    private function assertTechnologiesConnues(NodeAccessor $accessor, DOMDocument $document): void
    {
        $version = $accessor->getStringOrNull('//administratif/enum_version_id');
        foreach (self::GENERATEURS_CONNUS as $balise => [$champ, $maximum, $usage]) {
            foreach ($document->getElementsByTagName($balise) as $generateur) {
                if (!$generateur instanceof DOMElement) {
                    continue;
                }
                $type = $accessor->getIntOrNull('./donnee_entree/' . $champ, $generateur);
                if ($balise === 'generateur_ecs' && in_array($type, [135, 136, 137, 138, 139, 144], true)
                    && $version !== null && version_compare($version, '2.7', '>=')) {
                    continue;
                }
                if ($balise === 'generateur_chauffage' && in_array($type, [172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182], true)
                    && $version !== null && version_compare($version, '2.7', '>=')) {
                    continue;
                }
                if ($type !== null && $type > $maximum) {
                    throw new UnsupportedDpeModelException(sprintf(
                        'générateur %s de type %d : technologie introduite par le modèle DPE 2.7, '
                        . 'dont les caractéristiques ne sont pas encore digitalisées (le moteur '
                        . 'couvre %s jusqu\'à %d). Calculer ce fichier produirait un rendement de '
                        . 'génération implicite de 1 et une consommation fausse — voir TASK-K63.',
                        $usage,
                        $type,
                        $champ,
                        $maximum,
                    ));
                }
            }
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

        // Colonne « effet joule » ou « autres » des tables forfaitaires de U
        // (§3.2.1 p.13 et suivantes). §3.2 p.12 la définit sans ambiguïté :
        // « On considère qu'un logement est chauffé par effet joule lorsque la
        // chaleur est fournie par une résistance électrique. » Une pompe à
        // chaleur fonctionne sur un cycle thermodynamique : elle est électrique
        // sans être à effet joule, et relève donc de la colonne « autres ».
        $energieGenId = $accessor->getIntOrNull('(//generateur_chauffage/donnee_entree/enum_type_energie_id)[1]');
        $typeGenId    = $accessor->getIntOrNull('(//generateur_chauffage/donnee_entree/enum_type_generateur_ch_id)[1]');
        $energieChauffage = CalculationContext::colonneEffetJoule($energieGenId, $typeGenId);

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
