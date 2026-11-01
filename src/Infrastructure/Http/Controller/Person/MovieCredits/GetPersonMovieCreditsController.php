<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Person\MovieCredits;

use App\Application\Person\DTO\MovieCredits\Request\GetPersonMovieCreditsRequest;
use App\Application\Person\UseCase\MovieCredits\GetPersonMovieCreditsUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting person movie credits
 *
 * GET /{_locale}/api/person/{personId}/movie/credits?language=
 *
 * NOTA (out/2026):
 * - Era PersonMovieCreditsController em Controller/Person/; virou
 *   GetPersonMovieCreditsController em Controller/Person/MovieCredits/,
 *   espelhando Application/Person/{DTO,UseCase}/MovieCredits.
 * - Sem #[Route] (registrava uma rota extra em "/") e sem try/catch (o
 *   catch (Exception) devolvia 500 com a mensagem crua da exceção, e
 *   qualquer falha do UseCase saía com status 400). Agora a exceção segue
 *   para o ExceptionListener (400/404/502/503).
 * - Aceita ?language=, repassado ao TMDB.
 * - A resposta sai pelo auto-mapping (GetPersonMovieCreditsOutput).
 */
#[AsController]
final class GetPersonMovieCreditsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Person\UseCase\MovieCredits\GetPersonMovieCreditsUseCase $getPersonMovieCreditsUseCase
     */
    public function __construct(
        private readonly GetPersonMovieCreditsUseCase $getPersonMovieCreditsUseCase,
    ) {}

    public function __invoke(int $personId, Request $request): JsonResponse
    {
        $response = $this->getPersonMovieCreditsUseCase->execute(
            new GetPersonMovieCreditsRequest(
                personId: $personId,
                language: $request->query->get("language") ?: null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
