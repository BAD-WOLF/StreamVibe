<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Shared\Attribute;

use Attribute;

/**
 * Attribute to mark Output Models for automatic DTO mapping
 *
 * This attribute eliminates the need to create individual mappers for each DTO.
 * Simply add this attribute to your Output Model specifying which DTO it maps from,
 * and the system will automatically create a generic mapper using reflection.
 *
 * @example
 * #[AutoMapFromDto(SearchMoviesResponse::class)]
 * class SearchMoviesOutput { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AutoMapFromDto
{
    /**
     * @param class-string $dtoClass The DTO class to map from
     * @param array<string, string> $fieldMap Custom field mapping (outputField => dtoField)
     * @param array<string> $excludeFields Fields to exclude from automatic mapping
     * @param string $strategy Mapping strategy ('auto', 'strict', 'loose')
     * @param int $priority Priority for mapper registration (higher = earlier registration)
     * @param bool $enabled Whether this mapping should be active
     */
    public function __construct(
        public string $dtoClass,
        public array $fieldMap = [],
        public array $excludeFields = [],
        public string $strategy = 'auto',
        public int $priority = 0,
        public bool $enabled = true,
    ) {}
}
