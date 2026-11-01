<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Person\Details;

use App\Application\Person\DTO\Details\Request\GetPersonDetailsRequest;
use App\Application\Person\UseCase\Details\GetPersonDetailsUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting person details
 *
 * GET /{_locale}/api/person/{personId}/details?language=
 *
 * NOTA (out/2026):
 * - Era PersonDetailsController em Controller/Person/; virou
 *   GetPersonDetailsController em Controller/Person/Details/, espelhando
 *   Application/Person/{DTO,UseCase}/Details e o módulo Movie.
 * - O comentário antigo citava a rota /person/{id}/movie-credits; as rotas
 *   reais são /person/{personId}/details e /person/{personId}/movie/credits
 *   (com barra, como as novas tv/credits e external/ids).
 * - Sem #[Route] (registrava uma rota extra em "/"; a rota real é a
 *   operação do API Platform, GetPersonDetailsEntryPoint) e sem try/catch:
 *   o catch (Exception) devolvia 500 com a mensagem crua da exceção, e o
 *   status 400 era usado para qualquer falha (até pessoa inexistente).
 *   Agora a exceção segue para o ExceptionListener (400/404/502/503).
 * - personId <= 0 é validado no UseCase (RequestValidator), como nos
 *   endpoints de filme. `region` saiu (o TMDB não aceita esse parâmetro em
 *   pessoa); `language` chega ao TMDB.
 * - A resposta sai pelo auto-mapping (GetPersonDetailsOutput), não mais do
 *   fallback toArray().
 */
#[AsController]
final class GetPersonDetailsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Person\UseCase\Details\GetPersonDetailsUseCase $getPersonDetailsUseCase
     */
    public function __construct(
        private readonly GetPersonDetailsUseCase $getPersonDetailsUseCase,
    ) {}

    public function __invoke(int $personId, Request $request): JsonResponse
    {
        $response = $this->getPersonDetailsUseCase->execute(
            new GetPersonDetailsRequest(
                personId: $personId,
                language: $request->query->get("language") ?: null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
