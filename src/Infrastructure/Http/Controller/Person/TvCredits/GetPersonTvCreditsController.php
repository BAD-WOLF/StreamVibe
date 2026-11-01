<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Person\TvCredits;

use App\Application\Person\DTO\TvCredits\Request\GetPersonTvCreditsRequest;
use App\Application\Person\UseCase\TvCredits\GetPersonTvCreditsUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting person TV credits
 *
 * GET /{_locale}/api/person/{personId}/tv/credits?language=
 *
 * NOTA (out/2026): endpoint novo (antes os créditos de TV eram só um
 * placeholder sempre null). Mesma estrutura do
 * GetPersonMovieCreditsController.
 */
#[AsController]
final class GetPersonTvCreditsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Person\UseCase\TvCredits\GetPersonTvCreditsUseCase $getPersonTvCreditsUseCase
     */
    public function __construct(
        private readonly GetPersonTvCreditsUseCase $getPersonTvCreditsUseCase,
    ) {}

    public function __invoke(int $personId, Request $request): JsonResponse
    {
        $response = $this->getPersonTvCreditsUseCase->execute(
            new GetPersonTvCreditsRequest(
                personId: $personId,
                language: $request->query->get("language") ?: null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
