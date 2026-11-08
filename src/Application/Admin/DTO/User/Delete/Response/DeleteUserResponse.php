<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Delete\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do DeleteUserUseCase (DELETE /admin/users/{userId}):
 *
 *     {"user_id": 42, "revoked_sessions": 2}
 *
 * A conta não existe mais (nem os pedidos de troca de senha dela);
 * revoked_sessions: quantas sessões ativas foram encerradas junto.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class DeleteUserResponse {
    /**
     * @param int         $userId
     * @param int         $revokedSessions
     * @param string|null $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        #[SerializedName('revoked_sessions')]
        public private(set) int $revokedSessions {
            /**
             * @return int
             */ get => $this->revokedSessions;
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
     * @param int         $revokedSessions
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        int $userId,
        int $revokedSessions,
        ?string $message = null,
    ): self {
        return new self(userId: $userId, revokedSessions: $revokedSessions, message: $message);
    }
}
