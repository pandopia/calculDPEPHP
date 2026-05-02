<?php

declare(strict_types=1);

namespace CalculDpePHP\Xml;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Helpers de lecture typée du DOM.
 *
 * Normalise les valeurs lues :
 * - virgule → point pour les flottants
 * - reconnaît `xsi:nil="true"` et retourne null
 * - trim systématique
 */
final class NodeAccessor
{
    private DOMXPath $xpath;

    public function __construct(DOMDocument|DOMNode $context)
    {
        $doc = $context instanceof DOMDocument ? $context : $context->ownerDocument;
        if ($doc === null) {
            throw new \RuntimeException('Document propriétaire introuvable.');
        }
        $this->xpath = new DOMXPath($doc);
    }

    /**
     * Retourne la valeur textuelle (ou null) d'un nœud trouvé par xpath.
     */
    public function getStringOrNull(string $expression, ?DOMNode $context = null): ?string
    {
        $nodes = $this->xpath->query($expression, $context);
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $node = $nodes->item(0);
        if ($node instanceof DOMElement && $node->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'nil') === 'true') {
            return null;
        }
        $value = trim($node->textContent ?? '');
        return $value === '' ? null : $value;
    }

    public function getFloatOrNull(string $expression, ?DOMNode $context = null): ?float
    {
        $value = $this->getStringOrNull($expression, $context);
        if ($value === null) return null;
        $value = str_replace(',', '.', $value);
        return is_numeric($value) ? (float)$value : null;
    }

    public function getIntOrNull(string $expression, ?DOMNode $context = null): ?int
    {
        $value = $this->getStringOrNull($expression, $context);
        if ($value === null) return null;
        return is_numeric($value) ? (int)$value : null;
    }

    /**
     * Lit un enum (entier) et le retourne sous forme string.
     * Exemple : `<enum_zone_climatique_id>1</enum_zone_climatique_id>` → "1".
     */
    public function getEnumString(string $expression, ?DOMNode $context = null): ?string
    {
        return $this->getStringOrNull($expression, $context);
    }

    /**
     * Crée (ou retourne) une balise `<donnee_intermediaire>` enfant directe du nœud
     * passé. Si elle existe déjà, elle est retournée telle quelle.
     */
    public function ensureDonneeIntermediaire(DOMElement $parent): DOMElement
    {
        return $this->ensureChild($parent, 'donnee_intermediaire');
    }

    public function ensureSortie(DOMElement $parent): DOMElement
    {
        return $this->ensureChild($parent, 'sortie');
    }

    /**
     * Écrit une valeur scalaire dans un enfant nommé du conteneur. Crée l'enfant
     * s'il n'existe pas, sinon remplace son contenu.
     */
    public function setChildValue(DOMElement $container, string $tag, string|int|float|null $value): void
    {
        $existing = null;
        foreach ($container->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) {
                $existing = $c;
                break;
            }
        }
        if ($existing === null) {
            $existing = $container->ownerDocument->createElement($tag);
            $container->appendChild($existing);
        }
        while ($existing->firstChild !== null) {
            $existing->removeChild($existing->firstChild);
        }
        if ($value !== null) {
            $existing->appendChild($container->ownerDocument->createTextNode((string)$value));
        }
    }

    private function ensureChild(DOMElement $parent, string $tag): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) {
                return $c;
            }
        }
        $el = $parent->ownerDocument->createElement($tag);
        $parent->appendChild($el);
        return $el;
    }
}
