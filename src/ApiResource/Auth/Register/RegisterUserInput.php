<?php

namespace App\ApiResource\Auth\Register;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Assert;

final class RegisterUserInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[ApiProperty(description: 'E-mail do usuário', example: 'exemple@gmail.com')]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    #[ApiProperty(description: 'Senha (mínimo 6 caracteres)')]
    public string $password;
}
