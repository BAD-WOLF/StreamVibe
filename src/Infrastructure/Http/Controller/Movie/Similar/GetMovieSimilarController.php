<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Similar;

use App\Application\Movie\DTO\Similar\Request\GetMovieSimilarRequest;
use App\Application\Movie\UseCase\Similar\GetMovieSimilarUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting similar movies
 *
 * GET /{_locale}/api/movies/{movieId}/similar?page=&language=
 *
 * NOTA (out/2026):
 * - Sem #[Route] aqui: a rota real é a operação do API Platform
 *   (GetMovieSimilarEntryPoint). O #[Route] que existia (sem path) registrava uma
 *   rota extra em "/" que ninguém usava.
 * - Sem try/catch: exceção de domínio (validação, não encontrado, falha
 *   do TMDB) segue para o ExceptionListener, que responde com o status
 *   certo (400/404/502/503). O catch (Exception) devolvia 500 para tudo,
 *   com a mensagem crua da exceção.
 * - A resposta sai pelo auto-mapping (mapToJsonResponse -> GetMovieSimilarOutput),
 *   o mesmo modelo que o Swagger documenta.
 * - Aceita ?page= (antes a lista era sempre a página 1 do TMDB).
 */
#[AsController]
final class GetMovieSimilarController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Similar\GetMovieSimilarUseCase $getMovieSimilarUseCase
     */
    public function __construct(
        private readonly GetMovieSimilarUseCase $getMovieSimilarUseCase,
    ) {}

    public function __invoke(int $movieId, Request $request): JsonResponse
    {
        $response = $this->getMovieSimilarUseCase->execute(
            new GetMovieSimilarRequest(
                movieId: $movieId,
                page: $request->query->getInt("page", 1),
                language: $request->query->get("language") ?: null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
