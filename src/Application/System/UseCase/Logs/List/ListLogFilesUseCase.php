<?php

declare(strict_types = 1);

namespace App\Application\System\UseCase\Logs\List;

use App\Application\System\DTO\Logs\List\Response\ListLogFilesResponse;
use App\Application\System\Port\LogReaderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Os arquivos de log da aplicação (GET /system/logs, ROLE_DEV), do
 * alterado por último ao mais antigo. As linhas de cada um saem pelo
 * ReadLogFileUseCase.
 *
 * Não tem Request DTO: o endpoint não recebe nada.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class ListLogFilesUseCase {
    /**
     * @param \App\Application\System\Port\LogReaderInterface    $logs
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(
        private LogReaderInterface $logs,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return \App\Application\System\DTO\Logs\List\Response\ListLogFilesResponse
     */
    public function execute(): ListLogFilesResponse {
        return ListLogFilesResponse::success(
            directory: $this->logs->directory(),
            files: $this->logs->files(),
            message: $this->translator->trans('Log files retrieved successfully'),
        );
    }
}
