<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\TopRated;

use App\Application\Movie\DTO\TopRated\Request\GetTopRatedMoviesRequest;
use App\Application\Movie\DTO\TopRated\Response\GetTopRatedMoviesResponse;
use App\Application\Movie\Shared\MovieListItemMapper;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para filmes mais bem avaliados — separado de
 * SearchMoviesUseCase (era o método público getTopRated()).
 *
 * NOTA (out/2026): recebe GetTopRatedMoviesRequest (antes era `int $page`
 * solto), valida pelo RequestValidator e depende da porta
 * MovieDatabaseGatewayInterface. O try/catch das exceções do HttpClient
 * saiu: o adaptador do TMDB já devolve exceção de domínio, e a checagem de
 * "results" ficou no MovieListItemMapper::mapPage().
 */
final readonly class GetTopRatedMoviesUseCase {
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
     * @param \App\Application\Movie\DTO\TopRated\Request\GetTopRatedMoviesRequest $request
     *
     * @return \App\Application\Movie\DTO\TopRated\Response\GetTopRatedMoviesResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(GetTopRatedMoviesRequest $request): GetTopRatedMoviesResponse {
        $this->requestValidator->validate(
            $request,
            'movie_top_rated_request',
            ['page' => $request->page],
        );

        $result = $this->movieDatabaseGateway->getTopRatedMovies(
            page: $request->page,
            language: $request->language,
        );

        $list = $this->movieListItemMapper->mapPage(
            $result,
            $request->page,
            'TMDB Top Rated API',
            ['page' => $request->page],
        );

        $this->logger->info('Top rated movies retrieved successfully', [
            'page' => $list->page,
            'results_count' => count($list->movies),
        ]);

        return GetTopRatedMoviesResponse::success(
            movies: $list->movies,
            page: $list->page,
            totalPages: $list->totalPages,
            totalResults: $list->totalResults,
            message: $this->translator->trans('Top rated movies retrieved successfully'),
        );
    }
}
