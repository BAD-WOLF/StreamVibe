<?php

declare(strict_types = 1);

namespace App\Application\Person\UseCase\Credits;

use App\Application\Person\DTO\Credits\GetPersonMovieCreditsRequest;
use App\Application\Person\DTO\Credits\GetPersonMovieCreditsResponse;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Endpoint dedicado para créditos de filme de uma pessoa — separado de
 * GetPersonDetailsUseCase (ago/2026). Lógica de processamento idêntica
 * à que existia em GetPersonDetailsUseCase::processMovieCredits(), só
 * movida pra cá.
 */
final readonly class GetPersonMovieCreditsUseCase {
    /**
     * @param \App\Infrastructure\ExternalServices\TmdbApiService       $tmdbApiService
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Psr\Log\LoggerInterface                                  $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface        $translator
     */
    public function __construct(
        private TmdbApiService $tmdbApiService,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Person\DTO\GetPersonMovieCreditsRequest $request
     *
     * @return \App\Application\Person\DTO\GetPersonMovieCreditsResponse
     */
    public function execute(
        GetPersonMovieCreditsRequest $request,
    ): GetPersonMovieCreditsResponse {
        $violations = $this->validator->validate($request);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return GetPersonMovieCreditsResponse::failure(
                $this->translator->trans('Invalid person request parameters'),
                $errors,
            );
        }

        try {
            $credits = $this->tmdbApiService->getPersonMovieCredits(
                $request->getPersonId(),
            );

            $processed = $this->processMovieCredits($credits);

            $this->logger->info('Person movie credits retrieved successfully', [
                'person_id' => $request->getPersonId(),
            ]);

            return GetPersonMovieCreditsResponse::success(
                cast: $processed['cast'],
                crew: $processed['crew'],
                message: $this->translator->trans(
                    'Person movie credits retrieved successfully',
                ),
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching person movie credits', [
                'person_id' => $request->getPersonId(),
                'error' => $e->getMessage(),
            ]);

            return GetPersonMovieCreditsResponse::failure(
                $this->translator->trans('Invalid person request'),
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching person movie credits', [
                'person_id' => $request->getPersonId(),
                'error' => $e->getMessage(),
            ]);

            return GetPersonMovieCreditsResponse::failure(
                $this->translator->trans(
                    'Person service is temporarily unavailable',
                ),
            );
        } catch (Exception $e) {
            $this->logger->error(
                'Unexpected error fetching person movie credits',
                [
                    'person_id' => $request->getPersonId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            );

            return GetPersonMovieCreditsResponse::failure(
                $this->translator->trans(
                    'An error occurred while fetching person movie credits',
                ),
            );
        }
    }

    /**
     * Process movie credits data (movida de GetPersonDetailsUseCase,
     * sem alteração de lógica)
     */
    private function processMovieCredits(array $credits): array {
        return [
            'cast' => array_map(function (array $movie) {
                return [
                    'id' => $movie['id'] ?? null,
                    'title' => $movie['title'] ?? null,
                    'original_title' => $movie['original_title'] ?? null,
                    'character' => $movie['character'] ?? null,
                    'credit_id' => $movie['credit_id'] ?? null,
                    'order' => $movie['order'] ?? null,
                    'release_date' => $movie['release_date'] ?? null,
                    'poster_path' => $movie['poster_path'] ?? null,
                    'backdrop_path' => $movie['backdrop_path'] ?? null,
                    'vote_average' => $movie['vote_average'] ?? 0,
                    'vote_count' => $movie['vote_count'] ?? 0,
                    'popularity' => $movie['popularity'] ?? 0,
                    'adult' => $movie['adult'] ?? false,
                    'genre_ids' => $movie['genre_ids'] ?? [],
                    'original_language' => $movie['original_language'] ?? null,
                    'overview' => $movie['overview'] ?? null,
                    'video' => $movie['video'] ?? false,
                    'poster_url' => $this->buildImageUrl(
                        $movie['poster_path'] ?? null,
                        'w342',
                    ),
                    'backdrop_url' => $this->buildImageUrl(
                        $movie['backdrop_path'] ?? null,
                        'w780',
                    ),
                    'release_year' => $this->extractYear(
                        $movie['release_date'] ?? null,
                    ),
                    'rating_percentage' => $this->calculateRatingPercentage(
                        $movie['vote_average'] ?? 0,
                    ),
                ];
            }, $credits['cast'] ?? []),
            'crew' => array_map(function (array $movie) {
                return [
                    'id' => $movie['id'] ?? null,
                    'title' => $movie['title'] ?? null,
                    'original_title' => $movie['original_title'] ?? null,
                    'job' => $movie['job'] ?? null,
                    'department' => $movie['department'] ?? null,
                    'credit_id' => $movie['credit_id'] ?? null,
                    'release_date' => $movie['release_date'] ?? null,
                    'poster_path' => $movie['poster_path'] ?? null,
                    'backdrop_path' => $movie['backdrop_path'] ?? null,
                    'vote_average' => $movie['vote_average'] ?? 0,
                    'vote_count' => $movie['vote_count'] ?? 0,
                    'popularity' => $movie['popularity'] ?? 0,
                    'adult' => $movie['adult'] ?? false,
                    'genre_ids' => $movie['genre_ids'] ?? [],
                    'original_language' => $movie['original_language'] ?? null,
                    'overview' => $movie['overview'] ?? null,
                    'video' => $movie['video'] ?? false,
                    'poster_url' => $this->buildImageUrl(
                        $movie['poster_path'] ?? null,
                        'w342',
                    ),
                    'backdrop_url' => $this->buildImageUrl(
                        $movie['backdrop_path'] ?? null,
                        'w780',
                    ),
                    'release_year' => $this->extractYear(
                        $movie['release_date'] ?? null,
                    ),
                    'rating_percentage' => $this->calculateRatingPercentage(
                        $movie['vote_average'] ?? 0,
                    ),
                ];
            }, $credits['crew'] ?? []),
        ];
    }

    private function buildImageUrl(
        ?string $path,
        string $size = 'original',
    ): ?string {
        if (empty($path)) {
            return null;
        }

        return "https://image.tmdb.org/t/p/{$size}{$path}";
    }

    private function extractYear(?string $date): ?int {
        if (empty($date)) {
            return null;
        }

        $year = substr($date, 0, 4);

        return is_numeric($year) ? (int)$year : null;
    }

    private function calculateRatingPercentage(float $voteAverage): int {
        return (int)round(($voteAverage / 10) * 100);
    }
}
