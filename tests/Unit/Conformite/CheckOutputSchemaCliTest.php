<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CheckOutputSchemaCliTest extends TestCase
{
    private function runCommand(array $arguments): array
    {
        $root = dirname(__DIR__, 3);
        $process = proc_open([PHP_BINARY, $root . '/bin/check-output-schema', ...$arguments], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return ['code' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }

    public function testAide(): void
    {
        $result = $this->runCommand(['--help']);
        self::assertSame(0, $result['code']);
        self::assertStringContainsString('Ce n', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    public static function argumentsInvalides(): iterable
    {
        foreach (['0', '-1', 'abc'] as $limit) { yield 'limite ' . $limit => [['--limit=' . $limit]]; }
        foreach (['--reference', '--corpus=jeu', '--filter=cas'] as $option) { yield $option => [['--input=quelconque.xml', $option]]; }
        yield 'aucun cas' => [['--filter=aucun-cas-calculdpe-schema-test']];
    }

    #[DataProvider('argumentsInvalides')]
    public function testArgumentsRefuses(array $arguments): void
    {
        $result = $this->runCommand($arguments);
        self::assertSame(2, $result['code']);
        self::assertSame('', $result['stdout']);
        self::assertNotSame('', $result['stderr']);
    }

    public static function fichiers(): iterable
    {
        yield 'valide' => ['3', '2.6', false, 0, 0, 0];
        yield 'invalide' => ['0', '2.6', false, 1, 1, 0];
        yield 'version inconnue' => ['3', '2.8', false, 2, 0, 1];
        yield 'XML mal forme' => ['3', '2.6', true, 2, 0, 1];
    }

    #[DataProvider('fichiers')]
    public function testUnFichierDejaCalculeEstAuditeSansEtreReecrit(string $eer, string $version, bool $malformed, int $code, int $invalid, int $crashed): void
    {
        $file = tempnam(sys_get_temp_dir(), 'calculdpe-schema-cli-');
        $xml = '<dpe><administratif><enum_version_id>' . $version . '</enum_version_id></administratif><logement><climatisation_collection><climatisation><donnee_intermediaire><eer>' . $eer . '</eer><besoin_fr>0</besoin_fr><conso_fr>0</conso_fr><conso_fr_depensier>0</conso_fr_depensier></donnee_intermediaire></climatisation></climatisation_collection></logement></dpe>';
        file_put_contents($file, $malformed ? '<incomplet' : $xml);
        $hash = hash_file('sha256', $file);
        try {
            $result = $this->runCommand(['--input=' . $file]);
            self::assertSame($code, $result['code'], $result['stdout'] . $result['stderr']);
            self::assertSame('', $result['stderr']);
            $data = json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('existing_xml', $data['kind']);
            self::assertSame(1, $data['summary']['cases']);
            self::assertSame($invalid, $data['summary']['invalid_blocks']);
            self::assertSame($crashed, $data['summary']['crashed']);
            self::assertSame($hash, hash_file('sha256', $file));
        } finally {
            unlink($file);
        }
    }

    public function testUnFichierAbsentNeDonnePasUnFauxVert(): void
    {
        $result = $this->runCommand(['--input=/introuvable/calculdpe.xml']);
        self::assertSame(2, $result['code']);
        $data = json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $data['summary']['crashed']);
    }
}
