# Génération du rapport PDF du DPE

`CalculDpePHP::genererPdf($xml, new DonneesRapportPdf(...))` (DTO `CalculDpePHP\Dto\DonneesRapportPdf`) (ou `php bin/dpe-pdf input.xml`)
produit le rapport d'un DPE à partir de son XML ADEME validé (avec `<sortie>`).
Chaque page part du **modèle officiel** du ministère, dont les données d'exemple
sont retirées puis remplacées par celles du XML.

## Contenu

| Chemin | Rôle |
|---|---|
| `templates/*.pdf` | Modèles « logement existant » (éditions 2023, 2024, 2025), préparés : sans flux d'objets (FPDI) et **sans leur texte d'exemple**. |
| `templates/maps/*.php` | Position de chaque ligne de texte du modèle d'origine : les ancres de mise en page. |
| `templates/maps/*.zones.php` | Zones que le générateur recouvre ; le texte d'exemple qui s'y trouve est retiré du modèle. |
| `templates/preview/*.png` | Rendu 36 dpi des pages, pour reprendre la couleur exacte du fond sous un masque. |
| `design/png/` | Pictogrammes de l'archive « documents design DPE » publiée avec les modèles (+ icônes isolation, confort d'été et passoire extraites de ses visuels). |
| `fonts/` | IBM Plex Sans / Sans Condensed (police des modèles, licence SIL OFL) et leur conversion TCPDF. |

Source des modèles : <https://rt-re-batiment.developpement-durable.gouv.fr/modeles-des-dpe-a788.html>.
Le modèle est celui en vigueur à la date d'établissement du DPE : éditions du
1er janvier 2023, du 1er juillet 2024 et du 1er septembre 2025
(`TemplateCatalog::EDITIONS`). Elles ont la même structure ; seule l'édition
2025 porte le cartouche du QR code, et celle de 2023 dit « surface habitable »
(libellé repris du modèle). L'édition 2021-2022, organisée autrement, n'est pas
prise en charge : un DPE établi avant 2023 lève `UnsupportedTemplateException`.

## Régénérer les modèles

Outils nécessaires à la préparation seulement : `qpdf` (avec `fix-qdf`) et poppler.

```bash
php bin/build-pdf-templates --download
php bin/build-pdf-templates --zones chemin/vers/*.xml   # au moins un XML par modèle
```

Le relevé des zones génère des rapports (chaque XML est rejoué à la date de
début de chaque édition) et note chaque surface recouverte ;
il faut le relancer dès qu'un masque change dans `src/Pdf/Render/Page/`. Une
zone propre à une page de suite (débordement des travaux) n'est pas relevée :
elle cache du texte fixe du modèle.

## Choix techniques

- **FPDI + TCPDF** : import de la page du modèle comme fond vectoriel, puis
  texte UTF-8 aux polices du modèle, formes et images par-dessus.
- **chillerlan/php-qrcode** : matrice du QR code, dessinée en modules
  vectoriels ; il pointe vers `https://observatoire-dpe-audit.ademe.fr/afficher-dpe/{numéro}`.
- Étiquettes énergie/climat redessinées en vectoriel à la géométrie du modèle ;
  pour F et G, pictogramme « passoire énergétique » relié à la flèche.
- Conventions reproduites des rapports d'exemple : fourchette de coût
  −15 %/+15 % arrondie à la dizaine extérieure (total = somme des usages) ;
  gain d'un comportement économe = part de consommation dépensière évitée ;
  volume d'eau chaude = 56 ℓ/j par adulte équivalent du logement (§11.1) ;
  émissions ⇔ km parcourus à 193 g CO₂/km.
