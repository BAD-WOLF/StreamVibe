<?php

namespace App\ApiResource\Movies\Details;

use App\ApiResource\Movies\Details\Model\MovieDetails\Details;
use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Movies\Details\Model\PersonSummary\Summary;

class MovieDetailsOutput {
    #[ApiProperty]
    public private(set) Details $movieDetails;

    #[ApiProperty]
    public private(set) Summary $personSummary;
}