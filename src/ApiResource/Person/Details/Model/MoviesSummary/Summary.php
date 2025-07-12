<?php

namespace App\ApiResource\Person\Details\Model\MoviesSummary;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Person\Details\Model\MoviesSummary\CastMember\CastMember;
use App\ApiResource\Person\Details\Model\MoviesSummary\CrewMember\CrewMember;

final class Summary
{
    /**
     * @var CastMember[]
     */
    #[ApiProperty]
    public array $cast;

    /**
     * @var CrewMember[]
     */
    #[ApiProperty]
    public array $crew;

    #[ApiProperty]
    public int $id;
}