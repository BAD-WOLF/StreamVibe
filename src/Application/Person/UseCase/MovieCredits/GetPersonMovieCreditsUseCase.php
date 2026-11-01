<?php

declare(strict_types = 1);

namespace App\Application\Person\UseCase\MovieCredits;

use App\Application\Person\DTO\MovieCredits\Request\GetPersonMovieCreditsRequest;
use App\Application\Person\DTO\MovieCredits\Response\GetPersonMovieCreditsResponse;
use App\Application\Person\DTO\MovieCredits\Response\Model\MovieCastCredit;
use App\Application\Person\DTO\MovieCredits\Response\Model\MovieCrewCredit;
use App\Application\Shared\DateYearExtractor;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RatingPercentageCalculator;
use App\Application\Shared\RequestValidator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para créditos de filme de uma pessoa — separado de
 * GetPersonDetailsUseCase (ago/2026). Lógica de processamento idêntica
 * à que existia em GetPersonDetailsUseCase::processMovieCredits(), só
 * movida pra cá.
 *
 * NOTA (out/2026):
 * - Movido de UseCase/Credits/ para UseCase/MovieCredits/ (com os
 *   créditos de TV, "Credits" sozinho ficou ambíguo).
 * - Depende da porta MovieDatabaseGatewayInterface, não mais do
 *   TmdbApiService. Validação pelo RequestValidator.
 * - Erros viraram exceções (400 validação, 404 pessoa inexistente,
 *   502/503 TMDB); antes tudo virava failure() com status 400. O catch
 *   (Exception) genérico saiu.
 * - Cada item virou MovieCastCredit/MovieCrewCredit (mesmas chaves no
 *   JSON) e a resposta ganhou `person_id`. Os campos comuns de filme saem
 *   de movieFields(), em vez de dois arrays quase iguais.
 * - `language` é repassado ao TMDB.
 */
final readonly class GetPersonMovieCreditsUseCase {
    /**
     * @param \App\Application\Shared\Gateway\MovieDatabaseGatewayInterface $movieDatabaseGateway
     * @param \App\Application\Shared\RequestValidator                      $requestValidator
     * @param \App\Application\Shared\TmdbImageUrlBuilder                   $imageUrlBuilder
     * @param \App\Application\Shared\DateYearExtractor                     $yearExtractor
     * @param \App\Application\Shared\RatingPercentageCalculator            $ratingCalculator
     * @param \Psr\Log\LoggerInterface                                      $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private MovieDatabaseGatewayInterface $movieDatabaseGateway,
        private RequestValidator $requestValidator,
        private TmdbImageUrlBuilder $imageUrlBuilder,
        private DateYearExtractor $yearExtractor,
        private RatingPercentageCalculator $ratingCalculator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Person\DTO\MovieCredits\Request\GetPersonMovieCreditsRequest $request
     *
     * @return \App\Application\Person\DTO\MovieCredits\Response\GetPersonMovieCreditsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(
        GetPersonMovieCreditsRequest $request,
    ): GetPersonMovieCreditsResponse {
        $this->requestValidator->validate(
            $request,
            'person_movie_credits_request',
            ['person_id' => $request->personId],
        );

        $credits = $this->movieDatabaseGateway->getPersonMovieCredits(
            $request->personId,
            $request->language,
        );

        if (!isset($credits['id'])) {
            $this->logger->warning('Person not found for movie credits lookup', [
                'person_id' => $request->personId,
            ]);

            throw BusinessLogicException::notFound('Person', $request->personId);
        }

        $cast = array_map(
            fn(array $movie): MovieCastCredit => new MovieCastCredit(
                ...$this->movieFields($movie),
                character: (string) ($movie['character'] ?? ''),
                credit_id: (string) ($movie['credit_id'] ?? ''),
                order: (int) ($movie['order'] ?? 0),
            ),
            $credits['cast'] ?? [],
        );

        $crew = array_map(
            fn(array $movie): MovieCrewCredit => new MovieCrewCredit(
                ...$this->movieFields($movie),
                job: (string) ($movie['job'] ?? ''),
                department: (string) ($movie['department'] ?? ''),
                credit_id: (string) ($movie['credit_id'] ?? ''),
            ),
            $credits['crew'] ?? [],
        );

        $this->logger->info('Person movie credits retrieved successfully', [
            'person_id' => $request->personId,
            'cast_count' => count($cast),
            'crew_count' => count($crew),
        ]);

        return GetPersonMovieCreditsResponse::success(
            personId: (int) $credits['id'],
            cast: $cast,
            crew: $crew,
            message: $this->translator->trans(
                'Person movie credits retrieved successfully',
            ),
        );
    }

    /**
     * Campos de filme comuns a MovieCastCredit e MovieCrewCredit, como
     * argumentos nomeados.
     *
     * @param array<string, mixed> $movie crédito no formato do TMDB
     *
     * @return array<string, mixed>
     */
    private function movieFields(array $movie): array {
        $posterPath = $movie['poster_path'] ?? null;
        $backdropPath = $movie['backdrop_path'] ?? null;
        $voteAverage = (float) ($movie['vote_average'] ?? 0);

        return [
            'id' => (int) ($movie['id'] ?? 0),
            'title' => (string) ($movie['title'] ?? ''),
            'original_title' => (string) ($movie['original_title'] ?? ''),
            'release_date' => $movie['release_date'] ?? null,
            'poster_path' => $posterPath,
            'backdrop_path' => $backdropPath,
            'vote_average' => $voteAverage,
            'vote_count' => (int) ($movie['vote_count'] ?? 0),
            'popularity' => (float) ($movie['popularity'] ?? 0),
            'adult' => (bool) ($movie['adult'] ?? false),
            'genre_ids' => array_map('intval', $movie['genre_ids'] ?? []),
            'original_language' => (string) ($movie['original_language'] ?? ''),
            'overview' => (string) ($movie['overview'] ?? ''),
            'video' => (bool) ($movie['video'] ?? false),
            'poster_url' => $this->imageUrlBuilder->build($posterPath, 'w342'),
            'backdrop_url' => $this->imageUrlBuilder->build($backdropPath, 'w780'),
            'release_year' => $this->yearExtractor->extractYear($movie['release_date'] ?? null),
            'rating_percentage' => $this->ratingCalculator->calculatePercentage($voteAverage),
        ];
    }
}
