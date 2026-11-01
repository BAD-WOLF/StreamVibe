<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Uma solicitação de troca de senha ainda válida, em
 * `data.active_requests` de GET /reset-password/requests/{userId}.
 *
 *     {"id": 7, "created_at": "2026-10-28T14:04:05+00:00", "expires_at": "2026-10-28T15:04:05+00:00"}
 *
 * NOTA (out/2026): antes cada item era um array montado em
 * ResetUserPasswordUseCase::getUserActiveRequests(). Mesmas chaves; as
 * datas saem em ISO 8601 (RFC 3339).
 */
final class PasswordResetRequestSummary {
    /**
     * @param int                $id
     * @param \DateTimeImmutable $createdAt quando o link foi pedido
     * @param \DateTimeImmutable $expiresAt
     */
    public function __construct(
        public private(set) int $id,
        #[SerializedName('created_at')]
        public private(set) DateTimeImmutable $createdAt,
        #[SerializedName('expires_at')]
        public private(set) DateTimeImmutable $expiresAt,
    ) {
    }
}
