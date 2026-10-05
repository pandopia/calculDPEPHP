# Prompt — endpoints API « DPE des logements générés depuis le DPE immeuble »

À donner tel quel à l'agent qui travaille sur le dépôt `app.pandopia.com`.
Le contrat ci-dessous est celui qu'appelle déjà l'éditeur DPE
(`editeur/src/app/core/immeuble/logements.ts` et
`ServiceLogementsPandopia` dans `editeur/src/app/core/calcul/moteur-calcul.ts`
du dépôt calculDPEPHP) : ne pas en changer les noms de champs.

---

## Contexte

L'API publique `calculdpe` expose déjà deux routes dans
`app/src/modules/api/controllers/CalculdpeController.php` (déclarées dans
`app/src/Modeles/Api/Mapping.php`, testées dans `tests/Unit/CalculdpeApiTest.php`) :

- `POST /api/calculdpe/xmlademe` : XML ADEME → XML ADEME calculé ;
- `POST /api/calculdpe/pdfademe` : XML ADEME → PDF du diagnostic.

Ajoute deux routes pour le scénario officiel 3CL-2021 §17.2.2 : à partir d'un
DPE **immeuble** (méthode d'application 6, 7, 8 ou 9), générer le DPE de
chaque appartement, en reliant chaque logement aux parois sur lesquelles il
donne. Le calcul existe déjà dans la librairie `pandopia/calculdpephp` :
`CalculDpePHP\CalculDpePHP::calculateBuilding(BuildingInput): BuildingResult`
(voir `src/Collectif/BuildingCalculation.php`, `src/Dto/BuildingInput.php`,
`src/Dto/ApartmentInput.php`). Ne réimplémente aucune règle de calcul : ton
travail est uniquement l'adaptateur HTTP JSON ↔ DTO.

`lib/Myzen/DpeComparison/Apartments.php` construit déjà un `BuildingInput` depuis
le fichier appartements de l'uploader : inspire-t'en, mais l'entrée ici est du
JSON, et **aucune hypothèse d'import historique** n'est ajoutée (en particulier
pas de coefficient IFC forcé à 0 : un coefficient absent reste `null`, le moteur
applique alors 0,7 comme le prévoit la 3CL).

## Route 1 — `POST /api/calculdpe/logements`

Action `logementsAction()`. Requête `Content-Type: application/json` :

```json
{
  "xml": "<dpe>…</dpe>",
  "repartition": {
    "chauffage": 1,
    "ecs": 1,
    "coefficientIfc": 0.7,
    "approximerLiaisons": false
  },
  "logements": [
    {
      "reference": "Lot 1",
      "description": "T2 1er étage gauche",
      "surface": 45.2,
      "position": 1,
      "typologie": 2,
      "visite": true,
      "liaisons": {
        "murs": ["ref_mur_1", "ref_mur_3"],
        "plancher": ["ref_pb_1"],
        "plafond": [],
        "fenetre": ["ref_baie_2"],
        "porte": ["ref_porte_1"]
      }
    }
  ],
  "logement": "Lot 1",
  "inclureXml": true
}
```

Correspondance avec les DTO :

| JSON | DTO |
|---|---|
| `xml` | `BuildingInput::$xml` (XML ADEME du DPE immeuble, racine `<dpe>`) |
| `repartition.chauffage` | `BuildingInput::$heatingDistribution` : 1 = chauffage collectif avec individualisation des frais (IFC), 2 = collectif sans IFC (prorata des surfaces), 3 = chauffage individuel |
| `repartition.ecs` | `BuildingInput::$hotWaterDistribution` : 0 = ECS individuelle, 1 = collective |
| `repartition.coefficientIfc` | `BuildingInput::$individualizationCoefficient` (`null` autorisé, sinon 0 ≤ x ≤ 1) |
| `repartition.approximerLiaisons` | `BuildingInput::$approximateMissingAssociations` (défaut `false`) |
| `logements[]` | `ApartmentInput` : `reference`, `surface`, `associations` = `liaisons` (clés `murs`, `plancher`, `plafond`, `fenetre`, `porte`, listes de `donnee_entree/reference` des parois), `position` (1 RDC, 2 intermédiaire, 3 dernier étage, ou `null`), `typology` = `typologie` (1 à 7 ou `null`), `visited` = `visite` |
| `logement` (facultatif) | `BuildingInput::$apartmentReference` : ne calculer que ce logement (l'immeuble et les clés de répartition restent calculés sur tous) |
| `inclureXml` (facultatif, défaut `true`) | si `false`, ne pas renvoyer les XML (réponse légère, résultats seuls) |

Validation → **400** `{"error": "…"}` en français, comme les routes existantes :
JSON illisible ; `xml` absent ou dont la racine n'est pas `<dpe>`
(`Myzen_DpeComparison_Xml::load`) ; `chauffage` ∉ {1,2,3} ; `ecs` ∉ {0,1} ;
`coefficientIfc` hors [0,1] ; `logements` vide ; `reference` vide ou en double ;
`surface` non numérique ou ≤ 0 ; `liaisons` qui n'est pas un objet de listes de
chaînes (clés inconnues ignorées) ; `logement` fourni mais absent de la liste.
Méthode autre que POST → **405** avec `Allow: POST`.

Les erreurs **de calcul** ne sont pas des erreurs HTTP : `calculateBuilding`
renvoie une erreur par logement (`CalculationDocument::$error`) et pour
l'immeuble ; elles sont restituées dans la réponse **200** :

```json
{
  "batiment": {
    "erreur": null,
    "xml": "<dpe>… immeuble calculé …</dpe>",
    "resultats": { … }
  },
  "logements": [
    { "reference": "Lot 1", "erreur": null, "xml": "<dpe>…</dpe>", "resultats": { … } },
    { "reference": "Lot 2", "erreur": "Collection incomplète ou surfaces logements/immeuble incohérentes.", "xml": null, "resultats": null }
  ],
  "hypotheses": ["Paroi mur_4 sans liaison : répartition approximative entre …"]
}
```

- `logements` suit l'ordre de la requête (filtré sur `logement` s'il est fourni).
- `hypotheses` = `BuildingResult::$assumptions`.
- `resultats` (immeuble et chaque logement), lus dans `<sortie>` du XML
  calculé, `null` en cas d'erreur — **clés exactes**, valeurs numériques en
  nombre JSON (classes en chaîne) :

  | clé | source XML |
  |---|---|
  | `surface` | `caracteristique_generale/surface_habitable_logement`, sinon `surface_habitable_immeuble` |
  | `classe_bilan_dpe` | `sortie/ep_conso/classe_bilan_dpe` |
  | `classe_emission_ges` | `sortie/emission_ges/classe_emission_ges` |
  | `ep_conso_5_usages_m2`, `ep_conso_5_usages` | `sortie/ep_conso/…` |
  | `emission_ges_5_usages_m2`, `emission_ges_5_usages` | `sortie/emission_ges/…` |
  | `conso_5_usages`, `conso_5_usages_m2`, `conso_ch`, `conso_ecs`, `conso_fr`, `conso_eclairage`, `conso_totale_auxiliaire` | `sortie/ef_conso/…` |
  | `cout_5_usages` | `sortie/cout/cout_5_usages` |
  | `par_energie` | liste `{enum_type_energie_id, conso_5_usages, emission_ges_5_usages, cout_5_usages}` de `sortie/sortie_par_energie_collection` |

  Mets cette extraction dans une petite classe dédiée (ex.
  `Myzen_DpeComparison_LogementResultats::fromXml(string): array`), testée seule.

## Route 2 — `POST /api/calculdpe/pdflogement`

Action `pdflogementAction()`. Même corps JSON que la route 1, **`logement`
obligatoire** (400 sinon). Calcule avec `apartmentReference = logement`, puis
`CalculDpePHP::genererPdf($xmlDuLogement)` et renvoie `application/pdf`
avec `Content-Disposition: attachment; filename="dpe-logement-<reference assainie>.pdf"`.

- Calcul du logement en erreur → **422** `{"error": "<message du moteur>"}`.
- `genererPdf` qui échoue (modèle antérieur au 01/01/2023, etc.) → **422**
  avec le message, comme `pdfademe`.
- Le XML du logement n'a pas de numéro ADEME : le PDF porte le filigrane
  « DOCUMENT NON OFFICIEL ». C'est voulu, ne pas le contourner.

## Contraintes

- Réutilise la structure de `calculate(bool $pdf)` : `Myzen::noRender`,
  `Cache-Control: no-store`, mêmes formats d'erreur, `Myzen_Exception` → 400,
  `Throwable` → 422. Factorise la lecture/validation du JSON dans une méthode
  privée partagée par les deux actions.
- Le calcul d'un immeuble de 50 logements enchaîne une centaine de calculs
  moteur : relève `set_time_limit` (ex. 300 s) et vérifie la mémoire pour ces
  deux actions seulement ; borne la taille du corps (ex. 20 Mo) et le nombre de
  logements (ex. 500) → 400 au-delà.
- **CORS** : l'éditeur tourne sur `https://pandopia.github.io` et envoie du
  `application/json` (requête avec pré-vol `OPTIONS`). Applique exactement le
  même traitement CORS que celui qui sert déjà `xmlademe` / `pdfademe` ;
  vérifie que le pré-vol des deux nouvelles routes répond.
- Hôte : l'éditeur appelle `https://app.pandopia.com/api/calculdpe/logements`
  et `/pdflogement`. Si les routes doivent aussi répondre sur
  `api.pandopia.com`, garde le même chemin.
- Déclare les deux routes dans `Mapping.php` (`additionalPaths` de
  `calculdpe`, tags `public`) avec le schéma JSON de la requête et de la
  réponse ci-dessus et les codes 200/400/405/422.
- Aucune persistance, aucun appel externe, aucune donnée journalisée en clair
  (le XML contient adresses et identités).

## Tests (Pest, `tests/Unit/CalculdpeApiTest.php`)

Avec un DPE immeuble réel en fixture (par exemple le
`tests/Fixtures/comparatif-collectif.xml` de calculDPEPHP : méthode 9,
48 appartements, 3 780,58 m²) :

1. `logements`, répartition 2 (sans IFC), 48 logements de surfaces égales dont
   la somme vaut la surface de l'immeuble : 200, 48 résultats sans erreur,
   `conso_ch` de chaque logement = `conso_ch` de l'immeuble / 48 (à 1e-5 près),
   classes A→G présentes.
2. Répartition 1 avec liaisons complètes (ou `approximerLiaisons: true`) : 200,
   consommations de chauffage différentes selon les liaisons, `hypotheses`
   non vide en mode approximation.
3. `logement` fourni : un seul élément dans `logements`.
4. `inclureXml: false` : `xml` à `null`, `resultats` présents.
5. Somme des surfaces incohérente : 200 avec `erreur` sur chaque logement.
6. 400 : JSON illisible, `xml` manquant, `chauffage` = 5, référence en double,
   `logement` inconnu ; 405 sur GET.
7. `pdflogement` : 200, `application/pdf`, corps commençant par `%PDF-` ;
   400 sans `logement` ; 422 si le logement est en erreur.

Lance la suite existante et les nouveaux tests ; montre les sorties.
