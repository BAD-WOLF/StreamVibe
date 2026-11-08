<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Sessions\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do RevokeUserSessionsUseCase (DELETE /admin/users/{userId}/sessions):
 *
 *     {"user_id": 42, "revoked_sessions": 2}
 *
 * revoked_sessions: quantas sessões ativas foram encerradas (0 quando não
 * havia nenhuma). Cada uma precisa de um login novo.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class RevokeUserSessionsResponse {
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
