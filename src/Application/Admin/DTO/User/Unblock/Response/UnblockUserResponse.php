<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Unblock\Response;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do UnblockUserUseCase (POST /admin/users/{userId}/unblock):
 *
 *     {"user_id": 42, "blocked_at": null}
 *
 * O mesmo `blocked_at` do bloqueio, agora null (a conta está liberada).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class UnblockUserResponse {
    /**
     * @param int                     $userId
     * @param \DateTimeImmutable|null $blockedAt
     * @param string|null             $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        #[SerializedName('blocked_at')]
        public private(set) ?DateTimeImmutable $blockedAt {
            /**
             * @return \DateTimeImmutable|null
             */ get => $this->blockedAt;
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
     * @param int                     $userId
     * @param \DateTimeImmutable|null $blockedAt
     * @param string|null             $message
     *
     * @return self
     */
    public static function success(
        int $userId,
        ?DateTimeImmutable $blockedAt,
        ?string $message = null,
    ): self {
        return new self(userId: $userId, blockedAt: $blockedAt, message: $message);
    }
}
