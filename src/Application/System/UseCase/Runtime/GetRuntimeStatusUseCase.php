<?php

declare(strict_types = 1);

namespace App\Application\System\UseCase\Runtime;

use App\Application\System\DTO\Runtime\Response\GetRuntimeStatusResponse;
use App\Application\System\Port\RuntimeInspectorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * O runtime que atende a API (GET /system/runtime, ROLE_DEV): o PHP, o
 * OPcache e o Symfony.
 *
 * Não tem Request DTO: o endpoint não recebe nada.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class GetRuntimeStatusUseCase {
    /**
     * @param \App\Application\System\Port\RuntimeInspectorInterface $runtime
     * @param \Symfony\Contracts\Translation\TranslatorInterface     $translator
     */
    public function __construct(
        private RuntimeInspectorInterface $runtime,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return \App\Application\System\DTO\Runtime\Response\GetRuntimeStatusResponse
     */
    public function execute(): GetRuntimeStatusResponse {
        return GetRuntimeStatusResponse::success(
            php: $this->runtime->php(),
            opcache: $this->runtime->opcache(),
            symfony: $this->runtime->symfony(),
            message: $this->translator->trans('Runtime status retrieved successfully'),
        );
    }
}
