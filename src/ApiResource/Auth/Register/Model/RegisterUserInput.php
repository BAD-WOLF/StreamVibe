<?php

namespace App\ApiResource\Auth\Register\Model;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Assert;

final class RegisterUserInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[ApiProperty(description: 'User\'s email', example: 'exemple@gmail.com')]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    #[ApiProperty(description: 'User\'s password (minimum 6 characters)')]
    public string $password;
}
