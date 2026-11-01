<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Image\Sizes;

use App\Application\Image\DTO\Sizes\Request\GetImageSizesRequest;
use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\UseCase\Sizes\GetImageSizesUseCase;
use App\Infrastructure\ApiPlatform\Image\Content\GetImageEntryPoint;
use App\Infrastructure\ApiPlatform\Image\Info\GetImageInfoEntryPoint;
use App\Infrastructure\ApiPlatform\Image\Original\GetOriginalImageEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for listing the supported image sizes and formats
 *
 * GET /{_locale}/api/images/sizes
 *
 * NOTA (out/2026):
 * - Era ImageSizesController em Controller/Image/; virou
 *   GetImageSizesController em Controller/Image/Sizes/.
 * - Monta os usage_examples com o router (generateUrl() a partir dos nomes
 *   das rotas de imagem e do locale da requisição) e os entrega ao
 *   GetImageSizesUseCase. Antes eram strings fixas no UseCase, sem o
 *   /{_locale} — nenhum exemplo funcionava. Os nomes dos exemplos são os
 *   mesmos de antes, mais "original_binary" e "info_w500" (as duas rotas
 *   que não tinham exemplo).
 * - Sem #[Route] e sem try/catch; resposta pelo auto-mapping estrito
 *   (mapToJsonResponse()).
 */
#[AsController]
final class GetImageSizesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Image\UseCase\Sizes\GetImageSizesUseCase $getImageSizesUseCase
     */
    public function __construct(
        private readonly GetImageSizesUseCase $getImageSizesUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $locale = $request->getLocale();

        $response = $this->getImageSizesUseCase->execute(
            new GetImageSizesRequest(
                usageExamples: [
                    'poster_w500' => $this->generateUrl(GetImageEntryPoint::ROUTE_NAME, [
                        '_locale' => $locale,
                        'size' => 'w500',
                        'endpoint' => 'poster.jpg',
                    ]),
                    'backdrop_original' => $this->generateUrl(GetImageEntryPoint::ROUTE_NAME, [
                        '_locale' => $locale,
                        'size' => 'original',
                        'endpoint' => 'backdrop.jpg',
                        'format' => ImageFormat::BINARY,
                    ]),
                    'logo_w185_base64' => $this->generateUrl(GetImageEntryPoint::ROUTE_NAME, [
                        '_locale' => $locale,
                        'size' => 'w185',
                        'endpoint' => 'logo.png',
                        'format' => ImageFormat::BASE64,
                    ]),
                    'profile_h632_url' => $this->generateUrl(GetImageEntryPoint::ROUTE_NAME, [
                        '_locale' => $locale,
                        'size' => 'h632',
                        'endpoint' => 'profile.jpg',
                        'format' => ImageFormat::URL,
                    ]),
                    'original_binary' => $this->generateUrl(GetOriginalImageEntryPoint::ROUTE_NAME, [
                        '_locale' => $locale,
                        'endpoint' => 'poster.jpg',
                    ]),
                    'info_w500' => $this->generateUrl(GetImageInfoEntryPoint::ROUTE_NAME, [
                        '_locale' => $locale,
                        'endpoint' => 'poster.jpg',
                        'size' => 'w500',
                    ]),
                ],
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
