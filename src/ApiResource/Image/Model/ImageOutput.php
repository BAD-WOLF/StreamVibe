<?php

namespace App\ApiResource\Image\Model;

use ApiPlatform\Metadata\ApiResource;

class ImageOutput {
    #[ApiResource]
    public ?string $base64;
}