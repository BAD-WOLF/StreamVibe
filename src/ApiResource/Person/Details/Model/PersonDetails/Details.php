<?php

namespace App\ApiResource\Person\Details\Model\PersonDetails;

use ApiPlatform\Metadata\ApiProperty;

final class Details {
    #[ApiProperty]
    public private(set) bool $adult;

    /**
     * @var string[]
     */
    #[ApiProperty]
    public private(set) array $also_known_as;

    #[ApiProperty]
    public private(set) string $biography;

    #[ApiProperty]
    public private(set) ?string $birthday = null;

    #[ApiProperty]
    public private(set) ?string $deathday = null;

    #[ApiProperty]
    public private(set) int $gender;

    #[ApiProperty]
    public private(set) ?string $homepage = null;

    #[ApiProperty]
    public private(set) int $id;

    #[ApiProperty]
    public private(set) ?string $imdb_id = null;

    #[ApiProperty]
    public private(set) string $known_for_department;

    #[ApiProperty]
    public private(set) string $name;

    #[ApiProperty]
    public private(set) ?string $place_of_birth = null;

    #[ApiProperty]
    public private(set) float $popularity;

    #[ApiProperty]
    public private(set) ?string $profile_path = null;
}
