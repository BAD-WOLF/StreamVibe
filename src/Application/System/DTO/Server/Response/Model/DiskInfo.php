<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Server\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Um sistema de arquivos montado no contêiner (`data.disks` de
 * GET /system/server):
 *
 *     {"mount_point": "/app/src", "filesystem": "9p",
 *      "total_bytes": 510770802688, "free_bytes": 120394473472}
 *
 * filesystem é o tipo da montagem (/proc/self/mountinfo): "overlay" é o
 * disco do próprio contêiner; uma pasta do Windows montada pelo WSL costuma
 * aparecer como "9p" (bem mais lenta). As montagens virtuais (/proc, /sys,
 * /dev, /run) e as de um arquivo só (/etc/hosts...) ficam de fora.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class DiskInfo {
    /**
     * @param string $mountPoint
     * @param string $filesystem
     * @param int    $totalBytes
     * @param int    $freeBytes
     */
    public function __construct(
        #[SerializedName('mount_point')]
        public private(set) string $mountPoint {
            /**
             * @return string
             */ get => $this->mountPoint;
        },
        public private(set) string $filesystem {
            /**
             * @return string
             */ get => $this->filesystem;
        },
        #[SerializedName('total_bytes')]
        public private(set) int $totalBytes {
            /**
             * @return int
             */ get => $this->totalBytes;
        },
        #[SerializedName('free_bytes')]
        public private(set) int $freeBytes {
            /**
             * @return int
             */ get => $this->freeBytes;
        },
    ) {
    }
}
