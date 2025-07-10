<?php

namespace App\ApiResource\Auth\ResetPassword\Model\Internal\Result;

use ApiPlatform\Metadata\ApiProperty;

final class Result {
    #[ApiProperty]
    public private(set) string $status;
}