<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Dependencies\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * O banco de dados (`data.database` de GET /system/dependencies):
 *
 *     {"reachable": true, "latency_ms": 0.84, "error": null, "version": "16.4",
 *      "size_bytes": 8053091, "connections": 3,
 *      "migrations": {"executed": 2, "pending": 0, "current": "..."}}
 *
 * - latency_ms: quanto um SELECT 1 levou;
 * - reachable false: a conexão falhou; error traz o motivo (com os segredos
 *   trocados por "***") e o resto vem null;
 * - size_bytes e connections (as conexões abertas neste banco): só no
 *   PostgreSQL; em outro banco, null;
 * - migrations: null quando o status das migrations não pôde ser lido.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class DatabaseStatus {
    /**
     * @param bool                                                                         $reachable
     * @param float|null                                                                   $latencyMs
     * @param string|null                                                                  $error
     * @param string|null                                                                  $version
     * @param int|null                                                                     $sizeBytes
     * @param int|null                                                                     $connections
     * @param \App\Application\System\DTO\Dependencies\Response\Model\MigrationStatus|null $migrations
     */
    public function __construct(
        public private(set) bool $reachable {
            /**
             * @return bool
             */ get => $this->reachable;
        },
        #[SerializedName('latency_ms')]
        public private(set) ?float $latencyMs {
            /**
             * @return float|null
             */ get => $this->latencyMs;
        },
        public private(set) ?string $error {
            /**
             * @return string|null
             */ get => $this->error;
        },
        public private(set) ?string $version {
            /**
             * @return string|null
             */ get => $this->version;
        },
        #[SerializedName('size_bytes')]
        public private(set) ?int $sizeBytes {
            /**
             * @return int|null
             */ get => $this->sizeBytes;
        },
        public private(set) ?int $connections {
            /**
             * @return int|null
             */ get => $this->connections;
        },
        public private(set) ?MigrationStatus $migrations {
            /**
             * @return \App\Application\System\DTO\Dependencies\Response\Model\MigrationStatus|null
             */ get => $this->migrations;
        },
    ) {
    }

    /**
     * O mesmo status com outra mensagem de erro (o UseCase troca
     * os segredos da mensagem por "***").
     *
     * @param string|null $error
     *
     * @return self
     */
    public function withError(?string $error): self {
        return new self(
            reachable: $this->reachable,
            latencyMs: $this->latencyMs,
            error: $error,
            version: $this->version,
            sizeBytes: $this->sizeBytes,
            connections: $this->connections,
            migrations: $this->migrations,
        );
    }
}
