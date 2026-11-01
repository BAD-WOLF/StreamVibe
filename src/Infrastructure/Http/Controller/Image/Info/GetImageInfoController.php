<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Image\Info;

use App\Application\Image\DTO\Info\Request\GetImageInfoRequest;
use App\Application\Image\Shared\ImageSize;
use App\Application\Image\UseCase\Info\GetImageInfoUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting image information (URL and extension) without
 * downloading it
 *
 * GET /{_locale}/api/images/info/{endpoint}?size=
 *
 * NOTA (out/2026):
 * - Era ImageInfoController em Controller/Image/; virou
 *   GetImageInfoController em Controller/Image/Info/, com UseCase próprio
 *   (GetImageInfoUseCase) em vez de reaproveitar o GetImageUseCase com
 *   format=url.
 * - Sem #[Route], sem try/catch (500 com a mensagem crua) e sem o
 *   validateEndpoint() próprio: caminho e tamanho são validados no
 *   GetImageInfoRequest (400).
 * - A resposta sai pelo auto-mapping (GetImageInfoOutput), não mais do
 *   fallback toArray().
 */
#[AsController]
final class GetImageInfoController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Image\UseCase\Info\GetImageInfoUseCase $getImageInfoUseCase
     */
    public function __construct(
        private readonly GetImageInfoUseCase $getImageInfoUseCase,
    ) {}

    public function __invoke(string $endpoint, Request $request): JsonResponse
    {
        $response = $this->getImageInfoUseCase->execute(
            new GetImageInfoRequest(
                endpoint: '/' . ltrim($endpoint, '/'),
                size: $request->query->getString('size', ImageSize::ORIGINAL),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
