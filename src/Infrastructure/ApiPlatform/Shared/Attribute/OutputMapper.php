<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Shared\Attribute;

use Attribute;

/**
 * Attribute to mark classes as output mappers for automatic discovery
 *
 * This attribute eliminates the need to manually register mappers in services.yaml.
 * Classes marked with this attribute will be automatically discovered and registered
 * in the OutputMapperRegistry during container compilation.
 *
 * @example
 * #[OutputMapper]
 * class SearchMoviesOutputMapper implements OutputMapperInterface { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class OutputMapper
{
    /**
     * @param int $priority Priority for mapper registration (higher = earlier registration)
     * @param bool $enabled Whether this mapper should be registered (useful for conditional registration)
     */
    public function __construct(
        public int $priority = 0,
        public bool $enabled = true,
    ) {}
}
