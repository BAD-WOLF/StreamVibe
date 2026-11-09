<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Logs\List\Model;

use App\Application\System\DTO\Logs\List\Response\ListLogFilesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/system/logs: `data` é o
 * ListLogFilesResponse (ver o exemplo lá), com o envelope de sempre
 * ({"success": true, "data": {...}, "message": "Log files retrieved successfully"}).
 *
 * Mapeado do ListLogFilesResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ListLogFilesResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AutoMapFromDto(ListLogFilesResponse::class)]
final readonly class ListLogFilesOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\System\DTO\Logs\List\Response\ListLogFilesResponse $data
     * @param string|null $message
     */
    public function __construct(
        public ListLogFilesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
