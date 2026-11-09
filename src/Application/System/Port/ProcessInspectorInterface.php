<?php

declare(strict_types = 1);

namespace App\Application\System\Port;

/**
 * Porta para os processos que a aplicação enxerga — no Docker, os do
 * contêiner. A implementação (Infrastructure\System\ProcProcessInspector)
 * lê o /proc/<pid> de cada um; o ambiente dos processos
 * (/proc/<pid>/environ, onde ficam os segredos) nunca é lido.
 *
 * NOTA (nov/2026): criada com o painel do sistema (ROLE_DEV).
 */
interface ProcessInspectorInterface {
    /**
     * Os processos em ordem de PID, com a linha de comando como está (quem
     * a mostra troca os segredos por "***").
     *
     * @return list<\App\Application\System\DTO\Processes\Response\Model\ProcessInfo>
     */
    public function processes(): array;
}
