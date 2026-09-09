<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Credits;

use App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse;
use App\Application\Movie\DTO\Credits\Response\Model\CastMember;
use App\Application\Movie\DTO\Credits\Response\Model\CrewMember;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\ExternalServiceException;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Endpoint dedicado para créditos de filme — separado de
 * GetMovieDetailsUseCase. Chama diretamente
 * TmdbApiService::getMovieCredits(), sem buscar detalhes completos do
 * filme antes.
 */
final readonly class GetMovieCreditsUseCase {
    /**
     * @param \App\Infrastructure\ExternalServices\TmdbApiService $tmdbApiService
     * @param \Psr\Log\LoggerInterface                            $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private TmdbApiService $tmdbApiService,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param int $movieId
     *
     * @return \App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(int $movieId): GetMovieCreditsResponse {
        try {
            $credits = $this->tmdbApiService->getMovieCredits($movieId);

            if (!isset($credits['id'])) {
                $this->logger->warning('Movie not found for credits lookup', [
                    'movie_id' => $movieId,
                ]);

                throw BusinessLogicException::notFound('Movie', $movieId);
            }

            $cast = array_map(
                fn(array $member) => new CastMember(
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
                fn(array $member) => new CrewMember(
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
                'movie_id' => $movieId,
                'cast_count' => count($cast),
                'crew_count' => count($crew),
            ]);

            return new GetMovieCreditsResponse(
                id: (int) $credits['id'],
                cast: $cast,
                crew: $crew,
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching movie credits', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::clientError(
                'TMDB Movie API',
                $e->getMessage(),
                ['movie_id' => $movieId],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching movie credits', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::serverError(
                'TMDB Movie API',
                $e->getMessage(),
                ['movie_id' => $movieId],
            );
        } catch (BusinessLogicException|ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching movie credits', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                $this->translator->trans(
                    'An error occurred while fetching movie credits',
                ),
                500,
                $e,
            );
        }
    }
}
