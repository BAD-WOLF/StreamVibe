<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Processes\Model;

use App\Application\System\DTO\Processes\Response\ListProcessesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/system/processes: `data` é o
 * ListProcessesResponse (ver o exemplo lá), com o envelope de sempre
 * ({"success": true, "data": {...}, "message": "Processes retrieved successfully"}).
 *
 * Mapeado do ListProcessesResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ListProcessesResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AutoMapFromDto(ListProcessesResponse::class)]
final readonly class ListProcessesOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\System\DTO\Processes\Response\ListProcessesResponse $data
     * @param string|null $message
     */
    public function __construct(
        public ListProcessesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
