<?php

namespace App\ApiResource\Auth\ResetPassword\Model\Request;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;

final class RequestPasswordResetInput {
    #[Assert\NotBlank]
    #[Assert\Email]
    #[ApiProperty(description: 'E-mail do usuário solicitando reset', example: 'user@example.com')]
    public string $email;
}