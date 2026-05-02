# resources/specsplitted/

Spec officielle DPE 3CL-2021 (Annexe 1, octobre 2021) découpée en un fichier markdown par sous-section.

## Convention

Chaque fichier `.md` commence par un header YAML :

```yaml
---
section_id: "3.2.1"            # numéro de section dans la spec PDF
title: "Calcul des Umur"       # titre de la section
spec_pages: [13-16]            # plage de pages dans le PDF
xml_outputs: ["umur", "umur0"] # balises XML produites par cette section
tables: ["tv_umur_id", "tv_umur0_id"]  # tables tv_*_id mentionnées
depends_on: ["3.1"]            # autres sections nécessaires en amont
status: "verbatim"             # verbatim | digitalized | reviewed
---
```

## Cycle de vie d'un fichier

1. **`status: verbatim`** — texte brut extrait par `pdftotext -layout`. Lisible mais pas exploitable mécaniquement (tables désalignées, formules sans LaTeX). C'est l'état initial.
2. **`status: digitalized`** — un agent IA a :
   - reformaté les formules en LaTeX
   - transcrit les tables en pipe-markdown (`| col1 | col2 |`)
   - vérifié les indices/exposants
   - retiré le bloc verbatim une fois la transcription complète
3. **`status: reviewed`** — un second agent IA a relu et validé la transcription contre le PDF.

## Lien avec le code PHP

- Tout `Calculator` PHP doit pointer vers son fichier `.md` source via `@spec-source` dans son doc-block.
- Toute table PHP (`resources/tables/**/*.php`) doit pointer vers le `.md` qui contient sa transcription markdown.

Voir `CLAUDE.md` pour les conventions de traçabilité complètes.