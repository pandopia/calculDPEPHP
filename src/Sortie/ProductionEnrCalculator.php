<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Collectif\GeneratedApartment;
use CalculDpePHP\Common\ProductionSolaireThermique;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc `<sortie><production_enr>` — énergies renouvelables produites sur site.
 *
 * Introduit par l'arrêté du 11 juin 2026 portant adaptation au droit de
 * l'Union sur le DPE (JO du 13 août 2026, texte 23), annexe 2 — qui remplace
 * l'annexe 10 de l'arrêté du 31 mars 2021. Entrée en vigueur au
 * 1er janvier 2027, avec le modèle de données 2.7.
 *
 * Sept composantes, une par équipement de la liste du point 1.1 :
 *
 *   §1.2.1  Prod_enr_PAC            = Σ_PAC   Cch_pac_j × (SCOP_j − 1)
 *   §1.2.2  Prod_enr_thermodynamique = Σ_CET   Cecs_j   × (COP_j  − 1)
 *   §1.2.3  Ppv                      → annexe 1 de l'arrêté du 31 mars 2021
 *   §1.2.4  Prod_sol_therm           = Σ_ch  Bch_j × INT_j × Fch × Ich_j
 *                                    + Σ_ecs Becs_j × Fecs × Iecs_j
 *   §1.2.5  Prod_enr_bois            = la consommation de bois pour le chauffage
 *   §1.2.6  Prod_enr_rcfu            → taux ENR de l'annexe 7 de l'arrêté du
 *                                      15 septembre 2006
 *   §1.2.7  Prod_PAC_géo             = Σ_PAC  Cch_pac_j × (SCOP_j − 1)
 *
 * `(SCOP − 1)` et `(COP − 1)` isolent la **chaleur captée** : sur un
 * kilowattheure consommé, la machine en restitue SCOP, dont un seul vient du
 * réseau — le reste est l'énergie renouvelable prélevée à l'environnement.
 *
 * Les pompes à chaleur **hybrides** n'assurent qu'une part du besoin, et
 * l'arrêté la tabule par groupe de zones : `Cch_pac = 0,8 × Cch` en H1,
 * `0,83 × Cch` en H2, `0,88 × Cch` en H3. Ce sont exactement les prorata de
 * §9.1.4.3, que {@see \CalculDpePHP\Chauffage\GenerateurChAlias} porte déjà.
 *
 * Le bloc n'est écrit que pour le modèle 2.7 : l'écrire sur un DPE antérieur
 * produirait des balises que son schéma ne déclare pas.
 *
 * @spec-section Annexe 2, points 1.2.1 à 1.2.9
 * @spec-source  resources/textes/arrete-2026-06-11-epbd.pdf
 * @spec-source  editeur/public/schemas/DPEv2.7.xsd
 * @xml-input    installation_chauffage.generateur_chauffage.donnee_intermediaire.{conso_ch, scop},
 *               installation_ecs.generateur_ecs.donnee_intermediaire.{conso_ecs, cop},
 *               production_elec_enr.donnee_intermediaire.{production_pv, conso_elec_ac},
 *               sortie.ef_conso.conso_5_usages,
 *               sortie.apport_et_besoin.{besoin_ch, besoin_ecs}
 * @xml-output   sortie.production_enr.{prod_enr_pv, prod_enr_solaire_thermique,
 *                   prod_enr_chaleur_ambiante_pac, prod_enr_geothermie_pac,
 *                   prod_enr_chaleur_ambiante_cet, prod_enr_rcfu, prod_enr_bois,
 *                   prod_enr_totale, prod_enr_pourcentage}
 * @depends-on   \CalculDpePHP\Sortie\EfConsoCalculator
 * @depends-on   \CalculDpePHP\ProductionElec\ProductionPvCalculator
 * @depends-on   \CalculDpePHP\Chauffage\Strategy\ChauffageSolaire
 * @depends-on   \CalculDpePHP\Chauffage\Strategy\SolaireInsertPoele
 * @tables       (aucune — les coefficients sont dans l'arrêté)
 */
final class ProductionEnrCalculator implements CalculatorInterface
{
    /** Le bloc n'existe qu'à partir du modèle de données 2.7. */
    private const VERSION_MINIMALE = '2.7';

    /**
     * `enum_type_generateur_ch_id` des pompes à chaleur **géothermiques**,
     * que §1.2.7 compte à part de §1.2.1.
     *
     * Le XSD les nomme « pac géothermique » ou « pac eau glycolée/eau » : dans
     * les deux cas la source est le sol, non l'air ambiant.
     *
     * @var list<int>
     */
    private const PAC_GEOTHERMIQUES = [
        40, 41, 42, 43, 44, 45, 46, 47, 48,
        122, 123, 124, 125, 126, 127, 128, 129, 130,
        174, 175, 176, 180, 181,
    ];

    /**
     * `enum_type_energie_id` compatibles avec la mention A0.
     *
     * Électricité (1), bois sous toutes ses formes (4 à 7) et électricité
     * d'origine renouvelable (12). Le réseau de chaleur (8) en est écarté :
     * l'annexe ne l'admet que s'il est « efficace » au sens de L. 711-4, et le
     * DPE ne porte pas cette qualification.
     *
     * @var list<int>
     */
    private const ENERGIES_ADMISES_A0 = [1, 4, 5, 6, 7, 12];

    /** Prorata du besoin assuré par la partie PAC d'une hybride, par groupe de zones. */
    private const PART_PAC_HYBRIDE = ['H1' => 0.80, 'H2' => 0.83, 'H3' => 0.88];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        // La production photovoltaïque est reprise de §16.2 : il faut qu'elle
        // soit calculée, et les consommations de chauffage et d'ECS avec elle.
        return [
            EfConsoCalculator::class,
            \CalculDpePHP\ProductionElec\ProductionPvCalculator::class,
            \CalculDpePHP\Chauffage\Strategy\ChauffageSolaire::class,
            \CalculDpePHP\Chauffage\Strategy\SolaireInsertPoele::class,
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        if (!$this->modele27($accessor)) {
            return;
        }

        $composantes = [
            'prod_enr_pv'                   => $this->productionPv($accessor, $node),
            'prod_enr_solaire_thermique'    => ProductionSolaireThermique::total($context)
                * (GeneratedApartment::surfaceShare($node, $accessor) ?? 1.0),
            'prod_enr_chaleur_ambiante_pac' => $this->chaleurCaptee($accessor, $node, $context, geothermique: false),
            'prod_enr_geothermie_pac'       => $this->chaleurCaptee($accessor, $node, $context, geothermique: true),
            'prod_enr_chaleur_ambiante_cet' => $this->chaleurCapteeCet($accessor, $node),
            'prod_enr_rcfu'                 => (float) ($context->get('enr.reseau_chaleur') ?? 0.0),
            'prod_enr_bois'                 => $this->consommationBois($accessor, $node),
        ];

        $sortie = $accessor->ensureSortie($node);
        $bloc = $this->ensureBloc($context, $sortie);
        foreach ($composantes as $balise => $valeur) {
            $accessor->setChildValue($bloc, $balise, $valeur);
        }
        $accessor->setChildValue($bloc, 'prod_enr_totale', array_sum($composantes));
        $accessor->setChildValue($bloc, 'prod_enr_pourcentage', $this->fractionEnr($accessor, $node, $composantes));

        $accessor->setChildValue($sortie, 'mention_a0', $this->mentionA0($accessor, $node) ? '1' : '0');
    }

    /**
     * Article 1, 9.c de l'arrêté du 11/06/2026 et XSD 2.7, appinfo
     * sortie/production_enr/prod_enr_pourcentage : fraction (pas ×100),
     * sans plafond à 1. Le dénominateur réintègre les chaleurs renouvelables
     * et l'électricité autoconsommée retirées des consommations finales.
     *
     * @param array<string, float> $composantes
     * @spec-formula F-ENR-part-energie-finale
     */
    private function fractionEnr(NodeAccessor $accessor, DOMElement $logement, array $composantes): float
    {
        $denominateur = ($accessor->getFloatOrNull('./sortie/ef_conso/conso_5_usages', $logement) ?? 0.0)
            + ($accessor->getFloatOrNull('./production_elec_enr/donnee_intermediaire/conso_elec_ac', $logement) ?? 0.0)
            + $composantes['prod_enr_solaire_thermique']
            + $composantes['prod_enr_chaleur_ambiante_pac']
            + $composantes['prod_enr_geothermie_pac']
            + $composantes['prod_enr_chaleur_ambiante_cet'];

        // Aucun usage ni production : la fraction est nulle, jamais NaN/INF.
        return $denominateur > 0.0 ? array_sum($composantes) / $denominateur : 0.0;
    }

    /**
     * Annexe 1 point 3 : la mention « A0 », bâtiment à émissions nulles.
     *
     * Trois conditions cumulatives pour un DPE 3CL-2021 :
     *
     *   1. l'étiquette DPE est **A ou B** ;
     *   2. les énergies utilisées sont exclusivement renouvelables sur site ou
     *      à proximité, de l'électricité, ou issues d'un réseau de chaleur ou
     *      de froid efficace au sens de l'article L. 711-4 du code de
     *      l'énergie ;
     *   3. **aucune installation** de chauffage ou de production d'ECS n'est
     *      susceptible d'utiliser des énergies fossiles sur site ou à
     *      proximité.
     *
     * L'arrêté renvoie à un « logigramme » pour l'attribution, mais précise
     * qu'il ne fait qu'« intégrer ces conditions » — et il ne figure pas dans
     * l'extrait du Journal officiel. Le texte seul suffit donc.
     *
     * Les troisième et deuxième conditions se recouvrent : un générateur
     * déclaré sur une énergie fossile est à la fois une énergie non admise et
     * une installation susceptible d'en utiliser. Les énergies fossiles du
     * XSD sont le gaz naturel (2), le fioul (3), le propane, le butane, le
     * GPL (9, 10, 13) et le charbon (11).
     *
     * Un réseau de chaleur (8) n'est admis que s'il est « efficace ». Le DPE
     * ne porte pas cette qualification : par prudence, et faute de pouvoir la
     * démontrer sur le fichier, elle n'est pas présumée.
     */
    private function mentionA0(NodeAccessor $accessor, DOMElement $logement): bool
    {
        $classe = $accessor->getStringOrNull('./sortie/ep_conso/classe_bilan_dpe', $logement);
        if ($classe === null || !in_array(strtoupper(trim($classe)), ['A', 'B'], true)) {
            return false;
        }

        foreach (['generateur_chauffage', 'generateur_ecs'] as $balise) {
            $champ = $balise === 'generateur_chauffage'
                ? 'enum_type_energie_id'
                : 'enum_type_energie_id';
            foreach ($logement->getElementsByTagName($balise) as $generateur) {
                if (!$generateur instanceof DOMElement) {
                    continue;
                }
                $energie = $accessor->getIntOrNull('./donnee_entree/' . $champ, $generateur);
                if ($energie !== null && !in_array($energie, self::ENERGIES_ADMISES_A0, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** §1.2.3 : la production photovoltaïque, déjà calculée par §16.2. */
    private function productionPv(NodeAccessor $accessor, DOMElement $logement): float
    {
        return $accessor->getFloatOrNull(
            './/production_elec_enr/donnee_intermediaire/production_pv',
            $logement,
        ) ?? 0.0;
    }

    /**
     * §1.2.1 et §1.2.7 : `Σ Cch_pac_j × (SCOP_j − 1)`.
     *
     * Un SCOP inférieur ou égal à 1 ne capte rien : la machine consommerait
     * plus qu'elle ne restitue. La borne évite d'en déduire une production
     * négative.
     */
    private function chaleurCaptee(
        NodeAccessor $accessor,
        DOMElement $logement,
        CalculationContext $context,
        bool $geothermique,
    ): float {
        $total = 0.0;
        foreach ($logement->getElementsByTagName('generateur_chauffage') as $generateur) {
            if (!$generateur instanceof DOMElement) {
                continue;
            }
            $scop = $accessor->getFloatOrNull('./donnee_intermediaire/scop', $generateur);
            if ($scop === null || $scop <= 1.0) {
                continue;
            }
            $type = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $generateur);
            if ($type === null || in_array($type, self::PAC_GEOTHERMIQUES, true) !== $geothermique) {
                continue;
            }

            $conso = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch', $generateur) ?? 0.0;
            $total += $conso * $this->partHybride($generateur, $accessor, $context) * ($scop - 1.0);
        }

        return $total;
    }

    /** §1.2.2 : `Σ Cecs_j × (COP_j − 1)` sur les chauffe-eau thermodynamiques. */
    private function chaleurCapteeCet(NodeAccessor $accessor, DOMElement $logement): float
    {
        $total = 0.0;
        foreach ($logement->getElementsByTagName('generateur_ecs') as $generateur) {
            if (!$generateur instanceof DOMElement) {
                continue;
            }
            $cop = $accessor->getFloatOrNull('./donnee_intermediaire/cop', $generateur);
            if ($cop === null || $cop <= 1.0) {
                continue;
            }
            $total += ($accessor->getFloatOrNull('./donnee_intermediaire/conso_ecs', $generateur) ?? 0.0)
                * ($cop - 1.0);
        }

        return $total;
    }

    /**
     * Part du besoin assurée par la partie PAC d'une hybride (§1.2.1), ou 1
     * pour une PAC qui assure tout.
     */
    private function partHybride(
        DOMElement $generateur,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        $type = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $generateur);
        if ($type === null || $type < 143 || $type > 170) {
            return 1.0;
        }

        $groupe = CalculationContext::zoneGroupeFromId($context->zoneClimatique) ?? 'H1';

        return self::PART_PAC_HYBRIDE[$groupe] ?? 1.0;
    }

    /**
     * §1.2.5 : « Prod_enr_bois est égale à la consommation de bois pour le
     * chauffage ». Les énergies 4 à 7 du XSD sont les bois — bûches,
     * granulés, plaquettes forestières et d'industrie.
     */
    private function consommationBois(NodeAccessor $accessor, DOMElement $logement): float
    {
        $total = 0.0;
        foreach ($logement->getElementsByTagName('generateur_chauffage') as $generateur) {
            if (!$generateur instanceof DOMElement) {
                continue;
            }
            $energie = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $generateur);
            if ($energie !== null && $energie >= 4 && $energie <= 7) {
                $total += $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch', $generateur) ?? 0.0;
            }
        }

        return $total;
    }

    private function modele27(NodeAccessor $accessor): bool
    {
        $version = $accessor->getStringOrNull('//administratif/enum_version_id');

        return $version !== null && version_compare($version, self::VERSION_MINIMALE, '>=');
    }

    private function ensureBloc(CalculationContext $context, DOMElement $sortie): DOMElement
    {
        foreach ($sortie->childNodes as $enfant) {
            if ($enfant instanceof DOMElement && $enfant->nodeName === 'production_enr') {
                return $enfant;
            }
        }
        $bloc = $context->document->createElement('production_enr');
        $sortie->appendChild($bloc);

        return $bloc;
    }
}
