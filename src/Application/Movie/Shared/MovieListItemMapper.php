<?php

declare(strict_types = 1);

namespace App\Application\Movie\Shared;

use App\Application\Movie\DTO\Shared\Response\Model\MovieListItem;
use App\Application\Shared\DateYearExtractor;
use App\Application\Shared\RatingPercentageCalculator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\ExternalServiceException;

/**
 * Converte um filme de lista do TMDB (busca, tendência, populares, mais
 * bem avaliados, em cartaz, similares e recomendações) no item que a API
 * devolve em `data.movies` (MovieListItem).
 *
 * NOTA (out/2026): era o MovieSearchResultMapper (extraído antes de
 * SearchMoviesUseCase::processMovieResults()). Similar e Recommendations
 * tinham cada um uma cópia privada de processMovieList(), com menos
 * campos e tamanhos de imagem diferentes (w342/w780). Agora os 7 endpoints
 * de lista usam este mapper, então o item de filme é o mesmo em toda a
 * API. O nome mudou porque deixou de ser só da busca.
 *
 * - mapOne() devolve um MovieListItem (antes um array com as mesmas
 *   chaves): o item ganhou tipo e documentação no Swagger.
 * - formatted_overview passou a cortar com mb_substr(): substr() conta
 *   bytes e podia partir um caractere acentuado ao meio, gerando UTF-8
 *   inválido — e aí o json_encode da resposta falha (500). Com `language`
 *   agora chegando ao TMDB (ex.: pt-BR), sinopse acentuada é o caso comum.
 */
final readonly class MovieListItemMapper {
    private const int OVERVIEW_MAX_LENGTH = 250;

    /**
     * @param \App\Application\Shared\TmdbImageUrlBuilder        $imageUrlBuilder
     * @param \App\Application\Shared\DateYearExtractor          $yearExtractor
     * @param \App\Application\Shared\RatingPercentageCalculator $ratingCalculator
     */
    public function __construct(
        private TmdbImageUrlBuilder $imageUrlBuilder,
        private DateYearExtractor $yearExtractor,
        private RatingPercentageCalculator $ratingCalculator,
    ) {
    }

    /**
     * Converte uma resposta paginada do TMDB (page, results, total_pages,
     * total_results) numa MovieListPage.
     *
     * @param array<string, mixed> $payload       resposta do TMDB
     * @param int    $requestedPage página pedida (usada se o TMDB não devolver "page")
     * @param string $source        nome do endpoint do TMDB, para a mensagem de erro
     * @param array<string, mixed> $context       dados extras para o contexto do erro
     *
     * @return \App\Application\Movie\Shared\MovieListPage
     * @throws \App\Domain\Exception\ExternalServiceException quando falta o array "results"
     */
    public function mapPage(
        array $payload,
        int $requestedPage,
        string $source,
        array $context = [],
    ): MovieListPage {
        if (!isset($payload['results']) || !is_array($payload['results'])) {
            throw ExternalServiceException::invalidResponse(
                $source,
                "Response missing 'results' array",
                $context + ['response_keys' => array_keys($payload)],
            );
        }

        $movies = $this->mapList($payload['results']);

        return new MovieListPage(
            movies: $movies,
            page: (int) ($payload['page'] ?? $requestedPage),
            totalPages: (int) ($payload['total_pages'] ?? 1),
            totalResults: (int) ($payload['total_results'] ?? count($movies)),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $movies filmes no formato do TMDB
     *
     * @return list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem>
     */
    public function mapList(array $movies): array {
        return array_values(array_map($this->mapOne(...), $movies));
    }

    /**
     * @param array<string, mixed> $movie filme no formato do TMDB
     *
     * @return \App\Application\Movie\DTO\Shared\Response\Model\MovieListItem
     */
    public function mapOne(array $movie): MovieListItem {
        $posterPath = $movie['poster_path'] ?? null;
        $backdropPath = $movie['backdrop_path'] ?? null;
        $voteAverage = (float) ($movie['vote_average'] ?? 0);

        return new MovieListItem(
            id: (int) ($movie['id'] ?? 0),
            title: (string) ($movie['title'] ?? ''),
            original_title: (string) ($movie['original_title'] ?? ''),
            overview: (string) ($movie['overview'] ?? ''),
            release_date: $movie['release_date'] ?? null,
            poster_path: $posterPath,
            backdrop_path: $backdropPath,
            vote_average: $voteAverage,
            vote_count: (int) ($movie['vote_count'] ?? 0),
            popularity: (float) ($movie['popularity'] ?? 0),
            genre_ids: array_map('intval', $movie['genre_ids'] ?? []),
            adult: (bool) ($movie['adult'] ?? false),
            video: (bool) ($movie['video'] ?? false),
            original_language: (string) ($movie['original_language'] ?? ''),
            poster_url: $this->imageUrlBuilder->build($posterPath, 'w500'),
            backdrop_url: $this->imageUrlBuilder->build($backdropPath, 'w1280'),
            thumbnail_url: $this->imageUrlBuilder->build($posterPath, 'w342'),
            rating_percentage: $this->ratingCalculator->calculatePercentage($voteAverage),
            release_year: $this->yearExtractor->extractYear($movie['release_date'] ?? null),
            formatted_overview: $this->formatOverview($movie['overview'] ?? null),
        );
    }

    /**
     * @param string|null $overview
     *
     * @return string|null a sinopse, cortada em OVERVIEW_MAX_LENGTH caracteres com "..."
     */
    private function formatOverview(?string $overview): ?string {
        if ($overview === null || $overview === '') {
            return null;
        }

        if (mb_strlen($overview) > self::OVERVIEW_MAX_LENGTH) {
            return mb_substr($overview, 0, self::OVERVIEW_MAX_LENGTH - 3) . '...';
        }

        return $overview;
    }
}
