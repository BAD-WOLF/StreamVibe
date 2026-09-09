<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Details\Model;

use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;
use App\Application\Movie\DTO\Details\Response\GetMovieDetailsResponse as GetMovieDetailsResponseDto;

/**
 * Output model for movie details operations in ApiPlatform
 *
 * This class is automatically mapped from GetMovieDetailsResponse DTO
 * using the AutoMapFromDto attribute - no manual mapper needed!
 */
#[AutoMapFromDto(GetMovieDetailsResponseDto::class)]
final readonly class MovieDetailsOutput {
    /**
     * @param bool                    $success
     * @param GetMovieDetailsResponseDto $data
     * @param string|null             $message
     */
    public function __construct(
        public bool $success,
        public GetMovieDetailsResponseDto $data,
        public ?string $message = null,
    ) {
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data): self {
        return new self(
            success: $data["success"] ?? false,
            data: $data["data"] ?? [],
            message: $data["message"] ?? null,
        );
    }
}
