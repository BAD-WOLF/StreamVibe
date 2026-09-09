<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Similar;

use App\Application\Movie\DTO\GetMovieSimilarResponse;
use App\Domain\Exception\ExternalServiceException;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Endpoint dedicado para filmes similares — separado de
 * GetMovieDetailsUseCase. Chama diretamente
 * TmdbApiService::getSimilarMovies(), sem buscar detalhes completos do
 * filme antes.
 */
final readonly class GetMovieSimilarUseCase {
    /**
     * @param \App\Infrastructure\ExternalServices\TmdbApiService $tmdbApiService
     * @param \Psr\Log\LoggerInterface                            $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private TmdbApiService $tmdbApiService,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param int $movieId
     *
     * @return \App\Application\Movie\DTO\GetMovieSimilarResponse
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(int $movieId): GetMovieSimilarResponse {
        try {
            $result = $this->tmdbApiService->getSimilarMovies($movieId);

            // NOTA: /movie/{id}/similar é resposta paginada, sem "id"
            // no topo — mesmo raciocínio de GetMovieRecommendationsUseCase.
            if (!isset($result['results']) || !is_array($result['results'])) {
                $this->logger->error(
                    'Invalid TMDB API response structure for similar movies',
                    [
                        'movie_id' => $movieId,
                        'response_keys' => is_array($result)
                            ? array_keys($result)
                            : [],
                    ],
                );

                throw ExternalServiceException::invalidResponse(
                    'TMDB Movie API',
                    "Response missing 'results' array",
                    ['movie_id' => $movieId],
                );
            }

            $movies = $this->processMovieList($result['results']);

            $this->logger->info('Similar movies retrieved successfully', [
                'movie_id' => $movieId,
                'results_count' => count($movies),
            ]);

            return GetMovieSimilarResponse::success(
                similar: $movies,
                movieId: $movieId,
                page: $result['page'] ?? 1,
                totalPages: $result['total_pages'] ?? 1,
                totalResults: $result['total_results'] ?? count($movies),
                results: $movies,
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching similar movies', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::clientError(
                'TMDB Movie API',
                $e->getMessage(),
                ['movie_id' => $movieId],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching similar movies', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::serverError(
                'TMDB Movie API',
                $e->getMessage(),
                ['movie_id' => $movieId],
            );
        } catch (ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching similar movies', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                $this->translator->trans(
                    'An error occurred while fetching similar movies',
                ),
                500,
                $e,
            );
        }
    }

    /**
     * Process movie list (movida de GetMovieDetailsUseCase, sem
     * alteração de lógica)
     */
    private function processMovieList(array $movies): array {
        return array_map(function (array $movie) {
            return [
                'id' => $movie['id'] ?? null,
                'title' => $movie['title'] ?? null,
                'overview' => $movie['overview'] ?? null,
                'release_date' => $movie['release_date'] ?? null,
                'poster_path' => $movie['poster_path'] ?? null,
                'backdrop_path' => $movie['backdrop_path'] ?? null,
                'vote_average' => $movie['vote_average'] ?? 0,
                'vote_count' => $movie['vote_count'] ?? 0,
                'popularity' => $movie['popularity'] ?? 0,
                'poster_url' => $this->buildImageUrl(
                    $movie['poster_path'] ?? null,
                    'w342',
                ),
                'backdrop_url' => $this->buildImageUrl(
                    $movie['backdrop_path'] ?? null,
                    'w780',
                ),
                'rating_percentage' => $this->calculateRatingPercentage(
                    $movie['vote_average'] ?? 0,
                ),
                'release_year' => $this->extractReleaseYear(
                    $movie['release_date'] ?? null,
                ),
            ];
        }, $movies);
    }

    private function buildImageUrl(
        ?string $path,
        string $size = 'original',
    ): ?string {
        if (empty($path)) {
            return null;
        }

        return "https://image.tmdb.org/t/p/{$size}{$path}";
    }

    private function calculateRatingPercentage(float $voteAverage): int {
        return (int) round(($voteAverage / 10) * 100);
    }

    private function extractReleaseYear(?string $releaseDate): ?int {
        if (empty($releaseDate)) {
            return null;
        }

        $year = substr($releaseDate, 0, 4);

        return is_numeric($year) ? (int) $year : null;
    }
}
