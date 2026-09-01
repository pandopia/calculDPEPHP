# Jeux de tests d'évaluation — provenance et statut

Ce répertoire accueille les **jeux de cas de test** utilisés pour mesurer la
conformité du moteur, en les tenant séparés des exemples de travail du dépôt
(`resources/XML/input` et `resources/XML/verif`).

## 1. Statut des jeux officiels CSTB — recherche effectuée le 1er septembre 2026

**Les jeux d'autotests et de cas tests de la procédure officielle d'évaluation
ne sont pas téléchargeables publiquement.** Résultat de la recherche :

| Source consultée | URL | Constat |
|---|---|---|
| Portail RT-RE-bâtiment, page « Évaluation des logiciels » | <https://rt-re-batiment.developpement-durable.gouv.fr/evaluation-des-logiciels-a647.html> | Publie le règlement de la procédure et la liste des logiciels validés. **Aucun lien vers les jeux de tests.** |
| Règlement de la procédure d'évaluation (DHUP/ADEME/CSTB, v. 13/12/2022) | <https://rt-re-batiment.developpement-durable.gouv.fr/IMG/pdf/reglement_evaluation_logiciel_dpe_2021_-_audit_energetique-13122022_v2.pdf> | §1.1 : « L'ensemble des jeux d'autotests, de cas tests […] sont téléchargeables et consultables sur un site internet défini par le ministère ». §5 renvoie à la plateforme d'échanges des éditeurs. |
| Plateforme d'échanges éditeurs | <https://app.rt-batiment.fr/evaluation_logiciel/> (redirige vers `/dpe2021/`) | **Authentification requise** ; réservée aux éditeurs engagés dans la procédure. |
| Recherche de miroirs publics (GitHub, dépôts open source 3CL) | — | Aucun jeu identifiable comme provenant des cas tests officiels. Les projets open source (Open3CL, Py3CL…) valident sur l'open data ADEME, pas sur les cas CSTB. |

Le règlement public ne publie par ailleurs **aucun seuil de tolérance chiffré** :
les critères de conformité accompagnent les cas tests remis aux éditeurs.

**Conséquence** : tant que le dépôt n'a pas accès à la plateforme éditeur, la
conformité ne peut pas être mesurée contre les cas officiels. Elle est mesurée
contre le meilleur substitut public disponible, décrit ci-dessous.

## 2. Substitut public retenu — observatoire DPE de l'ADEME

| Champ | Valeur |
|---|---|
| Organisme | ADEME |
| Jeu de données | *DPE Logements existants (depuis juillet 2021)* |
| Portail | <https://data.ademe.fr/datasets/dpe03existant> |
| API de sélection | `https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines` |
| API de téléchargement XML | `https://api-externe.ademe.fr/api/v1/pub/dpe/{numero}/xml` |
| Licence | Licence Ouverte / Open Licence v2.0 |
| Périmètre | DPE opposables réellement déposés, produits par des logiciels **évalués par le CSTB** |
| Volumétrie | ≈ 15,4 millions de DPE au 1er septembre 2026 |

Ces fichiers ne sont **pas** les cas tests du CSTB : ce sont des DPE réels, dont
les `<donnee_intermediaire>` et `<sortie>` ont été calculés par un moteur
validé. Ils constituent une référence indépendante, traçable et publique, mais
avec deux limites à garder en tête :

- ils reflètent les choix d'implémentation du logiciel émetteur, y compris ses
  écarts éventuels à la méthode (`version_moteur_calcul` est reporté dans le
  rapport pour isoler ce biais) ;
- ils ne couvrent pas volontairement les cas limites que le CSTB construit
  expressément pour l'évaluation.

## 3. Disposition des fichiers

```
resources/XML/official/
    <nom-du-jeu>/
        metadata/
            manifest.json      ← suivi en git : provenance de chaque cas
            provenance.md      ← suivi en git : organisme, date, licence
        input/                 ← non suivi : XML d'entrée (sorties purgées)
        expected/              ← non suivi : XML ADEME d'origine, inchangé
```

Les XML eux-mêmes ne sont pas versionnés (voir `.gitignore` racine : le dépôt
ne suit pas `resources/XML/`). Seuls les **manifestes** le sont, ce qui rend le
jeu reproductible à l'identique sans dupliquer des mégaoctets de XML.

`expected/` contient le fichier ADEME **tel que téléchargé**, sans aucune
retouche. `input/` en est la version normalisée : `<donnee_intermediaire>` et
`<sortie>` purgés par `CalculDpePHP\Xml\OutputPurger`, tout le reste identique.

## 4. Reconstituer un jeu

```bash
php bin/fetch-official-corpus --manifest=resources/XML/official/<nom-du-jeu>/metadata/manifest.json
```

Pour construire un nouveau jeu stratifié depuis l'open data ADEME :

```bash
php bin/fetch-official-corpus --build=<nom-du-jeu> --per-stratum=10
```

Le téléchargement passe par l'API publique de l'ADEME et nécessite un accès
réseau sortant.

## 5. Ne pas mélanger les sources

Un jeu officiel CSTB, s'il devient accessible, doit être déposé dans son
**propre** sous-répertoire, avec son `provenance.md`. Les exemples issus d'un
logiciel commercial n'ont pas leur place ici : ils restent dans
`resources/XML/input` / `resources/XML/verif`, exposés par le runner sous le nom
de corpus `ademe-observatoire-local`.
