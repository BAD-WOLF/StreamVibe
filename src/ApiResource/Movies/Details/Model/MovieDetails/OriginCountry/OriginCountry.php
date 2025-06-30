<?php

namespace App\ApiResource\Movies\Details\Model\MovieDetails\OriginCountry;

use ApiPlatform\Metadata\ApiProperty;

class OriginCountry {

    #[ApiProperty]
    public private(set) string $name;
}