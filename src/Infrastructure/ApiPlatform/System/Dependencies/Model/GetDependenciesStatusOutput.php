<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Dependencies\Model;

use App\Application\System\DTO\Dependencies\Response\GetDependenciesStatusResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/system/dependencies: `data` é o
 * GetDependenciesStatusResponse (ver o exemplo lá), com o envelope de sempre
 * ({"success": true, "data": {...}, "message": "Dependencies checked successfully"}).
 *
 * Mapeado do GetDependenciesStatusResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetDependenciesStatusResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AutoMapFromDto(GetDependenciesStatusResponse::class)]
final readonly class GetDependenciesStatusOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\System\DTO\Dependencies\Response\GetDependenciesStatusResponse $data
     * @param string|null $message
     */
    public function __construct(
        public GetDependenciesStatusResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
