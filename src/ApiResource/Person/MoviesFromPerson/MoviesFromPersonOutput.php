<?php

namespace App\ApiResource\Person\MoviesFromPerson;

use ApiPlatform\Metadata\ApiProperty;

final class MoviesFromPersonOutput {
    #[ApiProperty]
    public MoviesFromPerson $moviesFromPerson;
}
