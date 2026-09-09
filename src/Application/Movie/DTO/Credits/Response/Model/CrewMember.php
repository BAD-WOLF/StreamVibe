<?php

namespace App\Application\Movie\DTO\Credits\Response\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\NotExposed;

/**
 * NOTA: mesmo motivo do #[NotExposed] em CastMember.
 */
#[NotExposed]
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

    public function __construct(
        bool $adult,
        int $gender,
        int $id,
        string $known_for_department,
        string $name,
        string $original_name,
        float $popularity,
        ?string $profile_path,
        string $credit_id,
        string $department,
        string $job,
    ) {
        $this->adult = $adult;
        $this->gender = $gender;
        $this->id = $id;
        $this->known_for_department = $known_for_department;
        $this->name = $name;
        $this->original_name = $original_name;
        $this->popularity = $popularity;
        $this->profile_path = $profile_path;
        $this->credit_id = $credit_id;
        $this->department = $department;
        $this->job = $job;
    }
}
