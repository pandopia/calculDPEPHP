<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><cout> : frais annuels d'énergie par usage (€).
 *
 * Trois règles structurent ce calcul, et l'implémentation précédente n'en
 * respectait aucune :
 *
 * 1. **Le barème dépend de la date du DPE.** Les tarifs sont réactualisés par
 *    arrêté ; l'arrêté du 31 mars 2021 impose d'ailleurs que le DPE porte la
 *    date de la version utilisée à côté de l'estimation des frais. Le barème
 *    est donc choisi sur `administratif/date_etablissement_dpe`.
 *
 * 2. **La tranche s'apprécie sur la consommation totale de l'énergie, pas
 *    usage par usage.** Tarifer séparément l'éclairage puis les auxiliaires
 *    place chacun dans la première tranche et facture deux fois la part
 *    d'abonnement. Le prix unitaire est calculé une fois sur le total de
 *    l'énergie, puis appliqué à chaque poste.
 *
 * 3. **La tranche s'apprécie par logement.** Le barème tarifie la
 *    consommation annuelle d'un ménage ; sur un DPE immeuble, la sortie porte
 *    sur tout le bâtiment. La tranche est donc déterminée sur la consommation
 *    ramenée au nombre de logements.
 *
 * @spec-section Annexe 7 (prix des énergies)
 * @spec-source  https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000044202205
 * @spec-source  https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000049446315
 * @xml-input    administratif.date_etablissement_dpe,
 *               caracteristique_generale.nombre_appartement,
 *               sortie.ef_conso.*,
 *               installation_chauffage.generateur_chauffage.donnee_entree.enum_type_energie_id,
 *               installation_ecs.generateur_ecs.donnee_entree.enum_type_energie_id
 * @xml-output   sortie.cout.{cout_ch, cout_ch_depensier, cout_ecs, cout_ecs_depensier,
 *                   cout_eclairage, cout_auxiliaire_*, cout_total_auxiliaire,
 *                   cout_fr, cout_fr_depensier, cout_5_usages}
 * @depends-on   \CalculDpePHP\Sortie\EfConsoCalculator
 * @tables       reference/tv_prix_energie
 */
final class CoutCalculator implements CalculatorInterface
{
    private const TABLE = 'reference/tv_prix_energie';

    /** `électricité` et `électricité d'origine renouvelable utilisée dans le bâtiment`. */
    private const ELEC_IDS = [1, 12];

    private const GAZ_NATUREL_ID = 2;

    /** Préfixe des clés de regroupement des énergies à prix unique. */
    private const KWH_PREFIX = 'kwh:';

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [EfConsoCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $sortie   = $accessor->ensureSortie($node);

        $ef = $this->getEfConso($accessor, $sortie);
        $bareme = $this->bareme($accessor, $context);
        $nbLogements = $this->nombreLogements($accessor, $node);

        $energieCh  = $this->primaryEnergieId($accessor, $node, 'installation_chauffage', 'generateur_chauffage') ?? 1;
        $energieEcs = $this->primaryEnergieId($accessor, $node, 'installation_ecs', 'generateur_ecs') ?? 1;
        // Le froid est électrique par convention : la méthode ne connaît pas de
        // groupe froid à combustion.
        $energieFr  = 1;

        // Les postes auxiliaires et l'éclairage sont toujours électriques.
        $conventionnel = [
            'cout_ch'                                  => [$energieCh,  $ef['conso_ch']],
            'cout_ecs'                                 => [$energieEcs, $ef['conso_ecs']],
            'cout_fr'                                  => [$energieFr,  $ef['conso_fr']],
            'cout_eclairage'                           => [1, $ef['conso_eclairage']],
            'cout_auxiliaire_generation_ch'            => [1, $ef['conso_auxiliaire_generation_ch']],
            'cout_auxiliaire_distribution_ch'          => [1, $ef['conso_auxiliaire_distribution_ch']],
            'cout_auxiliaire_generation_ecs'           => [1, $ef['conso_auxiliaire_generation_ecs']],
            'cout_auxiliaire_distribution_ecs'         => [1, $ef['conso_auxiliaire_distribution_ecs']],
            'cout_auxiliaire_ventilation'              => [1, $ef['conso_auxiliaire_ventilation']],
        ];

        // Le scénario dépensier est une autre consommation annuelle : il a donc
        // ses propres tranches, calculées sur son propre panier.
        $depensier = [
            'cout_ch_depensier'                        => [$energieCh,  $ef['conso_ch_depensier']],
            'cout_ecs_depensier'                       => [$energieEcs, $ef['conso_ecs_depensier']],
            'cout_fr_depensier'                        => [$energieFr,  $ef['conso_fr_depensier']],
            'cout_eclairage'                           => [1, $ef['conso_eclairage']],
            'cout_auxiliaire_generation_ch_depensier'  => [1, $ef['conso_auxiliaire_generation_ch_depensier']],
            'cout_auxiliaire_distribution_ch'          => [1, $ef['conso_auxiliaire_distribution_ch']],
            'cout_auxiliaire_generation_ecs_depensier' => [1, $ef['conso_auxiliaire_generation_ecs_depensier']],
            'cout_auxiliaire_distribution_ecs'         => [1, $ef['conso_auxiliaire_distribution_ecs']],
            'cout_auxiliaire_ventilation'              => [1, $ef['conso_auxiliaire_ventilation']],
        ];

        $couts = $this->couts($conventionnel, $bareme, $nbLogements)
            + $this->couts($depensier, $bareme, $nbLogements);

        $totalAux = $couts['cout_auxiliaire_generation_ch']
            + $couts['cout_auxiliaire_distribution_ch']
            + $couts['cout_auxiliaire_generation_ecs']
            + $couts['cout_auxiliaire_distribution_ecs']
            + $couts['cout_auxiliaire_ventilation'];

        $cout5Usages = $couts['cout_ch'] + $couts['cout_ecs'] + $couts['cout_fr']
            + $totalAux + $couts['cout_eclairage'];

        $cout = $context->document->createElement('cout');
        $sortie->appendChild($cout);

        foreach ([
            'cout_ch', 'cout_ch_depensier', 'cout_ecs', 'cout_ecs_depensier',
            'cout_eclairage',
            'cout_auxiliaire_generation_ch', 'cout_auxiliaire_generation_ch_depensier',
            'cout_auxiliaire_distribution_ch',
            'cout_auxiliaire_generation_ecs', 'cout_auxiliaire_generation_ecs_depensier',
            'cout_auxiliaire_distribution_ecs', 'cout_auxiliaire_ventilation',
        ] as $tag) {
            $accessor->setChildValue($cout, $tag, $couts[$tag] ?? 0.0);
        }

        $accessor->setChildValue($cout, 'cout_total_auxiliaire', $totalAux);
        $accessor->setChildValue($cout, 'cout_fr', $couts['cout_fr']);
        $accessor->setChildValue($cout, 'cout_fr_depensier', $couts['cout_fr_depensier']);
        $accessor->setChildValue($cout, 'cout_5_usages', $cout5Usages);
    }

    /**
     * Tarife un panier de postes : le prix unitaire de chaque énergie est
     * établi une fois, sur le total du panier pour cette énergie, puis
     * appliqué à chaque poste au prorata de sa consommation.
     *
     * @param array<string, array{0: int, 1: float}> $postes  poste ⇒ [énergie, conso kWh]
     * @param array<string, mixed> $bareme
     * @return array<string, float>
     */
    private function couts(array $postes, array $bareme, float $nbLogements): array
    {
        $totaux = [];
        foreach ($postes as [$energieId, $conso]) {
            $key = $this->energieKey($energieId);
            $totaux[$key] = ($totaux[$key] ?? 0.0) + $conso;
        }

        $prix = [];
        foreach ($totaux as $key => $total) {
            $prix[$key] = $this->prixUnitaire($key, $total, $bareme, $nbLogements);
        }

        $couts = [];
        foreach ($postes as $tag => [$energieId, $conso]) {
            $couts[$tag] = $conso * $prix[$this->energieKey($energieId)];
        }

        return $couts;
    }

    /**
     * Prix moyen du kWh (€) pour une énergie donnée.
     *
     * Pour l'électricité et le gaz naturel, le barème donne un coût annuel
     * `terme_fixe + prix_kwh × Cef` où Cef est la consommation **d'un
     * logement** : on détermine la tranche sur la consommation ramenée au
     * logement, puis on repasse en prix unitaire pour l'appliquer au total du
     * bâtiment.
     *
     * @param array<string, mixed> $bareme
     * @spec-formula Annexe7-prix-unitaire
     */
    private function prixUnitaire(string $energieKey, float $consoTotale, array $bareme, float $nbLogements): float
    {
        if ($consoTotale <= 0.0) {
            return 0.0;
        }

        if ($energieKey === 'electricite' || $energieKey === 'gaz_naturel') {
            $parLogement = $consoTotale / max(1.0, $nbLogements);
            $cout = $this->coutParTranche($bareme[$energieKey], $parLogement);

            return $cout / $parLogement;
        }

        // Les autres énergies ont un prix du kWh unique, sans abonnement.
        $id = (int) substr($energieKey, strlen(self::KWH_PREFIX));

        return (float) ($bareme['kwh'][$id] ?? 0.0);
    }

    /**
     * @param list<array{0: float, 1: float, 2: float}> $tranches [borne haute exclue, terme fixe, prix kWh]
     */
    private function coutParTranche(array $tranches, float $cef): float
    {
        foreach ($tranches as [$borne, $termeFixe, $prixKwh]) {
            if ($cef < $borne) {
                return $termeFixe + $prixKwh * $cef;
            }
        }

        // La dernière tranche est bornée par INF : inatteignable en pratique.
        [, $termeFixe, $prixKwh] = $tranches[array_key_last($tranches)];

        return $termeFixe + $prixKwh * $cef;
    }

    /**
     * Clé de regroupement d'une énergie : `electricite`, `gaz_naturel`, ou
     * l'identifiant XSD pour les énergies à prix unique.
     */
    private function energieKey(int $energieId): string
    {
        if (in_array($energieId, self::ELEC_IDS, true)) {
            return 'electricite';
        }
        if ($energieId === self::GAZ_NATUREL_ID) {
            return 'gaz_naturel';
        }

        // Préfixé : une clé purement numérique serait convertie en int par PHP
        // dans les tableaux de regroupement.
        return self::KWH_PREFIX . $energieId;
    }

    /**
     * Barème en vigueur à la date d'établissement du DPE. À défaut de date
     * lisible, le barème le plus récent s'applique.
     *
     * @return array<string, mixed>
     */
    private function bareme(NodeAccessor $accessor, CalculationContext $context): array
    {
        /** @var list<array<string, mixed>> $baremes */
        $baremes = $context->tables->load(self::TABLE);
        $date = $accessor->getStringOrNull('//administratif/date_etablissement_dpe');

        if ($date !== null) {
            foreach ($baremes as $bareme) {
                $from = (string) $bareme['valid_from'];
                $to = $bareme['valid_to'];
                if ($date >= $from && ($to === null || $date <= (string) $to)) {
                    return $bareme;
                }
            }
        }

        return $baremes[array_key_last($baremes)];
    }

    /**
     * Nombre de logements couverts par le DPE, qui sert à ramener la
     * consommation du bâtiment à celle d'un ménage pour choisir la tranche.
     * Absent ou nul sur une maison ou un appartement : un seul logement.
     */
    private function nombreLogements(NodeAccessor $accessor, DOMElement $logement): float
    {
        $n = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $logement);

        return ($n === null || $n < 1.0) ? 1.0 : $n;
    }

    /**
     * @return array<string, float>
     */
    private function getEfConso(NodeAccessor $accessor, DOMElement $sortie): array
    {
        $keys = [
            'conso_ch', 'conso_ch_depensier', 'conso_ecs', 'conso_ecs_depensier',
            'conso_eclairage', 'conso_fr', 'conso_fr_depensier',
            'conso_auxiliaire_generation_ch', 'conso_auxiliaire_generation_ch_depensier',
            'conso_auxiliaire_distribution_ch',
            'conso_auxiliaire_generation_ecs', 'conso_auxiliaire_generation_ecs_depensier',
            'conso_auxiliaire_distribution_ecs', 'conso_auxiliaire_ventilation',
        ];
        $ef = [];
        foreach ($keys as $key) {
            $ef[$key] = $accessor->getFloatOrNull('./ef_conso/' . $key, $sortie) ?? 0.0;
        }

        return $ef;
    }

    /**
     * Retourne l'enum_type_energie_id du premier générateur trouvé dans la collection.
     */
    private function primaryEnergieId(
        NodeAccessor $accessor,
        DOMElement $logement,
        string $installCollection,
        string $generateurTag,
    ): ?int {
        $collection = null;
        foreach ($logement->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $installCollection . '_collection') {
                $collection = $child;
                break;
            }
        }
        if ($collection === null) {
            return null;
        }
        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== $installCollection) {
                continue;
            }
            foreach ($install->childNodes as $genColl) {
                if (!$genColl instanceof DOMElement) {
                    continue;
                }
                foreach ($genColl->childNodes as $gen) {
                    if (!$gen instanceof DOMElement || $gen->nodeName !== $generateurTag) {
                        continue;
                    }
                    $id = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen);
                    if ($id !== null) {
                        return $id;
                    }
                }
            }
        }

        return null;
    }
}
