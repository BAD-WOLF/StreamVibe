<?php

namespace App\ApiResource\Auth\ResetPassword\Model\Reset;

use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiProperty;

final class ResetPasswordInput
{
    #[Assert\NotBlank]
    #[ApiProperty(description: 'Reset token received by email')]
    public string $token;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    #[ApiProperty(description: 'New password (minimum 6 characters)')]
    public string $plainPassword;
}
