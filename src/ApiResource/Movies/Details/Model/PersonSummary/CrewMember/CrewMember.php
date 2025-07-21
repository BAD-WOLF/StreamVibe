<?php

namespace App\ApiResource\Movies\Details\Model\PersonSummary\CrewMember;

use ApiPlatform\Metadata\ApiProperty;

final class CrewMember {
    #[ApiProperty]
    public private(set) bool $adult;

    #[ApiProperty]
    public private(set) int $gender;

    #[ApiProperty]
    public private(set) int $id;

    #[ApiProperty]
    public private(set) string $known_for_department;

    #[ApiProperty]
    public private(set) string $name;

    #[ApiProperty]
    public private(set) string $original_name;

    #[ApiProperty]
    public private(set) float $popularity;

    #[ApiProperty]
    public private(set) ?string $profile_path;

    #[ApiProperty]
    public private(set) string $credit_id;

    #[ApiProperty]
    public private(set) string $department;

    #[ApiProperty]
    public private(set) string $job;
}