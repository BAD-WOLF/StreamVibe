<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Dependencies\Response\Model;

/**
 * As migrations do Doctrine (`data.database.migrations` de
 * GET /system/dependencies):
 *
 *     {"executed": 2, "pending": 0, "current": "DoctrineMigrations\\Version20261108120000"}
 *
 * - pending: as da pasta migrations/ que ainda não rodaram neste banco (o
 *   entrypoint do contêiner roda todas ao subir, então em geral é 0);
 * - current: a última que rodou; null quando nenhuma rodou.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class MigrationStatus {
    /**
     * @param int         $executed
     * @param int         $pending
     * @param string|null $current
     */
    public function __construct(
        public private(set) int $executed {
            /**
             * @return int
             */ get => $this->executed;
        },
        public private(set) int $pending {
            /**
             * @return int
             */ get => $this->pending;
        },
        public private(set) ?string $current {
            /**
             * @return string|null
             */ get => $this->current;
        },
    ) {
    }
}
