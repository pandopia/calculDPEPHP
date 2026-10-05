# Éditeur de DPE (XML ADEME)

Application web Angular, **100 % navigateur**, pour lire, comprendre, compléter
et modifier un diagnostic de performance énergétique à partir de son XML ADEME,
puis réexporter le XML. Aucun fichier n'est envoyé à un serveur : l'import,
l'édition, la validation XSD et l'export se font localement ; les brouillons
sont conservés dans le navigateur (IndexedDB).

## Lancer

```bash
cd editeur
npm install
npm start            # http://localhost:4200
npm run build        # dist/editeur/browser : site statique à servir tel quel
npm test             # tests (Vitest + jsdom)
npm run test:corpus  # + aller-retour sur le corpus local resources/XML (s'il est présent)
```

Prérequis : Node 20.19 ou plus récent (Angular 20). Le build produit un site
statique (≈ 350 ko de JavaScript, plus les XSD et le validateur WebAssembly
chargés à la demande) ; aucun serveur applicatif n'est nécessaire.

## Analyse du format

| Constat | Conséquence dans l'application |
|---|---|
| Le XSD du dépôt (`resources/ademe_DPE.xsd`, V7.1.0, 2022) est plus ancien que les fichiers réels (modèle 2.6, nouvelles balises). | Les XSD officiels de l'observatoire ADEME (gitlab.com/observatoire-dpe, `modele_donnee`) sont embarqués dans `public/schemas/`, un par version du modèle. |
| La version se lit dans `administratif/enum_version_id`. Les noms de fichiers XSD ne suivent pas cette version. | Correspondance établie sur la liste `xs:enumeration` et l'en-tête de chaque XSD : 1 et 1.1 → DPEv1 (V6.4.0) ; 2 à 2.2 → DPEv2 (V7.1.0) ; 2.3 → DPEv2.2 (V8.0.4) ; 2.4 → DPEv2.3 (V8.2.0) ; 2.5 → DPEv2.4 (V9.1.1) ; 2.6 → DPEv2.6 (V9.2.3). Voir `schema-registry.ts`. |
| La racine `dpe` porte un `xs:choice` : `logement` (existant 3CL), `logement_neuf`, `tertiaire`. Une racine `audit` relève d'un autre schéma. | Détection de la famille ; seul le DPE logement existant a des fiches métier. |
| L'export public de l'observatoire ajoute `numero_dpe` et `statut` avant `administratif` ; ces balises ne figurent pas dans le XSD de dépôt. | Conservées, affichées en lecture seule, **exclues de la validation XSD** (sinon libxml s'arrête dès la première balise). |
| Les fichiers anonymisés de l'observatoire ont des champs vidés (diagnostiqueur, numéro fiscal…). | Ils s'ouvrent ; les écarts sont signalés, jamais « réparés ». |
| Objets et identifiants : chaque objet porte `donnee_entree/reference`. Liens documentés par le XSD : `reference_paroi` (baie/porte → mur, plancher haut ou bas), `reference_lnc` (paroi → espace tampon ou local non chauffé), `reference_1`/`reference_2` (pont thermique → éléments de l'enveloppe), `reference_generateur_mixte` (générateur chauffage ↔ ECS). | Graphe de relations dans les deux sens (`relations.ts`). |
| Mesuré sur 517 DPE réels : `reference_paroi` résolu à 99 % ; `reference_1/2` et `reference_lnc` souvent non résolus (parois ou locaux non décrits comme objets) ; 34 `reference` en double ; `reference_generateur_mixte` écrit tantôt à l'identique des deux côtés (convention XSD), tantôt avec la référence de l'autre partie. | Non résolu = avertissement pour une paroi support, information pour un local non chauffé ou un pont thermique. Les deux conventions mixtes sont reconnues ; l'éditeur écrit celle du XSD. |
| Lien générateur ↔ émetteur : pas d'identifiant, un rôle (`enum_lien_generateur_emetteur_id`) partagé dans l'installation. | Restitué comme « lien par rôle », sans arborescence artificielle. |
| Énumérations : codes bornés par `minInclusive/maxInclusive` ou `xs:enumeration`, libellés en JSON dans `xs:appinfo`. | Listes déroulantes à libellés ; un code inconnu est conservé et signalé. |
| Tables `tv_*_id` : identifiants de ligne de tables forfaitaires, sans libellés dans le XSD. | Saisie numérique, marquée « identifiant de ligne de table » (voir limites). |
| Saisies et résultats : `donnee_entree` (saisie), `donnee_intermediaire` (valeurs intermédiaires, parfois saisies par le logiciel : `pn`, `qp0`…), `sortie` (résultats). | Résultats en lecture seule, étiquetés « résultat du fichier source » ; intermédiaires modifiables en mode avancé seulement. |

## Architecture

```
src/app/core/                 logique pure, testée hors navigateur
  schema/        xsd-compiler, schema-registry   XSD → registre (libellés, types, facettes, ordre, cardinalités)
  xml/           safe-xml, serializer, uid, semantic-compare
                 lecture défensive, écriture déterministe, identifiants internes, comparaison sémantique
  format/        format-detector                  famille, version, schéma (adaptateur par version)
  metier/        catalog, field-meta, display-rules, relations, model
                 objets, onglets, libellés et unités, règles conditionnelles sourcées, graphe, vues
  edition/       editor, doc-ops, value-codec, change-set
                 opérations (champ, ajout, duplication, suppression), saisie ↔ XML, récapitulatif
  validation/    validator, xsd-validation, issues contrôles en direct, XSD (libxml2 WebAssembly)
  state/         dossier, skeleton               document de travail, historique, dossier vierge
  calcul/        moteur-calcul                    point d'extension d'un moteur (aucun connecté)
src/app/services/  dossier.service (état, autosauvegarde), draft-store (IndexedDB), nav.service (URL)
src/app/ui/        accueil, dossier-shell, synthese, tab-view, fiche, field, dialogues, controles
```

**Sans perte de données.** Le fichier importé est gardé tel quel (téléchargeable).
Le document de travail est le DOM du fichier ; chaque modification touche les
seuls nœuds concernés. Les éléments complexes portent un attribut
`ed:uid` dans un espace de noms propre à l'éditeur, retiré à l'export : il donne
une identité stable aux objets (diff, navigation, annulation) sans toucher aux
balises. Le sérialiseur conserve balises inconnues, attributs, espaces de noms,
commentaires, CDATA, `xsi:nil` et textes au caractère près ; il réindente
seulement le contenu « éléments seuls ». Une balise ajoutée est insérée à la
place prévue par l'ordre du schéma. Aucune conversion XML ↔ JSON.

**Balises vides de remplissage.** Les logiciels de diagnostic écrivent
souvent des balises vides (`<production_elec_enr/>`, `<numero_fiscal_local/>`,
collections vides…). Elles sont ignorées partout (ni anomalie, ni « à
compléter » ; un champ vide s'affiche « non renseigné ») et retirées sans
avertissement du XML de travail exporté, ainsi que du document soumis à la
validation XSD. Sont retirées : les balises vides facultatives, et les balises
obligatoires dont la valeur vide est de toute façon invalide (nombre, code,
motif). Restent : `xsi:nil`, balises portant un attribut, balises hors schéma,
balises obligatoires dont le vide est valide (ex. une collection pouvant être
vide). Voir `core/xml/prune.ts`. Le fichier d'origine reste téléchargeable
intact.

**Sécurité.** `<!DOCTYPE`/`<!ENTITY` refusés (pas d'entités externes), aucune
ressource réseau, 20 Mo et 64 niveaux maximum.

## Formats supportés

| Fichier | Prise en charge |
|---|---|
| DPE logement existant 3CL-2021, modèle 2 à 2.6 | Complète : fiches métier, relations, ajout / duplication / suppression, règles, validation XSD de la version. |
| DPE logement existant, modèle 1 / 1.1 (juillet–décembre 2021) | Complète, validée contre le XSD V1 (déclaré obsolète par l'ADEME), avec avertissement. |
| Version de modèle absente ou inconnue | Vue générique sans validation XSD ; aucune migration. |
| DPE logement neuf (RT2012/RE2020), DPE tertiaire | Vue générique (structure du XSD) et édition de champs, sans fiches métier ni ajout d'objets. |
| Audit énergétique, autre racine, XML mal formé | Refusés, avec le motif. |
| Création d'un dossier | DPE logement existant, modèle 2.6 (seule version en vigueur). |

## Règles documentées

- **Obligations** : `minOccurs` du XSD de la version (complétude), facettes
  (bornes, motifs, longueurs, énumérations) contrôlées en direct.
- **Affichage conditionnel** (`display-rules.ts`), chacune sourcée : méthode de
  saisie de U et U0 (épaisseur, résistance, période d'isolation, saisie directe),
  U des portes, k des ponts thermiques, performances des vitrages (Ug, Uw, Sw,
  Ujn « saisis directement » selon les libellés XSD), Q4Pa, facteurs de couverture
  solaire, Aiu/Aue selon l'adjacence (méthode 3CL §3.1 p. 8-12). Un champ non
  pertinent et vide est masqué ; renseigné, il reste visible et signalé, jamais effacé.
- **Règles métier** : surface d'un émetteur ≤ surface de son installation
  (documentation XSD) ; émetteur sans générateur de même rôle ; type de la cible
  d'une référence.
- **Plausibilité** (indicatif, non réglementaire) : hauteur sous plafond,
  cohérence année / période de construction, dates, ouvertures plus grandes que
  leur mur, surfaces nulles.

États de valeur distingués : **absent** (une balise vide est traitée comme absente),
**nul explicite** (`xsi:nil`, si le schéma l'autorise), **zéro** (valeur `0`),
**non applicable** (règle documentée), **valeur par défaut** (codes « valeur
forfaitaire » des méthodes de saisie, choisis explicitement).

Une saisie non représentable (texte dans un nombre) est refusée ; une valeur
hors borne est acceptée et signalée, pour qu'un brouillon reste enregistrable.

## Résultats et calcul

Consommations, déperditions, étiquettes… sont affichés comme **résultats du
fichier source**. Toute modification d'une donnée susceptible d'entrer dans le
calcul (tout sauf descriptions, identités et adresses) marque l'ensemble des
résultats « à recalculer » — faute de graphe de dépendances fiable — avec la
liste des modifications concernées. Aucun recalcul n'est fabriqué :
`core/calcul/moteur-calcul.ts` définit l'interface d'un moteur futur (aucun n'est
connecté). Le XML exporté n'est présenté ni comme recalculé, ni comme validé ou
accepté par l'ADEME ; aucun numéro ADEME n'est généré.

## Matrice de couverture

✓ couvert · G générique (schéma) · L lecture seule · — non applicable

| Partie du format | Import | Affichage | Édition | Création | Export | Validation |
|---|---|---|---|---|---|---|
| Racine `dpe` (attributs `version`, `id`, `hashkey`) | ✓ | ✓ | ✓ | ✓ (à saisir) | ✓ | ✓ XSD |
| En-tête observatoire `numero_dpe`, `statut` | ✓ | L | — | — | ✓ | exclu du XSD (signalé) |
| `administratif`, diagnostiqueur, géolocalisation, adresses, consentement | ✓ | ✓ | ✓ | ✓ structure | ✓ | ✓ |
| Caractéristiques générales, météo, inertie | ✓ | ✓ | ✓ | ✓ structure | ✓ | ✓ + plausibilité |
| Murs, planchers bas, planchers hauts | ✓ | ✓ fiches + relations | ✓ ajout/dup./suppr. | ✓ | ✓ | ✓ + règles U/U0/LNC |
| Baies vitrées (double fenêtre, masques lointains) | ✓ | ✓ | ✓ (rattachement auto depuis la paroi) | ✓ | ✓ | ✓ + règles vitrage |
| Portes | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Espaces tampons et leurs baies | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (aucun cas réel dans le corpus) |
| Ponts thermiques (`reference_1/2`) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ventilation | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Installations de chauffage, générateurs, émetteurs | ✓ | ✓ (multi-installations, mixte, rôles) | ✓ | ✓ | ✓ | ✓ + règles |
| Installations d'ECS, générateurs | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Climatisation | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Production d'électricité, panneaux PV | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Descriptifs simplifiés, ENR, fiches techniques, justificatifs | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Packs de travaux, travaux, gestes d'entretien | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| DPE immeuble, logements visités | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `donnee_intermediaire` | ✓ | L | mode avancé | — (non inventées) | ✓ | ✓ XSD |
| `sortie` (résultats) | ✓ | L, « à recalculer » | — | — (non produites) | ✓ | ✓ XSD |
| Balises hors schéma, extensions | ✓ | signalées | — | — | ✓ conservées | signalées |
| Branches `logement_neuf`, `tertiaire` | ✓ | G | G (champs) | — | ✓ | ✓ XSD |

## Tests

`tests/roundtrip.spec.ts` et `tests/editeur.spec.ts` couvrent en priorité les
risques de perte de données et de rupture de relations : aller-retour sans
modification (fixtures et, avec `npm run test:corpus`, 390 DPE locaux),
modification ciblée (un seul nœud changé, virgule décimale, précision
conservée), section inconnue conservée, réimport, ajout rattaché, duplication
(identifiants neufs, liens remappés, lien mixte), suppression avec dépendances
(aucune référence cassée), objets partagés et multi-installations, états de
valeur, code inconnu, obsolescence des résultats, annuler/rétablir, dossier
vierge repris depuis un brouillon, validation XSD rattachée aux champs, formats
hors périmètre.

Un export sans modification est identique à l'original, aux balises vides de
remplissage près (voir plus haut).

Les fixtures (`tests/fixtures/`) sont des DPE publiés par l'observatoire ADEME
(Licence Ouverte 2.0), anonymisés à la source.

## Limites connues

- **Libellés des tables forfaitaires** (`tv_*_id`) : non publiés dans le XSD
  (ils sont dans `valeur_tables.xlsx` de l'ADEME) ; ces champs se saisissent par
  identifiant de ligne.
- **Règles conditionnelles** : seules celles listées plus haut sont
  implémentées ; les contrôles de cohérence de l'ADEME (moteur de contrôle de
  l'observatoire) ne sont pas reproduits.
- **Pas de moteur de calcul** connecté : les résultats ne sont jamais recalculés.
- **Pas de migration** de version : un fichier s'exporte dans sa version.
- **DPE neuf et tertiaire** : vue générique uniquement.
- **Encodage** : l'export est toujours en UTF-8 (déclaration comprise), même si
  l'original était déclaré dans un autre encodage.
- **Brouillons** : stockés dans le navigateur du poste ; vider les données du
  site les efface. Le téléchargement du brouillon (.json) permet de les déplacer.
- Les objets d'un DPE immeuble volumineux (plus de 1 000) restent fluides,
  mais chaque saisie recalcule l'ensemble des vues.
