<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Collectif\ChauffageInstallationMultiplicity;
use CalculDpePHP\Collectif\EcsInstallationMultiplicity;
use CalculDpePHP\Collectif\GeneratedApartment;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Consommations de chauffage ou d'ECS d'un logement, ventilées par énergie et
 * ramenées à l'échelle de la sortie.
 *
 * Une `donnee_intermediaire` d'installation est à l'échelle du **bâtiment**
 * (ou de l'échantillon) ; la sortie, elle, porte sur le logement. Entre les
 * deux il y a un facteur par installation — `rdim` effectif, puis la clé de
 * répartition en mode ZONE — et **ce facteur n'est pas le même d'une
 * installation à l'autre** : sur `2675E2152874Y`, la chaudière gaz est ramenée
 * d'un facteur 0,0577 et les panneaux rayonnants d'un facteur 0,1154, soit le
 * double. Prendre les consos brutes des générateurs pour en tirer des parts
 * donne donc une répartition fausse — 56,9 / 43,1 au lieu de 39,8 / 60,2.
 *
 * Cette collecte est la source unique des deux blocs qui décrivent les mêmes
 * kilowattheures : `sortie_par_energie` et le `cout_ch` / `cout_ecs` de
 * {@see CoutCalculator}. Les dupliquer les faisait diverger.
 *
 * Mise à l'échelle identique à {@see EfConsoCalculator} (§17) :
 *   BAT  : conso × rdimEff (rdim, ou nbApt × ratio_virt / Σéchantillon pour
 *          une installation individuelle d'un DPE ZONE) ;
 *   ZONE : le total est ensuite ramené au logement via `cle_repartition_ch`
 *          ou `cle_repartition_ecs`.
 *
 * @spec-section 17
 * @spec-source  resources/specsplitted/17-collectif/02-appartement.md
 * @xml-input    installation_chauffage.{donnee_entree.{rdim, ratio_virtualisation,
 *                   cle_repartition_ch, nombre_logement_echantillon,
 *                   enum_methode_calcul_conso_id, enum_type_installation_id},
 *                   generateur_chauffage.{donnee_entree.enum_type_energie_id,
 *                   donnee_intermediaire.{conso_ch, conso_ch_depensier}}}
 * @tables       reference/tv_reseau_chaleur
 */
final class ConsoParEnergieCollector
{
    /**
     * @param string $installTag  `installation_chauffage` ou `installation_ecs`
     * @param string $genTag      `generateur_chauffage` ou `generateur_ecs`
     * @param string $consoField  `conso_ch` ou `conso_ecs`
     *
     * @return array{byEnergie: array<int, array{0: float, 1: float}>, reseauGes: array<int, float>}
     *         byEnergie : enum_type_energie_id ⇒ [conventionnel, dépensier] ;
     *         reseauGes : émissions des réseaux de chaleur, dont le facteur CO2
     *         dépend du réseau et ne peut donc pas être appliqué en aval.
     */
    public static function collecter(
        NodeAccessor $accessor,
        DOMElement $logement,
        string $installTag,
        string $genTag,
        string $consoField,
        CalculationContext $context,
    ): array {
        $isZone   = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logement) !== null;
        $nbreAppt = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $logement) ?? 1.0;

        $byEnergie = [];
        $reseauGesByEnergie = [];

        $consoDepField = $consoField . '_depensier';
        $collTag  = $installTag . '_collection';
        $isCh     = $installTag === 'installation_chauffage';
        $sumField = $isCh ? 'nombre_logement_echantillon' : 'nombre_logement';
        $cleField = $isCh ? 'cle_repartition_ch' : 'cle_repartition_ecs';

        foreach ($logement->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->nodeName !== $collTag) {
                continue;
            }

            // Σ(nombre_logement[_echantillon]) pour le rdim effectif ZONE individuel
            $sumEchantillon = 0.0;
            foreach ($child->childNodes as $install) {
                if ($install instanceof DOMElement && $install->nodeName === $installTag) {
                    $sumEchantillon += $accessor->getFloatOrNull('./donnee_entree/' . $sumField, $install) ?? 0.0;
                }
            }
            if ($sumEchantillon <= 0.0) {
                $sumEchantillon = 1.0;
            }

            foreach ($child->childNodes as $install) {
                if (!$install instanceof DOMElement || $install->nodeName !== $installTag) {
                    continue;
                }

                $methode     = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) ?? 1;
                $typeInstall = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id',    $install) ?? 1;
                $rdim        = $accessor->getFloatOrNull('./donnee_entree/rdim',                       $install) ?? 1.0;
                $ratioVirt   = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',       $install) ?? 1.0;

                if ($methode === 1) {
                    $rdimEff = $rdim;
                } elseif (!$isCh && $methode === 4 && $typeInstall === 1) {
                    $rdimEff = EcsInstallationMultiplicity::sampledOrNull($install, $accessor)
                        ?? ($nbreAppt * $ratioVirt / $sumEchantillon);
                } elseif ($isCh && ($multiplicite = ChauffageInstallationMultiplicity::sampledOrNull($install, $accessor)) !== null) {
                    $rdimEff = $multiplicite;
                } elseif ($typeInstall === 1) {
                    $rdimEff = $nbreAppt * $ratioVirt / $sumEchantillon;
                } else {
                    $rdimEff = $rdim;
                }
                $rdimEff = max(1e-9, $rdimEff);

                // ZONE : ramener du bâtiment au logement via la clé de répartition
                $scale = $rdimEff;
                if ($isZone) {
                    $cle = $accessor->getFloatOrNull('./donnee_entree/' . $cleField, $install);
                    if ($cle !== null && $cle > 0.0) {
                        if ($isCh) {
                            $cle = GeneratedApartment::heatingShare($logement, $accessor, $cle);
                        }
                        $scale *= $cle;
                    }
                }

                // Avec un seul générateur, son type d'énergie porte toute la
                // consommation de l'installation. C'est notamment nécessaire
                // au convecteur bi-jonction : le générateur ADEME publie Cch1
                // (circuit de base), tandis que l'installation et ses sorties
                // portent Cch1+Cch2 (§9.9).
                $generators = $install->getElementsByTagName($genTag);
                if ($generators->length === 1) {
                    $gen = $generators->item(0);
                    if ($gen instanceof DOMElement) {
                        $eId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen) ?? 1;
                        $conso = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoField, $install) ?? 0.0) * $scale;
                        $consoDep = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoDepField, $install) ?? 0.0) * $scale;
                        $byEnergie[$eId] ??= [0.0, 0.0];
                        $byEnergie[$eId][0] += $conso;
                        $byEnergie[$eId][1] += $consoDep;
                        if ($eId === 8) {
                            $factor = ReseauChaleurFactorResolver::resolve($gen, $accessor, $context);
                            $reseauGesByEnergie[$eId] = ($reseauGesByEnergie[$eId] ?? 0.0) + $conso * $factor;
                        }
                        continue;
                    }
                }

                foreach ($install->childNodes as $genColl) {
                    if (!$genColl instanceof DOMElement) {
                        continue;
                    }
                    foreach ($genColl->childNodes as $gen) {
                        if (!$gen instanceof DOMElement || $gen->nodeName !== $genTag) {
                            continue;
                        }
                        $eId      = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen) ?? 1;
                        $conso    = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoField,    $gen) ?? 0.0) * $scale;
                        $consoDep = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoDepField, $gen) ?? 0.0) * $scale;
                        if (!isset($byEnergie[$eId])) {
                            $byEnergie[$eId] = [0.0, 0.0];
                        }
                        $byEnergie[$eId][0] += $conso;
                        $byEnergie[$eId][1] += $consoDep;
                        if ($eId === 8) {
                            $factor = ReseauChaleurFactorResolver::resolve($gen, $accessor, $context);
                            $reseauGesByEnergie[$eId] = ($reseauGesByEnergie[$eId] ?? 0.0) + $conso * $factor;
                        }
                    }
                }
            }
        }

        return ['byEnergie' => $byEnergie, 'reseauGes' => $reseauGesByEnergie];
    }
}
