<?php

namespace App\ApiResource\Person\MoviesFromPerson;

use ApiPlatform\Metadata\ApiProperty;

final class MoviesFromPerson
{
    #[ApiProperty]
    public CastItem $cast;

    #[ApiProperty]
    public int $id;
}