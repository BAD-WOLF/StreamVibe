<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Details\Model;

use App\Application\Admin\DTO\User\Details\Response\GetUserDetailsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/admin/users/{userId}: `data` é o
 * GetUserDetailsResponse (ver o exemplo lá), com o envelope de sempre
 * ({"success": true, "data": {...}, "message": "User retrieved successfully"}).
 *
 * Mapeado do GetUserDetailsResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetUserDetailsResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(GetUserDetailsResponse::class)]
final readonly class GetUserDetailsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\Details\Response\GetUserDetailsResponse $data
     * @param string|null $message
     */
    public function __construct(
        public GetUserDetailsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
