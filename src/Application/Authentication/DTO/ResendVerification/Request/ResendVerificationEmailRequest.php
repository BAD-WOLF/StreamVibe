<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResendVerification\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do ResendVerificationEmailUseCase (POST /resend-verification).
 *
 * NOTA (out/2026): criado quando o reenvio virou UseCase próprio. Antes o
 * controller validava o email na mão (requiredFieldMissing,
 * invalidEmail) e montava um RegisterUserRequest com a flag
 * resendVerification. As regras de email são as mesmas do cadastro.
 */
final class ResendVerificationEmailRequest {
    /**
     * @param string $email
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Email cannot be blank')]
        #[Assert\Email(message: 'Please provide a valid email address')]
        #[Assert\Length(max: 180, maxMessage: 'Email cannot be longer than {{ limit }} characters')]
        public private(set) string $email {
            /**
             * @return string
             */ get => $this->email;
        },
    ) {
    }
}
