<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Search;

use App\Application\Movie\DTO\SearchMoviesRequest;
use App\Application\Movie\DTO\SearchMoviesResponse;
use App\Application\Movie\Shared\MovieSearchResultMapper;
use App\Domain\Exception\ValidationException;
use App\Domain\Exception\ExternalServiceException;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Endpoint dedicado para busca de filmes por texto — separado dos
 * outros 4 endpoints "de listagem" (Trending/Popular/TopRated/
 * NowPlaying), que antes viviam nos métodos públicos getTrending()/
 * getPopular()/getTopRated()/getNowPlaying() dessa mesma classe.
 */
final readonly class SearchMoviesUseCase {
    /**
     * @param \App\Infrastructure\ExternalServices\TmdbApiService       $tmdbApiService
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Psr\Log\LoggerInterface                                  $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface        $translator
     * @param \App\Application\Movie\Shared\MovieSearchResultMapper     $resultMapper
     */
    public function __construct(
        private TmdbApiService $tmdbApiService,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private MovieSearchResultMapper $resultMapper,
    ) {
    }

    /**
     * @param \App\Application\Movie\DTO\SearchMoviesRequest $request
     *
     * @return \App\Application\Movie\DTO\SearchMoviesResponse
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\ValidationException
     */
    public function execute(SearchMoviesRequest $request): SearchMoviesResponse {
        $violations = $this->validator->validate($request);
        if (count($violations) > 0) {
            $this->logger->warning('Movie search request validation failed', [
                'query' => $request->getQuery(),
                'page' => $request->getPage(),
                'violations_count' => count($violations),
            ]);

            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            throw ValidationException::withErrors(
                $errors,
                $this->translator->trans('Invalid search parameters'),
            );
        }

        try {
            $result = $this->tmdbApiService->searchMovies(
                query: $request->getQuery(),
                page: $request->getPage(),
                includeAdult: $request->shouldIncludeAdult(),
            );

            if (!isset($result['results']) || !is_array($result['results'])) {
                $this->logger->error(
                    'Invalid TMDB API response structure for search',
                    [
                        'query' => $request->getQuery(),
                        'page' => $request->getPage(),
                        'response_keys' => is_array($result)
                            ? array_keys($result)
                            : [],
                    ],
                );

                throw ExternalServiceException::invalidResponse(
                    'TMDB Search API',
                    "Response missing 'results' array",
                    [
                        'query' => $request->getQuery(),
                        'page' => $request->getPage(),
                    ],
                );
            }

            $movies = $this->resultMapper->mapList($result['results']);

            $totalPages = $result['total_pages'] ?? 1;
            $totalResults = $result['total_results'] ?? count($movies);
            $currentPage = $result['page'] ?? $request->getPage();

            $this->logger->info('Movie search completed successfully', [
                'query' => $request->getQuery(),
                'page' => $currentPage,
                'total_results' => $totalResults,
                'results_count' => count($movies),
            ]);

            return SearchMoviesResponse::success(
                movies: $movies,
                page: $currentPage,
                totalPages: $totalPages,
                totalResults: $totalResults,
                message: $this->translator->trans(
                    "Found {count} movies for '{query}'",
                    [
                        'count' => $totalResults,
                        'query' => $request->getQuery(),
                    ],
                ),
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error during movie search from TMDB', [
                'query' => $request->getQuery(),
                'page' => $request->getPage(),
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::clientError(
                'TMDB Search API',
                $e->getMessage(),
                ['query' => $request->getQuery(), 'page' => $request->getPage()],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error during movie search from TMDB', [
                'query' => $request->getQuery(),
                'page' => $request->getPage(),
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::serverError(
                'TMDB Search API',
                $e->getMessage(),
                ['query' => $request->getQuery(), 'page' => $request->getPage()],
            );
        } catch (ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error during movie search', [
                'query' => $request->getQuery(),
                'page' => $request->getPage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                $this->translator->trans(
                    'An error occurred while searching for movies',
                ),
                500,
                $e,
            );
        }
    }
}
