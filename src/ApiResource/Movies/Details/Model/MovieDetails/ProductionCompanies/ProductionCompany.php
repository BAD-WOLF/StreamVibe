<?php

namespace App\ApiResource\Movies\Details\Model\MovieDetails\ProductionCompanies;

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