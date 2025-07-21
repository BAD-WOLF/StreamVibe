<?php

namespace App\ApiResource\Movies\Image;

use ApiPlatform\Metadata\ApiResource;

class MovieImageOutput {
    #[ApiResource]
    public ?string $base64;
}