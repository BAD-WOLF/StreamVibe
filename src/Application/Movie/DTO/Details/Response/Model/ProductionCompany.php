<?php

namespace App\Application\Movie\DTO\Details\Response\Model;

use ApiPlatform\Metadata\ApiProperty;

final class ProductionCompany {
    #[ApiProperty]
    public private(set) int $id;

    #[ApiProperty]
    public private(set) ?string $logo_path;

    #[ApiProperty]
    public private(set) string $name;

    #[ApiProperty]
    public private(set) string $origin_country;
}
