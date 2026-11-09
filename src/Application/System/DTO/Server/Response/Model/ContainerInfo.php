<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Server\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * O que o cgroup da aplicação — o contêiner, no Docker — pode usar e está
 * usando (`data.container` de GET /system/server):
 *
 *     {"cgroup_version": 2, "memory_limit_bytes": 1073741824,
 *      "memory_usage_bytes": 268435456, "cpu_limit": 1.5,
 *      "processes_limit": null, "processes": 12}
 *
 * - memory_limit_bytes, cpu_limit (em CPUs) e processes_limit: null = sem
 *   limite (o contêiner usa o que a máquina tiver);
 * - memory_usage_bytes inclui o cache de arquivos do cgroup;
 * - um valor que o cgroup não informa também vem null.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ContainerInfo {
    /**
     * @param int        $cgroupVersion
     * @param int|null   $memoryLimitBytes
     * @param int|null   $memoryUsageBytes
     * @param float|null $cpuLimit
     * @param int|null   $processesLimit
     * @param int|null   $processes
     */
    public function __construct(
        #[SerializedName('cgroup_version')]
        public private(set) int $cgroupVersion {
            /**
             * @return int
             */ get => $this->cgroupVersion;
        },
        #[SerializedName('memory_limit_bytes')]
        public private(set) ?int $memoryLimitBytes {
            /**
             * @return int|null
             */ get => $this->memoryLimitBytes;
        },
        #[SerializedName('memory_usage_bytes')]
        public private(set) ?int $memoryUsageBytes {
            /**
             * @return int|null
             */ get => $this->memoryUsageBytes;
        },
        #[SerializedName('cpu_limit')]
        public private(set) ?float $cpuLimit {
            /**
             * @return float|null
             */ get => $this->cpuLimit;
        },
        #[SerializedName('processes_limit')]
        public private(set) ?int $processesLimit {
            /**
             * @return int|null
             */ get => $this->processesLimit;
        },
        public private(set) ?int $processes {
            /**
             * @return int|null
             */ get => $this->processes;
        },
    ) {
    }
}
