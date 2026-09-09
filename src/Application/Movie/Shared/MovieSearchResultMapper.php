<?php

declare(strict_types = 1);

namespace App\Application\Movie\Shared;

use App\Application\Shared\DateYearExtractor;
use App\Application\Shared\TmdbImageUrlBuilder;

/**
 * Processa lista de filmes no formato usado por busca/tendência/
 * populares/mais bem avaliados/em cartaz — extraído de
 * SearchMoviesUseCase::processMovieResults(), que estava duplicado
 * (mesmo método privado, chamado internamente pelos 5 métodos
 * públicos que agora são UseCases separados).
 */
final readonly class MovieSearchResultMapper {
    /**
     * @param \App\Application\Shared\TmdbImageUrlBuilder         $imageUrlBuilder
     * @param \App\Application\Shared\DateYearExtractor           $yearExtractor
     * @param \App\Application\Movie\Shared\MovieRatingCalculator $ratingCalculator
     */
    public function __construct(
        private TmdbImageUrlBuilder $imageUrlBuilder,
        private DateYearExtractor $yearExtractor,
        private MovieRatingCalculator $ratingCalculator,
    ) {
    }

    /**
     * @param array $movies
     *
     * @return array
     */
    public function mapList(array $movies): array {
        return array_map($this->mapOne(...), $movies);
    }

    /**
     * @param array $movie
     *
     * @return array
     */
    public function mapOne(array $movie): array {
        return [
            'id' => $movie['id'] ?? null,
            'title' => $movie['title'] ?? null,
            'original_title' => $movie['original_title'] ?? null,
            'overview' => $movie['overview'] ?? null,
            'release_date' => $movie['release_date'] ?? null,
            'poster_path' => $movie['poster_path'] ?? null,
            'backdrop_path' => $movie['backdrop_path'] ?? null,
            'vote_average' => $movie['vote_average'] ?? 0,
            'vote_count' => $movie['vote_count'] ?? 0,
            'popularity' => $movie['popularity'] ?? 0,
            'genre_ids' => $movie['genre_ids'] ?? [],
            'adult' => $movie['adult'] ?? false,
            'video' => $movie['video'] ?? false,
            'original_language' => $movie['original_language'] ?? null,
            'poster_url' => $this->imageUrlBuilder->build(
                $movie['poster_path'] ?? null,
                'w500',
            ),
            'backdrop_url' => $this->imageUrlBuilder->build(
                $movie['backdrop_path'] ?? null,
                'w1280',
            ),
            'thumbnail_url' => $this->imageUrlBuilder->build(
                $movie['poster_path'] ?? null,
                'w342',
            ),
            'rating_percentage' => $this->ratingCalculator->calculatePercentage(
                $movie['vote_average'] ?? 0,
            ),
            'release_year' => $this->yearExtractor->extractYear(
                $movie['release_date'] ?? null,
            ),
            'formatted_overview' => $this->formatOverview(
                $movie['overview'] ?? null,
            ),
        ];
    }

    /**
     * @param string|null $overview
     *
     * @return string|null
     */
    private function formatOverview(?string $overview): ?string {
        if (empty($overview)) {
            return null;
        }

        if (strlen($overview) > 250) {
            return substr($overview, 0, 247) . '...';
        }

        return $overview;
    }
}
