<?php

namespace App\ApiResource\Auth\ResetPassword\Model\Reset;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Auth\ResetPassword\Model\Internal\Result\Result;

final class ValidateResetTokenResponse
{
    #[ApiProperty(description: 'Indicador de token válido')]
    public private(set) bool $success;
    public private(set) Result $result;
}