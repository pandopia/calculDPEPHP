<?php

declare(strict_types=1);

/**
 * Coefficients d'orientation et d'inclinaison des parois vitrées : C1.
 *
 * Indexation : zone_id (1-8) → mois (1-12) → orientation → inclinaison.
 *
 * Zones : 1=H1a, 2=H1b, 3=H1c, 4=H2a, 5=H2b, 6=H2c, 7=H2d, 8=H3.
 *
 * Orientations : 'sud' (enum_orientation_id=1), 'nord' (2), 'est' (3), 'ouest' (4).
 * Inclinaisons :
 *   'sup75'      enum_inclinaison_vitrage_id=3  (≥75°, vertical)
 *   'pente'      enum_inclinaison_vitrage_id=2  (75°>…≥25°)
 *   'inf25'      enum_inclinaison_vitrage_id=1  (<25°)
 *   'horizontal' enum_inclinaison_vitrage_id=4  (horizontal)
 *
 * Ordre colonnes spec : Sud[sup75, pente, inf25], Ouest[sup75, pente, inf25],
 *                       Nord[sup75, pente, inf25], Est[sup75, pente, inf25], Horizontal.
 *
 * @spec-section 18.5
 * @spec-pages 144
 * @spec-source resources/specsplitted/18-annexes/05-c1-orientation-inclinaison/00-calcul.md
 * @generated-on 2026-04-29
 * @status verified-spec — valeurs lues depuis la table p.144 ; vérifiées sur bat_post2026 (Sse=474.517248 exact)
 */
return [
    // Zone 1 — H1a
    1 => [
         1 => ['sud' => ['sup75' => 1.00, 'pente' => 1.67, 'inf25' => 0.77], 'ouest' => ['sup75' => 0.43, 'pente' => 0.92, 'inf25' => 0.64], 'nord' => ['sup75' => 0.31, 'pente' => 0.66, 'inf25' => 0.52], 'est' => ['sup75' => 0.40, 'pente' => 0.85, 'inf25' => 0.63], 'horizontal' => 0.62],
         2 => ['sud' => ['sup75' => 1.00, 'pente' => 1.78, 'inf25' => 0.89], 'ouest' => ['sup75' => 0.47, 'pente' => 1.02, 'inf25' => 0.75], 'nord' => ['sup75' => 0.32, 'pente' => 0.67, 'inf25' => 0.62], 'est' => ['sup75' => 0.51, 'pente' => 1.07, 'inf25' => 0.76], 'horizontal' => 0.74],
         3 => ['sud' => ['sup75' => 1.00, 'pente' => 1.99, 'inf25' => 1.12], 'ouest' => ['sup75' => 0.58, 'pente' => 1.28, 'inf25' => 0.98], 'nord' => ['sup75' => 0.37, 'pente' => 0.79, 'inf25' => 0.85], 'est' => ['sup75' => 0.63, 'pente' => 1.38, 'inf25' => 1.00], 'horizontal' => 0.98],
         4 => ['sud' => ['sup75' => 1.00, 'pente' => 2.39, 'inf25' => 1.57], 'ouest' => ['sup75' => 0.79, 'pente' => 1.82, 'inf25' => 1.45], 'nord' => ['sup75' => 0.50, 'pente' => 1.17, 'inf25' => 1.34], 'est' => ['sup75' => 0.84, 'pente' => 1.93, 'inf25' => 1.47], 'horizontal' => 1.45],
         5 => ['sud' => ['sup75' => 1.00, 'pente' => 2.74, 'inf25' => 1.97], 'ouest' => ['sup75' => 1.05, 'pente' => 2.43, 'inf25' => 1.89], 'nord' => ['sup75' => 0.65, 'pente' => 1.67, 'inf25' => 1.80], 'est' => ['sup75' => 1.01, 'pente' => 2.38, 'inf25' => 1.88], 'horizontal' => 1.88],
         6 => ['sud' => ['sup75' => 1.00, 'pente' => 2.98, 'inf25' => 2.26], 'ouest' => ['sup75' => 1.25, 'pente' => 2.88, 'inf25' => 2.22], 'nord' => ['sup75' => 0.75, 'pente' => 2.06, 'inf25' => 2.14], 'est' => ['sup75' => 1.16, 'pente' => 2.71, 'inf25' => 2.18], 'horizontal' => 2.19],
         7 => ['sud' => ['sup75' => 1.00, 'pente' => 2.87, 'inf25' => 2.12], 'ouest' => ['sup75' => 1.13, 'pente' => 2.62, 'inf25' => 2.05], 'nord' => ['sup75' => 0.70, 'pente' => 1.87, 'inf25' => 1.98], 'est' => ['sup75' => 1.14, 'pente' => 2.64, 'inf25' => 2.06], 'horizontal' => 2.05],
         8 => ['sud' => ['sup75' => 1.00, 'pente' => 2.52, 'inf25' => 1.70], 'ouest' => ['sup75' => 0.86, 'pente' => 2.01, 'inf25' => 1.60], 'nord' => ['sup75' => 0.53, 'pente' => 1.29, 'inf25' => 1.49], 'est' => ['sup75' => 0.86, 'pente' => 2.03, 'inf25' => 1.60], 'horizontal' => 1.59],
         9 => ['sud' => ['sup75' => 1.00, 'pente' => 2.14, 'inf25' => 1.28], 'ouest' => ['sup75' => 0.76, 'pente' => 1.64, 'inf25' => 1.18], 'nord' => ['sup75' => 0.41, 'pente' => 0.91, 'inf25' => 1.02], 'est' => ['sup75' => 0.66, 'pente' => 1.48, 'inf25' => 1.15], 'horizontal' => 1.15],
        10 => ['sud' => ['sup75' => 1.00, 'pente' => 1.84, 'inf25' => 0.96], 'ouest' => ['sup75' => 0.58, 'pente' => 1.21, 'inf25' => 0.84], 'nord' => ['sup75' => 0.32, 'pente' => 0.66, 'inf25' => 0.67], 'est' => ['sup75' => 0.47, 'pente' => 1.04, 'inf25' => 0.80], 'horizontal' => 0.81],
        11 => ['sud' => ['sup75' => 1.00, 'pente' => 1.74, 'inf25' => 0.86], 'ouest' => ['sup75' => 0.42, 'pente' => 0.94, 'inf25' => 0.72], 'nord' => ['sup75' => 0.36, 'pente' => 0.76, 'inf25' => 0.62], 'est' => ['sup75' => 0.51, 'pente' => 1.07, 'inf25' => 0.75], 'horizontal' => 0.73],
        12 => ['sud' => ['sup75' => 1.00, 'pente' => 1.59, 'inf25' => 0.66], 'ouest' => ['sup75' => 0.38, 'pente' => 0.77, 'inf25' => 0.52], 'nord' => ['sup75' => 0.25, 'pente' => 0.52, 'inf25' => 0.41], 'est' => ['sup75' => 0.35, 'pente' => 0.73, 'inf25' => 0.52], 'horizontal' => 0.51],
    ],
    // Zone 2 — H1b
    2 => [
         1 => ['sud' => ['sup75' => 1.00, 'pente' => 1.65, 'inf25' => 0.72], 'ouest' => ['sup75' => 0.41, 'pente' => 0.84, 'inf25' => 0.60], 'nord' => ['sup75' => 0.27, 'pente' => 0.56, 'inf25' => 0.47], 'est' => ['sup75' => 0.37, 'pente' => 0.81, 'inf25' => 0.58], 'horizontal' => 0.58],
         2 => ['sud' => ['sup75' => 1.00, 'pente' => 1.80, 'inf25' => 0.84], 'ouest' => ['sup75' => 0.53, 'pente' => 1.13, 'inf25' => 0.71], 'nord' => ['sup75' => 0.35, 'pente' => 0.72, 'inf25' => 0.66], 'est' => ['sup75' => 0.49, 'pente' => 1.06, 'inf25' => 0.78], 'horizontal' => 0.78],
         3 => ['sud' => ['sup75' => 1.00, 'pente' => 2.04, 'inf25' => 1.07], 'ouest' => ['sup75' => 0.67, 'pente' => 1.46, 'inf25' => 0.94], 'nord' => ['sup75' => 0.38, 'pente' => 0.81, 'inf25' => 0.91], 'est' => ['sup75' => 0.59, 'pente' => 1.34, 'inf25' => 1.04], 'horizontal' => 1.04],
         4 => ['sud' => ['sup75' => 1.00, 'pente' => 2.39, 'inf25' => 1.53], 'ouest' => ['sup75' => 0.91, 'pente' => 2.00, 'inf25' => 1.42], 'nord' => ['sup75' => 0.49, 'pente' => 1.16, 'inf25' => 1.33], 'est' => ['sup75' => 0.76, 'pente' => 1.77, 'inf25' => 1.42], 'horizontal' => 1.44],
         5 => ['sud' => ['sup75' => 1.00, 'pente' => 2.80, 'inf25' => 2.08], 'ouest' => ['sup75' => 1.23, 'pente' => 2.70, 'inf25' => 2.00], 'nord' => ['sup75' => 0.66, 'pente' => 1.72, 'inf25' => 1.87], 'est' => ['sup75' => 0.99, 'pente' => 2.36, 'inf25' => 1.92], 'horizontal' => 1.95],
         6 => ['sud' => ['sup75' => 1.00, 'pente' => 2.88, 'inf25' => 2.21], 'ouest' => ['sup75' => 1.22, 'pente' => 2.78, 'inf25' => 2.17], 'nord' => ['sup75' => 0.78, 'pente' => 2.07, 'inf25' => 2.07], 'est' => ['sup75' => 1.11, 'pente' => 2.61, 'inf25' => 2.10], 'horizontal' => 2.11],
         7 => ['sud' => ['sup75' => 1.00, 'pente' => 2.81, 'inf25' => 2.37], 'ouest' => ['sup75' => 1.16, 'pente' => 2.64, 'inf25' => 2.29], 'nord' => ['sup75' => 0.74, 'pente' => 1.92, 'inf25' => 1.96], 'est' => ['sup75' => 1.06, 'pente' => 2.51, 'inf25' => 2.01], 'horizontal' => 2.01],
         8 => ['sud' => ['sup75' => 1.00, 'pente' => 2.53, 'inf25' => 1.88], 'ouest' => ['sup75' => 0.98, 'pente' => 2.19, 'inf25' => 1.77], 'nord' => ['sup75' => 0.55, 'pente' => 1.35, 'inf25' => 1.52], 'est' => ['sup75' => 0.83, 'pente' => 1.99, 'inf25' => 1.61], 'horizontal' => 1.62],
         9 => ['sud' => ['sup75' => 1.00, 'pente' => 2.13, 'inf25' => 1.18], 'ouest' => ['sup75' => 0.74, 'pente' => 1.60, 'inf25' => 1.08], 'nord' => ['sup75' => 0.35, 'pente' => 0.77, 'inf25' => 0.96], 'est' => ['sup75' => 0.56, 'pente' => 1.33, 'inf25' => 1.08], 'horizontal' => 1.11],
        10 => ['sud' => ['sup75' => 1.00, 'pente' => 1.89, 'inf25' => 1.36], 'ouest' => ['sup75' => 0.69, 'pente' => 1.43, 'inf25' => 1.18], 'nord' => ['sup75' => 0.40, 'pente' => 0.84, 'inf25' => 0.79], 'est' => ['sup75' => 0.47, 'pente' => 1.09, 'inf25' => 0.88], 'horizontal' => 0.91],
        11 => ['sud' => ['sup75' => 1.00, 'pente' => 1.76, 'inf25' => 0.81], 'ouest' => ['sup75' => 0.48, 'pente' => 1.03, 'inf25' => 0.68], 'nord' => ['sup75' => 0.37, 'pente' => 0.80, 'inf25' => 0.66], 'est' => ['sup75' => 0.47, 'pente' => 1.04, 'inf25' => 0.77], 'horizontal' => 0.76],
        12 => ['sud' => ['sup75' => 1.00, 'pente' => 1.66, 'inf25' => 0.96], 'ouest' => ['sup75' => 0.49, 'pente' => 1.00, 'inf25' => 0.76], 'nord' => ['sup75' => 0.33, 'pente' => 0.71, 'inf25' => 0.55], 'est' => ['sup75' => 0.39, 'pente' => 0.84, 'inf25' => 0.63], 'horizontal' => 0.63],
    ],
    // Zone 3 — H1c
    3 => [
         1 => ['sud' => ['sup75' => 1.00, 'pente' => 1.75, 'inf25' => 0.87], 'ouest' => ['sup75' => 0.46, 'pente' => 1.00, 'inf25' => 0.74], 'nord' => ['sup75' => 0.35, 'pente' => 0.75, 'inf25' => 0.63], 'est' => ['sup75' => 0.49, 'pente' => 1.04, 'inf25' => 0.75], 'horizontal' => 0.74],
         2 => ['sud' => ['sup75' => 1.00, 'pente' => 1.80, 'inf25' => 0.90], 'ouest' => ['sup75' => 0.48, 'pente' => 1.03, 'inf25' => 0.76], 'nord' => ['sup75' => 0.30, 'pente' => 0.62, 'inf25' => 0.62], 'est' => ['sup75' => 0.47, 'pente' => 1.04, 'inf25' => 0.76], 'horizontal' => 0.75],
         3 => ['sud' => ['sup75' => 1.00, 'pente' => 2.04, 'inf25' => 1.16], 'ouest' => ['sup75' => 0.64, 'pente' => 1.40, 'inf25' => 1.04], 'nord' => ['sup75' => 0.35, 'pente' => 0.75, 'inf25' => 0.88], 'est' => ['sup75' => 0.58, 'pente' => 1.31, 'inf25' => 1.02], 'horizontal' => 1.02],
         4 => ['sud' => ['sup75' => 1.00, 'pente' => 2.45, 'inf25' => 1.61], 'ouest' => ['sup75' => 0.60, 'pente' => 1.51, 'inf25' => 1.40], 'nord' => ['sup75' => 0.48, 'pente' => 1.16, 'inf25' => 1.37], 'est' => ['sup75' => 1.09, 'pente' => 2.37, 'inf25' => 1.60], 'horizontal' => 1.49],
         5 => ['sud' => ['sup75' => 1.00, 'pente' => 2.84, 'inf25' => 2.08], 'ouest' => ['sup75' => 1.22, 'pente' => 2.75, 'inf25' => 2.06], 'nord' => ['sup75' => 0.70, 'pente' => 1.84, 'inf25' => 1.94], 'est' => ['sup75' => 1.00, 'pente' => 2.40, 'inf25' => 1.98], 'horizontal' => 2.00],
         6 => ['sud' => ['sup75' => 1.00, 'pente' => 3.10, 'inf25' => 2.38], 'ouest' => ['sup75' => 1.26, 'pente' => 2.95, 'inf25' => 2.33], 'nord' => ['sup75' => 0.77, 'pente' => 2.19, 'inf25' => 2.26], 'est' => ['sup75' => 1.20, 'pente' => 2.88, 'inf25' => 2.31], 'horizontal' => 2.31],
         7 => ['sud' => ['sup75' => 1.00, 'pente' => 3.04, 'inf25' => 2.29], 'ouest' => ['sup75' => 1.26, 'pente' => 2.92, 'inf25' => 2.25], 'nord' => ['sup75' => 0.71, 'pente' => 1.99, 'inf25' => 2.14], 'est' => ['sup75' => 1.10, 'pente' => 2.66, 'inf25' => 2.19], 'horizontal' => 2.21],
         8 => ['sud' => ['sup75' => 1.00, 'pente' => 2.67, 'inf25' => 1.85], 'ouest' => ['sup75' => 1.03, 'pente' => 2.37, 'inf25' => 1.79], 'nord' => ['sup75' => 0.57, 'pente' => 1.44, 'inf25' => 1.66], 'est' => ['sup75' => 0.90, 'pente' => 2.12, 'inf25' => 1.74], 'horizontal' => 1.75],
         9 => ['sud' => ['sup75' => 1.00, 'pente' => 2.21, 'inf25' => 1.38], 'ouest' => ['sup75' => 0.78, 'pente' => 1.73, 'inf25' => 1.29], 'nord' => ['sup75' => 0.48, 'pente' => 1.07, 'inf25' => 1.15], 'est' => ['sup75' => 0.67, 'pente' => 1.58, 'inf25' => 1.26], 'horizontal' => 1.26],
        10 => ['sud' => ['sup75' => 1.00, 'pente' => 1.89, 'inf25' => 1.03], 'ouest' => ['sup75' => 0.62, 'pente' => 1.30, 'inf25' => 0.92], 'nord' => ['sup75' => 0.36, 'pente' => 0.75, 'inf25' => 0.76], 'est' => ['sup75' => 0.47, 'pente' => 1.09, 'inf25' => 0.87], 'horizontal' => 0.89],
        11 => ['sud' => ['sup75' => 1.00, 'pente' => 1.77, 'inf25' => 0.90], 'ouest' => ['sup75' => 0.53, 'pente' => 1.12, 'inf25' => 0.78], 'nord' => ['sup75' => 0.35, 'pente' => 0.74, 'inf25' => 0.64], 'est' => ['sup75' => 0.42, 'pente' => 0.95, 'inf25' => 0.75], 'horizontal' => 0.76],
        12 => ['sud' => ['sup75' => 1.00, 'pente' => 1.63, 'inf25' => 0.71], 'ouest' => ['sup75' => 0.42, 'pente' => 0.87, 'inf25' => 0.58], 'nord' => ['sup75' => 0.26, 'pente' => 0.56, 'inf25' => 0.45], 'est' => ['sup75' => 0.35, 'pente' => 0.75, 'inf25' => 0.56], 'horizontal' => 0.56],
    ],
    // Zone 4 — H2a
    4 => [
         1 => ['sud' => ['sup75' => 1.00, 'pente' => 1.66, 'inf25' => 0.75], 'ouest' => ['sup75' => 0.42, 'pente' => 0.88, 'inf25' => 0.49], 'nord' => ['sup75' => 0.28, 'pente' => 0.60, 'inf25' => 0.49], 'est' => ['sup75' => 0.39, 'pente' => 0.83, 'inf25' => 0.61], 'horizontal' => 1.14],
         2 => ['sud' => ['sup75' => 1.00, 'pente' => 1.79, 'inf25' => 0.91], 'ouest' => ['sup75' => 0.53, 'pente' => 1.11, 'inf25' => 0.64], 'nord' => ['sup75' => 0.33, 'pente' => 0.69, 'inf25' => 0.64], 'est' => ['sup75' => 0.48, 'pente' => 1.03, 'inf25' => 0.76], 'horizontal' => 1.21],
         3 => ['sud' => ['sup75' => 1.00, 'pente' => 2.01, 'inf25' => 1.13], 'ouest' => ['sup75' => 0.56, 'pente' => 1.26, 'inf25' => 0.85], 'nord' => ['sup75' => 0.35, 'pente' => 0.74, 'inf25' => 0.85], 'est' => ['sup75' => 0.66, 'pente' => 1.42, 'inf25' => 1.02], 'horizontal' => 1.29],
         4 => ['sud' => ['sup75' => 1.00, 'pente' => 2.40, 'inf25' => 1.57], 'ouest' => ['sup75' => 0.87, 'pente' => 1.96, 'inf25' => 1.35], 'nord' => ['sup75' => 0.51, 'pente' => 1.20, 'inf25' => 1.35], 'est' => ['sup75' => 0.85, 'pente' => 1.90, 'inf25' => 1.47], 'horizontal' => 1.72],
         5 => ['sud' => ['sup75' => 1.00, 'pente' => 2.86, 'inf25' => 2.08], 'ouest' => ['sup75' => 1.11, 'pente' => 2.58, 'inf25' => 1.92], 'nord' => ['sup75' => 0.65, 'pente' => 1.73, 'inf25' => 1.92], 'est' => ['sup75' => 1.08, 'pente' => 2.51, 'inf25' => 2.00], 'horizontal' => 2.36],
         6 => ['sud' => ['sup75' => 1.00, 'pente' => 2.98, 'inf25' => 2.26], 'ouest' => ['sup75' => 1.14, 'pente' => 2.71, 'inf25' => 2.15], 'nord' => ['sup75' => 0.77, 'pente' => 2.11, 'inf25' => 2.15], 'est' => ['sup75' => 1.27, 'pente' => 2.91, 'inf25' => 2.23], 'horizontal' => 2.72],
         7 => ['sud' => ['sup75' => 1.00, 'pente' => 2.80, 'inf25' => 2.08], 'ouest' => ['sup75' => 1.08, 'pente' => 2.51, 'inf25' => 1.95], 'nord' => ['sup75' => 0.74, 'pente' => 1.93, 'inf25' => 1.95], 'est' => ['sup75' => 1.14, 'pente' => 2.64, 'inf25' => 2.03], 'horizontal' => 2.83],
         8 => ['sud' => ['sup75' => 1.00, 'pente' => 2.57, 'inf25' => 1.76], 'ouest' => ['sup75' => 0.92, 'pente' => 2.12, 'inf25' => 1.56], 'nord' => ['sup75' => 0.55, 'pente' => 1.36, 'inf25' => 1.56], 'est' => ['sup75' => 0.97, 'pente' => 2.18, 'inf25' => 1.67], 'horizontal' => 2.15],
         9 => ['sud' => ['sup75' => 1.00, 'pente' => 2.20, 'inf25' => 1.32], 'ouest' => ['sup75' => 0.70, 'pente' => 1.57, 'inf25' => 1.04], 'nord' => ['sup75' => 0.37, 'pente' => 0.82, 'inf25' => 1.04], 'est' => ['sup75' => 0.70, 'pente' => 1.56, 'inf25' => 1.19], 'horizontal' => 1.31],
        10 => ['sud' => ['sup75' => 1.00, 'pente' => 1.86, 'inf25' => 0.98], 'ouest' => ['sup75' => 0.49, 'pente' => 1.07, 'inf25' => 0.69], 'nord' => ['sup75' => 0.33, 'pente' => 0.68, 'inf25' => 0.69], 'est' => ['sup75' => 0.54, 'pente' => 1.18, 'inf25' => 0.85], 'horizontal' => 1.15],
        11 => ['sud' => ['sup75' => 1.00, 'pente' => 1.72, 'inf25' => 0.83], 'ouest' => ['sup75' => 0.46, 'pente' => 0.97, 'inf25' => 0.57], 'nord' => ['sup75' => 0.32, 'pente' => 0.68, 'inf25' => 0.57], 'est' => ['sup75' => 0.46, 'pente' => 0.97, 'inf25' => 0.69], 'horizontal' => 1.02],
        12 => ['sud' => ['sup75' => 1.00, 'pente' => 1.59, 'inf25' => 0.66], 'ouest' => ['sup75' => 0.38, 'pente' => 0.77, 'inf25' => 0.40], 'nord' => ['sup75' => 0.24, 'pente' => 0.50, 'inf25' => 0.40], 'est' => ['sup75' => 0.34, 'pente' => 0.72, 'inf25' => 0.51], 'horizontal' => 0.86],
    ],
    // Zone 5 — H2b
    5 => [
         1 => ['sud' => ['sup75' => 1.00, 'pente' => 1.67, 'inf25' => 0.75], 'ouest' => ['sup75' => 0.41, 'pente' => 0.87, 'inf25' => 0.61], 'nord' => ['sup75' => 0.26, 'pente' => 0.55, 'inf25' => 0.47], 'est' => ['sup75' => 0.39, 'pente' => 0.83, 'inf25' => 0.60], 'horizontal' => 0.59],
         2 => ['sud' => ['sup75' => 1.00, 'pente' => 1.82, 'inf25' => 0.93], 'ouest' => ['sup75' => 0.49, 'pente' => 1.06, 'inf25' => 0.79], 'nord' => ['sup75' => 0.33, 'pente' => 0.67, 'inf25' => 0.65], 'est' => ['sup75' => 0.53, 'pente' => 1.12, 'inf25' => 0.80], 'horizontal' => 0.78],
         3 => ['sud' => ['sup75' => 1.00, 'pente' => 2.03, 'inf25' => 1.15], 'ouest' => ['sup75' => 0.60, 'pente' => 1.35, 'inf25' => 1.01], 'nord' => ['sup75' => 0.34, 'pente' => 0.73, 'inf25' => 0.86], 'est' => ['sup75' => 0.60, 'pente' => 1.33, 'inf25' => 1.01], 'horizontal' => 1.00],
         4 => ['sud' => ['sup75' => 1.00, 'pente' => 2.49, 'inf25' => 1.64], 'ouest' => ['sup75' => 0.90, 'pente' => 2.02, 'inf25' => 1.54], 'nord' => ['sup75' => 0.47, 'pente' => 1.13, 'inf25' => 1.39], 'est' => ['sup75' => 0.82, 'pente' => 1.90, 'inf25' => 1.51], 'horizontal' => 1.51],
         5 => ['sud' => ['sup75' => 1.00, 'pente' => 2.95, 'inf25' => 2.16], 'ouest' => ['sup75' => 1.21, 'pente' => 2.81, 'inf25' => 2.11], 'nord' => ['sup75' => 0.63, 'pente' => 1.74, 'inf25' => 1.98], 'est' => ['sup75' => 1.00, 'pente' => 2.42, 'inf25' => 2.03], 'horizontal' => 2.06],
         6 => ['sud' => ['sup75' => 1.00, 'pente' => 3.23, 'inf25' => 2.47], 'ouest' => ['sup75' => 1.21, 'pente' => 2.98, 'inf25' => 2.40], 'nord' => ['sup75' => 0.71, 'pente' => 2.12, 'inf25' => 2.33], 'est' => ['sup75' => 1.19, 'pente' => 2.92, 'inf25' => 2.39], 'horizontal' => 2.39],
         7 => ['sud' => ['sup75' => 1.00, 'pente' => 3.13, 'inf25' => 2.36], 'ouest' => ['sup75' => 1.21, 'pente' => 2.89, 'inf25' => 2.29], 'nord' => ['sup75' => 0.68, 'pente' => 1.99, 'inf25' => 2.21], 'est' => ['sup75' => 1.18, 'pente' => 2.82, 'inf25' => 2.28], 'horizontal' => 2.28],
         8 => ['sud' => ['sup75' => 1.00, 'pente' => 2.65, 'inf25' => 1.82], 'ouest' => ['sup75' => 0.95, 'pente' => 2.22, 'inf25' => 1.72], 'nord' => ['sup75' => 0.52, 'pente' => 1.31, 'inf25' => 1.60], 'est' => ['sup75' => 0.91, 'pente' => 2.14, 'inf25' => 1.71], 'horizontal' => 1.70],
         9 => ['sud' => ['sup75' => 1.00, 'pente' => 2.19, 'inf25' => 1.29], 'ouest' => ['sup75' => 0.67, 'pente' => 1.52, 'inf25' => 1.16], 'nord' => ['sup75' => 0.32, 'pente' => 0.72, 'inf25' => 0.99], 'est' => ['sup75' => 0.62, 'pente' => 1.44, 'inf25' => 1.14], 'horizontal' => 1.14],
        10 => ['sud' => ['sup75' => 1.00, 'pente' => 1.93, 'inf25' => 1.08], 'ouest' => ['sup75' => 0.59, 'pente' => 1.31, 'inf25' => 0.96], 'nord' => ['sup75' => 0.39, 'pente' => 0.83, 'inf25' => 0.82], 'est' => ['sup75' => 0.55, 'pente' => 1.24, 'inf25' => 0.95], 'horizontal' => 0.95],
        11 => ['sud' => ['sup75' => 1.00, 'pente' => 1.74, 'inf25' => 0.86], 'ouest' => ['sup75' => 0.48, 'pente' => 1.03, 'inf25' => 0.73], 'nord' => ['sup75' => 0.33, 'pente' => 0.70, 'inf25' => 0.60], 'est' => ['sup75' => 0.47, 'pente' => 1.00, 'inf25' => 0.72], 'horizontal' => 0.72],
        12 => ['sud' => ['sup75' => 1.00, 'pente' => 1.72, 'inf25' => 0.84], 'ouest' => ['sup75' => 0.46, 'pente' => 0.99, 'inf25' => 0.72], 'nord' => ['sup75' => 0.36, 'pente' => 0.77, 'inf25' => 0.61], 'est' => ['sup75' => 0.47, 'pente' => 1.00, 'inf25' => 0.72], 'horizontal' => 0.71],
    ],
    // Zone 6 — H2c
    6 => [
         1 => ['sud' => ['sup75' => 1.00, 'pente' => 1.71, 'inf25' => 0.80], 'ouest' => ['sup75' => 0.40, 'pente' => 0.89, 'inf25' => 0.65], 'nord' => ['sup75' => 0.28, 'pente' => 0.57, 'inf25' => 0.52], 'est' => ['sup75' => 0.40, 'pente' => 0.87, 'inf25' => 0.65], 'horizontal' => 0.64],
         2 => ['sud' => ['sup75' => 1.00, 'pente' => 1.84, 'inf25' => 0.96], 'ouest' => ['sup75' => 0.49, 'pente' => 1.11, 'inf25' => 0.83], 'nord' => ['sup75' => 0.33, 'pente' => 0.68, 'inf25' => 0.68], 'est' => ['sup75' => 0.51, 'pente' => 1.11, 'inf25' => 0.83], 'horizontal' => 0.82],
         3 => ['sud' => ['sup75' => 1.00, 'pente' => 2.13, 'inf25' => 1.26], 'ouest' => ['sup75' => 0.64, 'pente' => 1.46, 'inf25' => 1.13], 'nord' => ['sup75' => 0.37, 'pente' => 0.80, 'inf25' => 0.98], 'est' => ['sup75' => 0.64, 'pente' => 1.46, 'inf25' => 1.13], 'horizontal' => 1.12],
         4 => ['sud' => ['sup75' => 1.00, 'pente' => 2.54, 'inf25' => 1.70], 'ouest' => ['sup75' => 0.84, 'pente' => 1.98, 'inf25' => 1.59], 'nord' => ['sup75' => 0.51, 'pente' => 1.24, 'inf25' => 1.48], 'est' => ['sup75' => 0.90, 'pente' => 2.09, 'inf25' => 1.61], 'horizontal' => 1.59],
         5 => ['sud' => ['sup75' => 1.00, 'pente' => 2.99, 'inf25' => 2.23], 'ouest' => ['sup75' => 1.21, 'pente' => 2.84, 'inf25' => 2.18], 'nord' => ['sup75' => 0.69, 'pente' => 1.92, 'inf25' => 2.08], 'est' => ['sup75' => 1.06, 'pente' => 2.58, 'inf25' => 2.13], 'horizontal' => 2.14],
         6 => ['sud' => ['sup75' => 1.00, 'pente' => 3.24, 'inf25' => 2.54], 'ouest' => ['sup75' => 1.27, 'pente' => 3.07, 'inf25' => 2.48], 'nord' => ['sup75' => 0.79, 'pente' => 2.37, 'inf25' => 2.43], 'est' => ['sup75' => 1.32, 'pente' => 3.15, 'inf25' => 2.50], 'horizontal' => 2.48],
         7 => ['sud' => ['sup75' => 1.00, 'pente' => 3.17, 'inf25' => 2.43], 'ouest' => ['sup75' => 1.23, 'pente' => 2.96, 'inf25' => 2.37], 'nord' => ['sup75' => 0.74, 'pente' => 2.16, 'inf25' => 2.30], 'est' => ['sup75' => 1.20, 'pente' => 2.92, 'inf25' => 2.36], 'horizontal' => 2.35],
         8 => ['sud' => ['sup75' => 1.00, 'pente' => 2.74, 'inf25' => 1.92], 'ouest' => ['sup75' => 0.99, 'pente' => 2.32, 'inf25' => 1.83], 'nord' => ['sup75' => 0.57, 'pente' => 1.47, 'inf25' => 1.72], 'est' => ['sup75' => 0.96, 'pente' => 2.28, 'inf25' => 1.82], 'horizontal' => 1.81],
         9 => ['sud' => ['sup75' => 1.00, 'pente' => 2.24, 'inf25' => 1.35], 'ouest' => ['sup75' => 0.63, 'pente' => 1.47, 'inf25' => 1.19], 'nord' => ['sup75' => 0.36, 'pente' => 0.82, 'inf25' => 1.06], 'est' => ['sup75' => 0.75, 'pente' => 1.67, 'inf25' => 1.24], 'horizontal' => 1.21],
        10 => ['sud' => ['sup75' => 1.00, 'pente' => 1.93, 'inf25' => 1.06], 'ouest' => ['sup75' => 0.60, 'pente' => 1.31, 'inf25' => 0.94], 'nord' => ['sup75' => 0.33, 'pente' => 0.69, 'inf25' => 0.77], 'est' => ['sup75' => 0.48, 'pente' => 1.12, 'inf25' => 0.90], 'horizontal' => 0.91],
        11 => ['sud' => ['sup75' => 1.00, 'pente' => 1.79, 'inf25' => 0.92], 'ouest' => ['sup75' => 0.54, 'pente' => 1.17, 'inf25' => 0.81], 'nord' => ['sup75' => 0.35, 'pente' => 0.74, 'inf25' => 0.66], 'est' => ['sup75' => 0.42, 'pente' => 0.96, 'inf25' => 0.77], 'horizontal' => 0.78],
        12 => ['sud' => ['sup75' => 1.00, 'pente' => 1.71, 'inf25' => 0.81], 'ouest' => ['sup75' => 0.42, 'pente' => 0.93, 'inf25' => 0.68], 'nord' => ['sup75' => 0.31, 'pente' => 0.67, 'inf25' => 0.56], 'est' => ['sup75' => 0.42, 'pente' => 0.92, 'inf25' => 0.68], 'horizontal' => 0.67],
    ],
    // Zone 7 — H2d
    7 => [
         1 => ['sud' => ['sup75' => 1.00, 'pente' => 1.64, 'inf25' => 0.70], 'ouest' => ['sup75' => 0.39, 'pente' => 0.81, 'inf25' => 0.55], 'nord' => ['sup75' => 0.20, 'pente' => 0.40, 'inf25' => 0.39], 'est' => ['sup75' => 0.33, 'pente' => 0.71, 'inf25' => 0.53], 'horizontal' => 0.53],
         2 => ['sud' => ['sup75' => 1.00, 'pente' => 1.77, 'inf25' => 0.85], 'ouest' => ['sup75' => 0.46, 'pente' => 0.99, 'inf25' => 0.70], 'nord' => ['sup75' => 0.23, 'pente' => 0.45, 'inf25' => 0.53], 'est' => ['sup75' => 0.41, 'pente' => 0.90, 'inf25' => 0.68], 'horizontal' => 0.68],
         3 => ['sud' => ['sup75' => 1.00, 'pente' => 2.09, 'inf25' => 1.18], 'ouest' => ['sup75' => 0.64, 'pente' => 1.43, 'inf25' => 1.05], 'nord' => ['sup75' => 0.30, 'pente' => 0.64, 'inf25' => 0.87], 'est' => ['sup75' => 0.55, 'pente' => 1.28, 'inf25' => 1.02], 'horizontal' => 1.03],
         4 => ['sud' => ['sup75' => 1.00, 'pente' => 2.56, 'inf25' => 1.66], 'ouest' => ['sup75' => 0.91, 'pente' => 2.08, 'inf25' => 1.56], 'nord' => ['sup75' => 0.40, 'pente' => 1.00, 'inf25' => 1.39], 'est' => ['sup75' => 0.73, 'pente' => 1.80, 'inf25' => 1.50], 'horizontal' => 1.53],
         5 => ['sud' => ['sup75' => 1.00, 'pente' => 3.08, 'inf25' => 2.27], 'ouest' => ['sup75' => 1.24, 'pente' => 2.90, 'inf25' => 2.23], 'nord' => ['sup75' => 0.64, 'pente' => 1.82, 'inf25' => 2.10], 'est' => ['sup75' => 1.02, 'pente' => 2.56, 'inf25' => 2.15], 'horizontal' => 2.18],
         6 => ['sud' => ['sup75' => 1.00, 'pente' => 3.58, 'inf25' => 2.84], 'ouest' => ['sup75' => 1.54, 'pente' => 3.62, 'inf25' => 2.81], 'nord' => ['sup75' => 0.74, 'pente' => 2.45, 'inf25' => 2.70], 'est' => ['sup75' => 1.33, 'pente' => 3.29, 'inf25' => 2.74], 'horizontal' => 2.76],
         7 => ['sud' => ['sup75' => 1.00, 'pente' => 3.53, 'inf25' => 2.73], 'ouest' => ['sup75' => 1.34, 'pente' => 3.24, 'inf25' => 2.64], 'nord' => ['sup75' => 0.65, 'pente' => 2.16, 'inf25' => 2.55], 'est' => ['sup75' => 1.40, 'pente' => 3.35, 'inf25' => 2.66], 'horizontal' => 2.64],
         8 => ['sud' => ['sup75' => 1.00, 'pente' => 2.90, 'inf25' => 2.03], 'ouest' => ['sup75' => 1.08, 'pente' => 2.50, 'inf25' => 1.93], 'nord' => ['sup75' => 0.48, 'pente' => 1.33, 'inf25' => 1.78], 'est' => ['sup75' => 0.94, 'pente' => 2.29, 'inf25' => 1.89], 'horizontal' => 1.90],
         9 => ['sud' => ['sup75' => 1.00, 'pente' => 2.29, 'inf25' => 1.39], 'ouest' => ['sup75' => 0.76, 'pente' => 1.70, 'inf25' => 1.27], 'nord' => ['sup75' => 0.34, 'pente' => 0.78, 'inf25' => 1.09], 'est' => ['sup75' => 0.63, 'pente' => 1.51, 'inf25' => 1.23], 'horizontal' => 1.25],
        10 => ['sud' => ['sup75' => 1.00, 'pente' => 1.91, 'inf25' => 1.01], 'ouest' => ['sup75' => 0.62, 'pente' => 1.31, 'inf25' => 0.89], 'nord' => ['sup75' => 0.28, 'pente' => 0.58, 'inf25' => 0.70], 'est' => ['sup75' => 0.42, 'pente' => 0.99, 'inf25' => 0.83], 'horizontal' => 0.85],
        11 => ['sud' => ['sup75' => 1.00, 'pente' => 1.66, 'inf25' => 0.72], 'ouest' => ['sup75' => 0.36, 'pente' => 0.77, 'inf25' => 0.56], 'nord' => ['sup75' => 0.20, 'pente' => 0.40, 'inf25' => 0.41], 'est' => ['sup75' => 0.38, 'pente' => 0.80, 'inf25' => 0.56], 'horizontal' => 0.55],
        12 => ['sud' => ['sup75' => 1.00, 'pente' => 1.59, 'inf25' => 0.64], 'ouest' => ['sup75' => 0.40, 'pente' => 0.79, 'inf25' => 0.50], 'nord' => ['sup75' => 0.18, 'pente' => 0.36, 'inf25' => 0.34], 'est' => ['sup75' => 0.26, 'pente' => 0.59, 'inf25' => 0.46], 'horizontal' => 0.47],
    ],
    // Zone 8 — H3
    8 => [
         1 => ['sud' => ['sup75' => 1.00, 'pente' => 1.63, 'inf25' => 0.67], 'ouest' => ['sup75' => 0.42, 'pente' => 0.83, 'inf25' => 0.53], 'nord' => ['sup75' => 0.17, 'pente' => 0.34, 'inf25' => 0.35], 'est' => ['sup75' => 0.28, 'pente' => 0.63, 'inf25' => 0.49], 'horizontal' => 0.50],
         2 => ['sud' => ['sup75' => 1.00, 'pente' => 1.77, 'inf25' => 0.83], 'ouest' => ['sup75' => 0.48, 'pente' => 1.02, 'inf25' => 0.69], 'nord' => ['sup75' => 0.21, 'pente' => 0.40, 'inf25' => 0.51], 'est' => ['sup75' => 0.35, 'pente' => 0.80, 'inf25' => 0.65], 'horizontal' => 0.66],
         3 => ['sud' => ['sup75' => 1.00, 'pente' => 2.08, 'inf25' => 1.17], 'ouest' => ['sup75' => 0.71, 'pente' => 1.53, 'inf25' => 1.07], 'nord' => ['sup75' => 0.29, 'pente' => 0.63, 'inf25' => 0.86], 'est' => ['sup75' => 0.49, 'pente' => 1.17, 'inf25' => 0.99], 'horizontal' => 1.02],
         4 => ['sud' => ['sup75' => 1.00, 'pente' => 2.60, 'inf25' => 1.72], 'ouest' => ['sup75' => 0.85, 'pente' => 1.98, 'inf25' => 1.58], 'nord' => ['sup75' => 0.43, 'pente' => 1.10, 'inf25' => 1.46], 'est' => ['sup75' => 0.93, 'pente' => 2.13, 'inf25' => 1.62], 'horizontal' => 1.59],
         5 => ['sud' => ['sup75' => 1.00, 'pente' => 3.20, 'inf25' => 2.38], 'ouest' => ['sup75' => 1.30, 'pente' => 3.04, 'inf25' => 2.34], 'nord' => ['sup75' => 0.62, 'pente' => 1.85, 'inf25' => 2.19], 'est' => ['sup75' => 1.03, 'pente' => 2.62, 'inf25' => 2.24], 'horizontal' => 2.28],
         6 => ['sud' => ['sup75' => 1.00, 'pente' => 3.55, 'inf25' => 2.80], 'ouest' => ['sup75' => 1.54, 'pente' => 3.65, 'inf25' => 2.80], 'nord' => ['sup75' => 0.74, 'pente' => 2.41, 'inf25' => 2.65], 'est' => ['sup75' => 1.17, 'pente' => 3.04, 'inf25' => 2.66], 'horizontal' => 2.72],
         7 => ['sud' => ['sup75' => 1.00, 'pente' => 3.45, 'inf25' => 2.66], 'ouest' => ['sup75' => 1.40, 'pente' => 3.34, 'inf25' => 2.62], 'nord' => ['sup75' => 0.66, 'pente' => 2.14, 'inf25' => 2.49], 'est' => ['sup75' => 1.17, 'pente' => 2.99, 'inf25' => 2.54], 'horizontal' => 2.57],
         8 => ['sud' => ['sup75' => 1.00, 'pente' => 2.89, 'inf25' => 2.01], 'ouest' => ['sup75' => 1.11, 'pente' => 2.60, 'inf25' => 1.96], 'nord' => ['sup75' => 0.49, 'pente' => 1.35, 'inf25' => 1.77], 'est' => ['sup75' => 0.81, 'pente' => 2.08, 'inf25' => 1.84], 'horizontal' => 1.89],
         9 => ['sud' => ['sup75' => 1.00, 'pente' => 2.29, 'inf25' => 1.41], 'ouest' => ['sup75' => 0.83, 'pente' => 1.84, 'inf25' => 1.32], 'nord' => ['sup75' => 0.37, 'pente' => 0.86, 'inf25' => 1.13], 'est' => ['sup75' => 0.58, 'pente' => 1.44, 'inf25' => 1.24], 'horizontal' => 1.27],
        10 => ['sud' => ['sup75' => 1.00, 'pente' => 1.90, 'inf25' => 0.99], 'ouest' => ['sup75' => 0.65, 'pente' => 1.36, 'inf25' => 0.89], 'nord' => ['sup75' => 0.27, 'pente' => 0.54, 'inf25' => 0.68], 'est' => ['sup75' => 0.37, 'pente' => 0.90, 'inf25' => 0.79], 'horizontal' => 0.83],
        11 => ['sud' => ['sup75' => 1.00, 'pente' => 1.71, 'inf25' => 0.78], 'ouest' => ['sup75' => 0.38, 'pente' => 0.83, 'inf25' => 0.62], 'nord' => ['sup75' => 0.24, 'pente' => 0.48, 'inf25' => 0.48], 'est' => ['sup75' => 0.43, 'pente' => 0.91, 'inf25' => 0.64], 'horizontal' => 0.62],
        12 => ['sud' => ['sup75' => 1.00, 'pente' => 1.59, 'inf25' => 0.63], 'ouest' => ['sup75' => 0.41, 'pente' => 0.81, 'inf25' => 0.50], 'nord' => ['sup75' => 0.17, 'pente' => 0.34, 'inf25' => 0.33], 'est' => ['sup75' => 0.25, 'pente' => 0.56, 'inf25' => 0.45], 'horizontal' => 0.46],
    ],
];
