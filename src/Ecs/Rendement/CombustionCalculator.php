<?php

declare(strict_types=1);

namespace CalculDpe\Ecs\Rendement;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement de génération ECS Rg (§14.1 p.93-95).
 *
 * Générateurs électriques (enum_type_energie_id = 1) : Rg = 1 (pas de pertes combustion).
 *
 * Chauffe-eau gaz instantané (§14.1.1) :
 *   Rg = 1 / (1/Rpn + 1790×QP0/Becs + 6970×Pveil/Becs)
 *
 * Chaudière gaz/fioul/bois mixte (§14.1.2) :
 *   Rg = 1 / (1/Rpn + (1790×QP0 + Qgw) / Becs + 6970×0.5×Pveil / Becs)
 *   (Qgw ≈ 0 pour ECS seul)
 *
 * Accumulateur gaz (§14.1.3) :
 *   Rg = 1 / (1/Rpn + (8592×QP0 + Qgw) / Becs + 6970×Pveil / Becs)
 *   avec QP0 = 1.5% × Pn (override §14.1.3)
 *
 * La table primaire est indexée par enum_type_generateur_ecs_id (comme open3cl).
 * Fallback : tv_generateur_combustion_id (méthode saisie 3/4/5 ou type 84).
 *
 * @spec-section 14.1
 * @spec-pages   93-95
 * @spec-source  resources/specsplitted/14-rendement-ecs-generateurs/01-combustion.md
 * @xml-input    generateur_ecs.donnee_entree.{enum_type_energie_id, enum_type_generateur_ecs_id, tv_generateur_combustion_id, enum_methode_saisie_carac_sys_id, pn, rpn, qp0, pveil}
 * @xml-output   generateur_ecs.donnee_intermediaire.{pn, qp0, rpn, rendement_generation}
 * @depends-on   \CalculDpe\Ecs\BesoinEcsCalculator, \CalculDpe\Enveloppe\EnveloppeAggregator, \CalculDpe\Ventilation\VentilationAggregator
 * @tables       ecs/tv_generateur_combustion_ecs
 */
final class CombustionCalculator implements CalculatorInterface
{
    /** Heures annuelles ECS hors vacances — §14.1 p.93 */
    private const H_ECS = 1790.0;

    /** Diviseur pour pertes veilleuse annuelles */
    private const H_VEIL = 6970.0;

    /** Diviseur pour accumulateur gaz §14.1.3 */
    private const H_ACC = 8592.0;

    /** enum_type_energie_id = 1 → électricité */
    private const ENERGIE_ELEC = 1;

    /** enum_type_energie_id = 8 → réseau de chauffage urbain (géré par ReseauChaleurCalculator) */
    private const ENERGIE_RESEAU_CHALEUR = 8;

    /**
     * enum_type_generateur_ecs_id → catégorie de formule.
     * 'ceg' = chauffe-eau gaz (§14.1.1, sans 0.5×pveil)
     * 'acc' = accumulateur gaz (§14.1.3, 8592×, qp0 overridé)
     * 'chau' = chaudière (§14.1.2, 0.5×pveil)
     */
    private const TYPE_CHAUFFE_EAU_GAZ = [63, 64, 65, 66, 67, 110, 111, 112, 113, 114];
    private const TYPE_ACCUMULATEUR_GAZ = [58, 59, 60, 61, 62, 105, 106, 107, 108, 109];

    /** Temperature de base Tbase par zone × classe altitude */
    private const TBASE = [
        1 => [1 => -9.5,  2 => -11.5, 3 => -13.5],
        2 => [1 => -6.5,  2 =>  -8.5, 3 => -10.5],
        3 => [1 => -3.5,  2 =>  -5.5, 3 =>  -7.5],
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpe\Ecs\BesoinEcsCalculator',
            '\CalculDpe\Enveloppe\EnveloppeAggregator',
            '\CalculDpe\Ventilation\VentilationAggregator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'generateur_ecs';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor  = new NodeAccessor($context->document);
        $energieId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $node);

        // Réseau de chaleur → rendement géré exclusivement par ReseauChaleurCalculator §14.3
        if ($energieId === self::ENERGIE_RESEAU_CHALEUR) {
            return;
        }

        // Générateurs électriques → Rg = 1
        if ($energieId === self::ENERGIE_ELEC) {
            $di = $this->ensureDi($context->document, $node);
            $accessor->setChildValue($di, 'rendement_generation', 1.0);
            $this->storeContext($node, $accessor, $context, 1.0);
            return;
        }

        // Caractéristiques combustion via enum_type_generateur_ecs_id (priorité open3cl)
        $typeEcsId = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $node);
        $methode   = $accessor->getIntOrNull('./donnee_entree/enum_methode_saisie_carac_sys_id', $node) ?? 1;

        [$pn, $rpn, $qp0, $pveil] = $this->resolveCarac($node, $accessor, $context, $typeEcsId, $methode);

        // Becs en Wh depuis installation parente
        $becsWh = $this->resolveBecsWh($node, $accessor, $context);

        $rg = 1.0;
        if ($becsWh > 0.0 && $rpn > 0.0) {
            // pveil est ignoré si pas de veilleuse (open3cl: di.pveil = 0 when !di.pveilleuse)
            $pveilActif = $this->hasPveilleuse($node, $accessor) ? $pveil : 0.0;

            if ($typeEcsId !== null && in_array($typeEcsId, self::TYPE_CHAUFFE_EAU_GAZ, true)) {
                // §14.1.1 Chauffe-eau gaz : 1790×QP0/Becs + 6970×Pveil/Becs
                $rg = 1.0 / (
                    1.0 / $rpn
                    + self::H_ECS * $qp0 / $becsWh
                    + self::H_VEIL * $pveilActif / $becsWh
                );
            } elseif ($typeEcsId !== null && in_array($typeEcsId, self::TYPE_ACCUMULATEUR_GAZ, true)) {
                // §14.1.3 Accumulateur gaz : 8592×QP0/Becs + 6970×Pveil/Becs
                $qp0Acc = 0.015 * $pn; // override §14.1.3
                $rg = 1.0 / (
                    1.0 / $rpn
                    + self::H_ACC * $qp0Acc / $becsWh
                    + self::H_VEIL * $pveilActif / $becsWh
                );
                $qp0 = $qp0Acc;
            } else {
                // §14.1.2 Chaudière : 1790×QP0/Becs + 0.5×6970×Pveil/Becs (Qgw≈0 pour ECS seul)
                $rg = 1.0 / (
                    1.0 / $rpn
                    + self::H_ECS * $qp0 / $becsWh
                    + 0.5 * $pveilActif / (self::H_VEIL * $becsWh)
                );
            }
        }

        $di = $this->ensureDi($context->document, $node);
        if ($pn > 0.0) {
            $accessor->setChildValue($di, 'pn',  $pn);
            $accessor->setChildValue($di, 'qp0', $qp0);
            $accessor->setChildValue($di, 'rpn', $rpn);
        }
        $accessor->setChildValue($di, 'rendement_generation', $rg);

        $this->storeContext($node, $accessor, $context, $rg);
    }

    /**
     * Lit (pn, rpn, qp0, pveil) depuis la table ECS ou depuis donnee_entree.
     * @return array{float, float, float, float} [pn_W, rpn, qp0_W, pveil_W]
     */
    private function resolveCarac(
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context,
        ?int $typeEcsId,
        int $methode
    ): array {
        // Valeurs saisies (méthodes 3/4/5 → utilise directement les champs saisis)
        if (in_array($methode, [3, 4, 5], true)) {
            return $this->resolveFromSaisie($node, $accessor);
        }

        // Forfait : lookup par enum_type_generateur_ecs_id (priorité comme open3cl)
        if ($typeEcsId !== null && $typeEcsId !== 84) {
            $result = $this->lookupByEcsTypeId($typeEcsId, $node, $accessor, $context);
            if ($result !== null) {
                return $result;
            }
        }

        // Fallback : lookup par tv_generateur_combustion_id (type 84 ou table ECS non couverte)
        $tvId = $accessor->getIntOrNull('./donnee_entree/tv_generateur_combustion_id', $node);
        if ($tvId !== null) {
            $result = $this->lookupByCombustionId($tvId, $node, $accessor, $context);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->resolveFromSaisie($node, $accessor);
    }

    /**
     * Lookup par enum_type_generateur_ecs_id dans la table ECS.
     * @return array{float, float, float, float}|null
     */
    private function lookupByEcsTypeId(
        int $typeEcsId,
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context
    ): ?array {
        $table = $context->tables->load('ecs/tv_generateur_combustion_ecs');
        $paliers = $table[$typeEcsId] ?? null;
        if ($paliers === null) {
            return null;
        }

        $ratioVirt = $this->getRatioVirtEcs($node, $accessor);
        $pnW  = $this->computePnW($node, $accessor, $context, $ratioVirt);
        $pnKw = $pnW / 1000.0;

        // Sélectionne le palier selon pn_max_kw
        $row = null;
        foreach ($paliers as $palier) {
            if ($pnKw <= $palier['pn_max_kw']) {
                $row = $palier;
                break;
            }
        }
        if ($row === null) {
            $row = end($paliers); // dernier palier si dépassement
        }

        // Chauffe-eau gaz : pn forfaitaire fixe (LICIEL), pas dérivé du GV.
        if (isset($row['pn_forfait_kw'])) {
            $pnW  = $row['pn_forfait_kw'] * 1000.0;
            $pnKw = $row['pn_forfait_kw'];
        }

        $rpn = $row['rpn'];
        if ($rpn instanceof \Closure) {
            $rpn = $rpn($pnKw > 0 ? $pnKw : 1.0);
        }

        // Pour installations collectives (sans pn_forfait), ramener à la part du logement.
        $hasPnSaisie   = $accessor->getFloatOrNull('./donnee_entree/pn', $node) !== null;
        $hasPnForfait  = isset($row['pn_forfait_kw']);
        $pnApartmentW  = ($hasPnForfait || $hasPnSaisie || $ratioVirt >= 1.0)
            ? $pnW
            : $pnW * $ratioVirt;

        $qp0Pct = $row['qp0_pct'];
        $qp0W   = $qp0Pct !== null ? $qp0Pct * $pnApartmentW : 0.0;
        $pveil  = (float)($row['pveil'] ?? 0.0) * ($hasPnForfait || $ratioVirt >= 1.0 ? 1.0 : $ratioVirt);

        return [$pnApartmentW, (float)$rpn, $qp0W, $pveil];
    }

    /**
     * Lookup par tv_generateur_combustion_id (table CH, Closure ou array).
     * Pour les installations collectives (ratio_virt < 1), Pn est évalué à l'échelle du bâtiment
     * (plaffonné à 400 kW pour gaz/fioul), puis ramené à la part du logement.
     * @return array{float, float, float, float}|null
     */
    private function lookupByCombustionId(
        int $tvId,
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context
    ): ?array {
        $table = $context->tables->load('chauffage/tv_generateur_combustion');
        $entry = $table[$tvId] ?? null;
        if ($entry === null) {
            return null;
        }

        if ($entry instanceof \Closure) {
            $hasPnSaisie = $accessor->getFloatOrNull('./donnee_entree/pn', $node) !== null;
            $ratioVirt   = $this->getRatioVirtEcs($node, $accessor);
            $pnW         = $this->computePnW($node, $accessor, $context, $ratioVirt);
            $ventose     = $accessor->getIntOrNull('./donnee_entree/presence_ventouse', $node) ?? 0;
            $e = [0 => 2.5, 1 => 1.75][$ventose] ?? 2.5;
            $f = [0 => -0.8, 1 => -0.55][$ventose] ?? -0.8;
            // pnW = Pn_bâtiment (déjà plaffonné) pour installations collectives sans pn saisi
            $row = $entry($pnW / 1000.0, $e, $f);
            if (!$hasPnSaisie && $ratioVirt > 0.0 && $ratioVirt < 1.0) {
                $row['pn']    = $pnW * $ratioVirt;
                $row['qp0']   = ($row['qp0']  ?? 0.0) * $ratioVirt;
                $row['pveil'] = ($row['pveil'] ?? 0.0) * $ratioVirt;
            }
        } else {
            $row = $entry;
        }

        return [
            (float)($row['pn']    ?? 0.0),
            (float)($row['rpn']   ?? 0.0),
            (float)($row['qp0']   ?? 0.0),
            (float)($row['pveil'] ?? 0.0),
        ];
    }

    /** Lit ratio_virtualisation depuis l'installation ECS parente. */
    private function getRatioVirtEcs(DOMElement $node, NodeAccessor $accessor): float
    {
        $inst = $this->findParentInstallation($node);
        if ($inst === null) {
            return 1.0;
        }
        return $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation', $inst) ?? 1.0;
    }

    /**
     * @return array{float, float, float, float}
     */
    private function resolveFromSaisie(DOMElement $node, NodeAccessor $accessor): array
    {
        $pn    = $accessor->getFloatOrNull('./donnee_entree/pn',    $node) ?? 0.0;
        $rpn   = $accessor->getFloatOrNull('./donnee_entree/rpn',   $node) ?? 0.0;
        $qp0   = $accessor->getFloatOrNull('./donnee_entree/qp0',   $node) ?? 0.0;
        $pveil = $accessor->getFloatOrNull('./donnee_entree/pveil', $node) ?? 0.0;
        return [$pn, $rpn, $qp0, $pveil];
    }

    /**
     * Calcule Pn en W depuis GV ou depuis donnee_entree.pn.
     * Pour les installations collectives (ratio_virt < 1), retourne Pn_bâtiment (plaffonné).
     * La mise à l'échelle pn_logement = pn_bâtiment × ratio_virt est faite par l'appelant.
     */
    private function computePnW(
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context,
        float $ratioVirt = 1.0
    ): float {
        $pnEntry = $accessor->getFloatOrNull('./donnee_entree/pn', $node);
        if ($pnEntry !== null && $pnEntry > 0.0) {
            return $pnEntry;
        }

        $dpParois = (float)$context->get('enveloppe.dp_parois',        0.0);
        $dpPT     = (float)$context->get('enveloppe.dp_pont_thermique', 0.0);
        $hvent    = (float)$context->get('ventilation.hvent',           0.0);
        $hperm    = (float)$context->get('ventilation.hperm',           0.0);
        $gv       = $dpParois + $dpPT + $hvent + $hperm;

        if ($gv <= 0.0) {
            return 0.0;
        }

        $gvEffectif = ($ratioVirt > 0.0 && $ratioVirt < 1.0) ? $gv / $ratioVirt : $gv;

        $zoneGroupe = CalculationContext::zoneGroupeFromId($context->zoneClimatique);
        $zoneIdx    = match($zoneGroupe) { 'H1' => 1, 'H2' => 2, 'H3' => 3, default => 1 };
        $altId      = $context->classeAltitude !== null ? (int)$context->classeAltitude : 1;
        $tbase      = self::TBASE[$zoneIdx][$altId] ?? -9.5;

        $pnBuilding = (1.2 * $gvEffectif * (19.0 - $tbase)) / (0.95 ** 3);

        // Plafond 400 kW pour chaudières gaz/fioul collectives
        if ($ratioVirt < 1.0) {
            $pnBuilding = min($pnBuilding, 400000.0);
        }

        return $pnBuilding;
    }

    /** Vérifie si la veilleuse est présente (open3cl: pveil=0 si absence). */
    private function hasPveilleuse(DOMElement $node, NodeAccessor $accessor): bool
    {
        // Cherche dans le parent installation
        $inst = $this->findParentInstallation($node);
        if ($inst !== null) {
            $pveil = $accessor->getIntOrNull('./donnee_entree/presence_veilleuse', $inst);
            if ($pveil !== null) {
                return $pveil === 1;
            }
        }
        // Fallback : présence de pveilleuse dans donnee_entree du générateur
        $pveil = $accessor->getIntOrNull('./donnee_entree/presence_veilleuse', $node);
        return $pveil === 1;
    }

    private function resolveBecsWh(DOMElement $genNode, NodeAccessor $accessor, CalculationContext $context): float
    {
        $inst = $this->findParentInstallation($genNode);
        if ($inst !== null) {
            $becs = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs', $inst);
            if ($becs !== null) {
                return $becs * 1000.0;
            }
        }
        return (float)$context->get('ecs.besoin_ecs', 0.0) * 1000.0;
    }

    private function storeContext(DOMElement $node, NodeAccessor $accessor, CalculationContext $context, float $rg): void
    {
        $ref = $accessor->getStringOrNull('./donnee_entree/reference', $node) ?? '';
        $context->set('ecs.rendement_generation.' . $ref, $rg);
        $context->set('ecs.rendement_generation', $rg);
    }

    private function findParentInstallation(DOMElement $node): ?DOMElement
    {
        $cur = $node->parentNode;
        while ($cur !== null) {
            if ($cur instanceof DOMElement && $cur->nodeName === 'installation_ecs') {
                return $cur;
            }
            $cur = $cur->parentNode;
        }
        return null;
    }

    private function ensureDi(\DOMDocument $doc, DOMElement $parent): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === 'donnee_intermediaire') {
                return $c;
            }
        }
        $el = $doc->createElement('donnee_intermediaire');
        $parent->appendChild($el);
        return $el;
    }
}
