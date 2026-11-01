<?php

declare(strict_types = 1);

namespace App\Application\Person\UseCase\TvCredits;

use App\Application\Person\DTO\TvCredits\Request\GetPersonTvCreditsRequest;
use App\Application\Person\DTO\TvCredits\Response\GetPersonTvCreditsResponse;
use App\Application\Person\DTO\TvCredits\Response\Model\TvCastCredit;
use App\Application\Person\DTO\TvCredits\Response\Model\TvCrewCredit;
use App\Application\Shared\DateYearExtractor;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RatingPercentageCalculator;
use App\Application\Shared\RequestValidator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Créditos de TV de uma pessoa: as séries em que ela atuou (cast) e em que
 * trabalhou na equipe técnica (crew), via TMDB /person/{id}/tv_credits.
 *
 * NOTA (out/2026): criado agora. Antes existia só o placeholder
 * includeTvCredits no GetPersonDetailsUseCase, que sempre devolvia null.
 * Espelha o GetPersonMovieCreditsUseCase: mesma validação, mesmos erros
 * (400 validação, 404 pessoa inexistente, 502/503 TMDB), mesmos tamanhos
 * de imagem (w342/w780) e o mesmo jeito de montar os campos comuns
 * (tvFields()).
 */
final readonly class GetPersonTvCreditsUseCase {
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
     * @param \App\Application\Person\DTO\TvCredits\Request\GetPersonTvCreditsRequest $request
     *
     * @return \App\Application\Person\DTO\TvCredits\Response\GetPersonTvCreditsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(
        GetPersonTvCreditsRequest $request,
    ): GetPersonTvCreditsResponse {
        $this->requestValidator->validate(
            $request,
            'person_tv_credits_request',
            ['person_id' => $request->personId],
        );

        $credits = $this->movieDatabaseGateway->getPersonTvCredits(
            $request->personId,
            $request->language,
        );

        if (!isset($credits['id'])) {
            $this->logger->warning('Person not found for TV credits lookup', [
                'person_id' => $request->personId,
            ]);

            throw BusinessLogicException::notFound('Person', $request->personId);
        }

        $cast = array_map(
            fn(array $show): TvCastCredit => new TvCastCredit(
                ...$this->tvFields($show),
                character: (string) ($show['character'] ?? ''),
                credit_id: (string) ($show['credit_id'] ?? ''),
            ),
            $credits['cast'] ?? [],
        );

        $crew = array_map(
            fn(array $show): TvCrewCredit => new TvCrewCredit(
                ...$this->tvFields($show),
                job: (string) ($show['job'] ?? ''),
                department: (string) ($show['department'] ?? ''),
                credit_id: (string) ($show['credit_id'] ?? ''),
            ),
            $credits['crew'] ?? [],
        );

        $this->logger->info('Person TV credits retrieved successfully', [
            'person_id' => $request->personId,
            'cast_count' => count($cast),
            'crew_count' => count($crew),
        ]);

        return GetPersonTvCreditsResponse::success(
            personId: (int) $credits['id'],
            cast: $cast,
            crew: $crew,
            message: $this->translator->trans(
                'Person TV credits retrieved successfully',
            ),
        );
    }

    /**
     * Campos de série comuns a TvCastCredit e TvCrewCredit, como
     * argumentos nomeados.
     *
     * @param array<string, mixed> $show crédito no formato do TMDB
     *
     * @return array<string, mixed>
     */
    private function tvFields(array $show): array {
        $posterPath = $show['poster_path'] ?? null;
        $backdropPath = $show['backdrop_path'] ?? null;
        $voteAverage = (float) ($show['vote_average'] ?? 0);

        return [
            'id' => (int) ($show['id'] ?? 0),
            'name' => (string) ($show['name'] ?? ''),
            'original_name' => (string) ($show['original_name'] ?? ''),
            'episode_count' => (int) ($show['episode_count'] ?? 0),
            'first_air_date' => $show['first_air_date'] ?? null,
            'poster_path' => $posterPath,
            'backdrop_path' => $backdropPath,
            'vote_average' => $voteAverage,
            'vote_count' => (int) ($show['vote_count'] ?? 0),
            'popularity' => (float) ($show['popularity'] ?? 0),
            'adult' => (bool) ($show['adult'] ?? false),
            'genre_ids' => array_map('intval', $show['genre_ids'] ?? []),
            'origin_country' => array_map('strval', $show['origin_country'] ?? []),
            'original_language' => (string) ($show['original_language'] ?? ''),
            'overview' => (string) ($show['overview'] ?? ''),
            'poster_url' => $this->imageUrlBuilder->build($posterPath, 'w342'),
            'backdrop_url' => $this->imageUrlBuilder->build($backdropPath, 'w780'),
            'first_air_year' => $this->yearExtractor->extractYear($show['first_air_date'] ?? null),
            'rating_percentage' => $this->ratingCalculator->calculatePercentage($voteAverage),
        ];
    }
}
