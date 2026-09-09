<?php

namespace App\Application\Movie\DTO\Details\Response\Model;

use ApiPlatform\Metadata\ApiProperty;

class OriginCountry {

    #[ApiProperty]
    public private(set) string $name;
}