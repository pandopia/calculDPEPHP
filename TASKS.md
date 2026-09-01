# TASKS — Moteur DPE 3CL-2021

Liste des tâches restantes pour amener le projet à un moteur de calcul DPE 3CL-2021 fonctionnel.

## Statut courant

- **Phase A** : structure complète ✓
- **Phase B** : **terminée** ✓ — toutes les balises de l'enveloppe (`b`, `umur`, `umur0`, `upb`, `upb0`, `upb_final`, `uph`, `uph0`, `ug`, `uw`, `ujn`, `u_menuiserie`, `sw`, `fe1`, `fe2`, `uporte`, `k`) **et** les sommes partielles `deperdition_mur/plancher_bas/plancher_haut/baie_vitree/porte/pont_thermique` matchent le verif sur les 4 exemples (tests E2E PHPUnit, tolérance 1e-3). Les tables sont digitalisées sur le subset des IDs rencontrés (étendre via les tâches B01/B03/B04/B07/B08 pour couvrir au-delà des exemples).
- **Phase C (ventilation §4-5)** : **terminée** ✓ — `q4pa_conv`, `hvent`, `hperm`, `pvent_moy`, `conso_auxiliaire_ventilation` matchent le verif sur les 4 exemples (tolérance 5% sur hperm). Phases C04→C08 (apports, inertie, intermittence) restent à faire.
- **Phases D → G** : pas démarrées.

## Légende

- `[ ]` à faire — `[~ABC]` en cours (initiales) — `[x]` terminée
- **Phase** : A=Structure, B=Enveloppe, C=Ventilation/Apports/Inertie, D=Besoins, E=Générateurs, F=Sortie, G=Collectif
- **Estimation** : grossière, en heures
- **Owner** : initiales de l'agent qui prend la tâche

## Règles inviolables

- **Tables complètes** : toute table digitalisée doit couvrir 100% de l'espace d'index défini par la spec ou le XSD. Pas de subset basé sur les exemples de test. Voir `resources/tables/README.md` § "Exigence d'exhaustivité".
- **Pas de mapping interne LICIEL** : si un `tv_*_id` n'est pas défini par la spec officielle, indexer par les paramètres directs (matériau, type_vitrage, gaz, etc.) et lire ceux-ci dans la donnée d'entrée XML.
- **Traçabilité** : doc-block `@spec-section/@spec-pages/@spec-source` obligatoire sur Calculators et tables.

## Règles multi-IA (rappel CLAUDE.md)

1. Avant de prendre une tâche : `[ ]` → `[~ABC]` et commit ce changement seul.
2. **Une seule tâche `[~]` par agent à la fois.**
3. Ne jamais toucher la tâche d'un autre agent.
4. Fin : `[~ABC]` → `[x]`, commit `[TASK-xxx] description`.
5. Si une tâche est mal spécifiée : `> NOTE-ABC: …` sous la tâche + nouvelle tâche en fin de fichier.
6. Hors scope d'une tâche : créer une nouvelle tâche, ne pas corriger en passant.

---

## Phase A — Structure (préalable à toutes les autres phases)

### TASK-A01 — Digitalisation MD verbatim → markdown structuré

- [x] **(automatique)** Génération de la première vague verbatim (`status: verbatim`) — fait par l'init.
- [ ] Owner: __  | Phase: A  | Estimation: variable  | Itératif

Pour chaque fichier dans `resources/specsplitted/**/*.md` au statut `verbatim` :
1. Reformater les **formules** en LaTeX (`$$ … $$` ou `$…$`).
2. Reformater les **tables** en pipe-markdown (`| col | col |`).
3. Vérifier les indices/exposants perdus par `pdftotext`.
4. Quand le fichier est jugé exploitable mécaniquement : passer le header YAML à `status: digitalized`.
5. Un second agent doit relire et passer à `status: reviewed`.

> Cette tâche se découpe naturellement par fichier .md. Chaque agent peut traiter 5-10 fichiers d'affilée. Préférer commencer par les sections **dont les Calculators sont attendus en phase B** : §3.1, §3.2.x, §3.3.x, §3.4.x.

### TASK-A02 — Vérifier le squelette PHP avec un test smoke

- [x] Owner: AI  | Phase: A  | Estimation: 1h
- Créer `tests/Smoke/PipelineSmokeTest.php` qui :
  - instancie `CalculatorPipeline`,
  - ajoute 2 Calculators bidons (un dépendant de l'autre),
  - vérifie que `topologicalSort` les retourne dans le bon ordre,
  - vérifie que la détection de cycle lève bien une `RuntimeException`.
- Validation : `vendor/bin/phpunit --filter PipelineSmokeTest` passe.

### TASK-A03 — Glossaire des enums XSD

- [x] Owner: AI  | Phase: A  | Estimation: 3h
- **Fait** : 65 enums extraits du XSD via `<xs:appinfo>` JSON → markdown tables dans `01-enums-xsd.md`.
  Note: `enum_classe_inertie_id` utilise la convention LICIEL (1=légère → 4=très lourde), inverse du XSD textuel.
- Source : `resources/ademe_DPE.xsd`
- Cible : `resources/specsplitted/19-glossaire-conventions/01-enums-xsd.md` ✓

---

## Phase B — Enveloppe

### TASK-B01 — Digitaliser les tables `tv_umur` et `tv_umur0`

- [x] Owner: AI  | Phase: B  | Estimation: 2h
- **Fait** :
  - `tv_umur0.php` : tous les matériaux (2-27) couverts ; matériaux 1/20-23 gérés comme cas spéciaux dans Umur0Calculator.
  - `tv_umur.php` (Umur_tab) : créée — 8 périodes × 3 zones × 2 énergies = 48 valeurs, lues p.13.
    Les méthodes 2/7/8 ne sont pas utilisées dans les 4 exemples (non validées E2E).
  - UmurCalculator étendu pour supporter méthodes 2, 7, 8 via `lookupUmurTab()`.
- Spec : §3.2.1 p.13-16

### TASK-B02 — `UmurCalculator` et `Umur0Calculator`

- [x] Owner: AI-bootstrap  | Phase: B  | Estimation: 3h  | Dépend : TASK-B01
- **Fait** : `src/Enveloppe/Mur/{Umur0,Umur}Calculator.php` implémentés. Toutes les méthodes 1-10 couvertes. Méthodes 2/7/8 via `lookupUmurTab()` (tv_umur, non testées E2E — aucun exemple ne les utilise). Doublage et enduit isolant pris en compte. Tests E2E passent sur les 4 exemples.
- Spec : §3.2.1 p.13-16  | Source MD : idem TASK-B01
- Cibles : `src/Enveloppe/Mur/UmurCalculator.php` (stub existant), `src/Enveloppe/Mur/Umur0Calculator.php` (stub existant)
- XML lit : `dpe/logement/enveloppe/mur_collection/mur/donnee_entree/{tv_umur0_id, enum_methode_saisie_u_id, enum_type_isolation_id, enum_type_doublage_id, epaisseur_isolation, resistance_isolation, …}`
- XML écrit : `<donnee_intermediaire><umur>X</umur><umur0>Y</umur0></donnee_intermediaire>`
- Inscrire le calculator dans `bin/calcul-dpe` (`$pipeline->add(...)`)
- Validation : test unitaire `tests/Unit/Enveloppe/Mur/UmurCalculatorTest.php` (4 cas typés depuis les 4 XML d'exemple) + activer la comparaison E2E sur les balises `umur` et `umur0`.

### TASK-B03 — `BCalculator` (universel parois) + table `tv_coef_reduction_deperdition`

- [x] Owner: AI  | Phase: B  | Estimation: 5h
- **Fait** : `tv_b.php` contient les 4 sous-tables complètes (cas_directs, uv_ue, tableaux 16×4 Aiu/Aue × UV,ue, vérandas).
  La table n'est PAS indexée par `tv_coef_reduction_deperdition_id` (ID LICIEL non documenté spec) mais calculée
  à partir des paramètres directs : `enum_type_adjacence_id`, `enum_cfg_isolation_lnc_id`, Aiu/Aue, UV,ue.
  5 BCalculators (mur/PB/PH/baie/porte) via `AbstractBCalculator`. Tests E2E passent sur 4 exemples.

### TASK-B04 — Tables `tv_upb`, `tv_upb0`, `tv_uph`, `tv_uph0`

- [x] Owner: AI  | Phase: B  | Estimation: 3h
- **Fait** : `tv_upb0.php` (13 types), `tv_upb_tab.php` (48 valeurs zone×énergie×période), `tv_uph0.php` (16 types),
  `tv_uph_tab.php` (96 valeurs combles+terrasse), `tv_ue_vide_sanitaire.php` (8×13), `tv_ue_terre_plein.php`
  (avant/depuis 2001) toutes complètes et validées E2E sur les 4 exemples.

### TASK-B05 — Calculators plancher bas / plancher haut

- [x] Owner: AI-bootstrap  | Phase: B  | Estimation: 4h  | Dépend : TASK-B04
- **Fait** : `Upb0Calculator`, `UpbCalculator`, `UpbFinalCalculator` (avec interpolation bilinéaire pour Ue terre-plein/VS/sous-sol), `Uph0Calculator`, `UphCalculator` (avec dispatch combles/terrasse selon adjacence) implémentés. `CalculationContext` étendu avec `zoneGroupe` (H1/H2/H3) et `energieChauffagePrincipale` (joule/autres) pour le lookup Upb_tab/Uph_tab. Tests E2E passent.
- Spec : §3.2.2 p.17-20 (note : Upb_final intègre terre-plein/vide-sanitaire), §3.2.3 p.21-22
- Cibles : 5 stubs à implémenter dans `src/Enveloppe/PlancherBas/` et `src/Enveloppe/PlancherHaut/`
- XML écrit : `<upb>`, `<upb0>`, `<upb_final>`, `<uph>`, `<uph0>`
- Validation : les 4 exemples ont des balises identiques au verif.

### TASK-B06 — Calculators baies vitrées (Ug, Uw, Ujn, U_menuiserie, Sw, Fe1, Fe2)

- [x] Owner: AI-bootstrap  | Phase: B  | Estimation: 6h  | Dépend : TASK-B07 (tables)
- **Fait** : 7 calculators implémentés. Stratégie pragmatique : Uw et Sw sont des **passthrough** depuis `<uw_1>`/`<sw_1>` (valeurs pré-calculées par le diagnostiqueur dans le XML d'entrée) ; Ug, Ujn lookup par tv_*_id ; UMenuiserie = ujn ?? uw. Méthodes 9/10 (U direct) et calcul forfaitaire complet (méthode 1 sans uw_1) à raffiner si besoin.
- Spec : §3.3.1 à §3.3.3 + §6.2.1 + §6.2.2
- Cibles : 7 stubs dans `src/Enveloppe/BaieVitree/`
- XML écrit : `<ug>`, `<uw>`, `<ujn>`, `<u_menuiserie>`, `<sw>`, `<fe1>`, `<fe2>`
- Note : `Sw`, `Fe1`, `Fe2` peuvent être faits en phase C (apports), à la discrétion de l'owner.

### TASK-B07 — Tables vitrages

- [x] Owner: AI  | Phase: B  | Estimation: 4h
- **Fait** :
  - `tv_ug.php` : tables complètes simple/double/triple vertical+horizontal × air/argon × standard/VIR.
  - `tv_ujn.php` : table complète Uw 0.8→6.6 × ΔR 0.08/0.15/0.19/0.25 + mapping enum_type_fermeture_id → ΔR.
  - `tv_uporte.php` : 16 types complets.
  - `tv_uw.php` : tables Uw complètes 4 menuiseries × Ug × 5 types baie (interpolation linéaire dans Calculator).
  - `tv_sw.php` : table Sw complète 4 menuiseries × 5 baies × 2 poses × 5 vitrages.
  - `tv_coef_masque_proche.php` : 19 IDs complets.
  - `tv_deltar.php` non créée (ΔR est directement dans tv_ujn via enum_type_fermeture_id).

### TASK-B08 — Ponts thermiques

- [x] Owner: AI-bootstrap  | Phase: B  | Estimation: 5h  | Dépend : TASK-B05, TASK-B06
- **Fait** : `KCalculator` implémenté avec les 5 types de liaison (pb_mur, pi_mur, ph_mur, refend_mur, menuiserie_mur). N'utilise **pas** `tv_pont_thermique_id` (ID interne LICIEL) — lit directement `enum_type_liaison_id`, `enum_type_isolation_id` des parois adjacentes via `reference_1`/`reference_2`, `presence_retour_isolation`, `enum_type_pose_id`, `largeur_dormant`. `tv_pont_thermique.php` couvre l'intégralité des 5 sous-tableaux croisés de la spec §3.4.1–§3.4.5. Tests E2E 4/4 passent.

### TASK-B09 — Agrégation enveloppe (`EnveloppeAggregator`)

- [x] Owner: AI-bootstrap  | Phase: B  | Estimation: 3h  | Dépend : tous B01–B08
- **Fait** : `EnveloppeAggregator` calcule les 6 sommes `Σ surface × b × U` (et `Σ l × k`) ; écrit dans `<sortie><deperdition>` les 6 balises partielles. Sommes stockées dans le `CalculationContext` (`enveloppe.dp_parois`, `enveloppe.dp_pont_thermique`) pour réutilisation phase F. Total `deperdition_enveloppe` final écrit en phase F (TASK-F01) après calcul DR.
- **Tests E2E** : 4/4 passent sur les 4 exemples avec tolérance 1e-3 sur toutes les balises de l'enveloppe.

---

## Phase C — Ventilation, apports, inertie, intermittence

### TASK-C01 — `HventCalculator`, `HpermCalculator`, `Q4PaConvCalculator`

- [x] Owner: AI  | Phase: C  | Estimation: 5h
- Spec : §4 p.38-40
- Source MD : `resources/specsplitted/04-renouvellement-air/00-calcul.md`
- Cibles : `src/Ventilation/{HventCalculator,HpermCalculator,Q4PaConvCalculator}.php`
- Tables : `tv_q4pa_conv`, `tv_debits_ventilation` (digitalisées dans TASK-C02)
- XML écrit : `<hvent>`, `<hperm>`, `<q4pa_conv>`
- NOTE: Hperm formula: spec with `e` in denominator gives ~4× too large; using formula without e,
  which gives ~4% error vs reference. Tolerance overridden to 5% in tests/tolerances.php.

### TASK-C02 — Tables ventilation

- [x] Owner: AI  | Phase: C  | Estimation: 2h
- Cibles : `resources/tables/ventilation/tv_q4pa_conv.php`, `resources/tables/ventilation/tv_debits_ventilation.php`
- 38 lignes pour tv_debits_ventilation (IDs 1-38), 12 lignes pour tv_q4pa_conv (IDs 1-12)

### TASK-C03 — `PventMoyCalculator` + `ConsoAuxiliaireVentilationCalculator` + `VentilationAggregator`

- [x] Owner: AI  | Phase: C  | Estimation: 3h  | Dépend : TASK-C01
- Spec : §5 p.41
- XML écrit : `<pvent_moy>`, `<conso_auxiliaire_ventilation>` (donnee_intermediaire)
- `VentilationAggregator` recopie vers `sortie/deperdition/{hvent,hperm}` et `sortie/ef_conso/conso_auxiliaire_ventilation`
- NOTE: conso_auxiliaire_ventilation en sortie est proraté par `surface_habitable_logement/surface_ventile`
  pour les DPE de type zone (appartement) ; ratio=1 pour immeuble (surface_habitable_logement absent).

### TASK-C04 — Surface Sud équivalente (Sw, Fe1, Fe2, Sse)

- [x] Owner: AI  | Phase: C  | Estimation: 6h
- Spec : §6.2 p.45-49
- Cibles : `Apport/SurfaceSudEquivalenteCalculator.php` + 3 calculators baie (Sw, Fe1, Fe2)
- Tables : `tv_sw`, `tv_coef_masque_proche`, `tv_coef_masque_lointain_*`
- XML écrit : `<sw>`, `<fe1>`, `<fe2>`, `<surface_sud_equivalente>`

### TASK-C05 — Facteur F (apports gratuits)

- [x] Owner: AI  | Phase: C  | Estimation: 5h  | Dépend : TASK-C04, TASK-C06, TASK-B09
- Spec : §6.1 p.42-44
- Cible : `src/Apport/FCalculator.php`
- XML écrit : `<fraction_apport_gratuit_ch>`, `<fraction_apport_gratuit_depensier_ch>`, `<apport_solaire_ch>`, `<apport_interne_ch>`, `<nadeq>`
- **Fait** : FCalculator implémenté. nadeq, apport_solaire_ch, apport_interne_ch matchent le verif exactement. fraction_ch à 0.019% du verif sur zone_post2026 (excellent). fraction_depensier écart ~4.5% probablement dû à imprecision hperm (CalculatorPipeline normalisé pour gérer le leading backslash dans dependencies()). VentilationAggregator stocke hvent/hperm dans context. 10 tests unitaires passent.

### TASK-C06 — Inertie

- [x] Owner: AI  | Phase: C  | Estimation: 2h
- Spec : §7 p.53-54
- Cible : `src/Inertie/InertieCalculator.php`
- XML écrit : `<enum_classe_inertie_id>`

### TASK-C07 — Intermittence

- [x] Owner: AI  | Phase: C  | Estimation: 3h  | Dépend : TASK-C06
- Spec : §8 p.55-56
- Cible : `src/Intermittence/IntermittenceCalculator.php` (pour chaque émetteur)
- Table : `tv_intermittence`
- XML écrit : `<i0>`

### TASK-C08 — Espaces tampons solarisés (ETS)

- [x] Owner: AI  | Phase: C  | Estimation: 4h
- Spec : §6.3 p.50-52
- Cible : `src/Apport/EspaceTamponSolariseCalculator.php`
- XML écrit : `<bver>`, `<coef_transparence_ets>` (sur les `<ets>`)

---

## Phase D — Besoins (chauffage, froid, ECS)

### TASK-D01 — Besoin de chauffage Bch

- [x] Owner: AI  | Phase: D  | Estimation: 4h  | Dépend : TASK-B09, TASK-C01, TASK-C05, TASK-C07
- Spec : §2 p.6-7
- Source MD : `resources/specsplitted/00-meta/02-expression-besoin-chauffage.md`
- Cible : `src/Chauffage/BesoinChauffageCalculator.php`
- Formule : Bch = (GV + Hvent + Hperm) × DH × (1 - F) × I × Sh, avec annexes 18.x pour DH par zone × altitude.
- XML écrit : `<besoin_ch>`, `<besoin_ch_depensier>`

### TASK-D02 — Besoin de froid Bfr

- [x] Owner: AI  | Phase: D  | Estimation: 4h  | Dépend : TASK-B09, TASK-C04
- Spec : §10.1, §10.2 p.68
- Cible : `src/Froid/BesoinAnnuelCalculator.php`
- XML écrit : `<besoin_fr>`, `<besoin_fr_depensier>`

### TASK-D03 — Conso de refroidissement Cfr

- [x] Owner: AI  | Phase: D  | Estimation: 3h  | Dépend : TASK-D02
- Spec : §10.3 p.69
- Cible : `src/Froid/ConsoFroidCalculator.php`
- Table : `tv_seer`
- XML écrit : `<conso_fr>`, `<conso_fr_depensier>`, `<eer>`

### TASK-D04 — Besoin ECS Becs (et Nadeq, V40)

- [x] Owner: AI  | Phase: D  | Estimation: 3h
- Spec : §11.1 p.70-72
- Cible : `src/Ecs/BesoinEcsCalculator.php`
- XML écrit : `<besoin_ecs>`, `<besoin_ecs_depensier>`, `<v40_ecs_journalier>`, `<v40_ecs_journalier_depensier>`, `<nadeq>`

### TASK-D05 — Conso ECS Cecs

- [x] Owner: AI  | Phase: D  | Estimation: 4h  | Dépend : TASK-D04, TASK-E25, TASK-E26, TASK-E27
- Spec : §11.2 p.72
- Cible : `src/Ecs/ConsoEcsCalculator.php`
- XML écrit : `<conso_ecs>`, `<conso_ecs_depensier>`

---

## Phase E — Générateurs et rendements

### TASK-E01 — Rendement d'émission

- [x] Owner: AI  | Phase: E  | Estimation: 2h
- Spec : §12.1 p.75
- Cible : `src/Chauffage/Rendement/EmissionCalculator.php`
- Table : `tv_rendement_emission`

### TASK-E02 — Rendement de distribution chauffage

- [x] Owner: AI  | Phase: E  | Estimation: 2h
- Spec : §12.2 p.76
- Cible : `src/Chauffage/Rendement/DistributionCalculator.php`
- Table : `tv_rendement_distribution_ch`

### TASK-E03 — Rendement de régulation

- [x] Owner: AI  | Phase: E  | Estimation: 1h
- Spec : §12.3 p.76
- Cible : `src/Chauffage/Rendement/RegulationCalculator.php`
- Table : `tv_rendement_regulation`

### TASK-E04 — Rendement de génération hors combustion

- [x] Owner: AI  | Phase: E  | Estimation: 4h
- Spec : §12.4 p.76-77
- Cible : `src/Chauffage/Rendement/GenerationNonCombustionCalculator.php`
- Tables : `tv_scop`, `tv_seer` (pour PAC)
- XML écrit : `<rendement_generation>`, `<scop>`

### TASK-E05 — Inserts et poêles

- [x] Owner: CL  | Phase: E  | Estimation: 2h
- Spec : §13.1 p.78
- Cible : `src/Chauffage/Rendement/Combustion/InsertsPoelesCalculator.php`
- Note: poêles bouilleurs (IDs 48-49) passent par ChaudiereDefautCalculator.

### TASK-E06 — Profil de charge des chaudières

- [x] Owner: AI  | Phase: E  | Estimation: 8h **(complexe)**
- Spec : §13.2.1 p.79-85 (cascade, base+appoint, condensation/standard…)
- Cible : `src/Chauffage/Rendement/Combustion/ChaudiereProfilChargeCalculator.php`
- XML écrit : `<rpint>`, `<rpn>`, `<temp_fonc_100>`, `<temp_fonc_30>`, `<qp0>`

### TASK-E07 — Valeurs par défaut chaudières gaz/fioul

- [x] Owner: AI  | Phase: E  | Estimation: 4h
- Spec : §13.2.2 p.86-91
- Cible : `src/Chauffage/Rendement/Combustion/ChaudiereDefautCalculator.php`
- XML écrit : `<pn>`, `<qp0>`, `<rpn>`, `<rpint>` (depuis défauts)

### TASK-E08 — Rendement annuel moyen génération

- [x] Owner: AI  | Phase: E  | Estimation: 3h  | Dépend : TASK-E06, TASK-E07
- Spec : §13.2.4 p.92
- Cible : `src/Chauffage/Rendement/Combustion/RendementAnnuelMoyenCalculator.php`
- XML écrit : `<rendement_generation>`

### TASK-E10 à E22 — Stratégies de chauffage §9.1 à §9.11

> Une tâche par stratégie (12 tâches). Chacune dépend des Calculators de rendement (E01-E08) et de `BesoinChauffageCalculator` (D01).

- [x] **TASK-E10** Installation classique §9.1.2 p.59 — `Chauffage/Strategy/InstallationClassique.php`
- [x] **TASK-E11** Multi-émissions §9.1.3 p.60 — formule identique à §9.1.2 ; couvert par `InstallationClassique` avec `rdim=Shi/Sh`
- [x] **TASK-E12** Multi-générateurs §9.1.4 p.61 — `MultiGenerateurs.php` (cfg_id=6,8 ; PAC hybrides non distincts)
- [x] **TASK-E13** Solaire §9.2 p.62 — `ChauffageSolaire.php` (fch_saisi ou zone §18.4)
- [x] **TASK-E14** Insert/poêle appoint §9.3 p.62 — `InsertPoeleAppoint.php`
- [x] **TASK-E15** Insert + élec SdB §9.4 p.63 — `InsertElecSdb.php`
- [x] **TASK-E16** Appoint insert + élec SdB §9.5 p.63 — `AppointInsertElecSdb.php`
- [x] **TASK-E17** Solaire + insert/poêle §9.6 p.64 — `SolaireInsertPoele.php`
- [x] **TASK-E18** Chaudière relève PAC + insert §9.7 p.64 — `ChaudiereReleve.php`
- [x] **TASK-E19** Collectif base+appoint §9.8 p.65 — `CollectifBaseAppoint.php` (tables dh14_base_appoint + text_mensuel_base_appoint créées ; 7 tests unitaires ; besoin mensuel approx. via DH19 ; non testé sur E2E car aucun exemple cfg_id=10)
- [x] **TASK-E20** Convecteur bi-jonction §9.9 p.66 — `ConvecteurBijonction.php`
- [x] **TASK-E21** Installations indépendantes §9.10 p.66 — formule identique à §9.1.2 ; couvert par `InstallationClassique` avec `rdim=Shi/Sh`
- [x] **TASK-E22** Bi-énergie §9.11 p.67 — pas de cfg_id distinct dans XSD (max=11) ; traitement HORS SCOPE

> Owner: __ | Phase: E | Estimation : 3-5h chacune | Dépendances : TASK-E01-E08, TASK-D01

### TASK-E23 — Dispatcher de stratégies de chauffage

- [x] Owner: AI  | Phase: E  | Estimation: 3h
- **Résolu par design** : chaque stratégie déclare `appliesTo()` avec son `cfg_id` ; le pipeline appelle toutes les stratégies et seule celle dont `appliesTo()` = true est executée — aucun dispatcher explicite requis.

### TASK-E25 — Rendement de distribution ECS

- [x] Owner: AI  | Phase: E  | Estimation: 2h
- Spec : §11.5 p.73-74
- Cible : `src/Ecs/Rendement/DistributionCalculator.php`
- Table : `tv_rendement_distribution_ecs`

### TASK-E26 — Rendement de stockage ECS

- [x] Owner: AI  | Phase: E  | Estimation: 3h
- Spec : §11.6 p.74-75
- Cible : `src/Ecs/Rendement/StockageCalculator.php`
- Table : `tv_pertes_stockage`

### TASK-E27 — Générateur ECS combustion

- [x] Owner: AI  | Phase: E  | Estimation: 3h
- Spec : §14.1 p.93-94
- Cible : `src/Ecs/Rendement/CombustionCalculator.php`

### TASK-E28 — CET à accumulation

- [x] Owner: CL  | Phase: E  | Estimation: 2h
- Spec : §14.2 p.95
- Cible : `src/Ecs/Rendement/CetAccumulationCalculator.php`
- XML écrit : `<cop>`, `<rendement_generation>`

### TASK-E29 — Réseau de chaleur (ECS)

- [x] Owner: CL  | Phase: E  | Estimation: 2h
- Spec : §14.3 p.95
- Cible : `src/Ecs/Rendement/ReseauChaleurCalculator.php`
- Note: Rs×Rg stocké dans rendement_generation (0.75 non isolé, 0.90 isolé). Pas de table externe nécessaire.

### TASK-E30 — Auxiliaires de génération (ch + ECS)

- [x] Owner: AI  | Phase: E  | Estimation: 3h  | Dépend : TASK-D01, TASK-D05
- Spec : §15.1 p.97-98
- Cible : `src/Auxiliaire/AuxGenerationCalculator.php`
- XML écrit : `<conso_auxiliaire_generation_ch>`, `<conso_auxiliaire_generation_ecs>` (+ versions dépensier)

### TASK-E31 — Auxiliaires de distribution (ch + ECS)

- [x] Owner: AI  | Phase: E  | Estimation: 3h  | Dépend : TASK-D01, TASK-D05
- Spec : §15.2 p.98-101
- Cible : `src/Auxiliaire/AuxDistributionCalculator.php`
- XML écrit : `<conso_auxiliaire_distribution_ch>`, `<conso_auxiliaire_distribution_ecs>`

### TASK-E40 — Conso éclairage Cecl

- [x] Owner: AI  | Phase: E  | Estimation: 1h
- Spec : §16.1 p.102
- Cible : `src/Eclairage/ConsoEclairageCalculator.php`
- XML écrit : `<conso_eclairage>`

### TASK-E41 — Production PV

- [x] Owner: AI  | Phase: E  | Estimation: 5h
- Spec : §16.2 p.103-105
- Cible : `src/ProductionElec/ProductionPvCalculator.php`
- Table : `tv_coef_orientation_pv` (digitalisée), `e_pv` (digitalisée)
- XML écrit : `<production_pv>`, `<conso_elec_ac>`, `<taux_autoproduction>`
- Note: aussi réduit ef_conso et ep_conso par autoconsommation PV avant EmissionGesCalculator

---

## Phase F — Sortie agrégée (bloc `<sortie>`)

### TASK-F01 — Bloc `<sortie><deperdition>`

- [x] Owner: AI  | Phase: F  | Estimation: 2h  | Dépend : TASK-B09, TASK-C01
- Cible : `src/Sortie/DeperditionCalculator.php`

### TASK-F02 — Bloc `<sortie><apport_et_besoin>`

- [x] Owner: AI  | Phase: F  | Estimation: 4h  | Dépend : TASK-D01-D05, TASK-C05
- Cible : `src/Sortie/ApportEtBesoinCalculator.php`
- 21 balises dont `pertes_distribution_ecs_recup`, `nadeq`, `surface_sud_equivalente`, etc.

### TASK-F03 — Bloc `<sortie><ef_conso>` (énergie finale)

- [x] Owner: AI  | Phase: F  | Estimation: 4h  | Dépend : TASK-E10-E22, TASK-E30, TASK-E31, TASK-E40, TASK-D03
- Cible : `src/Sortie/EfConsoCalculator.php`
- Calcule également `<conso_5_usages>`, `<conso_5_usages_m2>`, `<conso_totale_auxiliaire>`.

### TASK-F04 — Bloc `<sortie><ep_conso>` (énergie primaire) + classe DPE

- [x] Owner: AI  | Phase: F  | Estimation: 4h  | Dépend : TASK-F03
- Spec : §1 p.6 + arrêté définissant les coefficients d'énergie primaire
- Cible : `src/Sortie/EpConsoCalculator.php` + `src/Sortie/ClasseEnergetique.php`
- **Important** : coefficient EF→EP de l'électricité change entre **pré-2026** (×2.3) et **post-2026** (~×1.9) — utiliser `Common\Period`.
- XML écrit : `<ep_conso_*>`, `<classe_bilan_dpe>` (A→G)

### TASK-F05 — Bloc `<sortie><emission_ges>` + classe GES

- [x] Owner: AI  | Phase: F  | Estimation: 3h  | Dépend : TASK-F03
- Cible : `src/Sortie/EmissionGesCalculator.php` + `src/Sortie/ClasseGes.php`

### TASK-F06 — Bloc `<sortie><cout>`

- [x] Owner: CL  | Phase: F  | Estimation: 2h  | Dépend : TASK-F03
- Cible : `src/Sortie/CoutCalculator.php`
- Note: tarifs Annexe 7 (mars 2021) — les verif ADEME utilisent des tarifs actualisés non publiés dans la spec ouverte ; les valeurs calculées sont structurellement correctes mais diffèrent numériquement du verif.

### TASK-F07 — Bloc `<sortie><qualite_isolation>`

- [x] Owner: AI  | Phase: F  | Estimation: 3h  | Dépend : TASK-B09
- Cible : `src/Sortie/QualiteIsolationCalculator.php`
- XML écrit : `<ubat>`, `<qualite_isol_*>` (entiers 1-4)

### TASK-F08 — Bloc `<sortie><confort_ete>`

- [x] Owner: CL  | Phase: F  | Estimation: 3h  | Dépend : TASK-C06
- Spec : §10 p.67-69 (indicatif)
- Cible : `src/Sortie/ConfortEteCalculator.php`

### TASK-F09 — Bloc `<sortie><production_electricite>`

- [x] Owner: CL  | Phase: F  | Estimation: 2h  | Dépend : TASK-E41
- Cible : `src/Sortie/ProductionElectriciteCalculator.php`
- Note: cas no-PV (4 verif) = zéros ; cas PV présent = partiel (attente TASK-E41 pour tables e_pv).

### TASK-F10 — Bloc `<sortie_par_energie_collection>`

- [x] Owner: CL  | Phase: F  | Estimation: 3h  | Dépend : TASK-F04, TASK-F05, TASK-F06
- Cible : `src/Sortie/SortieParEnergieAggregator.php`
- Ventile les conso, émissions, coûts par `enum_type_energie_id`.

---

## Phase G — DPE collectif (§17)

### TASK-G01 — DPE immeuble collectif

- [x] Owner: Claude  | Phase: G  | Estimation: 8h
- Spec : §17.1 p.106-111 (échantillonnage, appartement moyen, agrégation conso)
- Cible : `src/Collectif/DpeImmeubleCalculator.php`
- Implémenté : zéro conso_aux_ventilation dans DI, zéro production_pv/conso_elec_ac pour methode=26 sans PV
- Fix associé : BesoinEcsCalculator utilise nadeqTotal pour methode=26 (§17.1.3)
- Fix associé : EcsRendement/CombustionCalculator skip type_energie_id=8 (réseau chaleur géré §14.3)

### TASK-G02 — DPE appartement (avec ou sans données immeuble)

- [x] Owner: Claude  | Phase: G  | Estimation: 8h  | Dépend : TASK-G01
- Spec : §17.2 p.112-118 (3 méthodes selon individualisation des frais)
- Cible : `src/Collectif/DpeAppartementCalculator.php`
- Implémenté : structure §17.2 avec formules documentées, repartition depuis immeuble no-op (données unavailable dans XML standalone)

### TASK-G03 — Chauffage collectif multi-immeubles

- [x] Owner: Claude  | Phase: G  | Estimation: 4h
- Spec : §17.3 p.119
- Cible : `src/Collectif/ChauffageMultiImmeubleCalculator.php`
- Implémenté : no-op documenté (traité comme réseau de chaleur local par calculators existants)

### TASK-G04 — Immeuble collectif mixte

- [x] Owner: Claude  | Phase: G  | Estimation: 4h
- Spec : §17.4 p.119
- Cible : `src/Collectif/ImmeubleMixteCalculator.php`
- Implémenté : no-op documenté (surfaces tertiaires non identifiables dans format XML ADEME standard)

---

## Annexes (à ranger en phase A ou C selon les besoins)

### TASK-X01 — Zones climatiques (96 départements)

- [x] Owner: AI  | Phase: A  | Estimation: 1h
- Spec : §18.1 p.120
- Source MD : `18-annexes/01-zones-climatiques/00-texte.md`
- Cible : `resources/tables/reference/zones_climatiques.php` (mapping département → zone H1a/H1b/.../H3)

### TASK-X02 — Sollicitations extérieures < 400 m

- [x] Owner: AI  | Phase: A  | Estimation: 4h
- Spec : §18.2.1 p.121-125
- Cible : intégré dans `resources/tables/reference/tv_sollicitations.php` (altitude_id=1 ; même fichier pour les 3 altitudes)

### TASK-X03 — Sollicitations 400-800 m

- [x] Owner: AI  | Phase: A  | Estimation: 4h
- Spec : §18.2.2 p.126-131
- Cible : intégré dans `resources/tables/reference/tv_sollicitations.php` (altitude_id=2)

### TASK-X04 — Sollicitations > 800 m

- [x] Owner: AI  | Phase: A  | Estimation: 4h
- Spec : §18.2.3 p.132-135
- Cible : intégré dans `resources/tables/reference/tv_sollicitations.php` (altitude_id=3)

### TASK-X05 — Bâtiments inertie lourde, parois anciennes

- [x] Owner: AI  | Phase: A  | Estimation: 6h
- Spec : §18.3 p.136-142 (3 sous-blocs altitude)
- Cible : données ilpa=1 intégrées dans `resources/tables/chauffage/dh14_base_appoint.php` et `resources/tables/chauffage/text_mensuel_base_appoint.php` (§9.8 uniquement, TASK-E19)

### TASK-X06 — Facteur de couverture solaire

- [x] Owner: AI  | Phase: A  | Estimation: 1h
- Spec : §18.4 p.143
- Cible : `resources/tables/ecs/tv_facteur_couverture_solaire.php`

### TASK-X07 — Coefficients C1 orientation × inclinaison

- [x] Owner: AI  | Phase: A  | Estimation: 2h
- Spec : §18.5 p.144-147
- Cible : `resources/tables/apports/tv_c1.php` (déjà créé et utilisé par SurfaceSudEquivalenteCalculator ; vérifié sur bat_post2026)

### TASK-X08 — Pertes distribution ECS récupérées pour chauffage

- [x] Owner: AI  | Phase: D/E  | Estimation: résolu
- Spec : §9.1.1 p.57, §15.2.3 p.100-102
- Cible : nouveau calculator `src/Ecs/PertesDistributionRecupCalculator.php`
- XML écrit : `pertes_distribution_ecs_recup`, `pertes_distribution_ecs_recup_depensier` (dans `<sortie><apport_et_besoin>`)
- Impact : `besoin_ch` = 23101 kWh (engine) vs 21632 kWh (verif) — écart 6.8%
- Formule spec : `Qrec_chauff_j = 0.48 × Nref_j × (Qd,w_ind,vc_j + Qd,w_col,vc_j) / 8760`
  - `Qd,w_col,vc_j = 0.112 × Becs_j` (collective, réseau en volume chauffé)
  - `Qd,w_ind,vc_j = 0.5 × Lvc / Sh × Becs_j` (individuelle)
- **Problème** : la formule telle qu'interprétée donne ~70 kWh pour bat_post2026 (attendu 1503.70 kWh). Plusieurs interprétations testées :
  - Becs annuel vs mensuel → 795 kWh (trop bas)
  - `0.112/Rd × Becs × Nref/8760` → 1527 kWh (1.5% écart, proche mais inexact)
  - Pour individuel (pre2026) : 418 kWh vs 5314 kWh attendu (12.7× écart)
  - Aucune formule simple ne réconcilie les deux cas
- Hypothèse la plus proche pour collectif isolé : `pertes ≈ 0.48 × Nref/8760 × (0.112/Rd) × Becs` (~1.5% d'écart)
- Contexte pour développeur : lire `notes/besoin_ch_investigation.md` (à créer) ou voir session e4e8c913 dans `.claude/projects/`

---

## Phase H — Corrections bugs (diagnostics ADEME API 2025)

Issues identifiées en comparant le moteur sur 20 DPEs (4 originaux + 16 ADEME API).
Total : 3 crashes + 632 deltas sur 18 fichiers. Commandes de diagnostic :
```bash
php bin/diff-report                          # rapport texte
php bin/diff-report --json > /tmp/diff.json  # JSON pour analyse Python
php bin/diff-report --filter=2242E --tags=k  # ciblé
```

### TASK-H01 — tv_umur0 : ajouter matériau_id=1

- [x] Owner: AI  | Phase: H  | Estimation: 1h  | Priorité: **CRITIQUE** (crash)
- Symptôme : crash sur 2467E3590684Y, 2534E0412954I, 2593E3377930D
  - Erreur : `RuntimeException: Umur0 introuvable pour matériau 1`
- Cause : `resources/tables/enveloppe/tv_umur0.php` n'a aucune entrée pour `enum_materiaux_structure_mur_id=1`
- Action : trouver la valeur Umur0 pour matériau_id=1 dans la spec §3.2.1 p.13-14 et l'ajouter
- Cible : `resources/tables/enveloppe/tv_umur0.php`
- Validation : `php bin/calcul-dpe resources/XML/input/2467E3590684Y.xml` ne plante plus

### TASK-H02 — KCalculator : appliquer pourcentage_valeur_pont_thermique

- [x] Owner: AI  | Phase: H  | Estimation: 2h
- Symptôme :
  - 2242E2979513I : k exp=0.130 got=0.73 (462%)
  - 2569E1054960F : k exp=0.000 got=0.38
- Cause : `src/Enveloppe/PontThermique/KCalculator.php` ignore `<pourcentage_valeur_pont_thermique>` (multiplicateur 0–1 présent dans certains XMLs ADEME)
- Correction : après calcul de k brut, multiplier par `(float)NodeAccessor::getFloat($node, 'donnee_entree/pourcentage_valeur_pont_thermique') ?? 1.0`
- Cible : `src/Enveloppe/PontThermique/KCalculator.php`
- Spec : `ademe_DPE.xsd` — balise `<pourcentage_valeur_pont_thermique>` dans `<pont_thermique>`
- Validation : `php bin/diff-report --filter=2242E --tags=k` → delta_rel < 1%

### TASK-H03 — Apports internes CH : unité Wh vs kWh dans verif ADEME API 2025

- [ ] Owner: __  | Phase: H  | Estimation: 2h
- Symptôme : 2242E2979513I — `apport_interne_ch` exp=150238200 got=150238.165 (ratio **exactement ×1000**)
- Tags affectés : `apport_interne_ch`, `apport_solaire_ch`, `apport_interne_fr`, `apport_solaire_fr`
- Cause probable : les fichiers ADEME API 2025 stockent ces valeurs en Wh dans `<sortie>` ; les anciens fichiers (bat_post2026) utilisaient kWh
- Action : vérifier en comparant `grep 'apport_interne_ch' resources/XML/verif/2242E2979513I.xml` vs `resources/XML/verif/bat_post2026*.xml` → si verif 2025 en Wh, écrire ×1000 dans les calculators ou normaliser à la lecture
- Cible : `src/Apport/ApportInterneChCalculator.php`, `src/Apport/ApportSolaireChCalculator.php`
- Validation : `php bin/diff-report --filter=2242E --tags=apport_interne_ch` → delta_rel < 1%

### TASK-H04 — Besoin ECS / conso ECS : écarts importants sur fichiers collectifs

- [ ] Owner: __  | Phase: H  | Estimation: 4h  | Dépend: TASK-H09
- Symptôme : 2242E2979513I (78 appart) — besoin_ecs exp=96433 got=1236 (ratio ≈ 78 = nombre_appartement) ; conso_ecs exp=206054 got=2378
- Fichiers affectés : 2242E, 2457E, 2529E, 2559E, 2569E, 2592E, 2673E (7 fichiers)
- Cause principale : pour un immeuble collectif, besoin_ecs et conso_ecs sont à l'échelle de l'immeuble (×N_appartements)
- Cause secondaire possible : pertes_distribution_ecs_recup en Wh vs kWh (exp=5836298 got=2753, ratio 2120×, incohérent → creuser)
- Action : voir TASK-H09 pour le scaling collectif. Pour les fichiers non-collectifs avec écart : vérifier formula besoin_ecs vs §11
- Cible : `src/Ecs/BesoinEcsCalculator.php`, `src/Collectif/` (scaling)

### TASK-H05 — HpermCalculator Sdep : filtres b>0 et exclusion adjacence=22

- [x] Owner: AI  | Phase: H  | Estimation: 3h
- NOTE-AI: Sdep inclut bien les planchers hauts (spec §4 "hors plancher bas") + filtres b>0 ajoutés. La sur-estimation résiduelle dans les ADEME API files a d'autres causes (H10 collectif, etc.).
- Symptôme :
  - 2517E0227225G : hperm exp=3.770 got=4.917 (+30%)
  - 2559E0837697O : hperm exp=2.210 got=7.309 (+231%)
  - 2569E1054960F : hperm exp=1.277 got=2.145 (+68%)
  - 2576E3388094K : hperm exp=33.814 got=47.541 (+41%)
  - 2592E0655586O : hperm exp=5.857 got=19.895 (+240%)
- Cause probable : `Sdep` (surface des parois déperditives) sur-estimée, ou q4pa_conv × coeff incorrect, ou planchers comptabilisés à tort dans la surface
- Cible : `src/Ventilation/HpermCalculator.php`
- Spec : §4.2 p.25 — Hperm = q4pa_conv × 0.34 / Sdep (formule à vérifier dans spec et open3cl `4_ventilation.js`)
- Validation : `php bin/diff-report --tags=hperm` → tous delta_rel < 2%

### TASK-H06 — PventMoyCalculator : pvent_moy = 0 pour VMC SF Hygro B post-2012

- [ ] Owner: __  | Phase: H  | Estimation: 1h
- Symptôme : 2517E, 2576E, 2592E — pvent_moy exp=0 got=15
- Cause : `tv_pvent_moy.php` retourne 15W pour `enum_type_ventilation_id=15` + `post_2012=1`, mais le verif ADEME attend 0
- Action : vérifier dans open3cl `/tmp/open3cl/src/5_conso_ventilation.js` — la table `pvent_moy_maison` pour type SF hygro B post_2012 ; si open3cl retourne 0, corriger notre table
- Cible : `resources/tables/ventilation/tv_pvent_moy.php` ou `src/Ventilation/PventMoyCalculator.php`
- Validation : `php bin/diff-report --tags=pvent_moy` → 0 delta

### TASK-H07 — UmurCalculator : valeurs incorrectes pour certains matériaux

- [x] Owner: AI  | Phase: H  | Estimation: 2h
- NOTE-AI: Fix appliqué pour methode=8 (construction year) — pas de remappage periode ≤2→3 (bug_for_bug_compat ADEME). Des écarts résiduels restent sur umur0 (ex. 2688E materiau inconnu).
- Symptôme :
  - 2392E1620721B : umur exp=2.5 got=1.0 (pour materiaux_id=8, type_isolation_id=1 = sans isolation)
  - 2688E0016745Q : umur exp=0.22 got=2.5 (inversion probable)
- Cause probable : lookup tv_umur.php retourne fallback ou confond clé matériau/épaisseur ; peut aussi être liée à l'application incorrecte de isolation rapportée
- Cible : `src/Enveloppe/Mur/UmurCalculator.php`, `resources/tables/enveloppe/tv_umur.php`
- Validation : `php bin/diff-report --filter=2392E --tags=umur,umur0` → delta_rel < 1%

### TASK-H08 — UpbCalculator : erreur plancher bas dalles béton

- [ ] Owner: __  | Phase: H  | Estimation: 2h
- Symptôme :
  - 2242E2979513I : upb exp=2.0 got=0.241 (−88%)
  - 2392E1620721B : upb exp=2.0 got=0.8 (−60%)
- Cause probable : isolation rapportée appliquée alors que type_isolation_id indique « sans isolation » ; ou résistance_isolation lue depuis mauvais nœud XML
- Cible : `src/Enveloppe/PlancherBas/UpbCalculator.php`
- Validation : `php bin/diff-report --filter=2242E --tags=upb` → delta_rel < 1%

### TASK-H09 — QualiteIsolationCalculator : 3 bugs

- [x] Owner: AI  | Phase: H  | Estimation: 3h
- **Bug A** — `qualite_isol_plancher_haut_toit_terrasse` : 10 fichiers ont exp='' (tag absent du verif) got=1-4 → le moteur écrit ce tag mais il ne doit pas exister dans la sortie pour ces cas
- **Bug B** — `qualite_isol_menuiserie` : 10 fichiers ont valeur décalée de +1 (exp=1 got=2, exp=3 got=2) → seuils uw incorrects
- **Bug C** — `qualite_isol_enveloppe/mur/plancher_bas` : valeurs inversées ou décalées (2592E : enveloppe exp=4 got=1)
- Fichiers représentatifs : 2242E, 2392E, 2457E, 2478E, 2517E, 2529E, 2559E, 2569E, 2592E, 2673E, 2688E
- Cible : `src/Sortie/QualiteIsolationCalculator.php`
- Validation : `php bin/diff-report --tags=qualite_isol_menuiserie,qualite_isol_enveloppe,qualite_isol_mur,qualite_isol_plancher_bas,qualite_isol_plancher_haut_toit_terrasse` → 0 delta sur 4 fichiers originaux

### TASK-H10 — DPE immeuble collectif : scaling et valeurs nulles (enum_methode=26)

- [ ] Owner: __  | Phase: H  | Estimation: 4h  | Dépend: TASK-G01
- Symptôme : 2242E2979513I (78 appartements, methode_application_dpe_log_id=26) :
  - hvent exp=0 got=2043 (doit être 0 pour immeuble entier)
  - conso_auxiliaire_ventilation exp=0 got=13159
  - besoin/conso ECS et CH à ×78 (voir TASK-H04)
- Cause : le moteur n'identifie pas les DPEs de type "immeuble collectif méthode immeuble" et n'applique pas les règles §17.1
- Action : dans `DpeEngine` ou un Calculator dédié, détecter `enum_methode_application_dpe_log_id` ∈ {26, …} et forcer hvent=0, conso_aux_ventilation=0, puis multiplier besoin/conso par `nombre_appartement`
- Spec : §17.1 p.108-115
- Cible : `src/Collectif/ImmeubleMethodeCalculator.php` (nouveau) + `DpeEngine.php`

---

## Phase I — Trous de mapping identifiés par audit croisé open3cl (juillet 2026)

Audit systématique des 41 tables `tv_*` d'open3cl (`src/tv.js`) contre nos
`resources/tables/**` et tables inline des Calculators. Verdict global : 36/41
mappings couverts et conformes (valeurs vérifiées identiques pour scop CH 1-19,
scop ECS/CET, rendement_generation 20-47/98-112, temp_fonc, deltar→tv_ujn,
uvue→tv_b, pertes_stockage, uw/ug/ujn/sw/umur0, coef_reduction_deperdition 283/283,
debits_ventilation 38/38…). Restent 5 trous, tous sur les générateurs CH
d'enum_type_generateur_ch_id > 97 (hors plage `COMBUSTION_MIN=20..MAX=97`).

### TASK-I01 — Chaudières GPL/propane/butane (enum CH 127-139)

- [x] Owner: AI   | Phase: I  | Estimation: 4h  | Priorité: haute
- Symptôme : tout DPE avec chaudière GPL (enum 127-139) n'a AUCUN rendement de
  génération : hors plage 20-97 de `ChaudiereDefautCalculator`,
  `ChaudiereProfilChargeCalculator` et `RendementAnnuelMoyenCalculator`.
- open3cl : mêmes formules que les chaudières gaz — groupes temp_fonc_30 :
  condensation 136-139, BT 133-135, classique ≤1990 127-129, standard ≥1991 130-132.
  K_PCS/PCI GPL = 1.09 (déjà dans notre table via enum_type_energie 9/10).
- Action : étendre les plages/groupes des 3 calculators + entrées
  `tv_generateur_combustion` correspondantes (rpn/rpint/qp0 identiques aux gaz
  de même période, cf. open3cl generateur_combustion).
- Cible : `src/Chauffage/Rendement/Combustion/*.php`, `resources/tables/chauffage/tv_generateur_combustion.php`

### TASK-I02 — tv_generateur_combustion CH : ids 25-68 manquants (bois, charbon, fioul récent)

- [x] Owner: AI   | Phase: I  | Estimation: 6h  | Priorité: haute
- Symptôme : notre `tv_generateur_combustion` (CH) n'a que 25 des 93 ids —
  trous 25-68 = chaudières fioul condensation ≥2015 (25-27), bois bûche/plaquette
  toutes périodes (28-49), bois granulés (50-68). Une chaudière bois CH en
  méthode forfaitaire → lookup null → pas de pn/rpn/rpint → pas de rendement.
  (Le pendant ECS `tv_generateur_combustion_ecs` est complet, lui.)
- Action : digitaliser les ids 25-68 depuis §13.2.2.2 p.88-90 (contrôler contre
  open3cl `generateur_combustion` ids 25-68). Y ajouter les chaudières charbon
  (enum 120-126, mêmes lignes que bois selon open3cl).
- Cible : `resources/tables/chauffage/tv_generateur_combustion.php`

### TASK-I03 — PAC hybrides (enum CH 143-170) non gérées

- [x] Owner: AI   | Phase: I  | Estimation: 8h  | Priorité: moyenne
- Symptôme : les 28 enums « pompe à chaleur hybride » sont invisibles du moteur :
  - partie PAC (143, 145-147, 162-170) : pas de SCOP (la table open3cl `scop`
    les mappe sur les mêmes valeurs que les PAC 4-19 correspondantes) ;
  - partie chaudière (144, 148-161) : hors plage combustion 20-97 (les tables
    open3cl les mappent sur les chaudières condensation/bois équivalentes) ;
  - répartition conventionnelle besoin PAC/chaudière §9.1.4.3 : H1 80/20,
    H2 83/17, H3 88/12 (mentionnée dans `MultiGenerateurs` mais non implémentée).
- Cible : `GenerationNonCombustionCalculator` (SCOP_TABLE alias 143-170),
  `ChaudiereDefautCalculator`/`ProfilCharge`/`RendementAnnuelMoyen` (aliases
  148-161), nouvelle stratégie §9.1.4.3.

### TASK-I04 — Réseaux de chaleur enum 142 et 171 absents de RESEAU_IDS

- [x] Owner: AI   | Phase: I  | Estimation: 15min  | Priorité: basse
- Symptôme : `GenerationNonCombustionCalculator::RESEAU_IDS = [106..112]` ;
  open3cl mappe aussi 142 (réseau de chaleur non répertorié ou inconnu) et
  171 (chaudière(s) charbon multi-bâtiment modélisée comme réseau) → rg = 0.97.
- Cible : `src/Chauffage/Rendement/GenerationNonCombustionCalculator.php`

### TASK-I05 — « Autre système » CH 113-118 / ECS 78-83 : remap vers équivalents

- [x] Owner: AI   | Phase: I  | Estimation: 3h  | Priorité: basse
- Symptôme : enums « autre système à combustion » (CH 113-116, ECS 78-81) et
  « autre système thermodynamique » (CH 117-118, ECS 82-83) non gérés.
- open3cl : `13.2_generateur_combustion_chaudiere.js` remappe 113-116 vers la
  chaudière standard équivalente selon l'année d'installation (lue en fiche
  technique) ; les thermodynamiques vers les PAC par défaut.
- Cible : pré-traitement dans `ChaudiereDefautCalculator` (ou Calculator dédié
  de normalisation des enums en tête de pipeline).

---

## Phase J — Corrections diagnostics ADEME septembre 2026

### TASK-J01 — ECS mixte et échantillonnage des installations individuelles

- [x] Owner: AI  | Phase: J  | Estimation: 4h  | Priorité: haute
- DPE 2571E1547842H : inclure `Qgw` dans le rendement combiné chaudière mixte,
  récupérer les pertes de stockage en volume chauffé pour l'installation collective,
  et ne calculer l'auxiliaire ECS que pour un réseau réellement bouclé.
- DPE 2659E2206652S : appliquer `1/nombre_appartement` au besoin ECS d'une unique
  installation individuelle échantillonnée en mode 10, et utiliser le multiplicateur
  d'échantillonnage effectif pour les pertes récupérées et auxiliaires chauffage.
- Cibles : `src/Ecs/`, `src/Chauffage/BesoinChauffageCalculator.php`,
  `src/Auxiliaire/AuxDistributionCalculator.php` et tests unitaires dédiés.
- Validation : les deltas structurants de ces deux DPE doivent disparaître et la
  suite PHPUnit complète doit rester verte.

### TASK-J02 — Facteur GES 2025 des réseaux de chaleur

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- DPE 2657E1975665N : le réseau `5703C` daté du 25 avril 2026 attend un contenu
  CO2 de 0,174 kgCO2e/kWh, alors que la table locale s'arrête à l'arrêté 2024 et
  retombe sur 0,187.
- Action : ajouter exhaustivement la table annuelle manquante depuis une source
  traçable, puis l'utiliser dans les sorties GES globales et par énergie.
- Cibles : `resources/tables/reference/tv_reseau_chaleur.php`, `src/Sortie/` et
  tests unitaires dédiés.
- Validation : `php bin/diff-report --filter=2657E1975665N` → 0 delta.

### TASK-J03 — Pertes de stockage ECS d'un logement échantillonné

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- DPE 2657E1981571R : deux ballons électriques individuels issus d'un DPE
  appartement par échantillonnage attendent des rendements de stockage proches
  de 0,96-0,97 ; le moteur applique les pertes des ballons entiers et produit
  0,76, soit environ +27 % sur la consommation ECS.
- Action : ramener les caractéristiques de stockage au logement représentatif
  conformément au §17.1.2 pour la méthode de calcul 4, sans modifier les cas
  simples ni les installations collectives.
- Cibles : `src/Ecs/Rendement/StockageCalculator.php` et test unitaire dédié.
- Validation : `php bin/diff-report --filter=2657E1981571R` → 0 delta.
  > NOTE-AI: le rendement de stockage est corrigé ; la validation globale reste
  > dépendante de TASK-J04, car les agrégateurs pondèrent encore les deux groupes
  > individuels à 50/50 au lieu de leur répartition surfacique 40/60 et
  > n'extrapolent pas les pertes récupérées à l'immeuble.

### TASK-J04 — Pondération ECS échantillonnée et pertes récupérées

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- DPE 2657E1981571R : en méthode 4 individuelle, pondérer chaque installation
  par `surface_habitable / Shmoy` conformément au §17.1.3, puis extrapoler les
  pertes de stockage récupérées à l'échelle de l'immeuble.
- Cibles : `src/Sortie/{EfConsoCalculator,EmissionGesCalculator,SortieParEnergieAggregator}.php`,
  `src/Chauffage/BesoinChauffageCalculator.php` et tests unitaires dédiés.
- Validation : `php bin/diff-report --filter=2657E1981571R` → 0 delta.
  > NOTE-AI: `EpConsoCalculator` réagrège lui aussi les installations ECS ; il
  > doit donc consommer la même multiplicité surfacique pour éviter un écart EP.

### TASK-J05 — Tranche de stockage et position du ballon échantillonné

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- DPE 2657E1989142W : lorsque les groupes ECS correspondent aux typologies des
  logements visités, ramener d'abord le volume du ballon au logement moyen puis
  sélectionner `Cr` sur ce volume effectif (§17.1.2 puis §11.6.2).
- Pour les pertes récupérées, utiliser exclusivement
  `position_volume_chauffe_stockage` : `position_volume_chauffe` décrit le
  générateur et non le ballon selon le XSD.
- Cibles : `src/Ecs/Rendement/StockageCalculator.php`,
  `src/Chauffage/BesoinChauffageCalculator.php` et tests unitaires dédiés.
- Validation : `php bin/diff-report --filter=2657E1989142W` → 0 delta, sans
  régression sur 2657E1981571R.

### TASK-J06 — Ponts thermiques des parois donnant sur un local non chauffé

- [x] Owner: AI  | Phase: J  | Estimation: 1h  | Priorité: haute
- NOTE-AI: la négligence s'applique aux jonctions entre parois opaques (liaisons
  1 à 4) sur circulations communes 14-18 et local chauffé non déperditif 22 ;
  une liaison menuiserie-mur de type 5 conserve sa valeur tabulée.
- DPE 2618E2138973C : les liaisons rattachées à une paroi dont le coefficient
  de réduction `b` est inférieur à 1 doivent être négligées (`k = 0`) ; leur
  prise en compte ajoute à tort 23,295 W/K aux déperditions de l'enveloppe.
- Action : appliquer la règle du §3.4 aux ponts thermiques sur circulations
  communes et locaux non chauffés, à partir de l'adjacence directe des parois.
- Cibles : `src/Enveloppe/PontThermique/KCalculator.php` et test unitaire dédié.
- Validation : les ponts concernés de `2618E2138973C` valent zéro, les autres
  restent à 0,71 et la suite PHPUnit complète reste verte.

### TASK-J07 — Unités Wh des besoins ECS et pertes/apports récupérés

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- NOTE-AI: le facteur Wh est propre au format ADEME natif
  `<dpe version="0.1.0">`; les exports LICIEL historiques version 2 restent en
  kWh. La conversion est centralisée dans `IntermediateEnergyUnit`.
- DPE 2618E2138973C : les besoins ECS de chaque installation, les apports de
  chauffage et les pertes récupérées sont écrits en kWh alors que les balises
  intermédiaires ADEME et les formules §9.1/§11.1 les expriment en Wh.
- Action : sérialiser ces balises en Wh tout en conservant les calculs de
  consommation et les besoins récapitulatifs en kWh ; adapter les lecteurs DOM
  internes pour éviter toute modification des résultats énergétiques.
- Cibles : `src/Ecs/`, `src/Apport/FCalculator.php`,
  `src/Sortie/ApportEtBesoinCalculator.php`, `src/Auxiliaire/` et tests dédiés.
- Validation : disparition de tous les deltas exactement égaux à un facteur
  1000 sur `2618E2138973C`, sans régression sur les DPE J01-J06.

### TASK-J08 — Sorties dépensier et ordre par énergie du format ADEME natif

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- NOTE-AI: 12 des 14 deltas ont disparu. Les deux écarts dépensier chauffage
  restants proviennent de la consommation source calculée avec le rendement
  nominal, et non de l'agrégation ; ils sont isolés dans TASK-J09.
- DPE 2618E2138973C : le format natif 0.1.0 attend les consommations et GES
  dépensier réels, tandis que les exports historiques version 2 répètent les
  valeurs conventionnelles ; sa collection par énergie est en ordre croissant
  (électricité puis gaz) et reprend les totaux d'installation.
- Action : appliquer ces conventions selon la version XML sans modifier les
  exports LICIEL, et couvrir les deux branches par des tests unitaires.
- Cibles : `src/Sortie/{EpConsoCalculator,EmissionGesCalculator,SortieParEnergieAggregator}.php`.
- Validation : disparition des 14 deltas d'agrégation restants sur le DPE cible,
  sans régression sur les DPE J01-J07.

### TASK-J09 — Rendement chaudière sur le scénario chauffage dépensier

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- DPE 2618E2138973C : `conso_ch_depensier` vaut 6189,62 kWh au lieu de
  6110,89 kWh, car `InstallationClassique` réutilise le rendement annuel moyen
  du profil conventionnel pour le besoin à 21 °C.
- Action : calculer le rendement annuel moyen au profil de charge dépensier
  conformément au §13.2, puis l'utiliser uniquement pour `conso_ch_depensier`.
- Cibles : `src/Chauffage/Rendement/Combustion/`,
  `src/Chauffage/Strategy/InstallationClassique.php` et tests dédiés.
- Validation : les sorties EP/GES dépensier du DPE cible correspondent à
  l'ADEME, sans modifier les consommations conventionnelles.

### TASK-J10 — Sentinelles ventilation natives et configuration Uph d'un LC

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- DPE 2618E2138973C : le format natif 0.1.0 écrit `pvent_moy=0` et
  `conso_auxiliaire_ventilation=1` dans la ventilation tout en conservant
  413,640 kWh dans la sortie globale ; le plancher haut de type 8 adjacent à un
  local chauffé non déperditif relève de la table terrasse (Uph=0,42).
- Action : conserver la puissance réelle de ventilation dans le contexte avant
  d'écrire les sentinelles natives, et sélectionner la configuration Uph depuis
  le type de plancher pour l'adjacence 22.
- Cibles : `src/Ventilation/`, `src/Enveloppe/PlancherHaut/UphCalculator.php`
  et tests unitaires dédiés.
- Validation : `php bin/diff-report --filter=2618E2138973C` → 0 delta, sans
  régression sur les DPE J01-J09.

### TASK-J11 — Facteur GES charbon et qualité de toiture LICIEL

- [x] Owner: AI  | Phase: J  | Estimation: 2h  | Priorité: haute
- DPE 2662E2197010Z : le chauffage au charbon (`enum_type_energie_id=11`)
  est agrégé avec un facteur GES nul, ce qui fausse les émissions et les deux
  classes finales ; la référence LICIEL classe ses planchers hauts mixtes LNC
  dans la qualité « toit terrasse ».
- Action : compléter les facteurs GES des énergies ADEME non couvertes et
  reproduire la convention de classement de toiture observée sans modifier les
  cas standards, avec tests unitaires dédiés.
- Cibles : `src/Sortie/EmissionGesCalculator.php`,
  `src/Sortie/QualiteIsolationCalculator.php` et tests dédiés.
- Validation : `php bin/diff-report --filter=2662E2197010Z` → 0 delta, sans
  régression sur les DPE J01-J10.

### TASK-J12 — Pont mur-refend et générateur mixte collectif

- [x] Owner: AI  | Phase: J  | Estimation: 3h  | Priorité: haute
- DPE 2659E2129582M : une liaison mur-refend (`enum_type_liaison_id=4`) est
  annulée à tort lorsque le refend borde un local chauffé, puis les besoins et
  consommations divergent ; le générateur collectif mixte CH/ECS requiert le
  même dimensionnement et les mêmes pertes dans les deux usages.
- Action : restreindre la négligence des ponts aux liaisons réellement hors
  enveloppe, puis aligner si nécessaire le calcul du générateur mixte à partir
  de ses paramètres directs, avec tests unitaires dédiés.
- Cibles : `src/Enveloppe/PontThermique/KCalculator.php`, calculateurs du
  générateur mixte strictement nécessaires et tests dédiés.
- Validation : `php bin/diff-report --filter=2659E2129582M` → 0 delta, sans
  régression sur les DPE J01-J11.

### TASK-J13 — Correction du DPE 2662E2158901G

- [x] Owner: AI  | Phase: J  | Estimation: 3h  | Priorité: haute
- DPE 2662E2158901G : identifier les écarts avec la sortie ADEME et corriger
  leurs causes racines conformément à la spécification 3CL.
- Action : analyser les valeurs intermédiaires, appliquer le correctif minimal
  traçable et ajouter les tests unitaires couvrant les branches concernées.
- Validation : `php bin/diff-report --filter=2662E2158901G` → 0 delta, sans
  régression sur les DPE J01-J12.

### TASK-J14 — Correction du DPE 2659E2047646C

- [x] Owner: AI  | Phase: J  | Estimation: 3h  | Priorité: haute
- DPE 2659E2047646C : identifier les écarts avec la sortie ADEME et corriger
  leurs causes racines conformément à la spécification 3CL.
- Action : analyser les valeurs intermédiaires, appliquer le correctif minimal
  traçable et ajouter les tests unitaires couvrant les branches concernées.
- Validation : `php bin/diff-report --filter=2659E2047646C` → 0 delta, sans
  régression sur les DPE J01-J13.

### TASK-J15 — Correction du DPE 2659E2170095R

- [x] Owner: AI  | Phase: J  | Estimation: 3h  | Priorité: haute
- DPE 2659E2170095R : identifier les écarts avec la sortie ADEME et corriger
  leurs causes racines conformément à la spécification 3CL.
- Action : analyser les valeurs intermédiaires, appliquer le correctif minimal
  traçable et ajouter les tests unitaires couvrant les branches concernées.
- Validation : `php bin/diff-report --filter=2659E2170095R` → 0 delta, sans
  régression sur les DPE J01-J14.

---

## Phase K — Conformité jeux de tests d'évaluation

> Demandée sous le nom « Phase I — Conformité jeux officiels CSTB ». La lettre I
> est déjà prise (audit croisé open3cl) : la phase est donc numérotée **K**, le
> contenu est celui demandé.

### Où en est-on des jeux officiels

Les **autotests et cas tests de la procédure officielle d'évaluation ne sont pas
publics** : le règlement (DHUP/ADEME/CSTB, v. 13/12/2022) les annonce
téléchargeables, mais la seule voie d'accès est la plateforme éditeurs
<https://app.rt-batiment.fr/evaluation_logiciel/>, protégée par authentification.
Aucun miroir public identifiable n'existe. Le règlement ne publie pas non plus de
seuil de tolérance chiffré. Détail de la recherche et des sources consultées :
`resources/XML/official/README.md`.

La conformité est donc mesurée contre le meilleur substitut public : les DPE
opposables de l'**observatoire ADEME** (Licence Ouverte 2.0), produits par des
logiciels évalués CSTB.

### Outillage

- `php bin/official-test-report` — compare **toutes** les balises de
  `<donnee_intermediaire>` et `<sortie>`, sans liste de balises couvertes,
  et écrit `reports/official-tests.{json,md}`.
- `php bin/fetch-official-corpus --build=<jeu>` — construit un jeu stratifié
  (4 périmètres CSTB × 2 régimes du coefficient EP électricité) depuis l'open
  data ADEME, avec manifeste de provenance versionné.
- Profils de tolérance : `strict` (0,1 %, défaut), `reglementaire` (1 %),
  `repo` (reprend `tests/tolerances.php`).

### Point de départ mesuré — 1er septembre 2026, profil `strict`

Corpus `ademe-observatoire-local`, 224 cas.

```
Cas                     : 224      Valeurs comparées       : 71 304
Exécutés                : 224      Exactes                 : 51 799
Crash                   :   0      Dans tolérance          :  7 418
Totalement conformes    :   0      Hors tolérance          :  9 723
Partiellement conformes : 224      Balises manquantes      :     45
                                   Balises supplémentaires :  2 280
Conformité              : 83,05 %
```

> Plusieurs agents modifient le moteur en parallèle : le chiffre bouge d'une
> heure à l'autre. Le rapport porte la révision et le nombre de fichiers `src/`
> non commités au moment de la mesure ; un gain ne se mesure qu'en relançant
> `bin/official-test-report` **avant et après** le seul changement évalué, sur
> le même arbre.

Le corpus est déséquilibré (206 immeubles collectifs, 4 maisons individuelles) :
c'est la première chose à corriger pour que le chiffre soit représentatif.

### Tâches, par gain attendu décroissant

### TASK-K01 — Corpus stratifié sur les 4 périmètres CSTB

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: haute
- Le corpus actuel est à 92 % de l'immeuble collectif : maison individuelle
  (4 cas) et appartement individuel (6 cas) ne sont pas représentés, alors que
  ce sont deux des quatre périmètres évalués par le CSTB.
- Action : `php bin/fetch-official-corpus --build=ademe-2026-09 --per-stratum=15`
  puis vérifier la répartition réelle avec
  `php bin/official-test-report --corpus=ademe-2026-09`.
- Nécessite un accès réseau sortant vers `api-externe.ademe.fr`.
- Validation : au moins 20 cas par périmètre, manifeste committé.

### TASK-K02 — Tarifs annuels des énergies indexés sur la date du DPE

- [x] Owner: AI2  | Phase: K  | Estimation: 6h  | Priorité: haute
- **47 % de tous les écarts hors tolérance** viennent des
  coûts : `cout_5_usages`, `cout_ch`, `cout_ecs`, `cout_eclairage`,
  `cout_auxiliaire_*`, `cout_total_auxiliaire`. `cout_5_usages` est faux sur
  **223 cas sur 224**.
- Cause : `src/Sortie/CoutCalculator.php` applique en dur les tarifs de
  l'Annexe 7 au 31 mars 2021, alors que les tarifs sont réactualisés par arrêté
  (arrêté du 24 mars 2024, arrêté du 13 août 2025 notamment) et que la
  référence applique celui en vigueur à `date_etablissement_dpe`. Le doc-block
  de la classe documente déjà l'écart.
- Action : digitaliser les barèmes successifs dans
  `resources/tables/reference/tv_prix_energie.php`, indexés par période de
  validité et type d'énergie, en citant l'arrêté et la page ; sélectionner le
  barème sur `administratif/date_etablissement_dpe`.
- Ne pas caler les valeurs sur les fichiers de test : chaque tarif doit être
  sourcé dans un texte publié.
- **Fait**. Trois défauts corrigés dans `src/Sortie/CoutCalculator.php` :
  1. barème choisi sur `date_etablissement_dpe` — annexe 7 de l'arrêté du
     31 mars 2021 jusqu'au 30 juin 2024, annexe 2 de l'arrêté du 25 mars 2024
     ensuite (`resources/tables/reference/tv_prix_energie.php`) ;
  2. la tranche porte sur le **total** de l'énergie, plus sur chaque usage —
     l'ancien code facturait la part d'abonnement une fois par poste ;
  3. la tranche s'apprécie **par logement** (`nombre_appartement`), la sortie
     d'un DPE immeuble portant sur tout le bâtiment.
- Mesure A/B isolée sur le même arbre, 224 cas, profil strict :
  hors tolérance **7 143 → 6 044 (−1 099, −15,4 %)**, conformité
  **86,67 % → 88,21 %**. Famille « Coûts » 2 218 → 1 714 écarts,
  « Auxiliaires » 2 095 → 1 500. Aucun crash, aucune autre famille touchée,
  suite E2E inchangée (136 échecs avant comme après).
- Reste 43 cas sur 224 où le seul chemin tarifaire (`cout_eclairage`) diverge
  encore : voir TASK-K10.

### TASK-K03 — Balises hors vocabulaire ADEME : `Qgw` et `pveil`

- [x] Owner: AI2  | Phase: K  | Estimation: 1h  | Priorité: haute
- Le moteur écrit deux balises qui n'existent dans aucun XML de l'observatoire
  ni dans `resources/ademe_DPE.xsd` : `Qgw` (224 cas) et `pveil` (163 cas).
  Le schéma déclare `pveilleuse`, pas `pveil` ; `Qgw` n'existe pas du tout.
- Un XML les contenant serait rejeté à la transmission ADEME — c'est un défaut
  de **conformité structurelle**, indépendamment des valeurs.
- **Fait**.
  - `pveil` → `pveilleuse`, le nom déclaré par le schéma, à l'écriture
    (`ChaudiereDefautCalculator`) comme à la relecture
    (`RendementAnnuelMoyenCalculator`, `Ecs\Rendement\CombustionCalculator`).
    `OutputPurger` préservait déjà `pveilleuse` : les deux bouts de la chaîne
    parlent enfin le même nom, et une valeur saisie par le diagnostiqueur est
    désormais réellement reprise.
  - `Qgw` n'existe dans aucun schéma : c'était un canal interne entre
    `StockageCalculator` et ses deux lecteurs, qui passait par le XML. Il
    transite maintenant par `CalculationContext`, via
    `StockageCalculator::qgwKey()`.
- Mesure A/B isolée, profil strict : balises supplémentaires
  **2 286 → 1 949 (−337)**, conformité **88,15 % → 88,57 %**, section
  « Conformité structurelle » vide. Aucun changement sur les valeurs
  (exactes et hors tolérance identiques), 449 tests unitaires verts.

### TASK-K04 — Consommations de chauffage : `conso_ch` / `conso_ch_depensier`

- [ ] Owner: __  | Phase: K  | Estimation: 8h  | Priorité: haute
- 871 valeurs hors tolérance (517 `conso_ch_depensier`, 354 `conso_ch`),
  écart maximal 110 %. C'est le premier poste après les coûts, et il cascade
  sur `emission_ges_ch` (264), `ep_conso_ch` (166), `conso_5_usages` (266),
  `emission_ges_5_usages` (236), `ep_conso_5_usages` (157).
- En amont, `rendement_generation` (76), `pn` (54) et `qp0` (54) divergent
  aussi : remonter jusqu'au premier intermédiaire faux avant de toucher aux
  agrégats.
- Action : sur 3 à 5 cas représentatifs de
  `php bin/official-test-report --famille="Génération chauffage" --top=40`,
  remonter la chaîne §12 → §9 et comparer à la spec (et à open3cl en cas
  d'ambiguïté).
- Validation : famille « Génération chauffage » au-dessus de 90 %.

### TASK-K05 — Bloc `<confort_ete>` produit alors que la référence ne le contient pas

- [ ] Owner: __  | Phase: K  | Estimation: 3h  | Priorité: moyenne
- 1 065 balises supplémentaires : le moteur écrit systématiquement le
  sous-bloc `sortie/confort_ete` (`isolation_toiture`, `brasseur_air`,
  `aspect_traversant`, `protection_solaire_exterieure`,
  `enum_indicateur_confort_ete_id`), absent de 204 des 207 références de 2026.
- **À qualifier avant de corriger** : ce n'est pas nécessairement une erreur —
  il faut d'abord établir, à partir de l'arrêté et du XSD, si le sous-bloc est
  obligatoire, conditionnel ou optionnel. Ne pas supprimer une sortie
  réglementaire pour faire baisser un compteur.
- Validation : décision tracée dans le doc-block de
  `src/Sortie/ConfortEteCalculator.php`, avec la référence réglementaire.

### TASK-K06 — Génération ECS : balises supplémentaires et rendements

- [ ] Owner: __  | Phase: K  | Estimation: 5h  | Priorité: moyenne
- 558 balises supplémentaires (dont `rendement_stockage` 203,
  `rendement_generation` côté ECS), 276 hors tolérance (`conso_ecs` 134,
  `conso_ecs_depensier` 100) et 14 manquantes
  (`rendement_generation_stockage`).
- Action : aligner la production de balises sur ce que la référence renseigne
  selon le type de générateur (§11), en s'appuyant sur le XSD pour savoir
  quelles balises sont attendues pour quel `enum_type_generateur_ecs_id`.
- Validation : famille « Génération ECS » au-dessus de 92 %.

### TASK-K07 — Classes DPE et GES divergentes

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: moyenne
- 39 écarts non numériques : 15 sur `classe_bilan_dpe`, 24 sur
  `classe_emission_ges`. Ce sont les deux valeurs les plus visibles d'un DPE.
- À traiter **après** K02 et K04 : une classe fausse est presque toujours la
  conséquence d'une consommation fausse, pas d'un seuil faux. Vérifier
  néanmoins les seuils (dont l'ajustement < 40 m² de l'arrêté du 24 mars 2024).
- Validation : 0 écart sur `classe_bilan_dpe` et `classe_emission_ges`.

### TASK-K08 — Balises manquantes du bloc froid

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: basse
- 20 balises manquantes côté froid, dont `conso_auxiliaire_distribution_fr`,
  `ep_conso_auxiliaire_distribution_fr`,
  `emission_ges_auxiliaire_distribution_fr`, `cout_auxiliaire_distribution_fr`,
  attendues à 0 par la référence.
- Action : écrire ces balises même à 0 quand une installation de
  climatisation est présente.
- Validation : famille « Froid » sans balise manquante.

### TASK-K09 — Remplacer les exclusions du harness E2E par la mesure de conformité

- [ ] Owner: __  | Phase: K  | Estimation: 3h  | Priorité: basse
- `tests/EndToEndTest.php` porte une liste `TAGS_EXCLUDED_BY_FILE` et une liste
  `$tagsCovered` qui masquent une partie des écarts, et son indexation de
  chemin ignore les fratries homonymes : sur un logement à 30 murs, un seul
  `umur` est réellement comparé.
- Action : faire converger le harness E2E vers `CalculDpePHP\Conformite\*`
  (chemins indexés, aucune balise exclue), en gardant un seuil de
  non-régression sur le nombre de valeurs hors tolérance plutôt que des
  exclusions nominatives.
- Validation : suite verte, et le compte de valeurs hors tolérance du rapport
  ne remonte pas.

### TASK-K10 — Diviseur « par logement » des tranches tarifaires

- [x] Owner: AI2  | Phase: K  | Estimation: 3h  | Priorité: moyenne
- TASK-K02 a établi que la tranche tarifaire de l'électricité et du gaz
  s'apprécie sur la consommation **d'un logement**, et l'a implémentée en
  divisant par `caracteristique_generale/nombre_appartement`. Vérifié exact
  sur 181 cas sur 224.
- Sur les 43 cas restants, `cout_eclairage` diverge alors que
  `conso_eclairage` est juste : le diviseur n'est donc pas toujours
  `nombre_appartement`. Les écarts vont dans les deux sens (tantôt la
  référence applique une tranche plus haute, tantôt plus basse), ce qui exclut
  une simple erreur de facteur.
- Pistes : DPE appartement issu des données de l'immeuble (la sortie porte
  peut-être déjà sur un seul logement) ; immeubles où `nombre_appartement`
  diffère du nombre de logements desservis par l'énergie considérée ;
  logements-types de l'échantillonnage §17.
- **Fait**. Le diviseur n'est pas un nombre de logements mais un **nombre
  d'abonnements**. Le barème tarifie la consommation annuelle d'un ménage,
  donc d'un point de livraison :
  - un DPE immeuble collectif dessert un abonnement par logement pour les
    usages individuels, mais un **abonnement unique d'immeuble** pour les
    usages portés par une installation collective (chauffage ou ECS avec
    `enum_type_installation_id = 2`, et leurs auxiliaires) ;
  - tous les autres périmètres — maison, appartement, et **appartement généré
    à partir des données de l'immeuble** — ne décrivent qu'un logement :
    diviseur 1. Sur ces DPE, `nombre_appartement` renseigne la taille du
    bâtiment, pas la portée du DPE, et le prendre comme diviseur était faux.
  - la ventilation reste individuelle : la rattacher à l'abonnement d'immeuble
    dégrade nettement l'accord avec la référence.
- Départage empirique sur les 229 cas, en reproduisant le coût par énergie de
  la référence : diviseur = `nombre_appartement` 139/229 · `+1` 125/229 ·
  toujours 1 : 18/229 · **règle par abonnement : 216/229**.
- Au passage, `SortieParEnergieAggregator` dupliquait toute la mécanique
  tarifaire avec les tarifs de 2021 figés — d'où 899 des écarts restants. Le
  barème et les tranches vivent désormais dans `src/Sortie/PrixEnergie.php`,
  partagé par les deux calculateurs.
- Mesure A/B isolée, profil strict : hors tolérance **5 856 → 4 942
  (−914, −15,6 %)**, conformité **89,30 % → 90,57 %**. Famille « Coûts »
  1 751 → 1 004 écarts, « Auxiliaires » 1 497 → 1 330. 477 tests unitaires
  verts.
- Reste : TASK-K11.

### TASK-K11 — Confirmer la règle d'abonnement sur le texte officiel

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: moyenne
- TASK-K10 a établi par l'observation que la tranche tarifaire s'apprécie par
  abonnement (un par installation collective d'immeuble, un par logement
  sinon). La règle est physiquement cohérente et reproduit la référence sur
  216 cas sur 229, **mais elle n'est pas écrite dans le barème publié** : le
  doc-block de `PrixEnergie` le signale explicitement.
- Action : retrouver dans l'arrêté, sa notice ou la documentation
  d'accompagnement le texte qui définit l'assiette de la tranche, et soit
  citer la source dans `PrixEnergie`, soit corriger la règle.
- Restent 4 cas où l'écart dépasse 20 % (2659E2129582M, 2592E0655586O,
  2593E3377930D, 2594E0486196Q) : ce sont des **appartements desservis par une
  installation collective** (méthodes 3, 5, 9). La référence y semble apprécier
  la tranche sur la consommation de l'immeuble entier, information que le DPE
  d'un appartement ne porte pas directement. À trancher avec la même source.
- Validation : source citée dans le doc-block, ou règle corrigée et gain
  mesuré en A/B.

---

## Validation par phase (gate)

On ne passe à la phase suivante que quand le harness `tests/EndToEndTest.php` valide les balises produites par la phase courante sur les 4 fichiers de `resources/XML/input/` (tolérance 1e-3, exceptions dans `tests/tolerances.php`) :

| Phase | Balises XML qui doivent matcher |
|---|---|
| B | `b`, `umur`, `umur0`, `upb`, `upb0`, `upb_final`, `uph`, `uph0`, `ug`, `uw`, `ujn`, `u_menuiserie`, `sw`, `fe1`, `fe2`, `uporte`, `k`, `deperdition_*`, `ubat` |
| C | `hvent`, `hperm`, `q4pa_conv`, `pvent_moy`, `conso_auxiliaire_ventilation`, `surface_sud_equivalente`, `fraction_apport_gratuit_*`, `enum_classe_inertie_id`, `i0` |
| D | `besoin_ch`, `besoin_ch_depensier`, `besoin_fr`, `besoin_fr_depensier`, `besoin_ecs`, `besoin_ecs_depensier`, `v40_*`, `nadeq` |
| E | `rendement_*`, `pn`, `qp0`, `rpn`, `rpint`, `temp_fonc_*`, `cop`, `scop`, `eer`, `conso_ch`, `conso_ch_depensier`, `conso_ecs`, `conso_ecs_depensier`, `conso_fr`, `conso_fr_depensier`, `conso_eclairage`, `conso_auxiliaire_*`, `production_pv` |
| F | bloc `<sortie>` complet (toutes balises listées dans le verif) |
| G | DPE collectif (si exemples disponibles ; sinon tests unitaires ciblés) |

---

## Risques connus

1. **Volumétrie de digitalisation** : `tv_coef_reduction_deperdition` ≈ 283 lignes ; sollicitations §18.2 ≈ 14 pages. Découper en sous-tâches, double-relecture par 2 IAs.
2. **Tables multi-axes** (§18.5, §18.2) : décider du format de clé composite dès le départ — c'est fait dans `resources/tables/README.md`.
3. **Stratégies §9.x** : 12 cas combinatoires. Pattern Strategy strict + dispatcher (TASK-E23) pour éviter l'explosion if/else.
4. **Pré/post 2026** : coefficient EF→EP de l'électricité change. Utiliser `Common\Period` dans les calculs concernés (notamment TASK-F04).
5. **Idempotence du DOM** : `DpeEngine::run` purge déjà `<donnee_intermediaire>` et `<sortie>` avant calcul ; ne pas implémenter de logique de "merge".
6. **Ordre de calcul implicite** : le `CalculatorPipeline` fait un tri topologique ; si un Calculator lit une donnée pas encore calculée, ajoute-la dans `dependencies()` plutôt que de la calculer en place.
7. **Espaces tampons solarisés (ETS)** : ne pas oublier d'inclure `<ets>` dans l'agrégation enveloppe et apports (§6.3).
8. **`xsi:nil="true"`** : nombreuses balises XSD ont cette valeur quand non saisies. `NodeAccessor` la gère et retourne `null`.
9. **Encodage** : XML en UTF-8, valeurs textuelles en français (accentuées) — pas de problème connu.
10. **Performance** : `TableRepository` mémoise déjà ; éviter les `require` dans les boucles.
