<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\Mur;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Calcul de Umur0 (mur non isolé) — partie haute de l'arbre §3.2.1.1.
 *
 * Étapes :
 *   1. Détermination de Umur0_brut selon `enum_methode_saisie_u0_id` :
 *      - 1 (type inconnu)            → Umur0_brut = 2.5
 *      - 2 (table forfaitaire)       → lookup tv_umur0 par (matériau, épaisseur)
 *      - 20 (cloison de plâtre)      → Umur0 = 3.33 (cas spécial §3.2.1.2)
 *      - 3,4 (saisie directe)        → la valeur saisie est lue dans le XML (umur0)
 *      - 5 (non saisi car U direct)  → ne s'applique pas (cf. UmurCalculator)
 *   2. Application de l'enduit isolant si `enduit_isolant_paroi_ancienne == 1` :
 *      Umur0 = 1 / (1/Umur0_sansEnduit + R_enduit) avec R_enduit = 0,7 m².K/W
 *   3. Application du doublage selon `enum_type_doublage_id` :
 *      - 1 inconnu  → comportement à clarifier (pour le moment : pas de R)
 *      - 2 absence  → pas de R
 *      - 3 lame d'air <15mm ou indéterminé   → R_doublage = 0,1
 *      - 4 lame d'air ≥15mm                  → R_doublage = 0,21
 *      - 5 doublage connu (plâtre, brique, bois) → R_doublage = 0,21
 *   4. Plafonnement : **Umur_nu = min(Umur0_avec_doublage ; 2,5)**.
 *      C'est cette valeur qui est écrite dans le XML sous `<umur0>` — c'est
 *      l'entrée de la suite du calcul (Umur).
 *
 * @spec-section 3.2.1.2
 * @spec-pages 14-16
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/01-umur/02-calcul-umur0.md
 * @xml-input  mur.donnee_entree.{enum_methode_saisie_u0_id, enum_materiaux_structure_mur_id, epaisseur_structure, enduit_isolant_paroi_ancienne, enum_type_doublage_id, umur0_saisi}
 * @xml-output mur.donnee_intermediaire.umur0
 * @tables tv_umur0
 */
final class Umur0Calculator implements CalculatorInterface
{
    /** Plafond de Umur_nu — §3.2.1.1 schéma "Min(Umur0 ; 2,5)". */
    private const UMUR_NU_PLAFOND = 2.5;

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
        return $node->nodeName === 'mur';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor  = new NodeAccessor($context->document);
        $entree    = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('mur sans <donnee_entree>.');
        }

        $methodeU0  = $accessor->getIntOrNull('./enum_methode_saisie_u0_id', $entree);

        // methode_saisie_u0_id=5 means U is given directly via umur_saisi / enum_methode_saisie_u_id=9/10
        // open3cl does NOT compute umur0 in this case (calc_umur0 not called, umur0 not written)
        if ($methodeU0 === 5) {
            return;
        }

        $materiau   = $accessor->getIntOrNull('./enum_materiaux_structure_mur_id', $entree);
        $epaisseur  = $accessor->getFloatOrNull('./epaisseur_structure', $entree);

        $umur0Brut = $this->resolveUmur0Brut($methodeU0, $materiau, $epaisseur, $entree, $accessor, $context);

        // Enduit isolant pour parois anciennes
        $enduit = $accessor->getIntOrNull('./enduit_isolant_paroi_ancienne', $entree) ?? 0;
        if ($enduit === 1) {
            $umur0Brut = 1.0 / (1.0 / $umur0Brut + 0.7);
        }

        // Doublage
        $rDoublage  = $this->doublageResistance($accessor->getIntOrNull('./enum_type_doublage_id', $entree));
        $umur0Final = ($rDoublage > 0) ? 1.0 / (1.0 / $umur0Brut + $rDoublage) : $umur0Brut;

        // Plafond Umur_nu = min(Umur0 ; 2.5)
        $umurNu = min($umur0Final, self::UMUR_NU_PLAFOND);

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'umur0', $umurNu);
    }

    /**
     * Détermine Umur0 brut (avant doublage et plafonnement) selon la méthode de saisie.
     */
    private function resolveUmur0Brut(
        ?int $methode,
        ?int $materiau,
        ?float $epaisseur,
        DOMElement $entree,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        // Cloison de plâtre — §3.2.1.2
        if ($materiau === 20) {
            return 3.33;
        }

        // Matériaux sans entrée dans tv_umur0 : forfait 2.5 (inconnu, autres)
        // IDs 1 (inconnu), 21, 22, 23 (autres) — comme open3cl tv_umur0 sans epaisseur_structure
        if (in_array($materiau, [1, 21, 22, 23], true)) {
            return 2.5;
        }

        return match ($methode) {
            1       => 2.5,                                  // type de paroi inconnu
            2       => $this->lookupTable($materiau, $epaisseur, $context),
            3, 4    => $this->resolveSaisiDirect($entree, $accessor),
            default => 2.5,                                  // fallback raisonnable
        };
    }

    private function lookupTable(?int $materiau, ?float $epaisseur, CalculationContext $context): float
    {
        if ($materiau === null) {
            // Sans matériau on ne peut pas déterminer la table → forfaitaire
            return 2.5;
        }
        $epaisseur = $epaisseur ?? 0.0;
        $rows = $context->tables->load('enveloppe/tv_umur0');

        $matching = array_values(array_filter(
            $rows,
            static fn(array $r): bool => (int)$r['materiau'] === $materiau
        ));
        if ($matching === []) {
            throw new RuntimeException(sprintf(
                'Umur0 introuvable pour matériau %d. Compléter resources/tables/enveloppe/tv_umur0.php (TASK-B01).',
                $materiau
            ));
        }

        // bug_for_bug_compat : certains logiciels diagnostiqueurs stockent l'épaisseur
        // en mm au lieu de cm (open3cl détecte epaisseur > 80 et divise par 10).
        if ($epaisseur > 80.0) {
            $epaisseur /= 10.0;
        }

        // La première ligne avec epaisseur_max_cm >= epaisseur_demandée
        foreach ($matching as $row) {
            if ($epaisseur <= (float)$row['epaisseur_max_cm']) {
                return (float)$row['umur0'];
            }
        }
        // Aucune borne trouvée → dernière ligne (devrait être PHP_FLOAT_MAX)
        return (float)$matching[count($matching) - 1]['umur0'];
    }

    private function resolveSaisiDirect(DOMElement $entree, NodeAccessor $accessor): float
    {
        $value = $accessor->getFloatOrNull('./umur0_saisi', $entree);
        if ($value === null) {
            // Pas de balise umur0_saisi : on retombe sur le forfait
            return 2.5;
        }
        return $value;
    }

    /**
     * Résistance thermique du doublage selon §3.2.1.2.
     *
     * @return float R en m².K/W (0 si absence de doublage).
     */
    private function doublageResistance(?int $enumTypeDoublage): float
    {
        return match ($enumTypeDoublage) {
            3       => 0.10,  // doublage indéterminé ou lame d'air <15 mm
            4       => 0.21,  // doublage indéterminé avec lame d'air ≥15 mm
            5       => 0.21,  // doublage connu (plâtre, brique, bois)
            default => 0.0,   // 1 inconnu, 2 absence, ou null
        };
    }
}
