<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Popular;

use App\Application\Movie\DTO\Popular\Request\GetPopularMoviesRequest;
use App\Application\Movie\UseCase\Popular\GetPopularMoviesUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting popular movies
 *
 * GET /{_locale}/api/movies/popular?page=&language=
 *
 * NOTA (out/2026):
 * - Sem #[Route] aqui: a rota real é a operação do API Platform
 *   (GetPopularMoviesEntryPoint). O #[Route] que existia (sem path) registrava uma
 *   rota extra em "/" que ninguém usava.
 * - Sem try/catch: exceção de domínio (validação, não encontrado, falha
 *   do TMDB) segue para o ExceptionListener, que responde com o status
 *   certo (400/404/502/503). O catch (Exception) devolvia 500 para tudo,
 *   com a mensagem crua da exceção.
 * - A resposta sai pelo auto-mapping (mapToJsonResponse -> GetPopularMoviesOutput),
 *   o mesmo modelo que o Swagger documenta.
 * - Página inválida (0, -1, > 500) agora responde 400; antes era
 *   corrigida em silêncio para 1 (max(1, $page)).
 */
#[AsController]
final class GetPopularMoviesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Popular\GetPopularMoviesUseCase $getPopularMoviesUseCase
     */
    public function __construct(
        private readonly GetPopularMoviesUseCase $getPopularMoviesUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = $this->getPopularMoviesUseCase->execute(
            new GetPopularMoviesRequest(
                page: $request->query->getInt("page", 1),
                language: $request->query->get("language") ?: null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
