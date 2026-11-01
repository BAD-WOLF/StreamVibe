<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\Confirm\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do ConfirmPasswordResetUseCase: o `data` de
 * POST /reset-password/confirm.
 *
 *     {"password_reset": true}
 *
 * NOTA (out/2026): criado com o endpoint de troca de senha.
 */
final class ConfirmPasswordResetResponse {
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
     * Sempre true: token inválido/vencido e senha fraca saem como erro.
     */
    #[SerializedName('password_reset')]
    public bool $passwordReset {
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
