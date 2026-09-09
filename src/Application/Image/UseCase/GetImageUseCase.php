<?php

declare(strict_types=1);

namespace App\Application\Image\UseCase;

use App\Application\Image\DTO\GetImageRequest;
use App\Application\Image\DTO\GetImageResponse;
use App\Domain\Exception\UserValidationException;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\ExternalServiceException;
use App\Domain\Exception\RateLimitExceededException;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 *
 */
final readonly class GetImageUseCase
{
    /**
     * @param \App\Infrastructure\ExternalServices\TmdbApiService       $tmdbApiService
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Psr\Log\LoggerInterface                                  $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface        $translator
     */
    public function __construct(
        private TmdbApiService $tmdbApiService,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @param \App\Application\Image\DTO\GetImageRequest $request
     *
     * @return \App\Application\Image\DTO\GetImageResponse
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\ValidationException
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function execute(GetImageRequest $request): GetImageResponse
    {
        // Validate input
        $violations = $this->validator->validate($request);
        if (count($violations) > 0) {
            $this->logger->warning("Image request validation failed", [
                "endpoint" => $request->getEndpoint(),
                "size" => $request->getSize(),
                "violations_count" => count($violations),
            ]);

            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            throw UserValidationException::multipleValidationErrors(
                array_combine(range(0, count($errors) - 1), $errors),
            )->withContext([
                "endpoint" => $request->getEndpoint(),
                "size" => $request->getSize(),
            ]);
        }

        // Validate image extension
        if (!$request->isValidImageExtension()) {
            $this->logger->warning("Unsupported image format requested", [
                "endpoint" => $request->getEndpoint(),
                "detected_extension" => pathinfo(
                    $request->getEndpoint(),
                    PATHINFO_EXTENSION,
                ),
            ]);

            throw BusinessLogicException::invalidOperation(
                sprintf(
                    "Unsupported image format for endpoint: %s",
                    $request->getEndpoint(),
                ),
                $this->translator->trans("Unsupported image format"),
            )->withContext([
                "endpoint" => $request->getEndpoint(),
                "detected_extension" => pathinfo(
                    $request->getEndpoint(),
                    PATHINFO_EXTENSION,
                ),
            ]);
        }

        // Handle URL format (just return the TMDB URL)
        if ($request->isUrlFormat()) {
            return GetImageResponse::url(
                $request->buildTmdbUrl(),
                $this->translator->trans("Image URL generated successfully"),
            );
        }

        try {
            // Fetch image from TMDB
            $imageContent = $this->tmdbApiService->getImage(
                $request->getSize(),
                $request->getEndpoint(),
            );

            if (empty($imageContent)) {
                $this->logger->error("Empty image content received from TMDB", [
                    "endpoint" => $request->getEndpoint(),
                    "size" => $request->getSize(),
                ]);

                throw BusinessLogicException::notFound(
                    "Image",
                    $request->getEndpoint(),
                );
            }

            // Detect MIME type
            $mimeType = $this->detectMimeType($imageContent);
            if (!$mimeType) {
                $this->logger->error("Could not detect image MIME type", [
                    "endpoint" => $request->getEndpoint(),
                    "size" => $request->getSize(),
                    "content_length" => strlen($imageContent),
                    "content_preview" => substr($imageContent, 0, 50),
                ]);

                throw BusinessLogicException::invalidOperation(
                    "MIME type detection failed",
                    $this->translator->trans("Invalid image format detected"),
                )->withContext([
                    "endpoint" => $request->getEndpoint(),
                    "size" => $request->getSize(),
                    "content_length" => strlen($imageContent),
                    "content_preview" => substr($imageContent, 0, 50),
                ]);
            }

            // Validate that it's actually an image
            if (!$this->isValidImageMimeType($mimeType)) {
                $this->logger->error("Invalid image MIME type detected", [
                    "endpoint" => $request->getEndpoint(),
                    "detected_mime_type" => $mimeType,
                    "allowed_mime_types" => [
                        "image/jpeg",
                        "image/png",
                        "image/webp",
                        "image/gif",
                    ],
                    "content_length" => strlen($imageContent),
                ]);

                throw BusinessLogicException::invalidOperation(
                    "Invalid image MIME type",
                    $this->translator->trans("Content is not a valid image"),
                )->withContext([
                    "endpoint" => $request->getEndpoint(),
                    "detected_mime_type" => $mimeType,
                    "allowed_mime_types" => [
                        "image/jpeg",
                        "image/png",
                        "image/webp",
                        "image/gif",
                    ],
                    "content_length" => strlen($imageContent),
                ]);
            }

            // Process image based on format
            $processedData = $this->processImageContent(
                $imageContent,
                $request->getFormat(),
                $request->getQuality(),
            );

            // Generate metadata
            $metadata = $this->generateImageMetadata(
                $imageContent,
                $mimeType,
                $request,
            );

            $this->logger->info("Image retrieved successfully", [
                "endpoint" => $request->getEndpoint(),
                "size" => $request->getSize(),
                "format" => $request->getFormat(),
                "mime_type" => $mimeType,
                "content_length" => strlen($imageContent),
            ]);

            return GetImageResponse::success(
                imageData: $processedData,
                mimeType: $mimeType,
                format: $request->getFormat(),
                size: strlen($imageContent),
                url: $request->buildTmdbUrl(),
                metadata: $metadata,
                message: $this->translator->trans(
                    "Image retrieved successfully",
                ),
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error("Client error fetching image from TMDB", [
                "endpoint" => $request->getEndpoint(),
                "size" => $request->getSize(),
                "error" => $e->getMessage(),
                "error_class" => get_class($e),
                "trace" => $e->getTraceAsString(),
            ]);

            if (
                str_contains($e->getMessage(), "429") ||
                str_contains($e->getMessage(), "rate limit")
            ) {
                throw RateLimitExceededException::apiKeyLimitExceeded(
                    "TMDB_API_KEY",
                    100,
                    "hour",
                )->withContext([
                    "endpoint" => $request->getEndpoint(),
                    "size" => $request->getSize(),
                    "service" => "TMDB Image API",
                ]);
            }

            throw ExternalServiceException::clientError(
                "TMDB Image API",
                $e->getMessage(),
                [
                    "endpoint" => $request->getEndpoint(),
                    "size" => $request->getSize(),
                ],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error("Server error fetching image from TMDB", [
                "endpoint" => $request->getEndpoint(),
                "size" => $request->getSize(),
                "error" => $e->getMessage(),
                "error_class" => get_class($e),
                "trace" => $e->getTraceAsString(),
            ]);

            throw ExternalServiceException::serverError(
                "TMDB Image API",
                $e->getMessage(),
                [
                    "endpoint" => $request->getEndpoint(),
                    "size" => $request->getSize(),
                ],
            );
        } catch (Exception $e) {
            $this->logger->error("Unexpected error fetching image", [
                "endpoint" => $request->getEndpoint(),
                "size" => $request->getSize(),
                "error" => $e->getMessage(),
                "error_class" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "memory_usage" => memory_get_usage(true),
                "peak_memory" => memory_get_peak_usage(true),
            ]);

            throw new RuntimeException(
                $this->translator->trans(
                    "An error occurred while fetching the image",
                ),
                500,
                $e,
            );
        }
    }

    /**
     * Process multiple image requests (batch processing)
     */
    public function executeBatch(array $requests): array
    {
        $responses = [];

        foreach ($requests as $index => $request) {
            if (!$request instanceof GetImageRequest) {
                $this->logger->warning(
                    "Invalid request type in batch operation",
                    [
                        "index" => $index,
                        "expected_type" => GetImageRequest::class,
                        "actual_type" => get_class($request),
                    ],
                );

                $responses[$index] = GetImageResponse::failure(
                    $this->translator->trans(
                        "Invalid request at index {index}",
                        [
                            "index" => $index,
                        ],
                    ),
                );
                continue;
            }

            $responses[$index] = $this->execute($request);
        }

        return $responses;
    }

    /**
     * Get image with fallback options
     */
    public function executeWithFallback(
        GetImageRequest $primaryRequest,
        array $fallbackRequests = [],
    ): GetImageResponse {
        // Try primary request first
        $response = $this->execute($primaryRequest);

        if ($response->isSuccess()) {
            return $response;
        }

        // Try fallback requests
        foreach ($fallbackRequests as $fallbackRequest) {
            if (!$fallbackRequest instanceof GetImageRequest) {
                continue;
            }

            $fallbackResponse = $this->execute($fallbackRequest);
            if ($fallbackResponse->isSuccess()) {
                return $fallbackResponse;
            }
        }

        // If all failed, use fallback URL if provided
        if ($primaryRequest->getFallbackUrl()) {
            return GetImageResponse::url(
                $primaryRequest->getFallbackUrl(),
                $this->translator->trans("Using fallback image URL"),
            );
        }

        return $response; // Return original error
    }

    /**
     * Detect MIME type from image content
     */
    private function detectMimeType(string $content): ?string
    {
        if (function_exists("finfo_buffer")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $content);
            finfo_close($finfo);

            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        // Fallback: detect by content header
        if (str_starts_with($content, "\xFF\xD8\xFF")) {
            return "image/jpeg";
        }

        if (str_starts_with($content, "\x89PNG\x0D\x0A\x1A\x0A")) {
            return "image/png";
        }

        if (
            str_starts_with($content, "GIF87a") ||
            str_starts_with($content, "GIF89a")
        ) {
            return "image/gif";
        }

        if (
            str_starts_with($content, "RIFF") &&
            str_contains($content, "WEBP")
        ) {
            return "image/webp";
        }

        return null;
    }

    /**
     * Validate if MIME type is a supported image format
     */
    private function isValidImageMimeType(string $mimeType): bool
    {
        $validTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/webp",
            "image/svg+xml",
        ];

        return in_array($mimeType, $validTypes, true);
    }

    /**
     * Process image content based on requested format
     */
    private function processImageContent(
        string $content,
        string $format,
        ?int $quality = null,
    ): string {
        if ($format === "base64") {
            return base64_encode($content);
        }

        if ($format === "binary") {
            // Apply quality compression if requested and content is JPEG
            if ($quality !== null && $quality < 100) {
                return $this->compressImage($content, $quality);
            }
        }

        return $content;
    }

    /**
     * Compress image with specified quality (JPEG only)
     */
    private function compressImage(string $content, int $quality): string
    {
        try {
            $image = imagecreatefromstring($content);
            if ($image === false) {
                return $content; // Return original if compression fails
            }

            ob_start();
            imagejpeg($image, null, $quality);
            $compressed = ob_get_clean();
            imagedestroy($image);

            return $compressed ?: $content;
        } catch (Exception $e) {
            $this->logger->warning("Image compression failed", [
                "error" => $e->getMessage(),
                "quality" => $quality,
            ]);

            return $content;
        }
    }

    /**
     * Generate image metadata
     */
    private function generateImageMetadata(
        string $content,
        string $mimeType,
        GetImageRequest $request,
    ): array {
        $metadata = [
            "original_endpoint" => $request->getEndpoint(),
            "requested_size" => $request->getSize(),
            "effective_size" => $request->getEffectiveSize(),
            "tmdb_url" => $request->buildTmdbUrl(),
            "content_length" => strlen($content),
            "mime_type" => $mimeType,
            "file_extension" => $this->getExtensionFromMimeType($mimeType),
        ];

        // Try to get image dimensions
        try {
            $imageInfo = getimagesizefromstring($content);
            if ($imageInfo !== false) {
                $metadata["width"] = $imageInfo[0];
                $metadata["height"] = $imageInfo[1];
                $metadata["aspect_ratio"] = round(
                    $imageInfo[0] / $imageInfo[1],
                    3,
                );
            }
        } catch (Exception $e) {
            $this->logger->debug("Could not get image dimensions", [
                "error" => $e->getMessage(),
            ]);
        }

        return $metadata;
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): ?string
    {
        return match ($mimeType) {
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
            "image/webp" => "webp",
            "image/svg+xml" => "svg",
            default => null,
        };
    }

    /**
     * Validate image endpoint format
     */
    public function validateEndpoint(string $endpoint): bool
    {
        // Must start with /
        if (!str_starts_with($endpoint, "/")) {
            return false;
        }

        // Must have valid image extension
        $extension = strtolower(pathinfo($endpoint, PATHINFO_EXTENSION));
        $validExtensions = ["jpg", "jpeg", "png", "webp", "gif"];

        return in_array($extension, $validExtensions, true);
    }

    /**
     * Get supported image sizes
     */
    public function getSupportedSizes(): array
    {
        return [
            "w92" => "92px width (poster thumbnail)",
            "w154" => "154px width (poster small)",
            "w185" => "185px width (profile/poster medium)",
            "w342" => "342px width (poster large)",
            "w500" => "500px width (poster extra large)",
            "w780" => "780px width (backdrop medium)",
            "w1280" => "1280px width (backdrop large)",
            "h632" => "632px height (backdrop)",
            "original" => "Original size",
        ];
    }

    /**
     * Get supported formats
     */
    public function getSupportedFormats(): array
    {
        return [
            "base64" => "Base64 encoded string with data URI prefix",
            "binary" => "Raw binary image data",
            "url" => "Direct TMDB image URL",
        ];
    }
}
