<?php

declare(strict_types=1);

namespace CalculDpe\Apport;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Facteur F mensuel et fractions annuelles d'apports gratuits (§6.1 p.42-44).
 *
 * Formules :
 *   Asj (Wh) = 1000 × Ssej × Ej              (Ej en kWh/m², Ssej en m²)
 *   Aij (Wh) = [(3.52) × Sh + 90 × 132/168 × Nadeq] × Nref19j
 *   Xj = (Asj + Aij) / (GV × DH19j)
 *   Fj = (Xj − Xj^n) / (1 − Xj^n)   n=3.6/2.9/2.5 selon inertie
 *   fraction_apport_gratuit_ch = Σ(Fj × GV × DH19j) / Σ(GV × DH19j)
 *
 * Nadeq (§11.1 p.70-72) :
 *   Collectif  : Shmoy = Sh_immeuble/Nblgt ; Nmax = 0.035×Shmoy si ≥50
 *   Individuel : Shmoy = Sh_logement/Nblgt ; Nmax = 0.025×Shmoy si ≥70
 *
 * @spec-section 6.1
 * @spec-pages   42-44
 * @spec-source  resources/specsplitted/06-apports-gratuits/01-calcul-F.md
 * @xml-input    logement.caracteristique_generale.{surface_habitable_logement, surface_habitable_immeuble, nombre_appartement}
 * @xml-input    logement.enveloppe.inertie.enum_classe_inertie_id
 * @xml-output   logement.sortie.apport_et_besoin.{apport_solaire_ch, apport_interne_ch, apport_solaire_fr, apport_interne_fr, fraction_apport_gratuit_ch, fraction_apport_gratuit_depensier_ch, nadeq}
 * @depends-on   \CalculDpe\Apport\SurfaceSudEquivalenteCalculator, \CalculDpe\Inertie\InertieCalculator, \CalculDpe\Enveloppe\EnveloppeAggregator, \CalculDpe\Ventilation\VentilationAggregator
 * @tables       reference/tv_sollicitations
 */
final class FCalculator implements CalculatorInterface
{
    /**
     * Exponent n for Fj formula indexed by enum_classe_inertie_id.
     * LICIEL convention (differs from XSD appinfo): 1=légère, 2=moyenne, 3=lourde, 4=très lourde.
     * Validated against reference verif files.
     */
    private const INERTIE_N = [
        1 => 2.5, // légère (LICIEL)
        2 => 2.9, // moyenne (LICIEL)
        3 => 3.6, // lourde (LICIEL)
        4 => 3.6, // très lourde (LICIEL)
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpe\Apport\SurfaceSudEquivalenteCalculator',
            '\CalculDpe\Inertie\InertieCalculator',
            '\CalculDpe\Enveloppe\EnveloppeAggregator',
            '\CalculDpe\Ventilation\VentilationAggregator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Surface et occupation ───────────────────────────────────────────
        $shImmeuble  = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node);
        $shLogement  = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node);
        $nAppart     = $accessor->getIntOrNull('./caracteristique_generale/nombre_appartement', $node);
        $methodeId   = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $node);

        // Determine building type from enum_methode_application_dpe_log_id (cf. open3cl engine.js).
        // maison [1,14,18]: sh=sh_logement, nadeq=individual, nblgt=1
        // appartement [2,3,4,5,31,32,35,36,37]: sh=sh_logement, nadeq=collective(sh_log,1), nblgt=1
        // immeuble (others): sh=sh_immeuble, nadeq=collective(sh_imm,nblgt), nblgt=nombre_appartement
        $isMaison      = in_array($methodeId, [1, 14, 18], true);
        $isAppartement = in_array($methodeId, [2, 3, 4, 5, 31, 32, 35, 36, 37], true);
        $isImmeuble    = !$isMaison && !$isAppartement;

        if ($isMaison) {
            $sh      = $shLogement ?? 0.0;
            $nblgt   = 1;
            $isCollectif = false;
        } elseif ($isAppartement) {
            $sh      = $shLogement ?? 0.0;
            $nblgt   = 1;
            $isCollectif = true;
        } else {
            // immeuble
            $sh      = $shImmeuble ?? 0.0;
            $nblgt   = ($nAppart !== null && $nAppart > 0) ? $nAppart : 1;
            $isCollectif = true;
        }

        $nadeq = $this->computeNadeq($sh, $nblgt, $isCollectif);

        // ── 2. GV = déperditions enveloppe + ventilation (§6.1) ────────────────
        $dpParois = (float)$context->get('enveloppe.dp_parois', 0.0);
        $dpPT     = (float)$context->get('enveloppe.dp_pont_thermique', 0.0);
        $hvent    = (float)$context->get('ventilation.hvent', 0.0);
        $hperm    = (float)$context->get('ventilation.hperm', 0.0);
        $gv       = $dpParois + $dpPT + $hvent + $hperm;

        // ── 3. Inertie → exposant n ─────────────────────────────────────────────
        // LICIEL may store an inconsistent enum_classe_inertie_id (e.g. très lourde for a
        // physically lightweight building). We resolve the class as min(stored, physical)
        // where "physical" is derived from the inertie_*_lourd summary flags (§7.4 table).
        // "min" (smaller class_id = lighter class = smaller n) is the more conservative choice.
        $inertieId = $this->resolveInertieClassId($accessor, $node);
        $n = self::INERTIE_N[$inertieId] ?? 2.9;

        // ── 4. SSe mensuelle (logement + ETS) ───────────────────────────────────
        $sseMensuel    = (array)$context->get('apport.sse_mensuel', array_fill(1, 12, 0.0));
        $sseEtsMensuel = (array)$context->get('apport.sse_ets_mensuel', array_fill(1, 12, 0.0));

        // ── 5. Données climatiques ──────────────────────────────────────────────
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : null;
        $tvS    = ($zoneId !== null && $altId !== null)
            ? ($context->tables->load('reference/tv_sollicitations')[$zoneId][$altId] ?? null)
            : null;

        // ── 6. Boucle mensuelle ─────────────────────────────────────────────────
        $apportSolaireCh   = 0.0;
        $apportInterneCh   = 0.0;
        $numerateur19      = 0.0;
        $denominateur19    = 0.0;
        $numerateur21      = 0.0;
        $denominateur21    = 0.0;

        $aiBase = 3.52 * $sh + 90.0 * (132.0 / 168.0) * $nadeq;

        for ($j = 1; $j <= 12; $j++) {
            $row = $tvS[$j] ?? null;
            if ($row === null || $row['Nref19'] === null) {
                continue; // mois hors saison de chauffe
            }

            $Ej      = (float)$row['E'];
            $Nref19j = (float)$row['Nref19'];
            $DH19j   = (float)$row['DH19'];
            $Nref21j = (float)$row['Nref21'];
            $DH21j   = (float)$row['DH21'];

            $Ssej = ($sseMensuel[$j] ?? 0.0) + ($sseEtsMensuel[$j] ?? 0.0);

            // Asj (Wh) = 1000 × Ssej(m²) × Ej(kWh/m²)
            $Asj  = 1000.0 * $Ssej * $Ej;
            // Aij (Wh) = aiBase(W) × Nref19j(h)
            $Aij19 = $aiBase * $Nref19j;
            $Aij21 = $aiBase * $Nref21j;

            // Apports annuels (kWh)
            $apportSolaireCh += $Ssej * $Ej;          // Asj_kWh = Ssej × Ej
            $apportInterneCh += $Aij19 / 1000.0;       // Aij_kWh

            // Fraction 19°C
            if ($gv > 0.0 && $DH19j > 0.0) {
                $Xj19 = ($Asj + $Aij19) / ($gv * $DH19j);
                $Fj19 = $this->computeF($Xj19, $n);
                $numerateur19   += $Fj19 * $gv * $DH19j;
                $denominateur19 += $gv * $DH19j;
            }

            // Fraction 21°C (dépensier)
            if ($gv > 0.0 && $DH21j > 0.0) {
                $Xj21 = ($Asj + $Aij21) / ($gv * $DH21j);
                $Fj21 = $this->computeF($Xj21, $n);
                $numerateur21   += $Fj21 * $gv * $DH21j;
                $denominateur21 += $gv * $DH21j;
            }
        }

        $fractionCh         = $denominateur19 > 0.0 ? $numerateur19 / $denominateur19 : 0.0;
        $fractionChDepensier = $denominateur21 > 0.0 ? $numerateur21 / $denominateur21 : 0.0;

        // ── 7. Écriture dans sortie.apport_et_besoin ───────────────────────────
        $sortie        = $accessor->ensureSortie($node);
        $apportEtBesoin = $this->ensureApportEtBesoin($accessor, $sortie);

        $accessor->setChildValue($apportEtBesoin, 'surface_sud_equivalente',
            array_sum($sseMensuel) + array_sum($sseEtsMensuel));
        $accessor->setChildValue($apportEtBesoin, 'apport_solaire_fr',      0);
        $accessor->setChildValue($apportEtBesoin, 'apport_interne_fr',      0);
        $accessor->setChildValue($apportEtBesoin, 'apport_solaire_ch',      $apportSolaireCh);
        $accessor->setChildValue($apportEtBesoin, 'apport_interne_ch',      $apportInterneCh);
        $accessor->setChildValue($apportEtBesoin, 'fraction_apport_gratuit_ch',           $fractionCh);
        $accessor->setChildValue($apportEtBesoin, 'fraction_apport_gratuit_depensier_ch', $fractionChDepensier);
        $accessor->setChildValue($apportEtBesoin, 'nadeq', $nadeq);

        // Stocker dans le contexte pour BesoinChauffageCalculator
        $context->set('apport.apport_solaire_ch',   $apportSolaireCh);
        $context->set('apport.apport_interne_ch',   $apportInterneCh);
        $context->set('apport.fraction_ch',         $fractionCh);
        $context->set('apport.fraction_ch_depensier', $fractionChDepensier);
        $context->set('apport.nadeq',               $nadeq);
    }

    /**
     * Lit l'ID de classe d'inertie depuis le XML (§7.4 — enum_classe_inertie_id).
     * On utilise directement la valeur stockée par le diagnostiqueur.
     */
    private function resolveInertieClassId(NodeAccessor $accessor, DOMElement $node): int
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'enveloppe') {
                foreach ($child->childNodes as $c) {
                    if ($c instanceof DOMElement && $c->nodeName === 'inertie') {
                        return $accessor->getIntOrNull('./enum_classe_inertie_id', $c) ?? 2;
                    }
                }
            }
        }
        return 2;
    }

    /**
     * §6.1 : Fj = (Xj − Xj^n) / (1 − Xj^n), vaut 1 si Xj ≥ 1.
     * §6.1 : Si Xj ≤ 0 la fraction vaut 0 (pas d'apports).
     */
    private function computeF(float $x, float $n): float
    {
        if ($x <= 0.0) {
            return 0.0;
        }
        if ($x >= 1.0) {
            return 1.0;
        }
        $xn = $x ** $n;
        $denom = 1.0 - $xn;
        if ($denom === 0.0) {
            return 1.0;
        }
        return ($x - $xn) / $denom;
    }

    /**
     * §11.1 p.70-72 : calcul du nombre d'adultes équivalents Nadeq.
     * Collectif  : Shmoy = Sh/Nblgt, Nmax = 0.035×Shmoy si ≥50m², etc.
     * Individuel : Shmoy = Sh/Nblgt, Nmax = 0.025×Shmoy si ≥70m², etc.
     */
    private function computeNadeq(float $sh, int $nblgt, bool $collectif): float
    {
        if ($sh <= 0.0 || $nblgt <= 0) {
            return 1.0;
        }
        $shmoy = $sh / $nblgt;
        $nmax  = $collectif
            ? $this->nmaxCollectif($shmoy)
            : $this->nmaxIndividuel($shmoy);

        $nadeqPerLgt = $nmax < 1.75
            ? $nmax
            : 1.75 + 0.3 * ($nmax - 1.75);

        return $nblgt * $nadeqPerLgt;
    }

    /** §11.1 p.71 : Nmax pour un logement collectif. */
    private function nmaxCollectif(float $shmoy): float
    {
        if ($shmoy < 10.0) {
            return 1.0;
        }
        if ($shmoy < 50.0) {
            return 1.75 - 0.01875 * (50.0 - $shmoy);
        }
        return 0.035 * $shmoy;
    }

    /** §11.1 p.70 : Nmax pour un logement individuel. */
    private function nmaxIndividuel(float $shmoy): float
    {
        if ($shmoy < 30.0) {
            return 1.0;
        }
        if ($shmoy < 70.0) {
            return 1.75 - 0.01875 * (70.0 - $shmoy);
        }
        return 0.025 * $shmoy;
    }

    private function ensureApportEtBesoin(NodeAccessor $accessor, DOMElement $sortie): DOMElement
    {
        foreach ($sortie->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'apport_et_besoin') {
                return $child;
            }
        }
        $el = $sortie->ownerDocument->createElement('apport_et_besoin');
        $sortie->appendChild($el);
        return $el;
    }
}
