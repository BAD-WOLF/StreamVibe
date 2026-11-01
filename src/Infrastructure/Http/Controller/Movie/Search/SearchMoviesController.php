<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Search;

use App\Application\Movie\DTO\Search\Request\SearchMoviesRequest;
use App\Application\Movie\UseCase\Search\SearchMoviesUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for searching movies
 *
 * GET /{_locale}/api/movies/search/{query}/{page}
 *     ?include_adult=&language=&region=&year=&primary_release_year=
 *
 * NOTA (out/2026):
 * - Movido de Controller/Movie/ para Controller/Movie/Search/, espelhando
 *   Application/Movie/{DTO,UseCase}/Search e ApiPlatform/Movie/Search.
 * - Sem #[Route] aqui: a rota real é a operação do API Platform
 *   (SearchMoviesEntryPoint). O #[Route] sem path registrava uma rota
 *   extra em "/".
 * - region, year e primary_release_year chegam ao TMDB (antes eram lidos
 *   aqui e ignorados no UseCase); language é novo. Ano não numérico
 *   responde 400 (InputBag::getInt lança BadRequestException).
 * - A resposta sai pelo auto-mapping (SearchMoviesOutput), não mais pelo
 *   fallback toArray().
 */
#[AsController]
final class SearchMoviesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Search\SearchMoviesUseCase $searchMoviesUseCase
     */
    public function __construct(
        private readonly SearchMoviesUseCase $searchMoviesUseCase,
    ) {}

    public function __invoke(
        string $query,
        int $page,
        Request $request,
    ): JsonResponse {
        // NOTA: a rota aceita query vazia ('query' => '.*') justamente para
        // este 400 com "Required field 'query' is missing" — mensagem que o
        // MovieSearchApiTest::testMovieSearchWithEmptyQuery confere.
        if (trim($query) === "") {
            throw UserValidationException::requiredFieldMissing("query");
        }

        $response = $this->searchMoviesUseCase->execute(
            new SearchMoviesRequest(
                query: $query,
                page: $page,
                includeAdult: $request->query->getBoolean("include_adult"),
                language: $request->query->get("language") ?: null,
                region: $request->query->get("region") ?: null,
                year: $request->query->has("year")
                    ? $request->query->getInt("year")
                    : null,
                primaryReleaseYear: $request->query->has("primary_release_year")
                    ? $request->query->getInt("primary_release_year")
                    : null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
