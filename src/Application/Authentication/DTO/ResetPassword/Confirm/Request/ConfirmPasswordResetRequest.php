<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\Confirm\Request;

use App\Application\Authentication\Shared\Validation\NewPassword;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do ConfirmPasswordResetUseCase (POST /reset-password/confirm):
 * o token do link recebido por email e a senha nova.
 *
 * NOTA (out/2026): criado com o endpoint de troca de senha, que não
 * existia — dava para pedir o link, conferir e cancelar, mas não para
 * definir a senha nova. A senha segue as mesmas regras do cadastro
 * (NewPassword).
 */
final class ConfirmPasswordResetRequest {
    /**
     * @param string $token
     * @param string $password
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Token cannot be blank')]
        public private(set) string $token {
            /**
             * @return string
             */ get => $this->token;
        },

        #[NewPassword]
        public private(set) string $password {
            /**
             * @return string
             */ get => $this->password;
        },
    ) {
    }
}
