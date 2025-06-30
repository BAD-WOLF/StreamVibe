<?php

namespace App\ApiResource\Movies\Details\Model\PersonSummary\CastMember;

use ApiPlatform\Metadata\ApiProperty;

final class CastMember {
    #[ApiProperty]
    public bool $adult;

    #[ApiProperty]
    public int $gender;

    #[ApiProperty]
    public int $id;

    #[ApiProperty]
    public string $known_for_department;

    #[ApiProperty]
    public string $name;

    #[ApiProperty]
    public string $original_name;

    #[ApiProperty]
    public float $popularity;

    #[ApiProperty]
    public ?string $profile_path;

    #[ApiProperty]
    public int $cast_id;

    #[ApiProperty]
    public string $character;

    #[ApiProperty]
    public string $credit_id;

    #[ApiProperty]
    public int $order;
}