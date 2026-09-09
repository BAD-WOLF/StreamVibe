<?php

declare(strict_types=1);

namespace App\Application\Movie\UseCase\Details;

use App\Application\Movie\DTO\Details\Request\GetMovieDetailsRequest;
use App\Application\Movie\DTO\Details\Response\GetMovieDetailsResponse;
use App\Application\Movie\Shared\MovieRatingCalculator;
use App\Application\Shared\DateYearExtractor;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\UserValidationException;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\ExternalServiceException;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use DateTime;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * NOTA (ago/2026): fetchMovieCredits/Videos/Images/Recommendations/
 * Similar + seus processCredits/processVideos/processImages/
 * processImage/processMovieList foram removidos daqui. Cada um já tem
 * endpoint dedicado (GetMovieCreditsUseCase, GetMovieVideosUseCase,
 * etc.) fazendo sua própria chamada à TMDB. Manter as duas versões
 * causava: (1) toda chamada simples a GET /movies/{id} disparando uma
 * SEGUNDA chamada à TMDB por baixo dos panos (includeCredits era true
 * por default), e (2) elenco/equipe em formato DIFERENTE dependendo
 * de qual endpoint o cliente batesse (versão pobre aqui vs. versão
 * tipada em GetMovieCreditsUseCase). Esse UseCase agora só busca os
 * dados próprios do filme — mesmo comportamento da TMDB real sem
 * append_to_response.
 */
final readonly class GetMovieDetailsUseCase
{
    /**
     * @param \App\Infrastructure\ExternalServices\TmdbApiService       $tmdbApiService
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Psr\Log\LoggerInterface                                  $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface        $translator
     * @param \App\Application\Shared\TmdbImageUrlBuilder                $imageUrlBuilder
     * @param \App\Application\Shared\DateYearExtractor                  $yearExtractor
     * @param \App\Application\Movie\Shared\MovieRatingCalculator        $ratingCalculator
     */
    public function __construct(
        private TmdbApiService $tmdbApiService,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private TmdbImageUrlBuilder $imageUrlBuilder,
        private DateYearExtractor $yearExtractor,
        private MovieRatingCalculator $ratingCalculator,
    ) {}

    /**
     * @param \App\Application\Movie\DTO\Details\Request\GetMovieDetailsRequest $request
     *
     * @return \App\Application\Movie\DTO\Details\Response\GetMovieDetailsResponse
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\ValidationException
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function execute(
        GetMovieDetailsRequest $request,
    ): GetMovieDetailsResponse {
        $violations = $this->validator->validate($request);
        if (count($violations) > 0) {
            $this->logger->warning("Movie details request validation failed", [
                "movie_id" => $request->getMovieId(),
                "violations_count" => count($violations),
            ]);

            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            throw UserValidationException::multipleValidationErrors(
                array_combine(range(0, count($errors) - 1), $errors),
            )->withContext([
                "movie_id" => $request->getMovieId(),
                "validation_context" => "movie_details_request",
            ]);
        }

        try {
            $movieDetails = $this->tmdbApiService->getMovieDetails(
                $request->getMovieId(),
            );

            if (empty($movieDetails) || !isset($movieDetails["id"])) {
                $this->logger->warning("Movie not found in TMDB API", [
                    "movie_id" => $request->getMovieId(),
                    "response_keys" => is_array($movieDetails)
                        ? array_keys($movieDetails)
                        : [],
                ]);

                throw BusinessLogicException::notFound(
                    "Movie",
                    $request->getMovieId(),
                );
            }

            $processedDetails = $this->processMovieDetails($movieDetails);

            $this->logger->info("Movie details retrieved successfully", [
                "movie_id" => $request->getMovieId(),
                "movie_title" => $processedDetails["title"] ?? "Unknown",
            ]);

            return GetMovieDetailsResponse::success(
                movieDetails: $processedDetails,
                message: $this->translator->trans(
                    "Movie details retrieved successfully",
                ),
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error(
                "Client error fetching movie details from TMDB",
                [
                    "movie_id" => $request->getMovieId(),
                    "error" => $e->getMessage(),
                    "error_class" => get_class($e),
                    "trace" => $e->getTraceAsString(),
                ],
            );

            throw ExternalServiceException::clientError(
                "TMDB Movie API",
                $e->getMessage(),
                ["movie_id" => $request->getMovieId()],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error(
                "Server error fetching movie details from TMDB",
                [
                    "movie_id" => $request->getMovieId(),
                    "error" => $e->getMessage(),
                    "error_class" => get_class($e),
                    "trace" => $e->getTraceAsString(),
                ],
            );

            throw ExternalServiceException::serverError(
                "TMDB Movie API",
                $e->getMessage(),
                ["movie_id" => $request->getMovieId()],
            );
        } catch (BusinessLogicException|ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error("Unexpected error fetching movie details", [
                "movie_id" => $request->getMovieId(),
                "error" => $e->getMessage(),
                "error_class" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "memory_usage" => memory_get_usage(true),
                "peak_memory" => memory_get_peak_usage(true),
            ]);

            throw new RuntimeException(
                $this->translator->trans(
                    "An error occurred while fetching movie details",
                ),
                500,
                $e,
            );
        }
    }

    /**
     * Process and enhance movie details from TMDB API
     */
    private function processMovieDetails(array $movieDetails): array
    {
        return [
            "id" => $movieDetails["id"] ?? null,
            "title" => $movieDetails["title"] ?? null,
            "original_title" => $movieDetails["original_title"] ?? null,
            "overview" => $movieDetails["overview"] ?? null,
            "release_date" => $movieDetails["release_date"] ?? null,
            "poster_path" => $movieDetails["poster_path"] ?? null,
            "backdrop_path" => $movieDetails["backdrop_path"] ?? null,
            "vote_average" => $movieDetails["vote_average"] ?? 0,
            "vote_count" => $movieDetails["vote_count"] ?? 0,
            "popularity" => $movieDetails["popularity"] ?? 0,
            "runtime" => $movieDetails["runtime"] ?? null,
            "budget" => $movieDetails["budget"] ?? null,
            "revenue" => $movieDetails["revenue"] ?? null,
            "status" => $movieDetails["status"] ?? null,
            "tagline" => $movieDetails["tagline"] ?? null,
            "homepage" => $movieDetails["homepage"] ?? null,
            "imdb_id" => $movieDetails["imdb_id"] ?? null,
            "adult" => $movieDetails["adult"] ?? false,
            "video" => $movieDetails["video"] ?? false,
            "original_language" => $movieDetails["original_language"] ?? null,
            "spoken_languages" => $movieDetails["spoken_languages"] ?? [],
            "production_companies" =>
                $movieDetails["production_companies"] ?? [],
            "production_countries" =>
                $movieDetails["production_countries"] ?? [],
            "genres" => $movieDetails["genres"] ?? [],
            "belongs_to_collection" =>
                $movieDetails["belongs_to_collection"] ?? null,
            "origin_country" => $movieDetails["origin_country"] ?? [],

            "poster_url" => $this->imageUrlBuilder->build(
                $movieDetails["poster_path"] ?? null,
                "w500",
            ),
            "backdrop_url" => $this->imageUrlBuilder->build(
                $movieDetails["backdrop_path"] ?? null,
                "w1280",
            ),
            "thumbnail_url" => $this->imageUrlBuilder->build(
                $movieDetails["poster_path"] ?? null,
                "w342",
            ),
            "rating_percentage" => $this->ratingCalculator->calculatePercentage(
                $movieDetails["vote_average"] ?? 0,
            ),
            "release_year" => $this->yearExtractor->extractYear(
                $movieDetails["release_date"] ?? null,
            ),
            "formatted_runtime" => $this->formatRuntime(
                $movieDetails["runtime"] ?? null,
            ),
            "formatted_budget" => $this->formatCurrency(
                $movieDetails["budget"] ?? null,
            ),
            "formatted_revenue" => $this->formatCurrency(
                $movieDetails["revenue"] ?? null,
            ),
            "genre_names" => $this->extractGenreNames(
                $movieDetails["genres"] ?? [],
            ),
            "is_released" => $this->isMovieReleased(
                $movieDetails["release_date"] ?? null,
            ),
        ];
    }

    private function formatRuntime(?int $runtime): ?string
    {
        if ($runtime === null || $runtime <= 0) {
            return null;
        }

        $hours = intval($runtime / 60);
        $minutes = $runtime % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    private function formatCurrency(?int $amount): ?string
    {
        if ($amount === null || $amount <= 0) {
            return null;
        }

        return '$' . number_format($amount);
    }

    private function extractGenreNames(array $genres): array
    {
        return array_map(function (array $genre) {
            return $genre["name"] ?? "";
        }, $genres);
    }

    private function isMovieReleased(?string $releaseDate): bool
    {
        if (empty($releaseDate)) {
            return false;
        }

        try {
            $releaseDateTime = new DateTime($releaseDate);
            $now = new DateTime();

            return $releaseDateTime <= $now;
        } catch (Exception) {
            return false;
        }
    }
}
