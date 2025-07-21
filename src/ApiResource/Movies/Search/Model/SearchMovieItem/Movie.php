<?php

namespace App\ApiResource\Movies\Search\Model\SearchMovieItem;

use ApiPlatform\Metadata\ApiProperty;

final class Movie {
    #[ApiProperty]
    public private(set) bool $adult;

    #[ApiProperty]
    public private(set) ?string $backdrop_path;

    /** @var int[] $genre_ids */
    #[ApiProperty]
    public private(set) array $genre_ids;

    #[ApiProperty(identifier: true)]
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
    public private(set) ?string $poster_path;

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
}