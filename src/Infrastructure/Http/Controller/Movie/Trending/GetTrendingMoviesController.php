<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Trending;

use App\Application\Movie\DTO\Trending\Request\GetTrendingMoviesRequest;
use App\Application\Movie\UseCase\Trending\GetTrendingMoviesUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting trending movies
 *
 * GET /{_locale}/api/movies/trending/{timeWindow}?page=&language=
 *
 * NOTA (out/2026):
 * - Bug corrigido: o time window vinha de ?time_window= (padrão "week") e
 *   o {timeWindow} da própria rota era ignorado — /movies/trending/day
 *   devolvia a tendência da SEMANA. Agora o valor vem da rota (que já
 *   restringe a day|week).
 * - Era TrendingMoviesController em Controller/Movie/; virou
 *   GetTrendingMoviesController em Controller/Movie/Trending/, espelhando
 *   o UseCase e a operação do API Platform (GetTrendingMoviesEntryPoint).
 * - Sem #[Route] (a rota é a operação do API Platform) e sem try/catch: o
 *   catch (Exception) devolvia 500 para qualquer erro, inclusive os do
 *   TMDB, que agora saem como 502/503 pelo ExceptionListener.
 * - Página inválida responde 400 (antes era corrigida para 1 em silêncio).
 */
#[AsController]
final class GetTrendingMoviesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Trending\GetTrendingMoviesUseCase $getTrendingMoviesUseCase
     */
    public function __construct(
        private readonly GetTrendingMoviesUseCase $getTrendingMoviesUseCase,
    ) {}

    public function __invoke(string $timeWindow, Request $request): JsonResponse
    {
        $response = $this->getTrendingMoviesUseCase->execute(
            new GetTrendingMoviesRequest(
                timeWindow: $timeWindow,
                page: $request->query->getInt("page", 1),
                language: $request->query->get("language") ?: null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
