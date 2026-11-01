<?php

declare(strict_types = 1);

namespace App\Application\Authentication\Shared\Validation;

use Attribute;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Regras de senha nova, iguais no cadastro (RegisterUserRequest) e na
 * troca de senha (ConfirmPasswordResetRequest): obrigatória, de 12 a 4096
 * caracteres, força mínima média (PasswordStrength) e fora das listas de
 * senhas vazadas (NotCompromisedPassword — desligado nos testes em
 * config/packages/validator.yaml, porque consulta a API do
 * haveibeenpwned).
 *
 * NOTA (out/2026): as regras eram atributos soltos no RegisterUserRequest.
 * Com o endpoint novo de troca de senha (POST /reset-password/confirm),
 * viraram um Compound para as duas entradas usarem exatamente a mesma
 * regra — senão a troca de senha aceitaria uma senha que o cadastro
 * recusa.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class NewPassword extends Compound {
    /**
     * @param array<string, mixed> $options
     *
     * @return \Symfony\Component\Validator\Constraint[]
     */
    protected function getConstraints(array $options): array {
        return [
            new Assert\NotBlank(message: 'Password cannot be blank'),
            new Assert\Length(
                min: 12,
                max: 4096,
                minMessage: 'Password must be at least {{ limit }} characters long',
                maxMessage: 'Password cannot be longer than {{ limit }} characters',
            ),
            new Assert\PasswordStrength(),
            new Assert\NotCompromisedPassword(),
        ];
    }
}
