<?php

namespace App\ApiResource\Auth\ResetPassword\Model\Reset;

use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiProperty;

final class ResetPasswordInput
{
    #[Assert\NotBlank]
    #[ApiProperty(description: 'Token de reset recebido por e-mail')]
    public string $token;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    #[ApiProperty(description: 'Nova senha (mínimo 6 caracteres)')]
    public string $plainPassword;
}
