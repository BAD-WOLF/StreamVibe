<?php

namespace App\ApiResource\Movies\Search;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\Movies\Search\Model\SearchMovieItem\Movie;

final class SearchMoviesOutput {
    #[ApiProperty]
    public int $page;

    /** @var Movie[] $results */
    #[ApiProperty]
    public array $results;

    #[ApiProperty]
    public int $total_pages;

    #[ApiProperty]
    public int $total_results;
}