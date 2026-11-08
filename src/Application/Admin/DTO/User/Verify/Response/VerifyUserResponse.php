<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Verify\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do VerifyUserUseCase (POST /admin/users/{userId}/verify):
 *
 *     {"user_id": 42, "verified": true}
 *
 * O email da conta foi confirmado pelo admin (a pessoa já pode entrar).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class VerifyUserResponse {
    /**
     * @param int         $userId
     * @param bool        $verified
     * @param string|null $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        public private(set) bool $verified {
            /**
             * @return bool
             */ get => $this->verified;
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
     * @param bool        $verified
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        int $userId,
        bool $verified,
        ?string $message = null,
    ): self {
        return new self(userId: $userId, verified: $verified, message: $message);
    }
}
