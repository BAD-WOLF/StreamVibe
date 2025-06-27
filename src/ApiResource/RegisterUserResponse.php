<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

final class RegisterUserResponse {
    #[ApiProperty]
    public string $message;
}