<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\PontThermique;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Coefficient k linéique d'un pont thermique — §3.4.
 *
 * Le calcul utilise les paramètres directs présents dans le XML (type de liaison,
 * isolation des parois adjacentes via `reference_1`/`reference_2`, type de pose et
 * largeur de dormant pour les menuiseries) plutôt que `tv_pont_thermique_id` (mapping
 * interne LICIEL non documenté par la spec officielle).
 *
 * Selon `enum_methode_saisie_pont_thermique_id` :
 *   1 forfaitaire → calcul depuis spec §3.4.1-§3.4.5
 *   2 saisie justifiée → valeur saisie (k_saisi)
 *   3 RSET/RSEE → idem
 *
 * @spec-section 3.4
 * @spec-pages 32-37
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/04-ponts-thermiques/00-overview.md
 * @xml-input  pont_thermique.donnee_entree.{enum_type_liaison_id, reference_1, reference_2, presence_retour_isolation, enum_type_pose_id, largeur_dormant, k_saisi}
 * @xml-output pont_thermique.donnee_intermediaire.k
 * @tables tv_pont_thermique
 */
final class KCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        // Lit l'isolation des parois adjacentes (mur, plancher, baie). Pas de
        // dépendance sur leurs U calculés ; juste leur balise donnee_entree, qui
        // est toujours disponible.
        return [];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'pont_thermique';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('pont_thermique sans <donnee_entree>.');
        }

        $methode = $accessor->getIntOrNull('./enum_methode_saisie_pont_thermique_id', $entree);

        if ($methode === 2 || $methode === 3) {
            $kSaisi = $accessor->getFloatOrNull('./k_saisi', $entree);
            if ($kSaisi !== null) {
                $this->writeK($node, $accessor, $kSaisi);
                return;
            }
        }

        // §3.4 : ponts thermiques des parois adjacentes à des locaux en ADJACENCE_IGNORE → k=0.
        // This check must run even for forfait method (tv_pont_thermique_id lookup).
        $ref1 = $accessor->getStringOrNull('./reference_1', $entree);
        $ref2 = $accessor->getStringOrNull('./reference_2', $entree);
        [$isoMur, $autrePAroiNode, $murAdjacence] = $this->resolveIsolationAdjacencies($context->document, null, $ref1, $ref2);
        if ($murAdjacence !== null && in_array($murAdjacence, self::ADJACENCE_IGNORE, true)) {
            $this->writeK($node, $accessor, 0.0);
            return;
        }

        // Forfait (méthode=1) : lookup direct par tv_pont_thermique_id (comme open3cl)
        if ($methode === 1 || $methode === null) {
            $tvId = $accessor->getIntOrNull('./tv_pont_thermique_id', $entree);
            if ($tvId !== null) {
                $table = $context->tables->load('enveloppe/tv_pont_thermique_id');
                if (isset($table[$tvId])) {
                    $this->writeK($node, $accessor, (float)$table[$tvId]);
                    return;
                }
            }
        }

        $k = $this->computeFromSpec($entree, $accessor, $context);
        $this->writeK($node, $accessor, $k);
    }

    private function writeK(DOMElement $node, NodeAccessor $accessor, float $k): void
    {
        // §3.4 : pourcentage_valeur_pont_thermique is NOT applied to k (open3cl bug-for-bug
        // confirmed: the function pourcentageValeurPontThermique() returns k unchanged).
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'k', $k);
    }

    /**
     * Adjacence IDs for shared-space walls where thermal bridges are NOT counted (§3.4).
     * "Les ponts thermiques des parois au niveau des circulations communes ne sont pas pris en compte."
     */
    private const ADJACENCE_IGNORE = [14, 15, 16, 17, 18, 22];

    private function computeFromSpec(DOMElement $entree, NodeAccessor $accessor, CalculationContext $context): float
    {
        $liaison = $accessor->getIntOrNull('./enum_type_liaison_id', $entree);
        $table = $context->tables->load('enveloppe/tv_pont_thermique');
        $ref1  = $accessor->getStringOrNull('./reference_1', $entree);
        $ref2  = $accessor->getStringOrNull('./reference_2', $entree);

        // Identifie l'isolation du mur (paroi opaque verticale). reference_1 ou _2 selon ordre saisi.
        [$isoMur, $autrePAroiNode, $murAdjacence] = $this->resolveIsolationAdjacencies($context->document, $liaison, $ref1, $ref2);

        // §3.4: ponts thermiques on party walls / shared-space adjacences → k=0.
        if ($murAdjacence !== null && in_array($murAdjacence, self::ADJACENCE_IGNORE, true)) {
            return 0.0;
        }

        $isoMurKey = $this->isolationKey($isoMur);

        return match ($liaison) {
            1 => $this->lookupPbMur($table, $isoMurKey, $autrePAroiNode, $context),
            2 => $this->lookupPiOrRefend($table['pi_mur'], $isoMurKey),
            3 => $this->lookupPhMur($table, $isoMurKey, $autrePAroiNode, $context),
            4 => $this->lookupPiOrRefend($table['refend_mur'], $isoMurKey),
            5 => $this->lookupMenuiserieMur($table, $isoMurKey, $entree, $accessor),
            default => 0.0,
        };
    }

    /**
     * Résout l'enum_type_isolation des deux parois référencées par reference_1/reference_2.
     * Retourne [isolation_mur, autre_paroi_node, adjacence_mur]. Le "mur" est identifié par son tag XML.
     *
     * @return array{0: ?int, 1: ?DOMElement, 2: ?int}
     */
    private function resolveIsolationAdjacencies(DOMDocument $doc, ?int $liaison, ?string $ref1, ?string $ref2): array
    {
        $xpath = new DOMXPath($doc);
        $nodes = [];
        foreach ([$ref1, $ref2] as $ref) {
            if ($ref === null) continue;
            $found = $this->findParoiByReference($xpath, $ref);
            if ($found !== null) $nodes[] = $found;
        }

        $murNode = null;
        $autre   = null;
        foreach ($nodes as $n) {
            if ($n->nodeName === 'mur' && $murNode === null) {
                $murNode = $n;
            } else {
                $autre = $n;
            }
        }

        if ($murNode === null) {
            // Cas dégénéré : aucun mur trouvé. On considère "non isolé" par défaut.
            return [2, $autre, null];
        }
        $iso = $this->readIsolationOfParoi($murNode);
        $adjacence = $this->readAdjacenceOfParoi($murNode);
        return [$iso, $autre, $adjacence];
    }

    private function readAdjacenceOfParoi(DOMElement $paroi): ?int
    {
        $entree = $paroi->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) return null;
        foreach ($entree->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === 'enum_type_adjacence_id') {
                $v = trim($c->textContent ?? '');
                return is_numeric($v) ? (int)$v : null;
            }
        }
        return null;
    }

    private function findParoiByReference(DOMXPath $xpath, string $reference): ?DOMElement
    {
        // On cherche dans les collections d'enveloppe : mur, plancher_bas, plancher_haut, baie_vitree, porte
        foreach (['mur', 'plancher_bas', 'plancher_haut', 'baie_vitree', 'porte'] as $tag) {
            $expr = sprintf('//%s[donnee_entree/reference[normalize-space()=%s]]', $tag, self::xpathLiteral($reference));
            $list = $xpath->query($expr);
            if ($list !== false && $list->length > 0) {
                $item = $list->item(0);
                return $item instanceof DOMElement ? $item : null;
            }
        }
        return null;
    }

    private static function xpathLiteral(string $s): string
    {
        if (!str_contains($s, "'")) return "'$s'";
        if (!str_contains($s, '"')) return "\"$s\"";
        return "concat('" . str_replace("'", "',\"'\",'", $s) . "')";
    }

    private function readIsolationOfParoi(DOMElement $paroi): ?int
    {
        $entree = $paroi->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) return null;
        foreach ($entree->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === 'enum_type_isolation_id') {
                $v = trim($c->textContent ?? '');
                return is_numeric($v) ? (int)$v : null;
            }
        }
        return null;
    }

    /**
     * Mappe enum_type_isolation_id (1..9) vers la clé string utilisée par la table.
     */
    private function isolationKey(?int $iso): string
    {
        return match ($iso) {
            3       => 'iti',
            4       => 'ite',
            5       => 'itr',
            6       => 'iti_ite',
            7       => 'iti_itr',
            8       => 'ite_itr',
            // 1 inconnu, 2 non isolé, 9 isolé type inconnu → "non isolé" (cas conservateur §3.4)
            default => 'non_isole',
        };
    }

    /**
     * @param array<string, mixed> $table
     */
    private function lookupPbMur(array $table, string $isoMurKey, ?DOMElement $autre, CalculationContext $context): float
    {
        $isoPb = $autre !== null ? $this->isolationKey($this->readIsolationOfParoi($autre)) : 'non_isole';
        // La table PB n'a pas toutes les colonnes : ITR et ITE+ITR du PB tombent sur ITI+ITE
        $isoPb = $this->normalizePbKey($isoPb);
        $value = $table['pb_mur'][$isoMurKey][$isoPb] ?? null;
        return $value !== null ? (float)$value : 0.0;
    }

    private function normalizePbKey(string $isoPb): string
    {
        // La table §3.4.1 n'a que 4 colonnes pour le PB : non_isole, iti, ite, iti_ite.
        // ITR/iti_itr/ite_itr du PB tombent sur "iti_ite" (cas le plus proche).
        return match ($isoPb) {
            'iti', 'ite', 'non_isole', 'iti_ite' => $isoPb,
            default                              => 'iti_ite',
        };
    }

    /**
     * @param array<string, float> $byMurIso
     */
    private function lookupPiOrRefend(array $byMurIso, string $isoMurKey): float
    {
        return (float)($byMurIso[$isoMurKey] ?? 0.0);
    }

    /**
     * @param array<string, mixed> $table
     */
    private function lookupPhMur(array $table, string $isoMurKey, ?DOMElement $autre, CalculationContext $context): float
    {
        $isoPh = $autre !== null ? $this->isolationKey($this->readIsolationOfParoi($autre)) : 'non_isole';
        $isoPh = $this->normalizePbKey($isoPh); // table §3.4.3 a les mêmes 4 colonnes
        $value = $table['ph_mur'][$isoMurKey][$isoPh] ?? null;
        return $value !== null ? (float)$value : 0.0;
    }

    /**
     * @param array<string, mixed> $table
     */
    private function lookupMenuiserieMur(array $table, string $isoMurKey, DOMElement $ptEntree, NodeAccessor $accessor): float
    {
        // Pour la menuiserie, on a besoin de retour d'isolation, type de pose et largeur de dormant.
        // Ces données sont sur la baie_vitree (référencée par reference_1 ou reference_2), pas sur le pt.
        $ref1 = $accessor->getStringOrNull('./reference_1', $ptEntree);
        $ref2 = $accessor->getStringOrNull('./reference_2', $ptEntree);
        $xpath = new DOMXPath($ptEntree->ownerDocument);
        $baie = null;
        foreach ([$ref1, $ref2] as $r) {
            if ($r === null) continue;
            $expr = sprintf('//baie_vitree[donnee_entree/reference[normalize-space()=%s]]', self::xpathLiteral($r));
            $list = $xpath->query($expr);
            if ($list !== false && $list->length > 0) {
                $b = $list->item(0);
                if ($b instanceof DOMElement) { $baie = $b; break; }
            }
        }

        $retour = 1; // par défaut : avec retour
        $pose   = 2; // par défaut : nu intérieur
        $lp     = 5; // par défaut : 5 cm
        if ($baie !== null) {
            $bEntree = $baie->getElementsByTagName('donnee_entree')->item(0);
            if ($bEntree instanceof DOMElement) {
                $retour = $accessor->getIntOrNull('./presence_retour_isolation', $bEntree) ?? $retour;
                $pose   = $accessor->getIntOrNull('./enum_type_pose_id', $bEntree) ?? $pose;
                $lp     = (int)round((float)($accessor->getFloatOrNull('./largeur_dormant', $bEntree) ?? $lp));
            }
        }
        $lpKey = ($lp <= 7) ? 5 : 10;
        $poseKey = match ($pose) {
            1       => 'nu_exterieur',
            2       => 'nu_interieur',
            3       => 'tunnel',
            default => 'tunnel',
        };

        // Construit la clé d'isolation menuiserie selon iso_mur + retour
        $key = $this->menuiserieKey($isoMurKey, $retour === 1);

        $value = $table['menuiserie_mur'][$key][$poseKey][$lpKey] ?? null;
        return $value !== null ? (float)$value : 0.0;
    }

    private function menuiserieKey(string $isoMurKey, bool $avecRetour): string
    {
        // 'non_isole' et 'itr' n'ont pas de variante "avec/sans retour"
        if ($isoMurKey === 'non_isole') return 'non_isole';
        if ($isoMurKey === 'itr')       return 'itr';
        return $isoMurKey . ($avecRetour ? '_avec_retour' : '_sans_retour');
    }
}
