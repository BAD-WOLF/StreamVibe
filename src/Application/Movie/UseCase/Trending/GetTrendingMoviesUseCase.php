<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Trending;

use App\Application\Movie\DTO\SearchMoviesResponse;
use App\Application\Movie\Shared\MovieSearchResultMapper;
use App\Domain\Exception\ExternalServiceException;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Endpoint dedicado para filmes em tendência — separado de
 * SearchMoviesUseCase (era o método público getTrending()).
 */
final readonly class GetTrendingMoviesUseCase {
    /**
     * @param \App\Infrastructure\ExternalServices\TmdbApiService   $tmdbApiService
     * @param \Psr\Log\LoggerInterface                              $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface    $translator
     * @param \App\Application\Movie\Shared\MovieSearchResultMapper $resultMapper
     */
    public function __construct(
        private TmdbApiService $tmdbApiService,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private MovieSearchResultMapper $resultMapper,
    ) {
    }

    /**
     * @param string $timeWindow 'day' ou 'week'
     * @param int    $page
     *
     * @return \App\Application\Movie\DTO\SearchMoviesResponse
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(
        string $timeWindow = 'week',
        int $page = 1,
    ): SearchMoviesResponse {
        try {
            $result = $this->tmdbApiService->getTrending($timeWindow, $page);

            if (!isset($result['results']) || !is_array($result['results'])) {
                $this->logger->error(
                    'Invalid TMDB API response structure for trending',
                    [
                        'time_window' => $timeWindow,
                        'page' => $page,
                        'response_keys' => is_array($result)
                            ? array_keys($result)
                            : [],
                    ],
                );

                throw ExternalServiceException::invalidResponse(
                    'TMDB Trending API',
                    "Response missing 'results' array",
                    ['time_window' => $timeWindow, 'page' => $page],
                );
            }

            $movies = $this->resultMapper->mapList($result['results']);

            $this->logger->info('Trending movies retrieved successfully', [
                'time_window' => $timeWindow,
                'page' => $page,
                'results_count' => count($movies),
            ]);

            return SearchMoviesResponse::success(
                movies: $movies,
                page: $result['page'] ?? $page,
                totalPages: $result['total_pages'] ?? 1,
                totalResults: $result['total_results'] ?? count($movies),
                message: $this->translator->trans(
                    'Trending movies retrieved successfully',
                ),
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching trending movies', [
                'time_window' => $timeWindow,
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::clientError(
                'TMDB Trending API',
                $e->getMessage(),
                ['time_window' => $timeWindow, 'page' => $page],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching trending movies', [
                'time_window' => $timeWindow,
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::serverError(
                'TMDB Trending API',
                $e->getMessage(),
                ['time_window' => $timeWindow, 'page' => $page],
            );
        } catch (ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching trending movies', [
                'time_window' => $timeWindow,
                'page' => $page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                $this->translator->trans('Failed to fetch trending movies'),
                500,
                $e,
            );
        }
    }
}
