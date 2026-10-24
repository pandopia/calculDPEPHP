---
section_id: "13.2.2"
title: "Caractéristiques par défaut et dimensionnement des générateurs à combustion"
spec_pages: [86, 87, 88, 89, 90, 91, 92]
xml_outputs: ["pn", "qp0", "rpn", "rpint", "pveilleuse"]
tables: ["tv_generateur_combustion_id", "tv_pecs_combustion_id", "tv_pn_generateur_combustion_id"]
depends_on: ["13.2", "18.1"]
status: "digitalized"
---

# §13.2.2 — Caractéristiques par défaut des générateurs à combustion

> Source : resources/spec.pdf, p.86–92, annexe 1 d'octobre 2021.
> Tables, indices et exposants contrôlés visuellement le 24/10/2026.
> Transcription mise en forme ; relecture indépendante encore à effectuer.
> L'expression tronquée du PDF est conservée explicitement, pas reconstruite.

## Conventions de lecture

Dans les tables, $P_n$ est exprimée en kW et $L=\log_{10}(P_n)$.
Les rendements $R_{pn}$ et $R_{pint}$ sont donnés en **pour cent PCI** ;
les calculateurs qui les manipulent comme fractions doivent les diviser par 100.

Le symbole « — » conserve une cellule non renseignée dans le PDF.
Il ne constitue pas une valeur numérique ajoutée par la transcription.

Pour les lignes récentes gaz/fioul, on note l'expression du tableau :

$$
Q_f(P_n)=\frac{P_n(E+F\log_{10}(P_n))}{100}
$$

Le PDF place cette expression dans la colonne titrée « Qp0 en % puissance
nominale Pn » ; l'expression contient déjà $P_n$. Elle est reproduite telle
quelle, sans la multiplier une seconde fois par la puissance nominale.

## Chaudières gaz — p.86

| Type | Ancienneté | Critère de puissance | $R_{pn}$ (%) | $R_{pint}$ (%) | $QP_0$ | Veilleuse (W, si présente) |
|---|---|---|---:|---:|---|---:|
| Classique | Avant 1980 | $P_n$ | $84+2L$ | $80+3L$ | 4 % de $P_n$ | 240 |
| Classique | 1981–1985 | $P_n$ | $84+2L$ | $80+3L$ | 2 % de $P_n$ | 150 |
| Classique | 1986–1990 | $P_n$ | $84+2L$ | $80+3L$ | 1,5 % de $P_n$ | 150 |
| Standard | 1991–2000 | $P_n$ | $84+2L$ | $80+3L$ | 1,2 % de $P_n$ | 120 |
| Standard | 2001–2015 | $P_n$ | $84+2L$ | $80+3L$ | 1 % de $P_n$ | — |
| Standard | À partir de 2016 | $P_n$ | $84+2L$ | $80+3L$ | $Q_f(P_n)$ | — |
| Basse température | 1991–2000 | $P_n$ | $87,5+1,5L$ | $87,5+1,5L$ | 1,2 % de $P_n$ | 120 |
| Basse température | 2001–2015 | $P_n$ | $87,5+1,5L$ | $87,5+1,5L$ | 1 % de $P_n$ | — |
| Basse température | À partir de 2016 | $P_n$ | $87,5+1,5L$ | $87,5+1,5L$ | $Q_f(P_n)$ | — |
| Condensation | 1981–1985 | $P_n$ | $91+L$ | $97+L$ | 1 % de $P_n$ | 150 |
| Condensation | 1986–2000 | $P_n$ | $91+L$ | $97+L$ | 1 % de $P_n$ | 120 |
| Condensation | 2001–2015 | $P_n$ | $91+L$ | $97+L$ | 1 % de $P_n$ | — |
| Condensation | À partir de 2016 | $P_n\leq70$ | $91+3L$ | $103+2,5L$ | 0,5 % de $P_n$ | — |
| Condensation | À partir de 2016 | $70<P_n\leq400$ | $94+L$ | $105+0,5L$ | 0,3 % de $P_n$ | — |
| Condensation | À partir de 2016 | $P_n>400$ | 96,6 | 106,3 | 0,3 % de $P_n$ | — |

La borne « Avant 1980 » est celle imprimée dans le PDF ; elle n'est pas
remplacée ici par le libellé différent d'un enum XML.

## Chaudières fioul — p.86–87

| Type | Ancienneté | Critère de puissance | $R_{pn}$ (%) | $R_{pint}$ (%) | $QP_0$ |
|---|---|---|---:|---:|---|
| Classique | Avant 1970 | $P_n$ | $84+2L$ | $80+3L$ | 4 % de $P_n$ |
| Classique | 1970–1975 | $P_n$ | $84+2L$ | $80+3L$ | 3 % de $P_n$ |
| Classique | 1976–1980 | $P_n$ | $84+2L$ | $80+3L$ | 2 % de $P_n$ |
| Classique | 1981–1990 | $P_n$ | $84+2L$ | $80+3L$ | 1 % de $P_n$ |
| Standard | 1991–2015 | $P_n$ | $84+2L$ | $80+3L$ | 1 % de $P_n$ |
| Standard | À partir de 2016 | $P_n$ | $84+2L$ | $80+3L$ | $Q_f(P_n)$ |
| Basse température | 1991–2015 | $P_n$ | $87,5+1,5L$ | $87,5+1,5L$ | 1 % de $P_n$ |
| Basse température | À partir de 2016 | $P_n$ | $87,5+1,5L$ | $87,5+1,5L$ | $Q_f(P_n)$ |
| Condensation | 1996–2015 | $P_n$ | $91+L$ | $97+L$ | 1 % de $P_n$ |
| Condensation | À partir de 2016 | $P_n\leq70$ | $91+3L$ | $98+3L$ | 0,5 % de $P_n$ |
| Condensation | À partir de 2016 | $70<P_n\leq400$ | $94+L$ | $100+L$ | 0,6 % de $P_n$ |
| Condensation | À partir de 2016 | $P_n>400$ | 96,6 | 102,6 | 0,3 % de $P_n$ |

### Coefficients E et F — p.87

| Circuit de combustion, combustible liquide ou gazeux | E | F |
|---|---:|---:|
| Absence de ventilateur ou d'autre dispositif de circulation d'air ou de produits de combustion | 2,5 | −0,8 |
| Présence de ventilateur ou d'autre dispositif de circulation d'air ou de produits de combustion | 1,75 | −0,55 |

## §13.2.2.1 — Générateurs d'air chaud — p.87–88

Le point intermédiaire $w$ correspond à 50 % de charge.

Entre 0 et 50 % de charge :

$$
QP_x=\frac{(QP_{50}-0,15QP_0)x}{0,5}+0,15QP_0
$$

Entre 50 et 100 % de charge :

$$
QP_x=\frac{(QP_{100}-QP_{50})x}{0,5}+2QP_{50}-QP_{100}
$$

Avec les rendements exprimés en pour cent :

$$
QP_{50}=0,5P_n\frac{100-R_{pint}}{R_{pint}},
\qquad
QP_{100}=P_n\frac{100-R_{pn}}{R_{pn}}
$$

$$
QP_0=\frac{P_n(1,75-0,55\log_{10}(P_n))}{100}
$$

Pour $P_n>300$ kW, conserver pour **cette expression de $QP_0$** la valeur
obtenue à $P_n=300$ kW.

| Ancienneté / type | $R_{pn}$ (%) | $R_{pint}$ (%) |
|---|---:|---:|
| Avant 2006 | 77 | 74 |
| À partir de 2006, standard | 84 | 77 |
| À partir de 2006, condensation | 90 | 83 |

Les caractéristiques réelles issues des bases professionnelles peuvent être
utilisées pour les installations récentes ou recommandées.

## §13.2.2.2 — Radiateurs à gaz — p.88

$$
QP_x=1,04\,\frac{100-R_{pn}}{R_{pn}}\,P_nx
$$

| Ancienneté | $P_n<5$ kW : $R_{pn}$ (%) | $P_n\geq5$ kW : $R_{pn}$ (%) |
|---|---:|---:|
| Avant 2006 | 70 | 73 |
| À partir de 2006 | 80 | 82 |

## §13.2.2.3 — Chaudières bois — p.88–90

Les chaudières au charbon sont traitées comme les chaudières bois bûche.
Le point intermédiaire $w$ est à 50 % de charge.

Les deux expressions de $QP_x$, ainsi que celles de $QP_{50}$ et $QP_{100}$,
sont identiques à celles écrites ci-dessus pour l'air chaud, avec les
caractéristiques bois de la table suivante.

Pour conserver la lisibilité des cellules, on note, en kW :

$$
Q_a=0,08P_n(P_n)^{-0,27},\qquad
Q_b=0,07P_n(P_n)^{-0,3},\qquad
Q_c=0,085P_n(P_n)^{-0,4}
$$

Le tiret de la colonne $P_n$ conserve les cellules laissées vides dans le
document. Les valeurs de $R_{pn}$ et $R_{pint}$ restent en pour cent PCI.

### Bûche ou plaquette — p.89

| Année de fabrication | Critère $P_n$ (kW) | Colonne $P_n$ du PDF (kW) | $R_{pn}$ (%) | $R_{pint}$ (%) | $QP_0$ (kW) |
|---|---|---|---:|---:|---|
| <1978 | $\leq70$ | $P_n$ | $47+6L$ | $48+6L$ | $Q_a$ |
| <1978 | $70<P_n\leq400$ | 70 | 58 | 59 | 1,8 |
| <1978 | $>400$ | 70 | 58 | 59 | 1,1 |
| 1978–1994 | $\leq70$ | $P_n$ | $47+6L$ | $48+6L$ | $Q_b$ |
| 1978–1994 | $70<P_n\leq400$ | 70 | 58 | 59 | 1,4 |
| 1978–1994 | $>400$ | 70 | 58 | 59 | 0,8 |
| 1995–2003 | $\leq70$ | — | $47+6L$ | $48+6L$ | $Q_c$ |
| 1995–2003 | $70<P_n\leq400$ | 70 | 58 | 59 | 1,1 |
| 1995–2003 | $>400$ | 70 | 58 | 59 | 0,5 |
| 2004–2012 | $\leq70$ | — | $57+6L$ | $58+6L$ | expression tronquée† |
| 2004–2012 | $70<P_n\leq400$ | 70 | 68 | 69 | 1,1 |
| 2004–2012 | $>400$ | 70 | 68 | 69 | 0,5 |
| 2013–2017 | $\leq70$ | — | $67+6L$ | $68+6L$ | $Q_c$ |
| 2013–2017 | $70<P_n\leq400$ | 70 | 78 | 79 | 1,1 |
| 2013–2017 | $>400$ | 70 | 78 | 79 | 0,5 |
| 2018–2019 | $\leq70$ | — | $80+2L$ | $77+3L$ | $Q_c$ |
| 2018–2019 | $70<P_n\leq400$ | 70 | 84 | 83 | 1,1 |
| 2018–2019 | $>400$ | 70 | 84 | 83 | 0,5 |
| >2019 | $\leq20$ | — | $89+2L$ | $84+2L$ | $Q_c$ |
| >2019 | $20<P_n\leq70$ | — | $90+2L$ | $85+2L$ | $Q_c$ |
| >2019 | $70<P_n\leq400$ | 70 | 94 | 89 | 1,1 |
| >2019 | $>400$ | 70 | 94 | 89 | 0,5 |

† La cellule imprimée p.89 s'arrête après « $0,085P_n\cdot*$ ». La
transcription n'y ajoute aucun exposant manquant. Le complément utilisé par
le moteur est distingué dans les repères d'implémentation ci-dessous.

### Granulés — p.89–90

| Année de fabrication | Critère $P_n$ (kW) | Colonne $P_n$ du PDF (kW) | $R_{pn}$ (%) | $R_{pint}$ (%) | $QP_0$ (kW) |
|---|---|---|---:|---:|---|
| <1978 | $\leq70$ | $P_n$ | $47+6L$ | $48+6L$ | $Q_a$ |
| <1978 | $70<P_n\leq400$ | 70 | 58 | 59 | 1,8 |
| <1978 | $>400$ | 70 | 58 | 59 | 1,1 |
| 1978–1994 | $\leq70$ | $P_n$ | $47+6L$ | $48+6L$ | $Q_b$ |
| 1978–1994 | $70<P_n\leq400$ | 70 | 58 | 59 | 1,4 |
| 1978–1994 | $>400$ | 70 | 58 | 59 | 0,8 |
| 1995–2003 | $\leq70$ | — | $57+6L$ | $58+6L$ | $Q_c$ |
| 1995–2003 | $70<P_n\leq400$ | 70 | 68 | 69 | 1,1 |
| 1995–2003 | $>400$ | 70 | 68 | 69 | 0,5 |
| 2004–2012 | $\leq70$ | — | $67+6L$ | $68+6L$ | $Q_c$ |
| 2004–2012 | $70<P_n\leq400$ | 70 | 78 | 79 | 1,1 |
| 2004–2012 | $>400$ | 70 | 78 | 79 | 0,5 |
| 2013–2019 | $\leq70$ | — | $80+2L$ | $77+3L$ | $Q_c$ |
| 2013–2019 | $70<P_n\leq400$ | 70 | 84 | 83 | 1,1 |
| 2013–2019 | $>400$ | 70 | 84 | 83 | 0,5 |
| >2019 | $\leq20$ | — | $91+2L$ | $88+2L$ | $Q_c$ |
| >2019 | $20<P_n\leq70$ | — | $92+2L$ | $89+2L$ | $Q_c$ |
| >2019 | $70<P_n\leq400$ | 70 | 96 | 93 | 1,1 |
| >2019 | $>400$ | 70 | 96 | 93 | 0,5 |

Si l'année d'installation est inconnue, la chaudière bois est considérée
installée l'année de construction du bâtiment. Les valeurs des bases
professionnelles peuvent être utilisées pour les chaudières récentes ou
recommandées.

## §13.2.2.4 — Calcul des puissances nominales — p.90–92

Lorsque la puissance individuelle à combustion n'est pas connue, et pour
les recommandations, son estimation pour le chauffage est :

$$
P_{\mathrm{ch}}=\frac{1,2\,GV(19-T_{\mathrm{base}})}{1000\times0,95^3}
$$

$P_{\mathrm{ch}}$ est en kW ; $GV$ représente les déperditions par enveloppe
et renouvellement d'air en W/K ; $T_{\mathrm{base}}$ est la température
extérieure de base en °C, selon zone et altitude (§18.1).

### Appartement alimenté par un chauffage collectif — p.90

$$
GV_{\mathrm{immeuble}}
=GV_{\mathrm{appartement}}\frac{Sh_{\mathrm{immeuble}}}{Sh_{\mathrm{appartement}}},
\qquad
P_{\mathrm{ch,immeuble}}
=\frac{1,2\,GV_{\mathrm{immeuble}}(19-T_{\mathrm{base}})}
{1000\times0,95^3}
$$

### Appartement généré depuis l'immeuble, chauffage individuel homogène — p.90–91

Pour $N$ logements dans l'immeuble :

$$
P_{\mathrm{ch}}
=\frac{1,2\,(GV/N)(19-T_{\mathrm{base}})}{1000\times0,95^3}
$$

### Générateurs ne chauffant qu'une partie du logement — p.91

Pour deux surfaces $Sh_1+Sh_2=Sh_{\mathrm{tot}}$ :

$$
P_{\mathrm{ch},i}
=\frac{Sh_i}{Sh_{\mathrm{tot}}}
\frac{1,2\,GV(19-T_{\mathrm{base}})}{1000\times0,95^3},
\quad i\in\{1,2\}
$$

### Puissance nécessaire pour l'ECS — p.91

$V_s$ est le volume de stockage en litres.

| Type de production | Volume de stockage | $P_{\mathrm{ecs}}$ (kW) |
|---|---|---|
| Instantanée | $V_s=0$ | $21$ |
| Semi-instantanée | $0<V_s\leq20$ | $21-0,8V_s$ |
| Semi-accumulation | $20<V_s\leq150$ | $5-1,751(V_s-20)/65$ |
| Accumulation | $V_s>150$ | $(7,14V_s+428)/1000$ |

La puissance de dimensionnement est :

$$
P_{\mathrm{dim}}=\max(P_{\mathrm{ch}};P_{\mathrm{ecs}})
$$

### Puissance nominale des chaudières — p.91

Les intitulés temporels sont ceux du PDF.

| $P_{\mathrm{dim}}$ (kW) | Murales installées avant 2005 ou chaudières sur sol : $P_n$ (kW) | Murales installées à partir de 2006 : $P_n$ (kW) |
|---|---:|---:|
| $\leq5$ | 18 | 5 |
| $5<P_{\mathrm{dim}}\leq10$ | 18 | 10 |
| $10<P_{\mathrm{dim}}\leq13$ | 18 | 13 |
| $13<P_{\mathrm{dim}}\leq18$ | 18 | 18 |
| $18<P_{\mathrm{dim}}\leq24$ | 24 | 24 |
| $24<P_{\mathrm{dim}}\leq28$ | 28 | 28 |
| $28<P_{\mathrm{dim}}\leq32$ | 32 | 32 |
| $32<P_{\mathrm{dim}}\leq40$ | 40 | 40 |
| $>40$ | $(\lfloor P_{\mathrm{dim}}/5\rfloor+1)\times5$ | $(\lfloor P_{\mathrm{dim}}/5\rfloor+1)\times5$ |

### Logement chauffé par n radiateurs à gaz — p.92

La puissance de chaque radiateur est :

$$
P_n=\frac{P_{\mathrm{ch}}}{n}
$$

## Repères d'implémentation — hors transcription réglementaire

- Les formules de pertes utilisent des puissances homogènes : la table bois
  donne $QP_0$ en kW, tandis que les sorties XML de pertes à l'arrêt sont en W.
- Pour la cellule bois bûche/plaquette 2004–2012 tronquée, le moteur complète
  l'expression avec Open3CL/engine, src/tv.js, révision
  aa78053f549b93509009d195b9332ac85f33fb4d : $0,085P_n(P_n)^{-0,4}$.
  Ce complément ne prétend pas être une lecture de la cellule du PDF.
- Les caractéristiques des granulés à condensation après 2024 proviennent
  des lignes ADEME 94–97 du modèle 2.7, séparément datées dans les tables PHP.
  Elles ne sont pas insérées rétroactivement dans la table bois de 2021.
- Le dimensionnement collectif et la virtualisation renvoient aussi au §17.
  Les conventions d'arrondi non concordantes des références restent
  documentées dans TASKS_DONE.md / TASK-K31 ; cette transcription ne les résout pas.
