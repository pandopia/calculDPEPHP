<?php

declare(strict_types=1);

namespace CalculDpePHP\Inertie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Classe d'inertie du bâtiment — §7 p.53-54.
 *
 * Lit depuis `<logement>` les flags parois lourdes et déduit la classe d'inertie
 * selon la table §7.4 :
 *
 * | PB lourd | PH lourd | PV lourde | Classe        |
 * |----------|----------|-----------|---------------|
 * |   oui    |   oui    |   oui     | 4 très lourde |
 * |    -     |   oui    |   oui     | 3 lourde      |
 * |   oui    |    -     |   oui     | 3 lourde      |
 * |   oui    |   oui    |    -      | 3 lourde      |
 * |    -     |    -     |   oui     | 2 moyenne     |
 * |    -     |   oui    |    -      | 2 moyenne     |
 * |   oui    |    -     |    -      | 2 moyenne     |
 * |    -     |    -     |    -      | 1 légère      |
 *
 * Règles par défaut (§7.1-§7.3) :
 *   - PH inconnu → léger (défaut)
 *   - PB inconnu (autre que sur terre-plein) → lourd (défaut)
 *   - Mur inconnu → faible inertie (défaut)
 *
 * Source XML :
 *   - `plancher_bas/donnee_entree/paroi_lourde` (0/1) — présent dans chaque PB
 *   - `plancher_haut/donnee_entree/paroi_lourde` (0/1)
 *   - `mur/donnee_entree/paroi_lourde` (0/1)
 *
 * Règle "surface majoritaire" : la classe d'inertie d'un type de paroi est
 * celle des surfaces majoritaires. On prend donc la surface pondérée lourde
 * vs non-lourde (si `paroi_lourde` absent, on applique les défauts ci-dessus).
 *
 * Le résultat est écrit dans le contexte (clé `inertie.classe_id`) **et** dans
 * `<logement><donnee_intermediaire><enum_classe_inertie_id>` pour la sortie XML.
 *
 * @spec-section 7
 * @spec-pages   53-54
 * @spec-source  resources/specsplitted/07-inertie/00-calcul.md
 * @xml-input    logement.enveloppe.{mur,plancher_bas,plancher_haut}_collection.*.donnee_entree.paroi_lourde
 * @xml-input    logement.enveloppe.{mur,plancher_bas,plancher_haut}_collection.*.donnee_entree.surface_paroi_totale
 * @xml-output   logement.donnee_intermediaire.enum_classe_inertie_id
 * @depends-on   (aucun)
 * @tables       (aucune)
 */
final class InertieCalculator implements CalculatorInterface
{
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
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // Read pre-computed summary flags from <inertie> element (stored by diagnostiqueur tool).
        // These are more reliable than re-deriving from individual paroi paroi_lourde flags,
        // which may be absent or computed differently by LICIEL.
        $inertieNode = null;
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'enveloppe') {
                foreach ($child->childNodes as $c) {
                    if ($c instanceof DOMElement && $c->nodeName === 'inertie') {
                        $inertieNode = $c;
                        break 2;
                    }
                }
            }
        }

        if ($inertieNode !== null) {
            $pbLourd = $accessor->getIntOrNull('./inertie_plancher_bas_lourd',     $inertieNode) === 1;
            $phLourd = $accessor->getIntOrNull('./inertie_plancher_haut_lourd',    $inertieNode) === 1;
            $pvLourd = $accessor->getIntOrNull('./inertie_paroi_verticale_lourd',  $inertieNode) === 1;
        } else {
            // Fallback: derive from individual parois
            $pbLourd = $this->isMajoritairementLourd($node, 'plancher_bas', $accessor, defaultLourd: true);
            $phLourd = $this->isMajoritairementLourd($node, 'plancher_haut', $accessor, defaultLourd: false);
            $pvLourd = $this->isMajoritairementLourd($node, 'mur', $accessor, defaultLourd: false);
        }

        $classeId = $this->classeInertie($pbLourd, $phLourd, $pvLourd);

        $context->set('inertie.classe_id', $classeId);

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'enum_classe_inertie_id', $classeId);
    }

    /**
     * Détermine si la surface majoritaire d'un type de paroi est lourde.
     *
     * Si aucune paroi trouvée → retourne $defaultLourd.
     * Si `paroi_lourde` absent sur une paroi → applique $defaultLourd pour cette paroi.
     */
    private function isMajoritairementLourd(
        DOMElement $logement,
        string $tagName,
        NodeAccessor $accessor,
        bool $defaultLourd,
    ): bool {
        $surfaceLourde  = 0.0;
        $surfaceTotal   = 0.0;

        foreach ($logement->getElementsByTagName($tagName) as $paroi) {
            if (!$paroi instanceof DOMElement) {
                continue;
            }
            $de = null;
            foreach ($paroi->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                    $de = $child;
                    break;
                }
            }
            if ($de === null) {
                continue;
            }

            $surface = $accessor->getFloatOrNull('./surface_paroi_totale', $de) ?? 0.0;
            $surfaceTotal += $surface;

            $lourdFlag = $accessor->getIntOrNull('./paroi_lourde', $de);
            $estLourd  = $lourdFlag !== null ? ($lourdFlag === 1) : $defaultLourd;
            if ($estLourd) {
                $surfaceLourde += $surface;
            }
        }

        if ($surfaceTotal <= 0.0) {
            return $defaultLourd;
        }

        return $surfaceLourde >= $surfaceTotal / 2.0;
    }

    /**
     * Table §7.4.
     * Retourne l'ID enum_classe_inertie_id : 1=légère, 2=moyenne, 3=lourde, 4=très lourde.
     */
    private function classeInertie(bool $pb, bool $ph, bool $pv): int
    {
        return match (true) {
            $pb && $ph && $pv   => 4, // très lourde
            $ph && $pv          => 3, // lourde
            $pb && $pv          => 3,
            $pb && $ph          => 3,
            $pv                 => 2, // moyenne
            $ph                 => 2,
            $pb                 => 2,
            default             => 1, // légère
        };
    }
}
