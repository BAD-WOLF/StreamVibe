<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Image;

use App\Application\Image\UseCase\GetImageUseCase;
use App\Infrastructure\ApiPlatform\Image\ImageEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for image sizes operations
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class ImageSizesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Image\UseCase\GetImageUseCase     $getImageUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(
        private GetImageUseCase $getImageUseCase,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Get supported image sizes and formats
     */
    #[
        Route(
            name: "api_images_sizes",
            defaults: [
                "_api_resource_class" => ImageEntryPoint::class,
                "_api_operation_name" => "get_image_sizes",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Define supported image sizes and formats
            $supportedSizes = [
                "poster" => [
                    "w92",
                    "w154",
                    "w185",
                    "w342",
                    "w500",
                    "w780",
                    "original",
                ],
                "backdrop" => ["w300", "w780", "w1280", "original"],
                "logo" => [
                    "w45",
                    "w92",
                    "w154",
                    "w185",
                    "w300",
                    "w500",
                    "original",
                ],
                "profile" => ["w45", "w185", "h632", "original"],
                "still" => ["w92", "w185", "w300", "original"],
            ];

            $supportedFormats = ["binary", "base64", "url"];

            $responseData = [
                "success" => true,
                "data" => [
                    "supported_sizes" => $supportedSizes,
                    "supported_formats" => $supportedFormats,
                    "cache_enabled" => true,
                    "quality_options" => [
                        "min" => 1,
                        "max" => 100,
                        "default" => 85,
                    ],
                    "usage_examples" => [
                        "poster_w500" => "/api/image/w500/poster.jpg",
                        "backdrop_original" =>
                            "/api/image/original/backdrop.jpg?format=binary",
                        "logo_w185_base64" =>
                            "/api/image/w185/logo.png?format=base64",
                        "profile_h632_url" =>
                            "/api/image/h632/profile.jpg?format=url",
                    ],
                ],
                "message" =>
                    "Supported image sizes and formats retrieved successfully",
            ];

            // TODO: Create ImageSizesResponse DTO and use auto-mapping
            // For now, return structured data directly
            return $this->json($responseData, 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while retrieving image sizes information",
                    ),
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
