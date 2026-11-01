<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Search;

use App\Application\Movie\DTO\Search\Request\SearchMoviesRequest;
use App\Application\Movie\DTO\Search\Response\SearchMoviesResponse;
use App\Application\Movie\Shared\MovieListItemMapper;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para busca de filmes por texto — separado dos
 * outros 4 endpoints "de listagem" (Trending/Popular/TopRated/
 * NowPlaying), que antes viviam nos métodos públicos getTrending()/
 * getPopular()/getTopRated()/getNowPlaying() dessa mesma classe.
 *
 * NOTA (out/2026):
 * - Depende da porta MovieDatabaseGatewayInterface, não mais do
 *   TmdbApiService concreto. O try/catch das exceções do HttpClient saiu:
 *   o adaptador já devolve exceção de domínio.
 * - Validação pelo RequestValidator, como os outros UseCases (antes era
 *   validator + ValidationException na mão; o status continua 400).
 * - region, year, primaryReleaseYear e language agora chegam ao TMDB.
 * - Mensagem com placeholders "{count}"/"{query}": o translator só
 *   substitui a chave exatamente como foi passada, e antes as chaves eram
 *   "count"/"query" (sem chaves), o que gerava "Found {12} movies...".
 */
final readonly class SearchMoviesUseCase {
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
     * @param \App\Application\Movie\DTO\Search\Request\SearchMoviesRequest $request
     *
     * @return \App\Application\Movie\DTO\Search\Response\SearchMoviesResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(SearchMoviesRequest $request): SearchMoviesResponse {
        $this->requestValidator->validate(
            $request,
            'movie_search_request',
            ['query' => $request->query, 'page' => $request->page],
        );

        $result = $this->movieDatabaseGateway->searchMovies(
            query: $request->query,
            page: $request->page,
            includeAdult: $request->includeAdult,
            language: $request->language,
            region: $request->region,
            year: $request->year,
            primaryReleaseYear: $request->primaryReleaseYear,
        );

        $list = $this->movieListItemMapper->mapPage(
            $result,
            $request->page,
            'TMDB Search API',
            ['query' => $request->query, 'page' => $request->page],
        );

        $this->logger->info('Movie search completed successfully', [
            'query' => $request->query,
            'page' => $list->page,
            'total_results' => $list->totalResults,
            'results_count' => count($list->movies),
        ]);

        return SearchMoviesResponse::success(
            movies: $list->movies,
            page: $list->page,
            totalPages: $list->totalPages,
            totalResults: $list->totalResults,
            message: $this->translator->trans(
                "Found {count} movies for '{query}'",
                [
                    '{count}' => $list->totalResults,
                    '{query}' => $request->query,
                ],
            ),
        );
    }
}
