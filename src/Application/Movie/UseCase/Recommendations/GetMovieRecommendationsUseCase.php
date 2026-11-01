<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Recommendations;

use App\Application\Movie\DTO\Recommendations\Request\GetMovieRecommendationsRequest;
use App\Application\Movie\DTO\Recommendations\Response\GetMovieRecommendationsResponse;
use App\Application\Movie\Shared\MovieListItemMapper;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para recomendações de filme — separado de
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
final readonly class GetMovieRecommendationsUseCase {
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
     * @param \App\Application\Movie\DTO\Recommendations\Request\GetMovieRecommendationsRequest $request
     *
     * @return \App\Application\Movie\DTO\Recommendations\Response\GetMovieRecommendationsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(GetMovieRecommendationsRequest $request): GetMovieRecommendationsResponse {
        $this->requestValidator->validate(
            $request,
            'movie_recommendations_request',
            ['movie_id' => $request->movieId, 'page' => $request->page],
        );

        $result = $this->movieDatabaseGateway->getMovieRecommendations(
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

        $this->logger->info('Movie recommendations retrieved successfully', [
            'movie_id' => $request->movieId,
            'page' => $list->page,
            'results_count' => count($list->movies),
        ]);

        return GetMovieRecommendationsResponse::success(
            movieId: $request->movieId,
            movies: $list->movies,
            page: $list->page,
            totalPages: $list->totalPages,
            totalResults: $list->totalResults,
            message: $this->translator->trans('Movie recommendations retrieved successfully'),
        );
    }
}
