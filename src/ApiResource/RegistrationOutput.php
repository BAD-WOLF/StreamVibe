<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

final class RegistrationOutput {
    #[ApiProperty]
    public string $message;
}