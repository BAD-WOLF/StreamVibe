<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Details;

use App\Application\Movie\DTO\Details\Request\GetMovieDetailsRequest;
use App\Application\Movie\UseCase\Details\GetMovieDetailsUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting movie details
 *
 * GET /{_locale}/api/movies/{movieId}?language=
 *
 * NOTA (out/2026):
 * - Sem #[Route] aqui: a rota real é a operação do API Platform
 *   (GetMovieDetailsEntryPoint). O #[Route] que existia (sem path) registrava uma
 *   rota extra em "/" que ninguém usava.
 * - Sem try/catch: exceção de domínio (validação, não encontrado, falha
 *   do TMDB) segue para o ExceptionListener, que responde com o status
 *   certo (400/404/502/503). O catch (Exception) devolvia 500 para tudo,
 *   com a mensagem crua da exceção.
 * - A resposta sai pelo auto-mapping (mapToJsonResponse -> GetMovieDetailsOutput),
 *   o mesmo modelo que o Swagger documenta.
 * - movieId <= 0 é validado no UseCase (RequestValidator, 400), como nos
 *   outros endpoints.
 */
#[AsController]
final class GetMovieDetailsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Details\GetMovieDetailsUseCase $getMovieDetailsUseCase
     */
    public function __construct(
        private readonly GetMovieDetailsUseCase $getMovieDetailsUseCase,
    ) {}

    public function __invoke(int $movieId, Request $request): JsonResponse
    {
        $response = $this->getMovieDetailsUseCase->execute(
            new GetMovieDetailsRequest(
                movieId: $movieId,
                language: $request->query->get("language") ?: null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
