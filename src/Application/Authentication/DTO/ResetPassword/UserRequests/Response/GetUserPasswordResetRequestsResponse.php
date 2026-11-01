<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\UserRequests\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetUserPasswordResetRequestsUseCase: o `data` de
 * GET /reset-password/requests/{userId}.
 *
 *     {"user_id": 42, "active_requests": [{"id": 7, "created_at": "...", "expires_at": "..."}]}
 *
 * NOTA (out/2026): o controller montava esse JSON na mão a partir do
 * array de getUserActiveRequests() (o NOTA de lá explicava que não havia
 * DTO). Agora tem DTO e Output. O `count` saiu: era só o tamanho de
 * active_requests, repetido.
 */
final class GetUserPasswordResetRequestsResponse {
    /**
     * @param int                                                                                                             $userId
     * @param list<\App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model\PasswordResetRequestSummary> $activeRequests
     * @param string|null                                                                                                     $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        #[SerializedName('active_requests')]
        public private(set) array $activeRequests {
            /**
             * @return list<\App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model\PasswordResetRequestSummary>
             */ get => $this->activeRequests;
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
     * @param int                                                                                                             $userId
     * @param list<\App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model\PasswordResetRequestSummary> $activeRequests
     * @param string|null                                                                                                     $message
     *
     * @return self
     */
    public static function success(int $userId, array $activeRequests, ?string $message = null): self {
        return new self(userId: $userId, activeRequests: $activeRequests, message: $message);
    }
}
