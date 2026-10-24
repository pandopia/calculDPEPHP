---
section_id: "8"
title: "Modélisation de l'intermittence"
spec_pages: [55, 56, 57]
xml_outputs: ["i0"]
tables: ["tv_intermittence_id"]
depends_on: ["7"]
status: "digitalized"
---

# §8 — Modélisation de l'intermittence

> Source : resources/spec.pdf, p.55–57, annexe 1 d'octobre 2021.
> Tables et formules contrôlées visuellement sur le PDF le 24/10/2026.
> Transcription mise en forme ; relecture indépendante encore à effectuer.

## Données et formule — p.55

Données d'entrée : type de bâtiment, chauffage divisé ou central, régulation
par pièce ou non, équipement d'intermittence, émetteur, présence d'un comptage
et hauteur moyenne sous plafond.

Le facteur d'intermittence traduit les baisses temporaires de température
(absence, ralenti de nuit, éventuellement de façon inégale dans les pièces).
Il rapporte les besoins réels, pour un comportement moyen des occupants,
aux besoins théoriques :

$$
INT=\frac{I_0}{1+0,1(G-1)},
\qquad
G=\frac{GV}{H_{sp}Sh}
$$

- $GV$ : déperditions annuelles de l'enveloppe en W/K, déterminées en partie 3.
- $Sh$ : surface habitable en m².
- $H_{sp}$ : hauteur moyenne sous plafond en m.

## Lecture des tables

Pour alléger les en-têtes sans supprimer de colonne :

- **C−** : central sans minimum de température.
- **C+** : central avec minimum de température.
- **P+** : par pièce avec minimum de température.
- **P+D** : par pièce avec minimum de température et détection de présence.
- **Régu.** : régulation pièce par pièce, « avec » ou « sans ».
- **—** : tiret ou cellule sans valeur dans le PDF ; ce n'est pas un zéro.

### Maisons individuelles, chauffage individuel, inertie légère ou moyenne — p.55–56

| Chauffage | Régu. | Émetteur | Absent | C− | C+ | P+ | P+D |
|---|---|---|---:|---:|---:|---:|---:|
| Divisé | Avec | Air soufflé | 0,84 | 0,83 | 0,81 | 0,77 | 0,75 |
| Divisé | Avec | Radiateur / Convecteur | 0,84 | 0,83 | 0,81 | 0,77 | 0,75 |
| Divisé | Avec | Plafond chauffant | 0,84 | 0,83 | 0,81 | 0,77 | 0,75 |
| Divisé | Avec | Plancher chauffant | 0,90 | 0,89 | 0,88 | 0,86 | — |
| Central | Avec | Air soufflé | 0,86 | 0,85 | 0,83 | 0,79 | 0,77 |
| Central | Avec | Radiateur | 0,88 | 0,87 | 0,85 | 0,82 | 0,80 |
| Central | Avec | Plafond chauffant | 0,88 | 0,87 | 0,85 | 0,82 | 0,80 |
| Central | Avec | Plancher chauffant | 0,90 | 0,89 | 0,88 | 0,86 | — |
| Central | Sans | Air soufflé | 0,90 | 0,89 | 0,87 | — | — |
| Central | Sans | Radiateur | 0,91 | 0,90 | 0,88 | — | — |
| Central | Sans | Plafond chauffant | 0,91 | 0,90 | 0,88 | — | — |
| Central | Sans | Plancher chauffant | 0,92 | 0,91 | 0,90 | — | — |

### Maisons individuelles, chauffage individuel, inertie lourde ou très lourde — p.55–56

| Chauffage | Régu. | Émetteur | Absent | C− | C+ | P+ | P+D |
|---|---|---|---:|---:|---:|---:|---:|
| Divisé | Avec | Air soufflé | 0,86 | 0,85 | 0,83 | 0,80 | 0,78 |
| Divisé | Avec | Radiateur / Convecteur | 0,86 | 0,85 | 0,83 | 0,80 | 0,78 |
| Divisé | Avec | Plafond chauffant | 0,86 | 0,85 | 0,83 | 0,80 | 0,78 |
| Divisé | Avec | Plancher chauffant | 0,92 | 0,91 | 0,90 | 0,88 | — |
| Central | Avec | Air soufflé | 0,88 | 0,87 | 0,85 | 0,82 | 0,80 |
| Central | Avec | Radiateur | 0,90 | 0,89 | 0,87 | 0,85 | 0,82 |
| Central | Avec | Plafond chauffant | 0,90 | 0,89 | 0,87 | 0,85 | 0,82 |
| Central | Avec | Plancher chauffant | 0,92 | 0,91 | 0,90 | 0,88 | — |
| Central | Sans | Air soufflé | 0,91 | 0,91 | 0,89 | — | — |
| Central | Sans | Radiateur | 0,93 | 0,92 | 0,90 | — | — |
| Central | Sans | Plafond chauffant | 0,93 | 0,92 | 0,90 | — | — |
| Central | Sans | Plancher chauffant | 0,94 | 0,93 | 0,92 | — | — |

Une maison individuelle raccordée à un réseau collectif de fourniture
d'énergie pour le chauffage est traitée comme une maison individuelle avec
chauffage individuel central.

### Immeubles collectifs avec chauffage individuel — p.56

| Chauffage | Régu. | Émetteur | Absent | C− | C+ | P+ | P+D |
|---|---|---|---:|---:|---:|---:|---:|
| Divisé | Avec | Air soufflé | 0,90 | 0,89 | 0,88 | 0,86 | 0,83 |
| Divisé | Avec | Radiateur / Convecteur | 0,90 | 0,89 | 0,88 | 0,86 | 0,83 |
| Divisé | Avec | Plafond chauffant | 0,90 | 0,89 | 0,88 | 0,86 | 0,83 |
| Divisé | Avec | Plancher chauffant | 0,95 | 0,94 | 0,93 | 0,91 | — |
| Central | Avec | Air soufflé | 0,91 | 0,90 | 0,89 | 0,87 | 0,84 |
| Central | Avec | Radiateur | 0,93 | 0,92 | 0,91 | 0,89 | 0,86 |
| Central | Avec | Plafond chauffant | 0,93 | 0,92 | 0,91 | 0,89 | 0,86 |
| Central | Avec | Plancher chauffant | 0,95 | 0,94 | 0,93 | 0,91 | — |
| Central | Sans | Air soufflé | 0,95 | 0,94 | 0,93 | — | — |
| Central | Sans | Radiateur | 0,96 | 0,95 | 0,94 | — | — |
| Central | Sans | Plafond chauffant | 0,96 | 0,95 | 0,94 | — | — |
| Central | Sans | Plancher chauffant | 0,97 | 0,96 | 0,95 | — | — |

### Immeubles collectifs avec chauffage collectif — p.56

Le chauffage est central. Les trois colonnes d'équipement sont : absent,
central collectif (**CC**) et central collectif avec détection de présence
(**CC+D**).

| Régu. | Émetteur | Sans comptage : absent | Sans comptage : CC | Sans comptage : CC+D | Avec comptage : absent | Avec comptage : CC | Avec comptage : CC+D |
|---|---|---:|---:|---:|---:|---:|---:|
| Avec | Air soufflé | 1,01 | 0,99 | 0,96 | 0,93 | 0,91 | 0,88 |
| Avec | Radiateur | 1,03 | 1,01 | 0,98 | 0,95 | 0,93 | 0,90 |
| Avec | Plafond chauffant | 1,03 | 1,01 | 0,98 | 0,95 | 0,93 | 0,90 |
| Avec | Plancher chauffant | 1,05 | 1,03 | — | 0,97 | 0,95 | — |
| Sans | Air soufflé | 1,03 | 1,01 | — | 0,95 | 0,93 | — |
| Sans | Radiateur | 1,05 | 1,03 | — | 0,97 | 0,95 | — |
| Sans | Plafond chauffant | 1,05 | 1,03 | — | 0,97 | 0,95 | — |
| Sans | Plancher chauffant | 1,07 | 1,05 | — | 0,99 | 0,97 | — |

## Règles complémentaires — p.56–57

En immeuble collectif, un chauffage mixte facturé pour partie collectivement
et pour partie individuellement est traité, pour l'intermittence, comme un
système collectif avec comptage individuel.

Sur une installation base + appoint, seule l'intermittence de l'appoint est
prise en compte. Une régulation zonale peut être considérée comme une
régulation pièce par pièce. Un plancher chauffant régulé par zones jour/nuit
peut donc être associé à cette régulation.

Un poêle est modélisé comme un radiateur/convecteur pour déterminer
l'intermittence.

### Définition des équipements d'intermittence

En chauffage individuel :

- Absent : pas d'équipement permettant de programmer des réduits de température.
- Central sans minimum : programmation de la marche/arrêt seulement, sans
  garantie d'un minimum de température.
- Central avec minimum : ralenti ou abaissement central fixe, non modifiable
  par l'occupant, avec hors-gel, ou ralenti/abaissement au choix de l'occupant.
- Pièce par pièce avec minimum : ralenti ou abaissement fixe par pièce, non
  modifiable par l'occupant.

En chauffage collectif :

- Absent : pas de réduit de nuit.
- Central collectif : possibilité d'un ralenti de nuit.

### Chauffage divisé et central

Le chauffage divisé confond génération et émission : convecteurs électriques,
planchers chauffants électriques, etc.

Le chauffage central comprend un générateur central, individuel ou collectif,
et une distribution par un fluide chauffant, air ou eau.

## Repères d'implémentation — hors transcription réglementaire

- Les lignes « chauffage divisé sans régulation pièce par pièce » ne sont
  pas imprimées comme des lignes distinctes dans le PDF. La transcription
  n'ajoute pas ces lignes.
- La régulation prédictive du modèle 2.7 vient des 40 lignes ADEME 189–228,
  datées séparément dans resources/tables/chauffage/tv_intermittence_predictive.php.
- Les variantes de surface habitable pour le volume d'intermittence restent
  documentées en TASK-K94 ; cette mise en forme ne les réactive pas.
