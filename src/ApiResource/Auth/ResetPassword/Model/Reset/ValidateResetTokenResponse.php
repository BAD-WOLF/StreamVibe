<?php

namespace App\ApiResource\Auth\ResetPassword\Model\Reset;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Auth\ResetPassword\Model\Internal\Result\Result;

final class ValidateResetTokenResponse
{
    #[ApiProperty(description: 'Valid token indicator')]
    public private(set) bool $success;
    public private(set) Result $result;
}