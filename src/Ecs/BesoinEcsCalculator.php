<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Besoin annuel d'ECS Becs et Becs_depensier (§11.1 p.70-72).
 *
 * Formule mensuelle :
 *   Becsj (Wh) = 1,163 × Nadeq × V40 × (40 − Tefsj) × njj
 *   V40 = 56 l/j (conventionnel) ou 79 l/j (dépensier)
 *   Tefsj : température d'eau froide mensuelle (°C) — table ecs/tv_tefs
 *   njj : jours d'occupation mensuel (décembre = 24 — inoccupé 7 jours du 24 au 30)
 *
 * Nadeq selon type d'installation (§11.1 p.70-71) :
 *   — COLLECTIF (enum_type_installation_id = 2) : utilise la totalité des adultes équivalents
 *     du bâtiment (apport.nadeq depuis FCalculator).
 *   — INDIVIDUEL (enum_type_installation_id = 1) : Nadeq pour 1 logement.
 *       - Si logement collectif : Shmoy = Sh_immeuble / Nblgt → Nmax_collectif → Nadeq = 1×f(Nmax)
 *       - Si logement individuel : Sh = Sh_logement → Nmax_individuel → Nadeq = 1×f(Nmax)
 *
 * V40 journalier :
 *   V40_ecs_journalier = Nadeq × 56   (Nadeq utilisé pour le résumé = apport.nadeq)
 *
 * @spec-section 11.1
 * @spec-pages   70-72
 * @spec-source  resources/specsplitted/11-conso-ecs/01-besoin-ecs.md
 * @xml-input    logement.caracteristique_generale.{surface_habitable_logement, nombre_appartement}
 * @xml-input    logement.installation_ecs_collection.installation_ecs.donnee_entree.enum_type_installation_id
 * @xml-output   logement.sortie.apport_et_besoin.{besoin_ecs, besoin_ecs_depensier, v40_ecs_journalier, v40_ecs_journalier_depensier, nadeq}
 * @xml-output   logement.installation_ecs_collection.installation_ecs.donnee_intermediaire.{besoin_ecs, besoin_ecs_depensier}
 * @depends-on   \CalculDpePHP\Apport\FCalculator
 * @tables       ecs/tv_tefs
 */
final class BesoinEcsCalculator implements CalculatorInterface
{
    /** L/j/personne — §11.1 p.70 */
    private const V40_CONV = 56.0;
    private const V40_DEP  = 79.0;

    /** Facteur de conversion chaleur eau (Wh/l/°C) = 1,163 */
    private const FACTEUR_EAU = 1.163;

    /** Jours d'occupation par mois — décembre = 24 (absence 24-30 inclus) — §11.1 p.71 */
    private const NJJ = [
        1  => 31, 2  => 28, 3  => 31, 4  => 30, 5  => 31, 6  => 30,
        7  => 31, 8  => 31, 9  => 30, 10 => 31, 11 => 30, 12 => 24,
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpePHP\Apport\FCalculator'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Tefs mensuel depuis tv_tefs ─────────────────────────────────────
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : null;
        $tvTefs = ($zoneId !== null && $altId !== null)
            ? ($context->tables->load('ecs/tv_tefs')[$zoneId][$altId] ?? null)
            : null;

        // ── 2. Σ(40 - Tefsj) × njj sur l'année ────────────────────────────────
        $tefsJMensuel = [];
        for ($j = 1; $j <= 12; $j++) {
            $tefsJMensuel[$j] = ($tvTefs !== null && is_array($tvTefs))
                ? (float)($tvTefs[$j] ?? 0.0)
                : 0.0;
        }

        // ── 3. Nadeq pour le résumé (logement/sortie) = apport.nadeq ───────────
        $nadeqTotal = (float)$context->get('apport.nadeq', 0.0);

        // ── 4. Nadeq pour chaque installation (dépend du type individuel/collectif) ──
        // §17.1.3 : pour un DPE immeuble collectif (methode=26), même si l'ECS est
        // individuelle, le besoin s'exprime à l'échelle de l'immeuble (nadeqTotal).
        $isAnyCollectiveInstall = $this->hasCollectiveInstallation($node, $accessor);
        $methodeId = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $node);
        $isImmeubleCollectif = in_array($methodeId, [26], true);
        $nadeqForInstall = ($isAnyCollectiveInstall || $isImmeubleCollectif)
            ? $nadeqTotal
            : $this->computeNadeqPerLogement($node, $accessor, $methodeId);

        // ── 5a. Becs par installation (nadeqForInstall) → donnee_intermediaire ──
        [$becsInstall, $becsInstallDep] = $this->computeBecs($nadeqForInstall, $tefsJMensuel);

        // ── 5b. Becs bâtiment (nadeqTotal) → apport_et_besoin et contexte ───────
        // §11.1 p.70 : les valeurs de résumé (sortie et contexte) représentent
        // toujours le bâtiment entier. Pour les installations individuelles dans un
        // bâtiment collectif, nadeqForInstall ≠ nadeqTotal.
        [$becsTotal, $becsTotalDep, $becsJMensuel] = $this->computeBecs($nadeqTotal, $tefsJMensuel);

        // ── 6. V40 et résumé — TOUJOURS avec Nadeq total du bâtiment (§11.1 p.70) ──
        $v40Journalier    = $nadeqTotal * self::V40_CONV;
        $v40JournalierDep = $nadeqTotal * self::V40_DEP;

        // ── 7. Écriture dans sortie.apport_et_besoin (logement) ────────────────
        $sortie         = $accessor->ensureSortie($node);
        $apportEtBesoin = $this->ensureChild($context->document, $sortie, 'apport_et_besoin');
        $accessor->setChildValue($apportEtBesoin, 'besoin_ecs',                    $becsTotal);
        $accessor->setChildValue($apportEtBesoin, 'besoin_ecs_depensier',          $becsTotalDep);
        $accessor->setChildValue($apportEtBesoin, 'v40_ecs_journalier',            $v40Journalier);
        $accessor->setChildValue($apportEtBesoin, 'v40_ecs_journalier_depensier',  $v40JournalierDep);

        // ── 8. Écriture dans chaque installation_ecs/donnee_intermediaire ───────
        // Ratio LICIEL : besoin_install = besoin_immeuble × surface_install / (surface_immeuble × rdim)
        //
        // Cas typiques :
        //   • 1 install couvrant tout l'immeuble, rdim=1 → ratio = 1   (besoin install = total)
        //   • 1 install rdim=N représentant N apt moyens, surface_install = surface_immeuble
        //     → ratio = 1/N                                            (besoin install = total/N)
        //   • N installs partitionnant l'immeuble par surface (chacune rdim=1)
        //     → ratio = surface_install/surface_immeuble                (besoin proportionnel)
        $nbApt        = $accessor->getIntOrNull('./caracteristique_generale/nombre_appartement', $node) ?? 1;
        $surfImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node);
        if ($surfImmeuble === null || $surfImmeuble <= 0.0) {
            $surfImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node);
        }
        $instalNodes  = [];
        $allIndividual = true;
        foreach ($node->getElementsByTagName('installation_ecs') as $inst) {
            if (!$inst instanceof DOMElement) {
                continue;
            }
            $instalNodes[] = $inst;
            $typeId = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $inst);
            if ($typeId !== 1) {
                $allIndividual = false;
            }
        }
        $isImmeubleEcsIndividuels = count($instalNodes) > 1 && $allIndividual && $nbApt > 1;

        foreach ($instalNodes as $inst) {
            $rdim     = $accessor->getFloatOrNull('./donnee_entree/rdim',              $inst) ?? 1.0;
            $rdim     = $rdim > 0.0 ? $rdim : 1.0;
            $surfInst = $accessor->getFloatOrNull('./donnee_entree/surface_habitable', $inst);

            if ($surfInst !== null && $surfInst > 0.0 && $surfImmeuble !== null && $surfImmeuble > 0.0) {
                // Partition par surface : besoin_install = besoin × surface_install / (surface_immeuble × rdim)
                $ratio = $surfInst / ($surfImmeuble * $rdim);
            } elseif ($isImmeubleEcsIndividuels) {
                // Plusieurs installs ECS individuelles sans surface saisie : chaque install ≃ 1 apt-moyen
                $ratio = 1.0 / $nbApt;
            } else {
                // 1 seule install (ou install collective) → besoin /rdim
                $ratio = 1.0 / $rdim;
            }
            $becsForInst    = $becsTotal    * $ratio;
            $becsForInstDep = $becsTotalDep * $ratio;

            $di = $this->ensureChild($context->document, $inst, 'donnee_intermediaire');
            $accessor->setChildValue($di, 'besoin_ecs',           $becsForInst);
            $accessor->setChildValue($di, 'besoin_ecs_depensier', $becsForInstDep);
        }

        // ── 9. Contexte pour ConsoEcsCalculator ────────────────────────────────
        $context->set('ecs.besoin_ecs',           $becsTotal);
        $context->set('ecs.besoin_ecs_depensier',  $becsTotalDep);
        $context->set('ecs.nadeq',                $nadeqTotal);
        $context->set('ecs.v40_journalier',        $v40Journalier);
        $context->set('ecs.v40_journalier_dep',    $v40JournalierDep);
        $context->set('ecs.besoin_ecs_mensuel',    $becsJMensuel);

        // ── 10. Pertes distribution ECS récupérées pour chauffage (§11.5) ──────
        // Qrec = 0.48 × sumNref19 × Tau × becs_total / 8760  (kWh)
        // Tau = 0.1 (individuel) ou 0.212 (collectif)
        [$pertesRecup, $pertesRecupDep] = $this->computePertesDistributionRecup(
            $becsTotal, $becsTotalDep, $isAnyCollectiveInstall, $context
        );
        $context->set('ecs.pertes_distribution_recup',     $pertesRecup);
        $context->set('ecs.pertes_distribution_recup_dep', $pertesRecupDep);
    }

    /**
     * §11.5 p.77 — Pertes de distribution ECS récupérées pour le chauffage.
     *
     * Qrec = 0.48 × sumNref19 × Tau × becs_annual / 8760  (kWh)
     * Qrec_dep = 0.48 × sumNref21 × Tau × becs_dep_annual / 8760  (kWh)
     *
     * Tau = 0.1 installation individuelle, 0.212 installation collective.
     *
     * @spec-section 11.5
     * @spec-formula §11.5 p.77
     * @return array{float, float} [pertes_recup_kWh, pertes_recup_dep_kWh]
     */
    private function computePertesDistributionRecup(
        float $becs, float $becsDep, bool $isCollective, CalculationContext $context
    ): array {
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : null;
        if ($zoneId === null || $altId === null) {
            return [0.0, 0.0];
        }

        $tvS = $context->tables->load('reference/tv_sollicitations')[$zoneId][$altId] ?? null;
        if ($tvS === null) {
            return [0.0, 0.0];
        }

        $sumNref19 = 0.0;
        $sumNref21 = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $row = $tvS[$j] ?? null;
            if (!is_array($row)) {
                continue;
            }
            $sumNref19 += (float)($row['Nref19'] ?? 0.0);
            $sumNref21 += (float)($row['Nref21'] ?? 0.0);
        }

        $tau = $isCollective ? 0.212 : 0.1;

        $pertesRecup    = 0.48 * $sumNref19 * $tau * $becs    / 8760.0;
        $pertesRecupDep = 0.48 * $sumNref21 * $tau * $becsDep / 8760.0;

        return [$pertesRecup, $pertesRecupDep];
    }

    /** @return array{float, float, array<int, float>} [becs_kWh, becsDep_kWh, mensuel_kWh] */
    private function computeBecs(float $nadeq, array $tefsJMensuel): array
    {
        $becsWh    = 0.0;
        $becsDepWh = 0.0;
        $mensuel   = [];

        for ($j = 1; $j <= 12; $j++) {
            $tefs = $tefsJMensuel[$j] ?? 0.0;
            $njj  = self::NJJ[$j];
            $becsJ = self::FACTEUR_EAU * $nadeq * self::V40_CONV * (40.0 - $tefs) * $njj;
            $becsWh    += $becsJ;
            $becsDepWh += self::FACTEUR_EAU * $nadeq * self::V40_DEP * (40.0 - $tefs) * $njj;
            $mensuel[$j] = $becsJ / 1000.0;
        }

        return [$becsWh / 1000.0, $becsDepWh / 1000.0, $mensuel];
    }

    /**
     * Vérifie si au moins une installation est NON-INDIVIDUELLE (enum_type_installation_id ≠ 1).
     * id=1 → individuelle (Tau=0.1), id=2/3/4 → collective (Tau=0.212).
     * Pour les installations collectives, Becs = besoin du bâtiment entier (apport.nadeq).
     */
    private function hasCollectiveInstallation(DOMElement $logement, NodeAccessor $accessor): bool
    {
        foreach ($logement->getElementsByTagName('installation_ecs') as $inst) {
            if (!$inst instanceof DOMElement) {
                continue;
            }
            $typeId = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $inst);
            if ($typeId !== null && $typeId !== 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Nadeq pour 1 logement (installation individuelle, §11.1 p.70-71).
     * - Appartement (methode 2/3/4/5/31/32/35/36/37) : Sh = Sh_logement, collectif, Nblgt=1
     * - Maison (methode 1/14/18) : Sh = Sh_logement, individuel, Nblgt=1
     * - Immeuble (autres) : Shmoy = Sh_immeuble / Nblgt → collectif
     */
    private function computeNadeqPerLogement(DOMElement $logement, NodeAccessor $accessor, ?int $methodeId): float
    {
        $isMaison      = in_array($methodeId, [1, 14, 18], true);
        $isAppartement = in_array($methodeId, [2, 3, 4, 5, 31, 32, 35, 36, 37], true);

        if ($isMaison) {
            $sh   = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logement) ?? 0.0;
            $nmax = $this->nmaxIndividuel($sh);
        } elseif ($isAppartement) {
            $sh   = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logement) ?? 0.0;
            $nmax = $this->nmaxCollectif($sh);
        } else {
            // Immeuble: shmoy = sh_immeuble / nombre_appartement
            $shImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement) ?? 0.0;
            $nAppart    = $accessor->getIntOrNull('./caracteristique_generale/nombre_appartement', $logement) ?? 1;
            $shmoy      = $nAppart > 0 ? $shImmeuble / $nAppart : $shImmeuble;
            $nmax       = $this->nmaxCollectif($shmoy);
        }

        $nadeqPerLgt = $nmax < 1.75 ? $nmax : 1.75 + 0.3 * ($nmax - 1.75);
        return $nadeqPerLgt; // Nblgt = 1 → résultat pour 1 logement
    }

    /** §11.1 p.71 : Nmax pour un logement collectif (identique à FCalculator) */
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

    /** §11.1 p.70 : Nmax pour un logement individuel */
    private function nmaxIndividuel(float $sh): float
    {
        if ($sh < 30.0) {
            return 1.0;
        }
        if ($sh < 70.0) {
            return 1.75 - 0.01875 * (70.0 - $sh);
        }
        return 0.025 * $sh;
    }

    private function ensureChild(\DOMDocument $doc, DOMElement $parent, string $tag): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) {
                return $c;
            }
        }
        $el = $doc->createElement($tag);
        $parent->appendChild($el);
        return $el;
    }
}
