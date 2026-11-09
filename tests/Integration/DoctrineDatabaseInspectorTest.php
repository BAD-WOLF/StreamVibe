<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Infrastructure\Persistence\DoctrineDatabaseInspector;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * DoctrineDatabaseInspector contra o banco dos testes (PostgreSQL): a
 * latência, a versão, o tamanho, as conexões e as migrations da pasta
 * migrations/ (o entrypoint do stack de teste roda todas ao subir); e um
 * banco que não responde vira reachable false, sem exceção e sem a senha
 * na mensagem.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV). O mesmo
 * roteiro foi conferido num PostgreSQL 16 com a tabela de migrations
 * vazia, com uma e com todas.
 */
final class DoctrineDatabaseInspectorTest extends KernelTestCase
{
    private DependencyFactory $migrations;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        // o mesmo serviço que o config/services.yaml entrega ao inspector
        $this->migrations = static::getContainer()->get('doctrine.migrations.dependency_factory');
    }

    public function testTheDatabaseOfTheTests(): void
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = static::getContainer()->get('doctrine')->getConnection();
        $this->assertInstanceOf(Connection::class, $connection);

        $status = new DoctrineDatabaseInspector($connection, $this->migrations)->inspect();

        $this->assertTrue($status->reachable);
        $this->assertNull($status->error);
        $this->assertIsFloat($status->latencyMs);
        $this->assertGreaterThanOrEqual(0.0, $status->latencyMs);
        $this->assertMatchesRegularExpression('/^\d+(\.\d+)?/', (string) $status->version);
        $this->assertGreaterThan(0, $status->sizeBytes);
        $this->assertGreaterThanOrEqual(1, $status->connections);

        $migrations = $status->migrations;
        $this->assertNotNull($migrations);
        $files = glob(static::getContainer()->getParameter('kernel.project_dir') . '/migrations/Version*.php');
        $this->assertSame(count($files ?: []), $migrations->executed + $migrations->pending);
        $this->assertSame($migrations->executed === 0, $migrations->current === null);

        if ($migrations->current !== null) {
            $this->assertStringStartsWith('DoctrineMigrations\\Version', $migrations->current);
        }
    }

    public function testADatabaseThatDoesNotAnswer(): void
    {
        $down = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'host' => '127.0.0.1',
            'port' => 1,
            'user' => 'app',
            'password' => 'never-shown-9',
            'dbname' => 'app',
            'serverVersion' => '16',
        ]);
        $status = new DoctrineDatabaseInspector($down, $this->migrations)->inspect();

        $this->assertFalse($status->reachable);
        $this->assertNotEmpty($status->error);
        $this->assertStringNotContainsString('never-shown-9', (string) $status->error);
        $this->assertSame([null, null, null, null, null], [$status->latencyMs, $status->version, $status->sizeBytes, $status->connections, $status->migrations]);
    }
}
