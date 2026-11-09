<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Shared\Response\Model;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Um arquivo de log (`data.files` de GET /system/logs e `data.file` de
 * GET /system/logs/{file}):
 *
 *     {"name": "prod.log", "size_bytes": 52341, "modified_at": "2026-11-08T14:50:01+00:00"}
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class LogFileSummary {
    /**
     * @param string             $name
     * @param int                $sizeBytes
     * @param \DateTimeImmutable $modifiedAt
     */
    public function __construct(
        public private(set) string $name {
            /**
             * @return string
             */ get => $this->name;
        },
        #[SerializedName('size_bytes')]
        public private(set) int $sizeBytes {
            /**
             * @return int
             */ get => $this->sizeBytes;
        },
        #[SerializedName('modified_at')]
        public private(set) DateTimeImmutable $modifiedAt {
            /**
             * @return \DateTimeImmutable
             */ get => $this->modifiedAt;
        },
    ) {
    }
}
