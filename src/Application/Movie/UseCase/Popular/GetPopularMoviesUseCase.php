<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Popular;

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
 * Endpoint dedicado para filmes populares — separado de
 * SearchMoviesUseCase (era o método público getPopular()).
 */
final readonly class GetPopularMoviesUseCase {
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
     * @param int $page
     *
     * @return \App\Application\Movie\DTO\SearchMoviesResponse
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(int $page = 1): SearchMoviesResponse {
        try {
            $result = $this->tmdbApiService->getPopular($page);

            if (!isset($result['results']) || !is_array($result['results'])) {
                $this->logger->error(
                    'Invalid TMDB API response structure for popular',
                    [
                        'page' => $page,
                        'response_keys' => is_array($result)
                            ? array_keys($result)
                            : [],
                    ],
                );

                throw ExternalServiceException::invalidResponse(
                    'TMDB Popular API',
                    "Response missing 'results' array",
                    ['page' => $page],
                );
            }

            $movies = $this->resultMapper->mapList($result['results']);

            $this->logger->info('Popular movies retrieved successfully', [
                'page' => $page,
                'results_count' => count($movies),
            ]);

            return SearchMoviesResponse::success(
                movies: $movies,
                page: $result['page'] ?? $page,
                totalPages: $result['total_pages'] ?? 1,
                totalResults: $result['total_results'] ?? count($movies),
                message: $this->translator->trans(
                    'Popular movies retrieved successfully',
                ),
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching popular movies', [
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::clientError(
                'TMDB Popular API',
                $e->getMessage(),
                ['page' => $page],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching popular movies', [
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::serverError(
                'TMDB Popular API',
                $e->getMessage(),
                ['page' => $page],
            );
        } catch (ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching popular movies', [
                'page' => $page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                $this->translator->trans('Failed to fetch popular movies'),
                500,
                $e,
            );
        }
    }
}
