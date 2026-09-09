<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Image\Model;

use App\Application\Image\DTO\GetImageResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Output model for image operations in ApiPlatform
 *
 * This class is automatically mapped from GetImageResponse DTO
 * using the AutoMapFromDto attribute with custom field mapping.
 */
#[
    AutoMapFromDto(
        dtoClass: GetImageResponse::class,
        fieldMap: [
            "content" => "imageData",
            "contentType" => "mimeType",
        ],
    ),
]
final readonly class ImageOutput
{
    /**
     * @param bool        $success
     * @param mixed       $content
     * @param string      $contentType
     * @param string|null $message
     */
    public function __construct(
        public bool $success,
        public mixed $content,
        public string $contentType,
        public ?string $message = null,
    ) {}

    /**
     * @param mixed  $content
     * @param string $contentType
     *
     * @return self
     */
    public static function fromResponse(
        mixed $content,
        string $contentType,
    ): self {
        return new self(
            success: true,
            content: $content,
            contentType: $contentType,
        );
    }

    /**
     * @param string $message
     *
     * @return self
     */
    public static function error(string $message): self
    {
        return new self(
            success: false,
            content: null,
            contentType: "text/plain",
            message: $message,
        );
    }
}
