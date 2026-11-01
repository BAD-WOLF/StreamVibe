<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Credits;

use App\Application\Movie\DTO\Credits\Request\GetMovieCreditsRequest;
use App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse;
use App\Application\Movie\DTO\Credits\Response\Model\CastMember;
use App\Application\Movie\DTO\Credits\Response\Model\CrewMember;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para créditos de filme — separado de
 * GetMovieDetailsUseCase. Chama diretamente o TMDB
 * (/movie/{id}/credits), sem buscar detalhes completos do filme antes.
 *
 * NOTA (out/2026): depende da porta MovieDatabaseGatewayInterface (não
 * mais do TmdbApiService) e não tem mais o try/catch das exceções do
 * HttpClient — o adaptador já devolve exceção de domínio (filme
 * inexistente = 404). Passou a enviar `language` e a preencher `message`.
 */
final readonly class GetMovieCreditsUseCase {
    /**
     * @param \App\Application\Shared\Gateway\MovieDatabaseGatewayInterface $movieDatabaseGateway
     * @param \App\Application\Shared\RequestValidator                      $requestValidator
     * @param \Psr\Log\LoggerInterface                                      $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private MovieDatabaseGatewayInterface $movieDatabaseGateway,
        private RequestValidator $requestValidator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Movie\DTO\Credits\Request\GetMovieCreditsRequest $request
     *
     * @return \App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(GetMovieCreditsRequest $request): GetMovieCreditsResponse {
        $this->requestValidator->validate(
            $request,
            'movie_credits_request',
            ['movie_id' => $request->movieId],
        );

        $credits = $this->movieDatabaseGateway->getMovieCredits(
            $request->movieId,
            $request->language,
        );

        if (!isset($credits['id'])) {
            $this->logger->warning('Movie not found for credits lookup', [
                'movie_id' => $request->movieId,
            ]);

            throw BusinessLogicException::notFound('Movie', $request->movieId);
        }

        $cast = array_map(
            static fn(array $member) => new CastMember(
                adult: (bool) ($member['adult'] ?? false),
                gender: (int) ($member['gender'] ?? 0),
                id: (int) ($member['id'] ?? 0),
                known_for_department: (string) ($member['known_for_department'] ?? ''),
                name: (string) ($member['name'] ?? ''),
                original_name: (string) ($member['original_name'] ?? ''),
                popularity: (float) ($member['popularity'] ?? 0.0),
                profile_path: $member['profile_path'] ?? null,
                cast_id: (int) ($member['cast_id'] ?? 0),
                character: (string) ($member['character'] ?? ''),
                credit_id: (string) ($member['credit_id'] ?? ''),
                order: (int) ($member['order'] ?? 0),
            ),
            $credits['cast'] ?? [],
        );

        $crew = array_map(
            static fn(array $member) => new CrewMember(
                adult: (bool) ($member['adult'] ?? false),
                gender: (int) ($member['gender'] ?? 0),
                id: (int) ($member['id'] ?? 0),
                known_for_department: (string) ($member['known_for_department'] ?? ''),
                name: (string) ($member['name'] ?? ''),
                original_name: (string) ($member['original_name'] ?? ''),
                popularity: (float) ($member['popularity'] ?? 0.0),
                profile_path: $member['profile_path'] ?? null,
                credit_id: (string) ($member['credit_id'] ?? ''),
                department: (string) ($member['department'] ?? ''),
                job: (string) ($member['job'] ?? ''),
            ),
            $credits['crew'] ?? [],
        );

        $this->logger->info('Movie credits retrieved successfully', [
            'movie_id' => $request->movieId,
            'cast_count' => count($cast),
            'crew_count' => count($crew),
        ]);

        return GetMovieCreditsResponse::success(
            movieId: (int) $credits['id'],
            cast: $cast,
            crew: $crew,
            message: $this->translator->trans(
                'Movie credits retrieved successfully',
            ),
        );
    }
}
