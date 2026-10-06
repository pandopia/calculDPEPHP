<?php
declare(strict_types=1);
namespace CalculDpePHP\Collectif;

use CalculDpePHP\Dto\{ApartmentInput, BuildingInput};
use CalculDpePHP\Engine\DefaultDpeEngineFactory;
use CalculDpePHP\Xml\{NodeAccessor, XmlReader};
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Besoins simplifiés pour les clés de répartition chauffage individuel/IFC.
 * Chaque surface de paroi est répartie entre les logements associés, au prorata
 * de leurs surfaces habitables. Sur demande explicite, les associations absentes
 * sont estimées par le mur support, la position (RDC/dernier étage), puis tous
 * les logements. Ce dernier repli est une approximation documentée, pas une
 * règle imposée par la 3CL. Les liens explicites gardent la priorité. Les U/k
 * calculés à l'immeuble sont conservés ; les masques et pertes récupérées sont ignorés.
 * Aucun résultat de référence certifié n'est utilisé.
 * @spec-section 17.2.2.2.2
 * @spec-pages 116-117
 * @spec-source resources/specsplitted/17-collectif/02-appartement.md
 */
final class ApartmentHeatingNeeds
{
    private const PARTS = [
        'mur' => ['murs', 'surface_paroi_opaque', 'umur'],
        'plancher_bas' => ['plancher', 'surface_paroi_opaque', 'upb'],
        'plancher_haut' => ['plafond', 'surface_paroi_opaque', 'uph'],
        'baie_vitree' => ['fenetre', 'surface_totale_baie', null],
        'porte' => ['porte', 'surface_porte', null],
    ];

    /**
     * @param list<string> $assumptions Hypothèses ajoutées sans données de référence.
     * @return array<string, float> Besoins positifs, identifiés par référence appartement.
     */
    public static function calculate(BuildingInput $input, string $calculatedBuilding, bool $cooling = false, array &$assumptions = []): array
    {
        $source = (new XmlReader())->loadString($calculatedBuilding);
        $a = new NodeAccessor($source);
        $xp = new DOMXPath($source);
        $members = [];
        $apartments = [];
        foreach ($input->apartments as $apt) {
            if ($apt->reference === '' || isset($apartments[$apt->reference]) || !is_finite($apt->surface) || $apt->surface <= 0) {
                throw new RuntimeException('Référence ou surface logement invalide pour la répartition des parois.');
            }
            $apartments[$apt->reference] = $apt->surface;
        }

        foreach (self::PARTS as $tag => [$association]) {
            foreach ($xp->query('//' . $tag . '/donnee_entree') as $de) {
                $reference = $a->getStringOrNull('./reference', $de);
                if ($reference === null || isset($members[$reference])) {
                    throw new RuntimeException('Référence de paroi absente ou ambiguë : besoins logements non calculables.');
                }
                $members[$reference] = [];
                foreach ($input->apartments as $apt) {
                    if (in_array($reference, $apt->associations[$association] ?? [], true)) {
                        $members[$reference][$apt->reference] = $apt->surface;
                    }
                }
            }
        }
        foreach (self::PARTS as $tag => [$association]) {
            foreach ($xp->query('//' . $tag . '/donnee_entree') as $de) {
                $reference = $a->getStringOrNull('./reference', $de);
                if ($members[$reference]) { continue; }
                if (!$input->approximateMissingAssociations) {
                    throw new RuntimeException('Paroi ' . $reference . ' sans association logement : besoins de répartition incomplets.');
                }
                $parent = $a->getStringOrNull('./reference_paroi', $de);
                if ($parent !== null && !empty($members[$parent])) {
                    $members[$reference] = $members[$parent];
                    $rule = 'logements du mur support ' . $parent;
                } else {
                    $position = match ($tag) { 'plancher_bas' => 1, 'plancher_haut' => 3, default => null };
                    foreach ($input->apartments as $apt) {
                        if ($position === null || $apt->position === $position) { $members[$reference][$apt->reference] = $apt->surface; }
                    }
                    $rule = $position === 1 ? 'logements du rez-de-chaussée' : 'logements du dernier étage';
                    if ($position === null || !$members[$reference]) {
                        $members[$reference] = $apartments;
                        $rule = 'ensemble des logements, faute de liaison ou de position exploitable';
                    }
                }
                $assumptions[] = 'Paroi ' . $reference . ' sans liaison : répartition approximative entre ' . $rule . ', au prorata des surfaces habitables (§17.2.2.2.2).';
            }
        }
        $bridges = self::bridges($input, $a, $xp, $members, $apartments, $assumptions);
        $needs = [];
        foreach ($input->apartments as $apt) {
            $doc = self::project($source, $apt, $members, $bridges);
            $out = DefaultDpeEngineFactory::create()->calculateDocument($doc, true);
            $need = (new NodeAccessor($out))->getFloatOrNull('//sortie/apport_et_besoin/' . ($cooling ? 'besoin_fr' : 'besoin_ch'));
            if ($need === null || !is_finite($need) || $need < 0) {
                throw new RuntimeException('Besoin ' . ($cooling ? 'de refroidissement' : 'de chauffage') . ' invalide pour ' . $apt->reference . '.');
            }
            $needs[$apt->reference] = $need;
        }
        if (!$cooling && array_sum($needs) <= 0) { throw new RuntimeException('Somme des besoins de chauffage nulle : clé non définie.'); }
        $assumptions = array_values(array_unique($assumptions));
        return $needs;
    }

    /**
     * Logements concernés par chaque pont thermique, dans l'ordre du document.
     * Une liaison explicite (`associations['pont_thermique']`) prime ; sinon
     * le pont suit ses parois (`reference_1`/`reference_2` du XSD) : il borde
     * les logements communs aux deux parois, ou ceux de la seule paroi décrite
     * pour un plancher intermédiaire ou un refend. Les logiciels qui saisissent des
     * ponts « manuels » sans référence de paroi exigent l'une ou l'autre, ou
     * l'approximation explicite (tous les logements, au prorata des surfaces).
     *
     * @param array<string, array<string, float>> $members
     * @param array<string, float> $apartments
     * @param list<string> $assumptions
     * @return list<array<string, float>>
     */
    private static function bridges(BuildingInput $input, NodeAccessor $a, DOMXPath $xp, array $members, array $apartments, array &$assumptions): array
    {
        $bridges = [];
        foreach ($xp->query('//pont_thermique/donnee_entree') as $de) {
            $reference = $a->getStringOrNull('./reference', $de);
            $direct = [];
            foreach ($input->apartments as $apt) {
                if ($reference !== null && in_array($reference, $apt->associations['pont_thermique'] ?? [], true)) {
                    $direct[$apt->reference] = $apt->surface;
                }
            }
            if ($direct) { $bridges[] = $direct; continue; }
            $type = $a->getIntOrNull('./enum_type_liaison_id', $de);
            $r1 = $a->getStringOrNull('./reference_1', $de);
            $r2 = $a->getStringOrNull('./reference_2', $de);
            $m1 = $r1 !== null ? ($members[$r1] ?? null) : null;
            $m2 = $r2 !== null ? ($members[$r2] ?? null) : null;
            if ($m1 !== null && $m2 !== null) { $bridges[] = array_intersect_key($m1, $m2); continue; }
            // Plancher intermédiaire ou refend : l'élément intérieur n'est pas
            // une paroi déperditive décrite, seule la paroi décrite compte,
            // qu'elle soit référencée en premier ou en second.
            if (in_array($type, [2, 4], true) && ($m1 ?? $m2) !== null) { $bridges[] = $m1 ?? $m2; continue; }
            $name = $reference ?? $a->getStringOrNull('./description', $de) ?? '?';
            if (!$input->approximateMissingAssociations) {
                throw new RuntimeException('Pont thermique ' . $name . ' sans parois associées (reference_1/reference_2) ni liaison logement : besoins de répartition incomplets.');
            }
            $bridges[] = $apartments;
            $assumptions[] = 'Pont thermique ' . $name . ' sans liaison : réparti entre tous les logements, au prorata des surfaces habitables (§17.2.2.2.2).';
        }
        return $bridges;
    }

    /**
     * @param array<string, array<string, float>> $members
     * @param list<array<string, float>> $bridges
     */
    private static function project(DOMDocument $source, ApartmentInput $apt, array $members, array $bridges): DOMDocument
    {
        $doc = clone $source;
        $xp = new DOMXPath($doc);
        $a = new NodeAccessor($doc);
        $general = $xp->query('//logement/caracteristique_generale')->item(0);
        $a->setChildValue($general, 'enum_methode_application_dpe_log_id', 2);
        $a->setChildValue($general, 'surface_habitable_logement', $apt->surface);
        $a->setChildValue($general, 'nombre_niveau_logement', 1);
        foreach (self::PARTS as $tag => [$association, $surfaceTag, $uTag]) {
            foreach (iterator_to_array($xp->query('//' . $tag)) as $part) {
                $de = $xp->query('./donnee_entree', $part)->item(0);
                $reference = $a->getStringOrNull('./reference', $de);
                if (!isset($members[$reference][$apt->reference])) {
                    $part->parentNode->removeChild($part);
                    continue;
                }
                $ratio = $apt->surface / array_sum($members[$reference]);
                foreach (array_unique([$surfaceTag, 'surface_paroi_totale', 'surface_aiu', 'surface_aue', 'nb_baie']) as $field) {
                    $value = $a->getFloatOrNull('./' . $field, $de);
                    if ($value !== null) { $a->setChildValue($de, $field, $value * $ratio); }
                }
                if ($uTag !== null) {
                    $u = $a->getFloatOrNull('./donnee_intermediaire/' . $uTag, $part);
                    if ($u === null) { throw new RuntimeException('Coefficient ' . $uTag . ' immeuble absent.'); }
                    $a->setChildValue($de, 'enum_methode_saisie_u_id', 9);
                    $a->setChildValue($de, $uTag . '_saisi', $u);
                }
                // §17.2.2.2.2 : les masques solaires sont négligés uniquement
                // pour cette estimation interne, jamais dans le DPE immeuble.
                foreach (iterator_to_array($xp->query('.//*[contains(local-name(), "masque")]', $part)) as $mask) {
                    $mask->parentNode?->removeChild($mask);
                }
            }
        }
        foreach (iterator_to_array($xp->query('//pont_thermique')) as $i => $bridge) {
            $de = $xp->query('./donnee_entree', $bridge)->item(0);
            $linked = $bridges[$i];
            if (!isset($linked[$apt->reference])) {
                $bridge->parentNode->removeChild($bridge);
                continue;
            }
            $k = $a->getFloatOrNull('./donnee_intermediaire/k', $bridge);
            if ($k === null) { throw new RuntimeException('Coefficient de pont thermique immeuble absent.'); }
            $a->setChildValue($de, 'l', ($a->getFloatOrNull('./l', $de) ?? 0) * $apt->surface / array_sum($linked));
            $a->setChildValue($de, 'enum_methode_saisie_pont_thermique_id', 2);
            $a->setChildValue($de, 'k_saisi', $k);
        }
        // Les besoins sont évalués à la surface du logement, y compris l'air
        // renouvelé. La surface du bâtiment reste disponible pour ses systèmes.
        foreach ($xp->query('//ventilation/donnee_entree') as $de) {
            $a->setChildValue($de, 'surface_ventile', $apt->surface);
        }
        return $doc;
    }
}
