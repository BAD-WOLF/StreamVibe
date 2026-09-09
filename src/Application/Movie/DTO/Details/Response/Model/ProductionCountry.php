<?php

namespace App\Application\Movie\DTO\Details\Response\Model;

use ApiPlatform\Metadata\ApiProperty;

final class ProductionCountry {
    #[ApiProperty]
    public private(set) string $iso_3166_1;

    #[ApiProperty]
    public private(set) string $name;
}
