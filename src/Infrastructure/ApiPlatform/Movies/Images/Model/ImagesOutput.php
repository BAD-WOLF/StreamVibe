<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Movies\Images\Model;

use App\Application\Movie\DTO\GetMovieImagesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Output model for movie images operations in ApiPlatform
 *
 * This class is automatically mapped from GetMovieImagesResponse DTO
 * using the AutoMapFromDto attribute - no manual mapper needed!
 */
#[AutoMapFromDto(GetMovieImagesResponse::class)]
final readonly class ImagesOutput
{
    /**
     * @param bool        $success
     * @param array       $data
     * @param string|null $message
     * @param array       $errors
     * @param int|null    $movie_id
     * @param array|null  $images
     * @param array|null  $posters
     * @param array|null  $backdrops
     * @param array|null  $logos
     */
    public function __construct(
        public bool $success,
        public array $data,
        public ?string $message = null,
        public array $errors = [],
        public ?int $movie_id = null,
        public ?array $images = null,
        public ?array $posters = null,
        public ?array $backdrops = null,
        public ?array $logos = null,
    ) {}

    /**
     * Create instance from array data
     *
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data["success"] ?? false,
            data: $data["data"] ?? [],
            message: $data["message"] ?? null,
            errors: $data["errors"] ?? [],
            movie_id: $data["movie_id"] ?? null,
            images: $data["images"] ?? null,
            posters: $data["posters"] ?? null,
            backdrops: $data["backdrops"] ?? null,
            logos: $data["logos"] ?? null,
        );
    }

    /**
     * Check if the response represents a successful operation
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get response data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get response message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get errors array
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Convert to array format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            "success" => $this->success,
            "data" => $this->data,
            "message" => $this->message,
            "errors" => $this->errors,
            "movie_id" => $this->movie_id,
            "images" => $this->images,
            "posters" => $this->posters,
            "backdrops" => $this->backdrops,
            "logos" => $this->logos,
        ];
    }
}
