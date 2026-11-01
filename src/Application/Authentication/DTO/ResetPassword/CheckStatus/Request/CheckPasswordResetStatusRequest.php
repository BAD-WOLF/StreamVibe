<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\CheckStatus\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do CheckPasswordResetStatusUseCase
 * (GET /reset-password/status/{token}).
 *
 * NOTA (out/2026): criado na divisão do ResetUserPasswordUseCase
 * (checkStatus() recebia a string solta). O formato do token (40
 * caracteres) é conferido pelo ResetPasswordBundle, atrás da
 * PasswordResetInterface.
 */
final class CheckPasswordResetStatusRequest {
    /**
     * @param string $token
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Token cannot be blank')]
        public private(set) string $token {
            /**
             * @return string
             */ get => $this->token;
        },
    ) {
    }
}
