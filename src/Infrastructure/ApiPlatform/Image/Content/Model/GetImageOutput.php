<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Image\Content\Model;

use App\Application\Image\DTO\Content\Response\GetImageResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso das rotas de imagem no formato base64
 * (GET /{_locale}/api/image/{size}/{endpoint} e /image/{endpoint} com
 * ?format=base64):
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "endpoint": "/abc.jpg",
 *         "size": "w500",
 *         "format": "base64",
 *         "mime_type": "image/jpeg",
 *         "content_length": 48213,
 *         "url": "https://image.tmdb.org/t/p/w500/abc.jpg",
 *         "base64": "data:image/jpeg;base64,/9j/4AAQ..."
 *       },
 *       "message": "Image retrieved successfully"
 *     }
 *
 * Nos formatos binary e url não há JSON (bytes da imagem e redirect 302;
 * ver AbstractImageController).
 *
 * Mapeado do GetImageResponse pelo auto-mapping (#[AutoMapFromDto] +
 * GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetImageResponse::$message. É esta classe que o controller serializa e
 * que o Swagger documenta.
 *
 * NOTA (out/2026): era Image/Model/ImageOutput, com fieldMap
 * (content → imageData, contentType → mimeType) e `contentType` string
 * obrigatório — mapear uma resposta no formato url (mime null) daria
 * TypeError. O mapper nunca rodava (o JSON real era o toJsonResponse() do
 * DTO, sem `data`). Agora segue o envelope padrão; fromResponse() e
 * error() saíram (ninguém chamava; erro sai do ExceptionListener).
 */
#[AutoMapFromDto(GetImageResponse::class)]
final readonly class GetImageOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Image\DTO\Content\Response\GetImageResponse $data
     * @param string|null                                                  $message
     */
    public function __construct(
        public GetImageResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
