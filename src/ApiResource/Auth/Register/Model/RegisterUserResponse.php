<?php

namespace App\ApiResource\Auth\Register\Model;

use ApiPlatform\Metadata\ApiProperty;

final class RegisterUserResponse {
    #[ApiProperty]
    public string $message;
}