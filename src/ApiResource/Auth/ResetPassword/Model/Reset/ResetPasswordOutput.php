<?php

namespace App\ApiResource\Auth\ResetPassword\Model\Reset;

use ApiPlatform\Metadata\ApiProperty;

final class ResetPasswordOutput
{
    #[ApiProperty(description: 'Status da operação', example: 'password_changed')]
    public private(set) string $status;
}