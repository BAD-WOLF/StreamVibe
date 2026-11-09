<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Server\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * A memória da máquina (/proc/meminfo), em bytes (`data.host.memory` de
 * GET /system/server):
 *
 *     {"total_bytes": 16624349184, "available_bytes": 9814147072,
 *      "swap_total_bytes": 4294967296, "swap_free_bytes": 4294967296}
 *
 * available_bytes é o que ainda pode ser usado sem recorrer ao swap
 * (MemAvailable); o usado é total_bytes - available_bytes.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class MemoryInfo {
    /**
     * @param int $totalBytes
     * @param int $availableBytes
     * @param int $swapTotalBytes
     * @param int $swapFreeBytes
     */
    public function __construct(
        #[SerializedName('total_bytes')]
        public private(set) int $totalBytes {
            /**
             * @return int
             */ get => $this->totalBytes;
        },
        #[SerializedName('available_bytes')]
        public private(set) int $availableBytes {
            /**
             * @return int
             */ get => $this->availableBytes;
        },
        #[SerializedName('swap_total_bytes')]
        public private(set) int $swapTotalBytes {
            /**
             * @return int
             */ get => $this->swapTotalBytes;
        },
        #[SerializedName('swap_free_bytes')]
        public private(set) int $swapFreeBytes {
            /**
             * @return int
             */ get => $this->swapFreeBytes;
        },
    ) {
    }
}
