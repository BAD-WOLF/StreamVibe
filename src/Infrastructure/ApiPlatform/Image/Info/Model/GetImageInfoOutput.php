<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Image\Info\Model;

use App\Application\Image\DTO\Info\Response\GetImageInfoResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/images/info/{endpoint}:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "endpoint": "/abc.jpg",
 *         "size": "w500",
 *         "url": "https://image.tmdb.org/t/p/w500/abc.jpg",
 *         "file_extension": "jpg"
 *       },
 *       "message": "Image information retrieved successfully"
 *     }
 *
 * Mapeado do GetImageInfoResponse pelo auto-mapping (#[AutoMapFromDto] +
 * GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetImageInfoResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado com o GetImageInfoResponse. Antes a rota
 * declarava o ImageOutput (das imagens base64) no Swagger e devolvia o
 * toArray() do GetImageResponse.
 */
#[AutoMapFromDto(GetImageInfoResponse::class)]
final readonly class GetImageInfoOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Image\DTO\Info\Response\GetImageInfoResponse $data
     * @param string|null                                                   $message
     */
    public function __construct(
        public GetImageInfoResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
