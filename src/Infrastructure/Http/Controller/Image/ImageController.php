<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Image;

use App\Application\Image\DTO\GetImageRequest;
use App\Application\Image\UseCase\GetImageUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Image\ImageEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for image operations
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class ImageController extends AbstractController
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
     * Get TMDB image by size and endpoint
     */
    #[
        Route(
            name: "api_image_with_size",
            defaults: [
                "_api_resource_class" => ImageEntryPoint::class,
                "_api_operation_name" => "get_image_with_size",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(
        string $size,
        string $endpoint,
        Request $request,
    ): Response {
        try {
            // Validate endpoint format
            if (!$this->validateEndpoint($endpoint)) {
                throw UserValidationException::invalidFieldValue(
                    "endpoint",
                    $endpoint,
                    "valid image path",
                );
            }

            // Get query parameters
            $format = $request->query->get("format", "base64");
            $quality = $request->query->get("quality");
            $cache = $request->query->getBoolean("cache", true);

            // Validate format
            if (!in_array($format, ["base64", "binary", "url"], true)) {
                throw UserValidationException::invalidFieldValue(
                    "format",
                    $format,
                    "base64, binary, or url",
                );
            }

            // Create image request
            $imageRequest = new GetImageRequest(
                endpoint: "/" . ltrim($endpoint, "/"),
                size: $size,
                format: $format,
                cache: $cache,
                quality: $quality ? (int) $quality : null,
            );

            // Execute use case - exceptions will be handled by the ExceptionListener
            $response = $this->getImageUseCase->execute($imageRequest);

            // Handle different response formats
            return $this->handleImageResponse($response, $format, $cache);
        } catch (Exception $e) {
            // For JSON responses with auto-mapping when possible
            if ($this->shouldReturnJsonError($request)) {
                return $this->json(
                    [
                        "success" => false,
                        "error" => $this->translator->trans(
                            "An error occurred while processing the image",
                        ),
                        "message" => $e->getMessage(),
                        "endpoint" => $endpoint,
                        "size" => $size,
                    ],
                    500,
                );
            }

            // For binary/redirect responses, return simple error response
            return new Response("Image not found or processing error", 404, [
                "Content-Type" => "text/plain",
            ]);
        }
    }

    /**
     * Handle different types of image responses
     */
    private function handleImageResponse(
        object $response,
        string $format,
        bool $cache,
    ): Response {
        // Handle URL format - redirect
        if ($format === "url" && method_exists($response, "getUrl")) {
            $url = $response->getUrl();
            if ($url) {
                return $this->redirect($url, 302);
            }
        }

        // Handle binary format
        if ($format === "binary" && method_exists($response, "getImageData")) {
            $imageData = $response->getImageData();
            $mimeType = method_exists($response, "getMimeType")
                ? $response->getMimeType()
                : "application/octet-stream";

            $headers = [
                "Content-Type" => $mimeType,
                "Cache-Control" => $cache
                    ? "public, max-age=86400"
                    : "no-cache",
            ];

            if (method_exists($response, "getSize") && $response->getSize()) {
                $headers["Content-Length"] = (string) $response->getSize();
            }

            return new Response($imageData, 200, $headers);
        }

        // Handle JSON format (base64 or fallback) - try auto-mapping
        if ($this->canMapDto($response)) {
            return $this->mapToJsonResponse($response, 200);
        }

        // Fallback to traditional JSON response
        if (method_exists($response, "toJsonResponse")) {
            return $this->json($response->toJsonResponse());
        }

        if (method_exists($response, "toArray")) {
            return $this->json($response->toArray());
        }

        // Last resort - serialize the response object
        return $this->json([
            "success" => method_exists($response, "isSuccess")
                ? $response->isSuccess()
                : true,
            "data" => $response,
        ]);
    }

    /**
     * Determine if we should return JSON error response
     */
    private function shouldReturnJsonError(Request $request): bool
    {
        // Check Accept header
        $acceptHeader = $request->headers->get("Accept", "");
        if (str_contains($acceptHeader, "application/json")) {
            return true;
        }

        // Check format parameter
        $format = $request->query->get("format", "base64");
        return $format === "base64";
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
