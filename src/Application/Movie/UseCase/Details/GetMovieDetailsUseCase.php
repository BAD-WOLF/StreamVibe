<?php

declare(strict_types=1);

namespace App\Application\Movie\UseCase\Details;

use App\Application\Movie\DTO\Details\Request\GetMovieDetailsRequest;
use App\Application\Movie\DTO\Details\Response\GetMovieDetailsResponse;
use App\Application\Movie\DTO\Details\Response\Model\BelongsToCollection;
use App\Application\Movie\DTO\Details\Response\Model\Genre;
use App\Application\Movie\DTO\Details\Response\Model\ProductionCompany;
use App\Application\Movie\DTO\Details\Response\Model\ProductionCountry;
use App\Application\Movie\DTO\Details\Response\Model\SpokenLanguage;
use App\Application\Shared\DateYearExtractor;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RatingPercentageCalculator;
use App\Application\Shared\RequestValidator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
 *
 * NOTA (out/2026): depende da porta MovieDatabaseGatewayInterface (não
 * mais do TmdbApiService) e não tem mais try/catch de HttpClient: filme
 * inexistente vira BusinessLogicException::notFound() (404) no próprio
 * adaptador. processMovieDetails() calcula só os derivados que o DTO
 * expõe (formatted_budget/formatted_revenue/genre_names/is_released eram
 * calculados e descartados pelo GetMovieDetailsResponse::success()) e
 * converte genres, belongs_to_collection, production_companies,
 * production_countries e spoken_languages nas classes de
 * DTO/Details/Response/Model — o mesmo JSON, agora tipado.
 */
final readonly class GetMovieDetailsUseCase
{
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
    ) {}

    /**
     * @param \App\Application\Movie\DTO\Details\Request\GetMovieDetailsRequest $request
     *
     * @return \App\Application\Movie\DTO\Details\Response\GetMovieDetailsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(
        GetMovieDetailsRequest $request,
    ): GetMovieDetailsResponse {
        $this->requestValidator->validate(
            $request,
            "movie_details_request",
            ["movie_id" => $request->movieId],
        );

        $movieDetails = $this->movieDatabaseGateway->getMovieDetails(
            $request->movieId,
            $request->language,
        );

        if (!isset($movieDetails["id"])) {
            $this->logger->warning("Movie not found in TMDB API", [
                "movie_id" => $request->movieId,
                "response_keys" => array_keys($movieDetails),
            ]);

            throw BusinessLogicException::notFound("Movie", $request->movieId);
        }

        $processedDetails = $this->processMovieDetails($movieDetails);

        $this->logger->info("Movie details retrieved successfully", [
            "movie_id" => $request->movieId,
            "movie_title" => $processedDetails["title"] ?? "Unknown",
        ]);

        return GetMovieDetailsResponse::success(
            movieDetails: $processedDetails,
            message: $this->translator->trans(
                "Movie details retrieved successfully",
            ),
        );
    }

    /**
     * Mantém os campos do TMDB, converte as listas/objetos aninhados nas
     * classes de DTO/Details/Response/Model e acrescenta os derivados
     * expostos pelo DTO.
     *
     * @param array<string, mixed> $movieDetails filme no formato do TMDB
     *
     * @return array<string, mixed>
     */
    private function processMovieDetails(array $movieDetails): array
    {
        $collection = $movieDetails["belongs_to_collection"] ?? null;

        return [
            "belongs_to_collection" => is_array($collection)
                ? new BelongsToCollection(
                    id: (int) ($collection["id"] ?? 0),
                    name: (string) ($collection["name"] ?? ""),
                    poster_path: $collection["poster_path"] ?? null,
                    backdrop_path: $collection["backdrop_path"] ?? null,
                )
                : null,
            "genres" => array_map(
                static fn(array $genre): Genre => new Genre(
                    id: (int) ($genre["id"] ?? 0),
                    name: (string) ($genre["name"] ?? ""),
                ),
                $movieDetails["genres"] ?? [],
            ),
            "production_companies" => array_map(
                static fn(array $company): ProductionCompany => new ProductionCompany(
                    id: (int) ($company["id"] ?? 0),
                    logo_path: $company["logo_path"] ?? null,
                    name: (string) ($company["name"] ?? ""),
                    origin_country: (string) ($company["origin_country"] ?? ""),
                ),
                $movieDetails["production_companies"] ?? [],
            ),
            "production_countries" => array_map(
                static fn(array $country): ProductionCountry => new ProductionCountry(
                    iso_3166_1: (string) ($country["iso_3166_1"] ?? ""),
                    name: (string) ($country["name"] ?? ""),
                ),
                $movieDetails["production_countries"] ?? [],
            ),
            "spoken_languages" => array_map(
                static fn(array $language): SpokenLanguage => new SpokenLanguage(
                    english_name: (string) ($language["english_name"] ?? ""),
                    iso_639_1: (string) ($language["iso_639_1"] ?? ""),
                    name: (string) ($language["name"] ?? ""),
                ),
                $movieDetails["spoken_languages"] ?? [],
            ),
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
            "release_year" => $this->yearExtractor->extractYear(
                $movieDetails["release_date"] ?? null,
            ),
            "rating_percentage" => $this->ratingCalculator->calculatePercentage(
                (float) ($movieDetails["vote_average"] ?? 0),
            ),
            "formatted_runtime" => $this->formatRuntime(
                isset($movieDetails["runtime"]) ? (int) $movieDetails["runtime"] : null,
            ),
        ] + $movieDetails;
    }

    /**
     * @param int|null $runtime minutos
     *
     * @return string|null "2h 16m", "2h" ou "45m"
     */
    private function formatRuntime(?int $runtime): ?string
    {
        if ($runtime === null || $runtime <= 0) {
            return null;
        }

        $hours = intdiv($runtime, 60);
        $minutes = $runtime % 60;

        return match (true) {
            $hours > 0 && $minutes > 0 => "{$hours}h {$minutes}m",
            $hours > 0 => "{$hours}h",
            default => "{$minutes}m",
        };
    }
}
