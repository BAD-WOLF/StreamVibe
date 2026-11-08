<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Block\Response;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do BlockUserUseCase (POST /admin/users/{userId}/block):
 *
 *     {"user_id": 42, "blocked_at": "2026-11-08T12:00:00+00:00", "revoked_sessions": 2}
 *
 * revoked_sessions: quantas sessões ativas da conta foram encerradas.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class BlockUserResponse {
    /**
     * @param int                $userId
     * @param \DateTimeImmutable $blockedAt
     * @param int                $revokedSessions
     * @param string|null        $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        #[SerializedName('blocked_at')]
        public private(set) DateTimeImmutable $blockedAt {
            /**
             * @return \DateTimeImmutable
             */ get => $this->blockedAt;
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
     * @param int                $userId
     * @param \DateTimeImmutable $blockedAt
     * @param int                $revokedSessions
     * @param string|null        $message
     *
     * @return self
     */
    public static function success(
        int $userId,
        DateTimeImmutable $blockedAt,
        int $revokedSessions,
        ?string $message = null,
    ): self {
        return new self(userId: $userId, blockedAt: $blockedAt, revokedSessions: $revokedSessions, message: $message);
    }
}
