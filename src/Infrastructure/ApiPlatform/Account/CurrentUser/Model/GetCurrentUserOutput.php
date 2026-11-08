<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Account\CurrentUser\Model;

use App\Application\Account\DTO\CurrentUser\Response\GetCurrentUserResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/me:
 *
 *     {
 *       "success": true,
 *       "data": {"id": 42, "email": "ana@example.com", "role": "ROLE_USER", "verified": true},
 *       "message": "Current user retrieved successfully"
 *     }
 *
 * Mapeado do GetCurrentUserResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetCurrentUserResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com o GET /me.
 */
#[AutoMapFromDto(GetCurrentUserResponse::class)]
final readonly class GetCurrentUserOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Account\DTO\CurrentUser\Response\GetCurrentUserResponse $data
     * @param string|null                                                            $message
     */
    public function __construct(
        public GetCurrentUserResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
