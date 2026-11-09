<?php

declare(strict_types = 1);

namespace App\Application\System\UseCase\Processes;

use App\Application\Shared\Port\SecretMaskerInterface;
use App\Application\System\DTO\Processes\Response\ListProcessesResponse;
use App\Application\System\DTO\Processes\Response\Model\ProcessInfo;
use App\Application\System\Port\ProcessInspectorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Os processos do contêiner (GET /system/processes, ROLE_DEV), em ordem de
 * PID.
 *
 * Uma linha de comando pode levar um segredo (php -d ..., um DSN com
 * senha, --password=...): todas passam pelo SecretMasker antes de sair.
 *
 * Não tem Request DTO: o endpoint não recebe nada.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class ListProcessesUseCase {
    /**
     * @param \App\Application\System\Port\ProcessInspectorInterface $processes
     * @param \App\Application\Shared\Port\SecretMaskerInterface     $secretMasker
     * @param \Symfony\Contracts\Translation\TranslatorInterface     $translator
     */
    public function __construct(
        private ProcessInspectorInterface $processes,
        private SecretMaskerInterface $secretMasker,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return \App\Application\System\DTO\Processes\Response\ListProcessesResponse
     */
    public function execute(): ListProcessesResponse {
        return ListProcessesResponse::success(
            processes: array_map(
                fn(ProcessInfo $process): ProcessInfo => $process->withCommand($this->secretMasker->mask($process->command)),
                $this->processes->processes(),
            ),
            message: $this->translator->trans('Processes retrieved successfully'),
        );
    }
}
