<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Role\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do ChangeUserRoleUseCase (POST /admin/users/{userId}/role):
 *
 *     {"user_id": 42, "role": "ROLE_DEV", "previous_role": "ROLE_USER"}
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ChangeUserRoleResponse {
    /**
     * @param int         $userId
     * @param string      $role
     * @param string      $previousRole
     * @param string|null $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        public private(set) string $role {
            /**
             * @return string
             */ get => $this->role;
        },
        #[SerializedName('previous_role')]
        public private(set) string $previousRole {
            /**
             * @return string
             */ get => $this->previousRole;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param int         $userId
     * @param string      $role
     * @param string      $previousRole
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        int $userId,
        string $role,
        string $previousRole,
        ?string $message = null,
    ): self {
        return new self(userId: $userId, role: $role, previousRole: $previousRole, message: $message);
    }
}
