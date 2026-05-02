<?php

declare(strict_types=1);

namespace CalculDpePHP\Common;

/**
 * Période réglementaire applicable au DPE.
 *
 * La spec 3CL-2021 distingue deux régimes pour la conversion énergie finale →
 * énergie primaire de l'électricité (entre autres) : avant et après 2026.
 *
 * Les 4 exemples du repo couvrent les deux cas :
 *  - bat_pre2026coefelec_*.xml / zone_pre2026coefelec_*.xml  → PRE_2026
 *  - bat_post2026coefelec_*.xml / zone_post2026coefelec_*.xml → POST_2026
 */
enum Period: string
{
    case PRE_2026 = 'pre_2026';
    case POST_2026 = 'post_2026';
}
