<?php

declare(strict_types = 1);

namespace App\Application\Person\UseCase\Details;

use App\Application\Person\DTO\Details\GetPersonDetailsRequest;
use App\Application\Person\DTO\Details\GetPersonDetailsResponse;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use DateTime;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * NOTA (ago/2026): busca de movieCredits/tvCredits/images/externalIds
 * removida daqui. Movie credits ganhou endpoint dedicado
 * (GetPersonMovieCreditsUseCase). Os outros três nunca tiveram
 * implementação real (sempre devolviam null).
 */
final readonly class GetPersonDetailsUseCase {
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
     * @param \App\Application\Person\DTO\GetPersonDetailsRequest $request
     *
     * @return \App\Application\Person\DTO\GetPersonDetailsResponse
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function execute(
        GetPersonDetailsRequest $request,
    ): GetPersonDetailsResponse {
        // Validate input
        $violations = $this->validator->validate($request);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return GetPersonDetailsResponse::failure(
                $this->translator->trans('Invalid person request parameters'),
                $errors,
            );
        }

        try {
            // Get basic person details
            $personDetails = $this->tmdbApiService->getPersonDetails(
                $request->getPersonId(),
            );

            if (empty($personDetails) || !isset($personDetails['id'])) {
                $this->logger->warning('Person not found', [
                    'person_id' => $request->getPersonId(),
                ]);

                return GetPersonDetailsResponse::failure(
                    $this->translator->trans('Person not found'),
                );
            }

            // Process person details
            $processedDetails = $this->processPersonDetails($personDetails);

            $this->logger->info('Person details retrieved successfully', [
                'person_id' => $request->getPersonId(),
                'person_name' => $processedDetails['name'] ?? 'Unknown',
            ]);

            return GetPersonDetailsResponse::success(
                personDetails: $processedDetails,
                message: $this->translator->trans(
                    'Person details retrieved successfully',
                ),
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching person details', [
                'person_id' => $request->getPersonId(),
                'error' => $e->getMessage(),
            ]);

            return GetPersonDetailsResponse::failure(
                $this->translator->trans('Invalid person request'),
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching person details', [
                'person_id' => $request->getPersonId(),
                'error' => $e->getMessage(),
            ]);

            return GetPersonDetailsResponse::failure(
                $this->translator->trans(
                    'Person service is temporarily unavailable',
                ),
            );
        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching person details', [
                'person_id' => $request->getPersonId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return GetPersonDetailsResponse::failure(
                $this->translator->trans(
                    'An error occurred while fetching person details',
                ),
            );
        }
    }

    /**
     * Process and enhance person details from TMDB API
     */
    private function processPersonDetails(array $personDetails): array {
        return [
            'id' => $personDetails['id'] ?? null,
            'name' => $personDetails['name'] ?? null,
            'biography' => $personDetails['biography'] ?? null,
            'birthday' => $personDetails['birthday'] ?? null,
            'deathday' => $personDetails['deathday'] ?? null,
            'place_of_birth' => $personDetails['place_of_birth'] ?? null,
            'profile_path' => $personDetails['profile_path'] ?? null,
            'known_for_department' =>
                $personDetails['known_for_department'] ?? null,
            'popularity' => $personDetails['popularity'] ?? 0,
            'gender' => $personDetails['gender'] ?? null,
            'adult' => $personDetails['adult'] ?? false,
            'also_known_as' => $personDetails['also_known_as'] ?? [],
            'homepage' => $personDetails['homepage'] ?? null,
            'imdb_id' => $personDetails['imdb_id'] ?? null,

            // Enhanced fields
            'profile_url' => $this->buildImageUrl(
                $personDetails['profile_path'] ?? null,
                'w185',
            ),
            'profile_large_url' => $this->buildImageUrl(
                $personDetails['profile_path'] ?? null,
                'w500',
            ),
            'profile_original_url' => $this->buildImageUrl(
                $personDetails['profile_path'] ?? null,
                'original',
            ),
            'formatted_biography' => $this->formatBiography(
                $personDetails['biography'] ?? null,
            ),
            'age' => $this->calculateAge(
                $personDetails['birthday'] ?? null,
                $personDetails['deathday'] ?? null,
            ),
            'is_alive' => empty($personDetails['deathday']),
            'gender_display' => $this->getGenderDisplay(
                $personDetails['gender'] ?? 0,
            ),
            'birth_year' => $this->extractYear(
                $personDetails['birthday'] ?? null,
            ),
            'death_year' => $this->extractYear(
                $personDetails['deathday'] ?? null,
            ),
            'formatted_birth_date' => $this->formatDate(
                $personDetails['birthday'] ?? null,
            ),
            'formatted_death_date' => $this->formatDate(
                $personDetails['deathday'] ?? null,
            ),
        ];
    }

    /**
     * Build complete image URL from TMDB path
     */
    private function buildImageUrl(
        ?string $path,
        string $size = 'original',
    ): ?string {
        if (empty($path)) {
            return null;
        }

        return "https://image.tmdb.org/t/p/{$size}{$path}";
    }

    /**
     * Format biography text
     */
    private function formatBiography(?string $biography): ?string {
        if (empty($biography)) {
            return null;
        }

        $biography = str_replace(["\r\n", "\r"], "\n", trim($biography));
        $biography = preg_replace('/\n{3,}/', "\n\n", $biography);

        return $biography;
    }

    /**
     * Calculate person's age
     */
    private function calculateAge(?string $birthday, ?string $deathday): ?int {
        if (empty($birthday)) {
            return null;
        }

        try {
            $birthDate = new DateTime($birthday);
            $endDate = $deathday ? new DateTime($deathday) : new DateTime();

            return $birthDate->diff($endDate)->y;
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Get gender display string
     */
    private function getGenderDisplay(int $gender): string {
        return match ($gender) {
            1 => $this->translator->trans('Female'),
            2 => $this->translator->trans('Male'),
            3 => $this->translator->trans('Non-binary'),
            default => $this->translator->trans('Not specified'),
        };
    }

    /**
     * Extract year from date string
     */
    private function extractYear(?string $date): ?int {
        if (empty($date)) {
            return null;
        }

        $year = substr($date, 0, 4);

        return is_numeric($year) ? (int)$year : null;
    }

    /**
     * Format date for display
     */
    private function formatDate(?string $date): ?string {
        if (empty($date)) {
            return null;
        }

        try {
            $dateTime = new DateTime($date);

            return $dateTime->format('F j, Y');
        } catch (Exception) {
            return $date;
        }
    }
}
