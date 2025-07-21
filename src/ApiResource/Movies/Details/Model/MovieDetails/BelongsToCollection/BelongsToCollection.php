<?php

namespace App\ApiResource\Movies\Details\Model\MovieDetails\BelongsToCollection;

use ApiPlatform\Metadata\ApiProperty;

final class BelongsToCollection {
    #[ApiProperty]
    public private(set) int $id;

    #[ApiProperty]
    public private(set) string $name;

    #[ApiProperty]
    public private(set) string $poster_path;

    #[ApiProperty]
    public private(set) string $backdrop_path;
}