<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\Solicitation\Response;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do RequestPasswordResetUseCase: o `data` de
 * POST /reset-password.
 *
 *     {"email_sent": true, "expires_at": "2026-10-28T15:04:05+00:00"}
 *
 * - email_sent false + expires_at null: não existe conta com o email
 *   (mensagem genérica);
 * - email_sent false + expires_at preenchido: já existe um link válido
 *   (não manda outro);
 * - email_sent true: link novo enviado.
 *
 * NOTA (out/2026): era o DTO/Reset/Response/ResetPasswordResponse, usado
 * também pelo cancelamento. O JSON real era o toArray() (sem envelope:
 * message, request_id, expires_at, email_sent, user_id e expiry_status no
 * nível de cima). Saíram request_id e user_id — davam o id interno da
 * conta para quem só sabe o email —, expiry_status/timeUntilExpiry
 * (derivados de expires_at) e isSuccess(), que devolvia email_sent.
 * expires_at sai em ISO 8601 (RFC 3339), como as outras datas da API;
 * antes era "Y-m-d H:i:s" sem fuso.
 */
final class RequestPasswordResetResponse {
    /**
     * @param bool                    $emailSent
     * @param \DateTimeImmutable|null $expiresAt
     * @param string|null             $message
     */
    public function __construct(
        #[SerializedName('email_sent')]
        public private(set) bool $emailSent {
            /**
             * @return bool
             */ get => $this->emailSent;
        },
        #[SerializedName('expires_at')]
        public private(set) ?DateTimeImmutable $expiresAt = null {
            /**
             * @return \DateTimeImmutable|null
             */ get => $this->expiresAt;
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
     * @param bool                    $emailSent
     * @param \DateTimeImmutable|null $expiresAt
     * @param string|null             $message
     *
     * @return self
     */
    public static function success(
        bool $emailSent,
        ?DateTimeImmutable $expiresAt = null,
        ?string $message = null,
    ): self {
        return new self(emailSent: $emailSent, expiresAt: $expiresAt, message: $message);
    }
}
