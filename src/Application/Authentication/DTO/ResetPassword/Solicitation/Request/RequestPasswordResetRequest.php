<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\Solicitation\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do RequestPasswordResetUseCase (POST /reset-password): o email
 * da conta que quer trocar a senha.
 *
 * NOTA (out/2026): era o DTO/Reset/Request/ResetPasswordRequest.
 * Saíram userAgent, ipAddress, locale e sendEmail: os três primeiros só
 * iam para o contexto do email (o template nunca os usou) e sendEmail era
 * sempre true. Saíram também asArray/toArray() e os getters de
 * compatibilidade.
 */
final class RequestPasswordResetRequest {
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
