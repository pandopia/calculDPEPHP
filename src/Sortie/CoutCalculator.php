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
 * 3. **La tranche s'apprécie par abonnement.** Le barème tarifie la
 *    consommation annuelle d'un ménage — donc d'un point de livraison. Sur un
 *    DPE immeuble, la sortie porte sur tout le bâtiment : les usages desservis
 *    par une installation **collective** relèvent d'un unique abonnement
 *    d'immeuble, les usages **individuels** d'un abonnement par logement. Hors
 *    DPE immeuble (maison, appartement, appartement issu des données de
 *    l'immeuble), la sortie porte déjà sur un seul logement : un seul
 *    abonnement, sans découpage.
 *
 *    Le barème publié ne dit pas comment répartir les abonnements dans un
 *    immeuble : cette règle est inférée. Elle est cohérente avec la notion de
 *    point de livraison et reproduit le coût par énergie de la référence sur
 *    216 des 229 cas du corpus, contre 139 en divisant tout par le nombre de
 *    logements. À confirmer sur le texte officiel — voir TASK-K11.
 *
 * @spec-section Annexe 7 (prix des énergies)
 * @spec-source  https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000044202205
 * @spec-source  https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000049446315
 * @xml-input    administratif.date_etablissement_dpe,
 *               caracteristique_generale.{nombre_appartement, enum_methode_application_dpe_log_id},
 *               installation_chauffage.donnee_entree.enum_type_installation_id,
 *               installation_ecs.donnee_entree.enum_type_installation_id,
 *               sortie.ef_conso.*,
 *               installation_chauffage.generateur_chauffage.donnee_entree.enum_type_energie_id,
 *               installation_ecs.generateur_ecs.donnee_entree.enum_type_energie_id
 * @xml-output   sortie.cout.{cout_ch, cout_ch_depensier, cout_ecs, cout_ecs_depensier,
 *                   cout_eclairage, cout_auxiliaire_*, cout_total_auxiliaire,
 *                   cout_fr, cout_fr_depensier, cout_5_usages}
 * @depends-on   \CalculDpePHP\Sortie\EfConsoCalculator
 * @tables       reference/tv_prix_energie
 *
 * Le barème et la mécanique des tranches sont dans {@see PrixEnergie}, partagé
 * avec `SortieParEnergieAggregator` : les deux blocs décrivent les mêmes euros.
 */
final class CoutCalculator implements CalculatorInterface
{
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
        $prix = PrixEnergie::pour($node, $accessor, $context);

        $energieCh  = $this->primaryEnergieId($accessor, $node, 'installation_chauffage', 'generateur_chauffage') ?? 1;
        $energieEcs = $this->primaryEnergieId($accessor, $node, 'installation_ecs', 'generateur_ecs') ?? 1;

        $chCollectif  = $prix->chauffageCollectif;
        $ecsCollectif = $prix->ecsCollectif;
        // Le froid est électrique par convention : la méthode ne connaît pas de
        // groupe froid à combustion.
        $energieFr  = 1;

        // Les postes auxiliaires et l'éclairage sont toujours électriques.
        // La ventilation reste individuelle : la rattacher à l'abonnement
        // d'immeuble dégrade nettement l'accord avec la référence.
        $conventionnel = [
            'cout_ch'                                  => [$energieCh,  $ef['conso_ch'], $chCollectif],
            'cout_ecs'                                 => [$energieEcs, $ef['conso_ecs'], $ecsCollectif],
            'cout_fr'                                  => [$energieFr,  $ef['conso_fr'], false],
            'cout_eclairage'                           => [1, $ef['conso_eclairage'], false],
            'cout_auxiliaire_generation_ch'            => [1, $ef['conso_auxiliaire_generation_ch'], $chCollectif],
            'cout_auxiliaire_distribution_ch'          => [1, $ef['conso_auxiliaire_distribution_ch'], $chCollectif],
            'cout_auxiliaire_generation_ecs'           => [1, $ef['conso_auxiliaire_generation_ecs'], $ecsCollectif],
            'cout_auxiliaire_distribution_ecs'         => [1, $ef['conso_auxiliaire_distribution_ecs'], $ecsCollectif],
            'cout_auxiliaire_ventilation'              => [1, $ef['conso_auxiliaire_ventilation'], false],
        ];

        // Le scénario dépensier est une autre consommation annuelle : il a donc
        // ses propres tranches, calculées sur son propre panier.
        $depensier = [
            'cout_ch_depensier'                        => [$energieCh,  $ef['conso_ch_depensier'], $chCollectif],
            'cout_ecs_depensier'                       => [$energieEcs, $ef['conso_ecs_depensier'], $ecsCollectif],
            'cout_fr_depensier'                        => [$energieFr,  $ef['conso_fr_depensier'], false],
            'cout_eclairage'                           => [1, $ef['conso_eclairage'], false],
            'cout_auxiliaire_generation_ch_depensier'  => [1, $ef['conso_auxiliaire_generation_ch_depensier'], $chCollectif],
            'cout_auxiliaire_distribution_ch'          => [1, $ef['conso_auxiliaire_distribution_ch'], $chCollectif],
            'cout_auxiliaire_generation_ecs_depensier' => [1, $ef['conso_auxiliaire_generation_ecs_depensier'], $ecsCollectif],
            'cout_auxiliaire_distribution_ecs'         => [1, $ef['conso_auxiliaire_distribution_ecs'], $ecsCollectif],
            'cout_auxiliaire_ventilation'              => [1, $ef['conso_auxiliaire_ventilation'], false],
        ];

        $couts = $prix->tarifer($conventionnel) + $prix->tarifer($depensier);

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
