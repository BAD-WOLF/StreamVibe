<?php

namespace App\Application\Movie\DTO\Details\Response\Model;

use ApiPlatform\Metadata\ApiProperty;

final class SpokenLanguage {
    #[ApiProperty]
    public private(set) string $english_name;

    #[ApiProperty]
    public private(set) string $iso_639_1;

    #[ApiProperty]
    public private(set) string $name;
}
