<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\Mur;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Calcul de Umur (mur, isolation incluse) — partie basse de l'arbre §3.2.1.1.
 *
 * Utilise Umur_nu déjà calculé par {@see Umur0Calculator} (écrit dans
 * `<donnee_intermediaire><umur0>`).
 *
 * Selon `enum_methode_saisie_u_id` :
 *   1  non isolé                                     → Umur = Umur_nu
 *   2  isolation inconnue (table forfaitaire)        → Umur = min(Umur_nu, Umur_tab)
 *   3  épaisseur saisie justifiée mesure/observation → Umur = 1 / (1/Umur_nu + e/0,04)
 *   4  épaisseur saisie justifiée par documents      → idem 3
 *   5  R isolant saisie justifiée mesure             → Umur = 1 / (1/Umur_nu + R)
 *   6  R isolant saisie justifiée documents          → idem 5
 *   7  année isolation différente, table forfaitaire → Umur = min(Umur_nu, Umur_tab)
 *   8  année construction, table forfaitaire         → idem 7
 *   9  saisie directe U justifiée                    → Umur = u_saisi
 *   10 saisie directe U depuis RSET/RSEE             → idem 9
 *
 * Si `enum_type_isolation_id == 2` (non isolé) l'arbre court-circuite à Umur = Umur_nu.
 *
 * @spec-section 3.2.1
 * @spec-pages 13-16
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/01-umur/00-calcul.md
 * @xml-input  mur.donnee_entree.{enum_methode_saisie_u_id, enum_type_isolation_id, epaisseur_isolation, resistance_isolation, u_saisi}
 * @xml-output mur.donnee_intermediaire.umur
 * @depends-on \CalculDpe\Enveloppe\Mur\Umur0Calculator
 * @tables tv_umur
 */
final class UmurCalculator implements CalculatorInterface
{
    /** Conductivité thermique conventionnelle de l'isolant : λ = 0,04 W/(m.K). */
    private const LAMBDA_ISOLANT = 0.04;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [Umur0Calculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'mur';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree   = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('mur sans <donnee_entree>.');
        }
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);

        $methodeU      = $accessor->getIntOrNull('./enum_methode_saisie_u_id', $entree);
        $typeIsolation = $accessor->getIntOrNull('./enum_type_isolation_id', $entree);

        // methode_saisie_u=9/10 (saisie directe U): umur0 not computed, read umur_saisi directly
        if (in_array($methodeU, [9, 10], true)) {
            $umurSaisi = $accessor->getFloatOrNull('./umur_saisi', $entree);
            if ($umurSaisi !== null) {
                $accessor->setChildValue($intermediaire, 'umur', $umurSaisi);
                return;
            }
        }

        $umurNu = $accessor->getFloatOrNull('./umur0', $intermediaire);
        if ($umurNu === null) {
            throw new RuntimeException(
                'UmurCalculator : umur0 absent dans donnee_intermediaire (Umur0Calculator non exécuté ?).'
            );
        }

        $umur = $this->computeUmur($umurNu, $methodeU, $typeIsolation, $entree, $accessor, $context);

        $accessor->setChildValue($intermediaire, 'umur', $umur);
    }

    private function computeUmur(
        float $umurNu,
        ?int $methode,
        ?int $typeIsolation,
        DOMElement $entree,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        // Court-circuit : isolation explicitement absente
        if ($typeIsolation === 2 || $methode === 1) {
            return $umurNu;
        }

        return match ($methode) {
            3, 4 => $this->byEpaisseur($umurNu, $accessor->getFloatOrNull('./epaisseur_isolation', $entree)),
            5, 6 => $this->byResistance($umurNu, $accessor->getFloatOrNull('./resistance_isolation', $entree)),
            9, 10 => $accessor->getFloatOrNull('./umur_saisi', $entree) ?? $umurNu,
            2, 7, 8 => $this->lookupUmurTab($umurNu, $methode, $entree, $accessor, $context),
            default => $umurNu,
        };
    }

    /**
     * Lookup Umur_tab × zone × énergie × période.
     * Umur final = min(Umur_nu, Umur_tab).
     *
     * Règle méthode 8 : si enum_periode_construction_id ≤ 2 (≤74)
     *   → année isolation conventionnelle = période 3 (75-77).
     * Méthode 2 (inconnu) : utilise même periode_id mais sans remontée d'année.
     */
    private function lookupUmurTab(
        float $umurNu,
        int $methode,
        DOMElement $entree,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        $zone    = $context->zoneGroupe;
        $energie = $context->energieChauffagePrincipale;
        if ($zone === null || $energie === null) {
            return $umurNu;
        }

        // Période d'isolation : méthode 7 → lire enum_periode_isolation_id ;
        // méthode 8 → enum_periode_construction_id avec règle ≤74 → 3 ;
        // méthode 2 → enum_periode_isolation_id si présent, sinon enum_periode_construction_id.
        // Pour méthode 7 : on lit enum_periode_isolation_id (l'année d'isolation est connue).
        // Pour méthode 8 : on utilise la periode_construction_id directement — les logiciels
        //   diagnostiqueurs ADEME (LICIEL bug_for_bug_compat) ne remappent PAS les périodes
        //   ≤74 vers 75-77 pour le plancher bas/mur ; on reste sur la période de construction.
        // Pour méthode 2 (isolation inconnue) : préférer enum_periode_isolation_id.
        $periodeId = match ($methode) {
            7 => $accessor->getIntOrNull('./enum_periode_isolation_id', $entree),
            8 => $context->periodeConstructionId,      // ADEME: pas de remappage ≤74→75-77
            default => $accessor->getIntOrNull('./enum_periode_isolation_id', $entree)
                     ?? $context->periodeConstructionId,
        };

        if ($periodeId === null) {
            return $umurNu;
        }

        // Méthode 2 seulement : si période ≤74, convention 75-77 (période 3).
        // Méthode 7 (isolation period explicite) et 8 (ADEME bug_for_bug_compat) :
        //   pas de remappage — open3cl utilise la période saisie directement.
        if ($methode === 2 && $periodeId <= 2) {
            $periodeId = 3;
        }

        $table   = $context->tables->load('enveloppe/tv_umur');
        $umurTab = $table[$zone][$energie][$periodeId] ?? null;
        if ($umurTab === null) {
            return $umurNu;
        }
        return min($umurNu, (float)$umurTab);
    }

    /**
     * Umur = 1 / (1/Umur_nu + e/λ), avec e en mètres.
     *
     * @spec-formula F-3.2.1-épaisseur
     */
    private function byEpaisseur(float $umurNu, ?float $epaisseurCm): float
    {
        if ($epaisseurCm === null || $epaisseurCm <= 0) {
            return $umurNu;
        }
        $eMetres = $epaisseurCm / 100.0;
        return 1.0 / (1.0 / $umurNu + $eMetres / self::LAMBDA_ISOLANT);
    }

    /**
     * Umur = 1 / (1/Umur_nu + R), avec R en m².K/W.
     *
     * @spec-formula F-3.2.1-resistance
     */
    private function byResistance(float $umurNu, ?float $r): float
    {
        if ($r === null || $r <= 0) {
            return $umurNu;
        }
        return 1.0 / (1.0 / $umurNu + $r);
    }
}
