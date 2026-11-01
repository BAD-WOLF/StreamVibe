<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\Cancel\Response;

use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do CancelPasswordResetUseCase: o `data` de
 * POST /reset-password/cancel.
 *
 *     {"cancelled": true}
 *
 * NOTA (out/2026): antes o cancelamento devolvia o ResetPasswordResponse
 * (o mesmo do pedido de troca), com request_id/expires_at/email_sent/
 * user_id todos vazios. Agora tem DTO próprio.
 */
final class CancelPasswordResetResponse {
    /**
     * @param string|null $message
     */
    public function __construct(
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * Sempre true: token inexistente é 404 (ExceptionListener).
     */
    public bool $cancelled {
        /**
         * @return bool
         */
        get => true;
    }

    /**
     * @param string|null $message
     *
     * @return self
     */
    public static function success(?string $message = null): self {
        return new self(message: $message);
    }
}
