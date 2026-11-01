<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Person\Images;

use App\Application\Person\DTO\Images\Request\GetPersonImagesRequest;
use App\Application\Person\UseCase\Images\GetPersonImagesUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting person images
 *
 * GET /{_locale}/api/person/{personId}/images
 *
 * NOTA (out/2026): endpoint novo (antes as imagens de pessoa eram só um
 * placeholder sempre null). Mesma estrutura dos outros controllers de
 * pessoa; sem ?language= porque o TMDB não tem esse filtro para fotos de
 * pessoa.
 */
#[AsController]
final class GetPersonImagesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Person\UseCase\Images\GetPersonImagesUseCase $getPersonImagesUseCase
     */
    public function __construct(
        private readonly GetPersonImagesUseCase $getPersonImagesUseCase,
    ) {}

    public function __invoke(int $personId): JsonResponse
    {
        $response = $this->getPersonImagesUseCase->execute(
            new GetPersonImagesRequest(
                personId: $personId,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
