<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Image;

use App\Application\Image\DTO\GetImageRequest;
use App\Application\Image\UseCase\GetImageUseCase;
use App\Domain\Exception\UserValidationException;
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
 * Controller for image information operations
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class ImageInfoController extends AbstractController
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
     * Get image information without content
     */
    #[
        Route(
            name: "api_images_info",
            defaults: [
                "_api_resource_class" => ImageEntryPoint::class,
                "_api_operation_name" => "get_image_info",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(string $endpoint, Request $request): JsonResponse
    {
        $size = $request->query->get("size", "original");

        if (empty($endpoint)) {
            throw UserValidationException::requiredFieldMissing("endpoint");
        }

        // Validate endpoint format
        if (!$this->validateEndpoint($endpoint)) {
            throw UserValidationException::invalidFieldValue(
                "endpoint",
                $endpoint,
                "valid image path",
            );
        }

        try {
            $imageRequest = new GetImageRequest(
                endpoint: "/" . ltrim($endpoint, "/"),
                size: $size,
                format: "url", // Get URL info only, not content
                cache: false,
            );

            // Execute use case - exceptions will be handled by ExceptionListener
            $response = $this->getImageUseCase->execute($imageRequest);

            // Use auto-mapping system to convert DTO to ApiPlatform Output Model
            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while getting image information",
                    ),
                    "message" => $e->getMessage(),
                    "endpoint" => $endpoint,
                ],
                500,
            );
        }
    }

    /**
     * Validate image endpoint format
     */
    private function validateEndpoint(string $endpoint): bool
    {
        // Remove leading slash for validation
        $endpoint = ltrim($endpoint, "/");

        // Must not be empty
        if (empty($endpoint)) {
            return false;
        }

        // Must have valid image extension
        $extension = strtolower(pathinfo($endpoint, PATHINFO_EXTENSION));
        $validExtensions = ["jpg", "jpeg", "png", "webp", "gif"];

        if (!in_array($extension, $validExtensions, true)) {
            return false;
        }

        // Must not contain suspicious characters
        if (preg_match('/[<>"|*?]/', $endpoint)) {
            return false;
        }

        // Must not contain directory traversal
        if (str_contains($endpoint, "..")) {
            return false;
        }

        return true;
    }
}
