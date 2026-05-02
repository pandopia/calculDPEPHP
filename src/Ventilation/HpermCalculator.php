<?php

declare(strict_types=1);

namespace CalculDpe\Ventilation;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Coefficient Hperm de déperdition par infiltrations liées au vent — §4 p.38-40.
 *
 * Formule :
 *   Hperm = 0,34 × Qvinf
 *
 *   Qvinf = (Hsp × Sh × n50 × e) / (1 + (f × (Qvasouf − Qvarep) / (Hsp × n50))²)
 *
 *   n50 = Q4Pa / ((4/50)^(2/3) × Hsp × Sh)
 *
 *   Q4Pa = Q4Paconv/m² × Sdep + 0,45 × Smeaconv × Sh
 *
 *   e, f = coefficients de protection (§4 p.39) :
 *     Plusieurs façades exposées (plusieurs_facade_exposee=1) : e=0,07, f=15
 *     Une seule façade exposée                                 : e=0,02, f=20
 *
 *   Sdep = Σ(surface_paroi_opaque mur) + Σ(surface_paroi_opaque plancher_haut)
 *          + Σ(surface_totale_baie baie_vitree) + Σ(surface_porte porte)  [hors plancher_bas]
 *
 * La formule open3cl (bug_for_bug_compat avec la méthode officielle) est :
 *   Qvinf = (Hsp × Sh × n50 × e) / (1 + (f/e) × (dQ/(Hsp×n50))²)
 * Le dénominateur contient f/e, PAS f² (erreur initiale dans notre stub).
 *
 * @spec-section 4
 * @spec-pages 38-40
 * @spec-source resources/specsplitted/04-renouvellement-air/00-calcul.md
 * @xml-input  ventilation.donnee_entree.{enum_type_ventilation_id, surface_ventile, plusieurs_facade_exposee}
 * @xml-input  ventilation.donnee_intermediaire.q4pa_conv
 * @xml-input  ancestor::logement.{hsp}
 * @xml-input  ancestor::logement.enveloppe.{mur, plancher_haut, baie_vitree, porte}
 * @xml-output ventilation.donnee_intermediaire.hperm
 * @depends-on \CalculDpe\Ventilation\Q4PaConvCalculator
 * @tables tv_debits_ventilation
 */
final class HpermCalculator implements CalculatorInterface
{
    private const EXPONENT = 2 / 3;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [Q4PaConvCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'ventilation';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('ventilation sans <donnee_entree>.');
        }
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);

        $typeId = $accessor->getIntOrNull('./enum_type_ventilation_id', $entree);
        $sh = $accessor->getFloatOrNull('./surface_ventile', $entree);
        $q4paConv = $accessor->getFloatOrNull('./q4pa_conv', $intermediaire);
        if ($typeId === null || $sh === null || $q4paConv === null) {
            return;
        }

        $debits = $context->tables->load('ventilation/tv_debits_ventilation');
        $row = $debits[$typeId] ?? null;
        if ($row === null) {
            return;
        }

        $qvarep = (float)$row['qvarep'];
        $qvasouf = (float)$row['qvasouf'];
        $smea = (float)$row['smea'];

        // Coefficients de protection
        $plusieursF = $accessor->getIntOrNull('./plusieurs_facade_exposee', $entree);
        [$e, $f] = $plusieursF === 1 ? [0.07, 15.0] : [0.02, 20.0];

        // Hsp depuis le niveau logement (ancêtre direct du logement)
        $hsp = $this->resolveHsp($node, $accessor);
        if ($hsp === null || $hsp <= 0.0) {
            return;
        }

        // Sdep = somme des parois déperditives hors plancher_bas
        $sdep = $this->computeSdep($node, $accessor);
        if ($sdep <= 0.0) {
            return;
        }

        // Q4Pa = Q4Paenv + 0,45 × Smea × Sh
        $q4paEnv = $q4paConv * $sdep;
        $q4pa = $q4paEnv + 0.45 * $smea * $sh;

        // n50 = Q4Pa / ((4/50)^(2/3) × Hsp × Sh)
        $n50 = $q4pa / (((4 / 50) ** self::EXPONENT) * $hsp * $sh);

        // Qvinf — formule open3cl : dénominateur = 1 + (f/e) × (dQ/(Hsp×n50))²
        $dq = $qvasouf - $qvarep;
        $hspN50 = $hsp * $n50;
        $ratio = ($hspN50 > 0.0) ? ($dq / $hspN50) : 0.0;
        $denom = 1.0 + ($e > 0.0 ? ($f / $e) : 0.0) * ($ratio * $ratio);

        $qvinf = ($denom > 0.0) ? ($hsp * $sh * $n50 * $e / $denom) : 0.0;
        $hperm = 0.34 * $qvinf;

        $accessor->setChildValue($intermediaire, 'hperm', $hperm);
    }

    /**
     * Cherche `hsp` dans le nœud logement ancêtre du nœud ventilation.
     * Chemin XML : ventilation → ventilation_collection → logement → hsp
     */
    private function resolveHsp(DOMElement $ventilation, NodeAccessor $accessor): ?float
    {
        // Remontée manuelle : ventilation → ventilation_collection → logement
        $parent = $ventilation->parentNode; // ventilation_collection
        if ($parent !== null) {
            $parent = $parent->parentNode; // logement
        }
        if (!$parent instanceof DOMElement) {
            return null;
        }
        return $accessor->getFloatOrNull('./caracteristique_generale/hsp', $parent)
            ?? $accessor->getFloatOrNull('./hsp', $parent);
    }

    /**
     * Calcule Sdep = surface des parois déperditives hors plancher bas (m²).
     *
     * §4 p.39 : Sdep = Σ(mur) + Σ(plancher_haut) + Σ(baie_vitree) + Σ(porte), b>0.
     * Murs "local non déperditif" (adjacence_id=22) exclus.
     */
    private function computeSdep(DOMElement $ventilation, NodeAccessor $accessor): float
    {
        // Remontée : ventilation → ventilation_collection → logement → enveloppe
        $logement = $ventilation->parentNode?->parentNode;
        if (!$logement instanceof DOMElement) {
            return 0.0;
        }
        $enveloppe = null;
        foreach ($logement->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'enveloppe') {
                $enveloppe = $child;
                break;
            }
        }
        if ($enveloppe === null) {
            return 0.0;
        }

        $sdep = 0.0;

        // Murs : surface_paroi_opaque — filtre b>0 et exclusion "local non déperditif"
        foreach ($enveloppe->getElementsByTagName('mur_collection') as $coll) {
            foreach ($coll->getElementsByTagName('mur') as $paroi) {
                if ($this->isNonDeperditif($paroi, $accessor)) {
                    continue;
                }
                $s = $accessor->getFloatOrNull('./donnee_entree/surface_paroi_opaque', $paroi);
                $sdep += $s ?? 0.0;
            }
        }

        // Planchers hauts : surface_paroi_opaque — filtre b>0
        foreach ($enveloppe->getElementsByTagName('plancher_haut_collection') as $coll) {
            foreach ($coll->getElementsByTagName('plancher_haut') as $ph) {
                if ($this->bIsZero($ph, $accessor)) {
                    continue;
                }
                $s = $accessor->getFloatOrNull('./donnee_entree/surface_paroi_opaque', $ph);
                $sdep += $s ?? 0.0;
            }
        }

        // Baies vitrées : surface_totale_baie — filtre b>0
        foreach ($enveloppe->getElementsByTagName('baie_vitree_collection') as $coll) {
            foreach ($coll->getElementsByTagName('baie_vitree') as $baie) {
                if ($this->bIsZero($baie, $accessor)) {
                    continue;
                }
                $s = $accessor->getFloatOrNull('./donnee_entree/surface_totale_baie', $baie);
                $sdep += $s ?? 0.0;
            }
        }

        // Portes : surface_porte — filtre b>0
        foreach ($enveloppe->getElementsByTagName('porte_collection') as $coll) {
            foreach ($coll->getElementsByTagName('porte') as $porte) {
                if ($this->bIsZero($porte, $accessor)) {
                    continue;
                }
                $s = $accessor->getFloatOrNull('./donnee_entree/surface_porte', $porte);
                $sdep += $s ?? 0.0;
            }
        }

        return $sdep;
    }

    /** Vrai si la paroi est non-déperditive (b=0 calculé, ou type adjacence "local non déperditif"). */
    private function isNonDeperditif(DOMElement $paroi, NodeAccessor $accessor): bool
    {
        $b = $accessor->getFloatOrNull('./donnee_intermediaire/b', $paroi);
        if ($b !== null && $b === 0.0) {
            return true;
        }
        // adjacence_id=22 = "Local non déperditif (local à usage d'habitation chauffé)"
        $adj = $accessor->getIntOrNull('./donnee_entree/enum_type_adjacence_id', $paroi);
        return $adj === 22;
    }

    /** Vrai si b est explicitement 0 dans donnee_intermediaire. */
    private function bIsZero(DOMElement $paroi, NodeAccessor $accessor): bool
    {
        $b = $accessor->getFloatOrNull('./donnee_intermediaire/b', $paroi);
        return $b !== null && $b === 0.0;
    }
}
