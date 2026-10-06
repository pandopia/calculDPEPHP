# Dépendances livrées avec l'éditeur

## g-plan (`g-plan-1.8.0.tgz`)

Éditeur de plan Angular (Fabric.js, MapLibre GL, Three.js) développé par
Agileo Solutions (dépôt GitLab `agileosolutions/g-ordon/g-plan`, non public).
Sa diffusion dans ce dépôt et dans l'éditeur publié a été autorisée par son
auteur.

Tarball produit par `make pack` à la racine du dépôt g-plan, installé par
`"g-plan": "file:vendor/g-plan-1.8.0.tgz"` (`package.json`) ; `npm ci` (CI
GitHub Pages) l'installe tel quel.

Mise à jour :

```bash
cd ~/Sites/g-plan && git pull && make pack
cp dist/g-plan-X.Y.Z.tgz ~/Sites/calculDPEPHP/editeur/vendor/
cd ~/Sites/calculDPEPHP/editeur
npm uninstall g-plan && rm -f vendor/g-plan-1.8.0.tgz
npm install ./vendor/g-plan-X.Y.Z.tgz
rm -rf .angular/cache
```

Pairs exigés (`INSTALL.md` de g-plan) : `maplibre-gl@^5` (pas la 6),
`three`, `fabric@^5` (servi comme script global `window.fabric`, voir
`angular.json` → `assets` → `vendor/fabric.min.js`).
