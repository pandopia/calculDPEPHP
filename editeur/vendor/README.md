# Dépendances livrées avec l'éditeur

## g-plan (`g-plan-2.28.0.tgz`)

Éditeur de plan Angular (Fabric.js, MapLibre GL, Three.js) développé par
Agileo Solutions (dépôt GitLab `agileosolutions/g-ordon/g-plan`, non public).
Sa diffusion dans ce dépôt et dans l'éditeur publié a été autorisée par son
auteur.

Tarball produit par `make pack` à la racine du dépôt g-plan (branche
`feat/fabric-v7`, version 2.28.0), installé par
`"g-plan": "file:vendor/g-plan-2.28.0.tgz"` (`package.json`) ; `npm ci` (CI
GitHub Pages) l'installe tel quel.

Mise à jour :

```bash
cd ~/Sites/g-plan && git pull && make pack
cp dist/g-plan-X.Y.Z.tgz ~/Sites/calculDPEPHP/editeur/vendor/
cd ~/Sites/calculDPEPHP/editeur
npm uninstall g-plan && rm -f vendor/g-plan-2.28.0.tgz
npm install ./vendor/g-plan-X.Y.Z.tgz
rm -rf .angular/cache
```

Pairs exigés (`INSTALL.md` de g-plan) :

- `fabric@^7` : dépendance npm, importée par g-plan (plus de `window.fabric`
  ni de script à charger) ;
- `maplibre-gl` 6 (≥ 6.12, correctif de sécurité) : son worker, qui décode
  les fonds **vectoriels**, est servi avec l'application
  (`angular.json` → `assets` → `vendor/maplibre-gl-{worker,shared}.mjs`, les
  deux fichiers côte à côte) et déclaré par
  `provideGPlan({ maplibreWorkerUrl: 'vendor/maplibre-gl-worker.mjs' })` dans
  `src/app/ui/plan-editeur.ts`. Sans ces deux gestes, le satellite s'affiche
  mais les fonds vectoriels et le cadastre restent blancs, sans erreur ;
- `three`.

Tout est chargé à la demande, à l'ouverture du plan : le bundle initial n'en
contient rien.
