<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Logique partagée entre toutes les stratégies de chauffage (§9.x).
 *
 * Chaque stratégie appelle `computeAndWrite(besoin, node, context)` avec
 * le besoin effectif (fraction de Bch) alloué à cette installation.
 */
trait StrategieComputeTrait
{
    /**
     * Calcule conso_ch pour un besoin donné et l'écrit dans l'installation
     * et dans chaque générateur.
     *
     * @param float $besoin Besoin alloué à cette installation (kWh).
     */
    private function computeAndWrite(float $besoin, float $besoinDep, DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $gv         = (float)$context->get('chauffage.gv', 1.0);
        $shImmeuble = $this->getShImmeuble($accessor, $node);
        $hsp        = $this->getHsp($accessor, $node);

        $g   = ($hsp * $shImmeuble) > 0.0 ? $gv / ($hsp * $shImmeuble) : 1.0;
        $i0  = $this->weightedEmetteurFloat($accessor, $node, 'i0') ?? 1.0;
        $int = $i0 / (1.0 + 0.1 * ($g - 1.0));

        $re = $this->weightedEmetteurFloat($accessor, $node, 'rendement_emission')    ?? 1.0;
        $rd = $this->weightedEmetteurFloat($accessor, $node, 'rendement_distribution') ?? 1.0;
        $rr = $this->weightedEmetteurFloat($accessor, $node, 'rendement_regulation')  ?? 1.0;
        $rg = $this->weightedGenerateurFloat($accessor, $node, 'rendement_generation') ?? 1.0;

        $denom      = max(1e-9, $rg * $re * $rd * $rr);
        $consoCh    = $besoin    * $int / $denom;
        $consoChDep = $besoinDep * $int / $denom;

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch',           $besoin);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $besoinDep);
        $accessor->setChildValue($di, 'conso_ch',            $consoCh);
        $accessor->setChildValue($di, 'conso_ch_depensier',  $consoChDep);

        $genCollection = $this->getChildByTag($node, 'generateur_chauffage_collection');
        if ($genCollection !== null) {
            foreach ($genCollection->childNodes as $gen) {
                if (!($gen instanceof DOMElement) || $gen->nodeName !== 'generateur_chauffage') {
                    continue;
                }
                $genDi = $accessor->ensureDonneeIntermediaire($gen);
                $accessor->setChildValue($genDi, 'conso_ch',           $consoCh);
                $accessor->setChildValue($genDi, 'conso_ch_depensier', $consoChDep);
            }
        }
    }

    /**
     * Répartit la consommation entre les branches d'une même installation,
     * identifiées par `enum_lien_generateur_emetteur_id` (§9.3 à §9.5).
     *
     * Ces configurations décrivent un émetteur de base et un émetteur d'appoint
     * — ou de salle de bains — qui ont chacun leur générateur, donc leur propre
     * rendement de génération, leur propre intermittence et leurs propres
     * rendements d'émission, de distribution et de régulation. Moyenner le tout
     * sur l'installation efface précisément la distinction que ces paragraphes
     * établissent : sur un poêle bois à 0,5 de rendement doublé d'un convecteur
     * électrique à 1, la moyenne ne décrit aucun des deux.
     *
     * `besoin_ch` reste celui de l'installation entière : les parts ne portent
     * que sur les consommations, conformément aux formules
     * `Cch_i = part_i × Bch × INT_i × Ich_i`.
     *
     * @param array<int, float> $partsParLien enum_lien_generateur_emetteur_id ⇒ part de Bch
     * @return bool faux si l'installation ne porte pas ces branches, l'appelant
     *              retombant alors sur le calcul moyenné
     */
    private function computeAndWriteParLien(
        array $partsParLien,
        float $besoin,
        float $besoinDep,
        DOMElement $node,
        CalculationContext $context,
    ): bool {
        $accessor = new NodeAccessor($context->document);

        $emetteursParLien = $this->groupByLien($accessor, $node, 'emetteur_chauffage_collection', 'emetteur_chauffage');
        $generateursParLien = $this->groupByLien($accessor, $node, 'generateur_chauffage_collection', 'generateur_chauffage');

        $liensUtiles = array_intersect(array_keys($partsParLien), array_keys($generateursParLien));
        if (count($liensUtiles) < 2) {
            return false;
        }

        $gv = (float)$context->get('chauffage.gv', 1.0);
        $shImmeuble = $this->getShImmeuble($accessor, $node);
        $hsp = $this->getHsp($accessor, $node);
        $g = ($hsp * $shImmeuble) > 0.0 ? $gv / ($hsp * $shImmeuble) : 1.0;

        $consoTotale = 0.0;
        $consoTotaleDep = 0.0;
        foreach ($liensUtiles as $lien) {
            $emetteurs = $emetteursParLien[$lien] ?? [];
            $generateurs = $generateursParLien[$lien];
            $part = $partsParLien[$lien];

            $i0 = $this->moyennePonderee($accessor, $emetteurs, 'i0') ?? 1.0;
            $int = $i0 / (1.0 + 0.1 * ($g - 1.0));
            $re = $this->moyennePonderee($accessor, $emetteurs, 'rendement_emission') ?? 1.0;
            $rd = $this->moyennePonderee($accessor, $emetteurs, 'rendement_distribution') ?? 1.0;
            $rr = $this->moyennePonderee($accessor, $emetteurs, 'rendement_regulation') ?? 1.0;
            $rg = $this->moyenneSimple($accessor, $generateurs, 'rendement_generation') ?? 1.0;

            $denom = max(1e-9, $rg * $re * $rd * $rr);
            $conso = $part * $besoin * $int / $denom;
            $consoDep = $part * $besoinDep * $int / $denom;
            $consoTotale += $conso;
            $consoTotaleDep += $consoDep;

            $parGenerateur = max(1, count($generateurs));
            foreach ($generateurs as $generateur) {
                $genDi = $accessor->ensureDonneeIntermediaire($generateur);
                $accessor->setChildValue($genDi, 'conso_ch', $conso / $parGenerateur);
                $accessor->setChildValue($genDi, 'conso_ch_depensier', $consoDep / $parGenerateur);
            }
        }

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch', $besoin);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $besoinDep);
        $accessor->setChildValue($di, 'conso_ch', $consoTotale);
        $accessor->setChildValue($di, 'conso_ch_depensier', $consoTotaleDep);

        return true;
    }

    /**
     * @return array<int, list<DOMElement>> enum_lien_generateur_emetteur_id ⇒ nœuds
     */
    private function groupByLien(
        NodeAccessor $accessor,
        DOMElement $installNode,
        string $collection,
        string $tag,
    ): array {
        $col = $this->getChildByTag($installNode, $collection);
        if ($col === null) {
            return [];
        }
        $groupes = [];
        foreach ($col->childNodes as $child) {
            if (!($child instanceof DOMElement) || $child->nodeName !== $tag) {
                continue;
            }
            $lien = $accessor->getIntOrNull('./donnee_entree/enum_lien_generateur_emetteur_id', $child);
            if ($lien === null) {
                continue;
            }
            $groupes[$lien][] = $child;
        }

        return $groupes;
    }

    /**
     * Moyenne pondérée par la surface chauffée des émetteurs d'une branche.
     *
     * @param list<DOMElement> $emetteurs
     */
    private function moyennePonderee(NodeAccessor $accessor, array $emetteurs, string $field): ?float
    {
        $surfaceTotale = 0.0;
        $somme = 0.0;
        foreach ($emetteurs as $emetteur) {
            $valeur = $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $emetteur);
            if ($valeur === null) {
                continue;
            }
            $surface = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $emetteur) ?? 1.0;
            $surfaceTotale += $surface;
            $somme += $surface * $valeur;
        }

        return $surfaceTotale > 0.0 ? $somme / $surfaceTotale : null;
    }

    /** @param list<DOMElement> $generateurs */
    private function moyenneSimple(NodeAccessor $accessor, array $generateurs, string $field): ?float
    {
        $somme = 0.0;
        $n = 0;
        foreach ($generateurs as $generateur) {
            $valeur = $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $generateur);
            if ($valeur === null) {
                continue;
            }
            $somme += $valeur;
            $n++;
        }

        return $n > 0 ? $somme / $n : null;
    }

    /**
     * Position de ce nœud dans sa collection parent (1 = premier, 2 = second…).
     */
    private function positionInCollection(DOMElement $node): int
    {
        $pos  = 1;
        $prev = $node->previousElementSibling;
        while ($prev !== null) {
            if ($prev->nodeName === $node->nodeName) {
                $pos++;
            }
            $prev = $prev->previousElementSibling;
        }
        return $pos;
    }

    private function getShImmeuble(NodeAccessor $accessor, DOMElement $installNode): float
    {
        $logement = $installNode->parentNode?->parentNode;
        $sh = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement)
            ?? $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $installNode)
            ?? 1.0;
        return max(1.0, $sh);
    }

    private function getHsp(NodeAccessor $accessor, DOMElement $installNode): float
    {
        $logement = $installNode->parentNode?->parentNode;
        return $accessor->getFloatOrNull('./caracteristique_generale/hsp', $logement) ?? 2.5;
    }

    private function weightedEmetteurFloat(NodeAccessor $accessor, DOMElement $installNode, string $field): ?float
    {
        $col = $this->getChildByTag($installNode, 'emetteur_chauffage_collection');
        if ($col === null) {
            return null;
        }
        $totalSurface = 0.0;
        $weightedSum  = 0.0;
        foreach ($col->childNodes as $em) {
            if (!($em instanceof DOMElement) || $em->nodeName !== 'emetteur_chauffage') {
                continue;
            }
            $surface = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $em) ?? 1.0;
            $value   = $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $em);
            if ($value === null) {
                continue;
            }
            $totalSurface += $surface;
            $weightedSum  += $surface * $value;
        }
        return $totalSurface > 0.0 ? $weightedSum / $totalSurface : null;
    }

    private function weightedGenerateurFloat(NodeAccessor $accessor, DOMElement $installNode, string $field): ?float
    {
        $col = $this->getChildByTag($installNode, 'generateur_chauffage_collection');
        if ($col === null) {
            return null;
        }
        $count = 0;
        $sum   = 0.0;
        foreach ($col->childNodes as $gen) {
            if (!($gen instanceof DOMElement) || $gen->nodeName !== 'generateur_chauffage') {
                continue;
            }
            $value = $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $gen);
            if ($value === null) {
                continue;
            }
            $sum += $value;
            $count++;
        }
        return $count > 0 ? $sum / $count : null;
    }

    private function getChildByTag(DOMElement $parent, string $tag): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $tag) {
                return $child;
            }
        }
        return null;
    }
}
