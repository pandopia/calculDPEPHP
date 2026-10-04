<?php
declare(strict_types=1);
namespace CalculDpePHP\Dto;

use InvalidArgumentException;

/**
 * Données du rapport PDF que le XML ADEME ne transporte pas, à passer à
 * `CalculDpePHP::genererPdf()`.
 *
 * Tout est facultatif : sans une donnée, la zone correspondante du modèle
 * officiel reste vide (photo, logo, signature) ou prend une valeur par défaut
 * (lien du guide pédagogique). Les champs d'identité (propriétaire,
 * diagnostiqueur) remplacent ceux du XML lorsqu'ils sont renseignés.
 *
 * Les images sont du contenu binaire JPEG ou PNG ; `DonneesRapportPdf::image()`
 * lit un fichier.
 */
final readonly class DonneesRapportPdf
{
    public const URL_GUIDE_PEDAGOGIQUE = 'https://www.ecologie.gouv.fr/diagnostic-performance-energetique-dpe';

    public function __construct(
        /** Photo du bien (page 1), recadrée pour remplir le cadre. */
        public ?string $photo = null,
        /** Logo de l'entreprise de diagnostic (page 1). */
        public ?string $logo = null,
        /** Signature du diagnostiqueur (page 1). */
        public ?string $signature = null,
        /**
         * Numéro ADEME du DPE (13 caractères, ex. 2674E1068548B). Absent du XML
         * tant que le DPE n'est pas déposé ; c'est lui que porte le QR code.
         * Prioritaire sur `<numero_dpe>`.
         */
        public ?string $numeroDpe = null,
        /** Nom du propriétaire (page 1) ; à défaut, `nom_proprietaire` du XML. */
        public ?string $nomProprietaire = null,
        /** Adresse du propriétaire (page 1) ; à défaut, `adresse_proprietaire` du XML. */
        public ?string $adresseProprietaire = null,
        /** Identité et certification du diagnostiqueur, en complément du XML. */
        public DiagnostiqueurRapport $diagnostiqueur = new DiagnostiqueurRapport(),
        /** Explications personnalisées sur les écarts entre consommations estimées et réelles (annexe). */
        public ?string $explicationsPersonnalisees = null,
        /** Commentaires du diagnostiqueur sous les recommandations (p.5) ; à défaut, `commentaire_travaux`. */
        public ?string $commentaires = null,
        /** Lien « Pour en savoir plus » de l'en-tête (emplacement <url_gouv_guide_pédagogique> du modèle). */
        public string $urlGuidePedagogique = self::URL_GUIDE_PEDAGOGIQUE,
    ) {
        foreach (['photo' => $photo, 'logo' => $logo, 'signature' => $signature] as $champ => $image) {
            if ($image !== null && !self::estImageSupportee($image)) {
                throw new InvalidArgumentException("$champ : image JPEG ou PNG attendue.");
            }
        }
        if ($numeroDpe !== null && preg_match('/^[0-9A-Z]{13}$/', $numeroDpe) !== 1) {
            throw new InvalidArgumentException("Numéro de DPE invalide : « $numeroDpe » (13 caractères attendus, ex. 2674E1068548B).");
        }
        if (preg_match('#^https?://#', $urlGuidePedagogique) !== 1) {
            throw new InvalidArgumentException("URL du guide pédagogique invalide : « $urlGuidePedagogique ».");
        }
    }

    /** Contenu d'un fichier image, pour les champs photo, logo et signature. */
    public static function image(string $chemin): string
    {
        $contenu = @file_get_contents($chemin);
        if ($contenu === false) {
            throw new InvalidArgumentException("Image illisible : $chemin");
        }

        return $contenu;
    }

    private static function estImageSupportee(string $contenu): bool
    {
        $info = @getimagesizefromstring($contenu);

        return $info !== false && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true);
    }
}
