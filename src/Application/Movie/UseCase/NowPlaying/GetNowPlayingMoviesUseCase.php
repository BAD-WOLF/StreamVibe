<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\NowPlaying;

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
 * Endpoint dedicado para filmes em cartaz — separado de
 * SearchMoviesUseCase (era o método público getNowPlaying()).
 *
 * NOTA (ago/2026): mesma correção de inconsistência aplicada em
 * GetTopRatedMoviesUseCase — getNowPlaying() original também nunca
 * lançava, sempre devolvia failure(). Agora lança, igual aos outros 4.
 */
final readonly class GetNowPlayingMoviesUseCase {
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
            $result = $this->tmdbApiService->getNowPlaying($page);

            if (!isset($result['results']) || !is_array($result['results'])) {
                $this->logger->error(
                    'Invalid TMDB API response structure for now playing',
                    [
                        'page' => $page,
                        'response_keys' => is_array($result)
                            ? array_keys($result)
                            : [],
                    ],
                );

                throw ExternalServiceException::invalidResponse(
                    'TMDB Now Playing API',
                    "Response missing 'results' array",
                    ['page' => $page],
                );
            }

            $movies = $this->resultMapper->mapList($result['results']);

            $this->logger->info('Now playing movies retrieved successfully', [
                'page' => $page,
                'results_count' => count($movies),
            ]);

            return SearchMoviesResponse::success(
                movies: $movies,
                page: $result['page'] ?? $page,
                totalPages: $result['total_pages'] ?? 1,
                totalResults: $result['total_results'] ?? count($movies),
                message: $this->translator->trans(
                    'Now playing movies retrieved successfully',
                ),
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching now playing movies', [
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::clientError(
                'TMDB Now Playing API',
                $e->getMessage(),
                ['page' => $page],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching now playing movies', [
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::serverError(
                'TMDB Now Playing API',
                $e->getMessage(),
                ['page' => $page],
            );
        } catch (ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error(
                'Unexpected error fetching now playing movies',
                [
                    'page' => $page,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            );

            throw new RuntimeException(
                $this->translator->trans('Failed to fetch now playing movies'),
                500,
                $e,
            );
        }
    }
}
