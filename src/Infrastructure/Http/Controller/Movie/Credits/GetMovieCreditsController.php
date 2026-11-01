<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Credits;

use App\Application\Movie\DTO\Credits\Request\GetMovieCreditsRequest;
use App\Application\Movie\UseCase\Credits\GetMovieCreditsUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting movie credits (cast and crew)
 *
 * GET /{_locale}/api/movies/{movieId}/credits?language=
 *
 * NOTA (out/2026):
 * - Sem #[Route] aqui: a rota real é a operação do API Platform
 *   (GetMovieCreditsEntryPoint). O #[Route] que existia (sem path) registrava uma
 *   rota extra em "/" que ninguém usava.
 * - Sem try/catch: exceção de domínio (validação, não encontrado, falha
 *   do TMDB) segue para o ExceptionListener, que responde com o status
 *   certo (400/404/502/503). O catch (Exception) devolvia 500 para tudo,
 *   com a mensagem crua da exceção.
 * - A resposta sai pelo auto-mapping (mapToJsonResponse -> GetMovieCreditsOutput),
 *   o mesmo modelo que o Swagger documenta.
 * - movieId <= 0 é validado no UseCase (RequestValidator, 400).
 */
#[AsController]
final class GetMovieCreditsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Credits\GetMovieCreditsUseCase $getMovieCreditsUseCase
     */
    public function __construct(
        private readonly GetMovieCreditsUseCase $getMovieCreditsUseCase,
    ) {}

    public function __invoke(int $movieId, Request $request): JsonResponse
    {
        $response = $this->getMovieCreditsUseCase->execute(
            new GetMovieCreditsRequest(
                movieId: $movieId,
                language: $request->query->get("language") ?: null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
