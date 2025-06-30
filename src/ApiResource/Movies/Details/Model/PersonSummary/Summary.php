<?php

namespace App\ApiResource\Movies\Details\Model\PersonSummary;

use App\ApiResource\Movies\Details\Model\PersonSummary\CastMember\CastMember;
use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Movies\Details\Model\PersonSummary\CrewMember\CrewMember;

final class Summary {
    #[ApiProperty]
    public int $id;

    #[ApiProperty]
    /** @var CastMember[] $cast */
    public array $cast;

    #[ApiProperty]
    /** @var CrewMember[] $crew */
    public array $crew;
}