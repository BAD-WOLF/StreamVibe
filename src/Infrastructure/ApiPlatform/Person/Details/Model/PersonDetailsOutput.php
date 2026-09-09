<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\Details\Model;

use App\Application\Person\DTO\Details\GetPersonDetailsResponse as GetPersonDetailsResponseDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Output model for person details operations in ApiPlatform
 *
 * This class is automatically mapped from GetPersonDetailsResponse DTO
 * using the AutoMapFromDto attribute - no manual mapper needed!
 */
#[AutoMapFromDto(GetPersonDetailsResponseDto::class)]
final readonly class PersonDetailsOutput {
    /**
     * @param bool                        $success
     * @param GetPersonDetailsResponseDto $data
     * @param string|null                 $message
     */
    public function __construct(
        public bool $success,
        public GetPersonDetailsResponseDto $data,
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
