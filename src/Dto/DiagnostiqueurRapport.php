<?php
declare(strict_types=1);
namespace CalculDpePHP\Dto;

/**
 * Identité du diagnostiqueur affichée sur le rapport (page 1 et annexes).
 *
 * Chaque champ renseigné remplace la valeur de `administratif/diagnostiqueur`
 * du XML ; un champ null laisse celle du XML. Utile pour les XML publiés par
 * l'observatoire ADEME, d'où les données personnelles sont retirées.
 */
final readonly class DiagnostiqueurRapport
{
    public function __construct(
        public ?string $entreprise = null,
        /** Adresse postale de l'entreprise, sur une ligne (« 37 rue X 75008 PARIS »). */
        public ?string $adresse = null,
        /** Prénom et nom. */
        public ?string $nom = null,
        public ?string $telephone = null,
        public ?string $email = null,
        public ?string $numeroCertification = null,
        public ?string $organismeCertification = null,
        /** Adresse de l'organisme certificateur (« … certifiées par X, adresse » en annexe). */
        public ?string $adresseOrganismeCertification = null,
    ) {}

    /**
     * Valeurs du XML complétées par celles renseignées ici.
     *
     * @param array<string, string|null> $xml clés de DpeData::diagnostiqueur()
     * @return array<string, string|null>
     */
    public function completer(array $xml): array
    {
        $overrides = [
            'entreprise' => $this->entreprise,
            'adresse' => $this->adresse,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'certification' => $this->numeroCertification,
            'organisme' => $this->organismeCertification,
        ];
        foreach ($overrides as $key => $value) {
            if ($value !== null && trim($value) !== '') {
                $xml[$key] = trim($value);
            }
        }

        return $xml;
    }
}
