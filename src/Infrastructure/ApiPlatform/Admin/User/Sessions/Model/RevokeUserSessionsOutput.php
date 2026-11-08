<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Sessions\Model;

use App\Application\Admin\DTO\User\Sessions\Response\RevokeUserSessionsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de DELETE /{_locale}/api/admin/users/{userId}/sessions:
 *
 *     {"success": true, "data": {"user_id": 42, "revoked_sessions": 2}, "message": "User sessions revoked successfully"}
 *
 * Mapeado do RevokeUserSessionsResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de RevokeUserSessionsResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(RevokeUserSessionsResponse::class)]
final readonly class RevokeUserSessionsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\Sessions\Response\RevokeUserSessionsResponse $data
     * @param string|null $message
     */
    public function __construct(
        public RevokeUserSessionsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
