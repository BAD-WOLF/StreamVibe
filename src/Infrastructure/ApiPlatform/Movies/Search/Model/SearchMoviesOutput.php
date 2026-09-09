<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Search\Model;

use App\Application\Movie\DTO\SearchMoviesResponse as SearchMoviesResponseDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Output model for movie search operations in ApiPlatform
 *
 * This class is automatically mapped from SearchMoviesResponse DTO
 * using the AutoMapFromDto attribute - no manual mapper needed!
 */
#[AutoMapFromDto(SearchMoviesResponseDto::class)]
final readonly class SearchMoviesOutput {
    /**
     * @param bool                    $success
     * @param SearchMoviesResponseDto $data
     * @param string|null             $message
     */
    public function __construct(
        public bool $success,
        public SearchMoviesResponseDto $data,
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
