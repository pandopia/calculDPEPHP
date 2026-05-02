<?php

declare(strict_types=1);

namespace Tests\Smoke;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Engine\CalculatorPipeline;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Smoke test pour CalculatorPipeline.
 *
 * Vérifie le tri topologique et la détection de cycles.
 */
final class PipelineSmokeTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../..';

    private function makeContext(): CalculationContext
    {
        $doc = new DOMDocument();
        $doc->loadXML('<root/>');
        return new CalculationContext(
            document: $doc,
            tables:   new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    public function testTopologicalOrderRespectsDependencies(): void
    {
        // A ← B ← C  (C dépend de B qui dépend de A)
        $order = [];

        $calcA = new class($order) implements CalculatorInterface {
            public function __construct(private array &$order) {}
            public function id(): string { return 'CalcA'; }
            public function dependencies(): array { return []; }
            public function appliesTo(DOMElement $node): bool { return true; }
            public function calculate(DOMElement $node, CalculationContext $ctx): void {
                $this->order[] = 'A';
            }
        };

        $calcB = new class($order) implements CalculatorInterface {
            public function __construct(private array &$order) {}
            public function id(): string { return 'CalcB'; }
            public function dependencies(): array { return ['CalcA']; }
            public function appliesTo(DOMElement $node): bool { return true; }
            public function calculate(DOMElement $node, CalculationContext $ctx): void {
                $this->order[] = 'B';
            }
        };

        $calcC = new class($order) implements CalculatorInterface {
            public function __construct(private array &$order) {}
            public function id(): string { return 'CalcC'; }
            public function dependencies(): array { return ['CalcB']; }
            public function appliesTo(DOMElement $node): bool { return true; }
            public function calculate(DOMElement $node, CalculationContext $ctx): void {
                $this->order[] = 'C';
            }
        };

        $pipeline = new CalculatorPipeline();
        $pipeline->add($calcC);  // Add out of order
        $pipeline->add($calcA);
        $pipeline->add($calcB);

        $doc = new DOMDocument();
        $doc->loadXML('<root/>');
        $ctx = $this->makeContext();
        $pipeline->run($doc, $ctx);

        $this->assertSame(['A', 'B', 'C'], $order, 'Calculators must run in dependency order');
    }

    public function testCycleDetectionThrowsRuntimeException(): void
    {
        // A → B → A  (cycle)
        $calcA = new class implements CalculatorInterface {
            public function id(): string { return 'CycleA'; }
            public function dependencies(): array { return ['CycleB']; }
            public function appliesTo(DOMElement $node): bool { return true; }
            public function calculate(DOMElement $node, CalculationContext $ctx): void {}
        };

        $calcB = new class implements CalculatorInterface {
            public function id(): string { return 'CycleB'; }
            public function dependencies(): array { return ['CycleA']; }
            public function appliesTo(DOMElement $node): bool { return true; }
            public function calculate(DOMElement $node, CalculationContext $ctx): void {}
        };

        $pipeline = new CalculatorPipeline();
        $pipeline->add($calcA);
        $pipeline->add($calcB);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/[Cc]ycle/');

        $doc = new DOMDocument();
        $doc->loadXML('<root/>');
        $pipeline->run($doc, $this->makeContext());
    }

    public function testNoDependencies(): void
    {
        $pipeline = new CalculatorPipeline();
        $ran = [];

        for ($i = 0; $i < 3; $i++) {
            $pipeline->add(new class($ran, $i) implements CalculatorInterface {
                public function __construct(private array &$ran, private int $n) {}
                public function id(): string { return "NoDep{$this->n}"; }
                public function dependencies(): array { return []; }
                public function appliesTo(DOMElement $node): bool { return true; }
                public function calculate(DOMElement $node, CalculationContext $ctx): void {
                    $this->ran[] = $this->n;
                }
            });
        }

        $doc = new DOMDocument();
        $doc->loadXML('<root/>');
        $pipeline->run($doc, $this->makeContext());

        $this->assertCount(3, $ran, 'All 3 calculators should have run');
    }

    public function testUnregisteredDependencyIsTolerated(): void
    {
        // CalcD depends on CalcMissing which is not registered — should not crash
        $ran = false;
        $pipeline = new CalculatorPipeline();
        $pipeline->add(new class($ran) implements CalculatorInterface {
            public function __construct(private bool &$ran) {}
            public function id(): string { return 'CalcD'; }
            public function dependencies(): array { return ['CalcMissing']; }
            public function appliesTo(DOMElement $node): bool { return true; }
            public function calculate(DOMElement $node, CalculationContext $ctx): void {
                $this->ran = true;
            }
        });

        $doc = new DOMDocument();
        $doc->loadXML('<root/>');
        $pipeline->run($doc, $this->makeContext());

        $this->assertTrue($ran, 'Calculator with unregistered dependency should still run');
    }
}
