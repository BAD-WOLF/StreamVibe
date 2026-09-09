<?php

namespace App\Application\Movie\DTO\Details\Response\Model;

use ApiPlatform\Metadata\ApiProperty;

final class Genres {
    #[ApiProperty]
    public private(set) int $id;

    #[ApiProperty]
    public private(set) string $name;
}
