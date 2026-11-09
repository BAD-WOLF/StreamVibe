<?php

declare(strict_types = 1);

namespace App\Infrastructure\Persistence;

use App\Application\System\DTO\Dependencies\Response\Model\DatabaseStatus;
use App\Application\System\DTO\Dependencies\Response\Model\MigrationStatus;
use App\Application\System\Port\DatabaseInspectorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\DependencyFactory;
use Throwable;

/**
 * Implementação da DatabaseInspectorInterface com a conexão do Doctrine (a
 * mesma da aplicação) e o DependencyFactory do DoctrineMigrationsBundle (o
 * mesmo do doctrine:migrations:status).
 *
 * - latência: um SELECT 1 (getDummySelectSQL() da plataforma);
 * - tamanho e conexões abertas: pg_database_size() e pg_stat_activity —
 *   só no PostgreSQL;
 * - migrations: as que rodaram (a tabela doctrine_migration_versions) e as
 *   da pasta migrations/ que ainda não rodaram.
 * Cada parte que falha vira null (a conexão que falha, reachable false com
 * o motivo); nada aqui lança exceção.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class DoctrineDatabaseInspector implements DatabaseInspectorInterface {
    /**
     * @param \Doctrine\DBAL\Connection             $connection
     * @param \Doctrine\Migrations\DependencyFactory $migrations doctrine.migrations.dependency_factory
     */
    public function __construct(
        private Connection $connection,
        private DependencyFactory $migrations,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function inspect(): DatabaseStatus {
        try {
            $platform = $this->connection->getDatabasePlatform();
            $started = hrtime(true);
            $this->connection->fetchOne($platform->getDummySelectSQL());
            $latencyMs = round((hrtime(true) - $started) / 1_000_000, 2);
            $version = $this->connection->getServerVersion();
        } catch (Throwable $exception) {
            return new DatabaseStatus(
                reachable: false,
                latencyMs: null,
                error: $exception->getMessage(),
                version: null,
                sizeBytes: null,
                connections: null,
                migrations: null,
            );
        }

        $postgres = $platform instanceof PostgreSQLPlatform;

        return new DatabaseStatus(
            reachable: true,
            latencyMs: $latencyMs,
            error: null,
            version: $version,
            sizeBytes: $postgres ? $this->integer('SELECT pg_database_size(current_database())') : null,
            connections: $postgres ? $this->integer('SELECT count(*) FROM pg_stat_activity WHERE datname = current_database()') : null,
            migrations: $this->migrations(),
        );
    }

    /**
     * @return \App\Application\System\DTO\Dependencies\Response\Model\MigrationStatus|null
     */
    private function migrations(): ?MigrationStatus {
        try {
            $executed = $this->migrations->getMetadataStorage()->getExecutedMigrations();
            $pending = $this->migrations->getMigrationStatusCalculator()->getNewMigrations();

            return new MigrationStatus(
                executed: count($executed),
                pending: count($pending),
                current: count($executed) === 0 ? null : (string) $executed->getLast()->getVersion(),
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param string $sql uma consulta que devolve um número
     *
     * @return int|null
     */
    private function integer(string $sql): ?int {
        try {
            $value = $this->connection->fetchOne($sql);
        } catch (Throwable) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
