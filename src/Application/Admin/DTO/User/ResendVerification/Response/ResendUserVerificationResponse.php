<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\ResendVerification\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do ResendUserVerificationUseCase
 * (POST /admin/users/{userId}/resend-verification):
 *
 *     {"user_id": 42, "email_sent": true}
 *
 * Mesmo `email_sent` do POST /resend-verification; aqui é sempre true
 * (a conta existe, e uma falha no envio responde 503).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ResendUserVerificationResponse {
    /**
     * @param int         $userId
     * @param bool        $emailSent
     * @param string|null $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        #[SerializedName('email_sent')]
        public private(set) bool $emailSent {
            /**
             * @return bool
             */ get => $this->emailSent;
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
     * @param bool        $emailSent
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        int $userId,
        bool $emailSent,
        ?string $message = null,
    ): self {
        return new self(userId: $userId, emailSent: $emailSent, message: $message);
    }
}
