<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Delete\Model;

use App\Application\Admin\DTO\User\Delete\Response\DeleteUserResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de DELETE /{_locale}/api/admin/users/{userId}:
 *
 *     {"success": true, "data": {"user_id": 42, "revoked_sessions": 2}, "message": "User deleted successfully"}
 *
 * Mapeado do DeleteUserResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de DeleteUserResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(DeleteUserResponse::class)]
final readonly class DeleteUserOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\Delete\Response\DeleteUserResponse $data
     * @param string|null $message
     */
    public function __construct(
        public DeleteUserResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
