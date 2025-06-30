<?php

namespace App\ApiResource\Movies\Details\Model\MovieDetails\ProductionCountries;

use ApiPlatform\Metadata\ApiProperty;

final class ProductionCountry {
    #[ApiProperty]
    public private(set) string $iso_3166_1;

    #[ApiProperty]
    public private(set) string $name;
}