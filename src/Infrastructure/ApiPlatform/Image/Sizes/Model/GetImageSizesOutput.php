<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Image\Sizes\Model;

use App\Application\Image\DTO\Sizes\Response\GetImageSizesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/images/sizes:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "supported_sizes": {"poster": ["w92", "w154", ...], "backdrop": [...], ...},
 *         "supported_formats": ["binary", "base64", "url"],
 *         "cache_enabled": true,
 *         "quality_options": {"min": 1, "max": 100},
 *         "usage_examples": {"poster_w500": "/pt_BR/api/image/w500/poster.jpg", ...}
 *       },
 *       "message": "Supported image sizes and formats retrieved successfully"
 *     }
 *
 * Mapeado do GetImageSizesResponse pelo auto-mapping (#[AutoMapFromDto] +
 * GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetImageSizesResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Image/Model/ImageSizesOutput. O envelope é o
 * mesmo; `success` virou fixo (true) e saíram fromResponse(), isSuccess(),
 * getData(), getMessage() e toArray() (ninguém chamava). A NOTA antiga
 * sobre "getters sem #[Ignore]" deixou de se aplicar junto com os
 * getters.
 */
#[AutoMapFromDto(GetImageSizesResponse::class)]
final readonly class GetImageSizesOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Image\DTO\Sizes\Response\GetImageSizesResponse $data
     * @param string|null                                                     $message
     */
    public function __construct(
        public GetImageSizesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
