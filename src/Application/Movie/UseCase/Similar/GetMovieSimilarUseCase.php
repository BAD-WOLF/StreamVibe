<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Similar;

use App\Application\Movie\DTO\Similar\Request\GetMovieSimilarRequest;
use App\Application\Movie\DTO\Similar\Response\GetMovieSimilarResponse;
use App\Application\Movie\Shared\MovieListItemMapper;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para filmes similares — separado de
 * GetMovieDetailsUseCase. Chama diretamente o TMDB, sem buscar detalhes
 * completos do filme antes.
 *
 * NOTA (out/2026):
 * - Depende da porta MovieDatabaseGatewayInterface e não tem mais o
 *   try/catch das exceções do HttpClient.
 * - O processMovieList() privado (cópia do que existia em
 *   GetMovieDetailsUseCase, duplicado também no outro UseCase de lista
 *   por filme) saiu: os itens vêm do MovieListItemMapper, o mesmo da busca.
 * - A página pedida (?page=) chega ao TMDB; antes era sempre a 1.
 */
final readonly class GetMovieSimilarUseCase {
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
     * @param \App\Application\Movie\DTO\Similar\Request\GetMovieSimilarRequest $request
     *
     * @return \App\Application\Movie\DTO\Similar\Response\GetMovieSimilarResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(GetMovieSimilarRequest $request): GetMovieSimilarResponse {
        $this->requestValidator->validate(
            $request,
            'movie_similar_request',
            ['movie_id' => $request->movieId, 'page' => $request->page],
        );

        $result = $this->movieDatabaseGateway->getSimilarMovies(
            movieId: $request->movieId,
            page: $request->page,
            language: $request->language,
        );

        $list = $this->movieListItemMapper->mapPage(
            $result,
            $request->page,
            'TMDB Movie API',
            ['movie_id' => $request->movieId, 'page' => $request->page],
        );

        $this->logger->info('Similar movies retrieved successfully', [
            'movie_id' => $request->movieId,
            'page' => $list->page,
            'results_count' => count($list->movies),
        ]);

        return GetMovieSimilarResponse::success(
            movieId: $request->movieId,
            movies: $list->movies,
            page: $list->page,
            totalPages: $list->totalPages,
            totalResults: $list->totalResults,
            message: $this->translator->trans('Similar movies retrieved successfully'),
        );
    }
}
