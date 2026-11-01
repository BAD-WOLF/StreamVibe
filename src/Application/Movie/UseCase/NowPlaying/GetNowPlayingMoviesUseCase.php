<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\NowPlaying;

use App\Application\Movie\DTO\NowPlaying\Request\GetNowPlayingMoviesRequest;
use App\Application\Movie\DTO\NowPlaying\Response\GetNowPlayingMoviesResponse;
use App\Application\Movie\Shared\MovieListItemMapper;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para filmes em cartaz — separado de
 * SearchMoviesUseCase (era o método público getNowPlaying()).
 *
 * NOTA (out/2026): recebe GetNowPlayingMoviesRequest (antes era `int $page`
 * solto), valida pelo RequestValidator e depende da porta
 * MovieDatabaseGatewayInterface. O try/catch das exceções do HttpClient
 * saiu: o adaptador do TMDB já devolve exceção de domínio, e a checagem de
 * "results" ficou no MovieListItemMapper::mapPage().
 */
final readonly class GetNowPlayingMoviesUseCase {
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
     * @param \App\Application\Movie\DTO\NowPlaying\Request\GetNowPlayingMoviesRequest $request
     *
     * @return \App\Application\Movie\DTO\NowPlaying\Response\GetNowPlayingMoviesResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(GetNowPlayingMoviesRequest $request): GetNowPlayingMoviesResponse {
        $this->requestValidator->validate(
            $request,
            'movie_now_playing_request',
            ['page' => $request->page],
        );

        $result = $this->movieDatabaseGateway->getNowPlayingMovies(
            page: $request->page,
            language: $request->language,
        );

        $list = $this->movieListItemMapper->mapPage(
            $result,
            $request->page,
            'TMDB Now Playing API',
            ['page' => $request->page],
        );

        $this->logger->info('Now playing movies retrieved successfully', [
            'page' => $list->page,
            'results_count' => count($list->movies),
        ]);

        return GetNowPlayingMoviesResponse::success(
            movies: $list->movies,
            page: $list->page,
            totalPages: $list->totalPages,
            totalResults: $list->totalResults,
            message: $this->translator->trans('Now playing movies retrieved successfully'),
        );
    }
}
