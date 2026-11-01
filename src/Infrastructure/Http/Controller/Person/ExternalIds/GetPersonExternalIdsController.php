<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Person\ExternalIds;

use App\Application\Person\DTO\ExternalIds\Request\GetPersonExternalIdsRequest;
use App\Application\Person\UseCase\ExternalIds\GetPersonExternalIdsUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting person external IDs
 *
 * GET /{_locale}/api/person/{personId}/external/ids
 *
 * NOTA (out/2026): endpoint novo (antes os ids externos eram só um
 * placeholder sempre null). Mesma estrutura dos outros controllers de
 * pessoa.
 */
#[AsController]
final class GetPersonExternalIdsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Person\UseCase\ExternalIds\GetPersonExternalIdsUseCase $getPersonExternalIdsUseCase
     */
    public function __construct(
        private readonly GetPersonExternalIdsUseCase $getPersonExternalIdsUseCase,
    ) {}

    public function __invoke(int $personId): JsonResponse
    {
        $response = $this->getPersonExternalIdsUseCase->execute(
            new GetPersonExternalIdsRequest(
                personId: $personId,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
