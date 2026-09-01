<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

/**
 * Issue de la comparaison d'une valeur calculée avec sa référence.
 */
final class ComparisonStatus
{
    /** Valeurs identiques (au bruit flottant près). */
    public const EXACT = 'exact';

    /** Écart non nul mais dans la tolérance du profil. */
    public const WITHIN = 'within_tolerance';

    /** Écart supérieur à la tolérance. */
    public const OUT = 'out_of_tolerance';

    /** Balise présente dans la référence, absente de notre sortie. */
    public const MISSING = 'missing';

    /** Balise produite par le moteur, absente de la référence. */
    public const EXTRA = 'extra';

    /** Balise non comparable (texte non numérique différent). */
    public const MISMATCH = 'string_mismatch';

    public const ALL = [
        self::EXACT,
        self::WITHIN,
        self::OUT,
        self::MISSING,
        self::EXTRA,
        self::MISMATCH,
    ];

    /** Statuts comptant comme non conformes. */
    public const FAILING = [self::OUT, self::MISSING, self::EXTRA, self::MISMATCH];
}
