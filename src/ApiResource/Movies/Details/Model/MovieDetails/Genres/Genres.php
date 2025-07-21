<?php

namespace App\ApiResource\Movies\Details\Model\MovieDetails\Genres;

use ApiPlatform\Metadata\ApiProperty;

final class Genres {
    #[ApiProperty]
    public private(set) int $genre;

    #[ApiProperty]
    public private(set) string $name;
}