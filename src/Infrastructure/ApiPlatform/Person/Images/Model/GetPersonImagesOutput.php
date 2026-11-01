<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\Images\Model;

use App\Application\Person\DTO\Images\Response\GetPersonImagesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/person/{personId}/images:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "person_id": 287,
 *         "profiles": [{"file_path": "/...jpg", "width": 1000, "height": 1500, "url_h632": "https://...", ...}]
 *       },
 *       "message": "Person images retrieved successfully"
 *     }
 *
 * Mapeado do GetPersonImagesResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetPersonImagesResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado com o endpoint de imagens de pessoa (antes só um
 * placeholder sempre null). Espelha o GetMovieImagesOutput.
 */
#[AutoMapFromDto(GetPersonImagesResponse::class)]
final readonly class GetPersonImagesOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Person\DTO\Images\Response\GetPersonImagesResponse $data
     * @param string|null                                                         $message
     */
    public function __construct(
        public GetPersonImagesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
