<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Register\Request;

use App\Application\Authentication\Shared\Validation\NewPassword;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do RegisterUserUseCase (POST /register).
 *
 * NOTA (out/2026):
 * - Movido de DTO/Register/ para DTO/Register/Request/, espelhando os
 *   outros módulos.
 * - Saiu o `resendVerification`: o reenvio do email de verificação virou
 *   um UseCase próprio (ResendVerificationEmailUseCase), em vez de um
 *   "modo" do cadastro ligado por flag — o ResendVerificationController
 *   montava um RegisterUserRequest com senha vazia e agreeTerms=true só
 *   para passar pela validação.
 * - As regras de senha viraram a constraint NewPassword (as mesmas da
 *   troca de senha).
 * - Saíram as propriedades virtuais e os getters de compatibilidade
 *   (getEmail(), getPassword(), hasAgreedToTerms()...).
 */
final class RegisterUserRequest {
    /**
     * @param string $email
     * @param string $password
     * @param bool   $agreeTerms
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

        #[NewPassword]
        public private(set) string $password {
            /**
             * @return string
             */ get => $this->password;
        },

        #[Assert\IsTrue(message: 'You must agree to the terms and conditions')]
        public private(set) bool $agreeTerms = false {
            /**
             * @return bool
             */ get => $this->agreeTerms;
        },
    ) {
    }
}
