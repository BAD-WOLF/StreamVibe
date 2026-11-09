<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Runtime\Model;

use App\Application\System\DTO\Runtime\Response\GetRuntimeStatusResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/system/runtime: `data` é o
 * GetRuntimeStatusResponse (ver o exemplo lá), com o envelope de sempre
 * ({"success": true, "data": {...}, "message": "Runtime status retrieved successfully"}).
 *
 * Mapeado do GetRuntimeStatusResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetRuntimeStatusResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AutoMapFromDto(GetRuntimeStatusResponse::class)]
final readonly class GetRuntimeStatusOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\System\DTO\Runtime\Response\GetRuntimeStatusResponse $data
     * @param string|null $message
     */
    public function __construct(
        public GetRuntimeStatusResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
