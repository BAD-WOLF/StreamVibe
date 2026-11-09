<?php

declare(strict_types = 1);

namespace App\Application\System\Port;

use App\Application\System\DTO\Server\Response\Model\ContainerInfo;
use App\Application\System\DTO\Server\Response\Model\HostInfo;

/**
 * Porta para o servidor em que a API roda: a máquina, o contêiner (o
 * cgroup da aplicação) e os discos. A implementação
 * (Infrastructure\System\ProcServerInspector) lê o /proc e o
 * /sys/fs/cgroup do Linux.
 *
 * As portas do painel do sistema devolvem direto os Models da resposta
 * (DTO/.../Response/Model): são dados para ler, sem regra de negócio no
 * meio, e uma segunda classe com os mesmos campos só repetiria estas.
 *
 * NOTA (nov/2026): criada com o painel do sistema (ROLE_DEV).
 */
interface ServerInspectorInterface {
    /**
     * A máquina: hostname, sistema, kernel, CPUs, carga, memória e tempo
     * ligado.
     *
     * @return \App\Application\System\DTO\Server\Response\Model\HostInfo
     */
    public function host(): HostInfo;

    /**
     * Os limites e o uso do cgroup da aplicação (o contêiner, no Docker);
     * null quando não há cgroup para ler.
     *
     * @return \App\Application\System\DTO\Server\Response\Model\ContainerInfo|null
     */
    public function container(): ?ContainerInfo;

    /**
     * Os sistemas de arquivos montados, sem os virtuais, em ordem de ponto
     * de montagem.
     *
     * @return list<\App\Application\System\DTO\Server\Response\Model\DiskInfo>
     */
    public function disks(): array;
}
