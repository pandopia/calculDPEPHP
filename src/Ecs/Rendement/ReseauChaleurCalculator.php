<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement ECS via réseau de chaleur (§14.3 p.95).
 *
 * Les rendements de stockage et de génération sont remplacés par le rendement
 * d'échange de la sous-station :
 *   - installation isolée  : Rs × Rg = 0.9
 *   - sinon (non isolée)   : Rs × Rg = 0.75
 *
 * On stocke directement le produit dans `rendement_generation_stockage`,
 * conformément au vocabulaire XML ADEME.
 *
 * @spec-section 14.3
 * @spec-pages   95
 * @spec-source  resources/specsplitted/14-rendement-ecs-generateurs/03-reseau-chaleur.md
 * @xml-input    generateur_ecs.donnee_entree.enum_type_generateur_ecs_id
 * @xml-output   generateur_ecs.donnee_intermediaire.rendement_generation_stockage
 * @depends-on   aucun
 * @tables       (aucune — deux valeurs directement de la spec p.95)
 */
final class ReseauChaleurCalculator implements CalculatorInterface
{
    /**
     * enum_type_generateur_ecs_id → Rs × Rg (§14.3 p.95).
     *
     * Le § distingue l'installation isolée (0,9) de celle qui ne l'est pas
     * (0,75), et le XSD ne porte ce qualificatif que sur les réseaux urbains
     * 72/73 (et leurs équivalents « logement neuf » 107/108). Les types
     * 74-77 et 134, « chaudière(s) … multi bâtiment modélisée comme un réseau
     * de chaleur », désignent un générateur d'un bâtiment voisin : il n'y a pas
     * de réseau primaire non isolé à pénaliser, donc l'échange isolé s'applique.
     * Le type 119, « non répertorié ou inconnu », garde la valeur pénalisante.
     */
    private const RG_BY_TYPE = [
        72  => 0.75,  // réseau de chaleur non isolé (logement existant)
        73  => 0.90,  // réseau de chaleur isolé (logement existant)
        74  => 0.90,  // chaudière(s) bois multi bâtiment en réseau de chaleur
        75  => 0.90,  // chaudière(s) fioul multi bâtiment en réseau de chaleur
        76  => 0.90,  // chaudière(s) gaz multi bâtiment en réseau de chaleur
        77  => 0.90,  // pompe(s) à chaleur multi bâtiment en réseau de chaleur
        107 => 0.75,  // réseau de chaleur non isolé (logement neuf)
        108 => 0.90,  // réseau de chaleur isolé (logement neuf)
        119 => 0.75,  // réseau de chaleur non répertorié ou inconnu (XSD 2.6)
        134 => 0.90,  // chaudière(s) charbon multi bâtiment en réseau de chaleur
    ];

    /**
     * « Chaudière(s) … multi bâtiment modélisée comme un réseau de chaleur » :
     * le XSD ne leur donne aucun qualificatif « isolé / non isolé ».
     *
     * @var list<int>
     */
    private const MULTI_BATIMENT_IDS = [74, 75, 76, 77, 134];

    /** XSD : enum_type_stockage_ecs_id = 1, « sans stockage ». */
    private const STOCKAGE_SANS_BALLON = 1;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'generateur_ecs';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $typeId   = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $node);

        if ($typeId === null || !isset(self::RG_BY_TYPE[$typeId])) {
            return;
        }

        $rg = self::RG_BY_TYPE[$typeId];
        if (in_array($typeId, self::MULTI_BATIMENT_IDS, true) && $this->sousStationHorsVolumeChauffe($node, $accessor)) {
            $rg = 0.75;
        }

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_generation_stockage', $rg);
    }

    /**
     * §14.3 p.95 : « si l'installation est isolée : Rs × Rg = 0,9 ; sinon 0,75 ».
     *
     * Le XSD ne porte le qualificatif « isolé / non isolé » que sur les
     * réseaux urbains 72 et 73, dont le libellé l'énonce. Pour les
     * « chaudière(s) … multi bâtiment modélisée(s) comme un réseau de
     * chaleur », il faut le lire dans les positions déclarées : une
     * sous-station dont le générateur **et** le ballon sont hors volume
     * chauffé n'est pas isolée du dehors, et ses pertes ne sont pas
     * récupérées.
     *
     * Un générateur sans ballon n'a pas de stockage à placer : sa position
     * seule ne suffit pas à le déclarer non isolé.
     *
     * **Cette règle n'est plus départagée par le corpus, et le rester serait
     * la seule raison de la garder.** Elle l'était sur trente occurrences ;
     * elle est aujourd'hui à deux contre deux. Les quatre fichiers en
     * configuration « générateur et ballon hors volume chauffé » ont des
     * entrées identiques — type 76, usage 3, saisie 1 — et des sorties
     * opposées :
     *
     *   - 0,75 : `2501E1812848D` (3cl-2024.6.1.0), `2513E0192986F`
     *     (3cl_tribu_2024.6.1.0) ;
     *   - 0,90 : `2695E2427686K`, `2695E2608627R` (BBS_Slama_2025.11.1.0).
     *
     * Le seul discriminant est la **version du moteur éditeur**, pas une
     * donnée du fichier. Abandonner la règle — 0,90 pour tout multi-bâtiment —
     * a été mesuré en A/B isolé sur 541 cas : **exactement 0 écart de
     * différence**, deux cas gagnant 26 et 19 écarts, deux autres en perdant
     * 25 et 20. Voir TASK-K84.
     *
     * @spec-section 14.3
     * @spec-pages   95
     */
    private function sousStationHorsVolumeChauffe(DOMElement $node, NodeAccessor $accessor): bool
    {
        $stockage = $accessor->getIntOrNull('./donnee_entree/enum_type_stockage_ecs_id', $node);
        $volume = $accessor->getFloatOrNull('./donnee_entree/volume_stockage', $node) ?? 0.0;
        if ($stockage === null || $stockage === self::STOCKAGE_SANS_BALLON || $volume <= 0.0) {
            return false;
        }

        return $accessor->getIntOrNull('./donnee_entree/position_volume_chauffe', $node) === 0
            && $accessor->getIntOrNull('./donnee_entree/position_volume_chauffe_stockage', $node) === 0;
    }
}
