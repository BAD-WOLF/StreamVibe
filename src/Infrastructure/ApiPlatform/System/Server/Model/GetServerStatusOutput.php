<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Server\Model;

use App\Application\System\DTO\Server\Response\GetServerStatusResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/system/server: `data` é o
 * GetServerStatusResponse (ver o exemplo lá), com o envelope de sempre
 * ({"success": true, "data": {...}, "message": "Server status retrieved successfully"}).
 *
 * Mapeado do GetServerStatusResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetServerStatusResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AutoMapFromDto(GetServerStatusResponse::class)]
final readonly class GetServerStatusOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\System\DTO\Server\Response\GetServerStatusResponse $data
     * @param string|null $message
     */
    public function __construct(
        public GetServerStatusResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
