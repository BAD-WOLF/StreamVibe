<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Role\Model;

use App\Application\Admin\DTO\User\Role\Response\ChangeUserRoleResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/admin/users/{userId}/role:
 *
 *     {"success": true, "data": {"user_id": 42, "role": "ROLE_DEV", "previous_role": "ROLE_USER"},
 *      "message": "User role changed successfully"}
 *
 * Mapeado do ChangeUserRoleResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ChangeUserRoleResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(ChangeUserRoleResponse::class)]
final readonly class ChangeUserRoleOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\Role\Response\ChangeUserRoleResponse $data
     * @param string|null $message
     */
    public function __construct(
        public ChangeUserRoleResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
