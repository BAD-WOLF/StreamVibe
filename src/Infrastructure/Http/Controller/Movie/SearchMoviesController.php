<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie;

use App\Application\Movie\DTO\SearchMoviesRequest;
use App\Application\Movie\UseCase\Search\SearchMoviesUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Movies\Search\MoviesEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for searching movies
 */
#[AsController]
class SearchMoviesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Search\SearchMoviesUseCase $searchMoviesUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface        $translator
     */
    public function __construct(
        private readonly SearchMoviesUseCase $searchMoviesUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Search for movies by query
     */
    #[
        Route(
            name: "api_movies_search",
            defaults: [
                "_api_resource_class" => MoviesEntryPoint::class,
                "_api_operation_name" => "get_search_movies",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(
        string $query,
        int $page,
        Request $request,
    ): JsonResponse {
        $includeAdult = $request->query->getBoolean(
            key: "include_adult",
            default: false,
        );
        $region = $request->query->get(key: "region");
        $year = $request->query->get(key: "year");
        $primaryReleaseYear = $request->query->get(key: "primary_release_year");

        if (empty($query)) {
            throw UserValidationException::requiredFieldMissing("query");
        }

        $searchRequest = new SearchMoviesRequest(
            query: $query,
            page: $page,
            includeAdult: $includeAdult,
            region: $region,
            year: $year ? (int) $year : null,
            primaryReleaseYear: $primaryReleaseYear
                ? (int) $primaryReleaseYear
                : null,
        );

        $response = $this->searchMoviesUseCase->execute(
            request: $searchRequest,
        );

        return $this->safeMapToJsonResponse(dto: $response, status: 200);
    }
}
