<?php

declare(strict_types=1);

namespace Tests\Unit\Xml;

use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NodeAccessorTest extends TestCase
{
    private const XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    #[DataProvider('concreteValues')]
    public function testEcrireUneValeurSurUneBaliseNulleLaRendLisible(string|int|float $value): void
    {
        $document = new DOMDocument();
        $document->loadXML('<root xmlns:i="' . self::XSI . '"><value i:nil="true" origin="preserved"/></root>');
        $accessor = new NodeAccessor($document);
        $accessor->setChildValue($document->documentElement, 'value', $value);
        $node = $document->getElementsByTagName('value')->item(0);
        self::assertFalse($node->hasAttributeNS(self::XSI, 'nil'));
        self::assertSame('preserved', $node->getAttribute('origin'));
        self::assertSame((string)$value, $accessor->getStringOrNull('/root/value'));
        $first = $document->saveXML();
        $accessor->setChildValue($document->documentElement, 'value', $value);
        self::assertSame($first, $document->saveXML());
    }

    public static function concreteValues(): iterable
    {
        yield 'entier nul' => [0];
        yield 'entier positif' => [12];
        yield 'decimal' => [3.75];
        yield 'texte' => ['A & B'];
    }

    public function testEcrireUnEnfantRetireLeNilDuConteneurMaisConserveLesAutresAttributs(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<root xmlns:xsi="' . self::XSI . '"><donnee_intermediaire xsi:nil="1" marker="keep"/></root>');
        $accessor = new NodeAccessor($document);
        $di = $accessor->ensureDonneeIntermediaire($document->documentElement);
        $accessor->setChildValue($di, 'eer', 3.75);
        self::assertFalse($di->hasAttributeNS(self::XSI, 'nil'));
        self::assertSame('keep', $di->getAttribute('marker'));
        self::assertSame(3.75, $accessor->getFloatOrNull('/root/donnee_intermediaire/eer'));
    }

    public function testUneValeurNulleNeDevientPasUnNombreInvente(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<root xmlns:xsi="' . self::XSI . '"><value xsi:nil="true"/></root>');
        $accessor = new NodeAccessor($document);
        $accessor->setChildValue($document->documentElement, 'value', null);
        self::assertNull($accessor->getFloatOrNull('/root/value'));
        self::assertSame('true', $document->getElementsByTagName('value')->item(0)->getAttributeNS(self::XSI, 'nil'));
        $accessor->setChildValue($document->documentElement, 'other', null);
        self::assertNull($accessor->getFloatOrNull('/root/other'));
        self::assertFalse($document->getElementsByTagName('other')->item(0)->hasAttributeNS(self::XSI, 'nil'));
    }

    public function testLaValeurReecriteRespecteUnElementXsdNillable(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<eer xmlns:xsi="' . self::XSI . '" xsi:nil="true"/>');
        $container = new DOMDocument();
        $container->loadXML('<root/>');
        $container->documentElement->appendChild($container->importNode($document->documentElement, true));
        (new NodeAccessor($container))->setChildValue($container->documentElement, 'eer', 4.25);
        $single = new DOMDocument();
        $single->appendChild($single->importNode($container->getElementsByTagName('eer')->item(0), true));
        self::assertTrue($single->schemaValidateSource('<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"><xs:element name="eer" type="xs:double" nillable="true"/></xs:schema>'));
    }
}
