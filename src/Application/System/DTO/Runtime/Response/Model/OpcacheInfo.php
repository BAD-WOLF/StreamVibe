<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Runtime\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * O OPcache (`data.opcache` de GET /system/runtime; null quando a extensão
 * não está instalada):
 *
 *     {"enabled": true, "jit": false, "memory_used_bytes": 40370928,
 *      "memory_free_bytes": 93846784, "memory_wasted_bytes": 0,
 *      "cached_scripts": 3012, "hits": 581230, "misses": 3012}
 *
 * - enabled false (opcache.enable=0, ou opcache.enable_cli=0 num servidor
 *   que roda pela CLI): os números vêm null e jit, false;
 * - memory_wasted_bytes: memória de scripts que mudaram depois de entrar no
 *   cache; muito desperdício pede reiniciar o PHP;
 * - a taxa de acerto é hits / (hits + misses).
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class OpcacheInfo {
    /**
     * @param bool     $enabled
     * @param bool     $jit
     * @param int|null $memoryUsedBytes
     * @param int|null $memoryFreeBytes
     * @param int|null $memoryWastedBytes
     * @param int|null $cachedScripts
     * @param int|null $hits
     * @param int|null $misses
     */
    public function __construct(
        public private(set) bool $enabled {
            /**
             * @return bool
             */ get => $this->enabled;
        },
        public private(set) bool $jit {
            /**
             * @return bool
             */ get => $this->jit;
        },
        #[SerializedName('memory_used_bytes')]
        public private(set) ?int $memoryUsedBytes {
            /**
             * @return int|null
             */ get => $this->memoryUsedBytes;
        },
        #[SerializedName('memory_free_bytes')]
        public private(set) ?int $memoryFreeBytes {
            /**
             * @return int|null
             */ get => $this->memoryFreeBytes;
        },
        #[SerializedName('memory_wasted_bytes')]
        public private(set) ?int $memoryWastedBytes {
            /**
             * @return int|null
             */ get => $this->memoryWastedBytes;
        },
        #[SerializedName('cached_scripts')]
        public private(set) ?int $cachedScripts {
            /**
             * @return int|null
             */ get => $this->cachedScripts;
        },
        public private(set) ?int $hits {
            /**
             * @return int|null
             */ get => $this->hits;
        },
        public private(set) ?int $misses {
            /**
             * @return int|null
             */ get => $this->misses;
        },
    ) {
    }
}
