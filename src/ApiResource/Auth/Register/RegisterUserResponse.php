<?php

namespace App\ApiResource\Auth\Register;

use ApiPlatform\Metadata\ApiProperty;

final class RegisterUserResponse {
    #[ApiProperty]
    public string $message;
}