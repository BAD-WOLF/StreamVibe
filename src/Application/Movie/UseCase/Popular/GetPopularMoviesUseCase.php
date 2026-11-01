<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Popular;

use App\Application\Movie\DTO\Popular\Request\GetPopularMoviesRequest;
use App\Application\Movie\DTO\Popular\Response\GetPopularMoviesResponse;
use App\Application\Movie\Shared\MovieListItemMapper;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para filmes populares — separado de
 * SearchMoviesUseCase (era o método público getPopular()).
 *
 * NOTA (out/2026): recebe GetPopularMoviesRequest (antes era `int $page`
 * solto), valida pelo RequestValidator e depende da porta
 * MovieDatabaseGatewayInterface. O try/catch das exceções do HttpClient
 * saiu: o adaptador do TMDB já devolve exceção de domínio, e a checagem de
 * "results" ficou no MovieListItemMapper::mapPage().
 */
final readonly class GetPopularMoviesUseCase {
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
     * @param \App\Application\Movie\DTO\Popular\Request\GetPopularMoviesRequest $request
     *
     * @return \App\Application\Movie\DTO\Popular\Response\GetPopularMoviesResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(GetPopularMoviesRequest $request): GetPopularMoviesResponse {
        $this->requestValidator->validate(
            $request,
            'movie_popular_request',
            ['page' => $request->page],
        );

        $result = $this->movieDatabaseGateway->getPopularMovies(
            page: $request->page,
            language: $request->language,
        );

        $list = $this->movieListItemMapper->mapPage(
            $result,
            $request->page,
            'TMDB Popular API',
            ['page' => $request->page],
        );

        $this->logger->info('Popular movies retrieved successfully', [
            'page' => $list->page,
            'results_count' => count($list->movies),
        ]);

        return GetPopularMoviesResponse::success(
            movies: $list->movies,
            page: $list->page,
            totalPages: $list->totalPages,
            totalResults: $list->totalResults,
            message: $this->translator->trans('Popular movies retrieved successfully'),
        );
    }
}
