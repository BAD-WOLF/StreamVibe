<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Trending;

use App\Application\Movie\DTO\Trending\Request\GetTrendingMoviesRequest;
use App\Application\Movie\DTO\Trending\Response\GetTrendingMoviesResponse;
use App\Application\Movie\Shared\MovieListItemMapper;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para filmes em tendência — separado de
 * SearchMoviesUseCase (era o método público getTrending()).
 *
 * NOTA (out/2026): recebe GetTrendingMoviesRequest (antes eram
 * argumentos soltos) e depende da porta MovieDatabaseGatewayInterface.
 * O time window que chega ao TMDB é o da rota (/trending/{timeWindow}).
 */
final readonly class GetTrendingMoviesUseCase {
    /**
     * @param \App\Application\Shared\Gateway\MovieDatabaseGatewayInterface $movieDatabaseGateway
     * @param \App\Application\Shared\RequestValidator                      $requestValidator
     * @param \App\Application\Movie\Shared\MovieListItemMapper             $movieListItemMapper
     * @param \Psr\Log\LoggerInterface                                      $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private MovieDatabaseGatewayInterface $movieDatabaseGateway,
        private RequestValidator $requestValidator,
        private MovieListItemMapper $movieListItemMapper,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Movie\DTO\Trending\Request\GetTrendingMoviesRequest $request
     *
     * @return \App\Application\Movie\DTO\Trending\Response\GetTrendingMoviesResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(GetTrendingMoviesRequest $request): GetTrendingMoviesResponse {
        $this->requestValidator->validate(
            $request,
            'movie_trending_request',
            ['time_window' => $request->timeWindow, 'page' => $request->page],
        );

        $result = $this->movieDatabaseGateway->getTrendingMovies(
            timeWindow: $request->timeWindow,
            page: $request->page,
            language: $request->language,
        );

        $list = $this->movieListItemMapper->mapPage(
            $result,
            $request->page,
            'TMDB Trending API',
            ['time_window' => $request->timeWindow, 'page' => $request->page],
        );

        $this->logger->info('Trending movies retrieved successfully', [
            'time_window' => $request->timeWindow,
            'page' => $list->page,
            'results_count' => count($list->movies),
        ]);

        return GetTrendingMoviesResponse::success(
            timeWindow: $request->timeWindow,
            movies: $list->movies,
            page: $list->page,
            totalPages: $list->totalPages,
            totalResults: $list->totalResults,
            message: $this->translator->trans(
                'Trending movies retrieved successfully',
            ),
        );
    }
}
