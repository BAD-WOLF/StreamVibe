<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Server\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * A máquina em que o contêiner roda — no Windows, a VM do WSL —
 * (`data.host` de GET /system/server):
 *
 *     {"hostname": "3f2a9c1b7d4e", "os": "Debian GNU/Linux 12 (bookworm)",
 *      "kernel": "6.6.87.2-microsoft-standard-WSL2", "architecture": "x86_64",
 *      "cpus": 8, "uptime_seconds": 86400, "load_average": [0.52, 0.48, 0.4],
 *      "memory": {"total_bytes": ..., "available_bytes": ..., ...}}
 *
 * Dentro de um contêiner o /proc mostra o kernel, as CPUs, a memória, a
 * carga e o tempo ligado da máquina; o hostname e o sistema (os) são os do
 * contêiner. O que o contêiner pode usar está em `container`.
 *
 * - os: o PRETTY_NAME do /etc/os-release; null sem o arquivo;
 * - load_average: a carga média de 1, 5 e 15 minutos (processos querendo
 *   CPU; acima de `cpus`, há fila);
 * - cpus, uptime_seconds, load_average e memory vêm null quando o /proc não
 *   deixa ler.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class HostInfo {
    /**
     * @param string                                                            $hostname
     * @param string|null                                                       $os
     * @param string                                                            $kernel
     * @param string                                                            $architecture
     * @param int|null                                                          $cpus
     * @param int|null                                                          $uptimeSeconds
     * @param list<float>|null                                                  $loadAverage
     * @param \App\Application\System\DTO\Server\Response\Model\MemoryInfo|null $memory
     */
    public function __construct(
        public private(set) string $hostname {
            /**
             * @return string
             */ get => $this->hostname;
        },
        public private(set) ?string $os {
            /**
             * @return string|null
             */ get => $this->os;
        },
        public private(set) string $kernel {
            /**
             * @return string
             */ get => $this->kernel;
        },
        public private(set) string $architecture {
            /**
             * @return string
             */ get => $this->architecture;
        },
        public private(set) ?int $cpus {
            /**
             * @return int|null
             */ get => $this->cpus;
        },
        #[SerializedName('uptime_seconds')]
        public private(set) ?int $uptimeSeconds {
            /**
             * @return int|null
             */ get => $this->uptimeSeconds;
        },
        #[SerializedName('load_average')]
        public private(set) ?array $loadAverage {
            /**
             * @return list<float>|null
             */ get => $this->loadAverage;
        },
        public private(set) ?MemoryInfo $memory {
            /**
             * @return \App\Application\System\DTO\Server\Response\Model\MemoryInfo|null
             */ get => $this->memory;
        },
    ) {
    }
}
