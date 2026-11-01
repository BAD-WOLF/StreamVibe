<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResendVerification\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do ResendVerificationEmailUseCase: o `data` de
 * POST /resend-verification.
 *
 *     {"email_sent": true}
 *
 * email_sent é false quando não existe conta com o email (a mensagem é a
 * genérica "If an account with this email exists..."), igual ao pedido
 * de troca de senha (RequestPasswordResetResponse).
 *
 * NOTA (out/2026): este DTO já existia (com success/error/errors e
 * toArray()) mas ninguém o usava — o reenvio devolvia o
 * RegisterUserResponse. Agora é a resposta do UseCase próprio de
 * reenvio, só com o que é dado de fato.
 */
final class ResendVerificationEmailResponse {
    /**
     * @param bool        $emailSent
     * @param string|null $message
     */
    public function __construct(
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
     * @param bool        $emailSent
     * @param string|null $message
     *
     * @return self
     */
    public static function success(bool $emailSent, ?string $message = null): self {
        return new self(emailSent: $emailSent, message: $message);
    }
}
