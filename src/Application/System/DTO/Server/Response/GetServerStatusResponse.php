<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Server\Response;

use App\Application\System\DTO\Server\Response\Model\ContainerInfo;
use App\Application\System\DTO\Server\Response\Model\HostInfo;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do GetServerStatusUseCase (GET /system/server): a máquina, o
 * contêiner e os discos.
 *
 *     {"host": {"hostname": "3f2a9c1b7d4e", "cpus": 8, "memory": {...}, ...},
 *      "container": {"cgroup_version": 2, "memory_limit_bytes": null, ...},
 *      "disks": [{"mount_point": "/", "filesystem": "overlay", ...}]}
 *
 * - container: null quando o cgroup da aplicação não pode ser lido;
 * - disks: em ordem de ponto de montagem.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class GetServerStatusResponse {
    /**
     * @param \App\Application\System\DTO\Server\Response\Model\HostInfo           $host
     * @param \App\Application\System\DTO\Server\Response\Model\ContainerInfo|null $container
     * @param list<\App\Application\System\DTO\Server\Response\Model\DiskInfo>     $disks
     * @param string|null                                                          $message
     */
    public function __construct(
        public private(set) HostInfo $host {
            /**
             * @return \App\Application\System\DTO\Server\Response\Model\HostInfo
             */ get => $this->host;
        },
        public private(set) ?ContainerInfo $container {
            /**
             * @return \App\Application\System\DTO\Server\Response\Model\ContainerInfo|null
             */ get => $this->container;
        },
        public private(set) array $disks {
            /**
             * @return list<\App\Application\System\DTO\Server\Response\Model\DiskInfo>
             */ get => $this->disks;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param \App\Application\System\DTO\Server\Response\Model\HostInfo           $host
     * @param \App\Application\System\DTO\Server\Response\Model\ContainerInfo|null $container
     * @param list<\App\Application\System\DTO\Server\Response\Model\DiskInfo>     $disks
     * @param string|null                                                          $message
     *
     * @return self
     */
    public static function success(
        HostInfo $host,
        ?ContainerInfo $container,
        array $disks,
        ?string $message = null,
    ): self {
        return new self(
            host: $host,
            container: $container,
            disks: $disks,
            message: $message,
        );
    }
}
