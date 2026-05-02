<?php

declare(strict_types=1);

namespace CalculDpe\Collectif;

use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Génération d'un DPE à l'appartement (§17.2).
 *
 * Deux cas principaux :
 *
 * §17.2.1 — DPE appartement autonome (enum_methode_application_dpe_log_id ∈ {6, 7}) :
 *   Calcul à l'échelle de l'appartement. Usages individuels → méthode ordinaire.
 *   Usages collectifs non-combustion → uses caractéristiques générateur immeuble.
 *   Usages collectifs combustion → générateur équivalent : Pe = a × Pn_collectif,
 *   a = Sh_appartement / Sh_immeuble.
 *
 * §17.2.2 — Génération depuis données immeuble (enum_methode_application_dpe_log_id = 27) :
 *   Méthode 1 (CH collectif sans IFC) : Cch_ap = (Sh_ap / Sh) × Cch_immeuble
 *   Méthode 2 (CH collectif avec IFC ou CH individuel homogène) :
 *     Cch_ap = [(1-coef_IFC)×(Sh_ap/Sh) + coef_IFC×Clé_ap] × Cch_immeuble
 *   ECS collectif → Cecs_ap = Cecs_immeuble × (Becs_ap / Becs_immeuble)
 *   Auxiliaires ventilation → proportionnel à Sh_ap / Sh
 *
 * Pour l'instant, ce Calculator ne modifie le XML que pour les cas où les données
 * d'immeuble associé sont présentes et les formules §17.2.2 applicables.
 *
 * @spec-section 17.2
 * @spec-pages   112-119
 * @spec-source  resources/specsplitted/17-collectif/02-appartement.md
 * @xml-input    logement.caracteristique_generale.{enum_methode_application_dpe_log_id, surface_habitable_logement}
 * @xml-input    logement.dpe_immeuble_associe.* (si présent)
 * @xml-output   (répartition des conso depuis données immeuble le cas échéant)
 * @depends-on   \CalculDpe\Collectif\DpeImmeubleCalculator
 * @tables       (aucune)
 */
final class DpeAppartementCalculator implements CalculatorInterface
{
    /**
     * Codes methode correspondant à un DPE généré depuis les données immeuble.
     * 27 = "Génération depuis DPE immeuble".
     */
    private const METHODE_DEPUIS_IMMEUBLE = [27];

    /**
     * Codes methode correspondant à un DPE appartement autonome.
     * 6 = appartement collectif, 7 = appartement individuel (ventilation collective).
     */
    private const METHODE_APPARTEMENT = [6, 7];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpe\Collectif\DpeImmeubleCalculator'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor  = new NodeAccessor($context->document);
        $methodeId = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $node);

        if (in_array($methodeId, self::METHODE_DEPUIS_IMMEUBLE, true)) {
            $this->appliquerRepartitionDepuisImmeuble($node, $accessor, $context);
        }
        // Pour METHODE_APPARTEMENT (6, 7) : le calcul standard (§17.2.1) s'appuie sur
        // la même méthode que pour un logement individuel, avec pondération de la puissance
        // du générateur collectif par ratio a = Sh_app / Sh_immeuble. Cette pondération
        // n'est pas implémentée ici car elle nécessite de modifier les caractéristiques
        // du générateur AVANT les calculs CH/ECS — la stratégie correcte serait de
        // créer un Calculator distinct qui court-circuite le générateur collectif.
    }

    /**
     * §17.2.2 — Répartit les consommations immeuble vers appartements.
     *
     * Formules :
     *   CH méthode 1 (sans IFC) : Cch_ap = (Shap / Sh) × Cch_imm
     *   CH méthode 2 (avec IFC) : Cch_ap = [(1-IFC)×(Shap/Sh) + IFC×Clé_ap] × Cch_imm
     *     avec Clé_ap = Bch_ap / Σ Bch_ap ≈ Shap / Sh (approx. sans données individuelles)
     *   ECS : Cecs_ap = Cecs_imm × (Becs_ap / Becs_imm)
     *   Vent : Caux_vent_ap = (Shap / Sh) × Caux_vent_imm
     */
    private function appliquerRepartitionDepuisImmeuble(
        DOMElement $node, NodeAccessor $accessor, CalculationContext $context
    ): void {
        // Données de répartition depuis le contexte (rempli par un éventuel DPE immeuble lié)
        // Dans la version actuelle, les données immeuble ne sont pas liées dynamiquement.
        // L'implémentation complète de §17.2.2 nécessite :
        //   1. Accès aux consommations totales de l'immeuble (Cch, Cecs, Caux_vent)
        //   2. Surface de l'appartement (Shap) et surface totale immeuble (Sh)
        //   3. Coefficient IFC et clé Clé_ap
        // Ces données sont absentes dans le XML d'entrée des DPE appartements standalone.
        // Ce cas sera complété quand un fichier test avec méthode=27 sera disponible.
    }
}
