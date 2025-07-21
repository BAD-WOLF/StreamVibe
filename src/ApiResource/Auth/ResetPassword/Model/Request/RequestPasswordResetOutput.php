<?php

namespace App\ApiResource\Auth\ResetPassword\Model\Request;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Auth\ResetPassword\Model\Internal\Result\Result;

final class RequestPasswordResetOutput
{
    #[ApiProperty]
    public private(set) bool $success;
    public private(set) Result $result;
}
