<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Tarification des consommations pour les frais annuels d'énergie du DPE.
 *
 * Service partagé par `CoutCalculator` (bloc `<sortie><cout>`) et
 * `SortieParEnergieAggregator` (bloc `<sortie_par_energie>`), qui doivent
 * évidemment appliquer le même barème : les deux blocs décrivent les mêmes
 * euros, vus par usage puis par énergie.
 *
 * Trois règles, détaillées dans le doc-block de `CoutCalculator` :
 *
 * 1. le barème dépend de `date_etablissement_dpe` ;
 * 2. la tranche de l'électricité et du gaz porte sur le total de l'énergie,
 *    pas sur chaque usage pris isolément ;
 * 3. la tranche s'apprécie par abonnement — un par installation collective,
 *    un par logement pour les usages individuels.
 *
 * L'annexe tarifaire l'écrit :
 *
 *   « Pour chaque énergie, les frais annuels sont établis à partir de la
 *     formule de la plage de consommation correspondante, sans effet cumulatif
 *     des tranches précédentes. »
 *   « Abonnement individuel : la consommation considérée est celle de
 *     l'appartement seul. »
 *   « Abonnement collectif : la consommation de gaz naturel ou d'électricité à
 *     prendre en compte pour la détermination du prix du kWh est celle de
 *     l'ensemble de l'immeuble. »
 *   « Les frais annuels par type d'énergie et par usage sont obtenus en
 *     multipliant la consommation d'énergie finale pour ce type d'énergie et
 *     cet usage par le prix moyen du kWh. »
 *
 * Sur un DPE d'appartement desservi par une installation collective, la
 * consommation de l'immeuble s'estime en multipliant celle de l'appartement
 * par le rapport des surfaces habitables.
 *
 * @spec-section Annexe 7 (prix des énergies)
 * @spec-source  https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000044202205
 * @spec-source  https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000049446315
 * @tables       reference/tv_prix_energie
 */
final class PrixEnergie
{
    private const TABLE = 'reference/tv_prix_energie';

    /** `électricité` et `électricité d'origine renouvelable utilisée dans le bâtiment`. */
    public const ELEC_IDS = [1, 12];

    public const GAZ_NATUREL_ID = 2;

    /** Préfixe des clés de regroupement des énergies à prix unique. */
    private const KWH_PREFIX = 'kwh:';

    /**
     * `enum_methode_application_dpe_log_id` des DPE immeuble collectif : seuls
     * ceux-là décrivent plusieurs logements à la fois.
     */
    private const METHODES_DPE_IMMEUBLE = [6, 7, 8, 9, 26, 27, 28, 29, 30];

    /** `enum_type_installation_id` = 2 : installation collective. */
    private const INSTALLATION_COLLECTIVE = 2;

    /** @param array<string, mixed> $bareme */
    private function __construct(
        private readonly array $bareme,
        private readonly float $nbLogements,
        private readonly float $facteurImmeuble,
        public readonly bool $chauffageCollectif,
        public readonly bool $ecsCollectif,
    ) {
    }

    public static function pour(DOMElement $logement, NodeAccessor $accessor, CalculationContext $context): self
    {
        $estImmeuble = self::estDpeImmeuble($accessor, $logement);

        return new self(
            self::bareme($accessor, $context),
            self::nombreLogements($accessor, $logement, $estImmeuble),
            self::facteurImmeuble($accessor, $logement, $estImmeuble),
            self::aUneInstallationCollective($accessor, $logement, 'installation_chauffage'),
            self::aUneInstallationCollective($accessor, $logement, 'installation_ecs'),
        );
    }

    /**
     * Tarife un panier de postes. Le prix unitaire est établi une fois par
     * couple (énergie, abonnement), sur le total du panier correspondant, puis
     * appliqué à chaque poste au prorata de sa consommation.
     *
     * @param array<string, array{0: int, 1: float, 2: bool}> $postes clé ⇒ [énergie, conso kWh, collectif]
     * @return array<string, float> clé ⇒ coût €
     */
    public function tarifer(array $postes): array
    {
        $totaux = [];
        foreach ($postes as [$energieId, $conso, $collectif]) {
            $key = $this->abonnementKey($energieId, $collectif);
            $totaux[$key] = ($totaux[$key] ?? 0.0) + $conso;
        }

        $prix = [];
        foreach ($totaux as $key => $total) {
            [$energieKey, $collectif] = $this->splitAbonnementKey($key);
            $prix[$key] = $collectif
                // Abonnement collectif : la tranche s'apprécie sur la
                // consommation de l'immeuble entier.
                ? $this->prixUnitaire($energieKey, $total * $this->facteurImmeuble, 1.0)
                // Abonnement individuel : sur celle d'un logement.
                : $this->prixUnitaire($energieKey, $total, $this->nbLogements);
        }

        $couts = [];
        foreach ($postes as $tag => [$energieId, $conso, $collectif]) {
            $couts[$tag] = $conso * $prix[$this->abonnementKey($energieId, $collectif)];
        }

        return $couts;
    }

    public static function estElectricite(int $energieId): bool
    {
        return in_array($energieId, self::ELEC_IDS, true);
    }

    /**
     * Prix moyen du kWh (€) pour une énergie donnée.
     *
     * Pour l'électricité et le gaz naturel, le barème donne un coût annuel
     * `terme_fixe + prix_kwh × Cef` où Cef est la consommation **d'un
     * abonnement** : on détermine la tranche sur la consommation ramenée à un
     * abonnement, puis on repasse en prix unitaire pour l'appliquer au total.
     *
     * @spec-formula Annexe7-prix-unitaire
     */
    private function prixUnitaire(string $energieKey, float $consoTotale, float $nbAbonnements): float
    {
        if ($consoTotale <= 0.0) {
            return 0.0;
        }

        if ($energieKey === 'electricite' || $energieKey === 'gaz_naturel') {
            $parAbonnement = $consoTotale / max(1.0, $nbAbonnements);

            return $this->coutParTranche($this->bareme[$energieKey], $parAbonnement) / $parAbonnement;
        }

        // Les autres énergies ont un prix du kWh unique, sans abonnement.
        $id = (int) substr($energieKey, strlen(self::KWH_PREFIX));

        return (float) ($this->bareme['kwh'][$id] ?? 0.0);
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

    /** Clé de regroupement d'un poste : énergie + nature de l'abonnement. */
    private function abonnementKey(int $energieId, bool $collectif): string
    {
        return ($collectif ? 'C|' : 'I|') . $this->energieKey($energieId);
    }

    /** @return array{0: string, 1: bool} */
    private function splitAbonnementKey(string $key): array
    {
        return [substr($key, 2), str_starts_with($key, 'C|')];
    }

    /**
     * Clé d'une énergie : `electricite`, `gaz_naturel`, ou l'identifiant XSD
     * préfixé pour les énergies à prix unique — une clé purement numérique
     * serait convertie en int par PHP dans les tableaux de regroupement.
     */
    private function energieKey(int $energieId): string
    {
        if (self::estElectricite($energieId)) {
            return 'electricite';
        }
        if ($energieId === self::GAZ_NATUREL_ID) {
            return 'gaz_naturel';
        }

        return self::KWH_PREFIX . $energieId;
    }

    /**
     * Barème en vigueur à la date d'établissement du DPE. À défaut de date
     * lisible, le barème le plus récent s'applique.
     *
     * @return array<string, mixed>
     */
    private static function bareme(NodeAccessor $accessor, CalculationContext $context): array
    {
        /** @var list<array<string, mixed>> $baremes */
        $baremes = $context->tables->load(self::TABLE);
        $date = $accessor->getStringOrNull('//administratif/date_etablissement_dpe');

        if ($date !== null) {
            foreach ($baremes as $bareme) {
                $to = $bareme['valid_to'];
                if ($date >= (string) $bareme['valid_from'] && ($to === null || $date <= (string) $to)) {
                    return $bareme;
                }
            }
        }

        return $baremes[array_key_last($baremes)];
    }

    /**
     * Un DPE immeuble collectif décrit plusieurs logements à la fois ; tous les
     * autres périmètres n'en décrivent qu'un, y compris l'appartement généré à
     * partir des données de l'immeuble — où `nombre_appartement` renseigne la
     * taille du bâtiment, pas la portée du DPE.
     */
    private static function estDpeImmeuble(NodeAccessor $accessor, DOMElement $logement): bool
    {
        $methode = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logement);

        return $methode !== null && in_array($methode, self::METHODES_DPE_IMMEUBLE, true);
    }

    private static function nombreLogements(NodeAccessor $accessor, DOMElement $logement, bool $estImmeuble): float
    {
        if (!$estImmeuble) {
            return 1.0;
        }

        $n = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $logement);

        return ($n === null || $n < 1.0) ? 1.0 : $n;
    }

    /**
     * Facteur de passage de la consommation décrite à celle de l'immeuble, pour
     * les usages sur abonnement collectif.
     *
     * Sur un DPE immeuble la sortie porte déjà sur le bâtiment : facteur 1. Sur
     * un DPE d'appartement, l'annexe demande d'estimer la consommation de
     * l'immeuble par le rapport des surfaces habitables.
     */
    private static function facteurImmeuble(NodeAccessor $accessor, DOMElement $logement, bool $estImmeuble): float
    {
        if ($estImmeuble) {
            return 1.0;
        }

        $immeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement);
        $logementSh = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logement);

        if ($immeuble === null || $logementSh === null || $logementSh <= 0.0 || $immeuble <= $logementSh) {
            return 1.0;
        }

        return $immeuble / $logementSh;
    }

    /** Au moins une installation du type donné est-elle collective ? */
    private static function aUneInstallationCollective(NodeAccessor $accessor, DOMElement $logement, string $installation): bool
    {
        foreach ($logement->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->nodeName !== $installation . '_collection') {
                continue;
            }
            foreach ($child->childNodes as $inst) {
                if (!$inst instanceof DOMElement || $inst->nodeName !== $installation) {
                    continue;
                }
                if ($accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $inst) === self::INSTALLATION_COLLECTIVE) {
                    return true;
                }
            }
        }

        return false;
    }
}
