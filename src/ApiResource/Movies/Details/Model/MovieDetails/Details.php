<?php

namespace App\ApiResource\Movies\Details\Model\MovieDetails;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Movies\Details\Model\MovieDetails\Genres\Genres;
use App\ApiResource\Movies\Details\Model\MovieDetails\OriginCountry\OriginCountry;
use App\ApiResource\Movies\Details\Model\MovieDetails\ProductionCompanies\ProductionCompany;
use App\ApiResource\Movies\Details\Model\MovieDetails\BelongsToCollection\BelongsToCollection;
use App\ApiResource\Movies\Details\Model\MovieDetails\ProductionCountries\ProductionCountry;
use App\ApiResource\Movies\Details\Model\MovieDetails\SpokenLanguages\SpokenLanguage;

final class Details {
    #[ApiProperty]
    public private(set) bool $adult;

    #[ApiProperty]
    public private(set) ?string $backdrop_path;

    #[ApiProperty]
    public private(set) ?BelongsToCollection $belongs_to_collection;

    #[ApiProperty]
    public private(set) int $budget;

    #[ApiProperty]
    public private(set) Genres $genres;

    #[ApiProperty]
    public private(set) string $homepage;

    #[ApiProperty]
    public private(set) int $id;

    #[ApiProperty]
    public private(set) ?string $imdb_id;

    #[ApiProperty]
    public private(set) OriginCountry $origin_country;

    #[ApiProperty]
    public private(set) string $original_language;

    #[ApiProperty]
    public private(set) string $original_title;

    #[ApiProperty]
    public private(set) string $overview;

    #[ApiProperty]
    public private(set) float $popularity;

    #[ApiProperty]
    public private(set) string $poster_path;

    #[ApiProperty]
    public private(set) ProductionCompany $production_companies;

    #[ApiProperty]
    public private(set) ProductionCountry $production_countries;

    #[ApiProperty]
    public private(set) string $release_date;

    #[ApiProperty]
    public private(set) int $revenue;

    #[ApiProperty]
    public private(set) int $runtime;

    #[ApiProperty]
    public private(set) SpokenLanguage $spoken_languages;

    #[ApiProperty]
    public private(set) string $status;

    #[ApiProperty]
    public private(set) string $tagline;

    #[ApiProperty]
    public private(set) string $title;

    #[ApiProperty]
    public private(set) bool $video;

    #[ApiProperty]
    public private(set) float $vote_average;

    #[ApiProperty]
    public private(set) int $vote_count;
}
