<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Data;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Pdf\Template\TemplateVariant;
use CalculDpePHP\Sortie\SeuilsClasses;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

/**
 * Lecture, dans un XML DPE ADEME, de tout ce que le rapport affiche.
 *
 * Les valeurs viennent de `<sortie>` (consommations, émissions, coûts,
 * confort d'été, qualité d'isolation) et des collections descriptives
 * (`descriptif_simplifie`, `fiche_technique`, `descriptif_travaux`…) que le
 * logiciel diagnostiqueur dépose avec le DPE. Les conventions d'affichage
 * (fourchettes de coûts, équivalent kilométrique…) sont documentées sur
 * chaque méthode.
 */
final class DpeData
{
    /** Facteur d'émission d'un véhicule de référence : 7 569 kg ⇔ 39 222 km dans le modèle. */
    public const KG_CO2_PAR_KM = 0.193;

    private readonly DOMXPath $xpath;
    private readonly NodeAccessor $accessor;

    public function __construct(private readonly DOMDocument $document)
    {
        $this->xpath = new DOMXPath($document);
        $this->accessor = new NodeAccessor($document);
        if ($this->xpath->query('/dpe')?->length !== 1) {
            throw new InvalidArgumentException('Le XML fourni n\'est pas un DPE ADEME (racine <dpe> attendue).');
        }
    }

    public static function fromXml(string $xml): self
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_use_internal_errors($previous);
        if ($loaded === false) {
            throw new InvalidArgumentException('XML DPE illisible.');
        }

        return new self($document);
    }

    public function document(): DOMDocument
    {
        return $this->document;
    }

    // ── Accès génériques ────────────────────────────────────────────────────

    public function string(string $path, ?DOMElement $context = null): ?string
    {
        return $this->accessor->getStringOrNull($path, $context);
    }

    public function float(string $path, ?DOMElement $context = null): ?float
    {
        return $this->accessor->getFloatOrNull($path, $context);
    }

    public function int(string $path, ?DOMElement $context = null): ?int
    {
        $value = $this->float($path, $context);

        return $value === null ? null : (int) round($value);
    }

    /**
     * @return list<DOMElement>
     */
    public function elements(string $path, ?DOMElement $context = null): array
    {
        $result = [];
        foreach ($this->xpath->query($path, $context) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $result[] = $node;
            }
        }

        return $result;
    }

    private function sortie(string $path): ?float
    {
        return $this->float("/dpe/logement/sortie/$path");
    }

    // ── Administratif ───────────────────────────────────────────────────────

    public function numeroDpe(): ?string
    {
        return $this->string('/dpe/numero_dpe');
    }

    public function methodeApplication(): int
    {
        return $this->int('/dpe/logement/caracteristique_generale/enum_methode_application_dpe_log_id') ?? 1;
    }

    public function variant(): TemplateVariant
    {
        return TemplateVariant::fromMethodeApplication($this->methodeApplication());
    }

    public function dateEtablissement(): DateTimeImmutable
    {
        $date = $this->string('/dpe/administratif/date_etablissement_dpe')
            ?? throw new InvalidArgumentException('date_etablissement_dpe absente du XML.');

        return new DateTimeImmutable(substr($date, 0, 10));
    }

    /** Un DPE est valable 10 ans (art. L. 126-31 CCH) : jusqu'à la veille du dixième anniversaire. */
    public function dateFinValidite(): DateTimeImmutable
    {
        return $this->dateEtablissement()->modify('+10 years -1 day');
    }

    public function dateVisite(): ?DateTimeImmutable
    {
        $date = $this->string('/dpe/administratif/date_visite_diagnostiqueur');

        return $date === null ? null : new DateTimeImmutable(substr($date, 0, 10));
    }

    public function adresseBien(): string
    {
        return $this->adresse('/dpe/administratif/geolocalisation/adresses/adresse_bien');
    }

    public function adresseProprietaire(): string
    {
        return $this->adresse('/dpe/administratif/geolocalisation/adresses/adresse_proprietaire');
    }

    private function adresse(string $path): string
    {
        $label = $this->string("$path/label_brut")
            ?? trim(implode(' ', array_filter([
                $this->string("$path/adresse_brut"),
                $this->string("$path/code_postal_brut"),
                $this->string("$path/nom_commune_brut"),
            ])));
        $complement = $this->string("$path/compl_ref_logement");
        if ($complement !== null && !str_contains($label, $complement)) {
            $label .= " / $complement";
        }

        return $label;
    }

    public function nomProprietaire(): ?string
    {
        $nom = $this->string('/dpe/administratif/nom_proprietaire');

        return $nom === '#' ? null : $nom;
    }

    public function nomProprietaireInstallationCommune(): ?string
    {
        $nom = $this->string('/dpe/administratif/nom_proprietaire_installation_commune');

        return $nom === '#' ? null : $nom;
    }

    public function adresseProprietaireInstallationCommune(): ?string
    {
        $path = '/dpe/administratif/geolocalisation/adresses/adresse_proprietaire_installation_commune';

        return $this->string("$path/label_brut") === null ? null : $this->adresse($path);
    }

    /**
     * @return array<string, string|null>
     */
    public function diagnostiqueur(): array
    {
        $d = '/dpe/administratif/diagnostiqueur';

        return [
            'entreprise' => $this->string("$d/entreprise_diagnostiqueur"),
            'adresse' => $this->string("$d/adresse_diagnostiqueur"),
            'nom' => trim(($this->string("$d/prenom_diagnostiqueur") ?? '') . ' ' . ($this->string("$d/nom_diagnostiqueur") ?? '')),
            'telephone' => $this->string("$d/telephone_diagnostiqueur"),
            'email' => $this->string("$d/mail_diagnostiqueur"),
            'certification' => $this->string("$d/numero_certification_diagnostiqueur"),
            'organisme' => $this->string("$d/organisme_certificateur"),
            'logiciel' => $this->string("$d/version_logiciel"),
            'moteur' => $this->string("$d/version_moteur_calcul"),
        ];
    }

    public function invariantFiscal(): ?string
    {
        return $this->string('/dpe/administratif/geolocalisation/numero_fiscal_local');
    }

    public function parcelleCadastrale(): ?string
    {
        return $this->string('/dpe/administratif/geolocalisation/idpar');
    }

    public function immatriculationCopropriete(): ?string
    {
        return $this->string('/dpe/administratif/geolocalisation/immatriculation_copropriete');
    }

    /**
     * @return list<string>
     */
    public function justificatifs(): array
    {
        $result = [];
        foreach ($this->elements('/dpe/justificatif_collection/justificatif') as $j) {
            $result[] = $this->string('description', $j)
                ?? EnumLabels::label('enum_type_justificatif_id', $this->string('enum_type_justificatif_id', $j))
                ?? '';
        }

        return array_values(array_filter($result));
    }

    // ── Caractéristiques générales ──────────────────────────────────────────

    public function anneeConstruction(): ?string
    {
        $annee = $this->string('/dpe/logement/caracteristique_generale/annee_construction');
        if ($annee !== null) {
            return $annee;
        }

        return EnumLabels::label('enum_periode_construction_id', $this->string('/dpe/logement/caracteristique_generale/enum_periode_construction_id'));
    }

    public function surfaceReference(): ?float
    {
        $cg = '/dpe/logement/caracteristique_generale';
        if ($this->variant() === TemplateVariant::IMMEUBLE) {
            return $this->float("$cg/surface_habitable_immeuble") ?? $this->float("$cg/surface_habitable_logement");
        }

        return $this->float("$cg/surface_habitable_logement") ?? $this->float("$cg/surface_habitable_immeuble");
    }

    public function surfaceImmeuble(): ?float
    {
        return $this->float('/dpe/logement/caracteristique_generale/surface_habitable_immeuble');
    }

    public function nombreLogements(): ?int
    {
        return $this->int('/dpe/logement/caracteristique_generale/nombre_appartement');
    }

    public function typeBien(): string
    {
        return match ($this->variant()) {
            TemplateVariant::MAISON => 'maison individuelle',
            TemplateVariant::IMMEUBLE => 'immeuble collectif',
            default => 'appartement',
        };
    }

    // ── Étiquettes ──────────────────────────────────────────────────────────

    public function epConsoM2(): float
    {
        return $this->sortie('ep_conso/ep_conso_5_usages_m2') ?? 0.0;
    }

    public function efConsoM2(): float
    {
        return $this->sortie('ef_conso/conso_5_usages_m2') ?? 0.0;
    }

    public function gesM2(): float
    {
        return $this->sortie('emission_ges/emission_ges_5_usages_m2') ?? 0.0;
    }

    public function classeDpe(): string
    {
        return strtoupper($this->string('/dpe/logement/sortie/ep_conso/classe_bilan_dpe') ?? 'G');
    }

    public function classeGes(): string
    {
        return strtoupper($this->string('/dpe/logement/sortie/emission_ges/classe_emission_ges') ?? 'G');
    }

    public function emissionGesAnnuelle(): float
    {
        return $this->sortie('emission_ges/emission_ges_5_usages') ?? 0.0;
    }

    public function kmEquivalents(): float
    {
        return $this->emissionGesAnnuelle() / self::KG_CO2_PAR_KM;
    }

    // ── Coûts et consommations par usage ────────────────────────────────────

    public const USAGES = ['chauffage', 'ecs', 'refroidissement', 'eclairage', 'auxiliaires'];

    /**
     * Consommations (kWh/an) et coût conventionnel (€/an) d'un usage.
     *
     * @return array{ep: float, ef: float, cout: float, cout_depensier: float}
     */
    public function usage(string $usage): array
    {
        [$ef, $ep, $cout] = match ($usage) {
            'chauffage' => ['conso_ch', 'ep_conso_ch', 'cout_ch'],
            'ecs' => ['conso_ecs', 'ep_conso_ecs', 'cout_ecs'],
            'refroidissement' => ['conso_fr', 'ep_conso_fr', 'cout_fr'],
            'eclairage' => ['conso_eclairage', 'ep_conso_eclairage', 'cout_eclairage'],
            'auxiliaires' => ['conso_totale_auxiliaire', 'ep_conso_totale_auxiliaire', 'cout_total_auxiliaire'],
            default => throw new InvalidArgumentException("Usage inconnu : $usage"),
        };

        return [
            'ep' => $this->sortie("ep_conso/$ep") ?? 0.0,
            'ef' => $this->sortie("ef_conso/$ef") ?? 0.0,
            'cout' => $this->sortie("cout/$cout") ?? 0.0,
            'cout_depensier' => $this->sortie("cout/{$cout}_depensier") ?? $this->sortie("cout/$cout") ?? 0.0,
        ];
    }

    /**
     * Fourchette d'estimation affichée pour un coût annuel : de −15 % arrondi à
     * la dizaine inférieure à +15 % arrondi à la dizaine supérieure. Convention
     * des logiciels évalués : elle reproduit au centime les montants des
     * rapports d'exemple, et la fourchette totale est la somme des fourchettes
     * par usage.
     *
     * @return array{0: int, 1: int}
     */
    public static function fourchette(float $cout): array
    {
        if ($cout <= 0.0) {
            return [0, 0];
        }

        return [(int) (floor($cout * 0.85 / 10) * 10), (int) (ceil($cout * 1.15 / 10) * 10)];
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function fourchetteTotale(): array
    {
        $min = 0;
        $max = 0;
        foreach (self::USAGES as $usage) {
            [$a, $b] = self::fourchette($this->usage($usage)['cout']);
            $min += $a;
            $max += $b;
        }

        return [$min, $max];
    }

    public function epConsoTotale(): float
    {
        return $this->sortie('ep_conso/ep_conso_5_usages') ?? 0.0;
    }

    public function efConsoTotale(): float
    {
        return $this->sortie('ef_conso/conso_5_usages') ?? 0.0;
    }

    /**
     * Énergies consommées par un usage, de la plus consommée à la moins
     * consommée (`sortie_par_energie`).
     *
     * @return list<int> enum_type_energie_id
     */
    public function energiesUsage(string $usage): array
    {
        $field = match ($usage) {
            'chauffage' => 'conso_ch',
            'ecs' => 'conso_ecs',
            'refroidissement' => 'conso_fr',
            default => null,
        };
        if ($field === null) {
            // Éclairage et auxiliaires sont électriques par construction de la méthode.
            return $this->usage($usage)['ef'] > 0 ? [1] : [];
        }

        $energies = [];
        foreach ($this->elements('/dpe/logement/sortie/sortie_par_energie_collection/sortie_par_energie') as $s) {
            $conso = $this->float($field, $s) ?? 0.0;
            $id = $this->int('enum_type_energie_id', $s);
            if ($conso > 0 && $id !== null) {
                $energies[$id] = ($energies[$id] ?? 0) + $conso;
            }
        }
        arsort($energies);

        return array_keys($energies);
    }

    // ── Recommandations d'usage (p.3) ───────────────────────────────────────

    /**
     * Gain d'un comportement économe par rapport au scénario dépensier
     * (21 °C, 79 ℓ/j par adulte équivalent…) : part de la consommation
     * d'énergie finale évitée, appliquée au coût conventionnel.
     *
     * Les consommations dépensières sont toujours renseignées, alors que les
     * coûts dépensiers des DPE collectifs sont recopiés du conventionnel
     * (défaut de référence K05). Cette lecture reproduit les montants de tous
     * les rapports d'exemple.
     *
     * @return array{pourcentage: int, euros: int}
     */
    public function gainComportement(string $usage): array
    {
        $field = $usage === 'ecs' ? 'conso_ecs' : 'conso_ch';
        $conso = $this->sortie("ef_conso/$field") ?? 0.0;
        $depensier = $this->sortie("ef_conso/{$field}_depensier") ?? $conso;
        if ($depensier <= 0.0 || $conso <= 0.0) {
            return ['pourcentage' => 0, 'euros' => 0];
        }
        $evite = $depensier - $conso;

        return [
            'pourcentage' => (int) round($evite / $depensier * 100),
            'euros' => (int) round($this->usage($usage)['cout'] * $evite / $conso),
        ];
    }

    /**
     * Adultes équivalents d'un logement (§11.1). Pour un immeuble, logement
     * moyen ; pour un appartement issu d'un DPE immeuble, le XML porte le
     * total de l'immeuble : on le recalcule sur la surface de l'appartement.
     *
     * @spec-source resources/specsplitted/11-conso-ecs/01-besoin-ecs.md
     */
    public function nadeqLogement(): float
    {
        $nadeq = $this->sortie('apport_et_besoin/nadeq') ?? 0.0;

        return match ($this->variant()) {
            TemplateVariant::IMMEUBLE => $nadeq / max(1, $this->nombreLogements() ?? 1),
            TemplateVariant::APPARTEMENT_IMMEUBLE => self::nadeqCollectif(
                $this->float('/dpe/logement/caracteristique_generale/surface_habitable_logement') ?? 0.0,
            ),
            default => $nadeq,
        };
    }

    /** §11.1 : Nmax d'un logement collectif, puis Nadeq. */
    public static function nadeqCollectif(float $surface): float
    {
        $nmax = match (true) {
            $surface < 10.0 => 1.0,
            $surface < 50.0 => 1.75 - 0.01875 * (50.0 - $surface),
            default => 0.035 * $surface,
        };

        return $nmax < 1.75 ? $nmax : 1.75 + 0.3 * ($nmax - 1.75);
    }

    /** §11.1 : 56 ℓ/j d'eau à 40 °C par adulte équivalent, 79 ℓ/j en scénario dépensier. */
    public function volumeEcsJournalier(): float
    {
        return 56.0 * $this->nadeqLogement();
    }

    public function volumeEcsJournalierDepensier(): float
    {
        return 79.0 * $this->nadeqLogement();
    }

    // ── Déperditions et isolation (p.2) ─────────────────────────────────────

    /**
     * Part de chaque poste dans les déperditions de l'enveloppe, en %.
     *
     * @return array{ventilation: float, toiture: float, murs: float, menuiseries: float, ponts: float, plancher: float}
     */
    public function partsDeperditions(): array
    {
        $d = fn (string $tag): float => max(0.0, $this->sortie("deperdition/$tag") ?? 0.0);
        $parts = [
            'ventilation' => $d('deperdition_renouvellement_air'),
            'toiture' => $d('deperdition_plancher_haut'),
            'murs' => $d('deperdition_mur'),
            'menuiseries' => $d('deperdition_baie_vitree') + $d('deperdition_porte'),
            'ponts' => $d('deperdition_pont_thermique'),
            'plancher' => $d('deperdition_plancher_bas'),
        ];
        $total = array_sum($parts);
        if ($total <= 0) {
            return array_map(static fn (): float => 0.0, $parts);
        }

        return array_map(static fn (float $v): float => $v / $total * 100, $parts);
    }

    /** 1 très bonne, 2 bonne, 3 moyenne, 4 insuffisante (XSD qualite_isol_*). */
    public function qualiteIsolation(string $poste = 'enveloppe'): ?int
    {
        $q = '/dpe/logement/sortie/qualite_isolation';
        if ($poste === 'plancher_haut') {
            foreach (['plancher_haut_toit_terrasse', 'plancher_haut_comble_perdu', 'plancher_haut_comble_amenage'] as $tag) {
                $value = $this->int("$q/qualite_isol_$tag");
                if ($value !== null) {
                    return $value;
                }
            }

            return null;
        }

        return $this->int("$q/qualite_isol_$poste");
    }

    /**
     * @return array{indicateur: ?int, isolation_toiture: bool, protection_solaire_exterieure: bool, aspect_traversant: bool, brasseur_air: bool, inertie_lourde: bool}|null
     */
    public function confortEte(): ?array
    {
        $c = '/dpe/logement/sortie/confort_ete';
        $source = $this;
        if ($this->int("$c/enum_indicateur_confort_ete_id") === null) {
            // Bloc vide dans certains DPE déposés (défaut de référence K12) :
            // le moteur 3CL du projet le recalcule depuis les données d'entrée.
            $source = $this->recalcule();
            if ($source === null || $source->int("$c/enum_indicateur_confort_ete_id") === null) {
                return null;
            }
        }
        $flag = fn (string $tag): bool => ($source->int("$c/$tag") ?? 0) === 1;

        return [
            'indicateur' => $source->int("$c/enum_indicateur_confort_ete_id"),
            'isolation_toiture' => $flag('isolation_toiture'),
            'protection_solaire_exterieure' => $flag('protection_solaire_exterieure'),
            'aspect_traversant' => $flag('aspect_traversant'),
            'brasseur_air' => $flag('brasseur_air'),
            'inertie_lourde' => $flag('inertie_lourde'),
        ];
    }

    private ?self $recalcule = null;
    private bool $recalculTente = false;

    /** Le même DPE passé dans le moteur 3CL du projet (null en cas d'échec). */
    private function recalcule(): ?self
    {
        if (!$this->recalculTente) {
            $this->recalculTente = true;
            try {
                $xml = CalculDpePHP::calculate((string) $this->document->saveXML());
                $this->recalcule = is_string($xml) ? self::fromXml($xml) : null;
            } catch (\Throwable) {
                $this->recalcule = null;
            }
        }

        return $this->recalcule;
    }

    /**
     * Équipements d'énergie renouvelable présents (descriptif_enr).
     *
     * @return list<int> enum_categorie_enr_descriptif_id
     */
    public function enrPresentes(): array
    {
        $ids = [];
        foreach ($this->elements('/dpe/descriptif_enr_collection/descriptif_enr') as $e) {
            $id = $this->int('enum_categorie_enr_descriptif_id', $e);
            if ($id !== null) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    // ── Descriptifs (p.4) ───────────────────────────────────────────────────

    /**
     * Descriptions simplifiées d'une catégorie (enum_categorie_descriptif_simplifie_id).
     *
     * @return list<string>
     */
    public function descriptifs(int $categorie): array
    {
        $result = [];
        foreach ($this->elements('/dpe/descriptif_simplifie_collection/descriptif_simplifie') as $d) {
            if ($this->int('enum_categorie_descriptif_simplifie_id', $d) === $categorie) {
                $result[] = $this->string('description', $d) ?? '';
            }
        }

        return array_values(array_unique(array_filter($result)));
    }

    /**
     * Gestes d'entretien regroupés par pictogramme, dans l'ordre du XML.
     *
     * @return list<array{picto: int, categorie: string, descriptions: list<string>}>
     */
    public function gestesEntretien(): array
    {
        $groups = [];
        foreach ($this->elements('/dpe/descriptif_geste_entretien_collection/descriptif_geste_entretien') as $g) {
            $picto = $this->int('enum_picto_geste_entretien_id', $g) ?? 12;
            $categorie = $this->string('categorie_geste_entretien', $g)
                ?? EnumLabels::label('enum_picto_geste_entretien_id', $picto) ?? '';
            $key = mb_strtolower($categorie) . "#$picto";
            $groups[$key] ??= ['picto' => $picto, 'categorie' => $categorie, 'descriptions' => []];
            $description = $this->string('description', $g);
            if ($description !== null) {
                $groups[$key]['descriptions'][] = $description;
            }
        }

        return array_values($groups);
    }

    // ── Travaux (p.5-6) ─────────────────────────────────────────────────────

    /**
     * Packs de travaux : 1 (essentiels), 2 (à envisager). Un pack « 1+2 »
     * (enum 3) du XML porte la performance après les deux packs.
     *
     * @return array<int, array{conso: ?float, ges: ?float, cout_min: ?float, cout_max: ?float, travaux: list<array{lot: int, description: string, avertissement: ?string, performance: ?string}>}>
     */
    public function packsTravaux(): array
    {
        $packs = [];
        foreach ($this->elements('/dpe/descriptif_travaux/pack_travaux_collection/pack_travaux') as $p) {
            $num = $this->int('enum_num_pack_travaux_id', $p) ?? 1;
            $travaux = [];
            foreach ($this->elements('travaux_collection/travaux', $p) as $t) {
                $travaux[] = [
                    'lot' => $this->int('enum_lot_travaux_id', $t) ?? 0,
                    'description' => $this->string('description_travaux', $t) ?? '',
                    'avertissement' => $this->string('avertissement_travaux', $t),
                    'performance' => $this->string('performance_recommande', $t),
                ];
            }
            $packs[$num] = [
                'conso' => $this->float('conso_5_usages_apres_travaux', $p),
                'ges' => $this->float('emission_ges_5_usages_apres_travaux', $p),
                'cout_min' => $this->float('cout_pack_travaux_min', $p),
                'cout_max' => $this->float('cout_pack_travaux_max', $p),
                'travaux' => $travaux,
            ];
        }
        ksort($packs);

        return $packs;
    }

    /**
     * Classes énergie et climat d'un état après travaux, avec les seuils
     * réglementaires du DPE (petites surfaces et altitude comprises).
     * L'étiquette énergie est la moins bonne des deux (double seuil).
     *
     * @return array{dpe: string, ges: string}
     */
    public function classesPour(float $epM2, float $gesM2): array
    {
        $context = new CalculationContext($this->document, new TableRepository(dirname(__DIR__, 3) . '/resources/tables'));
        $surface = $this->surfaceReference() ?? 0.0;
        $zone = $this->int('/dpe/logement/meteo/enum_zone_climatique_id');
        $altitude = $this->int('/dpe/logement/meteo/enum_classe_altitude_id');
        $energie = SeuilsClasses::energie((int) floor($epM2), $surface, $zone, $altitude, $context);
        $ges = SeuilsClasses::ges((int) floor($gesM2), $surface, $zone, $altitude, $context);

        return ['dpe' => max($energie, $ges), 'ges' => $ges];
    }

    public function commentaireTravaux(): ?string
    {
        return $this->string('/dpe/descriptif_travaux/commentaire_travaux');
    }

    // ── Fiche technique (annexes) ───────────────────────────────────────────

    /**
     * @return list<array{categorie: int, lignes: list<array{description: string, valeur: string, origine: ?int, detail: ?string}>}>
     */
    public function fichesTechniques(): array
    {
        $fiches = [];
        foreach ($this->elements('/dpe/fiche_technique_collection/fiche_technique') as $f) {
            $lignes = [];
            foreach ($this->elements('sous_fiche_technique_collection/sous_fiche_technique', $f) as $s) {
                $lignes[] = [
                    'description' => $this->string('description', $s) ?? '',
                    'valeur' => $this->string('valeur', $s) ?? '',
                    'origine' => $this->int('enum_origine_donnee_id', $s),
                    'detail' => $this->string('detail_origine_donnee', $s),
                ];
            }
            $fiches[] = ['categorie' => $this->int('enum_categorie_fiche_technique_id', $f) ?? 11, 'lignes' => $lignes];
        }

        return $fiches;
    }
}
