<?php

namespace App\ApiResource\Person\Details\Model\MoviesSummary\CrewMember;

use ApiPlatform\Metadata\ApiProperty;

final class CrewMember {
    #[ApiProperty]
    public private(set) bool $adult;

    #[ApiProperty]
    public private(set) ?string $backdrop_path = null;

    /**
     * @var array<int> $genre_ids
     */
    #[ApiProperty]
    public private(set) array $genre_ids;

    #[ApiProperty]
    public private(set) int $id;

    #[ApiProperty]
    public private(set) string $original_language;

    #[ApiProperty]
    public private(set) string $original_title;

    #[ApiProperty]
    public private(set) string $overview;

    #[ApiProperty]
    public private(set) float $popularity;

    #[ApiProperty]
    public private(set) ?string $poster_path = null;

    #[ApiProperty]
    public private(set) string $release_date;

    #[ApiProperty]
    public private(set) string $title;

    #[ApiProperty]
    public private(set) bool $video;

    #[ApiProperty]
    public private(set) float $vote_average;

    #[ApiProperty]
    public private(set) int $vote_count;

    #[ApiProperty]
    public private(set) string $credit_id;

    #[ApiProperty]
    public private(set) string $department;

    #[ApiProperty]
    public private(set) string $job;
}