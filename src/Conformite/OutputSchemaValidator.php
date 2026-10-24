<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

/**
 * Audit exact des blocs calculés, avec le XSD du modèle déclaré.
 *
 * Complète XsdVocabulary : contrôle les enfants obligatoires, les types,
 * les bornes et xsi:nil, sans dépendre de la présence dans la référence.
 * Ne valide pas les entrées ni l'administratif d'un ancien export. Ne remplace
 * pas une validation du document entier et ne modifie aucun compteur A/B.
 *
 * @spec-source editeur/public/schemas/SOURCE.md (modèle → livraison XSD)
 * @spec-source https://www.w3.org/TR/xmlschema-1/#xsi_nil
 */
final class OutputSchemaValidator
{
    private const XS = 'http://www.w3.org/2001/XMLSchema';

    /** @var array<string, array<string, string>> */
    private array $schemas = [];

    public function __construct(private readonly string $schemaDirectory)
    {
    }

    public static function schemaFilename(string $version): string
    {
        return match ($version) {
            '1', '1.1' => 'DPEv1-obsolete.xsd',
            '2', '2.1', '2.2' => 'DPEv2.xsd',
            '2.3' => 'DPEv2.2.xsd',
            '2.4' => 'DPEv2.3.xsd',
            '2.5' => 'DPEv2.4.xsd',
            '2.6' => 'DPEv2.6.xsd',
            '2.7' => 'DPEv2.7.xsd',
            default => throw new InvalidArgumentException('Version DPE absente ou inconnue : ' . $version),
        };
    }

    /**
     * Chaque bloc présent est contrôlé, même vide ou marqué xsi:nil.
     * Un bloc absent ne peut être audité isolément : ce n'est pas un contrôle
     * de complétude de tout le logement. Aucun zéro ni résultat n'est ajouté.
     *
     * @return array{version: string, schema: string, validated_blocks: int, issues: list<array{path: string, errors: list<string>}>}
     */
    public function validate(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        $version = trim($xpath->evaluate('string(/dpe/administratif/enum_version_id)'));
        $filename = self::schemaFilename($version);
        $schemas = $this->schemas[$filename] ??= $this->buildSchemas($filename);
        $result = ['version' => $version, 'schema' => $filename, 'validated_blocks' => 0, 'issues' => []];

        foreach ($xpath->query('/dpe/logement//*[self::donnee_intermediaire or self::sortie]') as $node) {
            $path = $node->getNodePath();
            $canonical = ltrim((string) preg_replace('/\[\d+\]/', '', $path), '/');
            if (!isset($schemas[$canonical])) {
                $result['issues'][] = ['path' => $path, 'errors' => ['Bloc calculé absent du schéma à ce chemin.']];
                continue;
            }

            $single = new DOMDocument();
            $single->appendChild($single->importNode($node, true));
            $previous = libxml_use_internal_errors(true);
            libxml_clear_errors();
            try {
                $valid = $single->schemaValidateSource($schemas[$canonical]);
                $errors = array_values(array_unique(array_map(
                    static fn(\LibXMLError $error): string => trim($error->message),
                    libxml_get_errors(),
                )));
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $result['validated_blocks']++;
            if (!$valid) {
                $result['issues'][] = ['path' => $path, 'errors' => $errors];
            }
        }

        return $result;
    }

    /** @return array<string, string> chemin réel de déclaration → XSD autonome */
    private function buildSchemas(string $filename): array
    {
        $source = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $loaded = $source->load($this->schemaDirectory . '/' . $filename, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$loaded) {
            throw new RuntimeException('Schéma ADEME illisible : ' . $filename);
        }
        $xpath = new DOMXPath($source);
        $xpath->registerNamespace('xs', self::XS);
        if ($xpath->query('/xs:schema')->length !== 1
            || $xpath->query('/xs:schema/xs:import | /xs:schema/xs:include | /xs:schema/xs:redefine')->length !== 0) {
            throw new RuntimeException('Schéma ADEME non autonome : ' . $filename);
        }
        $schemas = [];
        foreach ($xpath->query('//xs:element[@name="donnee_intermediaire" or @name="sortie"]') as $declaration) {
            $names = [];
            for ($cursor = $declaration; $cursor instanceof DOMElement; $cursor = $cursor->parentNode) {
                if ($cursor->namespaceURI === self::XS && $cursor->localName === 'element') {
                    array_unshift($names, $cursor->getAttribute('name'));
                }
            }
            $path = implode('/', $names);
            if (!str_starts_with($path, 'dpe/logement/')) {
                continue;
            }
            $schema = new DOMDocument();
            $schema->appendChild($schema->createElementNS(self::XS, 'xs:schema'));
            foreach ($xpath->query('/xs:schema/xs:simpleType | /xs:schema/xs:complexType') as $type) {
                $schema->documentElement->appendChild($schema->importNode($type, true));
            }
            $global = $schema->importNode($declaration, true);
            $global->removeAttribute('minOccurs');
            $global->removeAttribute('maxOccurs');
            $schema->documentElement->appendChild($global);
            $schemas[$path] = $schema->saveXML();
        }
        if ($schemas === []) {
            throw new RuntimeException('Schéma sans blocs calculés : ' . $filename);
        }

        return $schemas;
    }
}
