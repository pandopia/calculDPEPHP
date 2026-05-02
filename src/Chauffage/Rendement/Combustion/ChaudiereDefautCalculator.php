<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement\Combustion;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Valeurs par défaut des chaudières (Pn, Rpn, Rpint, QP0, Pveil) — §13.2.2 p.86-92.
 *
 * Si `enum_methode_saisie_carac_sys_id = 1` (saisie par table), les valeurs sont lues
 * dans `resources/tables/chauffage/tv_generateur_combustion.php` via `tv_generateur_combustion_id`.
 * La puissance nominale Pn est calculée depuis GV si absente de donnee_entree :
 *   Pn = 1.2 × GV × (19 − Tbase) / 0.95³   (§13.2.2 p.87, open3cl 13.2_generateur_combustion.js)
 *
 * Si `enum_methode_saisie_carac_sys_id = 2` (valeurs réelles connues), les champs
 * pn / rpn / rpint / qp0 / pveil sont supposés présents en donnee_entree et recopiés.
 *
 * Ne s'applique qu'aux générateurs à combustion (IDs 20-97 excl. PAC et électrique).
 *
 * @spec-section 13.2.2
 * @spec-pages   86-92
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/02-valeurs-defaut-gaz-fioul.md
 * @xml-input    generateur_chauffage.donnee_entree.{tv_generateur_combustion_id, enum_methode_saisie_carac_sys_id, pn, presence_ventouse}
 * @xml-output   generateur_chauffage.donnee_intermediaire.{pn, rpn, rpint, qp0, pveil}
 * @depends-on   \CalculDpePHP\Enveloppe\EnveloppeAggregator, \CalculDpePHP\Ventilation\VentilationAggregator
 * @tables       chauffage/tv_generateur_combustion
 */
final class ChaudiereDefautCalculator implements CalculatorInterface
{
    /** IDs générateurs à combustion gérés ici (PAC 1-19 et électrique 98-116 exclus) */
    private const COMBUSTION_MIN = 20;
    private const COMBUSTION_MAX = 97;

    /**
     * Température de base Tbase par zone × classe altitude (§13.2.2 p.87).
     *   [enum_zone_climatique_id][enum_classe_altitude_id] → °C
     * Source : open3cl utils.js Tbase table.
     *   Zone H1=1, H2=2, H3=3 ; Altitude <400m=1, 400-800m=2, >800m=3
     */
    private const TBASE = [
        1 => [1 => -9.5,  2 => -11.5, 3 => -13.5],  // H1
        2 => [1 => -6.5,  2 =>  -8.5, 3 => -10.5],  // H2
        3 => [1 => -3.5,  2 =>  -5.5, 3 =>  -7.5],  // H3
    ];

    /** E/F factors for qp0 formula — indexed by presence_ventouse (0=non, 1=oui) */
    private const E_TAB = [0 => 2.5,   1 => 1.75];
    private const F_TAB = [0 => -0.8,  1 => -0.55];

    /** Pn cap (W) for gas/oil boilers — IDs 75-97, 127-139, 148-151, 160-161 */
    private const PN_CAP_GAZ_FIOUL = 400000.0;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Enveloppe\EnveloppeAggregator',
            '\CalculDpePHP\Ventilation\VentilationAggregator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'generateur_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $genId    = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $node);

        if ($genId === null || $genId < self::COMBUSTION_MIN || $genId > self::COMBUSTION_MAX) {
            return;
        }

        $methode = $accessor->getIntOrNull('./donnee_entree/enum_methode_saisie_carac_sys_id', $node) ?? 1;

        $di = $accessor->ensureDonneeIntermediaire($node);

        if ($methode === 2) {
            // Caractéristiques réelles saisies par le diagnostiqueur — recopier depuis donnee_entree
            foreach (['pn', 'rpn', 'rpint', 'qp0', 'pveil'] as $field) {
                $val = $accessor->getFloatOrNull("./donnee_entree/$field", $node);
                if ($val !== null) {
                    $accessor->setChildValue($di, $field, $val);
                }
            }
            return;
        }

        // methode=1 : lecture de la table tv_generateur_combustion
        $tvId = $accessor->getIntOrNull('./donnee_entree/tv_generateur_combustion_id', $node);
        if ($tvId === null) {
            return;
        }

        $table = $context->tables->load('chauffage/tv_generateur_combustion');
        $entry = $table[$tvId] ?? null;
        if ($entry === null) {
            return; // ID non encore digitalisé — TASK-A07
        }

        // Ratio de virtualisation pour installations collectives (§17.2)
        $ratioVirt = $this->getRatioVirtualisation($node, $accessor);

        // Puissance nominale : depuis donnee_entree si saisie, sinon calculée depuis GV
        $pnW = $accessor->getFloatOrNull('./donnee_entree/pn', $node);
        if ($pnW === null || $pnW <= 0.0) {
            $pnW = $this->computePnFromGv($context, $ratioVirt, $genId);
        }
        $pnKw = $pnW / 1000.0;

        // Facteurs E, F (ventouse)
        $ventouse = $accessor->getIntOrNull('./donnee_entree/presence_ventouse', $node) ?? 0;
        $e = self::E_TAB[$ventouse] ?? self::E_TAB[0];
        $f = self::F_TAB[$ventouse] ?? self::F_TAB[0];

        // Évaluation de la fermeture ou tableau de valeurs fixes
        if ($entry instanceof \Closure) {
            // Pour les installations collectives, rpn est évalué à la puissance du bâtiment
            // (après plafonnement), mais pn stocké = puissance du logement (× ratio_virt).
            // Pour pn saisi (non calculé), pas de virtualisation (ratio=1 implicite).
            $pnBuildingKw = $pnKw;
            $pnApartmentW = $pnW;
            if ($ratioVirt > 0.0 && $ratioVirt < 1.0 && ($accessor->getFloatOrNull('./donnee_entree/pn', $node) === null)) {
                // pnW ici = pn_bâtiment (déjà plaffonné dans computePnFromGv)
                $pnBuildingKw = $pnW / 1000.0;
                $pnApartmentW = $pnW * $ratioVirt;
            }
            $row = $entry($pnBuildingKw, $e, $f);
            // pn stocké = part du logement ; qp0/pveil proportionnels si collectif
            $row['pn']    = $pnApartmentW;
            $row['qp0']   = ($row['qp0']   ?? 0.0) * ($ratioVirt < 1.0 ? $ratioVirt : 1.0);
            $row['pveil'] = ($row['pveil']  ?? 0.0) * ($ratioVirt < 1.0 ? $ratioVirt : 1.0);
        } else {
            $row = $entry;
        }

        $accessor->setChildValue($di, 'pn',    (float)($row['pn']    ?? $pnW));
        $accessor->setChildValue($di, 'rpn',   (float)($row['rpn']   ?? 0.0));
        $accessor->setChildValue($di, 'rpint', (float)($row['rpint'] ?? 0.0));
        $accessor->setChildValue($di, 'qp0',   (float)($row['qp0']   ?? 0.0));
        $accessor->setChildValue($di, 'pveil', (float)($row['pveil'] ?? 0.0));
    }

    /**
     * Calcule Pn depuis GV et la temperature de base (§13.2.2 p.87).
     *   Pn = 1.2 × GV × (19 − Tbase) / 0.95³
     * Pour les installations collectives (ratio_virt < 1) : GV est mis à l'échelle du bâtiment
     * (GV_logement / ratio_virt), Pn plaffonné selon le type, puis retourné comme Pn_bâtiment.
     * La conversion en Pn_logement = Pn_bâtiment × ratio_virt se fait dans calculate().
     */
    private function computePnFromGv(CalculationContext $context, float $ratioVirt, ?int $genId): float
    {
        $dpParois = (float)$context->get('enveloppe.dp_parois',        0.0);
        $dpPT     = (float)$context->get('enveloppe.dp_pont_thermique', 0.0);
        $hvent    = (float)$context->get('ventilation.hvent',           0.0);
        $hperm    = (float)$context->get('ventilation.hperm',           0.0);
        $gv       = $dpParois + $dpPT + $hvent + $hperm;

        if ($gv <= 0.0) {
            return 0.0;
        }

        // Pour installation collective, GV est rapporté au bâtiment entier
        $gvEffectif = ($ratioVirt > 0.0 && $ratioVirt < 1.0) ? $gv / $ratioVirt : $gv;

        $zoneGroupe = CalculationContext::zoneGroupeFromId($context->zoneClimatique);
        $zoneIdx    = match($zoneGroupe) { 'H1' => 1, 'H2' => 2, 'H3' => 3, default => 1 };
        $altId      = $context->classeAltitude !== null ? (int)$context->classeAltitude : 1;
        $tbase      = self::TBASE[$zoneIdx][$altId] ?? -9.5;

        $pnBuilding = (1.2 * $gvEffectif * (19.0 - $tbase)) / (0.95 ** 3);

        // Plafonnement Pn selon type de générateur (chaudières gaz/fioul : 400 kW)
        if ($ratioVirt < 1.0 && $genId !== null) {
            $pnBuilding = min($pnBuilding, $this->getPnCap($genId));
        }

        return $pnBuilding;
    }

    /**
     * Retourne le plafond de Pn (W) selon le type de générateur.
     * Appliqué uniquement pour les installations collectives (ratio_virt < 1).
     */
    private function getPnCap(int $genId): float
    {
        // Chaudières gaz (85-97, 127-139, 148-151, 160-161) et fioul (75-84, 116-119, 150-151) : 400 kW
        if (($genId >= 75 && $genId <= 97)
            || ($genId >= 116 && $genId <= 119)
            || ($genId >= 127 && $genId <= 139)
            || ($genId >= 148 && $genId <= 151)
            || ($genId >= 160 && $genId <= 161)) {
            return self::PN_CAP_GAZ_FIOUL;
        }
        return PHP_FLOAT_MAX;
    }

    /**
     * Lit ratio_virtualisation depuis l'installation parente (installation_chauffage.donnee_entree).
     */
    private function getRatioVirtualisation(DOMElement $genNode, NodeAccessor $accessor): float
    {
        // generateur_chauffage → generateur_chauffage_collection → installation_chauffage
        $parent = $genNode->parentNode?->parentNode;
        if (!$parent instanceof DOMElement || $parent->nodeName !== 'installation_chauffage') {
            return 1.0;
        }
        return $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation', $parent) ?? 1.0;
    }
}
